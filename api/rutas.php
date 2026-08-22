<?php
// api/rutas.php
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
        $stmt = $pdo->query("SELECT * FROM ruta WHERE activo = 1");
        $rutas = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $rutas]);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function crearRuta($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "INSERT INTO ruta (nombre, descripcion, tipo, color_hex) 
            VALUES (:nombre, :descripcion, :tipo, :color_hex)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':tipo' => $data['tipo'] ?? 'minibus',
        ':color_hex' => $data['color_hex'] ?? '#0066CC'
    ]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function actualizarRuta($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['error' => 'ID no proporcionado']);
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
        echo json_encode(['error' => 'ID no proporcionado']);
        return;
    }
    
    $sql = "UPDATE ruta SET activo = 0 WHERE id_ruta = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    echo json_encode(['success' => true]);
}
?>