<?php
/**
 * sincronizar.php - Sincroniza GeoJSON con MySQL (CON DUPLICATE KEY CORREGIDO)
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

$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'app_turistica_la_paz';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

$CACHE_DIR = __DIR__ . '/../data/umap_cache';

if (!is_dir($CACHE_DIR)) {
    echo json_encode(['success' => false, 'error' => '❌ No se encontró la carpeta umap_cache']);
    exit;
}

$archivos = glob($CACHE_DIR . '/*.json');

if (empty($archivos)) {
    echo json_encode(['success' => false, 'error' => '⚠️ No hay archivos .json en ' . $CACHE_DIR]);
    exit;
}

try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '❌ Error de conexión: ' . $e->getMessage()]);
    exit;
}

function extraerNombreLugar($nombreArchivo, $props = []) {
    $mapeo = [
        '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Mirador Montículo',
        '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Mirador Montículo (Vuelta)',
        '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Mirador Killi Killi',
        'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Plaza Villarroel',
        'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Plaza Villarroel (Vuelta)',
        '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Parque Laikakota',
        '291c212e-44db-4460-b84e-773bcfede107' => 'Parque Laikakota (Vuelta)',
        'cota_cota_838' => 'Laguna Cota Cota',
    ];
    
    $nombre = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    if (isset($mapeo[$nombre])) return $mapeo[$nombre];
    if (!empty($props['name'])) return $props['name'];
    if (!empty($props['title'])) return $props['title'];
    
    $nombre = str_replace(['_geojson', '.geojson'], '', $nombre);
    $nombre = str_replace(['__', '_'], ' ', $nombre);
    $nombre = preg_replace('/^(Minibus|minibus|Micro|micro)\s*\d+\s*[-–]?\s*/i', '', $nombre);
    $nombre = preg_replace('/\s*[-–]?\s*(IDA|VUELTA|ID|VTA|Vta|Vuelta)\s*$/i', '', $nombre);
    $nombre = preg_replace('/\s*\([^)]*\)\s*/', '', $nombre);
    
    return trim($nombre) ?: 'Lugar sin nombre';
}

function detectarSentido($nombreArchivo) {
    $n = strtolower($nombreArchivo);
    if (strpos($n, 'vuelta') !== false) return 'VUELTA';
    if (strpos($n, 'ida') !== false) return 'IDA';
    return 'NORMAL';
}

function extraerUuid($nombreArchivo) {
    $nombre = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $nombre, $matches)) {
        return strtolower($matches[0]);
    }
    return strtolower($nombre);
}

