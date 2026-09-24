<?php
/**
 * categorias.php - Lista de categorías de lugares turísticos
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

try {
    $sql = "SELECT id_categoria, nombre, slug, icono 
            FROM categoria_lugar 
            WHERE activo = 1 
            ORDER BY id_categoria ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($categorias as $cat) {
        $data[] = [
            'id_categoria' => (int)$cat['id_categoria'],
            'nombre' => $cat['nombre'],
            'slug' => $cat['slug'],
            'icono' => $cat['icono'],
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
