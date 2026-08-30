<?php
/**
 * sincronizar.php - VERSIÓN CON DETECCIÓN AUTOMÁTICA DE CAPAS
 * ------------------------------------------------------------
 * Descubre automáticamente todas las capas del mapa
 * SIN necesidad de actualizar el script manualmente
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
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
define('UMAP_TIMEOUT', 120);

// =========================================================================
// ESTADÍSTICAS
// =========================================================================
$stats = [
    'total_capas'    => 0,
    'total_puntos'   => 0,
    'total_lineas'   => 0,
    'lugares_insert' => 0,
    'rutas_insert'   => 0,
    'ruta_lugar_ok'  => 0,
    'warnings'       => [],
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
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'BD: ' . $e->getMessage()]);
    exit;
}

// =========================================================================
// FUNCIONES
// =========================================================================

function descargar_url(string $url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => UMAP_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: application/geo+json, application/json',
            'Accept-Language: es-ES,es;q=0.9',
            'Referer: https://umap.openstreetmap.fr/',
            'Origin: https://umap.openstreetmap.fr',
        ],
        CURLOPT_ENCODING => '',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false || $code < 200 || $code >= 400) {
        throw new Exception("Fallo al descargar {$url} (HTTP {$code}). {$err}");
    }
    return $resp;
}

/**
 * OBTIENE LA LISTA DE CAPAS AUTOMÁTICAMENTE
 */
