<?php
/**
 * sincronizar.php
 * -----------------
 * uMap (#1447967) es la FUENTE DE VERDAD.
 * Este script:
 *   1) Descarga TODO el mapa (GeoJSON completo) desde uMap.openstreetmap.fr
 *   2) Clasifica features:
 *        Point      → INSERT/UPDATE en lugar_turistico
 *        LineString → INSERT/UPDATE en ruta + parada + ruta_parada
 *   3) Asocia ruta ↔ lugar:
 *        - Extrae nombre de lugar entre paréntesis del nombre de ruta
 *        - O bien: encuentra el lugar más cercano a <50 metros del trazado
 *        - Guarda la relación N:M en ruta_lugar
 *   4) Guarda estadística en sincronizacion_log
 *
 * USO:
 *   a) Abrir URL manualmente:   https://tu-host/api/sincronizar.php
 *   b) Con cron cada 5 min:     curl -fsS https://tu-host/api/sincronizar.php >/dev/null
 *
 * NOTA: Antes de ejecutar, corre en Aiven el script
 *       sql/000_inicial_sincronizacion_umap.sql (1 sola vez).
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 300);

// =========================================================================
// CONFIGURACIÓN
// =========================================================================
define('UMAP_MAP_ID',   1447967);                      // ← Tu mapa
define('UMAP_LANG',    'es');                          // idioma
define('UMAP_TIMEOUT', 120);                           // segundos

$stats = [
    'total_leidos'     => 0,
    'puntos_leidos'    => 0,
    'lineas_leidas'    => 0,
    'poligonos_leidos' => 0,
    'lugares_insert'   => 0, 'lugares_update'   => 0,
    'rutas_insert'     => 0, 'rutas_update'     => 0,
    'paradas_insert'   => 0, 'paradas_update'   => 0,
    'ruta_parada_ok'   => 0,
    'ruta_lugar_ok'    => 0,
    'warnings'         => [],
];

// =========================================================================
// HELPERS
// =========================================================================

/**
 * Descarga una URL con fallback (cURL si existe, si no file_get_contents).
 * uMap necesita un User-Agent válido.
 */
function descargar_url(string $url): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => UMAP_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (TurismoAPI-sync; +https://tu-dominio.bo)',
            CURLOPT_HTTPHEADER     => ['Accept: application/geo+json, application/json'],
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
            'header'  => "User-Agent: TurismoAPI-sync\r\nAccept: application/geo+json\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        throw new Exception("Fallo al descargar {$url} (file_get_contents). Habilita php-curl.");
    }
    return $resp;
}

/**
 * Extrae el ID del feature (uMap lo guarda internamente _umap_options.id o properties._storage_options.id)
 * Si no, hace un hash con nombre + geometría (determinista) para no duplicar.
 */
function feature_id(array $f, string $fallbackLayer = 'global'): string {
    $props = $f['properties'] ?? [];
    if (!empty($props['_storage_options']['id'])) return (string) $props['_storage_options']['id'];
    if (!empty($props['_umap_options']['id']))     return (string) $props['_umap_options']['id'];
    if (!empty($props['id']))                      return (string) $props['id'];
    if (!empty($f['id']))                          return (string) $f['id'];
    // Hash de fallback
    return $fallbackLayer . '::' . md5(($props['name'] ?? '') . json_encode($f['geometry'], JSON_UNESCAPED_UNICODE));
}

function feature_nombre(array $f): string {
    $props = $f['properties'] ?? [];
    if (!empty($props['name']))  return trim((string)$props['name']);
    if (!empty($props['title'])) return trim((string)$props['title']);
    return 'Feature sin nombre';
}

function feature_color(array $f, string $default = '#E74C3C'): string {
    $props = $f['properties'] ?? [];
    foreach (['color', 'stroke', 'marker-color', '_umap_options.color'] as $k) {
        $v = $props;
        foreach (explode('.', $k) as $kk) { if (is_array($v) && isset($v[$kk])) $v = $v[$kk]; else { $v = null; break; } }
        if (!empty($v) && is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) return $v;
    }
    return $default;
}

/**
 * Detecta sentido IDA/VUELTA desde el nombre de ruta.
 * Si encuentra ambas por error: el último gana.
 */
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

