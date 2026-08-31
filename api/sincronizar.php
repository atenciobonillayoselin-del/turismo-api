<?php
/**
 * sincronizar.php - Versión MEJORADA
 * - Acepta archivos con ANY nombre
 * - Extrae UUID del contenido si existe
 * - Usa el nombre del archivo como fallback
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
// CONFIGURACIÓN - CREDENCIALES DIRECTAS
// =========================================================================
$DB_HOST = 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = 23909;
$DB_NAME = 'defaultdb';
$DB_USER = 'avnadmin';
$DB_PASS = 'AVNS_l6o3iZfQycKDeBAGO4c';

$CACHE_DIR = __DIR__ . '/../data/umap_cache';

if (!is_dir($CACHE_DIR)) {
    echo json_encode([
        'success' => false,
        'error' => '❌ No se encontró la carpeta umap_cache',
        'busqueda' => $CACHE_DIR
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// ESCANEAR ARCHIVOS - CUALQUIER NOMBRE
// =========================================================================
$archivos = glob($CACHE_DIR . '/*.json');
$archivosGeo = glob($CACHE_DIR . '/*.geojson');
$archivos = array_merge($archivos, $archivosGeo);

if (empty($archivos)) {
    echo json_encode([
        'success' => false,
        'error' => '⚠️ No hay archivos .json o .geojson en ' . $CACHE_DIR
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// CONEXIÓN A BASE DE DATOS
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '❌ Error de conexión: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// FUNCIONES INTELIGENTES
// =========================================================================

/**
 * Extrae el UUID del contenido del GeoJSON o del nombre del archivo
 */
function extraerUUID($contenido, $nombreArchivo) {
    // 1. Buscar en properties del GeoJSON
    $json = json_decode($contenido, true);
    if ($json && isset($json['features'])) {
        foreach ($json['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            // Buscar campos que contengan UUID
            $campos = ['uuid', 'id', 'layer_id', 'datalayer_id', 'id_capa'];
            foreach ($campos as $campo) {
                if (!empty($props[$campo]) && preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $props[$campo])) {
                    return $props[$campo];
                }
            }
        }
    }
    
    // 2. Intentar extraer UUID del nombre del archivo
    $nombre = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $nombre, $matches)) {
        return $matches[0];
    }
    
    // 3. Generar UUID basado en el nombre (consistente)
    return md5($nombreArchivo);
}

/**
 * Detecta el nombre del lugar desde el archivo
 */
function detectarLugar($nombreArchivo, $props) {
    // Intentar desde el nombre del archivo
    $nombre = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    $nombre = str_replace(['_geojson', '.geojson'], '', $nombre);
    $nombre = str_replace(['__', '_'], ' ', $nombre);
    
    // Limpiar prefijos
    $nombre = preg_replace('/^(Minibus|minibus|Micro|micro)\s*\d+\s*[-–]?\s*/i', '', $nombre);
    $nombre = preg_replace('/\s*[-–]?\s*(IDA|VUELTA|ID|VTA|Vta|Vuelta)\s*$/i', '', $nombre);
    $nombre = preg_replace('/\s*\([^)]*\)\s*/', '', $nombre);
    
    // Si el nombre está vacío o es muy corto, usar properties
    if (strlen($nombre) < 3 && !empty($props['name'])) {
        $nombre = $props['name'];
    }
    if (strlen($nombre) < 3 && !empty($props['title'])) {
        $nombre = $props['title'];
    }
    if (strlen($nombre) < 3 && !empty($props['description'])) {
        $nombre = substr($props['description'], 0, 50);
    }
    
    return trim($nombre) ?: 'Lugar sin nombre';
}

function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'ida')) return 'IDA';
    if (str_contains($n, 'vuelta') || str_contains($n, 'vta')) return 'VUELTA';
    return 'NORMAL';
}

// =========================================================================
// EJECUCIÓN
// =========================================================================
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
        'errores' => []
    ];

    $stmtLugar = $pdo->prepare("
        INSERT INTO lugar_turistico (nombre, descripcion, latitud, longitud, categoria, uuid_capa, activo)
        VALUES (:nombre, :descripcion, :latitud, :longitud, 'Atracción turística', :uuid, 1)
    ");

    $stmtRuta = $pdo->prepare("
        INSERT INTO ruta (nombre, descripcion, tipo, color_hex, sentido, uuid_capa, activo)
        VALUES (:nombre, :descripcion, 'minibus', :color_hex, :sentido, :uuid, 1)
    ");

    $stmtParada = $pdo->prepare("
        INSERT INTO parada (nombre, latitud, longitud, uuid_capa, activo)
        VALUES (:nombre, :latitud, :longitud, :uuid, 1)
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

        // Extraer UUID del archivo
        $uuid = extraerUUID($jsonRaw, $filename);
        
        // Detectar lugar
        $primerFeature = $geojson['features'][0] ?? [];
        $props = $primerFeature['properties'] ?? [];
        $nombreLugar = detectarLugar($filename, $props);
        
        $sentido = detectar_sentido($filename);
        $colorRuta = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';

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
                        ':nombre' => $props['name'] ?? $nombreLugar,
                        ':descripcion' => $props['description'] ?? '',
                        ':latitud' => $lat,
                        ':longitud' => $lng,
                        ':uuid' => $uuid
                    ]);
                    $idLugar = $pdo->lastInsertId();
                    $stats['lugares']++;
                }
            }

            if ($gtype === 'LineString' && count($coords) >= 2) {
                $coordsRuta = $coords;
                $nombreRuta = $props['name'] ?? $props['title'] ?? pathinfo($filename, PATHINFO_FILENAME);
                
                $stmtRuta->execute([
                    ':nombre' => $nombreRuta,
                    ':descripcion' => $props['description'] ?? "Ruta $nombreRuta",
                    ':color_hex' => $props['color'] ?? $colorRuta,
                    ':sentido' => $sentido,
                    ':uuid' => $uuid
                ]);
                $idRuta = $pdo->lastInsertId();
                $stats['rutas']++;
            }
        }

        // Guardar paradas
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
                    $insRP = $pdo->prepare("INSERT INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin) VALUES (?,?,?,?,?)");
                    $insRP->execute([$idRuta, $idParada, $orden, ($orden === 1), ($orden === $total)]);
                }
            }
        }

        // Asociar ruta-lugar
        if ($idRuta && $idLugar) {
            $insRL = $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar, orden) VALUES (?,?,1)");
            $insRL->execute([$idRuta, $idLugar]);
        }

        $stats['procesados']++;
    }

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