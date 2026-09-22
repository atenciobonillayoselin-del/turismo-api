<?php
/**
 * verificar_estructura_tabla.php - Muestra la estructura actual de la tabla lugar_turistico
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM lugar_turistico");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tabla' => 'lugar_turistico',
        'total_columnas' => count($columnas),
        'columnas' => $columnas,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
