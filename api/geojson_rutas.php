<?php
/**
 * geojson_rutas.php - GeoJSON de rutas de transporte
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
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

    $selRuta = ['r.id_ruta','r.nombre','r.descripcion','r.tipo','r.color_hex','r.puntos_gps_ida'];
    if ($hasCoordsJson) $selRuta[] = 'r.coords_geojson';
    if ($hasSentido)    $selRuta[] = 'r.sentido';

    $where = ["r.activo = 1"];
    $params = [];

    if ($idRuta > 0) {
        $where[] = "r.id_ruta = :id_ruta";
        $params[':id_ruta'] = $idRuta;
    }
    if (!empty($grupo)) {
        $grupoLike = "%{$grupo}%";
        $subWhere = [];
        $subWhere[] = "r.nombre LIKE :g1";
        $subWhere[] = "EXISTS (
            SELECT 1 FROM ruta_lugar rl
            INNER JOIN lugar_turistico l ON l.id_lugar = rl.id_lugar
            WHERE rl.id_ruta = r.id_ruta
              AND l.nombre LIKE :g2
        )";
        $where[] = '(' . implode(' OR ', $subWhere) . ')';
        $params[':g1'] = $grupoLike;
        $params[':g2'] = $grupoLike;
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

    $data = [];
    foreach ($rutas as $ruta) {
        $idR = (int)$ruta['id_ruta'];
        $puntos = [];

        if ($hasCoordsJson && !empty($ruta['coords_geojson'])) {
            $parsed = json_decode($ruta['coords_geojson'], true);
            if (is_array($parsed) && count($parsed) >= 2) {
                foreach ($parsed as $c) {
                    $lat = (float)($c[1] ?? 0);
                    $lng = (float)($c[0] ?? 0);
                    if ($lat !== 0.0 && $lng !== 0.0) $puntos[] = [$lat, $lng];
                }
            }
        }
        if (count($puntos) < 2) {
            if ($stmtPuntos === null) {
                $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                                FROM ruta_parada rp
                                INNER JOIN parada p ON p.id_parada = rp.id_parada
                                WHERE rp.id_ruta = :id_ruta
                                ORDER BY rp.orden ASC";
                $stmtPuntos = $pdo->prepare($sqlPuntos);
            }
            $stmtPuntos->execute([':id_ruta' => $idR]);
            $puntosDb = $stmtPuntos->fetchAll(PDO::FETCH_ASSOC);
            foreach ($puntosDb as $pt) {
                $lat = (float)$pt['latitud'];
                $lng = (float)$pt['longitud'];
                if ($lat !== 0.0 && $lng !== 0.0) $puntos[] = [$lat, $lng];
            }
        }
        if (count($puntos) < 2 && !empty($ruta['puntos_gps_ida'])) {
            $pares = explode(';', trim($ruta['puntos_gps_ida']));
            foreach ($pares as $par) {
                $par = trim($par);
                if (empty($par)) continue;
                $coords = explode(',', $par);
                if (count($coords) >= 2) {
                    try {
                        $lat = (float)trim($coords[0]);
                        $lng = (float)trim($coords[1]);
                        if ($lat !== 0.0 && $lng !== 0.0) $puntos[] = [$lat, $lng];
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }
        }
        if (count($puntos) < 2) continue;

        $color = $ruta['color_hex'];
        if (empty($color)) {
            $color = (stripos($ruta['nombre'], 'vuelta') !== false) ? '#2980B9' : '#E74C3C';
        }

        $data[] = [
            'nombre' => $ruta['nombre'],
            'descripcion' => $ruta['descripcion'] ?? '',
            'tipo' => $ruta['tipo'] ?? 'minibus',
            'color' => $color,
            'puntos' => $puntos,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}