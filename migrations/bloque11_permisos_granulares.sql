-- US-B: Permisos granulares dentro de un módulo.
-- perfil_modulo controla si un perfil ve un módulo completo (ej. "pos"),
-- pero no distingue acciones dentro de ese módulo (ej. cobrar vs anular).
-- perfil_permiso agrega esa segunda capa: una clave de permiso específica
-- (ej. 'pos.anular') que un perfil puede o no tener, además del módulo.
CREATE TABLE IF NOT EXISTS `perfil_permiso` (
  `pp_id`      INT NOT NULL AUTO_INCREMENT,
  `per_id`     INT NOT NULL,
  `pp_permiso` VARCHAR(50) NOT NULL COMMENT 'Clave de permiso granular, ej: pos.anular',
  PRIMARY KEY (`pp_id`),
  UNIQUE KEY `uk_per_permiso` (`per_id`, `pp_permiso`),
  CONSTRAINT `fk_pp_perfil` FOREIGN KEY (`per_id`) REFERENCES `perfil` (`per_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Permisos granulares (acciones específicas) por perfil';
