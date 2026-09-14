# Mover transacción de local

## Contexto

Cuando un cajero cambia de local sin que se actualice el sistema, sigue facturando con su mismo usuario — y las ventas quedan registradas en `consumo.loc_id` del local viejo en vez del nuevo. Hoy la única corrección posible es editar la base de datos a mano. El cliente (Proconty) pidió, vía nota de voz del 2026-09-11, poder hacer esta corrección desde el sistema sin depender de un desarrollador.

Decisiones confirmadas con el cliente (brainstorming del 2026-09-11):

1. Acceso vía un permiso granular nuevo y asignable (mismo patrón que `pos.anular`), no restringido a Super Admin.
2. Solo se puede mover una transacción a un local de la **misma franquicia** (misma `marca`) — evita el problema de tener que revalidar/transferir cupo por marca entre marcas distintas.
3. Sin límite de tiempo, pero el motivo es obligatorio y queda auditado.
4. Si la transacción ya salió en un Estado de Cuenta enviado al convenio, el sistema avisa antes de confirmar, pero permite mover igual.
5. Para identificar la transacción correcta hace falta poder ver y filtrar por **cajero** (quién cobró), no solo por fecha/local — hoy el Historial de Ventas no lo expone.

## Alcance

- Un botón nuevo "Mover de local" en `pages/pos/historial.php`, junto al botón "Anular" que ya existe por cada venta.
- Nuevo permiso granular `pos.mover_local`.
- Filtro y columna "Cajero" en el Historial de Ventas (requisito para poder ubicar la transacción a mover).
- El filtro de Local (hoy solo visible para Super Admin/Administrador) y el nuevo filtro de Cajero se habilitan también para cualquiera con `pos.mover_local`, aunque no sea administrador general.
- Fuera de alcance: mover entre franquicias distintas, revalidación/transferencia de cupo por marca, límite de tiempo para mover, bloquear el movimiento si ya está facturada (solo se avisa).

## Modelo de datos

**`consumo_movimiento_local`** (nueva, mismo espíritu que `consumo_anulacion`):

