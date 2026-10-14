-- Las ventas diferidas pasan a estar atadas a una marca (franquicia): venta_diferida.mar_id.
-- Por defecto ARGOS HOME; las ventas que ya existían quedan en ARGOS HOME. La marca decide en qué columna
-- del Estado de Cuenta sale cada cuota. (La app también crea la columna sola; este .sql es la misma lógica.)
-- Sin ADD COLUMN IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064) — ver CLAUDE.md.

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venta_diferida' AND COLUMN_NAME = 'mar_id');
SET @ddl = IF(@col_existe = 0, 'ALTER TABLE venta_diferida ADD COLUMN mar_id INT NULL', 'SELECT 1');
PREPARE stmt_vd FROM @ddl;
EXECUTE stmt_vd;
DEALLOCATE PREPARE stmt_vd;

-- Las que ya existían quedan en ARGOS HOME (por nombre: los ids cambian entre entornos)
UPDATE venta_diferida
   SET mar_id = (SELECT m.mar_id FROM marca m WHERE UPPER(TRIM(m.mar_descripcion)) = 'ARGOS HOME' ORDER BY m.mar_id ASC LIMIT 1)
 WHERE mar_id IS NULL;

SELECT COUNT(*) AS ventas_diferidas, SUM(mar_id IS NULL) AS sin_marca FROM venta_diferida;
