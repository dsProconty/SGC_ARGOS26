<?php
/**
 * deploy.php — Webhook receiver para auto-deploy desde GitHub
 * Colocar en: /home/sgipro/public_html/SGC_ARGOS26/deploy.php
 */

$token   = 'argos26_deploy_2026';
$branch  = 'feature/nuevas-funcionalidades';
$repoDir = '/home/sgipro/public_html/SGC_ARGOS26';
$logFile = $repoDir . '/deploy.log';

// Verificar token en query string
$receivedToken = $_GET['token'] ?? '';
if (!hash_equals($token, $receivedToken)) {
    http_response_code(403);
    exit('Forbidden: token inválido');
}

$payload = file_get_contents('php://input');

// Solo disparar en push a la rama correcta
$data = json_decode($payload, true);
$pushedBranch = basename($data['ref'] ?? '');
if ($pushedBranch !== $branch && $branch !== '*') {
    http_response_code(200);
    exit("Ignorado: rama $pushedBranch");
}

// Ejecutar git pull
$cmd    = "cd $repoDir && git pull origin $branch 2>&1";
$output = shell_exec($cmd);
$log    = date('Y-m-d H:i:s') . " | Branch: $pushedBranch\n$output\n---\n";

file_put_contents($logFile, $log, FILE_APPEND);

http_response_code(200);
echo "Deploy OK\n" . $output;
