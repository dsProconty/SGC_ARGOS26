# Cupos globales y diferenciados por marca

## Contexto

Hoy un convenio (`cliente`) con beneficio tipo `Cupo` define un único monto (`cli_valor_beneficio`) que se reparte entre sus empleados (`personal.per_cupo_asignado` / `per_cupo_disponible`). Al pagar en el POS, ese cupo se descuenta sin importar la marca (Pizza Hut, Fridays, etc.) del local donde se realiza la venta (`local.mar_id` → `marca`).

El cliente (Proconty) necesita que un convenio pueda funcionar de dos formas, elegibles por convenio:

1. **Global**: un solo cupo que el empleado gasta libremente entre todas las marcas (incluso todo en una sola). Comportamiento actual.
2. **Por marca**: el cupo del empleado se reparte en montos independientes por marca (ej. $50 en Pizza Hut, $30 en otra marca), no fungibles entre sí.

Confirmado por el cliente vía nota de voz: "tengo empresas que sí puedo descontar... para pizzas cincuenta dólares, para las demás marcas cien. Y tengo otras empresas que quieren global... cien dólares entre todas las marcas."

## Alcance

- Un convenio usa **un solo modo** (global o por marca), no ambos a la vez.
- La diferenciación por marca aplica a nivel de **empleado** (cada empleado tiene su propio cupo por marca), no solo a nivel de convenio.
- Si una marca no tiene monto asignado a un empleado en modo "por marca", su cupo ahí es 0 y el pago se rechaza.
- Convenios y empleados existentes quedan en modo **global** por defecto — sin migración de datos, sin cambio de comportamiento.

## Modelo de datos

- **`cliente`**: nueva columna `cli_modo_cupo` ENUM('global','marca') NOT NULL DEFAULT 'global'. Solo tiene efecto cuando `cli_tipo_beneficio = 'Cupo'`. `cli_valor_beneficio` se sigue usando cuando el modo es `global`.
- **`cliente_cupo_marca`** (nueva): `cli_id`, `mar_id`, `ccm_monto_max` — monto máximo que el convenio permite asignar a un empleado en esa marca. PK compuesta (`cli_id`, `mar_id`). Solo se usa cuando `cli_modo_cupo = 'marca'`.
- **`personal`**: sin cambios de columnas. `per_cupo_asignado` / `per_cupo_disponible` se siguen usando para empleados de convenios en modo `global`.
- **`personal_cupo_marca`** (nueva): `per_id`, `mar_id`, `pcm_asignado`, `pcm_disponible` — cupo del empleado en esa marca específica. PK compuesta (`per_id`, `mar_id`). Solo se usa cuando el convenio del empleado está en modo `marca`. Ausencia de fila = cupo 0 en esa marca.

## Formularios

> Nota post-sync: el módulo administrativo activo es **Clientes** (`pages/clientes/view.php` + `ajax/clientes/clientes.php`) — es lo que está enlazado en el sidebar y lo que se ve en producción. `convenio/view.php` / `ajax/convenio/convenio.php` no están enlazados en el menú (código legado); se actualizan igual por consistencia de datos, pero no son la superficie que usan los admins.

### Cliente/convenio (`pages/clientes/view.php:410-423` + `ajax/clientes/clientes.php` `crear`/`editar`)

Cuando `Tipo de beneficio = Cupo`, aparece un select **"Modo de cupo"**: `Global` | `Por marca`.

- `Global` → se muestra el campo actual "Monto del cupo" (sin cambios).
- `Por marca` → ese campo se reemplaza por una lista dinámica con un input de monto máximo por cada marca activa (catálogo `marca`). Al guardar, se hace upsert en `cliente_cupo_marca` por cada marca con monto > 0 (monto 0 u omitido = sin fila).
- Se replica el mismo cambio en `convenio/view.php` + `ajax/convenio/convenio.php` por consistencia, aunque no esté enlazado en el menú.

### Empleado — alta/edición individual (`ajax/clientes/clientes.php` `personal_editar`, y el mismo patrón en `ajax/portal_empresa/portal_empresa.php`)

- Convenio en modo `global`: formulario sin cambios — un campo "Cupo" validado contra `cli_valor_beneficio`.
- Convenio en modo `marca`: el formulario muestra un input por marca (con su nombre), cada uno validado contra el máximo definido en `cliente_cupo_marca` para esa marca. Al guardar, se crean/actualizan filas en `personal_cupo_marca` (monto 0 u omitido = sin fila, cupo 0 en esa marca).
- Edición conserva la lógica actual de ajuste proporcional del disponible cuando cambia el asignado (hoy en `ajax/portal_empresa/portal_empresa.php:217-237`, y su equivalente en `ajax/clientes/clientes.php` `personal_editar`), aplicada por cada marca de forma independiente.
- El endpoint `cupo_convenio` (prellenado del máximo permitido) se extiende: modo `global` devuelve el monto único como hoy; modo `marca` devuelve un arreglo `{mar_id, mar_descripcion, monto_max}`.

