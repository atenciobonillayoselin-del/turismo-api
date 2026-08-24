<?php
// api/ruta_lugar.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        obtenerRutasDeLugar($pdo);
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

function obtenerRutasDeLugar($pdo) {
    $idLugar = $_GET['id_lugar'] ?? $_GET['id'] ?? null;
    
    if (!$idLugar) {
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere id_lugar o id'
        ]);
        return;
    }
    
    try {
        $sql = "SELECT 
                    r.id_ruta,
                    r.nombre,
                    r.descripcion,
                    r.tipo,
                    r.color_hex,
                    rl.orden,
                    rl.distancia_km
                FROM ruta_lugar rl
                JOIN ruta r ON rl.id_ruta = r.id_ruta
                WHERE rl.id_lugar = :id_lugar AND r.activo = 1
                ORDER BY rl.orden";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_lugar' => $idLugar]);
        $rutas = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $rutas
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
}
?>