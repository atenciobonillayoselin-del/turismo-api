<?php
/**
 * geojson_lugares.php - GeoJSON de lugares turísticos
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$filtroGrupo     = isset($_GET['grupo'])     ? trim($_GET['grupo'])     : '';
$filtroCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

try {
    $where = ["lt.activo = 1"];
    $params = [];

    if (!empty($filtroGrupo)) {
        $where[] = "lt.nombre LIKE :grupo";
        $params[':grupo'] = "%{$filtroGrupo}%";
    }
    if (!empty($filtroCategoria)) {
        $where[] = "LOWER(cl.nombre) LIKE :categoria";
        $params[':categoria'] = "%" . strtolower($filtroCategoria) . "%";
    }

    $sql = "SELECT lt.id_lugar, lt.nombre, lt.descripcion, lt.descripcion_corta, lt.latitud, lt.longitud,
                   lt.direccion, lt.calificacion, lt.costo, lt.es_gratuito, lt.abierto_todos_los_dias, lt.horarios,
                   lt.tipo_transporte, lt.created_at, lt.updated_at,
                   cl.nombre as categoria, cl.slug as categoria_slug, cl.icono as categoria_icono
            FROM lugar_turistico lt
            LEFT JOIN categoria_lugar cl ON lt.id_categoria = cl.id_categoria
            WHERE " . implode(" AND ", $where) . "
            ORDER BY lt.id_lugar ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $features = [];
    foreach ($filas as $row) {
        $lat = (float) $row['latitud'];
        $lng = (float) $row['longitud'];
        if ($lat === 0.0 || $lng === 0.0) continue;

        $grupo = $row['nombre'];
        $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/', '', $row['nombre']));
        if (empty($nombreLimpio)) $nombreLimpio = $row['nombre'];

        // Obtener multimedia del lugar
        $idLugar = (int)$row['id_lugar'];
        $stmtMedia = $pdo->prepare("SELECT tipo, url, descripcion FROM lugar_multimedia WHERE id_lugar = :id_lugar AND activo = 1 ORDER BY orden ASC");
        $stmtMedia->execute([':id_lugar' => $idLugar]);
        $mediaItems = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

        $panoramaUrl = '';
        $imagenUrl = '';
        foreach ($mediaItems as $media) {
            if ($media['tipo'] === '360' && empty($panoramaUrl)) {
                $panoramaUrl = $media['url'];
            }
            if ($media['tipo'] === 'imagen' && empty($imagenUrl)) {
                $imagenUrl = $media['url'];
            }
        }

        $icono = 'star';
        $cat = strtolower($row['categoria'] ?? '');
        $mapIcon = [
            'mirador'    => 'landmark',
            'museo'      => 'museum',
            'parque'     => 'park',
            'plaza'      => 'town-hall',
            'iglesia'    => 'religious-christian',
            'naturaleza' => 'garden',
            'mercado'    => 'shop',
        ];
        foreach ($mapIcon as $k => $v) if (str_contains($cat, $k)) { $icono = $v; break; }

        $color = '#E74C3C';

        $descriptionHtml = "<strong>" . htmlspecialchars($nombreLimpio, ENT_QUOTES, 'UTF-8') . "</strong>";
        if (!empty($row['categoria']))   $descriptionHtml .= "<br><em>" . htmlspecialchars($row['categoria']) . "</em>";
        if (!empty($row['descripcion'])) $descriptionHtml .= "<br><br>" . htmlspecialchars($row['descripcion']);

        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [$lng, $lat]],
            'properties' => [
                'name'        => $nombreLimpio,
                'title'       => $nombreLimpio,
                'description' => $descriptionHtml,
                'grupo'       => $grupo,
                'group'       => $grupo,
                'categoria'   => $row['categoria'] ?? '',
                'category'    => $row['categoria'] ?? '',
                'id_lugar'    => (int)$row['id_lugar'],
                'icon'        => $icono,
                'color'       => $color,
                'panorama_url' => $panoramaUrl,
                'updated_at'   => $row['updated_at'] ?? '',
            ],
        ];
    }

    echo json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'Lugares Turísticos - La Paz',
        'generator'=> 'turismo-api/' . date('c'),
        'totalFeatures' => count($features),
        'metadata' => [
            'fuente'           => 'MySQL Aiven',
            'generado'         => date('c'),
            'total'            => count($features),
            'filtro_grupo'     => $filtroGrupo,
            'filtro_categoria' => $filtroCategoria,
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'FeatureCollection', 'error' => $e->getMessage(), 'features' => []
    ], JSON_UNESCAPED_UNICODE);
}