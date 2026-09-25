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

## Recordatorio obligatorio: al pasar a producción real

Mientras el proyecto esté en preproducción, el envío real de correos de
Estado de Cuenta queda bloqueado por el interruptor `configuracion.cfg_clave
= 'modo_preproduccion'` (ver `services/estado_cuenta_service.php` →
`ec_modo_preproduccion()`), y el cron de HostPapa
(`cron/enviar_estados_cuenta.php`, cPanel > Cron Jobs) puede estar
deshabilitado.

**Cuando el usuario confirme que ya van a operar con datos y correos reales
de producción (no más pruebas/preproducción), SIEMPRE recordarle, sin que
tenga que pedirlo:**
1. Volver a habilitar el cron en cPanel si lo habían deshabilitado.
2. `UPDATE configuracion SET cfg_valor='0' WHERE cfg_clave='modo_preproduccion';`
   en la base real — mientras siga en `1`, ningún camino de envío (botón
   manual, cron, piggyback en `content.php`) manda un correo real, sin
   importar qué tan bien configurado esté el SMTP.

## Migración desde el sistema viejo (Joomla/Argos) — estado y plan

Se está migrando datos reales desde un dump viejo (Joomla + extensión
"Argos", ~150MB, tablas `gvony_ads_*`) hacia el esquema nuevo, por
prioridades, depurando duplicados y basura en cada paso — no es un dump
directo. Bases de trabajo locales (XAMPP, no versionadas): `sgc_argos_viejo`
(dump viejo importado tal cual, para consultarlo) y `sgc_argos_prioridad1`
(espejo de lo ya migrado a producción, para probar scripts antes de
correrlos ahí). Los `.sql` generados para cada tanda viven en
`_dump_viejo/` (no versionado — son para correr manualmente por phpMyAdmin
contra la base real de HostPapa, no para pushear).

**Prioridad 1 — YA HECHO Y EN PRODUCCIÓN:**
- `marca`, `local`, `cliente` (187 convenios `published=1` de la base
  vieja), `personal` migrados y deduplicados (la base vieja tenía masiva
  duplicación por re-cargas de nómina sin control de duplicados — ver
  historial de esta conversación para el detalle del hallazgo).
- Decisión del cliente: no se maneja más un estado "inactivo" visible —
  solo `activo` y `bloqueado`. Los antiguos `personal` inactivos sin
  historial de consumo viejo se eliminaron definitivamente; los que sí
  tenían historial (1.876 personas, ~$8.201 en consumos viejos) se
  conservan con `per_estado = 'archivado'` — invisibles en toda la app
  (Personal, POS, Portal Empresa, Reportes) pero presentes en la BD para
  no romper la suma de futuros estados de cuenta históricos por convenio.
  Ver filtros `AND per_estado != 'archivado'` en `ajax/clientes/clientes.php`,
  `ajax/pos/pos.php`, `ajax/portal_empresa/portal_empresa.php`,
  `pages/reportes/excel.php`.
- Tabs Activos/Bloqueados con contador en el tab Personal de la ficha del
  cliente, filtrando en el navegador sobre los datos ya cargados.

**Prioridad 2 — HECHA Y VERIFICADA EN PRODUCCIÓN (9 de septiembre de 2026):**
- Se migró **todo** el rango 2016-2026 sin recortar (decisión del cliente,
  el volumen resultante es chico, ver depuración abajo).
