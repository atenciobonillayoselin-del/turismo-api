<?php
/**
 * sincronizar.php - VERSIÓN QUE OBTIENE COORDENADAS DE CADA CAPA
 * ----------------------------------------------------
 * 1. Obtiene metadatos del mapa desde la URL correcta
 * 2. Para cada capa, descarga el GeoJSON individual
 * 3. Extrae coordenadas de puntos y líneas
 */

// =========================================================================
// CONFIGURACIÓN DE ERRORES
// =========================================================================
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 300);

// =========================================================================
// CONFIGURACIÓN DESDE VARIABLES DE ENTORNO
// =========================================================================
$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'defaultdb';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

if (empty($DB_PASS)) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: PDO_PASSWORD no está configurada en variables de entorno.'
    ]);
    exit;
}

// =========================================================================
// CONFIGURACIÓN DE UMAP - URLS CORRECTAS
// =========================================================================
define('UMAP_MAP_ID', 1447967);
define('UMAP_TIMEOUT', 120);

// ⭐ URL CORRECTA para metadatos del mapa
$METADATA_URL = "https://umap.openstreetmap.fr/es/map/" . UMAP_MAP_ID . "/geojson/";

// =========================================================================
// ESTADÍSTICAS
// =========================================================================
$stats = [
    'total_grupos'   => 0,
    'total_capas'    => 0,
    'total_puntos'   => 0,
    'total_lineas'   => 0,
    'lugares_insert' => 0,
    'rutas_insert'   => 0,
    'ruta_lugar_ok'  => 0,
    'warnings'       => [],
];

// =========================================================================
// CONEXIÓN A BASE DE DATOS
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a BD: ' . $e->getMessage()
    ]);
    exit;
}

// =========================================================================
// FUNCIONES
// =========================================================================

/**
 * Descarga una URL con cURL
 */
function descargar_url(string $url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => UMAP_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (TurismoAPI-sync)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
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
 * Obtiene el GeoJSON de una capa específica
 */
function obtener_geojson_capa(int $mapId, string $datalayerId): ?array {
    $url = "https://umap.openstreetmap.fr/es/datalayer/{$mapId}/{$datalayerId}/?format=geojson";
    try {
        $data = descargar_url($url);
        $geo = json_decode($data, true);
        if (!empty($geo['features'])) {
            return $geo;
        }
    } catch (Exception $e) {
        // Silencioso - la capa puede no tener datos
    }
    return null;
}

/**
 * Detecta sentido IDA/VUELTA del nombre
 */
function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'ida') && !str_contains($n, 'vuelta')) return 'IDA';
    if (str_contains($n, 'vuelta') && !str_contains($n, 'ida')) return 'VUELTA';
    if (str_contains($n, 'ida') && str_contains($n, 'vuelta')) {
        return strpos($n, 'vuelta') > strpos($n, 'ida') ? 'VUELTA' : 'IDA';
    }
    return 'NORMAL';
}

/**
 * Extrae nombre del lugar entre paréntesis
 */
function extraer_grupo_parentesis(string $nombre): array {
    preg_match_all('/\(([^)]+)\)/u', $nombre, $m);
    return array_map('trim', $m[1] ?? []);
}

/**
 * Limpia descripción de HTML
 */
function limpiar_descripcion(?string $texto): ?string {
    if ($texto) {
        return strip_tags($texto);
    }
    return null;
}

/**
 * UPSERT lugar_turistico
 */
function upsert_lugar(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if ($id) {
        $sql = "UPDATE lugar_turistico
                SET nombre=?, grupo_umap=?, latitud=?, longitud=?, descripcion=?
                WHERE id_lugar = ?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['grupo_umap'],
            $datos['latitud'], $datos['longitud'],
            $datos['descripcion'] ?? null,
            (int)$id,
        ]);
        return (int)$id;
    }

    $sql = "INSERT INTO lugar_turistico
            (nombre, grupo_umap, latitud, longitud, descripcion, id_umap, activo)
            VALUES (?,?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['grupo_umap'],
        $datos['latitud'], $datos['longitud'],
        $datos['descripcion'] ?? null,
        $datos['id_umap'],
    ]);
    $stats['lugares_insert']++;
    return (int)$pdo->lastInsertId();
}

