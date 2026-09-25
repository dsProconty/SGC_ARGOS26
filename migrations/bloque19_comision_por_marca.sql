-- Comisión que Argos cobra a cada marca (franquicia) al liquidar sus ventas
-- mensuales. Antes era un 12.5% fijo asumido a mano fuera del sistema;
-- ahora se guarda por marca porque no todas cobran lo mismo (ej. Vaco y
-- Vaca es 10%, el resto 12.5%). Usada en el reporte "Ventas por Locales
-- (Liquidación)" — pages/reportes/excel.php, case 'ventas por locales
-- liquidacion'.

ALTER TABLE marca ADD COLUMN IF NOT EXISTS mar_comision DECIMAL(5,2) NOT NULL DEFAULT 12.50;

UPDATE marca SET mar_comision = 10.00 WHERE mar_descripcion = 'Vaco y Vaca';
