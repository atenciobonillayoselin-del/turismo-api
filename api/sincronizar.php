<?php
/**
 * sincronizar.php - VERSIÓN QUE LEE METADATOS DEL MAPA
 * ----------------------------------------------------
 * Obtiene las coordenadas de las capas desde la API de uMap
 * usando los metadatos del mapa (que sí funcionan)
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
// CONFIGURACIÓN DE UMAP
// =========================================================================
define('UMAP_MAP_ID', 1447967);
define('UMAP_TIMEOUT', 120);

// URL de metadatos (esta SÍ funciona)
$METADATA_URL = "https://umap.openstreetmap.fr/es/map/{$MAP_ID}/geojson/";

// =========================================================================
// ESTADÍSTICAS
// =========================================================================
$stats = [
    'total_grupos'   => 0,
    'total_rutas'    => 0,
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

function descargar_url(string $url): string {
    if (function_exists('curl_init')) {
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
    $ctx = stream_context_create([
        'http' => [
            'timeout' => UMAP_TIMEOUT,
            'header'  => "User-Agent: TurismoAPI-sync\r\nAccept: application/json\r\n",
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        throw new Exception("Fallo al descargar {$url} (file_get_contents).");
    }
    return $resp;
}

/**
 * Obtiene las coordenadas de una capa específica
 */
function obtener_coordenadas_capa(int $mapId, string $datalayerId): ?array {
    $url = "https://umap.openstreetmap.fr/es/datalayer/{$mapId}/{$datalayerId}/?format=geojson";
    try {
        $data = descargar_url($url);
        $geo = json_decode($data, true);
        if (!empty($geo['features'])) {
            return $geo['features'];
        }
    } catch (Exception $e) {
        // Silencioso
    }
    return null;
}

function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    $ida    = str_contains($n, 'ida');
    $vuelta = str_contains($n, 'vuelta');
    if ($ida && !$vuelta) return 'IDA';
    if ($vuelta && !$ida) return 'VUELTA';
    if ($ida && $vuelta) {
        return (mb_strrpos($n, 'vuelta') > mb_strrpos($n, 'ida')) ? 'VUELTA' : 'IDA';
    }
    return 'NORMAL';
}

function extraer_grupo_parentesis(string $nombre): array {
    $out = [];
    if (preg_match_all('/\(([^)]+)\)/u', $nombre, $matches)) {
        foreach ($matches[1] as $m) {
            $l = trim($m);
            if ($l !== '') $out[] = $l;
        }
    }
    return $out;
}

function upsert_lugar(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if (!$id) {
        $sel2 = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE latitud = ? AND longitud = ? LIMIT 1");
        $sel2->execute([$datos['latitud'], $datos['longitud']]);
        $id = $sel2->fetchColumn();
    }

    if ($id) {
        $sql = "UPDATE lugar_turistico
                SET nombre=?, grupo_umap=?, latitud=?, longitud=?
                WHERE id_lugar = ?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['grupo_umap'],
            $datos['latitud'], $datos['longitud'],
            (int)$id,
        ]);
        return (int)$id;
    }

    $sql = "INSERT INTO lugar_turistico
            (nombre, grupo_umap, latitud, longitud, id_umap, activo)
            VALUES (?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['grupo_umap'],
        $datos['latitud'], $datos['longitud'],
        $datos['id_umap'],
    ]);
    $stats['lugares_insert']++;
    return (int)$pdo->lastInsertId();
}

function upsert_ruta(PDO $pdo, array $datos, array &$stats): int {
    $sel = $pdo->prepare("SELECT id_ruta FROM ruta WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    if (!$id && !empty($datos['nombre'])) {
        $sel2 = $pdo->prepare("SELECT id_ruta FROM ruta WHERE nombre = ? LIMIT 1");
        $sel2->execute([$datos['nombre']]);
        $id = $sel2->fetchColumn();
    }

    if ($id) {
        $sql = "UPDATE ruta SET nombre=?, sentido=?, color_hex=?, activo=1 WHERE id_ruta=?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['sentido'],
            $datos['color_hex'], (int)$id,
        ]);
        return (int)$id;
    }

    $sql = "INSERT INTO ruta (nombre, sentido, color_hex, id_umap, activo)
            VALUES (?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['sentido'],
        $datos['color_hex'], $datos['id_umap'],
    ]);
    $stats['rutas_insert']++;
    return (int)$pdo->lastInsertId();
}

