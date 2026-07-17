-- CO-01: cobranza de lotes Gift Card, independiente de cartera 30/60/90.
-- Reutiliza el motor de cartera/gestion/pago existente con un nuevo
-- car_tipo = 'GC'. lgc_id traza cada registro de cobranza al lote de
-- códigos que lo originó (nullable: los car_tipo 30/60/90/91 no lo usan).
ALTER TABLE cartera ADD COLUMN lgc_id INT NULL COMMENT 'FK lote_gift_card (solo car_tipo=GC)';
