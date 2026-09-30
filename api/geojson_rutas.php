<?php
/**
 * geojson_rutas.php - Rutas de transporte con recorrido de IDA y VUELTA
 *
 * Formato de respuesta (lo que espera RutaGPX.fromJson en Flutter):
 *  {
 *    "id_ruta": 1, "numero_ruta": "364", "nombre": "Minibus 364",
 *    "descripcion": "...", "tipo": "minibus",
 *    "ida":    { "color": "#A100D4", "puntos": [[lat,lng], ...] },   // opcional
 *    "vuelta": { "color": "#33FF00", "puntos": [[lat,lng], ...] }    // opcional
 *  }
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$idRuta   = isset($_GET['id_ruta']) ? (int) $_GET['id_ruta'] : 0;
$grupo    = isset($_GET['grupo'])   ? trim($_GET['grupo'])   : '';
$tipoRuta = isset($_GET['tipo'])    ? trim($_GET['tipo'])    : '';

function tableColExists(PDO $pdo, string $table, string $col): bool {
    static $cache = [];
    $k = "$table.$col";
    if (isset($cache[$k])) return $cache[$k];
    try {
        $s = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        $cache[$k] = (bool)$s->fetch();
    } catch (Throwable $e) { $cache[$k] = false; }
    return $cache[$k];
}

/** "lat,lng;lat,lng;..." (admite saltos de línea y espacios) -> [[lat,lng],...] */
function parseGps(?string $str): array {
    $out = [];
    if ($str === null || trim($str) === '') return $out;
    foreach (preg_split('/[;\r\n]+/', $str) as $par) {
        $par = trim($par);
        if ($par === '') continue;
        $c = explode(',', $par);
        if (count($c) < 2) continue;
        if (!is_numeric(trim($c[0])) || !is_numeric(trim($c[1]))) continue;
        $lat = (float) trim($c[0]);
        $lng = (float) trim($c[1]);
        if ($lat !== 0.0 && $lng !== 0.0) $out[] = [$lat, $lng];
    }
    return $out;
}

/** Devuelve un #RRGGBB válido o el valor por defecto */
function colorValido($c, string $default): string {
    $c = is_string($c) ? trim($c) : '';
    return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtoupper($c) : $default;
}

try {
    $selRuta = ['r.id_ruta', 'r.numero_ruta', 'r.descripcion', 'r.tipo', 'r.color_hex', 'r.puntos_gps_ida'];
    foreach (['puntos_gps_vuelta', 'color_hex_ida', 'color_hex_vuelta', 'sentido'] as $col) {
        if (tableColExists($pdo, 'ruta', $col)) $selRuta[] = "r.$col";
    }

    $where  = ["r.activo = 1"];
    $params = [];

    if ($idRuta > 0) {
        $where[] = "r.id_ruta = :id_ruta";
        $params[':id_ruta'] = $idRuta;
    }
    if (!empty($grupo)) {
        $grupoLike = "%{$grupo}%";
        $where[] = "(r.descripcion LIKE :g1 OR EXISTS (
            SELECT 1 FROM ruta_lugar rl
            INNER JOIN lugar_turistico l ON l.id_lugar = rl.id_lugar
            WHERE rl.id_ruta = r.id_ruta AND l.nombre LIKE :g2
        ))";
        $params[':g1'] = $grupoLike;
        $params[':g2'] = $grupoLike;
    }
    if (!empty($tipoRuta)) {
        $where[] = "LOWER(r.tipo) = :tipo";
        $params[':tipo'] = strtolower($tipoRuta);
    }

    $sqlRutas = "SELECT DISTINCT " . implode(', ', $selRuta) . "
                 FROM ruta r
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY r.tipo, r.numero_ruta";
    $stmtRutas = $pdo->prepare($sqlRutas);
    $stmtRutas->execute($params);
    $rutas = $stmtRutas->fetchAll(PDO::FETCH_ASSOC);

    // Respaldo para rutas antiguas que solo tienen paradas (sin GPS de ida)
    $stmtParadas = $pdo->prepare("SELECT p.latitud, p.longitud
                                  FROM ruta_parada rp
                                  INNER JOIN parada p ON p.id_parada = rp.id_parada
                                  WHERE rp.id_ruta = :id_ruta
                                  ORDER BY rp.orden ASC");

    $data = [];
    foreach ($rutas as $ruta) {
        $idR = (int) $ruta['id_ruta'];

        // ---- IDA: GPS de ida; si no hay, usar paradas ----
        $puntosIda = parseGps($ruta['puntos_gps_ida'] ?? null);
        if (count($puntosIda) < 2) {
            $stmtParadas->execute([':id_ruta' => $idR]);
            $puntosIda = [];
            foreach ($stmtParadas->fetchAll(PDO::FETCH_ASSOC) as $pt) {
                $lat = (float) $pt['latitud'];
                $lng = (float) $pt['longitud'];
                if ($lat !== 0.0 && $lng !== 0.0) $puntosIda[] = [$lat, $lng];
            }
        }

        // ---- VUELTA ----
        $puntosVuelta = parseGps($ruta['puntos_gps_vuelta'] ?? null);

        $tieneIda    = count($puntosIda) >= 2;
        $tieneVuelta = count($puntosVuelta) >= 2;
        if (!$tieneIda && !$tieneVuelta) continue;

        $tipo = $ruta['tipo'] ?? 'minibus';
        $nombreArmado = !empty($ruta['numero_ruta'])
            ? ucfirst($tipo) . ' ' . $ruta['numero_ruta']
            : ($ruta['descripcion'] ?? 'Ruta sin nombre');

        // Colores propios de ida / vuelta (los que se eligen en el admin)
        $colorBase   = colorValido($ruta['color_hex'] ?? null, '#0066CC');
        $colorIda    = colorValido($ruta['color_hex_ida'] ?? null, $colorBase);
        $colorVuelta = colorValido($ruta['color_hex_vuelta'] ?? null, '#FF6600');

        $item = [
            'id_ruta'     => $idR,
            'numero_ruta' => $ruta['numero_ruta'] ?? '',
            'nombre'      => $nombreArmado,
            'descripcion' => $ruta['descripcion'] ?? '',
            'tipo'        => $tipo,
        ];
        if ($tieneIda)    $item['ida']    = ['color' => $colorIda,    'puntos' => $puntosIda];
        if ($tieneVuelta) $item['vuelta'] = ['color' => $colorVuelta, 'puntos' => $puntosVuelta];

        $data[] = $item;
    }

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'total'   => count($data),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data'    => [],
        'total'   => 0,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