function asociar_ruta_lugar(PDO $pdo, int $idRuta, string $nombre, array &$stats): void {
    $pdo->prepare("DELETE FROM ruta_lugar WHERE id_ruta = ?")->execute([$idRuta]);
    
    $candidatos = extraer_grupo_parentesis($nombre);
    foreach ($candidatos as $c) {
        $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
        $sel->execute(["%$c%", "%$c%"]);
        $idL = $sel->fetchColumn();
        if ($idL) {
            $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta,id_lugar) VALUES (?,?)")->execute([$idRuta, $idL]);
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
    
    if (empty($mapData['datalayers'])) {
        throw new Exception("No se encontraron capas en los metadatos del mapa.");
    }
    
    $stats['total_grupos'] = count($mapData['datalayers']);
    
    // 2. Procesar cada grupo
    foreach ($mapData['datalayers'] as $grupo) {
        $nombreGrupo = $grupo['properties']['name'] ?? 'Sin nombre';
        $grupoId = $grupo['id'];
        
        // 2a. Procesar las capas dentro del grupo
        if (!empty($grupo['layers'])) {
            foreach ($grupo['layers'] as $capa) {
                $nombreCapa = $capa['properties']['name'] ?? 'Sin nombre';
                $capaId = $capa['id'];
                
                // Obtener coordenadas de la capa
                $features = obtener_coordenadas_capa(UMAP_MAP_ID, $capaId);
                
                if ($features) {
                    foreach ($features as $feature) {
                        $gtype = $feature['geometry']['type'] ?? '';
                        $coords = $feature['geometry']['coordinates'] ?? [];
                        
                        if ($gtype === 'LineString' && count($coords) >= 2) {
                            // Es una RUTA (línea)
                            $sentido = detectar_sentido($nombreCapa);
                            $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                            
                            $idRuta = upsert_ruta($pdo, [
                                'id_umap' => $capaId,
                                'nombre' => $nombreCapa,
                                'sentido' => $sentido,
                                'color_hex' => $color,
                            ], $stats);
                            
                            asociar_ruta_lugar($pdo, $idRuta, $nombreCapa, $stats);
                            $stats['total_rutas']++;
                        }
                    }
                }
            }
        }
        
        // 2b. Si el grupo tiene un punto, crear lugar
        // Buscar un punto en las capas del grupo
        if (!empty($grupo['layers'])) {
            foreach ($grupo['layers'] as $capa) {
                $capaId = $capa['id'];
                $features = obtener_coordenadas_capa(UMAP_MAP_ID, $capaId);
                if ($features) {
                    foreach ($features as $feature) {
                        if ($feature['geometry']['type'] === 'Point') {
                            $coords = $feature['geometry']['coordinates'];
                            $lat = $coords[1] ?? 0;
                            $lng = $coords[0] ?? 0;
                            
                            if ($lat != 0 && $lng != 0) {
                                upsert_lugar($pdo, [
                                    'id_umap' => 'grupo_' . $grupoId,
                                    'nombre' => $nombreGrupo,
                                    'grupo_umap' => $nombreGrupo,
                                    'latitud' => $lat,
                                    'longitud' => $lng,
                                ], $stats);
                                break 2; // Salir de ambos loops
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 3. Guardar log
    $pdo->prepare("INSERT INTO sincronizacion_log
            (origen,status,total_leidos,lugares_insert,rutas_insert,ruta_lugar_ok,error_msg)
            VALUES (?,?,?,?,?,?,?)")
        ->execute([
            'umap#' . UMAP_MAP_ID, 'OK',
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
        'url_metadatos' => $METADATA_URL,
        'stats' => $stats,
        'mensaje' => "Sincronización OK. {$stats['lugares_insert']} lugares · {$stats['rutas_insert']} rutas · {$stats['ruta_lugar_ok']} asociaciones",
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    $err = $e->getMessage();

    try {
        if (isset($pdo)) {
            $pdo->prepare("INSERT INTO sincronizacion_log
                    (origen,status,total_leidos,lugares_insert,rutas_insert,ruta_lugar_ok,error_msg)
                    VALUES (?,?,?,?,?,?,?)")
                ->execute([
                    'umap#' . UMAP_MAP_ID, 'ERROR',
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