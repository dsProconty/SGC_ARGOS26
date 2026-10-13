-- Bloqueos en cuatro niveles, independientes entre sí. El POS solo deja comprar si TODOS están activos:
--   convenio            cliente.cli_estado              ('activo' | 'bloqueado')  -> toda la empresa
--   convenio y marca    cliente_cupo_marca.ccm_estado   ('activo' | 'bloqueado')  -> una marca para toda la empresa
--   persona             personal.per_estado             (ya existía)              -> una persona, todas sus marcas
--   persona y marca     personal_cupo_marca.pcm_estado  (bloque23)                -> una persona en una marca
-- Los registros que ya existían quedan 'activo' (cero cambio de comportamiento).
-- cliente_trazabilidad guarda quién bloqueó/activó un convenio o una de sus marcas, y cuándo.
-- Sin ADD COLUMN IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064)
-- — se verifica a mano contra information_schema (ver CLAUDE.md).

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cliente' AND COLUMN_NAME = 'cli_estado');
SET @ddl = IF(@col_existe = 0,
  'ALTER TABLE cliente ADD COLUMN cli_estado VARCHAR(10) NOT NULL DEFAULT ''activo''',
  'SELECT 1');
PREPARE stmt_cli FROM @ddl;
EXECUTE stmt_cli;
DEALLOCATE PREPARE stmt_cli;

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cliente_cupo_marca' AND COLUMN_NAME = 'ccm_estado');
SET @ddl = IF(@col_existe = 0,
  'ALTER TABLE cliente_cupo_marca ADD COLUMN ccm_estado VARCHAR(10) NOT NULL DEFAULT ''activo''',
  'SELECT 1');
PREPARE stmt_ccm FROM @ddl;
EXECUTE stmt_ccm;
DEALLOCATE PREPARE stmt_ccm;

CREATE TABLE IF NOT EXISTS `cliente_trazabilidad` (
  `tra_id` INT NOT NULL AUTO_INCREMENT,
  `cli_id` INT NOT NULL,
  `id_user` INT NOT NULL,
  `tra_campo` VARCHAR(50) NOT NULL,
  `tra_campo_label` VARCHAR(100) NOT NULL,
  `tra_valor_anterior` VARCHAR(255) NULL,
  `tra_valor_nuevo` VARCHAR(255) NULL,
  `tra_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tra_id`),
  KEY `idx_ctra_cli` (`cli_id`, `tra_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de bloqueos/activaciones de un convenio y de sus marcas';
