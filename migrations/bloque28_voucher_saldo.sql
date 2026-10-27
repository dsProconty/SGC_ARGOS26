-- Voucher impreso (v7.55): saldo que le queda a la persona justo después de cada venta.
-- La app también crea esta columna sola (voucherAsegurarColumnas() en helpers/voucher_helpers.php),
-- pero se puede correr a mano. Si ya existe, MySQL avisa "Duplicate column name" y se ignora.
ALTER TABLE consumo ADD COLUMN con_saldo_posterior DECIMAL(10,2) NULL;
