<?php
/**
 * debug_env.php - Muestra las variables de entorno que recibe PHP
 * 
 * ⚠️ SOLO PARA DIAGNÓSTICO - ELIMINAR DESPUÉS
 */

header('Content-Type: application/json; charset=utf-8');

$envVars = [
    'PDO_HOST' => getenv('PDO_HOST') ?: 'NO SETEADO',
    'PDO_PORT' => getenv('PDO_PORT') ?: 'NO SETEADO',
    'PDO_DATABASE' => getenv('PDO_DATABASE') ?: 'NO SETEADO',
    'PDO_USERNAME' => getenv('PDO_USERNAME') ?: 'NO SETEADO',
    'PDO_PASSWORD' => getenv('PDO_PASSWORD') ? '****** (SETEADO)' : '❌ VACÍO O NO SETEADO',
    'PDO_SSL_CA' => getenv('PDO_SSL_CA') ?: 'NO SETEADO',
];

echo json_encode([
    'success' => true,
    'env_vars' => $envVars,
    'php_version' => PHP_VERSION,
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'desconocido',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);