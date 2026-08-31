// api/geojson_rutas_por_lugar.php (versión optimizada)
<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;

if ($idLugar <= 0) {
    echo json_encode([
        'type' => 'FeatureCollection',
        'error' => 'Se requiere ?id_lugar=X',
        'features' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ✅ Obtener rutas del lugar
    $sqlRutas = "
        SELECT DISTINCT
            r.id_ruta,
            r.nombre,
            r.descripcion,
            r.tipo,
            r.color_hex,
            r.sentido,
            r.coords_geojson
        FROM ruta r
        INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta
        WHERE rl.id_lugar = :id_lugar
          AND r.activo = 1
        ORDER BY r.nombre
    ";
    $stmtRutas = $pdo->prepare($sqlRutas);
    $stmtRutas->execute([':id_lugar' => $idLugar]);
    $rutas = $stmtRutas->fetchAll();

    // ✅ Si no hay en ruta_lugar, buscar por nombre
    if (empty($rutas)) {
        // Obtener nombre del lugar
        $sqlLugar = "SELECT nombre FROM lugar_turistico WHERE id_lugar = :id_lugar";
        $stmtLugar = $pdo->prepare($sqlLugar);
        $stmtLugar->execute([':id_lugar' => $idLugar]);
        $lugar = $stmtLugar->fetch();
        
        if ($lugar) {
            $nombreLugar = $lugar['nombre'];
            $sqlRutasNombre = "
                SELECT id_ruta, nombre, descripcion, tipo, color_hex, sentido, coords_geojson
                FROM ruta
                WHERE activo = 1
                  AND (nombre LIKE :nombre1 OR nombre LIKE :nombre2)
                ORDER BY nombre
            ";
            $stmtRutas = $pdo->prepare($sqlRutasNombre);
            $stmtRutas->execute([
                ':nombre1' => "%{$nombreLugar}%",
                ':nombre2' => "%" . str_replace('Mirador', '', $nombreLugar) . "%"
            ]);
            $rutas = $stmtRutas->fetchAll();
        }
    }

    $features = [];
    foreach ($rutas as $ruta) {
        $coords = [];
        
        // ✅ PRIMERO: Intentar con coords_geojson
        if (!empty($ruta['coords_geojson'])) {
            $parsed = json_decode($ruta['coords_geojson'], true);
            if (is_array($parsed) && count($parsed) >= 2) {
                foreach ($parsed as $c) {
                    $lng = (float)($c[0] ?? 0);
                    $lat = (float)($c[1] ?? 0);
                    if ($lat != 0.0 && $lng != 0.0) {
                        $coords[] = [$lng, $lat];
                    }
                }
            }
        }
        
        // ✅ SEGUNDO: Si no hay coords_geojson, usar ruta_parada
        if (count($coords) < 2) {
            $sqlPuntos = "
                SELECT p.latitud, p.longitud
                FROM ruta_parada rp
                INNER JOIN parada p ON p.id_parada = rp.id_parada
                WHERE rp.id_ruta = :id_ruta
                ORDER BY rp.orden ASC
            ";
            $stmtPuntos = $pdo->prepare($sqlPuntos);
            $stmtPuntos->execute([':id_ruta' => $ruta['id_ruta']]);
            $puntos = $stmtPuntos->fetchAll();
            
            foreach ($puntos as $p) {
                $lat = (float)$p['latitud'];
                $lng = (float)$p['longitud'];
                if ($lat != 0.0 && $lng != 0.0) {
                    $coords[] = [$lng, $lat];
                }
            }
        }
        
        if (count($coords) < 2) continue;

        $color = $ruta['color_hex'] ?: '#E74C3C';
        $sentido = $ruta['sentido'] ?? 'NORMAL';
        $label = $sentido === 'IDA' ? '🟢 IDA' : ($sentido === 'VUELTA' ? '🔵 VUELTA' : '📍');

        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coords
            ],
            'properties' => [
                'name' => $ruta['nombre'],
                'title' => $ruta['nombre'],
                'description' => "<strong>" . htmlspecialchars($ruta['nombre']) . "</strong><br>{$label} · " . count($coords) . " paradas" . (!empty($ruta['descripcion']) ? "<br><br>" . htmlspecialchars($ruta['descripcion']) : ""),
                'id_ruta' => (int)$ruta['id_ruta'],
                'tipo' => $ruta['tipo'] ?? 'minibus',
                'sentido' => $sentido,
                'color' => $color,
                'stroke' => $color,
                'stroke-width' => 5,
                '_umap_options' => ['color' => $color, 'weight' => 5, 'opacity' => 0.9],
            ],
        ];
    }

    echo json_encode([
        'type' => 'FeatureCollection',
        'name' => 'Rutas del lugar',
        'totalFeatures' => count($features),
        'metadata' => [
            'id_lugar' => $idLugar,
            'generado' => date('c'),
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'FeatureCollection',
        'error' => $e->getMessage(),
        'features' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>