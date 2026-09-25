-- Comisión que Argos cobra a cada marca (franquicia) al liquidar sus ventas
-- mensuales. Antes era un 12.5% fijo asumido a mano fuera del sistema;
-- ahora se guarda por marca porque no todas cobran lo mismo (ej. Vaco y
-- Vaca es 10%, el resto 12.5%). Usada en el reporte "Ventas por Locales
-- (Liquidación)" — pages/reportes/excel.php, case 'ventas por locales
-- liquidacion'.

-- ADD COLUMN IF NOT EXISTS no lo soporta la versión de MySQL de
-- producción (sintaxis más nueva) — se verifica a mano contra
-- information_schema para que el script sea idempotente igual.
SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marca' AND COLUMN_NAME = 'mar_comision');
SET @ddl_marca = IF(@col_existe = 0,
  'ALTER TABLE marca ADD COLUMN mar_comision DECIMAL(5,2) NOT NULL DEFAULT 12.50',
  'SELECT 1');
PREPARE stmt_marca FROM @ddl_marca;
EXECUTE stmt_marca;
DEALLOCATE PREPARE stmt_marca;

UPDATE marca SET mar_comision = 10.00 WHERE mar_descripcion = 'Vaco y Vaca';
