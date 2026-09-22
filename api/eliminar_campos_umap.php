<?php
/**
 * eliminar_campos_umap.php - Elimina específicamente campos de uMap
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST']);
    exit;
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $camposAEliminar = ['grupo_umap', 'icono_umap', 'color_hex', 'categoria'];
    $resultados = [];
    
    foreach ($camposAEliminar as $campo) {
        try {
            // Verificar si el campo existe
            $stmt = $pdo->query("SHOW COLUMNS FROM lugar_turistico LIKE '$campo'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                $pdo->exec("ALTER TABLE lugar_turistico DROP COLUMN $campo");
                $resultados[] = "✅ Eliminado: $campo";
            } else {
                $resultados[] = "⏭️ No existe: $campo";
            }
        } catch (PDOException $e) {
            $resultados[] = "❌ Error en $campo: " . $e->getMessage();
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Proceso completado',
        'resultados' => $resultados,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
