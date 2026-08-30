<?php
/**
 * sincronizar.php - VERSIÓN CON ESTRATEGIA DE DESCARGA ALTERNATIVA
 * Usa múltiples métodos para descargar las capas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
define('UMAP_TIMEOUT', 120);

$stats = [
    'total_capas'    => 0,
    'total_puntos'   => 0,
    'total_lineas'   => 0,
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
// FUNCIÓN DE DESCARGA CON MÚLTIPLES ESTRATEGIAS
// =========================================================================

function descargar_con_estrategias(string $url): ?string {
    $strategies = [
        // Estrategia 1: Con User-Agent de navegador y headers completos
        function($url) {
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
                CURLOPT_ENCODING => '',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($code === 200) ? $resp : null;
        },
        // Estrategia 2: Con proxy
        function($url) {
            $proxyUrl = 'https://cors-anywhere.herokuapp.com/' . $url;
            $ch = curl_init($proxyUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($code === 200) ? $resp : null;
        },
        // Estrategia 3: Con enlace corto
        function($url) {
            // Reemplazar URL con enlace corto
            $shortUrl = str_replace(
                'https://umap.openstreetmap.fr/es/datalayer/',
                'http://u.osmfr.org/m/1447967/datalayer/',
                $url
            );
            $ch = curl_init($shortUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($code === 200) ? $resp : null;
        },
    ];
    
    foreach ($strategies as $index => $strategy) {
        try {
            $result = $strategy($url);
            if ($result !== null) {
                return $result;
            }
        } catch (Exception $e) {
            // Continuar con la siguiente estrategia
        }
    }
    return null;
}

// =========================================================================
// LISTA DE CAPAS
// =========================================================================
function obtener_capas(): array {
    return [
        ['id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc', 'nombre' => 'Minibus 254 - IDA (Mirador Montículo)', 'grupo' => 'Mirador Montículo', 'tiene_punto' => true],
        ['id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9', 'nombre' => 'Minibus 254 - VUELTA (Mirador Montículo)', 'grupo' => 'Mirador Montículo', 'tiene_punto' => false],
        ['id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd', 'nombre' => 'Minibus 204 - IDA (MIRADOR KILLI KILLI)', 'grupo' => 'Mirador Killi Killi', 'tiene_punto' => true],
        ['id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47', 'nombre' => 'Minibus 889 - IDA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel', 'tiene_punto' => false],
        ['id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5', 'nombre' => 'Minibus 889 - VUELTA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel', 'tiene_punto' => false],
        ['id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', 'nombre' => 'Minibus 364 - IDA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota', 'tiene_punto' => false],
        ['id' => '291c212e-44db-4460-b84e-773bcfede107', 'nombre' => 'Minibus 364 - VUELTA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota', 'tiene_punto' => false],
    ];
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
    $capas = obtener_capas();
    $stats['total_capas'] = count($capas);
    $stats['debug'][] = "📋 Total capas: " . count($capas);
    
    // LIMPIAR TABLAS ANTES DE INSERTAR
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE ruta_lugar");
    $pdo->exec("TRUNCATE TABLE ruta");
    $pdo->exec("TRUNCATE TABLE lugar_turistico");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $stats['debug'][] = "🧹 Tablas limpiadas";
    
    foreach ($capas as $capa) {
        $url = "https://umap.openstreetmap.fr/es/datalayer/{$capa['id']}/?format=geojson";
        $stats['debug'][] = "📥 Descargando: " . $capa['nombre'];
        
        $data = descargar_con_estrategias($url);
        
        if (!$data) {
            $stats['warnings'][] = "No se pudo descargar: {$capa['nombre']}";
            $stats['debug'][] = "❌ Falló descarga: " . $capa['nombre'];
            continue;
        }
        
        $geojson = json_decode($data, true);
        if (empty($geojson['features'])) {
            $stats['warnings'][] = "Sin features: {$capa['nombre']}";
            $stats['debug'][] = "⚠️ Sin features para: " . $capa['nombre'];
            continue;
        }
        
        $stats['debug'][] = "✅ Descargado: " . $capa['nombre'] . " (" . count($geojson['features']) . " features)";
        
        foreach ($geojson['features'] as $feature) {
            $gtype = $feature['geometry']['type'] ?? '';
            $coords = $feature['geometry']['coordinates'] ?? [];
            
            if ($gtype === 'Point' && !empty($coords)) {
                $stats['total_puntos']++;
                $lat = $coords[1] ?? 0;
                $lng = $coords[0] ?? 0;
                
                if ($lat != 0 && $lng != 0) {
                    $nombreLugar = $capa['nombre'];
                    // Si el nombre tiene paréntesis, extraer solo el nombre del lugar
                    $parentesis = extraer_grupo_parentesis($nombreLugar);
                    if (!empty($parentesis)) {
                        $nombreLugar = $parentesis[0];
                    }
                    
                    $sql = "INSERT INTO lugar_turistico 
                            (nombre, grupo_umap, latitud, longitud, id_umap, activo) 
                            VALUES (?, ?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $nombreLugar,
                        $capa['grupo'],
                        $lat,
                        $lng,
                        $capa['id']
                    ]);
                    $stats['lugares_insert']++;
                    $stats['debug'][] = "📍 Lugar insertado: " . $nombreLugar;
                }
            }
            
            if ($gtype === 'LineString' && count($coords) >= 2) {
                $stats['total_lineas']++;
                $sentido = detectar_sentido($capa['nombre']);
                $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                
                $sql = "INSERT INTO ruta 
                        (nombre, sentido, color_hex, id_umap, id_grupo_umap, activo) 
                        VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $capa['nombre'],
                    $sentido,
                    $color,
                    $capa['id'],
                    $capa['grupo']
                ]);
                $idRuta = (int)$pdo->lastInsertId();
                $stats['rutas_insert']++;
                $stats['debug'][] = "🛤️ Ruta insertada: " . $capa['nombre'];
                
                // Asociar con lugar
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
                        $stats['debug'][] = "🔗 Asociación creada: " . $capa['nombre'] . " → lugar ID " . $idLugar;
                    }
                }
            }
        }
    }
    
    $stats['debug'][] = "--- RESUMEN FINAL ---";
    $stats['debug'][] = "Lugares insertados: " . $stats['lugares_insert'];
    $stats['debug'][] = "Rutas insertadas: " . $stats['rutas_insert'];
    $stats['debug'][] = "Asociaciones: " . $stats['ruta_lugar_ok'];
    
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
    $err = $e->getMessage();
    try {
        if (isset($pdo)) {
            $pdo->prepare("INSERT INTO sincronizacion_log 
                (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg) 
                VALUES (?,?,?,?,?,?,?)")
                ->execute(['umap#' . UMAP_MAP_ID, 'ERROR', 0, 0, 0, 0, $err]);
        }
    } catch (Throwable $_) {}
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $err, 
        'stats' => $stats,
        'debug' => $stats['debug']
    ]);
}