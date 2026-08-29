<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;
$grupo   = isset($_GET['grupo'])    ? trim($_GET['grupo']) : '';

if ($idLugar <= 0 && empty($grupo)) {
    echo json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'Error',
        'error'    => 'Se requiere ?id_lugar=X o ?grupo=Nombre',
        'features' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $joinLugar = '';
    $where = ["r.activo = 1"];
    $params = [];

    if ($idLugar > 0) {
        $joinLugar = "INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta";
        $where[] = "rl.id_lugar = :id_lugar";
        $params[':id_lugar'] = $idLugar;
    } elseif (!empty($grupo)) {
        $joinLugar = "INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta
                      INNER JOIN lugar l ON l.id_lugar = rl.id_lugar";
        $where[] = "(l.grupo_umap LIKE :grupo OR l.nombre LIKE :grupo2)";
        $params[':grupo']  = "%{$grupo}%";
        $params[':grupo2'] = "%{$grupo}%";
    }

    $sqlRutas = "SELECT DISTINCT
                    r.id_ruta,
                    r.nombre,
                    r.descripcion,
                    r.tipo,
                    r.color_hex
                FROM ruta r
                {$joinLugar}
                WHERE " . implode(" AND ", $where) . "
                ORDER BY r.nombre";
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
            'geometry' => [
                'type'        => 'LineString',
                'coordinates' => $coords,
            ],
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
        'name'          => 'Rutas del lugar',
        'totalFeatures' => count($features),
        'metadata'      => [
            'id_lugar' => $idLugar,
            'grupo'    => $grupo,
            'generado' => date('c'),
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'FeatureCollection', 'error' => $e->getMessage(), 'features' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
