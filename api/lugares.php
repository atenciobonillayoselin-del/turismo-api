<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $sql = "SELECT lt.id_lugar, lt.nombre, lt.descripcion, lt.descripcion_corta, lt.latitud, lt.longitud,
                   lt.direccion, lt.calificacion, lt.costo, lt.costo_nino, lt.costo_adulto, lt.costo_tercera_edad,
                   lt.es_gratuito, lt.abierto_todos_los_dias, lt.horarios, lt.tipo_transporte,
                   lt.activo, lt.created_at, lt.updated_at, lt.id_categoria,
                   cl.nombre as categoria, cl.slug as categoria_slug, cl.icono as categoria_icono
            FROM lugar_turistico lt
            LEFT JOIN categoria_lugar cl ON lt.id_categoria = cl.id_categoria
            WHERE lt.activo = 1
            ORDER BY lt.id_lugar ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $iconosDefault = [
        'mirador'    => 'landmark',
        'museo'      => 'museum',
        'parque'     => 'park',
        'plaza'      => 'town-hall',
        'iglesia'    => 'religious-christian',
        'naturaleza' => 'garden',
        'mercado'    => 'shop',
    ];

    foreach ($lugares as &$lugar) {
        // Asegurar que campos opcionales tengan valores por defecto
        if (!isset($lugar['descripcion_corta'])) {
            $lugar['descripcion_corta'] = '';
        }
        if (!isset($lugar['calificacion'])) {
            $lugar['calificacion'] = 0.0;
        }
        if (!isset($lugar['costo'])) {
            $lugar['costo'] = 0.00;
        }
        if (!isset($lugar['es_gratuito'])) {
            $lugar['es_gratuito'] = 0;
        }
        if (!isset($lugar['abierto_todos_los_dias'])) {
            $lugar['abierto_todos_los_dias'] = 0;
        }
        if (!isset($lugar['horarios'])) {
            $lugar['horarios'] = null;
        }
        if (!isset($lugar['id_categoria'])) {
            $lugar['id_categoria'] = null;
        }
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
