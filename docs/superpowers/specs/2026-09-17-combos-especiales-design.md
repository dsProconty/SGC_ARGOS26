# Combos Especiales

## Contexto

El sponsor pidió un módulo de "combos especiales": paquetes promocionales (ej.
"Pack Familiar $19.99") con un código único que cualquier cajero puede cobrar
en el punto de venta — a un empleado de convenio (a crédito, cargado a su
cupo) o a cualquier persona de la calle (de contado, sin que exista en el
sistema). El código se promociona por WhatsApp/redes (artes con el código
visible), así que **lo puede cobrar cualquier cantidad de clientes distintos**
hasta que venza — no es un código de un solo uso como una gift card.

Decisiones confirmadas con el cliente (voice del 2026-09-11, HU aprobada por
el sponsor y diseño técnico aprobado el 2026-09-17):

1. Un combo tiene nombre, precio, descripción, código único, fecha de
   caducidad y franquicia asociada.
2. El código es **reutilizable**: cualquier cliente lo puede cobrar, cuantas
   veces sea, hasta la fecha de caducidad — no se "agota" ni tiene saldo.
3. El código solo funciona en locales de la franquicia asociada.
4. En el POS hay un campo nuevo y **separado** del de Gift Card/cédula
   ("Código B2B"), porque el flujo a crédito necesita ambos al mismo tiempo
   (cédula del empleado + código del combo).
5. A crédito: se busca al empleado por cédula (igual que hoy) y el valor del
   combo se carga a su cupo, como cualquier compra normal — sujeto a la
   misma validación de cupo disponible y de franquicia (cupo por marca) que
   ya existe.
6. De contado: no se pide cédula ni ningún dato del cliente; se cobra en
   caja y se registra igual.
7. Reporte general o filtrado por combo, con todos los movimientos.

## Alcance

- No se puede combinar un combo con Gift Card o pago externo en la misma
  venta — el valor del combo es fijo y se cobra completo, por un solo medio
  (cupo del convenio, o "contado").
