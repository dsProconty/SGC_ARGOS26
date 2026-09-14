-- Mover transacción de local: corrige una venta que quedó registrada en el
-- local equivocado (cajero cambiado de local sin avisar al sistema), sin
-- necesidad de editar la base de datos a mano. Solo se permite mover entre
-- locales de la misma franquicia (misma marca) — ver
-- docs/superpowers/specs/2026-09-13-mover-transaccion-local-design.md.

CREATE TABLE IF NOT EXISTS `consumo_movimiento_local` (
  `cml_id`         INT NOT NULL AUTO_INCREMENT,
  `con_id`         INT NOT NULL COMMENT 'FK consumo movido',
  `id_user`        INT NOT NULL COMMENT 'FK usuario que hizo el movimiento',
  `loc_id_origen`  INT NOT NULL COMMENT 'FK local de donde salió',
  `loc_id_destino` INT NOT NULL COMMENT 'FK local a donde se movió',
  `cml_motivo`     TEXT NOT NULL,
  `cml_fecha`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cml_id`),
  KEY `idx_cml_con` (`con_id`),
  CONSTRAINT `fk_cml_consumo` FOREIGN KEY (`con_id`) REFERENCES `consumo` (`con_id`),
  CONSTRAINT `fk_cml_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`),
  CONSTRAINT `fk_cml_origen` FOREIGN KEY (`loc_id_origen`) REFERENCES `local` (`loc_id`),
  CONSTRAINT `fk_cml_destino` FOREIGN KEY (`loc_id_destino`) REFERENCES `local` (`loc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Auditoría de movimientos de local de una venta';