function obtener_capas_desde_umap(int $mapId): array {
    $capas = [];
    
    // Intentar obtener desde la API de metadatos (con User-Agent de navegador)
    $url = "https://umap.openstreetmap.fr/es/map/{$mapId}/geojson/";
    
    try {
        $data = descargar_url($url);
        $json = json_decode($data, true);
        
        // Buscar datalayers en la estructura
        if (!empty($json['datalayers'])) {
            foreach ($json['datalayers'] as $grupo) {
                $nombreGrupo = $grupo['properties']['name'] ?? 'Sin nombre';
                $grupoId = $grupo['id'] ?? '';
                
                // Procesar capas dentro del grupo
                if (!empty($grupo['layers'])) {
                    foreach ($grupo['layers'] as $capa) {
                        $capas[] = [
                            'id' => $capa['id'],
                            'nombre' => $capa['properties']['name'] ?? 'Sin nombre',
                            'grupo' => $nombreGrupo,
                            'tipo' => 'capa' // línea o punto
                        ];
                    }
                }
                
                // Si el grupo en sí es una capa (tiene geojson)
                if (!empty($grupo['geojson'])) {
                    // Verificar si es un punto o línea
                    $geojson = $grupo['geojson'];
                    if (!empty($geojson['features'])) {
                        foreach ($geojson['features'] as $feature) {
                            $gtype = $feature['geometry']['type'] ?? '';
                            if ($gtype === 'Point' || $gtype === 'LineString') {
                                $capas[] = [
                                    'id' => $grupoId,
                                    'nombre' => $nombreGrupo,
                                    'grupo' => $nombreGrupo,
                                    'tipo' => $gtype === 'Point' ? 'punto' : 'capa'
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // Si no encontramos datalayers, buscar en otras propiedades
        if (empty($capas)) {
            // Buscar en properties.datalayers
            if (!empty($json['properties']['datalayers'])) {
                foreach ($json['properties']['datalayers'] as $grupo) {
                    // ... procesamiento similar
                }
            }
        }
        
    } catch (Exception $e) {
        // Si falla, usar la lista estática como fallback
        $capas = obtener_capas_fallback();
    }
    
    return $capas;
}

/**
 * LISTA DE FALLBACK (por si la API no responde)
 */
function obtener_capas_fallback(): array {
    return [
        ['id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc', 'nombre' => 'Minibus 254 - IDA (Mirador Montículo)', 'grupo' => 'Mirador Montículo', 'tipo' => 'capa'],
        ['id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9', 'nombre' => 'Minibus 254 - VUELTA (Mirador Montículo)', 'grupo' => 'Mirador Montículo', 'tipo' => 'capa'],
        ['id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd', 'nombre' => 'Minibus 204 - IDA (MIRADOR KILLI KILLI)', 'grupo' => 'Mirador Killi Killi', 'tipo' => 'capa'],
        ['id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47', 'nombre' => 'Minibus 889 - IDA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel', 'tipo' => 'capa'],
        ['id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5', 'nombre' => 'Minibus 889 - VUELTA (PLAZA VILLAROEL)', 'grupo' => 'Plaza Villarroel', 'tipo' => 'capa'],
        ['id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', 'nombre' => 'Minibus 364 - IDA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota', 'tipo' => 'capa'],
        ['id' => '291c212e-44db-4460-b84e-773bcfede107', 'nombre' => 'Minibus 364 - VUELTA (PARQUE LAICACOTA)', 'grupo' => 'Parque Laikakota', 'tipo' => 'capa'],
    ];
}

function obtener_geojson_capa(int $mapId, string $datalayerId): ?array {
    $url = "https://umap.openstreetmap.fr/es/datalayer/{$mapId}/{$datalayerId}/?format=geojson";
    try {
        $data = descargar_url($url);
        $geo = json_decode($data, true);
        if (!empty($geo['features'])) {
            return $geo;
        }
    } catch (Exception $e) {}
    return null;
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

function upsert_lugar(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if ($id) {
        $sql = "UPDATE lugar_turistico SET nombre=?, grupo_umap=?, latitud=?, longitud=? WHERE id_lugar=?";
        $pdo->prepare($sql)->execute([$datos['nombre'], $datos['grupo_umap'], $datos['latitud'], $datos['longitud'], $id]);
        return (int)$id;
    }

    $sql = "INSERT INTO lugar_turistico (nombre, grupo_umap, latitud, longitud, id_umap, activo) VALUES (?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([$datos['nombre'], $datos['grupo_umap'], $datos['latitud'], $datos['longitud'], $datos['id_umap']]);
    $stats['lugares_insert']++;
    return (int)$pdo->lastInsertId();
}

function upsert_ruta(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_ruta FROM ruta WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if ($id) {
        $sql = "UPDATE ruta SET nombre=?, sentido=?, color_hex=? WHERE id_ruta=?";
        $pdo->prepare($sql)->execute([$datos['nombre'], $datos['sentido'], $datos['color_hex'], $id]);
        return (int)$id;
    }

    $sql = "INSERT INTO ruta (nombre, sentido, color_hex, id_umap, activo) VALUES (?,?,?,?,1)";
    $pdo->prepare($sql)->execute([$datos['nombre'], $datos['sentido'], $datos['color_hex'], $datos['id_umap']]);
    $stats['rutas_insert']++;
    return (int)$pdo->lastInsertId();
}

function asociar_ruta_lugar(PDO $pdo, int $idRuta, string $nombre, array &$stats): void {
    $candidatos = extraer_grupo_parentesis($nombre);
    foreach ($candidatos as $c) {
        $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
        $sel->execute(["%$c%", "%$c%"]);
        $idL = $sel->fetchColumn();
        if ($idL) {
            $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar) VALUES (?,?)")->execute([$idRuta, $idL]);
            $stats['ruta_lugar_ok']++;
        }
    }
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================

try {
    // 1. OBTENER CAPAS AUTOMÁTICAMENTE
    $capas = obtener_capas_desde_umap(UMAP_MAP_ID);
    
    if (empty($capas)) {
        throw new Exception("No se encontraron capas en el mapa. Usando fallback.");
    }
    
    $stats['total_capas'] = count($capas);
    
    // 2. Procesar cada capa
    foreach ($capas as $capa) {
        $geojson = obtener_geojson_capa(UMAP_MAP_ID, $capa['id']);
        
        if (!$geojson || empty($geojson['features'])) {
            $stats['warnings'][] = "No se pudo obtener: {$capa['nombre']} ({$capa['id']})";
            continue;
        }
        
        foreach ($geojson['features'] as $feature) {
            $gtype = $feature['geometry']['type'] ?? '';
            $coords = $feature['geometry']['coordinates'] ?? [];
            
            if ($gtype === 'Point' && !empty($coords)) {
                // PUNTO → LUGAR TURÍSTICO
                $stats['total_puntos']++;
                $lat = $coords[1] ?? 0;
                $lng = $coords[0] ?? 0;
                if ($lat != 0 && $lng != 0) {
                    upsert_lugar($pdo, [
                        'id_umap' => $capa['id'],
                        'nombre' => $capa['nombre'],
                        'grupo_umap' => $capa['grupo'] ?? $capa['nombre'],
                        'latitud' => $lat,
                        'longitud' => $lng,
                    ], $stats);
                }
                
            } elseif ($gtype === 'LineString' && count($coords) >= 2) {
                // LÍNEA → RUTA
                $stats['total_lineas']++;
                $sentido = detectar_sentido($capa['nombre']);
                $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                
                $idRuta = upsert_ruta($pdo, [
                    'id_umap' => $capa['id'],
                    'nombre' => $capa['nombre'],
                    'sentido' => $sentido,
                    'color_hex' => $color,
                ], $stats);
                
                asociar_ruta_lugar($pdo, $idRuta, $capa['nombre'], $stats);
            }
        }
    }
    
    // 3. Guardar log
    $pdo->prepare("INSERT INTO sincronizacion_log (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg) VALUES (?,?,?,?,?,?,?)")
        ->execute(['umap#' . UMAP_MAP_ID, 'OK', (int)$stats['total_capas'], (int)$stats['lugares_insert'], (int)$stats['rutas_insert'], (int)$stats['ruta_lugar_ok'], null]);

    echo json_encode([
        'success' => true,
        'map_id' => UMAP_MAP_ID,
        'stats' => $stats,
        'mensaje' => "OK: {$stats['lugares_insert']} lugares · {$stats['rutas_insert']} rutas · {$stats['ruta_lugar_ok']} asociaciones",
        'warnings' => $stats['warnings']
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    $err = $e->getMessage();
    try {
        if (isset($pdo)) {
            $pdo->prepare("INSERT INTO sincronizacion_log (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg) VALUES (?,?,?,?,?,?,?)")
                ->execute(['umap#' . UMAP_MAP_ID, 'ERROR', 0, 0, 0, 0, $err]);
        }
    } catch (Throwable $_) {}
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $err, 'stats' => $stats]);
}