-- ============================================================
-- SGC ARGOS - Bloque 5: Columna email en tabla usuario
-- Fecha: 2026-04-22
-- Ejecutar en phpMyAdmin si aún no existe la columna email
-- ============================================================

ALTER TABLE `usuario`
    ADD COLUMN `email` VARCHAR(150) NULL COMMENT 'Email del usuario para notificaciones';
