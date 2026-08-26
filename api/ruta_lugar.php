<?php
// api/ruta_lugar.php
// ============================================================
// ✅ HEADERS CORS - DEBEN IR ANTES DE CUALQUIER OTRA COSA
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        obtenerRutasDeLugar($pdo);
        break;
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
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
        // Verificar que el lugar existe
        $checkLugar = $pdo->prepare("SELECT id_lugar, nombre FROM lugar_turistico WHERE id_lugar = :id_lugar AND activo = 1");
        $checkLugar->execute([':id_lugar' => $idLugar]);
        $lugar = $checkLugar->fetch(PDO::FETCH_ASSOC);
        
        if (!$lugar) {
            echo json_encode([
                'success' => false,
                'error' => "No se encontró el lugar con ID: $idLugar"
            ]);
            return;
        }
        
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
                ORDER BY rl.orden ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_lugar' => $idLugar]);
        $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear datos
        $data = [];
        foreach ($rutas as $ruta) {
            $data[] = [
                'id_ruta' => (int)$ruta['id_ruta'],
                'nombre' => $ruta['nombre'],
                'descripcion' => $ruta['descripcion'] ?? '',
                'tipo' => $ruta['tipo'] ?? 'minibus',
                'color_hex' => $ruta['color_hex'] ?? '#0066CC',
                'orden' => (int)($ruta['orden'] ?? 0),
                'distancia_km' => $ruta['distancia_km'] ? (float)$ruta['distancia_km'] : null
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'lugar' => [
                'id' => (int)$lugar['id_lugar'],
                'nombre' => $lugar['nombre']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
}
?>