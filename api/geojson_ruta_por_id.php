<?php
/**
 * geojson_ruta_por_id.php - GeoJSON de una ruta específica
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$idRuta = isset($_GET['id_ruta']) ? (int) $_GET['id_ruta'] : 0;
$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

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
        $where[] = "descripcion LIKE :nombre";
        $params[':nombre'] = "%{$nombre}%";
    }

    $sqlRuta = "SELECT id_ruta, numero_ruta, descripcion, tipo, color_hex, sentido, puntos_gps_ida
                  FROM ruta
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY id_ruta ASC LIMIT 50";
    $stmtR = $pdo->prepare($sqlRuta);
    $stmtR->execute($params);
    $rutas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                    FROM ruta_parada rp
                    INNER JOIN parada p ON p.id_parada = rp.id_parada
                    WHERE rp.id_ruta = :id_ruta
                    ORDER BY rp.orden ASC";
    $stmtPuntos = $pdo->prepare($sqlPuntos);

    $features = [];
    foreach ($rutas as $ruta) {
        $idR = (int)$ruta['id_ruta'];
        
        // Build name from numero_ruta + tipo or descripcion
        $tipo = $ruta['tipo'] ?? 'minibus';
        $tipoCapitalizado = ucfirst($tipo);
        if (!empty($ruta['numero_ruta'])) {
            $nombreArmado = "{$tipoCapitalizado} {$ruta['numero_ruta']}";
        } else {
            $nombreArmado = $ruta['descripcion'] ?? 'Ruta sin nombre';
        }
        
        // Get coordinates from ruta_parada
        $stmtPuntos->execute([':id_ruta' => $idR]);
        $puntos = $stmtPuntos->fetchAll(PDO::FETCH_ASSOC);
        $coords = [];
        foreach ($puntos as $pt) {
            $lat = (float)$pt['latitud'];
            $lng = (float)$pt['longitud'];
            if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
        }
        
        // Fallback to puntos_gps_ida if not enough points
        if (count($coords) < 2 && !empty($ruta['puntos_gps_ida'])) {
            $pares = explode(';', trim($ruta['puntos_gps_ida']));
            foreach ($pares as $par) {
                $par = trim($par);
                if (empty($par)) continue;
                $gpsCoords = explode(',', $par);
                if (count($gpsCoords) >= 2) {
                    try {
                        $lat = (float)trim($gpsCoords[0]);
                        $lng = (float)trim($gpsCoords[1]);
                        if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }
        }
        
        if (count($coords) < 2) continue;

        $color = $ruta['color_hex'];
        if (empty($color)) {
            $color = (stripos($nombreArmado, 'vuelta') !== false) ? '#2980B9' : '#E74C3C';
        }
        $esIda    = stripos($nombreArmado, 'ida')    !== false;
        $esVuelta = stripos($nombreArmado, 'vuelta') !== false;
        $label    = $esIda ? '🟢 IDA' : ($esVuelta ? '🔵 VUELTA' : '📍');

        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type'=>'LineString','coordinates'=>$coords],
            'properties' => [
                'name'           => $nombreArmado,
                'title'          => $nombreArmado,
                'description'    => "<strong>".htmlspecialchars($nombreArmado)."</strong><br>{$label} · ".count($coords)." paradas".(!empty($ruta['descripcion']) ? "<br><br>".htmlspecialchars($ruta['descripcion']) : ""),
                'id_ruta'        => $idR,
                'tipo'           => $tipo,
                'color'          => $color,
                'stroke'         => $color,
                'stroke-width'   => 5,
                'stroke-opacity' => 0.95,
                'cant_paradas' => count($coords),
                'sentido'      => $esIda ? 'IDA' : ($esVuelta ? 'VUELTA' : 'NORMAL'),
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
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type'=>'FeatureCollection','error'=>$e->getMessage(),'features'=>[]
    ], JSON_UNESCAPED_UNICODE);
}