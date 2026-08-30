<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    
    echo json_encode([
        'status' => 'OK',
        'pdo_exists' => isset($pdo),
        'connection_ok' => $pdo ? true : false,
        'tables' => $pdo ? $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) : [],
        'php_version' => PHP_VERSION,
        'error_reporting' => error_reporting()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}