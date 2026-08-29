<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$filtroGrupo     = isset($_GET['grupo'])     ? trim($_GET['grupo'])     : '';
$filtroCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

try {
    $where = ["activo = 1"];
    $params = [];

    if (!empty($filtroGrupo)) {
        $where[] = "(grupo_umap LIKE :grupo OR nombre LIKE :grupo2)";
        $params[':grupo']  = "%{$filtroGrupo}%";
        $params[':grupo2'] = "%{$filtroGrupo}%";
    }
    if (!empty($filtroCategoria)) {
        $where[] = "LOWER(categoria) LIKE :categoria";
        $params[':categoria'] = "%" . strtolower($filtroCategoria) . "%";
    }

    $sql = "SELECT id_lugar, nombre, descripcion, categoria, latitud, longitud,
                   grupo_umap, icono_umap, color_hex, panorama_url, imagen_url, updated_at
            FROM lugar_turistico
            WHERE " . implode(" AND ", $where) . "
            ORDER BY id_lugar ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $features = [];
    foreach ($filas as $row) {
        $lat = (float) $row['latitud'];
        $lng = (float) $row['longitud'];
        if ($lat === 0.0 || $lng === 0.0) continue;

        $grupo = !empty($row['grupo_umap']) ? $row['grupo_umap'] : $row['nombre'];
        $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/', '', $row['nombre']));
        if (empty($nombreLimpio)) $nombreLimpio = $row['nombre'];

        $icono = $row['icono_umap'];
        if (empty($icono)) {
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
            $icono = 'star';
            foreach ($mapIcon as $k => $v) if (str_contains($cat, $k)) { $icono = $v; break; }
        }

        $color = $row['color_hex'] ?: '#E74C3C';

        $descriptionHtml = "<strong>" . htmlspecialchars($nombreLimpio, ENT_QUOTES, 'UTF-8') . "</strong>";
        if (!empty($row['categoria']))   $descriptionHtml .= "<br><em>" . htmlspecialchars($row['categoria']) . "</em>";
        if (!empty($row['descripcion'])) $descriptionHtml .= "<br><br>" . htmlspecialchars($row['descripcion']);
        if (!empty($row['panorama_url'])) $descriptionHtml .= "<br><br>🔗 <a href='".htmlspecialchars($row['panorama_url'])."' target='_blank'>Ver panorama 360°</a>";

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
                '_umap_options' => [
                    'iconClass' => 'Default',
                    'color'     => $color,
                    'icon'      => [
                        'type'  => 'awesomeMarker',
                        'prefix'=> 'fa',
                        'icon'  => $icono,
                        'markerColor' => 'red',
                        'iconColor'   => 'white',
                    ],
                ],
                'panorama_url' => $row['panorama_url'] ?? '',
                'imagen_url'   => $row['imagen_url'] ?? '',
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
            'fuente'           => 'MySQL Aiven - tabla lugar_turistico',
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
?>
