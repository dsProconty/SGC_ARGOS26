<?php
/**
 * Migración: backfill pagos históricos como "Aprobados"
 * Ejecutar UNA sola vez desde el navegador: localhost/SGC_ARGOS26/migrations/backfill_pagos_confirmados.php
 * Luego eliminar o proteger este archivo.
 */
require_once '../config/database.php';

// Asegurar que las columnas existen
$mysqli->query("ALTER TABLE pago ADD COLUMN IF NOT EXISTS pag_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'");
$mysqli->query("ALTER TABLE pago ADD COLUMN IF NOT EXISTS pag_fecha_confirmacion DATETIME NULL");
$mysqli->query("ALTER TABLE pago ADD COLUMN IF NOT EXISTS pag_confirmado_por VARCHAR(100) NULL");

// Actualizar TODOS los pagos históricos que no han pasado por el flujo de confirmación
$sql = "UPDATE pago
        SET pag_estado            = 'confirmado',
            pag_fecha_confirmacion = COALESCE(pag_fecha, NOW()),
            pag_confirmado_por    = 'Aprobado - Registro histórico'
        WHERE pag_estado = 'pendiente'
          AND pag_fecha_confirmacion IS NULL";

$ok = $mysqli->query($sql);
$afectados = $mysqli->affected_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración pagos</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 60px auto; }
        .ok  { background:#d4edda; border:1px solid #c3e6cb; padding:20px; border-radius:6px; }
        .err { background:#f8d7da; border:1px solid #f5c6cb; padding:20px; border-radius:6px; }
        h2   { margin-top:0; }
    </style>
</head>
<body>
<?php if ($ok): ?>
    <div class="ok">
        <h2>✓ Migración completada</h2>
        <p><strong><?php echo $afectados; ?></strong> pagos históricos actualizados como <em>"Aprobado - Registro histórico"</em>.</p>
        <p>Ya puedes eliminar este archivo del servidor.</p>
    </div>
<?php else: ?>
    <div class="err">
        <h2>✗ Error</h2>
        <p><?php echo $mysqli->error; ?></p>
    </div>
<?php endif; ?>
</body>
</html>
