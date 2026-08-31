<?php
/**
 * sincronizar.php - Carga AUTOMÁTICA de GeoJSON locales a MySQL Aiven
 * Turismo La Paz
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 300);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =========================================================================
// CONFIGURACIÓN CENTRALIZADA
// =========================================================================
$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'defaultdb';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: ''; // Poner tu contraseña o usar variable de entorno

// Búsqueda inteligente de la carpeta umap_cache
$posiblesRutas = [
    __DIR__ . '/data/umap_cache',
    __DIR__ . '/../data/umap_cache',
    __DIR__ . '/umap_cache',
    __DIR__ . '/../umap_cache',
];

$rutaEncontrada = null;
foreach ($posiblesRutas as $ruta) {
    if (is_dir($ruta)) {
        $rutaEncontrada = realpath($ruta);
        break;
    }
}

if (!$rutaEncontrada) {
    echo json_encode([
        'success' => false,
        'error'   => '❌ No se encontró la carpeta umap_cache en las rutas esperadas',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

define('LOCAL_GEOJSON_DIR', $rutaEncontrada);

if (empty($DB_PASS)) {
    echo json_encode([
        'success' => false,
        'error'   => '❌ Variable PDO_PASSWORD no configurada',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// ESCANEO AUTOMÁTICO DE ARCHIVOS GEOJSON EN LA CARPETA
// =========================================================================
$archivosEncontrados = glob(LOCAL_GEOJSON_DIR . '/*.{geojson,json}', GLOB_BRACE);

$stats = [
    'total_capas'      => count($archivosEncontrados),
    'capas_ok'         => 0,
    'lugares_insert'   => 0,
    'lugares_update'   => 0,
    'rutas_insert'     => 0,
    'rutas_update'     => 0,
    'paradas_insert'   => 0,
    'paradas_skip'     => 0,
    'ruta_lugar_ok'    => 0,
    'ruta_parada_ok'   => 0,
    'warnings'         => [],
    'debug'            => [
        "📂 Carpeta detectada: " . LOCAL_GEOJSON_DIR
    ],
];

if (empty($archivosEncontrados)) {
    echo json_encode([
        'success' => false,
        'error'   => '⚠️ No se encontraron archivos .geojson o .json en ' . LOCAL_GEOJSON_DIR,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// CONEXIÓN A BASE DE DATOS (Aiven MySQL)
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $sslCa = __DIR__ . '/config/ca.pem';
    if (!file_exists($sslCa)) {
        $sslCa = __DIR__ . '/../config/ca.pem';
    }
    if (file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
    $pdo->exec("SET SESSION time_zone = '-04:00'");
    
    $stats['debug'][] = "✅ Conexión a Aiven BD exitosa → $DB_HOST:$DB_PORT/$DB_NAME";
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => '❌ BD Connection failed: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// FUNCIONES AUXILIARES
// =========================================================================
function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'ida')) return 'IDA';
    if (str_contains($n, 'vuelta') || str_contains($n, 'vta')) return 'VUELTA';
    return 'NORMAL';
}

function generar_nombre_legible(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace('__', ' ', $name);
    $name = str_replace('_', ' ', $name);
    return ucwords(trim($name));
}

function limpiar_nombre_lugar(string $nombre): string {
    $nombre = preg_replace('/^(Minibus|minibus|Micro|micro|Teleferico|teleferico)\s*\d+\s*[-–]?\s*/i', '', $nombre);
    $nombre = preg_replace('/\s*[-–]?\s*(IDA|VUELTA|ID|VTA|Vta|Vuelta)\s*$/i', '', $nombre);
    $nombre = preg_replace('/\s*\([^)]*\)\s*/', '', $nombre);
    return trim($nombre);
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================
try {
    $pdo->beginTransaction();
    $stats['debug'][] = "🔓 Transacción iniciada";

    // Limpieza de datos antiguos
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta_lugar");
    $pdo->exec("DELETE FROM ruta");
    $pdo->exec("DELETE FROM lugar_turistico");
    $pdo->exec("DELETE FROM parada");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $stats['debug'][] = "🧹 Tablas limpiadas correctamente";

    $stmtLugar = $pdo->prepare("
        INSERT INTO lugar_turistico (nombre, descripcion, latitud, longitud, categoria, activo)
        VALUES (:nombre, :descripcion, :latitud, :longitud, 'Atracción turística', 1)
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1
    ");

    $stmtRuta = $pdo->prepare("
        INSERT INTO ruta (nombre, descripcion, tipo, color_hex, sentido, activo)
        VALUES (:nombre, :descripcion, 'minibus', :color_hex, :sentido, 1)
    ");

    $stmtParada = $pdo->prepare("
        INSERT INTO parada (nombre, latitud, longitud, activo)
        VALUES (:nombre, :latitud, :longitud, 1)
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1
    ");

    foreach ($archivosEncontrados as $filePath) {
        $filename = basename($filePath);
        $nombreCapa = generar_nombre_legible($filename);

        $jsonRaw = file_get_contents($filePath);
        $geojson = json_decode($jsonRaw, true);

        if (!$geojson || !isset($geojson['features'])) {
            $stats['warnings'][] = "⚠️ GeoJSON inválido o vacío: $filename";
            continue;
        }

        $sentido = detectar_sentido($filename);
        $colorRuta = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
        $grupoCapa = limpiar_nombre_lugar($nombreCapa);

        $stmtRuta->execute([
            ':nombre'      => $nombreCapa,
            ':descripcion' => "Ruta de " . $nombreCapa,
            ':color_hex'   => $colorRuta,
            ':sentido'     => $sentido,
        ]);
        $idRuta = $pdo->lastInsertId();
        $stats['rutas_insert']++;

        $idLugar = null;

        foreach ($geojson['features'] as $feature) {
            $gtype  = $feature['geometry']['type'] ?? '';
            $coords = $feature['geometry']['coordinates'] ?? [];
            $props  = $feature['properties'] ?? [];

            if ($gtype === 'Point' && count($coords) >= 2) {
                $lat = (float)$coords[1];
                $lng = (float)$coords[0];
                $nomPunto = trim($props['name'] ?? '') ?: $grupoCapa;

                $stmtLugar->execute([
                    ':nombre'      => $nomPunto,
                    ':descripcion' => $props['description'] ?? '',
                    ':latitud'     => $lat,
                    ':longitud'    => $lng,
                ]);

                $selId = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                $selId->execute([$lat, $lng]);
                $idLugar = $selId->fetchColumn();
                $stats['lugares_insert']++;
            }

            if ($gtype === 'LineString' && count($coords) >= 2) {
                $orden = 1;
                $totalParadas = count($coords);

                foreach ($coords as $coord) {
                    $lat = (float)$coord[1];
                    $lng = (float)$coord[0];
                    if ($lat == 0 || $lng == 0) continue;

                    $nomParada = "Punto $orden de $nombreCapa";

                    $stmtParada->execute([
                        ':nombre'   => $nomParada,
                        ':latitud'  => $lat,
                        ':longitud' => $lng,
                    ]);

                    $selP = $pdo->prepare("SELECT id_parada FROM parada WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                    $selP->execute([$lat, $lng]);
                    $idParada = $selP->fetchColumn();

                    if ($idParada) {
                        $stats['paradas_insert']++;
                        $esInicio = ($orden === 1) ? 1 : 0;
                        $esFin    = ($orden === $totalParadas) ? 1 : 0;

                        $insRP = $pdo->prepare("INSERT IGNORE INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin) VALUES (?,?,?,?,?)");
                        $insRP->execute([$idRuta, $idParada, $orden, $esInicio, $esFin]);
                        $stats['ruta_parada_ok']++;
                    }
                    $orden++;
                }
            }
        }

        if ($idRuta && $idLugar) {
            $insRL = $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar, orden) VALUES (?,?,1)");
            $insRL->execute([$idRuta, $idLugar]);
            $stats['ruta_lugar_ok']++;
        }

        $stats['capas_ok']++;
    }

    $pdo->commit();
    $stats['debug'][] = "💾 Sincronización finalizada y COMMIT realizado";

    echo json_encode([
        'success' => true,
        'mensaje' => 'Sincronización de GeoJSON locales completada con éxito',
        'stats'   => $stats,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'linea'   => $e->getLine(),
        'debug'   => $stats['debug'],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}