<?php
/**
 * sincronizar_umap_definitivo.php
 * -------------------------------------------------------------
 * SCRIPT DEFINITIVO - Detecta y descarga TODAS las capas del mapa
 * Mapa: rutaslapaz_1447967 (CORRECTO)
 * Método: Worker Cloudflare (prioridad) + fallback directo
 * -------------------------------------------------------------
 * USO: php api/sincronizar_umap_definitivo.php
 * CON COOKIE: php api/sincronizar_umap_definitivo.php --cookie "sessionid=xxx"
 * FORZAR: php api/sincronizar_umap_definitivo.php --force
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("❌ Solo se permite ejecución desde línea de comandos (CLI).\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// CONFIGURACIÓN
// ============================================================
define('UMAP_WORKER_URL', 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/?url=');
define('UMAP_MAP_ID', 1447967);
define('CACHE_DIR', __DIR__ . '/../data/umap_cache');
define('MAP_SLUG', 'rutaslapaz');

// Leer argumentos CLI
$FORCE = in_array('--force', $argv, true);
$COOKIE = '';
foreach ($argv as $i => $arg) {
    if ($arg === '--cookie' && isset($argv[$i + 1])) {
        $COOKIE = trim($argv[$i + 1]);
    }
}

// ============================================================
// CONEXIÓN A AIVEN
// ============================================================
$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'app_turistica_la_paz';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
    echo "✅ Conexión a Aiven exitosa\n\n";
} catch (PDOException $e) {
    die("❌ Error conectando a Aiven: " . $e->getMessage() . "\n");
}

if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0777, true);
    echo "📁 Directorio caché creado: " . CACHE_DIR . "\n";
}

// ============================================================
// 1. DETECTAR TODAS LAS CAPAS DEL MAPA
// ============================================================
echo "🔍 Detectando capas del mapa #" . UMAP_MAP_ID . "...\n";

/**
 * Detectar capas usando la API pública de uMap
 */
function detectarCapasDesdeAPI(): array {
    $apiUrl = "https://umap.openstreetmap.fr/api/0.1/map/" . UMAP_MAP_ID . "/";
    echo "   ↳ API: $apiUrl\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'TurismoLaPaz-Sync/4.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: es-ES,es;q=0.9',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code !== 200 || !$resp) {
        return [];
    }
    
    $data = json_decode($resp, true);
    if (!is_array($data) || !isset($data['datalayers'])) {
        return [];
    }
    
    $capas = [];
    foreach ($data['datalayers'] as $layer) {
        $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
        $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
        if ($uuid) {
            $capas[$uuid] = $name;
        }
    }
    return $capas;
}

/**
 * Detectar capas descargando la configuración JSON del mapa
 */
function detectarCapasDesdeConfig(): array {
    $urls = [
        "https://umap.openstreetmap.fr/en/map/" . MAP_SLUG . "_" . UMAP_MAP_ID . "?json",
        "https://umap.openstreetmap.fr/m/" . UMAP_MAP_ID . "?json",
        "https://umap.openstreetmap.fr/api/0.1/map/" . UMAP_MAP_ID . "/",
    ];
    
    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'TurismoLaPaz-Sync/4.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            if (is_array($data) && isset($data['datalayers'])) {
                $capas = [];
                foreach ($data['datalayers'] as $layer) {
                    $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
                    $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
                    if ($uuid) {
                        $capas[$uuid] = $name;
                    }
                }
                if (!empty($capas)) {
                    return $capas;
                }
            }
        }
    }
    return [];
}

// Intentar detectar capas
$capas = detectarCapasDesdeAPI();
if (empty($capas)) {
    echo "   ⚠️  API falló, intentando con configuración JSON...\n";
    $capas = detectarCapasDesdeConfig();
}

// Si aún no hay capas, usar lista de respaldo (conocidas del mapa)
if (empty($capas)) {
    echo "   ⚠️  Usando lista de respaldo (capas conocidas del mapa)\n";
    $capas = [
        // Capas del mapa rutaslapaz_1447967
        '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA (Mirador Montículo)',
        '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA (Mirador Montículo)',
        '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA (Mirador Killi Killi)',
        'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA (Plaza Villarroel)',
        'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA (Plaza Villarroel)',
        '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA (Parque Laikakota)',
        '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA (Parque Laikakota)',
    ];
}