- No hay restricción adicional por convenio (la idea de "combo solo para
  Proconty Y solo en tal franquicia" quedó fuera de esta versión — ver la HU).
- Los combos no se eliminan (mismo criterio que locales/personal: un combo
  vencido simplemente deja de poder cobrarse, pero se conserva para no
  romper el reporte histórico). Sí se pueden editar.

## Modelo de datos

**`combo_especial`** (nueva):

```sql
CREATE TABLE `combo_especial` (
  `ce_id`              INT NOT NULL AUTO_INCREMENT,
  `ce_nombre`          VARCHAR(150) NOT NULL,
  `ce_descripcion`     VARCHAR(255) DEFAULT NULL,
  `ce_codigo`          VARCHAR(50) NOT NULL,
  `ce_valor`           DECIMAL(10,2) NOT NULL,
  `ce_fecha_caducidad` DATE NOT NULL,
  `mar_id`             INT NOT NULL COMMENT 'Franquicia donde se puede cobrar',
  `id_user_creador`    INT DEFAULT NULL,
  `ce_fecha_creacion`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ce_id`),
  UNIQUE KEY `uk_ce_codigo` (`ce_codigo`),
  KEY `fk_ce_marca` (`mar_id`),
  CONSTRAINT `fk_ce_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`),
  CONSTRAINT `fk_ce_user` FOREIGN KEY (`id_user_creador`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**`consumo`**: se agrega `con_combo_id INT NULL` (FK a `combo_especial`). No
se necesita ninguna otra tabla nueva para registrar la venta — se reutiliza
`consumo` exactamente como ya funciona para Gift Card sin empleado:

- **Crédito**: `per_id` = empleado buscado por cédula, `con_monto_convenio`
  = `ce_valor` (se descuenta del cupo, igual que cualquier compra),
  `con_combo_id` = el combo.
- **Contado**: `per_id` = NULL, `con_monto_convenio` = 0, `con_monto_externo`
  = `ce_valor`, `con_combo_id` = el combo.

## Permisos y menú

- Nuevo módulo `combos` en `MODULOS_SISTEMA` (`ajax/perfiles/perfiles.php`)
  y `$modulos_categorias` (`pages/perfiles/view.php`), categoría Finanzas
  (junto a Gift Cards). Se asigna igual que cualquier módulo, vía Perfiles y
  Permisos — no hay bypass de Super Admin en `content.php`/`shared/sidebar.php`
  (comprobado: el acceso se resuelve por filas reales en `perfil_modulo`), así
  que la migración le agrega la fila `combos` al perfil Super Admin existente
  para no dejarlo afuera del módulo nuevo.
- Ítem de menú "Combos Especiales" en `shared/sidebar.php`, junto a Gift Cards.

## Backend

**`ajax/combos/combos.php`** (nuevo, módulo de administración):

- `list`: todos los combos con su franquicia y un estado calculado
  (`vigente`/`vencido` según `ce_fecha_caducidad`) — no hay campo de estado
  persistido, se calcula en la consulta.
- `get`: un combo por id (para editar).
- `crear` / `editar`: valida nombre, precio > 0, código único (no vacío,
  no duplicado — con el mismo estilo `mysqli_real_escape_string` que el
  resto del código legado en `pos.php`, no PDO), fecha de caducidad, y
  marca válida (existe en `marca`). El código no se puede editar una vez
  creado (evita romper ventas que ya lo referencian con voucher/reportes
  impresos) — todo lo demás sí.
- `movimientos`: reporte — `consumo` con `con_combo_id IS NOT NULL`,
  filtrable por `con_combo_id` y rango de fechas; devuelve fecha, hora,
  tipo de pago (crédito si `per_id` no es null, contado si lo es), nombre
  del empleado si aplica, local, monto.

**`ajax/pos/pos.php`** (se amplía):

- `buscar_combo` (GET, `codigo`): resuelve `loc_id` del cajero
  (`resolverLocId()`) y su marca (`cupoMarcaDeLocal()`); busca el combo por
  código. Rechaza si no existe, si `ce_fecha_caducidad < hoy`, o si su
  `mar_id` no coincide con la marca del local actual (mensaje: "Este combo
  no aplica en este local"). Si es válido, devuelve `ce_id`, `ce_nombre`,
  `ce_valor`.
- `registrar` (existente, se amplía): acepta `combo_id` opcional. Si viene,
  vuelve a validar el combo igual que `buscar_combo` (nunca confiar en lo
  que mandó el navegador) y **fuerza** `con_monto_convenio = ce_valor`,
  ignorando cualquier monto que llegue por POST — impide que alguien
  manipule el monto de un combo desde el cliente. Con combo no se acepta
  gift card ni pago externo en la misma venta (se ignoran si vinieran). El
  resto del flujo (validar empleado, cupo disponible, cupo por marca,
  descuento) es exactamente el mismo que ya existe.
- `registrar_combo_contado` (nuevo, POST): sin empleado. Valida el combo
  igual que `buscar_combo`, inserta el `consumo` con `per_id = NULL`,
  `con_monto_convenio = 0`, `con_monto_externo = ce_valor`, `con_combo_id`.

## Frontend

**`pages/pos/view.php`**: nueva tarjeta "Código B2B", independiente de la de
"Buscar Empleado" (no la reemplaza ni la reutiliza — confirmado con el
cliente que necesitan poder usar cédula y código de combo a la vez).

- Input de código + botón Buscar → llama `buscar_combo`; si es válido
  muestra nombre y precio del combo (solo lectura, sin poder editarlos) y
  dos opciones: **Crédito** / **Contado**.
- **Contado**: aparece un botón "Registrar Combo (Contado)" autocontenido
  — no toca la tarjeta de empleado ni la de "Registrar Venta". Al
  confirmar, llama `registrar_combo_contado` y reutiliza el modal de
  voucher ya existente.
- **Crédito**: muestra el aviso "Busque al empleado por su cédula para
  continuar". Cuando el cajero encuentra un empleado (flujo de cédula ya
  existente), si hay un combo pendiente en modo Crédito, la tarjeta
  "Registrar Venta" entra en "modo combo": descripción y monto quedan
  fijados al nombre/valor del combo (no editables), se oculta el campo de
  pago externo, y "Confirmar Venta" llama a `registrar` con `combo_id`.

**`pages/combos/view.php`** (nuevo, admin): tabla de combos (nombre, código,
valor, franquicia, vence, estado vigente/vencido) con botón "Nuevo Combo" y
"Editar" por fila (modal), y una sección de reporte con filtro por combo y
rango de fechas, reutilizando el mismo patrón de tabla + resumen que
`pages/pos/historial.php`.
