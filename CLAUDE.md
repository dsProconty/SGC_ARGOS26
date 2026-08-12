# SGC ARGOS — Notas para Claude Code

## Regla obligatoria: incrementar VERSION en cada push a producción

El archivo `VERSION` en la raíz contiene un número (`1.1`, `1.2`, ...) que se
muestra como una marca de agua pequeña en la esquina inferior izquierda de
`index.php` (login) y `main.php` (app), para que el usuario pueda confirmar
a simple vista que un despliegue llegó a producción sin tener que probar
ninguna funcionalidad.

**Antes de cada `git push` a una rama de despliegue** (`claude/session-*`,
`nuevas-funcionalidades-v2`, `feature/nuevas-funcionalidades`), incrementa
el número menor en `VERSION` (`1.1` → `1.2` → `1.3` ...) e inclúyelo en el
mismo commit que el resto de los cambios. No se salta nunca, incluso para
cambios triviales — es la única forma de que la marca de versión sirva como
señal confiable de que el deploy ocurrió.

## Infraestructura del repo (para no repetir investigación)

- **Rama de producción real**: `feature/nuevas-funcionalidades`. El servidor
  en HostPapa corre `_pull.php` (webhook) que hace
  `git fetch origin && git reset --hard origin/feature/nuevas-funcionalidades`
  — o sea, todo lo que se pushea a esa rama llega a producción en el próximo
  pull, sin revisión intermedia. `nuevas-funcionalidades-v2` existió como
  rama separada pero ya está unificada (0 commits de diferencia) — no usarla,
  es historia, no una rama activa distinta.
- **Módulo admin real es "Clientes"** (`pages/clientes/view.php` +
  `ajax/clientes/clientes.php`), NO "Convenios" (`convenio/view.php` /
  `ajax/convenio/convenio.php` existen pero no están enlazados en ningún
  menú ni ruta de `content.php` — es código muerto, no tocar salvo que se
  vuelva a enlazar deliberadamente).
- El árbol `shared/` en la raíz es una copia vieja sin usar — `content.php`
  incluye siempre desde `pages/`, nunca desde `shared/pages/`. Ignorar
  `shared/` al buscar o editar código real.
- **Producción corre PHP anterior a 7.1** (ver comentario en `env.php`).
  Código PHP nuevo debe evitar: type hints escalares/de retorno en firmas de
  función, `list()` corto con claves, `str_contains`/`str_starts_with`/
  `str_ends_with`, arrow functions, `match`, argumentos con nombre, `?->`.
  El operador `??` sí es seguro (usado en todo el código existente).
- **No hay framework de tests** (sin PHPUnit, sin carpeta `tests/`). La
  verificación se hace con la CLI de `mysql` contra la BD local
  (`sgipro_sgc_argos`, XAMPP, usuario `root` sin password), scripts PHP CLI
  desechables, y QA manual en `http://localhost/SGC_ARGOS26/`. Nunca usar
  contraseñas/sesiones de usuarios reales para probar — crear cuentas de
  prueba desechables (`usuario` con contraseña propia) y borrarlas al final.

## Feature reciente: Cupos globales y diferenciados por marca (CU-01)

Implementada y desplegada a producción entre las versiones **5.7 → 6.2**
(agosto 2026). Un convenio (`cliente` con `cli_tipo_beneficio = 'Cupo'`)
ahora puede elegir entre dos modos, guardado en `cliente.cli_modo_cupo`:

- **`global`** (default, comportamiento de siempre): un solo cupo
  (`cliente.cli_valor_beneficio`) que el empleado gasta libremente entre
  todas las marcas del sistema (Pizza Hut, Fridays, etc. — tabla `marca`).
- **`marca`**: el cupo se reparte en montos independientes por marca, sin
  fungibilidad entre ellas. El convenio define un tope máximo por marca en
  `cliente_cupo_marca` (`cli_id`, `mar_id`, `ccm_monto_max`); cada empleado
  tiene su propio cupo por marca en `personal_cupo_marca` (`per_id`, `mar_id`,
  `pcm_asignado`, `pcm_disponible`). Ausencia de fila = cupo 0 en esa marca
  (se rechaza el pago, no se trata como "ilimitado" — este fue un bug real
  que se repitió varias veces durante la implementación y terminó
  extraído a un helper compartido, ver abajo).

