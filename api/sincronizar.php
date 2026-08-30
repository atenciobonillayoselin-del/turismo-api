<?php
/**
 * sincronizar.php - VERSIÓN CON PROXY
 * Usa CORS-Anywhere para evitar el bloqueo 403
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 300);

// =========================================================================
// CONFIGURACIÓN
// =========================================================================
$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'defaultdb';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

if (empty($DB_PASS)) {
    echo json_encode(['success' => false, 'error' => 'PDO_PASSWORD no configurada']);
    exit;
}

define('UMAP_MAP_ID', 1447967);
define('UMAP_TIMEOUT', 30);

$stats = [
    'total_capas'    => 0,
    'lugares_insert' => 0,
    'rutas_insert'   => 0,
    'ruta_lugar_ok'  => 0,
    'warnings'       => [],
    'debug'          => [],
];

// =========================================================================
// CONEXIÓN A BD
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    $stats['debug'][] = "✅ Conexión a BD exitosa";
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'BD: ' . $e->getMessage()]);
    exit;
}

// =========================================================================
// LISTA DE CAPAS
// =========================================================================
$capas = [
    ['id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc', 'nombre' => 'Minibus 254 - IDA (Mirador Montículo)', 'grupo' => 'Mirador Montículo'],
    ['id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9', 'nombre' => 'Minibus 254 - VUELTA (Mirador Montículo)', 'grupo' => 'Mirador Montículo'],
    ['id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd', 'nombre' => 'Minibus 204 - IDA (MIRADOR KILLI KILLI)', 'grupo' => 'Mirador Killi Killi'],
    ['id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47', 'nombre' => 'Minibus 889 - IDA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel'],
    ['id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5', 'nombre' => 'Minibus 889 - VUELTA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel'],
    ['id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', 'nombre' => 'Minibus 364 - IDA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota'],
    ['id' => '291c212e-44db-4460-b84e-773bcfede107', 'nombre' => 'Minibus 364 - VUELTA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota'],
];

// =========================================================================
// FUNCIÓN DE DESCARGA CON PROXY
// =========================================================================

function descargar_con_proxy(string $url): string {
    // 1. Intentar con CORS-Anywhere
    $proxyUrls = [
        'https://cors-anywhere.herokuapp.com/' . $url,
        'https://api.allorigins.win/raw?url=' . urlencode($url),
        'https://proxy.cors.sh/' . $url,
    ];
    
    foreach ($proxyUrls as $proxyUrl) {
        try {
            $ch = curl_init($proxyUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/geo+json, application/json',
                    'Accept-Language: es-ES,es;q=0.9',
                    'Origin: https://umap.openstreetmap.fr',
                    'Referer: https://umap.openstreetmap.fr/',
                ],
                CURLOPT_ENCODING => '',
                CURLOPT_VERBOSE => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            
            // Si el proxy devuelve datos válidos (aunque sea 200 o 403)
            if ($code === 200 && !empty($resp)) {
                // Verificar que sea JSON válido
                json_decode($resp);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $resp;
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    // 2. Fallback: intentar directo (por si acaso)
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: application/geo+json, application/json',
                'Accept-Language: es-ES,es;q=0.9',
                'Referer: https://umap.openstreetmap.fr/',
                'Origin: https://umap.openstreetmap.fr',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && !empty($resp)) {
            json_decode($resp);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $resp;
            }
        }
    } catch (Exception $e) {}
    
    throw new Exception("Todos los métodos de descarga fallaron");
}

function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'ida') && !str_contains($n, 'vuelta')) return 'IDA';
    if (str_contains($n, 'vuelta') && !str_contains($n, 'ida')) return 'VUELTA';
    return 'NORMAL';
}

function extraer_grupo_parentesis(string $nombre): array {
    preg_match_all('/\(([^)]+)\)/u', $nombre, $m);
    return array_map('trim', $m[1] ?? []);
}

// =========================================================================
// EJECUCIÓN
// =========================================================================

try {
    $stats['debug'][] = "🚀 Iniciando sincronización...";
    $stats['total_capas'] = count($capas);
    
    // Limpiar tablas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE ruta_lugar");
    $pdo->exec("TRUNCATE TABLE ruta");
    $pdo->exec("TRUNCATE TABLE lugar_turistico");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $stats['debug'][] = "🧹 Tablas limpiadas";
    
    foreach ($capas as $capa) {
        $url = "https://umap.openstreetmap.fr/es/datalayer/" . UMAP_MAP_ID . "/{$capa['id']}/?format=geojson";
        $stats['debug'][] = "📥 Descargando: " . $capa['nombre'];
        
        try {
            $data = descargar_con_proxy($url);
            $geojson = json_decode($data, true);
            
            if (empty($geojson['features'])) {
                $stats['warnings'][] = "Sin features: {$capa['nombre']}";
                continue;
            }
            
            $stats['debug'][] = "✅ Descargado: " . $capa['nombre'] . " (" . count($geojson['features']) . " features)";
            
            foreach ($geojson['features'] as $feature) {
                $gtype = $feature['geometry']['type'] ?? '';
                $coords = $feature['geometry']['coordinates'] ?? [];
                
                if ($gtype === 'Point' && !empty($coords)) {
                    $lat = $coords[1] ?? 0;
                    $lng = $coords[0] ?? 0;
                    if ($lat != 0 && $lng != 0) {
                        $nombreLugar = $capa['grupo'];
                        $sql = "INSERT INTO lugar_turistico 
                                (nombre, grupo_umap, latitud, longitud, id_umap, activo) 
                                VALUES (?, ?, ?, ?, ?, 1)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nombreLugar, $capa['grupo'], $lat, $lng, $capa['id']]);
                        $stats['lugares_insert']++;
                        $stats['debug'][] = "📍 Lugar: $nombreLugar";
                    }
                }
                
                if ($gtype === 'LineString' && count($coords) >= 2) {
                    $sentido = detectar_sentido($capa['nombre']);
                    $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                    
                    $sql = "INSERT INTO ruta 
                            (nombre, sentido, color_hex, id_umap, id_grupo_umap, activo) 
                            VALUES (?, ?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$capa['nombre'], $sentido, $color, $capa['id'], $capa['grupo']]);
                    $idRuta = (int)$pdo->lastInsertId();
                    $stats['rutas_insert']++;
                    $stats['debug'][] = "🛤️ Ruta: " . $capa['nombre'];
                    
                    // Asociar
                    $parentesis = extraer_grupo_parentesis($capa['nombre']);
                    foreach ($parentesis as $p) {
                        $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
                        $sel->execute(["%$p%", "%$p%"]);
                        $idLugar = $sel->fetchColumn();
                        if ($idLugar) {
                            $sql2 = "INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar) VALUES (?, ?)";
                            $stmt2 = $pdo->prepare($sql2);
                            $stmt2->execute([$idRuta, $idLugar]);
                            $stats['ruta_lugar_ok']++;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $stats['warnings'][] = "Error en {$capa['nombre']}: " . $e->getMessage();
            $stats['debug'][] = "❌ Error: " . $e->getMessage();
        }
    }
    
    // Guardar log
    $pdo->prepare("INSERT INTO sincronizacion_log 
        (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg) 
        VALUES (?,?,?,?,?,?,?)")
        ->execute([
            'umap#' . UMAP_MAP_ID, 
            'OK', 
            (int)$stats['total_capas'], 
            (int)$stats['lugares_insert'], 
            (int)$stats['rutas_insert'], 
            (int)$stats['ruta_lugar_ok'],
            null
        ]);

    echo json_encode([
        'success' => true,
        'map_id' => UMAP_MAP_ID,
        'stats' => $stats,
        'mensaje' => "OK: {$stats['lugares_insert']} lugares · {$stats['rutas_insert']} rutas · {$stats['ruta_lugar_ok']} asociaciones",
        'debug' => $stats['debug']
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(), 
        'stats' => $stats,
        'debug' => $stats['debug']
    ]);
}