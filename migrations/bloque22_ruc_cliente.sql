-- cliente no tenía columna de RUC (el sistema viejo sí: gvony_ads_company.ruc),
-- así que no se pudo migrar al pasar los convenios. Se agrega cli_ruc para
-- poder cargarlo desde la migración (_dump_viejo/11_backfill_ruc_clientes.sql)
-- y editarlo desde Clientes. Guarda RUC (13 dígitos) o cédula (10 dígitos).
-- Sin IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064) —
-- se verifica a mano contra information_schema (ver CLAUDE.md).

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cliente' AND COLUMN_NAME = 'cli_ruc');
SET @ddl_cliente = IF(@col_existe = 0,
  'ALTER TABLE cliente ADD COLUMN cli_ruc VARCHAR(20) NULL',
  'SELECT 1');
PREPARE stmt_cliente FROM @ddl_cliente;
EXECUTE stmt_cliente;
DEALLOCATE PREPARE stmt_cliente;
