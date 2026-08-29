<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$idRuta   = isset($_GET['id_ruta'])   ? (int) $_GET['id_ruta']   : 0;
$grupo    = isset($_GET['grupo'])     ? trim($_GET['grupo'])     : '';
$tipoRuta = isset($_GET['tipo'])      ? trim($_GET['tipo'])      : '';

try {
    $where = ["r.activo = 1"];
    $params = [];

    if ($idRuta > 0) {
        $where[] = "r.id_ruta = :id_ruta";
        $params[':id_ruta'] = $idRuta;
    }
    if (!empty($grupo)) {
        $where[] = "(r.nombre LIKE :grupo OR EXISTS (
                        SELECT 1 FROM ruta_lugar rl
                        INNER JOIN lugar_turistico l ON l.id_lugar = rl.id_lugar
                        WHERE rl.id_ruta = r.id_ruta
                          AND (l.grupo_umap LIKE :grupo2 OR l.nombre LIKE :grupo3)
                    ))";
        $params[':grupo']  = "%{$grupo}%";
        $params[':grupo2'] = "%{$grupo}%";
        $params[':grupo3'] = "%{$grupo}%";
    }
    if (!empty($tipoRuta)) {
        $where[] = "LOWER(r.tipo) = :tipo";
        $params[':tipo'] = strtolower($tipoRuta);
    }

    $sqlRutas = "SELECT DISTINCT
                    r.id_ruta,
                    r.nombre,
                    r.descripcion,
                    r.tipo,
                    r.color_hex
                FROM ruta r
                WHERE " . implode(" AND ", $where) . "
                ORDER BY r.tipo, r.nombre";
    $stmtRutas = $pdo->prepare($sqlRutas);
    $stmtRutas->execute($params);
    $rutas = $stmtRutas->fetchAll(PDO::FETCH_ASSOC);

    $sqlPuntos = "SELECT p.latitud, p.longitud, rp.orden
                    FROM ruta_parada rp
                    INNER JOIN parada p ON p.id_parada = rp.id_parada
                    WHERE rp.id_ruta = :id_ruta
                    ORDER BY rp.orden ASC";
    $stmtPuntos = $pdo->prepare($sqlPuntos);

    $features = [];
    foreach ($rutas as $ruta) {
        $idR = (int) $ruta['id_ruta'];
        $stmtPuntos->execute([':id_ruta' => $idR]);
        $puntos = $stmtPuntos->fetchAll(PDO::FETCH_ASSOC);

        $coords = [];
        foreach ($puntos as $pt) {
            $lat = (float)$pt['latitud'];
            $lng = (float)$pt['longitud'];
            if ($lat !== 0.0 && $lng !== 0.0) $coords[] = [$lng, $lat];
        }
        if (count($coords) < 2) continue;

        $color = $ruta['color_hex'];
        if (empty($color)) {
            $color = (stripos($ruta['nombre'], 'vuelta') !== false) ? '#2980B9' : '#E74C3C';
        }
        $esIda    = stripos($ruta['nombre'], 'ida') !== false;
        $esVuelta = stripos($ruta['nombre'], 'vuelta') !== false;
        $label    = $esIda ? '🟢 IDA' : ($esVuelta ? '🔵 VUELTA' : '📍');

        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type'=>'LineString','coordinates'=>$coords],
            'properties' => [
                'name'        => $ruta['nombre'],
                'title'       => $ruta['nombre'],
                'description' => "<strong>" . htmlspecialchars($ruta['nombre']) . "</strong><br>{$label} · " . count($coords) . " paradas" . (!empty($ruta['descripcion']) ? "<br><br>" . htmlspecialchars($ruta['descripcion']) : ""),
                'id_ruta'     => $idR,
                'tipo'        => $ruta['tipo'] ?? 'minibus',
                'color'       => $color,
                'stroke'      => $color,
                'stroke-width' => 5,
                '_umap_options' => ['color' => $color, 'weight' => 5, 'opacity' => 0.9],
            ],
        ];
    }

    echo json_encode([
        'type'          => 'FeatureCollection',
        'name'          => 'Rutas de Transporte - La Paz',
        'totalFeatures' => count($features),
        'metadata'      => [
            'fuente'       => 'MySQL Aiven - ruta + ruta_parada + parada + lugar_turistico',
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
        'type'=>'FeatureCollection','error'=>$e->getMessage(),'features'=>[]
    ], JSON_UNESCAPED_UNICODE);
}
?>
