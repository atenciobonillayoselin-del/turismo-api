<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
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
                activo,
                created_at,
                updated_at
            FROM lugar_turistico
            WHERE activo = 1
            ORDER BY id_lugar ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lugares as &$lugar) {
        if (empty($lugar['grupo_umap'])) {
            $lugar['grupo_umap'] = $lugar['nombre'];
        }
        if (empty($lugar['icono_umap'])) {
            $cat = strtolower($lugar['categoria'] ?? '');
            $mapIcon = [
                'mirador'    => 'landmark',
                'museo'      => 'museum',
                'parque'     => 'park',
                'plaza'      => 'town-hall',
                'iglesia'    => 'religious-christian',
                'naturaleza' => 'garden',
                'mercado'    => 'shop',
            ];
            $lugar['icono_umap'] = 'star';
            foreach ($mapIcon as $k => $v) {
                if (str_contains($cat, $k)) { $lugar['icono_umap'] = $v; break; }
            }
        }
        if (empty($lugar['color_hex'])) $lugar['color_hex'] = '#E74C3C';
    }
    unset($lugar);

    echo json_encode([
        'success' => true,
        'total'   => count($lugares),
        'data'    => $lugares,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>