/**
 * UPSERT ruta
 */
function upsert_ruta(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_ruta FROM ruta WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if ($id) {
        $sql = "UPDATE ruta SET nombre=?, sentido=?, color_hex=?, descripcion=? WHERE id_ruta=?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['sentido'],
            $datos['color_hex'], $datos['descripcion'] ?? null,
            (int)$id,
        ]);
        return (int)$id;
    }

    $sql = "INSERT INTO ruta (nombre, sentido, color_hex, descripcion, id_umap, activo)
            VALUES (?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['sentido'],
        $datos['color_hex'], $datos['descripcion'] ?? null,
        $datos['id_umap'],
    ]);
    $stats['rutas_insert']++;
    return (int)$pdo->lastInsertId();
}

/**
 * Asocia una ruta con sus lugares (por nombre entre paréntesis)
 */
function asociar_ruta_lugar(PDO $pdo, int $idRuta, string $nombre, array &$stats): void {
    $candidatos = extraer_grupo_parentesis($nombre);
    foreach ($candidatos as $c) {
        $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
        $sel->execute(["%$c%", "%$c%"]);
        $idL = $sel->fetchColumn();
        if ($idL) {
            $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta, id_lugar) VALUES (?,?)")
                ->execute([$idRuta, $idL]);
            $stats['ruta_lugar_ok']++;
        }
    }
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================

