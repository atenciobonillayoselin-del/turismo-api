<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

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
            'fuente'       => 'MySQL Aiven (coords_geojson=' . ($hasCoordsJson ? 'SI' : 'NO') . ')',
            'generado'     => date('c'),
            'total'        => count($features),
            'id_ruta'      => $idRuta,
            'grupo_lugar'  => $grupo,
            'tipo'         => $tipoRuta,
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'FeatureCollection', 'error' => $e->getMessage(), 'features' => []
    ], JSON_UNESCAPED_UNICODE);
}
