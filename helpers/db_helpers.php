<?php
// Migraciones "perezosas" seguras: agregan una columna solo si no existe
// todavía, sin depender de ADD COLUMN IF NOT EXISTS — no soportado por el
// MySQL/MariaDB de producción (error 1064) — ni de que mysqli levante
// excepción ante un "Duplicate column name" en PHP más nuevo (como local,
// a diferencia de la producción real que corre PHP < 7.1).
function agregarColumnaSiNoExiste($mysqli, $tabla, $columna, $definicionSql) {
    $tabla   = mysqli_real_escape_string($mysqli, $tabla);
    $columna = mysqli_real_escape_string($mysqli, $columna);
    $existe  = $mysqli->query(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tabla' AND COLUMN_NAME = '$columna'"
    );
    if ($existe && $existe->num_rows > 0) {
        return;
    }
    $mysqli->query("ALTER TABLE $tabla ADD COLUMN $columna $definicionSql");
}
