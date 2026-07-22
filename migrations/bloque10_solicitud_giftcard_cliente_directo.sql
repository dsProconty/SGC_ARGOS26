-- GC-B (corrección): crear un lote directo desde el módulo Gift Card ya NO
-- se aprueba solo — genera una giftcard_solicitud PENDING como cualquier
-- otra, para que pase por el mismo flujo de "Aprobaciones Pendientes". Como
-- quien la crea es el Super Admin (no necesariamente un usuario de Portal
-- Empresa con cli_id), la solicitud necesita poder apuntar directo al
-- cliente elegido/creado en el modal, sin depender de usuario.cli_id.
ALTER TABLE giftcard_solicitud ADD COLUMN cli_id INT NULL COMMENT 'FK cliente (si la solicitud fue creada directo por un Super Admin para un cliente específico)';
