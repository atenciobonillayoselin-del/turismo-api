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

function colExiste(PDO $pdo, string $tabla, string $col): bool {
    static $cache = [];
    $key = "$tabla.$col";
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabla` LIKE ?");
        $stmt->execute([$col]);
        $cache[$key] = (bool)$stmt->fetch();
        return $cache[$key];
    } catch (Throwable $e) {
        $cache[$key] = false;
        return false;
    }
}

try {
    $selCols = ['id_lugar','nombre','descripcion','latitud','longitud','activo','created_at','updated_at'];
    $extras = ['descripcion_corta','calificacion','costo','es_gratuito','abierto_todos_los_dias','horarios','costo_nino','costo_adulto','costo_tercera_edad','id_categoria'];
    foreach ($extras as $c) {
        if (colExiste($pdo, 'lugar_turistico', $c)) {
            $selCols[] = $c;
        }
    }
    $colsSql = implode(', ', $selCols);

    $sql = "SELECT $colsSql FROM lugar_turistico WHERE activo = 1 ORDER BY id_lugar ASC";
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
