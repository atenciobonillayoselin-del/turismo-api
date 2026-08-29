<?php
// api/geojson_lugar_por_id.php
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
    $sql = "SELECT id_lugar, nombre, descripcion, latitud, longitud, categoria, icono_umap, color_hex 
            FROM lugar_turistico WHERE activo = 1";
    
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
    
    // Construir GeoJSON Feature
    $feature = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [
                (float)$lugar['longitud'],
                (float)$lugar['latitud']
            ]
        ],
        'properties' => [
            'id' => (int)$lugar['id_lugar'],
            'name' => $lugar['nombre'],
            'description' => $lugar['descripcion'] ?? '',
            'categoria' => $lugar['categoria'] ?? 'Atracción turística',
            '_umap_options' => [
                'icon' => $lugar['icono_umap'] ?? 'landmark',
                'color' => $lugar['color_hex'] ?? '#0066CC',
                'popup' => '<b>' . $lugar['nombre'] . '</b><br>' . ($lugar['descripcion'] ?? '')
            ]
        ]
    ];
    
    $geojson = [
        'type' => 'FeatureCollection',
        'features' => [$feature]
    ];
    
    echo json_encode($geojson);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>