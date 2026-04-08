-- ============================================================
-- SGC ARGOS - Bloque 4: Módulo de Perfiles y Permisos
-- Fecha: 2026-04-08
-- ============================================================

-- --------------------------------------------------------
-- 1. Tabla de perfiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `perfil` (
  `per_id`          INT NOT NULL AUTO_INCREMENT,
  `per_nombre`      VARCHAR(100) NOT NULL,
  `per_descripcion` TEXT NULL,
  `per_es_sistema`  TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = perfil base, no eliminable',
  `per_activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `per_fecha`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`per_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Perfiles de acceso personalizables';

-- --------------------------------------------------------
-- 2. Tabla de módulos por perfil
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `perfil_modulo` (
  `pm_id`     INT NOT NULL AUTO_INCREMENT,
  `per_id`    INT NOT NULL,
  `pm_modulo` VARCHAR(50) NOT NULL COMMENT 'Clave del módulo: dashboard, gestiones, etc.',
  PRIMARY KEY (`pm_id`),
  UNIQUE KEY `uk_per_modulo` (`per_id`, `pm_modulo`),
  CONSTRAINT `fk_pm_perfil` FOREIGN KEY (`per_id`) REFERENCES `perfil` (`per_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Módulos habilitados por perfil';

-- --------------------------------------------------------
-- 3. FK en tabla usuario → perfil
-- --------------------------------------------------------
ALTER TABLE `usuario`
  ADD COLUMN IF NOT EXISTS `per_id` INT NULL COMMENT 'FK perfil asignado'
  AFTER `cli_id`,
  ADD CONSTRAINT `fk_user_perfil` FOREIGN KEY (`per_id`) REFERENCES `perfil` (`per_id`);

-- --------------------------------------------------------
-- 4. Insertar los 6 perfiles de sistema (migración de roles)
-- --------------------------------------------------------
INSERT INTO `perfil` (`per_nombre`, `per_descripcion`, `per_es_sistema`, `per_activo`) VALUES
('Super Admin',      'Acceso total al sistema',                                          1, 1),
('Supervisor',       'Acceso a gestiones, reportes, convenios y gift cards',             1, 1),
('Operador',         'Acceso a gestiones y reportes',                                    1, 1),
('Cajero',           'Acceso al punto de venta',                                         1, 1),
('Empresa Cliente',  'Acceso al portal empresa, nómina y gift cards',                    1, 1),
('Cliente GiftCard', 'Acceso exclusivo al módulo de gift cards',                         1, 1);

-- --------------------------------------------------------
-- 5. Asignar módulos a cada perfil de sistema
-- --------------------------------------------------------

-- Super Admin (per_id=1): todos los módulos
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(1, 'dashboard'), (1, 'gestiones'), (1, 'reportes'), (1, 'pos'),
(1, 'convenios'), (1, 'giftcard'), (1, 'venta_diferida'), (1, 'estado_cuenta'),
(1, 'portal_empresa'), (1, 'usuarios'), (1, 'configuracion'), (1, 'locales'),
(1, 'clientes'), (1, 'perfiles');

-- Supervisor (per_id=2)
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(2, 'dashboard'), (2, 'gestiones'), (2, 'reportes'), (2, 'convenios'),
(2, 'giftcard'), (2, 'venta_diferida'), (2, 'estado_cuenta'), (2, 'usuarios');

-- Operador (per_id=3)
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(3, 'gestiones'), (3, 'reportes');

-- Cajero (per_id=4)
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(4, 'pos');

-- Empresa Cliente (per_id=5)
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(5, 'portal_empresa'), (5, 'giftcard');

-- Cliente GiftCard (per_id=6)
INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`) VALUES
(6, 'giftcard');

-- --------------------------------------------------------
-- 6. Migrar usuarios existentes → asignar per_id según permisos_acceso
-- --------------------------------------------------------
UPDATE `usuario` SET `per_id` = 1 WHERE `permisos_acceso` = 'Super Admin';
UPDATE `usuario` SET `per_id` = 2 WHERE `permisos_acceso` = 'Supervisor';
UPDATE `usuario` SET `per_id` = 3 WHERE `permisos_acceso` = 'Operador';
UPDATE `usuario` SET `per_id` = 4 WHERE `permisos_acceso` = 'cajero';
UPDATE `usuario` SET `per_id` = 5 WHERE `permisos_acceso` = 'empresa_cliente';
UPDATE `usuario` SET `per_id` = 6 WHERE `permisos_acceso` = 'cliente_giftcard';
