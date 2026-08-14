# Migración de reportes legacy (argos-ec.com) a SGC_ARGOS26

## Contexto

El sistema viejo (argos-ec.com) tiene un menú "REPORTES" con 21 entradas
(documentadas en `docs/migracion-reportes/argos-ec-reportes-legacy.md` y
`argos-ec-reportes-legacy-parte2.md`, levantadas navegando el sistema con
Claude en Chrome). Este spec cubre qué de eso se migra a
`pages/reportes/` de SGC_ARGOS26, y cómo.

## Alcance: de 21 a 8

Se descartaron 13 de los 21 originales, por tres motivos distintos:

**Ya existe (1):** Estado de Cuenta para Cliente — hay un módulo propio en
`pages/estado_cuenta/` más completo que el original (incluye cuotas de
venta diferida, PDF, seguimiento de envío). No se toca.

**Depende de una funcionalidad que no existe en SGC_ARGOS26, y construirla
está fuera de alcance (3):** Reporte Recargas (no hay concepto de "recarga"
de saldo prepago), Reporte Landing (no hay formulario de captación tipo
landing page), Transacciones DataFast (no hay integración con esa
pasarela).

**El modelo de datos del sistema nuevo es estructuralmente distinto y
migrar a medias produciría un reporte engañoso (9):**
- *Tarjetas individuales* (número, estado, caducidad por tarjeta): el
  sistema nuevo usa cupos por convenio/empleado, no tarjetas con master
  propio → **Reporte Detalle de Tarjetas, Tarjetas Virtuales** descartados.
- *Gift cards nominales tipo certificado*: el sistema nuevo ya tiene su
  propio modelo de gift card por código/lote (`codigo_gift_card`,
  `lote_gift_card`, `giftcard_solicitud`), incompatible con el modelo viejo
  → **GiftPoint, Reporte GifCards, Registro Pagos Gift** descartados.
- *Comisión en dos niveles* (empresa y marca/Argos, con tasas fijas
  hardcodeadas 1.5%/3% en el sistema viejo): el sistema nuevo solo tiene
  `cliente.cli_comision` (un nivel) → **Comisión Mensual Empresas, Ventas
  por Locales (Liquidación), Detalle Cobranza** descartados.
- *Compañía cobradora externa* (ADELNORTE/COSTAHUT/SODETUR): `cartera` no
  registra qué gestora externa cobra cada cuenta → se migra **Cobranza
  Pendiente por Empresa** pero sin esa columna/filtro (decisión ya tomada).

Los 2 enlaces rotos del menú viejo no aplican (no llevan a ningún reporte).

## Los 8 reportes a construir

Todos siguen el patrón ya existente en `pages/reportes/view.php` +
`pages/reportes/excel.php`: un `case` nuevo por `tipo` dentro del mismo
switch (Opción A, confirmada — ver "Decisión de arquitectura" abajo).

### 1. Transacciones por Local
- **Filtros:** MARCA (obligatorio, `select` de `marca`), PROVINCIA
  (opcional, valores distintos de `local.loc_provincia`; los locales con
  `loc_provincia` NULL se agrupan bajo "Sin especificar" — ver nota de
  datos abajo), LOCAL (dependiente), CLIENTE (`select` de `cliente`),
  FECHA DESDE / FECHA HASTA.
- **Query:** `consumo` → `personal` (`per_id`) → `cliente` (`cli_id`) →
  `local` (`loc_id`) → `marca` (`mar_id`), filtrando por los criterios
  anteriores y `con_fecha` en el rango.
- **Columnas:** FECHA, HORA, LOCAL, CLIENTE, TARJETA
  (`con_numero_tarjeta`), DOCUMENTO (`per_documento`), NOMBRES
  (`per_nombre`), AUTORIZACION (`con_autorizacion`), VALOR
  (`con_valor_total`).
- **Sin totales** (listado transaccional fila por fila, igual que el
  original). **Sin filtro "tipo de tarjeta"** del legacy — no hay
  distinción Business/Gift Card en el esquema nuevo.
- **Excel:** sí, sigue el patrón `excel.php` existente.

### 2. Total Ventas
- **Filtro:** MARCA (obligatorio, único filtro).
- **Query:** `SUM(con_valor_total)` agrupado por `YEAR(con_fecha)` y
  `MONTH(con_fecha)`, vía `consumo` → `local` → `marca`.
- **Salida:** matriz Año (fila) × Ene..Dic (columna) + columna Total,
  pivoteada en PHP a partir del resultado agrupado.
