<?php
// api/rutas.php
// ============================================================
// ✅ HEADERS CORS
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

require_once __DIR__ . '/../config/database.php';

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
        // Verificar si se solicita una ruta específica
        $id_ruta = isset($_GET['id_ruta']) ? intval($_GET['id_ruta']) : 0;
        $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;
        
        // Si se solicita una ruta específica CON paradas
        if ($id_ruta > 0) {
            // Obtener la ruta
            $stmt = $pdo->prepare("SELECT * FROM ruta WHERE id_ruta = ? AND activo = 1");
            $stmt->execute([$id_ruta]);
            $ruta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ruta) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ruta no encontrada'
                ]);
                return;
            }
            
            // Obtener paradas de la ruta
            $stmt = $pdo->prepare("
                SELECT p.*, rp.orden, rp.es_inicio, rp.es_fin, rp.tiempo_estimado, rp.distancia_metros
                FROM parada p
                INNER JOIN ruta_parada rp ON p.id_parada = rp.id_parada
                WHERE rp.id_ruta = ?
                ORDER BY rp.orden ASC
            ");
            $stmt->execute([$id_ruta]);
            $paradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener lugares de interés en la ruta
            $stmt = $pdo->prepare("
                SELECT lt.*, rl.orden, rl.distancia_km
                FROM lugar_turistico lt
                INNER JOIN ruta_lugar rl ON lt.id_lugar = rl.id_lugar
                WHERE rl.id_ruta = ?
                ORDER BY rl.orden ASC
            ");
            $stmt->execute([$id_ruta]);
            $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'ruta' => $ruta,
                    'paradas' => $paradas,
                    'lugares_interes' => $lugares
                ]
            ]);
            return;
        }
        
        // Si no, listar todas las rutas (con filtro opcional por tipo)
        $sql = "SELECT id_ruta, nombre, descripcion, tipo, color_hex, activo, id_grupo_umap, sentido 
                FROM ruta 
                WHERE activo = 1";
        
        $params = [];
        if ($tipo) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
                'sentido' => $ruta['sentido'] ?? 'NORMAL',
                'grupo_umap' => $ruta['id_grupo_umap'] ?? ''
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