-- US-A: Nuevo perfil de sistema "Supervisor de local".
-- Igual que Cajero (módulo pos = puede cobrar) pero además con el
-- permiso granular pos.anular, para poder anular ventas del mismo día
-- en el local (ver bloque12/bloque11).
INSERT INTO `perfil` (`per_nombre`, `per_descripcion`, `per_es_sistema`, `per_activo`)
SELECT 'Supervisor de local', 'Acceso al punto de venta, con permiso para anular ventas del mismo día', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `perfil` WHERE `per_nombre` = 'Supervisor de local');

INSERT INTO `perfil_modulo` (`per_id`, `pm_modulo`)
SELECT p.per_id, 'pos' FROM `perfil` p
WHERE p.per_nombre = 'Supervisor de local'
  AND NOT EXISTS (SELECT 1 FROM `perfil_modulo` pm WHERE pm.per_id = p.per_id AND pm.pm_modulo = 'pos');

INSERT INTO `perfil_permiso` (`per_id`, `pp_permiso`)
SELECT p.per_id, 'pos.anular' FROM `perfil` p
WHERE p.per_nombre = 'Supervisor de local'
  AND NOT EXISTS (SELECT 1 FROM `perfil_permiso` pp WHERE pp.per_id = p.per_id AND pp.pp_permiso = 'pos.anular');