function reconstruirRelacionesRutaLugar(PDO $pdo): void {
    $pdo->exec("DELETE FROM ruta_lugar");
    $pdo->exec("INSERT INTO ruta_lugar (id_ruta, id_lugar, orden)
        SELECT r.id_ruta, l.id_lugar, 1
        FROM ruta r
        INNER JOIN lugar_turistico l ON l.uuid_capa = r.uuid_capa
        WHERE r.activo = 1 AND l.activo = 1
        ORDER BY r.id_ruta, l.id_lugar");
}

try {
    $pdo->beginTransaction();
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta_lugar");
    $pdo->exec("DELETE FROM lugar_turistico");
    $pdo->exec("DELETE FROM ruta");
    $pdo->exec("DELETE FROM parada");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $stats = [
        'total_archivos' => count($archivos),
        'procesados' => 0,
        'rutas' => 0,
        'lugares' => 0,
        'paradas' => 0,
        'relaciones' => 0,
        'errores' => []
    ];

    // ✅ CORREGIDO: Sin placeholders en VALUES
    $stmtLugar = $pdo->prepare("
        INSERT INTO lugar_turistico (nombre, descripcion, latitud, longitud, categoria, uuid_capa, activo)
        VALUES (:nombre, :descripcion, :latitud, :longitud, 'Atracción turística', :uuid, 1)
        ON DUPLICATE KEY UPDATE 
            nombre = VALUES(nombre),
            descripcion = VALUES(descripcion),
            categoria = VALUES(categoria),
            activo = 1
    ");

    $stmtRuta = $pdo->prepare("
        INSERT INTO ruta (nombre, descripcion, tipo, color_hex, sentido, uuid_capa, activo)
        VALUES (:nombre, :descripcion, 'minibus', :color_hex, :sentido, :uuid, 1)
        ON DUPLICATE KEY UPDATE 
            descripcion = VALUES(descripcion),
            color_hex = VALUES(color_hex),
            sentido = VALUES(sentido),
            activo = 1
    ");

    $stmtParada = $pdo->prepare("
        INSERT INTO parada (nombre, latitud, longitud, uuid_capa, activo)
        VALUES (:nombre, :latitud, :longitud, :uuid, 1)
        ON DUPLICATE KEY UPDATE 
            nombre = VALUES(nombre),
            activo = 1
    ");

    foreach ($archivos as $filePath) {
        $filename = basename($filePath);
        echo "📄 Procesando: $filename\n";
        
        $jsonRaw = file_get_contents($filePath);
        $geojson = json_decode($jsonRaw, true);

        if (!$geojson || !isset($geojson['features'])) {
            $stats['errores'][] = "$filename no es GeoJSON válido";
            continue;
        }

        $uuid = extraerUuid($filename);
        $sentido = detectarSentido($filename);
        $colorRuta = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
        
        $primerFeature = $geojson['features'][0] ?? [];
        $props = $primerFeature['properties'] ?? [];
        $nombreLugar = extraerNombreLugar($filename, $props);

        $idRuta = null;
        $idLugar = null;
        $coordsRuta = [];

        foreach ($geojson['features'] as $feature) {
            $gtype = $feature['geometry']['type'] ?? '';
            $coords = $feature['geometry']['coordinates'] ?? [];
            $props = $feature['properties'] ?? [];

            if ($gtype === 'Point' && count($coords) >= 2) {
                $lat = (float)$coords[1];
                $lng = (float)$coords[0];
                
                if ($lat != 0 && $lng != 0) {
                    $stmtLugar->execute([
                        ':nombre' => $nombreLugar,
                        ':descripcion' => $props['description'] ?? '',
                        ':latitud' => $lat,
                        ':longitud' => $lng,
                        ':uuid' => $uuid
                    ]);
                    $idLugar = $pdo->lastInsertId();
                    if (!$idLugar || $idLugar <= 0) {
                        $idLugarRow = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE uuid_capa = ? ORDER BY id_lugar DESC LIMIT 1");
                        $idLugarRow->execute([$uuid]);
                        $idLugar = (int)($idLugarRow->fetchColumn() ?: 0);
                    }
                    $stats['lugares']++;
                }
            }

            if ($gtype === 'LineString' && count($coords) >= 2) {
                $coordsRuta = $coords;
                $nombreRuta = $props['name'] ?? $props['title'] ?? $nombreLugar;
                
                if ($sentido !== 'NORMAL' && !str_contains($nombreRuta, $sentido)) {
                    $nombreRuta = "$nombreRuta ($sentido)";
                }
                
                $stmtRuta->execute([
                    ':nombre' => $nombreRuta,
                    ':descripcion' => $props['description'] ?? "Ruta hacia $nombreLugar",
                    ':color_hex' => $props['color'] ?? $colorRuta,
                    ':sentido' => $sentido,
                    ':uuid' => $uuid
                ]);
                $idRuta = $pdo->lastInsertId();
                if (!$idRuta || $idRuta <= 0) {
                    $idRutaRow = $pdo->prepare("SELECT id_ruta FROM ruta WHERE uuid_capa = ? ORDER BY id_ruta DESC LIMIT 1");
                    $idRutaRow->execute([$uuid]);
                    $idRuta = (int)($idRutaRow->fetchColumn() ?: 0);
                }
                $stats['rutas']++;
            }
        }

        if ($idRuta && !empty($coordsRuta)) {
            $total = count($coordsRuta);
            foreach ($coordsRuta as $i => $coord) {
                $lat = (float)$coord[1];
                $lng = (float)$coord[0];
                if ($lat == 0 || $lng == 0) continue;
                
                $orden = $i + 1;
                $stmtParada->execute([
                    ':nombre' => "Parada $orden - $nombreLugar",
                    ':latitud' => $lat,
                    ':longitud' => $lng,
                    ':uuid' => $uuid
                ]);
                $idParada = $pdo->lastInsertId();
                $stats['paradas']++;
                
                if ($idParada) {
                    $insRP = $pdo->prepare("INSERT IGNORE INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin) VALUES (?,?,?,?,?)");
                    $insRP->execute([$idRuta, $idParada, $orden, ($orden === 1), ($orden === $total)]);
                }
            }
        }

        if ($idRuta && $idLugar) {
            $insRL = $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar, orden) VALUES (?,?,1)");
            $insRL->execute([$idRuta, $idLugar]);
            $stats['relaciones']++;
        }

        $stats['procesados']++;
    }

    reconstruirRelacionesRutaLugar($pdo);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Sincronización completada',
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'linea' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}