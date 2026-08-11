-- CU-01: cupos globales y diferenciados por marca.
-- cli_modo_cupo define, por convenio, si el cupo (cli_valor_beneficio) es
-- global entre todas las marcas o si se reparte en montos independientes
-- por marca (cliente_cupo_marca / personal_cupo_marca). Los convenios
-- existentes quedan en 'global' por defecto: cero cambio de comportamiento.
ALTER TABLE cliente ADD COLUMN cli_modo_cupo VARCHAR(10) NOT NULL DEFAULT 'global' COMMENT 'global | marca — solo aplica si cli_tipo_beneficio = Cupo';

CREATE TABLE IF NOT EXISTS `cliente_cupo_marca` (
  `cli_id` INT NOT NULL,
  `mar_id` INT NOT NULL,
  `ccm_monto_max` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Máximo que el convenio permite asignar a un empleado en esa marca',
  PRIMARY KEY (`cli_id`, `mar_id`),
  CONSTRAINT `fk_ccm_cliente` FOREIGN KEY (`cli_id`) REFERENCES `cliente` (`cli_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccm_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tope de cupo por marca de un convenio en modo "marca" (CU-01)';

CREATE TABLE IF NOT EXISTS `personal_cupo_marca` (
  `per_id` INT NOT NULL,
  `mar_id` INT NOT NULL,
  `pcm_asignado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pcm_disponible` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`per_id`, `mar_id`),
  CONSTRAINT `fk_pcm_personal` FOREIGN KEY (`per_id`) REFERENCES `personal` (`per_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcm_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cupo de un empleado en una marca específica, cuando su convenio está en modo "marca" (CU-01). Ausencia de fila = cupo 0 en esa marca.';
