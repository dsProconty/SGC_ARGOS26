-- Cierre de locales: motivo y fecha de cierre al inactivar + historial de cierres y reaperturas.
-- `local.loc_activo` ya existía (1 = activo). Un local no se elimina nunca (consumo, consumo_movimiento_local y
-- usuario lo referencian). El código crea estas columnas y la tabla solo si faltan (localesAsegurarEsquema()).
-- Sin ADD COLUMN IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064) — ver CLAUDE.md.

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'local' AND COLUMN_NAME = 'loc_motivo_cierre');
SET @ddl = IF(@col_existe = 0, 'ALTER TABLE `local` ADD COLUMN loc_motivo_cierre VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt_m FROM @ddl;
EXECUTE stmt_m;
DEALLOCATE PREPARE stmt_m;

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'local' AND COLUMN_NAME = 'loc_fecha_cierre');
SET @ddl = IF(@col_existe = 0, 'ALTER TABLE `local` ADD COLUMN loc_fecha_cierre DATE NULL', 'SELECT 1');
PREPARE stmt_f FROM @ddl;
EXECUTE stmt_f;
DEALLOCATE PREPARE stmt_f;

CREATE TABLE IF NOT EXISTS `local_trazabilidad` (
  `tra_id` INT NOT NULL AUTO_INCREMENT,
  `loc_id` INT NOT NULL,
  `id_user` INT NOT NULL,
  `tra_accion` VARCHAR(20) NOT NULL,
  `tra_motivo` VARCHAR(255) NULL,
  `tra_fecha_cierre` DATE NULL,
  `tra_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tra_id`),
  KEY `idx_ltra_loc` (`loc_id`, `tra_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de cierres y reaperturas de un local';
