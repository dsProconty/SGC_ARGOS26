<?php
// Script de emergencia: forzar git pull
// Eliminar este archivo después de usar
$token = $_GET['t'] ?? '';
if ($token !== 'argos26pull') { die('Forbidden'); }

$repoDir = '/home/sgipro/public_html/SGC_ARGOS26';
$branch  = 'feature/nuevas-funcionalidades';

$cmd    = "cd $repoDir && git fetch origin && git reset --hard origin/$branch 2>&1";
$output = shell_exec($cmd);

echo '<pre style="font-family:monospace;font-size:13px;">';
echo htmlspecialchars($output);
echo '</pre>';
echo '<p style="color:green;font-weight:bold;">Listo. Elimina este archivo cuando termines.</p>';