/**
 * Si el nombre contiene "(...)", extrae el texto interno (ej: "Minibus 364 - IDA (PARQUE LAIKAKOTA)" → "PARQUE LAIKAKOTA")
 * También quita signos y normaliza para comparar con grupo_umap/nombre.
 */
function extraer_grupo_parentesis(string $nombre): array {
    $matches = [];
    $out = [];
    // Toma TODOS los textos entre paréntesis si hay varios
    if (preg_match_all('/\(([^)]+)\)/u', $nombre, $matches)) {
        foreach ($matches[1] as $m) {
            $l = trim($m);
            if ($l !== '') $out[] = $l;
        }
    }
    return $out;
}

/**
 * Distancia Haversine entre dos coords lat/lng en METROS.
 */
function distancia_metros(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
    return $R * (2 * atan2(sqrt($a), sqrt(1-$a)));
}

/**
 * "Upsert" flexible por clave única de sincronización (id_umap + fallback).
 * Devuelve el id (PK) de la fila afectada.
 */
function upsert_lugar(PDO $pdo, array $datos, array &$stats): int {
    // Búsqueda por id_umap
    $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE id_umap = ? LIMIT 1");
    $sel->execute([$datos['id_umap']]);
    $id = $sel->fetchColumn();

    // Fallback: búsqueda por coordenada única (la BD tiene UNIQUE KEY uq_lugar_coordenadas)
    if (!$id) {
        $sel2 = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE latitud = ? AND longitud = ? LIMIT 1");
        $sel2->execute([$datos['latitud'], $datos['longitud']]);
        $id = $sel2->fetchColumn();
    }

    if ($id) {
        $sql = "UPDATE lugar_turistico
                   SET nombre=?, descripcion=?, categoria=?, latitud=?, longitud=?,
                       grupo_umap=?, icono_umap=?, color_hex=?, panorama_url=?, imagen_url=?,
                       datalayer_id=?, id_capa=?, activo=1
                 WHERE id_lugar = ?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['descripcion'], $datos['categoria'],
            $datos['latitud'], $datos['longitud'],
            $datos['grupo_umap'], $datos['icono_umap'], $datos['color_hex'],
            $datos['panorama_url'], $datos['imagen_url'],
            $datos['datalayer_id'], $datos['id_capa'],
            (int)$id,
        ]);
        $stats['lugares_update']++;
        return (int)$id;
    }

    $sql = "INSERT INTO lugar_turistico
            (nombre,descripcion,categoria,latitud,longitud,grupo_umap,icono_umap,color_hex,
             panorama_url,imagen_url,id_umap,datalayer_id,id_capa,activo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['descripcion'], $datos['categoria'],
        $datos['latitud'], $datos['longitud'],
        $datos['grupo_umap'], $datos['icono_umap'], $datos['color_hex'],
        $datos['panorama_url'], $datos['imagen_url'],
        $datos['id_umap'], $datos['datalayer_id'], $datos['id_capa'],
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
        $sql = "UPDATE ruta SET nombre=?, descripcion=?, tipo=?, sentido=?, color_hex=?,
                                 datalayer_id=?, id_grupo_umap=?, activo=1 WHERE id_ruta=?";
        $pdo->prepare($sql)->execute([
            $datos['nombre'], $datos['descripcion'], $datos['tipo'], $datos['sentido'],
            $datos['color_hex'], $datos['datalayer_id'], $datos['id_grupo_umap'], (int)$id,
        ]);
        $stats['rutas_update']++;
        return (int)$id;
    }

    $sql = "INSERT INTO ruta (nombre,descripcion,tipo,sentido,color_hex,id_umap,datalayer_id,id_grupo_umap,activo)
            VALUES (?,?,?,?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([
        $datos['nombre'], $datos['descripcion'], $datos['tipo'], $datos['sentido'],
        $datos['color_hex'], $datos['id_umap'], $datos['datalayer_id'], $datos['id_grupo_umap'],
    ]);
    $stats['rutas_insert']++;
    return (int)$pdo->lastInsertId();
}

/**
 * Busca o crea una parada por lat/lng única.
 */
