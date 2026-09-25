<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;

if ($idLugar <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Parámetro id_lugar inválido. Debe ser entero > 0.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "SELECT
                r.id_ruta,
                r.numero_ruta,
                r.descripcion,
                r.tipo,
                r.color_hex,
                r.activo
            FROM ruta r
            INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta
            WHERE rl.id_lugar = :id_lugar
              AND r.activo = 1
            ORDER BY r.tipo, r.numero_ruta";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_lugar' => $idLugar]);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rutas) === 0) {
        $sqlFallback = "SELECT
                            r.id_ruta,
                            r.numero_ruta,
                            r.descripcion,
                            r.tipo,
                            r.color_hex,
                            r.activo
                        FROM ruta r
                        WHERE r.activo = 1
                        LIMIT 20";
        $stmt2 = $pdo->prepare($sqlFallback);
        $stmt2->execute();
        $rutas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    // Build names for all routes
    $data = [];
    foreach ($rutas as $ruta) {
        $tipo = $ruta['tipo'] ?? 'minibus';
        $tipoCapitalizado = ucfirst($tipo);
        if (!empty($ruta['numero_ruta'])) {
            $nombreArmado = "{$tipoCapitalizado} {$ruta['numero_ruta']}";
        } else {
            $nombreArmado = $ruta['descripcion'] ?? 'Ruta sin nombre';
        }

        $data[] = [
            'id_ruta' => (int)$ruta['id_ruta'],
            'nombre' => $nombreArmado,
            'descripcion' => $ruta['descripcion'] ?? '',
            'tipo' => $tipo,
            'color_hex' => $ruta['color_hex'] ?? '#0066CC',
            'activo' => (int)$ruta['activo'],
        ];
    }

    echo json_encode([
        'success'   => true,
        'id_lugar'  => $idLugar,
        'total'     => count($data),
        'data'      => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>
