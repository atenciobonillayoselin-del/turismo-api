<?php
/**
 * geojson_lugar_por_id.php - GeoJSON de un lugar turístico específico
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

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;
$grupo   = isset($_GET['grupo'])    ? trim($_GET['grupo']) : '';

if ($idLugar <= 0 && empty($grupo)) {
    echo json_encode([
        'type' => 'FeatureCollection',
        'error'=> 'Se requiere ?id_lugar=X o ?grupo=NombreDelLugar',
        'features' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $where = ["activo = 1"];
    $params = [];
    if ($idLugar > 0) {
        $where[] = "id_lugar = :id";
        $params[':id'] = $idLugar;
    } else {
        $where[] = "(grupo_umap LIKE :g1 OR nombre LIKE :g2)";
        $params[':g1'] = "%{$grupo}%";
        $params[':g2'] = "%{$grupo}%";
    }

    $sql = "SELECT id_lugar, nombre, descripcion, categoria, latitud, longitud,
                   grupo_umap, icono_umap, color_hex, panorama_url, imagen_url, id_umap
            FROM lugar_turistico
            WHERE " . implode(' AND ', $where) . "
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $features = [];
    if ($row) {
        $lat = (float)$row['latitud'];
        $lng = (float)$row['longitud'];
        if ($lat !== 0.0 && $lng !== 0.0) {
            $grupoTxt = !empty($row['grupo_umap']) ? $row['grupo_umap'] : $row['nombre'];
            $icono = $row['icono_umap'];
            if (empty($icono)) {
                $cat = strtolower($row['categoria'] ?? '');
                $mapIcon = [
                    'mirador'=>'landmark','museo'=>'museum','parque'=>'park',
                    'plaza'=>'town-hall','iglesia'=>'religious-christian',
                    'naturaleza'=>'garden','mercado'=>'shop'
                ];
                $icono = 'star';
                foreach ($mapIcon as $k=>$v) if (str_contains($cat,$k)) { $icono=$v; break; }
            }
            $color = $row['color_hex'] ?: '#E74C3C';
            $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/','',$row['nombre'])) ?: $row['nombre'];

            $descHtml = "<strong>".htmlspecialchars($nombreLimpio)."</strong>";
            if (!empty($row['categoria']))   $descHtml .= "<br><em>".htmlspecialchars($row['categoria'])."</em>";
            if (!empty($row['descripcion'])) $descHtml .= "<br><br>".htmlspecialchars($row['descripcion']);
            if (!empty($row['panorama_url'])) $descHtml .= "<br><br>🔗 <a href='".htmlspecialchars($row['panorama_url'])."' target='_blank'>Ver panorama 360°</a>";

            $features[] = [
                'type' => 'Feature',
                'geometry' => ['type'=>'Point','coordinates'=>[$lng,$lat]],
                'properties' => [
                    'name' => $nombreLimpio,
                    'title'=> $nombreLimpio,
                    'description' => $descHtml,
                    'grupo' => $grupoTxt,
                    'group' => $grupoTxt,
                    'categoria' => $row['categoria'] ?? '',
                    'id_lugar' => (int)$row['id_lugar'],
                    'icon'  => $icono,
                    'color' => $color,
                    '_umap_options' => [
                        'color' => $color,
                        'icon'  => [
                            'type'=>'awesomeMarker','prefix'=>'fa','icon'=>$icono,
                            'markerColor'=>'red','iconColor'=>'white'
                        ]
                    ],
                    'panorama_url' => $row['panorama_url'] ?? '',
                    'imagen_url'   => $row['imagen_url'] ?? '',
                    'id_umap'      => $row['id_umap'] ?? '',
                ]
            ];
        }
    } else {
        // Si no está en MySQL, intentar obtener de uMap directamente
        $capasUmap = [
            '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
            '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
            'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
            '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
        ];
        
        foreach ($capasUmap as $uuid) {
            $data = obtenerCapaUmap($uuid);
            if ($data && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if ($feature['geometry']['type'] === 'Point') {
                        $coords = $feature['geometry']['coordinates'];
                        $nombreFeature = $feature['properties']['name'] ?? 'Lugar turístico';
                        if (empty($grupo) || stripos($nombreFeature, $grupo) !== false) {
                            $features[] = [
                                'type' => 'Feature',
                                'geometry' => ['type' => 'Point', 'coordinates' => $coords],
                                'properties' => [
                                    'name' => $nombreFeature,
                                    'title' => $nombreFeature,
                                    'description' => $feature['properties']['description'] ?? '',
                                    'fuente' => 'uMap Worker',
                                    'icon' => 'star',
                                ]
                            ];
                            break 2;
                        }
                    }
                }
            }
        }
    }

    echo json_encode([
        'type'=>'FeatureCollection',
        'name'=>'Marcador Lugar Turístico',
        'totalFeatures'=>count($features),
        'metadata'=>['id_lugar'=>$idLugar,'grupo'=>$grupo,'generado'=>date('c'), 'worker_url'=>UMAP_WORKER_URL],
        'features'=>$features
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['type'=>'FeatureCollection','error'=>$e->getMessage(),'features'=>[]], JSON_UNESCAPED_UNICODE);
}