<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db");
    $row = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Conexión exitosa',
        'version' => $row['version'],
        'database' => $row['db'],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}