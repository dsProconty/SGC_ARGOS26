-- PV-F: anular una venta el mismo día, con justificación obligatoria y
-- trazabilidad de quién la anuló y por qué.
CREATE TABLE IF NOT EXISTS `consumo_anulacion` (
  `can_id`     INT NOT NULL AUTO_INCREMENT,
  `con_id`     INT NOT NULL COMMENT 'FK consumo anulado',
  `id_user`    INT NOT NULL COMMENT 'FK usuario que anuló',
  `can_motivo` TEXT NOT NULL,
  `can_fecha`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`can_id`),
  KEY `idx_can_con` (`con_id`),
  CONSTRAINT `fk_can_consumo` FOREIGN KEY (`con_id`) REFERENCES `consumo` (`con_id`),
  CONSTRAINT `fk_can_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Auditoría de anulación de ventas (PV-F)';
