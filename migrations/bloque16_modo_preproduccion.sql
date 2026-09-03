-- Interruptor de seguridad para el envío de correos de Estados de Cuenta
-- (botón manual, cron/enviar_estados_cuenta.php, y el piggyback en
-- content.php). Mientras valga '1', ec_enviar_correo() nunca manda un
-- correo real, sin importar cuál de los 3 caminos lo dispare — ver
-- services/estado_cuenta_service.php > ec_modo_preproduccion().
--
-- Arranca en '1' (bloqueado) a propósito: mejor pecar de cauto. Para pasar
-- a producción real:
--   UPDATE configuracion SET cfg_valor = '0' WHERE cfg_clave = 'modo_preproduccion';

INSERT INTO configuracion (cfg_clave, cfg_valor, cfg_descripcion)
VALUES ('modo_preproduccion', '1', 'Si vale 1, bloquea el envío real de correos de Estados de Cuenta (botón manual, cron y piggyback)')
ON DUPLICATE KEY UPDATE cfg_valor = cfg_valor;
