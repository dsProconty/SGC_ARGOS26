-- ============================================================
-- SGC ARGOS - Bloque 3: GiftCard Approval Flow
-- Fecha: 2026-04-07
-- Ejecutar DESPUÉS de bloque2_giftcard_pos.sql
-- ============================================================

-- --------------------------------------------------------
-- 1. Tabla de solicitudes de Gift Cards
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `giftcard_solicitud` (
  `sol_id`                  INT NOT NULL AUTO_INCREMENT,
  `id_user`                 INT NOT NULL             COMMENT 'FK usuario solicitante',
  `sol_cantidad`            INT NOT NULL             COMMENT 'Cantidad de códigos solicitados',
  `sol_cupo_codigo`         DECIMAL(10,2) NOT NULL   COMMENT 'Cupo por código',
  `sol_periodo_facturacion` DATE NOT NULL             COMMENT 'Período de facturación',
  `sol_fecha_caducidad`     DATE NOT NULL             COMMENT 'Fecha de caducidad de los códigos',
  `sol_estado`              VARCHAR(15) NOT NULL DEFAULT 'PENDING'
                            COMMENT 'PENDING | APPROVED | REJECTED',
  `sol_fecha_solicitud`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sol_lgc_id`              INT NULL                 COMMENT 'FK lote_gift_card generado tras aprobación',
  PRIMARY KEY (`sol_id`),
  KEY `idx_sol_user`   (`id_user`),
  KEY `idx_sol_estado` (`sol_estado`),
  CONSTRAINT `fk_sol_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Solicitudes de lotes de Gift Cards pendientes de aprobación';

-- --------------------------------------------------------
-- 2. Tabla de historial de aprobaciones (auditoría)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `giftcard_approval_history` (
  `aph_id`        INT NOT NULL AUTO_INCREMENT,
  `sol_id`        INT NOT NULL         COMMENT 'FK giftcard_solicitud',
  `admin_id`      INT NOT NULL         COMMENT 'FK usuario administrador que ejecutó la acción',
  `aph_accion`    VARCHAR(10) NOT NULL COMMENT 'APPROVE | REJECT',
  `aph_notas`     TEXT NULL            COMMENT 'Motivo del rechazo u observaciones',
  `aph_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`aph_id`),
  KEY `idx_aph_sol`   (`sol_id`),
  KEY `idx_aph_admin` (`admin_id`),
  CONSTRAINT `fk_aph_sol`   FOREIGN KEY (`sol_id`)   REFERENCES `giftcard_solicitud` (`sol_id`),
  CONSTRAINT `fk_aph_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial de auditoría de aprobaciones/rechazos de Gift Cards';