echo "📊 Total capas detectadas: " . count($capas) . "\n";
foreach ($capas as $uuid => $nombre) {
    echo "   📌 $nombre [$uuid]\n";
}
echo "\n";

// ============================================================
// 2. DESCARGAR CADA CAPA
// ============================================================
echo "⬇️  Descargando capas...\n";

$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
];

function descargarCapa(string $uuid, string $nombre, string $proxy, string $cookie, array $uas): ?array {
    $mapId = UMAP_MAP_ID;
    $urls = [
        // ✅ PRIORIDAD 1: Worker + API oficial
        "https://umap.openstreetmap.fr/api/0.1/map/$mapId/layer/$uuid/data/",
        // ✅ PRIORIDAD 2: Worker + datalayer directo
        "https://umap.openstreetmap.fr/es/datalayer/$mapId/$uuid/",
        // ✅ PRIORIDAD 3: Worker + formato GeoJSON
        "https://umap.openstreetmap.fr/es/map/_/$mapId?data=$uuid&format=geojson",
        // ✅ FALLBACK: Directo sin Worker
        "https://umap.openstreetmap.fr/api/0.1/map/$mapId/layer/$uuid/data/",
    ];
    
    foreach ($urls as $url) {
        // Usar Worker para todas las URLs
        $target = $proxy . urlencode($url);
        
        for ($i = 0; $i < 3; $i++) {
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_ENCODING => 'gzip, deflate, br',
                CURLOPT_USERAGENT => $uas[array_rand($uas)],
                CURLOPT_HTTPHEADER => array_filter([
                    'Accept: application/geo+json, application/json, text/plain, */*',
                    'Accept-Language: es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Referer: https://umap.openstreetmap.fr/es/map/' . MAP_SLUG . '_' . $mapId,
                    'Origin: https://umap.openstreetmap.fr',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    $cookie !== '' ? 'Cookie: ' . $cookie : '',
                ]),
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $size = strlen($resp);
            curl_close($ch);
            
            if ($code === 200 && $size > 200) {
                $json = json_decode($resp, true);
                if (is_array($json) && isset($json['type']) && $json['type'] === 'FeatureCollection') {
                    $features = isset($json['features']) ? count($json['features']) : 0;
                    if ($features > 0) {
                        return [
                            'data' => $resp,
                            'features' => $features,
                            'size' => $size,
                            'url' => $url,
                        ];
                    }
                }
            }
            sleep(1);
        }
    }
    return null;
}

$descargas = [];
$OK = 0;
$FAIL = 0;

foreach ($capas as $uuid => $nombre) {
    echo "   ⬇️  $nombre... ";
    $result = descargarCapa($uuid, $nombre, UMAP_WORKER_URL, $COOKIE, $USER_AGENTS);
    
    if ($result) {
        $filename = CACHE_DIR . "/$uuid.json";
        file_put_contents($filename, $result['data']);
        $descargas[$uuid] = [
            'nombre' => $nombre,
            'features' => $result['features'],
            'size' => $result['size'],
            'file' => $filename,
        ];
        echo "✅ {$result['features']} features, " . round($result['size']/1024,1) . "KB\n";
        $OK++;
    } else {
        echo "❌ FALLÓ\n";
        $FAIL++;
    }
    sleep(1);
}

echo "\n📊 Descargas: OK=$OK / FALLOS=$FAIL\n\n";

if ($OK === 0) {
    die("❌ Ninguna capa descargada. Verifica el Worker y la conexión.\n");
}

// ============================================================
// 3. SINCRONIZAR CON AIVEN
// ============================================================
echo "🔄 Sincronizando con Aiven...\n";

// Limpiar tablas
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("DELETE FROM ruta_parada");
$pdo->exec("DELETE FROM ruta_lugar");
$pdo->exec("DELETE FROM lugar_turistico");
$pdo->exec("DELETE FROM ruta");
$pdo->exec("DELETE FROM parada");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

$stats = [
    'rutas' => 0,
    'lugares' => 0,
    'paradas' => 0,
    'ruta_parada' => 0,
    'ruta_lugar' => 0,
];

