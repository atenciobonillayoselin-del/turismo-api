<?php
/**
 * vaciar_base_datos.php - Vacia todas las tablas de la base de datos
 * ⚠️ USAR CON PRECAUCIÓN - ESTO ELIMINA TODOS LOS DATOS
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir método POST para evitar ejecuciones accidentales por GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST para vaciar la base de datos.'
    ]);
    exit;
}

try {
    // Obtener todas las tablas
    $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tablas)) {
        echo json_encode([
            'success' => true,
            'mensaje' => 'No hay tablas en la base de datos',
            'tablas_vaciadas' => []
        ]);
        exit;
    }
    
    $tablasVaciadas = [];
    $errores = [];
    
    // Desactivar restricciones de clave foránea temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tablas as $tabla) {
        try {
            // Vaciar la tabla
            $pdo->exec("TRUNCATE TABLE `$tabla`");
            $tablasVaciadas[] = $tabla;
        } catch (PDOException $e) {
            $errores[] = [
                'tabla' => $tabla,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Reactivar restricciones de clave foránea
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Base de datos vaciada exitosamente',
        'tablas_vaciadas' => $tablasVaciadas,
        'total_tablas' => count($tablasVaciadas),
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Asegurar que las restricciones se reactiven en caso de error
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignorar error al reactivar restricciones
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
