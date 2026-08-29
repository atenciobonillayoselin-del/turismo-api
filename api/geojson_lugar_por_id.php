<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;
$grupo   = isset($_GET['grupo'])    ? trim($_GET['grupo']) : '';

if ($idLugar <= 0 && empty($grupo)) {
    echo json_encode([
        'type' => 'FeatureCollection',
        'error'=> 'Se requiere ?id_lugar=X o ?grupo=NombreLugar',
        'features' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $where = ["activo = 1"];
    $params = [];
    if ($idLugar > 0) {
        $where[] = "id_lugar = :id";
        $params[':id'] = $idLugar;
    } else {
        $where[] = "(grupo_umap LIKE :g1 OR nombre LIKE :g2)";
        $params[':g1'] = "%{$grupo}%";
        $params[':g2'] = "%{$grupo}%";
    }

    $sql = "SELECT id_lugar, nombre, descripcion, categoria, latitud, longitud,
                   grupo_umap, icono_umap, color_hex, panorama_url, imagen_url
            FROM lugar WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $features = [];
    if ($row) {
        $lat = (float)$row['latitud'];
        $lng = (float)$row['longitud'];
        if ($lat !== 0.0 && $lng !== 0.0) {
            $grupoTxt = !empty($row['grupo_umap']) ? $row['grupo_umap'] : $row['nombre'];
            $icono = $row['icono_umap'];
            if (empty($icono)) {
                $cat = strtolower($row['categoria'] ?? '');
                $mapIcon = [
                    'mirador'=>'landmark','museo'=>'museum','parque'=>'park',
                    'plaza'=>'town-hall','iglesia'=>'religious-christian',
                    'naturaleza'=>'garden','mercado'=>'shop'
                ];
                foreach ($mapIcon as $k=>$v) if (str_contains($cat,$k)) { $icono=$v; break; }
                if (empty($icono)) $icono = 'star';
            }
            $color = $row['color_hex'] ?: '#E74C3C';
            $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/','',$row['nombre'])) ?: $row['nombre'];

            $descHtml = "<strong>".htmlspecialchars($nombreLimpio)."</strong>";
            if (!empty($row['categoria'])) $descHtml .= "<br><em>".htmlspecialchars($row['categoria'])."</em>";
            if (!empty($row['descripcion'])) $descHtml .= "<br><br>".htmlspecialchars($row['descripcion']);
            if (!empty($row['panorama_url'])) $descHtml .= "<br><br>🔗 <a href='".htmlspecialchars($row['panorama_url'])."' target='_blank'>Ver panorama 360°</a>";

            $features[] = [
                'type' => 'Feature',
                'geometry' => ['type'=>'Point','coordinates'=>[$lng,$lat]],
                'properties' => [
                    'name' => $nombreLimpio,
                    'title'=> $nombreLimpio,
                    'description' => $descHtml,
                    'grupo' => $grupoTxt,
                    'group' => $grupoTxt,
                    'categoria' => $row['categoria'] ?? '',
                    'id_lugar' => (int)$row['id_lugar'],
                    'icon'  => $icono,
                    'color' => $color,
                    '_umap_options' => [
                        'color' => $color,
                        'icon'  => [
                            'type'=>'awesomeMarker','prefix'=>'fa','icon'=>$icono,
                            'markerColor'=>'red','iconColor'=>'white'
                        ]
                    ],
                    'panorama_url' => $row['panorama_url'] ?? '',
                    'imagen_url'   => $row['imagen_url'] ?? '',
                ]
            ];
        }
    }

    echo json_encode([
        'type'=>'FeatureCollection',
        'name'=>'Marcador Lugar',
        'totalFeatures'=>count($features),
        'metadata'=>['id_lugar'=>$idLugar,'grupo'=>$grupo,'generado'=>date('c')],
        'features'=>$features
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['type'=>'FeatureCollection','error'=>$e->getMessage(),'features'=>[]], JSON_UNESCAPED_UNICODE);
}
?>
