-- session_version en usuario: permite invalidar la sesión activa de un
-- cajero cuando se le reasigna de local (ver ajax/users/users.php y
-- main.php). Hasta ahora esta columna solo se creaba de forma perezosa
-- dentro de esa acción AJAX (ALTER TABLE ... IF NOT EXISTS), nunca por
-- una migración real, así que cualquier entorno donde nadie haya usado
-- "reasignar local del cajero" no la tiene y main.php falla en cada carga.

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS session_version INT NOT NULL DEFAULT 0;