- Histórico de consumos/autorizaciones (`gvony_ads_company_card_authorization`,
  125.730 filas viejas) → tabla `consumo`: **95.212 filas, $2.330.871,99**.
  Depuración real (no es igual a la de `personal`): solo 26 duplicados
  exactos; `cancel_state` NO significa "anulado" (significa "ya pasó por
  un cierre mensual viejo", se migra igual); las anulaciones reales
  (`reverse_tran=1`, monto negativo) se migran ambos lados del par, se
  cancelan solos al sumar. `per_id` resuelto por cédula en 2 pasadas
  (tarjeta actual, y para el resto, historial completo de tarjetas
  reemitidas en `card_detail`) — **importante**: en el script final el
  `per_id` de cada fila se resuelve con una subconsulta por `per_documento`
  en el momento de importar, nunca como número fijo — el auto_increment de
  `personal` avanza distinto en cada entorno (local vs. producción real
  con actividad propia), un `per_id` fijo calculado en local rompe en
  producción. Mismo cuidado aplica a cualquier futuro script que necesite
  referenciar filas por su id autogenerado en otra base.
- Al armar esta migración se descubrió que el chequeo original de
  Prioridad 1 (¿quién archivar vs. eliminar?) solo miraba la tarjeta MÁS
  RECIENTE de cada persona, no su historial completo — **5.919 personas
  se habían eliminado por error** (tenían consumo real en una tarjeta
  anterior reemitida). Se corrigieron reinsertándolas como `archivado`.
  Total archivados: **7.795** (1.876 originales + 5.919 recuperados).
- Gift cards emitidas en el sistema viejo (`gvony_ads_giftcard`) →
  `codigo_gift_card`: **30.621 códigos** (excluidos 156 placeholders
  basura tipo `----||||`/`0` y 6 duplicados). Estado (`cgc_estado`)
  calculado por saldo real, no por el campo viejo `consumo` (no era
  confiable). 52 lotes sintéticos (agrupados por marca+fecha) atribuidos a
  una cuenta dedicada `sistemamigracion` ("Sistema Migración"), creada
  bloqueada — la tabla vieja no tiene ningún campo de responsable/usuario.
  `activo`=4.840 ($148.968,13), `vencido`=13.269 ($122.440,38),
  `consumido`=12.512 ($0).
- Scripts en `_dump_viejo/`: `04_correccion_archivados_faltantes.sql`,
  `05_migrar_giftcards.sql`, `06_migrar_consumos.sql` (todos ya corridos).
- Cierres de cuenta históricos (`gvony_ads_company_account_close`) → NO
  migrados, no calzan 1:1 con el `estado_cuenta` nuevo, sin definir con
  el cliente si migran igual como referencia.

**Correcciones post-migración (24 de septiembre de 2026), detectadas por el
sponsor de Argos al notar personal real de convenios activos escondido:**
- El chequeo original de Prioridad 1 que determinaba `activo`/`bloqueado`
  en la migración tenía más de un problema, además del ya documentado
  arriba (5.919 eliminados por error). Casos reales encontrados:
  - **747 personas** cuya última acción real en el sistema viejo
    (`gvony_ads_company_card_block`, `type_reg='B'`) era "Bloqueado", no
    "inactivo" — quedaron `archivado` (invisibles) en vez de `bloqueado`
    (visibles). Corregido con `_dump_viejo/07_correccion_bloqueados_mal_archivados.sql`.
  - **216 personas** con una transacción real en el sistema viejo durante
    2025 o 2026 (hasta 6 meses antes del corte de migración) que igual
    quedaron `archivado` sin razón identificable en ningún campo del dump
    viejo (tarjeta publicada, empresa activa, sin bloqueo). Corregidas a
    `activo` con `_dump_viejo/08_correccion_archivados_con_actividad_reciente.sql`.
    Caso disparador: convenio ARGOS PUBLICIDAD S.C.C. (`cli_id=99`), donde
    los 14 empleados habían quedado `archivado`/sin revisar.
  - **482 casos** con última acción "R" (observación siempre "CIERRE") se
    evaluaron y se dejaron como `archivado` — parece un cierre real de
    tarjeta/convenio, no un error de clasificación.
  - **43 casos** con última acción "A" (reactivado) siguen `archivado` sin
    revisar — pendiente, es la anomalía más sospechosa que queda sin tocar.
  - El resto de los archivados sin actividad desde 2023 o antes (~6.831)
    se dejó como está — no se hizo bulk-reclasificación, ver tab
    "Archivados" abajo.
  - Se detectaron además **23 convenios con el 100% de su personal
    archivado** (0 visibles). De esos, 20 (~1.507 personas) no tienen
    actividad desde 2023 o antes — probablemente cierres reales. 3
    convenios (ARCACONTINENTAL, SINDICATO HOSPITAL EUGENIO ESPEJO,
    GARLANDS — 44 personas) sí tienen compras reales en 2024, un poco por
    debajo del corte de 2025 usado arriba. **Decisión explícita del
    cliente: NO reactivarlos.** Criterio: prefiere que alguien archivado
    se acerque a comprar y no pueda (se resuelve al momento reactivándolo
    manualmente) a reactivar de más y que la persona consuma sin que haya
    forma de cobrarle al convenio después. Ante esta duda (activar de más
    vs. dejar archivado de más), el default del cliente es **quedarse
    archivado** — aplicar el mismo criterio si aparecen casos similares.
- **Nueva pestaña "Archivados"** en el tab Personal de la ficha del
  cliente (`pages/clientes/view.php`, junto a Activos/Bloqueados), con
  botón "Reactivar" por persona (pasa a `activo`, usa el mismo endpoint
  `personal_cambiar_estado` y el mismo modal de confirmación que
  Bloquear/Activar). Decisión explícita del cliente: en vez de seguir
  hacienda scripts SQL para cada corte de recencia, cualquier archivado
  ahora se puede revisar y reactivar manualmente desde la UI si se
  confirma que sigue siendo una persona real y activa. El endpoint
  `personal_list` dejó de excluir `archivado` (antes lo excluía); el
  contador de la pestaña "Personal" y el contador "N emp." del listado de
  convenios (`ajax/clientes/clientes.php`, `total_personal`) siguen
  contando solo activo+bloqueado, no archivado.

**Descartado / pendiente de confirmar con el cliente, no urgente:**
`gvony_ads_discount_card` (sin actividad desde 2021), `credit_businesscard`,
`clients_order`/`product*` (e-commerce viejo, sin equivalente en el
esquema nuevo), `gvony_ads_users` (data de prueba, no real).
Falta también resolver: `cliente` nuevo no tiene columna RUC (la vieja sí),
y `cli_tipo_cartera` no tiene de dónde migrarse.

## Corte de datos: 2026-09-09 — todo lo migrado es real, todo lo posterior es prueba

El **9 de septiembre de 2026** se dio por completa y verificada la migración
del sistema viejo (Prioridad 1 + Prioridad 2, ver sección de abajo): 187
convenios, ~25.582 `personal` (activo + archivado), 95.212 `consumo`
históricos, 30.621 `codigo_gift_card`. Esos son datos **reales** del
cliente y no se deben tocar, mezclar con pruebas, ni usar como base para
generar más datos ficticios.

**A partir de esa fecha, cualquier registro nuevo que se cree en la base
real (`cliente`, `personal`, `consumo`, `giftcard_solicitud`, `usuario`,
etc.) mientras el sistema siga en preproducción debe ser obviamente
**dummy/de prueba** — nombres, cédulas, montos y convenios que no
correspondan a personas o empresas reales, y que se puedan identificar a
simple vista como ficticios (ej. "EMPRESA DE PRUEBA QA", cédulas tipo
`0000000001`).** No crear registros de prueba que parezcan reales o que
puedan confundirse con datos migrados.

**Por qué:** cuando se pase a producción real, va a hacer falta poder
diferenciar sin ambigüedad "esto vino de la migración" de "esto se creó
mientras probábamos" — para poder limpiar todo lo segundo antes del
lanzamiento real sin arriesgar borrar datos migrados por error. Si Claude
crea datos de prueba en la base real como parte de una tarea (cuentas de
prueba desechables, empleados de prueba para QA, etc.), debe dejarlos
marcados de forma obviamente ficticia por este mismo motivo, no solo
borrarlos al final de la sesión — si algo queda sin borrar por error, tiene
que notarse igual que es basura de prueba.

## Prioridad 3 — Usuarios (cajeros/supervisores por local), en curso

Reunión del sponsor (25 de septiembre de 2026, Elbany) reveló más pendientes
además de personal/consumos, ya resueltos por separado en este mismo
archivo:

- **Login soporta dos formatos de contraseña** (`login-check.php`): las
  cuentas nuevas siguen en MD5 sin sal (comportamiento de siempre); las
  migradas del sistema viejo guardan su hash bcrypt original (`$2y$`/`$2a$`/
  `$2b$`) tal cual, sin resetear nada — se detecta el formato por el
  prefijo del hash guardado y se valida con `password_verify()` en ese
  caso. Así cada persona migrada entra con la misma clave de siempre. Los
  usuarios reales del sistema viejo NO están en `gvony_ads_users` (esa
  tiene 1 fila de prueba) — están en `gvony_users`, la tabla nativa de
  Joomla extendida con columnas propias (`local_id`, `documentNumber`,
  etc.). `gvony_users.local_id` mapea 1:1 exacto a `local.loc_id` ya
  migrado (verificado), a diferencia de `personal.per_id` que no es
  portable.
- `_dump_viejo/09_migrar_usuarios_cajeros.sql`: 1.387 cuentas de
  cajero/supervisor por local (`local_id` presente en el dump viejo),
  preservando el hash de contraseña tal cual y el estado `block` (1.239
  activo / 148 bloqueado) — sin filtrar por recencia de acceso, a
  diferencia de `personal`: una cuenta de login dormida no tiene el mismo
  riesgo (cobranza) que un empleado oculto, así que no hace falta
  inventar un criterio de actividad aquí. Todas nacen con
  `permisos_acceso='Cajero'`; el sistema viejo no distingue cajero de
  supervisor en un campo propio (está mezclado en el nombre de la
  cuenta) — se decidió no adivinar por texto, Elbany asciende a los
  supervisores puntuales desde Perfiles y Permisos después.
- **134 cuentas de staff interno de Argos y "control de ventas" por
  marca** (sin `local_id`) quedaron fuera de ese script a propósito —
  entregadas en `_dump_viejo/09b_usuarios_staff_para_revisar.csv` para
  que Diego/Elbany decidan cuáles migrar y con qué rol.
- Tabla `usuario` en Usuarios ahora separa **Marca** de **Asignación**
  (antes venían combinadas en una sola celda) — `ajax/users/users.php`.
- `usuario` nunca tuvo columna de cédula/documento — nadie podía buscar a
  un cajero por cédula (caso real: MERCY ALAY, doc `0922100698`, migrada
  como `maalay01` pero invisible al buscar por su cédula). Se agregó
  `usuario.documento` (`migrations/bloque20_documento_usuario.sql`) y se
  rellenó para 1.154 de las 1.387 cuentas migradas con
  `_dump_viejo/10_backfill_documento_cajeros.sql` (las otras 233 no
  traían cédula en el sistema viejo, quedan `NULL`). La columna
  "Documento" ya es visible en la tabla y el buscador de DataTables la
  encuentra solo, sin cambios adicionales de JS.

**Otros dos hallazgos de la misma reunión, ya corregidos:**
- `ajax/gestiones/gestiones.php` interpolaba `$_GET`/`$_POST` sin escapar
  en varios `case` (inyección SQL real) — corregido casteando a `(int)`
  los IDs y con `mysqli_real_escape_string()` los strings, sin cambiar el
  comportamiento.
- El reporte "Ventas por Locales (Liquidación)" no calculaba la comisión
  de Argos (el sistema viejo sí). Se agregó `marca.mar_comision` (default
  12.5%, "Vaco y Vaca" en 10% — `migrations/bloque19_comision_por_marca.sql`)
  y las filas de Comisión/IVA Comisión/Total Factura a la Marca en
  `pages/reportes/excel.php` (usa el % de IVA configurado del sistema, no
  uno fijo).

**`ADD COLUMN IF NOT EXISTS` no es válido en el MySQL/MariaDB de
producción (error 1064) — nunca usarlo.** Ya se había descubierto una vez
en julio (commits `e1319a6`/`a6a4ba4`, `usuario.session_version` y
`pago.pag_estado`) y volvió a aparecer en `ajax/gestiones/gestiones.php` y
`ajax/users/users.php` (7 sitios) porque la migración "perezosa" ahí
copiaba el mismo patrón. Para cualquier columna que deba crearse sola en
el primer request que la necesite, usar
`agregarColumnaSiNoExiste($mysqli, $tabla, $columna, $definicionSql)` de
`helpers/db_helpers.php` — chequea `information_schema` en vez de
depender de la sintaxis `IF NOT EXISTS`. Importante: en local (PHP más
nuevo) mysqli lanza excepción ante un "Duplicate column name", mientras
que en producción (PHP < 7.1) `mysqli_report` está OFF y la falla queda
silenciosa — un ALTER TABLE repetido sin este helper puede pasar
desapercibido en producción pero tumbar la página entera en local.

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
