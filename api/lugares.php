<?php
// api/lugares.php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        obtenerLugares($pdo);
        break;
    case 'POST':
        crearLugar($pdo);
        break;
    case 'PUT':
        actualizarLugar($pdo);
        break;
    case 'DELETE':
        eliminarLugar($pdo);
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

function obtenerLugares($pdo) {
    try {
        $stmt = $pdo->query("SELECT id_lugar, nombre, descripcion, latitud, longitud, direccion, categoria, calificacion, horario, imagen_url, panorama_url, activo FROM lugar_turistico WHERE activo = 1");
        $lugares = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $lugares]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function crearLugar($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "INSERT INTO lugar_turistico (nombre, descripcion, latitud, longitud, direccion, categoria, calificacion, horario, imagen_url, panorama_url) 
            VALUES (:nombre, :descripcion, :latitud, :longitud, :direccion, :categoria, :calificacion, :horario, :imagen_url, :panorama_url)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':latitud' => $data['latitud'],
        ':longitud' => $data['longitud'],
        ':direccion' => $data['direccion'] ?? '',
        ':categoria' => $data['categoria'] ?? 'Atracción turística',
        ':calificacion' => $data['calificacion'] ?? null,
        ':horario' => $data['horario'] ?? '',
        ':imagen_url' => $data['imagen_url'] ?? null,
        ':panorama_url' => $data['panorama_url'] ?? null
    ]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function actualizarLugar($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        return;
    }
    
    $sql = "UPDATE lugar_turistico SET 
            nombre = :nombre,
            descripcion = :descripcion,
            latitud = :latitud,
            longitud = :longitud,
            direccion = :direccion,
            categoria = :categoria,
            calificacion = :calificacion,
            horario = :horario,
            imagen_url = :imagen_url,
            panorama_url = :panorama_url
            WHERE id_lugar = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':latitud' => $data['latitud'],
        ':longitud' => $data['longitud'],
        ':direccion' => $data['direccion'] ?? '',
        ':categoria' => $data['categoria'] ?? 'Atracción turística',
        ':calificacion' => $data['calificacion'] ?? null,
        ':horario' => $data['horario'] ?? '',
        ':imagen_url' => $data['imagen_url'] ?? null,
        ':panorama_url' => $data['panorama_url'] ?? null,
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true]);
}

function eliminarLugar($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
        return;
    }
    
    $sql = "UPDATE lugar_turistico SET activo = 0 WHERE id_lugar = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    echo json_encode(['success' => true]);
}
?>