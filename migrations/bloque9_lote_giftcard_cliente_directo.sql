-- GC-B: permite que el Super Admin cree un lote de Gift Cards directamente
-- (sin pasar por una solicitud del cliente final) y lo asocie a una empresa
-- que ya existe en Clientes, o a una nueva creada en el momento. Antes, el
-- cliente de un lote solo se podía rastrear vía lote_gift_card.id_user ->
-- usuario.cli_id, lo que exigía que existiera un usuario de Portal Empresa.
-- Ahora el lote puede apuntar directo al cliente aunque no tenga login.
ALTER TABLE lote_gift_card ADD COLUMN cli_id INT NULL COMMENT 'FK cliente (empresa a la que pertenece el lote)';
