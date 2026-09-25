-- La tabla usuario nunca tuvo columna de cédula/documento -- por eso no se
-- podía buscar a nadie por cédula en Usuarios (caso real: MERCY AMARILIS
-- ALAY HOLGUIN, doc 0922100698, migrada como 'maalay01' pero invisible al
-- buscar por su cédula). Se agrega para las 1.387 cuentas de cajero/
-- supervisor migradas y para cualquier cuenta nueva a futuro.
-- Sin IF NOT EXISTS: no lo soporta el MySQL de producción (error 1064) --
-- se verifica a mano contra information_schema (ver CLAUDE.md).

SET @col_existe = (SELECT COUNT(1) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'documento');
SET @ddl_usuario = IF(@col_existe = 0,
  'ALTER TABLE usuario ADD COLUMN documento VARCHAR(20) NULL',
  'SELECT 1');
PREPARE stmt_usuario FROM @ddl_usuario;
EXECUTE stmt_usuario;
DEALLOCATE PREPARE stmt_usuario;
