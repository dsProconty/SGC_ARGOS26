-- CL-A (revisión): el Tipo de Cliente (Empresarial / Gift Card / Mixto) pasa
-- a ser un campo manual elegido en el modal de Clientes, en vez de derivarse
-- automáticamente de si el cliente tiene Personal y/o lotes de Gift Card.
ALTER TABLE cliente ADD COLUMN cli_tipo_cliente VARCHAR(20) NULL COMMENT 'Empresarial | Gift Card | Mixto (elegido manualmente)';
