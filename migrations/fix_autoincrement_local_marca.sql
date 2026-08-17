-- ─────────────────────────────────────────────────────────────
-- FIX: Resincronización de AUTO_INCREMENT para tabla local y marca
-- Ejecutar cuando INSERT falla por conflicto de clave primaria
-- (ocurre tras borrados manuales o importaciones sin resetear el contador)
--
-- MySQL ajusta AUTO_INCREMENT al MAX(id)+1 automáticamente
-- cuando el valor indicado es menor o igual al máximo actual.
-- ─────────────────────────────────────────────────────────────

ALTER TABLE local AUTO_INCREMENT = 1;
ALTER TABLE marca AUTO_INCREMENT = 1;
