<?php
header('Content-Type: application/json');

$ip = file_get_contents('https://ifconfig.me/ip');
$all = file_get_contents('https://ifconfig.me/all.json');

echo json_encode([
    'success' => true,
    'ip' => trim($ip),
    'detalles' => json_decode($all, true),
    'servidor' => $_SERVER['SERVER_ADDR'] ?? 'no disponible',
    'remoto' => $_SERVER['REMOTE_ADDR'] ?? 'no disponible'
]);