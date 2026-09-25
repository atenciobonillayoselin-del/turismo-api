// api/geojson_rutas_por_lugar.php (versión optimizada)
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

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;

if ($idLugar <= 0) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => 'Se requiere ?id_lugar=X',
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
            r.sentido
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
                SELECT id_ruta, nombre, descripcion, tipo, color_hex, sentido
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

    $data = [];
    foreach ($rutas as $ruta) {
        $color = $ruta['color_hex'] ?: '#E74C3C';
        $sentido = $ruta['sentido'] ?? 'NORMAL';

        $data[] = [
            'id_ruta' => (int)$ruta['id_ruta'],
            'nombre' => $ruta['nombre'],
            'descripcion' => $ruta['descripcion'] ?? '',
            'tipo' => $ruta['tipo'] ?? 'minibus',
            'color_hex' => $color,
            'sentido' => $sentido,
            'activo' => (int)$ruta['activo'],
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>