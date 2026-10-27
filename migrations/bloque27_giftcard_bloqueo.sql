-- Bloqueo / reactivación de Gift Cards aprobadas (v7.54).
-- Un código bloqueado pasa a codigo_gift_card.cgc_estado = 'bloqueado' (la columna ya es VARCHAR(15), no cambia).
-- Esta tabla guarda quién bloqueó o reactivó, cuándo, por qué y cuántos códigos.
-- La app también la crea sola (gcBloqueoAsegurarEsquema() en helpers/giftcard_bloqueo_helpers.php).
CREATE TABLE IF NOT EXISTS giftcard_bloqueo_historial (
    gbh_id       INT AUTO_INCREMENT PRIMARY KEY,
    lgc_id       INT NOT NULL,
    gbh_accion   VARCHAR(12) NOT NULL,
    gbh_alcance  VARCHAR(10) NOT NULL,
    gbh_cantidad INT NOT NULL DEFAULT 0,
    gbh_codigos  TEXT NULL,
    gbh_motivo   VARCHAR(255) NULL,
    id_user      INT NOT NULL,
    gbh_fecha    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_gbh_lote (lgc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
