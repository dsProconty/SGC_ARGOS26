-- Combos Especiales: paquetes promocionales con código único, reutilizable
-- por cualquier cliente hasta su fecha de caducidad, cobrables en el POS a
-- crédito (cargado al cupo de un empleado de convenio) o de contado (sin
-- cliente en el sistema). Ver
-- docs/superpowers/specs/2026-09-17-combos-especiales-design.md.

CREATE TABLE IF NOT EXISTS `combo_especial` (
  `ce_id`              INT NOT NULL AUTO_INCREMENT,
  `ce_nombre`          VARCHAR(150) NOT NULL,
  `ce_descripcion`     VARCHAR(255) DEFAULT NULL,
  `ce_codigo`          VARCHAR(50) NOT NULL,
  `ce_valor`           DECIMAL(10,2) NOT NULL,
  `ce_fecha_caducidad` DATE NOT NULL,
  `mar_id`             INT NOT NULL COMMENT 'Franquicia donde se puede cobrar',
  `id_user_creador`    INT DEFAULT NULL,
  `ce_fecha_creacion`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ce_id`),
  UNIQUE KEY `uk_ce_codigo` (`ce_codigo`),
  KEY `fk_ce_marca` (`mar_id`),
  CONSTRAINT `fk_ce_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`),
  CONSTRAINT `fk_ce_user` FOREIGN KEY (`id_user_creador`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Combos promocionales por código, reutilizables hasta su vencimiento';

ALTER TABLE `consumo`
  ADD COLUMN `con_combo_id` INT NULL COMMENT 'FK combo_especial si esta venta fue un combo' AFTER `con_monto_giftcard`,
  ADD CONSTRAINT `fk_consumo_combo` FOREIGN KEY (`con_combo_id`) REFERENCES `combo_especial` (`ce_id`);

-- El acceso a módulos no tiene bypass de Super Admin (se resuelve por filas
-- reales en perfil_modulo) — sin esto, hasta Super Admin quedaría afuera del
-- módulo nuevo hasta asignárselo a mano.
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`)
SELECT p.per_id, 'combos' FROM `perfil` p
WHERE LOWER(p.per_nombre) = 'super admin'
  AND NOT EXISTS (SELECT 1 FROM `perfil_modulo` pm WHERE pm.per_id = p.per_id AND pm.pm_modulo = 'combos');
