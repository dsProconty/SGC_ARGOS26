-- Cupo por marca "igual al sistema viejo": en Argos cada persona tenía una tarjeta
-- por marca, y cada tarjeta tenía su propio número, estado (ACTIVA/BLOQUEADA),
-- cupo y saldo. personal_cupo_marca solo guardaba marca + cupo + disponible, así
-- que se agregan el estado y la tarjeta de esa marca.
--   pcm_estado  : 'activo' | 'bloqueado' (el POS no deja cobrar en una marca bloqueada)
--   pcm_tarjeta : número de la tarjeta de esa persona en esa marca
-- Las filas que ya existían quedan 'activo' (cero cambio de comportamiento).
-- Sin ADD COLUMN IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064)
-- — se verifica a mano contra information_schema (ver CLAUDE.md).

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personal_cupo_marca' AND COLUMN_NAME = 'pcm_estado');
SET @ddl = IF(@col_existe = 0,
  'ALTER TABLE personal_cupo_marca ADD COLUMN pcm_estado VARCHAR(10) NOT NULL DEFAULT ''activo''',
  'SELECT 1');
PREPARE stmt_estado FROM @ddl;
EXECUTE stmt_estado;
DEALLOCATE PREPARE stmt_estado;

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personal_cupo_marca' AND COLUMN_NAME = 'pcm_tarjeta');
SET @ddl = IF(@col_existe = 0,
  'ALTER TABLE personal_cupo_marca ADD COLUMN pcm_tarjeta VARCHAR(20) NULL',
  'SELECT 1');
PREPARE stmt_tarjeta FROM @ddl;
EXECUTE stmt_tarjeta;
DEALLOCATE PREPARE stmt_tarjeta;
