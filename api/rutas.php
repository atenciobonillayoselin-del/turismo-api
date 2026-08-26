<?php
// api/rutas.php
// ============================================================
// ✅ HEADERS CORS - DEBEN IR ANTES DE CUALQUIER OTRA COSA
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
        obtenerRutas($pdo);
        break;
    case 'POST':
        crearRuta($pdo);
        break;
    case 'PUT':
        actualizarRuta($pdo);
        break;
    case 'DELETE':
        eliminarRuta($pdo);
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

function obtenerRutas($pdo) {
    try {
        $stmt = $pdo->query("SELECT id_ruta, nombre, descripcion, tipo, color_hex FROM ruta WHERE activo = 1");
        $rutas = $stmt->fetchAll();
        
        // Formatear datos
        $data = [];
        foreach ($rutas as $ruta) {
            $data[] = [
                'id_ruta' => (int)$ruta['id_ruta'],
                'nombre' => $ruta['nombre'],
                'descripcion' => $ruta['descripcion'] ?? '',
                'tipo' => $ruta['tipo'] ?? 'minibus',
                'color_hex' => $ruta['color_hex'] ?? '#0066CC'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
}

function crearRuta($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['nombre'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Faltan datos requeridos'
        ]);
        return;
    }
    
    $sql = "INSERT INTO ruta (nombre, descripcion, tipo, color_hex, activo) 
            VALUES (:nombre, :descripcion, :tipo, :color_hex, 1)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':tipo' => $data['tipo'] ?? 'minibus',
        ':color_hex' => $data['color_hex'] ?? '#0066CC'
    ]);
    
    echo json_encode([
        'success' => true,
        'id' => (int)$pdo->lastInsertId()
    ]);
}

function actualizarRuta($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'error' => 'ID no proporcionado'
        ]);
        return;
    }
    
    $sql = "UPDATE ruta SET 
            nombre = :nombre,
            descripcion = :descripcion,
            tipo = :tipo,
            color_hex = :color_hex
            WHERE id_ruta = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':tipo' => $data['tipo'] ?? 'minibus',
        ':color_hex' => $data['color_hex'] ?? '#0066CC',
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true]);
}

function eliminarRuta($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'error' => 'ID no proporcionado'
        ]);
        return;
    }
    
    $sql = "UPDATE ruta SET activo = 0 WHERE id_ruta = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    echo json_encode(['success' => true]);
}
?>