try {
    // 1. Obtener metadatos del mapa
    $jsonRaw = descargar_url($METADATA_URL);
    $mapData = json_decode($jsonRaw, true);
    
    // Verificar que tenemos datos válidos
    if (empty($mapData)) {
        throw new Exception("No se pudieron obtener los metadatos del mapa.");
    }
    
    // Buscar los datalayers en la estructura correcta
    $datalayers = [];
    
    // Si el JSON tiene la estructura de uMap
    if (isset($mapData['datalayers'])) {
        $datalayers = $mapData['datalayers'];
    } elseif (isset($mapData['features'])) {
        // Buscar en las propiedades de los features
        foreach ($mapData['features'] as $feature) {
            if (!empty($feature['properties']['datalayers'])) {
                $datalayers = $feature['properties']['datalayers'];
                break;
            }
        }
    }
    
    // Si no encontramos datalayers, buscar en cualquier propiedad
    if (empty($datalayers)) {
        foreach ($mapData as $key => $value) {
            if ($key === 'datalayers' || str_contains($key, 'datalayer')) {
                $datalayers = $value;
                break;
            }
        }
    }
    
    if (empty($datalayers)) {
        throw new Exception("No se encontraron capas en los metadatos del mapa.");
    }
    
    $stats['total_grupos'] = count($datalayers);
    
    // 2. Procesar cada grupo/capa
    foreach ($datalayers as $grupo) {
        // Obtener nombre del grupo
        $nombreGrupo = 'Sin nombre';
        if (isset($grupo['properties']['name'])) {
            $nombreGrupo = $grupo['properties']['name'];
        } elseif (isset($grupo['name'])) {
            $nombreGrupo = $grupo['name'];
        }
        
        $grupoId = $grupo['id'] ?? '';
        $grupoDescripcion = limpiar_descripcion($grupo['properties']['description'] ?? null);
        
        // Verificar si este grupo tiene capas hijas (layers)
        $capas = [];
        if (!empty($grupo['layers'])) {
            $capas = $grupo['layers'];
        } elseif (!empty($grupo['id'])) {
            // Si es una capa individual, la procesamos directamente
            $capas = [$grupo];
        }
        
        // Si no hay capas, pero el grupo tiene un punto o línea, procesarlo como capa
        if (empty($capas) && !empty($grupo['geojson'])) {
            $capas = [$grupo];
        }
        
        foreach ($capas as $capa) {
            $stats['total_capas']++;
            
            $nombreCapa = $capa['properties']['name'] ?? $capa['name'] ?? 'Sin nombre';
            $capaId = $capa['id'] ?? '';
            $capaDescripcion = limpiar_descripcion($capa['properties']['description'] ?? null);
            
            if (empty($capaId)) {
                continue;
            }
            
            // Obtener el GeoJSON de la capa
            $geojson = obtener_geojson_capa(UMAP_MAP_ID, $capaId);
            
            if ($geojson && !empty($geojson['features'])) {
                foreach ($geojson['features'] as $feature) {
                    $gtype = $feature['geometry']['type'] ?? '';
                    $coords = $feature['geometry']['coordinates'] ?? [];
                    
                    if ($gtype === 'Point' && !empty($coords)) {
                        // PUNTO → LUGAR TURÍSTICO
                        $stats['total_puntos']++;
                        $lat = $coords[1] ?? 0;
                        $lng = $coords[0] ?? 0;
                        
                        if ($lat != 0 && $lng != 0) {
                            $idLugar = upsert_lugar($pdo, [
                                'id_umap' => $capaId . '_point',
                                'nombre' => $nombreCapa,
                                'grupo_umap' => $nombreGrupo,
                                'latitud' => $lat,
                                'longitud' => $lng,
                                'descripcion' => $capaDescripcion ?: $grupoDescripcion,
                            ], $stats);
                        }
                        
                    } elseif ($gtype === 'LineString' && count($coords) >= 2) {
                        // LÍNEA → RUTA
                        $stats['total_lineas']++;
                        $sentido = detectar_sentido($nombreCapa);
                        $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                        
                        $idRuta = upsert_ruta($pdo, [
                            'id_umap' => $capaId,
                            'nombre' => $nombreCapa,
                            'sentido' => $sentido,
                            'color_hex' => $color,
                            'descripcion' => $capaDescripcion,
                        ], $stats);
                        
                        asociar_ruta_lugar($pdo, $idRuta, $nombreCapa, $stats);
                    }
                }
            } else {
                $stats['warnings'][] = "No se pudieron obtener datos de la capa: {$nombreCapa} ({$capaId})";
            }
        }
    }
    
    // 3. Guardar log
    $pdo->prepare("INSERT INTO sincronizacion_log
            (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg)
            VALUES (?,?,?,?,?,?,?)")
        ->execute([
            'umap#' . UMAP_MAP_ID,
            'OK',
            (int)$stats['total_grupos'],
            (int)$stats['lugares_insert'],
            (int)$stats['rutas_insert'],
            (int)$stats['ruta_lugar_ok'],
            null,
        ]);
    
    // 4. Respuesta
    echo json_encode([
        'success' => true,
        'map_id' => UMAP_MAP_ID,
        'stats' => $stats,
        'mensaje' => "Sincronización OK. {$stats['lugares_insert']} lugares · {$stats['rutas_insert']} rutas · {$stats['ruta_lugar_ok']} asociaciones",
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    $err = $e->getMessage();

    try {
        if (isset($pdo)) {
            $pdo->prepare("INSERT INTO sincronizacion_log
                    (origen, status, total_leidos, lugares_insert, rutas_insert, ruta_lugar_ok, error_msg)
                    VALUES (?,?,?,?,?,?,?)")
                ->execute([
                    'umap#' . UMAP_MAP_ID,
                    'ERROR',
                    (int)$stats['total_grupos'],
                    (int)$stats['lugares_insert'],
                    (int)$stats['rutas_insert'],
                    (int)$stats['ruta_lugar_ok'],
                    $err,
                ]);
        }
    } catch (Throwable $_) {}

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $err,
        'stats' => $stats,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}