function upsert_parada(PDO $pdo, float $lat, float $lng, ?string $nombre = null, ?string $id_umap = null, array &$stats): int {
    if ($id_umap) {
        $sel = $pdo->prepare("SELECT id_parada FROM parada WHERE id_umap = ? LIMIT 1");
        $sel->execute([$id_umap]);
        $id = $sel->fetchColumn();
        if ($id) return (int)$id;
    }
    $sel2 = $pdo->prepare("SELECT id_parada FROM parada WHERE latitud = ? AND longitud = ? LIMIT 1");
    $sel2->execute([$lat, $lng]);
    $id = $sel2->fetchColumn();
    if ($id) return (int)$id;

    $nombreFinal = $nombre ?: "Parada ({$lat}, {$lng})";
    $sql = "INSERT INTO parada (nombre,latitud,longitud,id_umap,activo) VALUES (?,?,?,?,1)";
    $pdo->prepare($sql)->execute([$nombreFinal, $lat, $lng, $id_umap]);
    $stats['paradas_insert']++;
    return (int)$pdo->lastInsertId();
}

/**
 * Borra todas las filas ruta_parada para una ruta, y re-inserta según el array de coordenadas del LineString.
 */
function reconstruir_ruta_parada(PDO $pdo, int $idRuta, array $coordenadasLineString, array &$stats): void {
    $pdo->prepare("DELETE FROM ruta_parada WHERE id_ruta = ?")->execute([$idRuta]);
    $orden = 0;
    foreach ($coordenadasLineString as $i => $coord) {
        [$lng, $lat] = [$coord[0], $coord[1]];
        $orden++;
        $idParada = upsert_parada($pdo, (float)$lat, (float)$lng, "Parada #{$orden}", null, $stats);
        $sql = "INSERT INTO ruta_parada (id_ruta,id_parada,orden,es_inicio,es_fin) VALUES (?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
            $idRuta, $idParada, $orden,
            ($orden === 1 ? 1 : 0),
            ($orden === count($coordenadasLineString) ? 1 : 0),
        ]);
        $stats['ruta_parada_ok']++;
    }
}

/**
 * Asocia ruta con lugares:
 *   a) Busca por el nombre entre paréntesis: "364 IDA (Parque Laikakota)" → Parque Laikakota
 *   b) O busca lugar más cercano al punto MEDIO de la ruta (<100m)
 *   c) O busca coincidencia en el nombre de la capa
 */