**Dónde vive la lógica:**
- `helpers/cupo_marca_helpers.php` — toda la lógica de lectura/escritura de
  cupo por marca (`cupoObtenerModo`, `cupoMaximosPorMarca`,
  `cupoMarcasActivas`, `cupoMarcaDeLocal`, `cupoEmpleadoEnMarca`,
  `cupoEmpleadoPorMarca`, `cupoUpsertEmpleadoMarca`,
  `cupoDescontarEmpleadoMarca`, `cupoDevolverEmpleadoMarca`,
  `cupoGuardarMaximosPorMarca`, `cupoValidarPorMarca`). Cualquier lugar
  nuevo que necesite tocar cupo por marca debe reusar estas funciones, no
  reimplementar la validación (ya se repitió sin querer 3 veces antes de
  centralizarse en `cupoValidarPorMarca`).
- `js/cupo_marca_shared.js` — render/lectura de inputs de cupo por marca en
  el navegador, compartido entre `pages/clientes/view.php` y
  `pages/portal_empresa/view.php` (cada página mantiene sus propios nombres
  de función como wrappers finos sobre este archivo, para no tocar los
  puntos de llamada existentes).
- Backend tocado: `ajax/clientes/clientes.php` (crear/editar convenio,
  editar empleado, Carga Masiva), `ajax/portal_empresa/portal_empresa.php`
  (crear/editar empleado, resumen, nómina, detalle), `ajax/pos/pos.php`
  (buscar, registrar, anular_venta).
- Frontend tocado: `pages/clientes/view.php`, `pages/portal_empresa/view.php`,
  `pages/pos/view.php`.
- Migración: `migrations/bloque14_cupo_por_marca.sql`.

**Regla de negocio clave** (confirmada por el cliente en nota de voz): el
cupo de una marca **nunca** se presta ni cae a otra marca, ni al cupo
global. El POS (`ajax/pos/pos.php`) valida y descuenta contra la marca del
local donde ocurre la venta (resuelta desde `local.mar_id`), y al anular
una venta el cupo se devuelve a la marca **de la venta original**
(`consumo.loc_id`), nunca a la marca del local de quien anula.

**Documentación completa**: spec en
`docs/superpowers/specs/2026-08-11-cupos-globales-marca-design.md`, plan de
implementación (17 tareas + 1 refactor, cada una con revisión de spec y de
calidad de código) en
`docs/superpowers/plans/2026-08-11-cupos-globales-marca.md`.

**Follow-ups conocidos, no bloqueantes, sin resolver todavía:**
- `ajax/locales/locales.php` (usado para el catálogo de marcas) exige
  `esSuperAdmin()` en todo el archivo, pero el módulo Clientes se habilita
  por permiso granular (`$has('clientes')`), no solo Super Admin — si algún
  rol no-superadmin usa Clientes, el selector "Por marca" se vería sin
  inputs de marca. No confirmado como problema real en producción, solo
  como riesgo teórico.
- Pequeña duplicación de lógica PHP entre `cupo_convenio` (portal_empresa)
  y `cupo_convenio_cliente` (clientes) al construir el array `por_marca`
  — mismo patrón que ya se extrajo del lado de validación
  (`cupoValidarPorMarca`), pero esta parte de lectura no se consolidó.
  candidato natural si aparece un tercer consumidor.
  - `ajax/clientes/clientes.php`'s Carga Masiva hace una consulta
  `cupoMaximosPorMarca` por cada fila del archivo (no es N+1 por columna,
  solo por fila) — barato hoy, pero si algún día se suben archivos de
  cientos de filas convendría sacar la consulta fuera del loop.
  - Pequeñas inconsistencias de naming entre `ajax/pos/pos.php`'s
  `registrar` (`$modoCupoEmp`, `$mar_id_venta`) y `anular_venta`
  (`$modoAnulacion`, `$marDeVenta`) — cosmético, no funcional.
