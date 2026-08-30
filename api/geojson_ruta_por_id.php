<?php
/**
 * geojson_ruta_por_id.php - GeoJSON de una ruta específica
 * 
 * Usa Worker de Cloudflare para obtener datos frescos de uMap
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

// ============================================================
// CONFIGURACIÓN DEL WORKER DE CLOUDFLARE
// ============================================================
define('UMAP_WORKER_URL', 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/?url=');
define('UMAP_MAP_ID', 1451289);

/**
 * Obtener una capa GeoJSON via Cloudflare Worker
 */
function obtenerCapaUmap($layerUuid) {
    $targetUrl = "https://umap.openstreetmap.fr/api/0.1/map/" . UMAP_MAP_ID . "/layer/{$layerUuid}/data/";
    $proxyUrl = UMAP_WORKER_URL . urlencode($targetUrl);
    
    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-API/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/geo+json, application/json',
            'Accept-Language: es-ES,es;q=0.9',
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    return null;
}

$idRuta = isset($_GET['id_ruta']) ? (int) $_GET['id_ruta'] : 0;
$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

// Mapeo de UUIDs de capas uMap por nombre de ruta
$capasUmapPorNombre = [
    '254' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
    '254 vuelta' => '1131cb1a-631f-4d7b-8f33-f46a469366f9',
    '204' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
    '889' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
    '889 vuelta' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5',
    '364' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
    '364 vuelta' => '291c212e-44db-4460-b84e-773bcfede107',
];

if ($idRuta <= 0 && empty($nombre)) {
    echo json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'Error',
        'error'    => 'Se requiere ?id_ruta=X o ?nombre=NombreDeLaRuta (puede ser parcial)',
        'features' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $where = ["activo = 1"];
    $params = [];
    if ($idRuta > 0) {
        $where[] = "id_ruta = :id";
        $params[':id'] = $idRuta;
    } else {
        $where[] = "nombre LIKE :nombre";
        $params[':nombre'] = "%{$nombre}%";
    }

    $sqlRuta = "SELECT id_ruta, nombre, descripcion, tipo, color_hex, id_umap
                  FROM ruta
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY id_ruta ASC LIMIT 50";
    $stmtR = $pdo->prepare($sqlRuta);
    $stmtR->execute($params);
    $rutas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    // Si no hay ruta en MySQL, intentar obtener de uMap directamente
    if (empty($rutas)) {
        $features = [];
        $found = false;
        $nombreLower = strtolower($nombre);
        
        foreach ($capasUmapPorNombre as $key => $uuid) {
            if (strpos($key, $nombreLower) !== false) {
                $data = obtenerCapaUmap($uuid);
                if ($data && isset($data['features'])) {
                    foreach ($data['features'] as $feature) {
                        if ($feature['geometry']['type'] === 'LineString') {
                            $coords = $feature['geometry']['coordinates'];
                            if (count($coords) >= 2) {
                                $features[] = [
                                    'type' => 'Feature',
                                    'geometry' => ['type' => 'LineString', 'coordinates' => $coords],
                                    'properties' => [
                                        'name' => "Ruta desde uMap: $key",
                                        'title' => "Ruta desde uMap: $key",
                                        'fuente' => 'uMap Worker',
                                        'color' => '#E74C3C',
                                    ]
                                ];
                                $found = true;
                            }
                        }
                    }
                }
            }
        }
        
        if ($found) {
            echo json_encode([
                'type' => 'FeatureCollection',
                'name' => 'Ruta desde uMap (directo)',
                'totalFeatures' => count($features),
                'metadata' => ['fuente' => 'uMap Worker', 'worker_url' => UMAP_WORKER_URL],
                'features' => $features,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    }

    $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                    FROM ruta_parada rp
                    INNER JOIN parada p ON p.id_parada = rp.id_parada
                    WHERE rp.id_ruta = :id_ruta
                    ORDER BY rp.orden ASC";
    $stmtPuntos = $pdo->prepare($sqlPuntos);

    $features = [];
    foreach ($rutas as $ruta) {
        $idR = (int)$ruta['id_ruta'];
        
        // Intentar obtener coordenadas de coords_geojson si existe
        $hasCoords = isset($ruta['coords_geojson']) && !empty($ruta['coords_geojson']);
        $coords = [];
        
        if ($hasCoords) {
            $parsed = json_decode($ruta['coords_geojson'], true);
            if (is_array($parsed) && count($parsed) >= 2) {
                foreach ($parsed as $c) {
                    $lat = (float)($c[1] ?? 0);
                    $lng = (float)($c[0] ?? 0);
                    if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
                }
            }
        }
        
        if (count($coords) < 2) {
            $stmtPuntos->execute([':id_ruta' => $idR]);
            $puntos = $stmtPuntos->fetchAll(PDO::FETCH_ASSOC);
            $coords = [];
            foreach ($puntos as $pt) {
                $lat = (float)$pt['latitud'];
                $lng = (float)$pt['longitud'];
                if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
            }
        }
        
        if (count($coords) < 2) continue;

        $color = $ruta['color_hex'];
        if (empty($color)) {
            $color = (stripos($ruta['nombre'], 'vuelta') !== false) ? '#2980B9' : '#E74C3C';
        }
        $esIda    = stripos($ruta['nombre'], 'ida')    !== false;
        $esVuelta = stripos($ruta['nombre'], 'vuelta') !== false;
        $label    = $esIda ? '🟢 IDA' : ($esVuelta ? '🔵 VUELTA' : '📍');

        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type'=>'LineString','coordinates'=>$coords],
            'properties' => [
                'name'           => $ruta['nombre'],
                'title'          => $ruta['nombre'],
                'description'    => "<strong>".htmlspecialchars($ruta['nombre'])."</strong><br>{$label} · ".count($coords)." paradas".(!empty($ruta['descripcion']) ? "<br><br>".htmlspecialchars($ruta['descripcion']) : ""),
                'id_ruta'        => $idR,
                'tipo'           => $ruta['tipo'] ?? 'minibus',
                'color'          => $color,
                'stroke'         => $color,
                'stroke-width'   => 5,
                'stroke-opacity' => 0.95,
                '_umap_options'  => [
                    'color'   => $color,
                    'weight'  => 5,
                    'opacity' => 0.95,
                ],
                'cant_paradas' => count($coords),
                'sentido'      => $esIda ? 'IDA' : ($esVuelta ? 'VUELTA' : 'NORMAL'),
                'id_umap'      => $ruta['id_umap'] ?? '',
            ],
        ];
    }

    echo json_encode([
        'type'          => 'FeatureCollection',
        'name'          => 'Ruta individual (LineString)',
        'totalFeatures' => count($features),
        'metadata'      => [
            'id_ruta'  => $idRuta,
            'nombre'   => $nombre,
            'generado' => date('c'),
            'tabla_fuente' => 'ruta, ruta_parada, parada',
            'worker_url'   => UMAP_WORKER_URL,
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type'=>'FeatureCollection','error'=>$e->getMessage(),'features'=>[]
    ], JSON_UNESCAPED_UNICODE);
}