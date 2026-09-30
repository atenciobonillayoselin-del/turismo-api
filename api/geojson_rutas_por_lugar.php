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
    // ✅ Verificar columnas disponibles
    $hasIdaVuelta = false;
    try {
        $check = $pdo->query("SHOW COLUMNS FROM ruta LIKE 'color_hex_ida'");
        $hasIdaVuelta = (bool)$check->fetch();
    } catch (Exception $e) {
        $hasIdaVuelta = false;
    }

    // ✅ Obtener rutas del lugar con colores de ida/vuelta
    if ($hasIdaVuelta) {
        $sqlRutas = "
            SELECT DISTINCT
                r.id_ruta,
                r.numero_ruta,
                r.descripcion,
                r.tipo,
                r.color_hex,
                r.color_hex_ida,
                r.color_hex_vuelta,
                r.puntos_gps_ida,
                r.puntos_gps_vuelta,
                r.sentido
            FROM ruta r
            INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta
            WHERE rl.id_lugar = :id_lugar
              AND r.activo = 1
            ORDER BY r.numero_ruta
        ";
    } else {
        $sqlRutas = "
            SELECT DISTINCT
                r.id_ruta,
                r.numero_ruta,
                r.descripcion,
                r.tipo,
                r.color_hex,
                r.sentido
            FROM ruta r
            INNER JOIN ruta_lugar rl ON rl.id_ruta = r.id_ruta
            WHERE rl.id_lugar = :id_lugar
              AND r.activo = 1
            ORDER BY r.numero_ruta
        ";
    }
    $stmtRutas = $pdo->prepare($sqlRutas);
    $stmtRutas->execute([':id_lugar' => $idLugar]);
    $rutas = $stmtRutas->fetchAll();

    // ✅ Si no hay en ruta_lugar, buscar por descripcion
    if (empty($rutas)) {
        // Obtener nombre del lugar
        $sqlLugar = "SELECT nombre FROM lugar_turistico WHERE id_lugar = :id_lugar";
        $stmtLugar = $pdo->prepare($sqlLugar);
        $stmtLugar->execute([':id_lugar' => $idLugar]);
        $lugar = $stmtLugar->fetch();
        
        if ($lugar) {
            $nombreLugar = $lugar['nombre'];
            if ($hasIdaVuelta) {
                $sqlRutasNombre = "
                    SELECT id_ruta, numero_ruta, descripcion, tipo, color_hex, 
                           color_hex_ida, color_hex_vuelta, puntos_gps_ida, puntos_gps_vuelta, sentido
                    FROM ruta
                    WHERE activo = 1
                      AND (descripcion LIKE :nombre1 OR descripcion LIKE :nombre2)
                    ORDER BY numero_ruta
                ";
            } else {
                $sqlRutasNombre = "
                    SELECT id_ruta, numero_ruta, descripcion, tipo, color_hex, sentido
                    FROM ruta
                    WHERE activo = 1
                      AND (descripcion LIKE :nombre1 OR descripcion LIKE :nombre2)
                    ORDER BY numero_ruta
                ";
            }
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
        
        // Colores de ida/vuelta (si están disponibles)
        $colorIda = isset($ruta['color_hex_ida']) && !empty($ruta['color_hex_ida'])
            ? $ruta['color_hex_ida']
            : $color;
        $colorVuelta = isset($ruta['color_hex_vuelta']) && !empty($ruta['color_hex_vuelta'])
            ? $ruta['color_hex_vuelta']
            : '#2980B9';
        
        // Verificar si tiene puntos de ida/vuelta
        $tieneIda = isset($ruta['puntos_gps_ida']) && !empty(trim($ruta['puntos_gps_ida']));
        $tieneVuelta = isset($ruta['puntos_gps_vuelta']) && !empty(trim($ruta['puntos_gps_vuelta']));
        
        // Build name from numero_ruta + tipo or descripcion
        $tipo = $ruta['tipo'] ?? 'minibus';
        $tipoCapitalizado = ucfirst($tipo);
        if (!empty($ruta['numero_ruta'])) {
            $nombreArmado = "{$tipoCapitalizado} {$ruta['numero_ruta']}";
        } else {
            $nombreArmado = $ruta['descripcion'] ?? 'Ruta sin nombre';
        }

        $item = [
            'id_ruta' => (int)$ruta['id_ruta'],
            'numero_ruta' => $ruta['numero_ruta'] ?? '',
            'nombre' => $nombreArmado,
            'descripcion' => $ruta['descripcion'] ?? '',
            'tipo' => $tipo,
            'color_hex' => $color,
            'sentido' => $sentido,
        ];
        
        // Agregar colores de ida/vuelta si están disponibles
        if ($hasIdaVuelta) {
            $item['color_hex_ida'] = $colorIda;
            $item['color_hex_vuelta'] = $colorVuelta;
            $item['tiene_ida'] = $tieneIda;
            $item['tiene_vuelta'] = $tieneVuelta;
        }
        
        $data[] = $item;
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