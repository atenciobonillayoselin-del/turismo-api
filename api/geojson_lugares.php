<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$filtroGrupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
$filtroCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

try {
    $where = ["activo = 1"];
    $params = [];

    if (!empty($filtroGrupo)) {
        $where[] = "grupo_umap LIKE :grupo";
        $params[':grupo'] = "%{$filtroGrupo}%";
    }
    if (!empty($filtroCategoria)) {
        $where[] = "LOWER(categoria) LIKE :categoria";
        $params[':categoria'] = "%" . strtolower($filtroCategoria) . "%";
    }

    $sql = "SELECT
                id_lugar,
                nombre,
                descripcion,
                categoria,
                latitud,
                longitud,
                grupo_umap,
                icono_umap,
                color_hex,
                panorama_url,
                imagen_url,
                updated_at
            FROM lugar
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
        $nombreLimpio = preg_replace('/\s*\([^)]*\)\s*/', '', $row['nombre']);
        $nombreLimpio = trim($nombreLimpio);
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
                'atraccion'  => 'star',
            ];
            foreach ($mapIcon as $k => $v) {
                if (str_contains($cat, $k)) { $icono = $v; break; }
            }
            if (empty($icono)) $icono = 'star';
        }

        $color = $row['color_hex'] ?? '#E74C3C';
        if (empty($color)) $color = '#E74C3C';

        $descriptionHtml = "<strong>" . htmlspecialchars($nombreLimpio, ENT_QUOTES, 'UTF-8') . "</strong>";
        if (!empty($row['categoria'])) {
            $descriptionHtml .= "<br><em>" . htmlspecialchars($row['categoria'], ENT_QUOTES, 'UTF-8') . "</em>";
        }
        if (!empty($row['descripcion'])) {
            $descriptionHtml .= "<br><br>" . htmlspecialchars($row['descripcion'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($row['panorama_url'])) {
            $descriptionHtml .= "<br><br>🔗 <a href='" . htmlspecialchars($row['panorama_url'], ENT_QUOTES) . "' target='_blank'>Ver panorama 360°</a>";
        }

        $features[] = [
            'type'       => 'Feature',
            'geometry'   => [
                'type'        => 'Point',
                'coordinates' => [$lng, $lat],
            ],
            'properties' => [
                'name'         => $nombreLimpio,
                'title'        => $nombreLimpio,
                'description'  => $descriptionHtml,
                'grupo'        => $grupo,
                'group'        => $grupo,
                'categoria'    => $row['categoria'] ?? '',
                'category'     => $row['categoria'] ?? '',
                'id_lugar'     => (int) $row['id_lugar'],
                'icon'         => $icono,
                'iconUrl'      => '',
                'color'        => $color,
                '_umap_options' => [
                    'iconClass'  => 'Default',
                    'iconUrl'    => '',
                    'color'      => $color,
                    'icon'       => [
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

    $total = count($features);
    $generado = date('c');

    echo json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'Lugares Turísticos - La Paz',
        'generator'=> 'turismo-api/' . $generado,
        'totalFeatures' => $total,
        'metadata' => [
            'fuente'     => 'MySQL Aiven - tabla lugar',
            'generado'   => $generado,
            'total'      => $total,
            'filtro_grupo'     => $filtroGrupo,
            'filtro_categoria' => $filtroCategoria,
        ],
        'features' => $features,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'Error',
        'features' => [],
        'error'    => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>
