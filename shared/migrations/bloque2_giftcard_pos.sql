-- ============================================================
-- SGC ARGOS - Bloque 2: Gift Card + POS Gift Card
-- Fecha: 2026-03-20
-- ============================================================

-- Fecha de caducidad en códigos de gift card
ALTER TABLE `codigo_gift_card`
    ADD COLUMN `cgc_fecha_caducidad` DATE NULL COMMENT 'Fecha de caducidad del código';

-- Columnas de gift card en consumo
ALTER TABLE `consumo`
    ADD COLUMN `con_giftcard_codigo`  VARCHAR(50)    NULL    COMMENT 'Código de gift card usado',
    ADD COLUMN `con_monto_giftcard`   DECIMAL(10,2)  NOT NULL DEFAULT 0 COMMENT 'Monto pagado con gift card';
