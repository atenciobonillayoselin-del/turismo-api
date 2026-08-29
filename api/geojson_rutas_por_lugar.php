<?php
// api/geojson_rutas_por_lugar.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../config/database.php';

$idLugar = $_GET['id_lugar'] ?? $_GET['id'] ?? null;
$grupo = $_GET['grupo'] ?? null;

if (!$idLugar && !$grupo) {
    echo json_encode(['error' => 'Se requiere id_lugar o grupo']);
    exit;
}

try {
    // 1. Obtener el lugar
    $sql = "SELECT id_lugar FROM lugar_turistico WHERE activo = 1";
    if ($idLugar) {
        $sql .= " AND id_lugar = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $idLugar]);
    } else {
        $sql .= " AND grupo_umap = :grupo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':grupo' => $grupo]);
    }
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lugar) {
        echo json_encode(['error' => 'Lugar no encontrado']);
        exit;
    }
    
    // 2. Obtener rutas asociadas a ese lugar
    $sql = "SELECT r.id_ruta, r.nombre, r.descripcion, r.color_hex, r.tipo,
                   rp.orden, rp.latitud, rp.longitud
            FROM ruta_lugar rl
            JOIN ruta r ON rl.id_ruta = r.id_ruta
            JOIN ruta_parada rp ON r.id_ruta = rp.id_ruta
            WHERE rl.id_lugar = :id_lugar AND r.activo = 1
            ORDER BY r.nombre, rp.orden";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_lugar' => $lugar['id_lugar']]);
    $paradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($paradas)) {
        echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
        exit;
    }
    
    // 3. Agrupar paradas por ruta
    $rutas = [];
    foreach ($paradas as $p) {
        $rutas[$p['id_ruta']]['nombre'] = $p['nombre'];
        $rutas[$p['id_ruta']]['descripcion'] = $p['descripcion'] ?? '';
        $rutas[$p['id_ruta']]['color'] = $p['color_hex'] ?? '#0066CC';
        $rutas[$p['id_ruta']]['tipo'] = $p['tipo'] ?? 'minibus';
        $rutas[$p['id_ruta']]['puntos'][] = [
            (float)$p['longitud'],
            (float)$p['latitud']
        ];
    }
    
    // 4. Construir GeoJSON
    $features = [];
    foreach ($rutas as $ruta) {
        if (count($ruta['puntos']) < 2) continue;
        
        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $ruta['puntos']
            ],
            'properties' => [
                'id' => $ruta['id'] ?? 0,
                'name' => $ruta['nombre'],
                'description' => $ruta['descripcion'],
                '_umap_options' => [
                    'color' => $ruta['color'],
                    'weight' => 5,
                    'opacity' => 0.8,
                    'popup' => '<b>' . $ruta['nombre'] . '</b><br>' . $ruta['descripcion']
                ]
            ]
        ];
    }
    
    echo json_encode([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>