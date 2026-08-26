-- ------------------------------------------------------------
-- Bloque 15: motivo de error en el envío automático/manual del
-- estado de cuenta (CL-EC). Sin esto, ec_estado_envio = 'error' no dice
-- si faltaba el correo del cliente o si mail() falló en el servidor.
-- ------------------------------------------------------------
ALTER TABLE estado_cuenta
    ADD COLUMN ec_error_detalle VARCHAR(255) NULL COMMENT 'Motivo del error cuando ec_estado_envio = error'
    AFTER ec_reintentos;
