<?php
// api/ruta_parada.php
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

// ============================================================
// CONEXIÓN A BASE DE DATOS
// ============================================================
require_once '../config/database.php';

$idRuta = $_GET['id_ruta'] ?? $_GET['id'] ?? null;

if (!$idRuta) {
    echo json_encode([
        'success' => false,
        'error' => 'Se requiere id_ruta'
    ]);
    exit();
}

try {
    // Verificar que la ruta existe
    $checkRuta = $pdo->prepare("SELECT id_ruta, nombre FROM ruta WHERE id_ruta = :id_ruta AND activo = 1");
    $checkRuta->execute([':id_ruta' => $idRuta]);
    $ruta = $checkRuta->fetch(PDO::FETCH_ASSOC);
    
    if (!$ruta) {
        echo json_encode([
            'success' => false,
            'error' => "No se encontró la ruta con ID: $idRuta"
        ]);
        exit();
    }

    // Obtener paradas de la ruta con orden
    $sql = "SELECT 
                p.id_parada,
                p.nombre,
                p.latitud,
                p.longitud,
                p.direccion,
                rp.orden,
                rp.es_inicio,
                rp.es_fin
            FROM ruta_parada rp
            INNER JOIN parada p ON rp.id_parada = p.id_parada
            WHERE rp.id_ruta = :id_ruta
            ORDER BY rp.orden ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_ruta' => $idRuta]);
    $paradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos para el frontend
    $data = [];
    foreach ($paradas as $parada) {
        $data[] = [
            'id_parada' => (int)$parada['id_parada'],
            'nombre' => $parada['nombre'],
            'latitud' => (float)$parada['latitud'],
            'longitud' => (float)$parada['longitud'],
            'direccion' => $parada['direccion'] ?? '',
            'orden' => (int)$parada['orden'],
            'es_inicio' => (bool)$parada['es_inicio'],
            'es_fin' => (bool)$parada['es_fin']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'ruta' => [
            'id' => (int)$ruta['id_ruta'],
            'nombre' => $ruta['nombre']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>