- **Simplificado a propósito** respecto al legacy: sin comparativo año vs.
  año (%), sin desglose Business/Gift Card (no aplica), sin desglose por
  ciudad (subsumido por el punto 8 de abajo). Solo el bloque "Evolutivo".

### 3. Registro Cobranza
- **Filtros:** CLIENTE (`select`), FECHA DESDE / FECHA HASTA (sobre
  `car_fecha_ingreso`). **Sin filtro MARCA** — `cartera` no tiene `mar_id`,
  no existe esa dimensión a nivel de cartera.
- **Query:** `cartera` → `cliente`, filtrando por `cli_id` y
  `car_fecha_ingreso` en el rango.
- **Columnas:** EMPRESA (`cli_descripcion`), FECHA REGISTRO
  (`car_fecha_ingreso`), FECHA DESDE (`car_fecha_inicio`), FECHA HASTA
  (`car_fecha_fin`), MORA (`car_tipo`: 30/60/90/91 — análogo al campo
  "MORA" del legacy, aunque con escala distinta), TOTAL
  (`cli_valor_pagar`).
- **Se omite la columna OBSERVACION** del legacy: no hay un campo de
  observación a nivel de cartera (`gestion.ges_observacion` es por gestión
  individual, no por cartera completa — no hay un único valor que mostrar).
- **Totales:** fila TOTAL con la suma de `cli_valor_pagar`.

### 4. Ventas Locales
- **Filtros:** MARCA (obligatorio), FECHA DESDE, FECHA HASTA.
- **Query:** `consumo` → `local` → `marca`, `GROUP BY loc_provincia,
  loc_id`.
- **Salida:** agrupado por PROVINCIA (reemplaza "ciudad" del legacy — ver
  nota de datos; locales sin provincia van a "Sin especificar"), y dentro
  de cada grupo por LOCAL: LOCAL, VALOR BRUTO (`SUM con_valor_total`),
  VALOR NETO SIN IVA (`con_valor_total - con_iva`). Fila `TOTAL
  <PROVINCIA>` por grupo, igual que el legacy tenía `TOTAL <CIUDAD>`.
- **Excel:** no (igual que el legacy, no tenía botón de exportar).

### 5. Cobranza Pendiente por Empresa
- **Filtros:** CLIENTE (opcional), FECHA CORTE (obligatorio). **Sin
  MARCA/COMPAÑÍA/CIUDAD/LOCAL** — ninguna de esas dimensiones existe en
  `cartera` (la de COMPAÑÍA cobradora fue una decisión explícita, ver
  arriba).
- **Query:** `cartera` → `cliente`, `WHERE car_estado IN ('pendiente',
  'notificacion', 'compromiso') AND car_fecha_ingreso <= :fecha_corte`,
  `GROUP BY cli_id, YEAR(car_fecha_ingreso)`.
- **Columnas:** EMPRESA, AÑO, VALOR (`SUM cli_valor_pagar`).
- **Totales:** fila TOTAL.

### 6. Cobranza Pendiente por Mes
- **Filtros:** FECHA CORTE (obligatorio), CLIENTE (opcional).
- **Query:** igual base que el punto 5, pero `GROUP BY
  YEAR(car_fecha_ingreso), MONTH(car_fecha_ingreso)` (histórico
  acumulado, no limitado al año en curso, igual que el legacy).
- **Columnas:** AÑO, MES, VALOR. Fila TOTAL.

### 7. Reporte Pendiente Empresas
- **Filtros:** CLIENTE (opcional), AÑO (obligatorio).
- **Query:** `cartera` → `cliente`, `WHERE YEAR(car_fecha_ingreso) = :año
  AND car_estado IN ('pendiente','notificacion','compromiso')`, `GROUP BY
  cli_id, MONTH(car_fecha_ingreso)`.
- **Salida:** matriz EMPRESA × Ene..Dic + TOTAL, incluyendo **todas** las
  empresas del catálogo aunque su saldo sea 0.00 en todos los meses (igual
  que el legacy — no filtra filas vacías). Fila final `TOTAL POR COBRAR`.

