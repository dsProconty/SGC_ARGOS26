-- ============================================================
-- SGC ARGOS - Bloque 1: Migraciones Base
-- Fecha: 2026-03-17
-- Spec: Especificación Técnica v1.2
-- ============================================================

-- ------------------------------------------------------------
-- 1. ALTER TABLE cliente (HU10)
-- ------------------------------------------------------------
ALTER TABLE cliente
    ADD COLUMN cli_tipo_beneficio VARCHAR(15) NULL COMMENT 'Porcentaje | Cupo',
    ADD COLUMN cli_valor_beneficio DECIMAL(10,2) NULL COMMENT 'Monto del cupo si tipo=Cupo',
    ADD COLUMN cli_tipo_cartera VARCHAR(5) NULL COMMENT '30 | 60 | 90 | 90+';

-- ------------------------------------------------------------
-- 2. ALTER TABLE personal (HU02, HU09)
-- ------------------------------------------------------------
ALTER TABLE personal
    ADD COLUMN per_correo VARCHAR(150) NULL COMMENT 'Email para OTP',
    ADD COLUMN per_estado VARCHAR(10) NOT NULL DEFAULT 'activo' COMMENT 'activo | bloqueado | inactivo',
    ADD COLUMN per_cupo_asignado DECIMAL(10,2) NULL COMMENT 'Cupo total asignado al empleado',
    ADD COLUMN per_cupo_disponible DECIMAL(10,2) NULL COMMENT 'Cupo restante disponible';

-- ------------------------------------------------------------
-- 3. ALTER TABLE consumo (HU01, HU02, HU03)
-- ------------------------------------------------------------
ALTER TABLE consumo
    ADD COLUMN id_user INT NULL COMMENT 'FK usuario - cajero que registró la venta',
    ADD COLUMN con_monto_convenio DECIMAL(10,2) NULL COMMENT 'Monto cargado al convenio',
    ADD COLUMN con_monto_externo DECIMAL(10,2) NULL COMMENT 'Monto pago externo (pago mixto)',
    ADD COLUMN con_otp_validado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si OTP fue validado',
    ADD COLUMN con_voucher_impreso TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si voucher fue impreso';

-- ------------------------------------------------------------
-- 4. ALTER TABLE local (HU07)
-- ------------------------------------------------------------
ALTER TABLE local
    ADD COLUMN loc_provincia VARCHAR(10) NULL COMMENT 'sierra | costa | oriente',
    ADD COLUMN loc_activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo';

-- ------------------------------------------------------------
-- 5. ALTER TABLE usuario (HU01)
-- Ampliar permisos_acceso para nuevos roles
-- ------------------------------------------------------------
ALTER TABLE usuario
    ADD COLUMN loc_id INT NULL COMMENT 'FK local - sucursal asignada al cajero',
    MODIFY COLUMN permisos_acceso VARCHAR(50) NOT NULL DEFAULT 'Operador'
        COMMENT 'Super Admin | Operador | cajero | cliente_giftcard | empresa_cliente';

-- ------------------------------------------------------------
-- 6. NUEVA TABLA: venta_diferida (HU06)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS venta_diferida (
    vd_id           INT             NOT NULL AUTO_INCREMENT,
    per_id          INT             NOT NULL COMMENT 'FK personal',
    id_user         INT             NOT NULL COMMENT 'FK usuario - operador que registró',
    vd_descripcion  TEXT            NOT NULL COMMENT 'Descripción libre del producto',
    vd_monto_total  DECIMAL(10,2)   NOT NULL COMMENT 'Valor total a financiar',
    vd_num_cuotas   INT             NOT NULL COMMENT 'Número total de cuotas',
    vd_cuotas_pagadas INT           NOT NULL DEFAULT 0 COMMENT 'Cuotas cobradas hasta la fecha',
    vd_monto_cuota  DECIMAL(10,2)   NOT NULL COMMENT 'Valor fijo por cuota',
    vd_fecha_inicio DATE            NOT NULL COMMENT 'Período de corte en que inicia el cobro',
    vd_estado       VARCHAR(15)     NOT NULL DEFAULT 'activo' COMMENT 'activo | completado | cancelado',
    PRIMARY KEY (vd_id),
    KEY idx_vd_per_id (per_id),
    KEY idx_vd_id_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. NUEVA TABLA: lote_gift_card (HU08)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lote_gift_card (
    lgc_id                  INT             NOT NULL AUTO_INCREMENT,
    id_user                 INT             NOT NULL COMMENT 'FK usuario - cliente GiftCard',
    lgc_cantidad            INT             NOT NULL COMMENT 'Cantidad de códigos en el lote',
    lgc_cupo_codigo         DECIMAL(10,2)   NOT NULL COMMENT 'Cupo por código',
    lgc_fecha               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha generación',
    lgc_periodo_facturacion DATE            NOT NULL COMMENT 'Período de corte para facturación',
    PRIMARY KEY (lgc_id),
    KEY idx_lgc_id_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. NUEVA TABLA: codigo_gift_card (HU08)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS codigo_gift_card (
    cgc_id              INT             NOT NULL AUTO_INCREMENT,
    lgc_id              INT             NOT NULL COMMENT 'FK lote_gift_card',
    cgc_codigo          VARCHAR(50)     NOT NULL COMMENT 'Código alfanumérico único',
    cgc_cupo_inicial    DECIMAL(10,2)   NOT NULL COMMENT 'Saldo inicial',
    cgc_cupo_disponible DECIMAL(10,2)   NOT NULL COMMENT 'Saldo disponible actual',
    cgc_estado          VARCHAR(15)     NOT NULL DEFAULT 'activo' COMMENT 'activo | consumido | vencido | anulado',
    cgc_fecha_activacion DATETIME       NOT NULL COMMENT 'Activación automática al generar',
    cgc_fecha_uso       DATETIME        NULL COMMENT 'Fecha de consumo (NULL si no usado)',
    PRIMARY KEY (cgc_id),
    UNIQUE KEY uk_cgc_codigo (cgc_codigo),
    KEY idx_cgc_lgc_id (lgc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. NUEVA TABLA: estado_cuenta (HU05)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS estado_cuenta (
    ec_id               INT             NOT NULL AUTO_INCREMENT,
    cli_id              INT             NOT NULL COMMENT 'FK cliente',
    ec_periodo_inicio   DATE            NOT NULL COMMENT 'Inicio del período facturado',
    ec_periodo_fin      DATE            NOT NULL COMMENT 'Fecha de corte',
    ec_monto_total      DECIMAL(10,2)   NOT NULL COMMENT 'Total consumos del período',
    ec_archivo_pdf      VARCHAR(500)    NULL COMMENT 'Ruta del PDF generado',
    ec_fecha_generacion DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ec_fecha_envio      DATETIME        NULL COMMENT 'NULL si aún no enviado',
    ec_estado_envio     VARCHAR(15)     NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente | enviado | error',
    ec_reintentos       INT             NOT NULL DEFAULT 0,
    PRIMARY KEY (ec_id),
    KEY idx_ec_cli_id (cli_id),
    KEY idx_ec_periodo (ec_periodo_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