```sql
CREATE TABLE `consumo_movimiento_local` (
  `cml_id`          INT NOT NULL AUTO_INCREMENT,
  `con_id`          INT NOT NULL COMMENT 'FK consumo movido',
  `id_user`         INT NOT NULL COMMENT 'FK usuario que hizo el movimiento',
  `loc_id_origen`   INT NOT NULL COMMENT 'FK local de donde salió',
  `loc_id_destino`  INT NOT NULL COMMENT 'FK local a donde se movió',
  `cml_motivo`      TEXT NOT NULL,
  `cml_fecha`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cml_id`),
  KEY `idx_cml_con` (`con_id`),
  CONSTRAINT `fk_cml_consumo` FOREIGN KEY (`con_id`) REFERENCES `consumo` (`con_id`),
  CONSTRAINT `fk_cml_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`),
  CONSTRAINT `fk_cml_origen` FOREIGN KEY (`loc_id_origen`) REFERENCES `local` (`loc_id`),
  CONSTRAINT `fk_cml_destino` FOREIGN KEY (`loc_id_destino`) REFERENCES `local` (`loc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

No hace falta tocar `consumo`: el movimiento es un `UPDATE consumo SET loc_id = <destino>`.

## Permisos

- `PERMISOS_GRANULARES` en `ajax/perfiles/perfiles.php` y `$permisos_granulares` en `pages/perfiles/view.php`: agregar `'pos.mover_local' => 'Mover una venta a otro local (Punto de Venta)'`. No se asigna por defecto a ningún perfil — el cliente decide a quién dárselo desde Perfiles y Permisos.
- Como el cupo por marca nunca entra en juego (destino siempre misma marca que el origen), no hace falta ninguna validación de cupo al mover.

## Backend (`ajax/pos/pos.php`)

**`historial_filtro` (existente, se amplía):**

- Bug pre-existente que bloquea el diseño: la visibilidad ancha (ver todos los locales/cajeros, no solo los propios) hoy se decide con `$_SESSION['permisos_acceso']` crudo (`in_array($permisos, ['Super Admin', 'Administrador'])`), no con los helpers `esSuperAdmin()`/`tienePermiso()` que sí reconocen perfiles personalizados. Alguien con solo el permiso `pos.mover_local` (sin ser Super Admin/Administrador) quedaría igual limitado a `c.id_user = $id_user` y no podría buscar la venta de otro cajero. Se reemplaza por:
  ```php
  $puedeVerTodo = esSuperAdmin($mysqli) || tienePerfil($mysqli, 'Administrador') || tienePermiso($mysqli, 'pos.mover_local');
  ```
- Nuevo filtro opcional `id_user_cajero` (solo aplica si `$puedeVerTodo`, igual que `loc_id`).
- El SELECT agrega `u.name_user AS cajero_nombre` (`LEFT JOIN usuario u ON c.id_user = u.id_user`), `l.loc_direccion AS local_nombre` (`LEFT JOIN local l ON c.loc_id = l.loc_id`), y `(SELECT COUNT(*) FROM consumo_movimiento_local cml WHERE cml.con_id = c.con_id) AS con_veces_movida`.

**`locales_para_mover` (nuevo, GET):**

- Requiere `tienePermiso($mysqli, 'pos.mover_local')`.
- Input: `con_id`. Resuelve el `loc_id` actual del consumo y su `mar_id` (`cupoMarcaDeLocal`). Devuelve la lista de locales activos de esa misma marca, excluyendo el local actual.

**`mover_local` (nuevo, POST):**

- Requiere `tienePermiso($mysqli, 'pos.mover_local')`.
- Input: `con_id`, `loc_id_destino`, `motivo`, `confirmar` (bool, default false).
- Validaciones: consumo existe; no está `anulado`; motivo no vacío; `loc_id_destino` distinto al actual; `cupoMarcaDeLocal(destino) === cupoMarcaDeLocal(origen)` (si no, error "Solo se puede mover a un local de la misma franquicia").
- Aviso de Estado de Cuenta ya enviado: si el consumo tiene `per_id`, busca si existe una fila en `estado_cuenta` con `cli_id` del empleado (vía `personal.cli_id`) y `con_fecha` dentro de `[ec_periodo_inicio, ec_periodo_fin]` con `ec_estado_envio = 'enviado'` — mismo criterio que usa `ec_generar_estado_cuenta()` para incluir un consumo en un período. Si existe y `confirmar` no vino en `true`, responde `{success:false, requiere_confirmacion:true, mensaje:'Esta venta ya salió en un Estado de Cuenta enviado el DD/MM/AAAA. ¿Moverla igual?'}` sin tocar nada — el frontend reintenta con `confirmar=1`.
- Si todo pasa: transacción que actualiza `consumo.loc_id` e inserta en `consumo_movimiento_local` (`id_user` = sesión actual).

**`ver_movimiento_local` (nuevo, GET):** mismo patrón que `ver_anulacion` — último movimiento de un `con_id`, con nombre de quien lo hizo, local origen/destino y motivo.

## Frontend (`pages/pos/historial.php`)

- `$puedeVerTodo` (misma condición ampliada de arriba) controla mostrar los filtros de Local **y** Cajero (nuevo, un `<select>` con los usuarios activos).
- `$puedeMoverLocal = esSuperAdmin($mysqli) || tienePermiso($mysqli, 'pos.mover_local');` controla el botón "Mover de local" por fila (solo en ventas no anuladas) y un botón "Ver movimiento" (ícono) cuando `con_veces_movida > 0`, visible para cualquiera que vea el historial (no solo quien puede mover).
- Nuevas columnas en la tabla: **Cajero** y **Local**.
- Modal "Mover de Local": al abrir, pide a `locales_para_mover` la lista de destinos válidos (si viene vacía, avisa "no hay otro local de la misma franquicia"); select de destino + textarea de motivo obligatorio. Al confirmar, llama `mover_local`; si la respuesta trae `requiere_confirmacion`, muestra el aviso y un botón "Mover igual" que reintenta con `confirmar=1`.
- Modal "Ver Movimiento": igual que "Ver motivo de anulación", muestra quién, cuándo, de qué local a cuál, y el motivo.