// Preparar consultas
$stmtLugar = $pdo->prepare("
    INSERT INTO lugar_turistico (nombre, latitud, longitud, categoria, grupo_umap, uuid_capa, activo)
    VALUES (:nombre, :latitud, :longitud, 'Atracción turística', :grupo, :uuid, 1)
    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1
");

$stmtRuta = $pdo->prepare("
    INSERT INTO ruta (nombre, tipo, color_hex, sentido, uuid_capa, activo)
    VALUES (:nombre, 'minibus', :color, :sentido, :uuid, 1)
");

$stmtParada = $pdo->prepare("
    INSERT INTO parada (nombre, latitud, longitud, uuid_capa, activo)
    VALUES (:nombre, :latitud, :longitud, :uuid, 1)
");

$stmtRutaParada = $pdo->prepare("
    INSERT INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin)
    VALUES (:id_ruta, :id_parada, :orden, :es_inicio, :es_fin)
");

$stmtRutaLugar = $pdo->prepare("
    INSERT INTO ruta_lugar (id_ruta, id_lugar, orden)
    VALUES (:id_ruta, :id_lugar, 1)
");

foreach ($descargas as $uuid => $info) {
    $geojson = json_decode(file_get_contents($info['file']), true);
    if (!$geojson || !isset($geojson['features'])) continue;
    
    $nombreCapa = $info['nombre'];
    $sentido = (stripos($nombreCapa, 'vuelta') !== false) ? 'VUELTA' : 'IDA';
    $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
    
    // Extraer nombre del lugar (sin prefijo "Minibus XXX - ")
    $nombreLugar = preg_replace('/^Minibus\s*\d+\s*[-–]\s*/i', '', $nombreCapa);
    $nombreLugar = preg_replace('/\s*\([^)]*\)\s*/', '', $nombreLugar);
    $nombreLugar = trim($nombreLugar);
    
    $idRuta = null;
    $idLugar = null;
    $coordsRuta = [];
    
    foreach ($geojson['features'] as $feature) {
        $type = $feature['geometry']['type'] ?? '';
        $coords = $feature['geometry']['coordinates'] ?? [];
        $props = $feature['properties'] ?? [];
        
        if ($type === 'Point' && count($coords) >= 2) {
            $lat = (float)$coords[1];
            $lng = (float)$coords[0];
            if ($lat != 0 && $lng != 0) {
                $stmtLugar->execute([
                    ':nombre' => $nombreLugar,
                    ':latitud' => $lat,
                    ':longitud' => $lng,
                    ':grupo' => $nombreLugar,
                    ':uuid' => $uuid,
                ]);
                $idLugar = $pdo->lastInsertId();
                $stats['lugares']++;
            }
        }
        
        if ($type === 'LineString' && count($coords) >= 2) {
            $coordsRuta = $coords;
            $nombreRuta = $props['name'] ?? $nombreCapa;
            if ($sentido !== 'NORMAL' && !str_contains($nombreRuta, $sentido)) {
                $nombreRuta = "$nombreRuta ($sentido)";
            }
            $stmtRuta->execute([
                ':nombre' => $nombreRuta,
                ':color' => $props['color'] ?? $color,
                ':sentido' => $sentido,
                ':uuid' => $uuid,
            ]);
            $idRuta = $pdo->lastInsertId();
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
                ':uuid' => $uuid,
            ]);
            $idParada = $pdo->lastInsertId();
            $stats['paradas']++;
            
            if ($idParada) {
                $stmtRutaParada->execute([
                    ':id_ruta' => $idRuta,
                    ':id_parada' => $idParada,
                    ':orden' => $orden,
                    ':es_inicio' => ($orden === 1) ? 1 : 0,
                    ':es_fin' => ($orden === $total) ? 1 : 0,
                ]);
                $stats['ruta_parada']++;
            }
        }
        
        if ($idLugar) {
            $stmtRutaLugar->execute([
                ':id_ruta' => $idRuta,
                ':id_lugar' => $idLugar,
            ]);
            $stats['ruta_lugar']++;
        }
    }
}

// ============================================================
// 4. RESUMEN FINAL
// ============================================================
echo "\n═══════════════════════════════════════════════\n";
echo "📊 RESUMEN FINAL:\n";
echo "   🗺️  Rutas: " . $stats['rutas'] . "\n";
echo "   📍  Lugares: " . $stats['lugares'] . "\n";
echo "   🅿️  Paradas: " . $stats['paradas'] . "\n";
echo "   🔗  ruta-lugar: " . $stats['ruta_lugar'] . "\n";
echo "   🔗  ruta-parada: " . $stats['ruta_parada'] . "\n";
echo "\n✅ Sincronización completada!\n";