### Carga Masiva de Personal (`ajax/clientes/clientes.php:304-471` `personal_carga_masiva` + modal en `pages/clientes/view.php`)

Hoy la plantilla Excel tiene 3 columnas fijas (Cédula, Nombre, Cupo) y 3 acciones (Añadir, Actualizar cupo, Bloquear), con vista previa antes de aplicar. Está construida enteramente para modo `global` (usa `cli_valor_beneficio` como tope, escribe en `per_cupo_asignado`/`per_cupo_disponible`). Se adapta así:

- **Plantilla** (`descargarPlantillaCargaMasiva`): si el convenio es `global`, sin cambios (Cédula, Nombre, Cupo). Si es `marca`, la plantilla trae Cédula, Nombre y **una columna "Cupo <nombre de marca>" por cada marca activa** del catálogo.
- **Lectura del archivo** (`btn_procesar_carga_masiva`): en modo `marca`, en vez de una columna `cupo` fija se lee un mapa `{mar_id: monto}` a partir de los encabezados de marca presentes en el archivo (match por nombre de marca, igual que hoy se detecta el encabezado de la columna Cédula).
- **Backend `personal_carga_masiva`**: recibe el modo del convenio y bifurca:
  - `anadir`: en modo `marca`, cada columna de marca se valida contra `cliente_cupo_marca.ccm_monto_max` de esa marca; se requiere al menos una marca con monto > 0 (igual que hoy se requiere `cupo > 0`). Al crear el empleado se insertan las filas correspondientes en `personal_cupo_marca` (una por marca con monto > 0).
  - `actualizar_cupo`: en modo `marca`, se valida y actualiza **solo las columnas de marca presentes con valor en el archivo** — una celda vacía para una marca **no toca** el cupo que el empleado ya tenía ahí (actualización parcial, no se pone en 0). Cada marca actualizada aplica el mismo ajuste proporcional del disponible que hoy se hace a nivel global (líneas 403-405), pero calculado con los valores de `personal_cupo_marca` de esa marca.
  - `bloquear`: sin cambios — es a nivel de `personal.per_estado`, no depende del modo de cupo.
  - El preview (`solo_preview`) muestra, por fila, el detalle de qué marca(s) cambian y a qué monto, igual que hoy muestra el cupo único.

## Historial/trazabilidad (`personal_trazabilidad`)

Los registros de trazabilidad (alta manual, alta masiva, actualización de cupo, bloqueo) siguen igual; para cambios de cupo en modo `marca`, el campo `tra_campo` identifica la marca afectada (ej. `per_cupo_marca_<mar_id>`) para no perder el detalle de cuál marca cambió.

## Lógica de venta (POS — `ajax/pos/pos.php`)

- `buscar`: incluye `cli_modo_cupo` en la respuesta. Si es `marca`, resuelve la marca del local activo (`$_SESSION['loc_id']` → `local.mar_id`) y devuelve el asignado/disponible **de esa marca** (de `personal_cupo_marca`) en vez de las columnas globales de `personal`.
- `registrar`: si el convenio es `marca`, resuelve `mar_id` del local de sesión y busca la fila en `personal_cupo_marca` para `(per_id, mar_id)`. Si no existe o el disponible es insuficiente, se rechaza igual que hoy (mensaje de cupo insuficiente / cupo 0 si no hay fila). El descuento se aplica sobre `personal_cupo_marca.pcm_disponible`.
- Si `$_SESSION['loc_id']` está vacío en un convenio modo `marca` (terminal sin local asignado), se rechaza el pago con convenio por no poder determinar la marca.
- Convenio en modo `global` (o beneficio distinto de `Cupo`): sin cambios, comportamiento idéntico al actual.

## Reportes (Portal empresa — `ajax/portal_empresa/portal_empresa.php`)

El widget resumen (hoy `SUM(per_cupo_asignado)`, `SUM(per_cupo_disponible)`) y el detalle de empleado se extienden: para convenios en modo `marca`, el total se calcula sumando `personal_cupo_marca` y se agrega un desglose por marca junto al total.

## Migración

Script SQL nuevo (patrón `migrations/bloqueN_*.sql`):
- Agrega `cli_modo_cupo` a `cliente` con default `global`.
- Crea `cliente_cupo_marca` y `personal_cupo_marca`.
- No modifica datos existentes.

## Fuera de alcance

- No se permite mezclar ambos modos en un mismo convenio.
- No se migran convenios existentes a modo `marca` automáticamente.
- No se rebalancea automáticamente el cupo de empleados existentes cuando se crea una marca nueva.
- No se construye una carga masiva de *clientes/convenios* (solo existe hoy la de *personal*); no está en el pedido original.
