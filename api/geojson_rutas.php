<?php
/**
 * geojson_rutas.php - GeoJSON de rutas de transporte
 * 
 * Usa Worker de Cloudflare para obtener datos frescos de uMap
 * y combina con datos de MySQL
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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

$idRuta   = isset($_GET['id_ruta'])   ? (int) $_GET['id_ruta']   : 0;
$grupo    = isset($_GET['grupo'])     ? trim($_GET['grupo'])     : '';
$tipoRuta = isset($_GET['tipo'])      ? trim($_GET['tipo'])      : '';

function tableColExists(PDO $pdo, string $table, string $col): bool {
    static $cache = [];
    $k = "$table.$col";
    if (isset($cache[$k])) return $cache[$k];
    try {
        $s = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $s->execute([$col]);
        $cache[$k] = (bool)$s->fetch();
    } catch (Throwable $e) { $cache[$k] = false; }
    return $cache[$k];
}

try {
    $hasCoordsJson = tableColExists($pdo, 'ruta', 'coords_geojson');
    $hasSentido    = tableColExists($pdo, 'ruta', 'sentido');
    $hasGrupoUmap  = tableColExists($pdo, 'ruta', 'id_grupo_umap');
    $hasLugarGrupo = tableColExists($pdo, 'lugar_turistico', 'grupo_umap');

    $selRuta = ['r.id_ruta','r.nombre','r.descripcion','r.tipo','r.color_hex'];
    if ($hasCoordsJson) $selRuta[] = 'r.coords_geojson';
    if ($hasSentido)    $selRuta[] = 'r.sentido';
    if ($hasGrupoUmap)  $selRuta[] = 'r.id_grupo_umap';

    $where = ["r.activo = 1"];
    $params = [];

    if ($idRuta > 0) {
        $where[] = "r.id_ruta = :id_ruta";
        $params[':id_ruta'] = $idRuta;
    }
    if (!empty($grupo)) {
        $grupoLike = "%{$grupo}%";
        $subWhere = [];
        if ($hasGrupoUmap)  $subWhere[] = "r.id_grupo_umap LIKE :g1";
        $subWhere[] = "r.nombre LIKE :g2";
        if ($hasLugarGrupo) {
            $subWhere[] = "EXISTS (
                SELECT 1 FROM ruta_lugar rl
                INNER JOIN lugar_turistico l ON l.id_lugar = rl.id_lugar
                WHERE rl.id_ruta = r.id_ruta
                  AND (l.grupo_umap LIKE :g3 OR l.nombre LIKE :g4)
            )";
        }
        $where[] = '(' . implode(' OR ', $subWhere) . ')';
        if ($hasGrupoUmap)  $params[':g1'] = $grupoLike;
        $params[':g2'] = $grupoLike;
        if ($hasLugarGrupo) {
            $params[':g3'] = $grupoLike;
            $params[':g4'] = $grupoLike;
        }
    }
    if (!empty($tipoRuta)) {
        $where[] = "LOWER(r.tipo) = :tipo";
        $params[':tipo'] = strtolower($tipoRuta);
    }

    $sqlRutas = "SELECT DISTINCT " . implode(', ', $selRuta) . "
                 FROM ruta r
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY r.tipo, r.nombre";
    $stmtRutas = $pdo->prepare($sqlRutas);
    $stmtRutas->execute($params);
    $rutas = $stmtRutas->fetchAll(PDO::FETCH_ASSOC);

    // Si no hay rutas en MySQL, intentar obtener de uMap directamente
    if (empty($rutas)) {
        $capasUmap = [
            '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA',
            '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA',
            '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA',
            'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA',
            'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA',
            '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA',
            '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA',
        ];
        
        $features = [];
        foreach ($capasUmap as $uuid => $nombre) {
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
                                    'name' => $nombre,
                                    'title' => $nombre,
                                    'fuente' => 'uMap directo',
                                    'color' => '#E74C3C',
                                    'stroke' => '#E74C3C',
                                ]
                            ];
                        }
                    }
                }
            }
        }
        
        echo json_encode([
            'type' => 'FeatureCollection',
            'name' => 'Rutas desde uMap (directo)',
            'totalFeatures' => count($features),
            'metadata' => ['fuente' => 'uMap Worker', 'worker_url' => UMAP_WORKER_URL],
            'features' => $features,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    $sqlPuntos = null;
    $stmtPuntos = null;
    if (!$hasCoordsJson) {
        $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                        FROM ruta_parada rp
                        INNER JOIN parada p ON p.id_parada = rp.id_parada
                        WHERE rp.id_ruta = :id_ruta
                        ORDER BY rp.orden ASC";
        $stmtPuntos = $pdo->prepare($sqlPuntos);
    }

    $features = [];
    foreach ($rutas as $ruta) {
        $idR = (int)$ruta['id_ruta'];
        $coords = null;

        if ($hasCoordsJson && !empty($ruta['coords_geojson'])) {
            $parsed = json_decode($ruta['coords_geojson'], true);
            if (is_array($parsed) && count($parsed) >= 2) {
                $coords = [];
                foreach ($parsed as $c) {
                    $lat = (float)($c[1] ?? 0);
                    $lng = (float)($c[0] ?? 0);
                    if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
                }
            }
        }
        if (!$coords || count($coords) < 2) {
            if ($stmtPuntos === null) {
                $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                                FROM ruta_parada rp
                                INNER JOIN parada p ON p.id_parada = rp.id_parada
                                WHERE rp.id_ruta = :id_ruta
                                ORDER BY rp.orden ASC";
                $stmtPuntos = $pdo->prepare($sqlPuntos);
            }
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
        $sentido = $ruta['sentido'] ?? null;
        if (!$sentido) {
            $esIda    = stripos($ruta['nombre'], 'ida')    !== false;
            $esVuelta = stripos($ruta['nombre'], 'vuelta') !== false;
            if ($esIda && !$esVuelta)       $sentido = 'IDA';
            elseif ($esVuelta && !$esIda)   $sentido = 'VUELTA';
            else                            $sentido = 'NORMAL';
        }
        $label  = ($sentido === 'IDA') ? '🟢 IDA' : ($sentido === 'VUELTA' ? '🔵 VUELTA' : '📍');

        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type' => 'LineString', 'coordinates' => $coords],
            'properties' => [
                'name'           => $ruta['nombre'],
                'title'          => $ruta['nombre'],
                'description'    => "<strong>" . htmlspecialchars($ruta['nombre']) . "</strong><br>{$label} · "
                                   . count($coords) . " paradas"
                                   . (!empty($ruta['descripcion']) ? "<br><br>" . htmlspecialchars($ruta['descripcion']) : ""),
                'id_ruta'        => $idR,
                'tipo'           => $ruta['tipo'] ?? 'minibus',
                'sentido'        => $sentido,
                'grupo_umap'     => $ruta['id_grupo_umap'] ?? '',
                'color'          => $color,
                'stroke'         => $color,
                'stroke-width'   => 5,
                'stroke-opacity' => 0.9,
                '_umap_options'  => ['color' => $color, 'weight' => 5, 'opacity' => 0.9],
            ],
        ];
    }

    echo json_encode([
        'type'          => 'FeatureCollection',
        'name'          => 'Rutas de Transporte - La Paz',
        'totalFeatures' => count($features),
        'metadata'      => [
            'fuente'       => 'MySQL Aiven (coords_geojson=' . ($hasCoordsJson ? 'SI' : 'NO') . ') + uMap Worker',
            'generado'     => date('c'),
            'total'        => count($features),
            'id_ruta'      => $idRuta,
            'grupo_lugar'  => $grupo,
            'tipo'         => $tipoRuta,
            'worker_url'   => UMAP_WORKER_URL,
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'FeatureCollection', 'error' => $e->getMessage(), 'features' => []
    ], JSON_UNESCAPED_UNICODE);
}