function asociar_ruta_lugar(PDO $pdo, int $idRuta, string $nombreRuta, string $idCapa, array $coordsLS, array &$stats): void {
    $pdo->prepare("DELETE FROM ruta_lugar WHERE id_ruta = ?")->execute([$idRuta]);

    $idLugares = [];

    // a) Nombre entre paréntesis
    $candidatos = extraer_grupo_parentesis($nombreRuta);
    foreach ($candidatos as $c) {
        $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico
                               WHERE grupo_umap LIKE ? OR nombre LIKE ? OR id_capa LIKE ? LIMIT 1");
        $sel->execute(["%{$c}%", "%{$c}%", "%{$c}%"]);
        $res = $sel->fetchColumn();
        if ($res) $idLugares[(int)$res] = true;
    }

    // b) Coincidencia por nombre de capa (ej: capa "Parque Laikakota - Rutas")
    if (!empty($idCapa)) {
        $sel2 = $pdo->prepare("SELECT id_lugar FROM lugar_turistico
                                WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 3");
        $sel2->execute(["%{$idCapa}%", "%{$idCapa}%"]);
        foreach ($sel2->fetchAll(PDO::FETCH_COLUMN) as $lid) $idLugares[(int)$lid] = true;
    }

    // c) Cercanía a puntos de la ruta (<50m)
    if (empty($idLugares)) {
        $todos = $pdo->query("SELECT id_lugar,latitud,longitud FROM lugar_turistico WHERE activo=1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($todos as $l) {
            foreach ($coordsLS as $c) {
                $d = distancia_metros((float)$l['latitud'], (float)$l['longitud'], (float)$c[1], (float)$c[0]);
                if ($d <= 50) { $idLugares[(int)$l['id_lugar']] = true; break; }
            }
        }
    }

    foreach ($idLugares as $idL => $_) {
        $ins = $pdo->prepare("INSERT IGNORE INTO ruta_lugar (id_ruta,id_lugar) VALUES (?,?)");
        $ins->execute([$idRuta, $idL]);
        if ($ins->rowCount() > 0) $stats['ruta_lugar_ok']++;
    }
}

/**
 * Extrae propiedades de popup de uMap para descripción limpia.
 */
function limpiar_descripcion(array $props): ?string {
    if (!empty($props['description']) && is_string($props['description'])) {
        return strip_tags($props['description']);
    }
    return null;
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================

try {

    // ---------------------------------------------------------------------
    // PASO 1: Descargar GeoJSON completo del mapa
    // ---------------------------------------------------------------------
    $urlFull = sprintf('https://umap.openstreetmap.fr/%s/map/%d/?format=geojson', UMAP_LANG, UMAP_MAP_ID);
    $jsonRaw = descargar_url($urlFull);

    $geo = json_decode($jsonRaw, true);
    if (!is_array($geo) || empty($geo['type']) || $geo['type'] !== 'FeatureCollection') {
        throw new Exception("La respuesta de uMap no es un FeatureCollection válido. Revisa MAP_ID.");
    }

    $features = $geo['features'] ?? [];
    $stats['total_leidos'] = count($features);

    // ---------------------------------------------------------------------
    // PASO 2: Clasificar features
    // ---------------------------------------------------------------------
    foreach ($features as $idx => $f) {

        if (empty($f['geometry'])) { $stats['warnings'][] = "Feature #{$idx}: sin geometry"; continue; }

        $gtype = $f['geometry']['type'] ?? '';
        $props = $f['properties'] ?? [];
        $nombre = feature_nombre($f);
        $id_umap = feature_id($f, (string)$idx);
        $id_capa   = trim((string)($props['_datalayer_name'] ?? $props['datalayer_name'] ?? $props['layer'] ?? ''));
        $dlayer_id = (string)($props['_datalayer_id'] ?? $props['datalayer_id'] ?? '');

        if ($gtype === 'Point') {
            // -------------------------------------------------------------
            // POINT → lugar_turistico
            // -------------------------------------------------------------
            $stats['puntos_leidos']++;
            [$lng, $lat] = [$f['geometry']['coordinates'][0], $f['geometry']['coordinates'][1]];
            $categoria = trim((string)($props['categoria'] ?? $props['category'] ?? ($id_capa ?: 'Atracción turística')));
            $icono_umap = '';
            if (!empty($props['icon'])) $icono_umap = (string)$props['icon'];
            else {
                if (!empty($props['_umap_options']['icon']['icon'])) $icono_umap = (string)$props['_umap_options']['icon']['icon'];
                elseif (!empty($props['_storage_options']['icon'])) $icono_umap = (string)$props['_storage_options']['icon'];
            }
            $grupo = $props['grupo'] ?? $props['group'] ?? null;
            if (!$grupo) $grupo = $id_capa ?: $nombre;

            $idLugar = upsert_lugar($pdo, [
                'id_umap'      => $id_umap,
                'nombre'       => $nombre,
                'descripcion'  => limpiar_descripcion($props),
                'categoria'    => $categoria,
                'latitud'      => (float)$lat,
                'longitud'     => (float)$lng,
                'grupo_umap'   => (string)$grupo,
                'icono_umap'   => $icono_umap,
                'color_hex'    => feature_color($f, '#E74C3C'),
                'panorama_url' => (string)($props['panorama_url'] ?? ''),
                'imagen_url'   => (string)($props['imagen_url']   ?? $props['image'] ?? ''),
                'datalayer_id' => $dlayer_id,
                'id_capa'      => $id_capa,
            ], $stats);

            // Actualizamos id_capa al grupo del nombre si estaba vacío
            if ($id_capa === '' && $grupo) {
                $pdo->prepare("UPDATE lugar_turistico SET grupo_umap = COALESCE(NULLIF(grupo_umap,''), ?), id_capa = ? WHERE id_lugar = ?")
                    ->execute([$grupo, $grupo, $idLugar]);
            }

        } elseif ($gtype === 'LineString') {
            // -------------------------------------------------------------
            // LINESTRING → ruta + ruta_parada + parada + ruta_lugar
            // -------------------------------------------------------------
            $stats['lineas_leidas']++;
            $coordsLS = $f['geometry']['coordinates'] ?? [];
            if (count($coordsLS) < 2) { $stats['warnings'][] = "Ruta {$nombre}: <2 puntos"; continue; }

            $sentido = detectar_sentido($nombre);
            $tipo = (string)($props['tipo'] ?? 'minibus');
            if (!in_array($tipo, ['micro','minibus','teleferico','otros'], true)) $tipo = 'minibus';

            $nombreGrupoHint = '';
            foreach (extraer_grupo_parentesis($nombre) as $h) { $nombreGrupoHint = $h; break; }
            if (!$nombreGrupoHint) $nombreGrupoHint = $id_capa;

            $idRuta = upsert_ruta($pdo, [
                'id_umap'       => $id_umap,
                'nombre'        => $nombre,
                'descripcion'   => limpiar_descripcion($props),
                'tipo'          => $tipo,
                'sentido'       => $sentido,
                'color_hex'     => feature_color($f, ($sentido === 'VUELTA' ? '#2980B9' : '#E74C3C')),
                'datalayer_id'  => $dlayer_id,
                'id_grupo_umap' => $nombreGrupoHint,
            ], $stats);

            reconstruir_ruta_parada($pdo, $idRuta, $coordsLS, $stats);
            asociar_ruta_lugar($pdo, $idRuta, $nombre, $id_capa, $coordsLS, $stats);

        } elseif ($gtype === 'Polygon' || $gtype === 'MultiLineString' || $gtype === 'MultiPoint') {
            $stats['poligonos_leidos']++;
            $stats['warnings'][] = "Feature #{$idx} {$gtype}: soporte parcial (sin guardar)";
        } else {
            $stats['warnings'][] = "Feature #{$idx}: tipo desconocido {$gtype}";
        }
    }

    // ---------------------------------------------------------------------
    // PASO 3: Registro de log
    // ---------------------------------------------------------------------
    $pdo->prepare("INSERT INTO sincronizacion_log
            (origen,status,total_leidos,lugares_insert,lugares_update,rutas_insert,rutas_update,paradas_insert,ruta_lugar_ok,ruta_parada_ok,error_msg)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            'umap#' . UMAP_MAP_ID, 'OK',
            (int)$stats['total_leidos'],
            (int)$stats['lugares_insert'], (int)$stats['lugares_update'],
            (int)$stats['rutas_insert'],   (int)$stats['rutas_update'],
            (int)$stats['paradas_insert'],
            (int)$stats['ruta_lugar_ok'],  (int)$stats['ruta_parada_ok'],
            null,
        ]);

    // ---------------------------------------------------------------------
    // SALIDA JSON
    // ---------------------------------------------------------------------
    echo json_encode([
        'success'  => true,
        'map_id'   => UMAP_MAP_ID,
        'url_umap' => $urlFull,
        'stats'    => $stats,
        'mensaje'  => "Sincronización OK. {$stats['lugares_insert']} nuevos lugares · {$stats['rutas_insert']} nuevas rutas · {$stats['ruta_parada_ok']} paradas en ruta · {$stats['ruta_lugar_ok']} asociaciones ruta↔lugar",
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {

    $err = $e->getMessage();

    try {
        global $pdo;
        if (isset($pdo)) {
            $pdo->prepare("INSERT INTO sincronizacion_log
                    (origen,status,total_leidos,lugares_insert,lugares_update,rutas_insert,rutas_update,paradas_insert,ruta_lugar_ok,ruta_parada_ok,error_msg)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    'umap#' . UMAP_MAP_ID, 'ERROR',
                    (int)$stats['total_leidos'],
                    (int)$stats['lugares_insert'], (int)$stats['lugares_update'],
                    (int)$stats['rutas_insert'],   (int)$stats['rutas_update'],
                    (int)$stats['paradas_insert'],
                    (int)$stats['ruta_lugar_ok'],  (int)$stats['ruta_parada_ok'],
                    $err,
                ]);
        }
    } catch (Throwable $_) {}

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $err,
        'stats'   => $stats,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