### 8. Ventas Ciudades → redefinido como ranking plano de locales
El legacy agrupaba por ciudad y, dentro de cada ciudad, por EMPRESA — con
el dato de ciudad ya descartado (ver Nota de datos), agrupar por empresa
sin ciudad sería casi idéntico al reporte ya existente "Cliente +
Consumos" (`case 'cliente consumos'`). Para no duplicar, y siguiendo la
decisión tomada ("agrupar por LOCAL"), este reporte queda como un
**ranking plano de locales por ventas** — mismo cálculo que "Ventas
Locales" (punto 4) pero **sin** el agrupador por provincia, ordenado por
VALOR BRUTO descendente, con un único TOTAL general al final. Complementa
al punto 4: ese da subtotales por provincia, este da el ranking top-down
sin importar dónde está cada local.
- **Filtros:** MARCA (obligatorio), FECHA DESDE, FECHA HASTA (mismos que
  Ventas Locales).
- **Columnas:** LOCAL, VALOR BRUTO, VALOR NETO SIN IVA — ordenado por
  VALOR BRUTO desc. Fila TOTAL al final (a diferencia de Ventas Locales,
  que no tenía un total general, solo por ciudad).
- **Nota para la revisión del spec:** esta redefinición es una decisión de
  producto de mi parte (mantener el propósito de "comparar rendimiento
  entre puntos de venta" sin el dato de ciudad que ya no existe) — si no
  tiene sentido para el negocio, se puede descartar del lote igual que los
  otros 13.

## Nota de datos: "ciudad" no es un campo real en `local`

`local` no tiene columna de ciudad — solo `loc_provincia` (which, en la
base local de desarrollo, está vacía en 12 de 15 locales y solo tiene el
valor "sierra" en el resto; probablemente sea distinto en producción, pero
estructuralmente sigue siendo *provincia*, no *ciudad*). Los tres reportes
que dependían de ciudad se resolvieron así: Transacciones por Local y
Ventas Locales usan `loc_provincia` tal cual (agrupando lo vacío bajo "Sin
especificar"); Ventas Ciudades se redefinió sin esa dimensión (punto 8).
Si en el futuro se agrega una columna de ciudad real a `local`, estos tres
reportes son el lugar natural para aprovecharla.

## Decisión de arquitectura: Opción A — extender el switch existente

Los 8 reportes se agregan como `case` nuevos en `pages/reportes/view.php`
(formulario de filtros) y `pages/reportes/excel.php` (query + salida XLS),
siguiendo exactamente el patrón de los 10 `case` que ya existen ahí. Se
descartó separar en archivos por reporte (reestructuración innecesaria de
lo que ya funciona) y un motor de reportes configurable (sobre-ingeniería
para reportes estáticos que no necesitan ser editables sin tocar código).

Cada `case` nuevo sigue la forma ya establecida:
- En `view.php`: un formulario con los filtros del reporte, que hace
  submit por GET a `excel.php` (igual que los 10 casos existentes).
- En `excel.php`: lee los filtros de `$_GET`, arma la query, imprime una
  tabla HTML con `Content-Type: application/xls` (mismo mecanismo de
  "descarga" que el resto del módulo — no es un XLS real, es HTML servido
  con esa cabecera, igual que todos los reportes actuales).

## Enlaces en el menú

Se agregan 8 entradas nuevas a `shared/sidebar.php`, mismo patrón
`?module=reportes&tipo=<nombre>` que las existentes. De paso, se enlazan
también los 2 tipos que ya estaban codificados pero sueltos del menú
(`cliente - consumos` y `consumos del mes`, detectados al inicio de esta
conversación) — quedarían así 18 entradas de reportes en el sidebar en
total (8 existentes ya enlazadas + 2 sueltas que se enlazan + 8 nuevas).

## Seguridad y datos sensibles

Ninguno de los 8 reportes expone cédula/tarjeta completa más allá de lo
que el sistema ya muestra hoy en el módulo Reportes existente (ej.
`con_numero_tarjeta` y `per_documento` ya se listan sin enmascarar en
"Cliente + Consumos"). No se introduce ningún dato más sensible que el que
ya es visible internamente en el sistema — no se requiere anonimización
adicional en pantalla.

## Testing

Sin framework de tests en el proyecto (ver CLAUDE.md). Verificación:
scripts SQL CLI contra `sgipro_sgc_argos` para confirmar que cada query
nueva devuelve datos coherentes con los datos de prueba, y QA manual en
`http://localhost/SGC_ARGOS26/` navegando cada uno de los 8 reportes
nuevos con al menos un filtro con datos y uno sin datos (para confirmar
que el caso vacío no rompe la página, siguiendo el patrón `$hay_datos`
que ya usan varios de los casos existentes).
