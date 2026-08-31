<?php
/**
 * sincronizar.php - Sincronización 100% AUTOMÁTICA de uMap → MySQL Aiven
 * 
 * @package TurismoLaPaz
 * @version 14.0-worker-json-fix
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Trigger');
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
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

define('UMAP_MAP_ID', 1451289);
define('UMAP_TIMEOUT', 40);
define('CACHE_DIR', dirname(__DIR__) . '/data/umap_cache');
define('CACHE_MAX_AGE_SECONDS', 60 * 60);

// 🔥 WORKER DE CLOUDFLARE
define('UMAP_PROXY_WORKER', 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/?url=');

$proxyEnv = trim(getenv('UMAP_PROXY_URL') ?: '');
define('UMAP_PROXY_URLS', $proxyEnv === '' ? [UMAP_PROXY_WORKER] : array_values(array_filter(array_map('trim', explode(',', $proxyEnv)))));

define('GITHUB_RAW_BASE', rtrim(getenv('GITHUB_RAW_BASE') ?: '', '/'));
define('UMAP_TOKEN', trim(getenv('UMAP_TOKEN') ?: ''));
define('GITHUB_REPO', trim(getenv('GITHUB_REPO') ?: ''));
define('GITHUB_TOKEN', trim(getenv('GITHUB_TOKEN') ?: ''));
define('GITHUB_WAIT_SECONDS', (int)(getenv('GITHUB_WAIT_SECONDS') ?: 90));

$ALLOW_TRIGGER_GHA = !empty($_GET['force_gha']) || !empty($_GET['refresh']);

// UUIDs de fallback (solo si uMap no responde)
$FALLBACK_UUIDS = [
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA',
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA',
    '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA',
];

if (empty($DB_PASS)) {
    echo json_encode([
        'success' => false,
        'error'   => '❌ Variable PDO_PASSWORD no configurada',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$stats = [
    'total_capas'      => 0,
    'capas_ok'         => 0,
    'capas_fallback'   => 0,
    'metodo_usado'     => [],
    'lugares_insert'   => 0,
    'lugares_update'   => 0,
    'rutas_insert'     => 0,
    'rutas_update'     => 0,
    'paradas_insert'   => 0,
    'paradas_skip'     => 0,
    'ruta_lugar_ok'    => 0,
    'ruta_parada_ok'   => 0,
    'warnings'         => [],
    'debug'            => [],
    'schema_migration' => null,
    'gha_triggered'    => false,
    'cache_status'     => [],
    'capas_detectadas' => [],
    'transaction_active' => false,
    'metodo_deteccion' => 'pendiente',
    'json_debug'       => [],
];

// =========================================================================
// CONEXIÓN A BASE DE DATOS (Aiven MySQL con SSL)
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $sslCa = '';
    $sslCaEnv = getenv('PDO_SSL_CA') ?: '';
    if (!empty($sslCaEnv)) {
        if (strpos($sslCaEnv, '/') === 0 || strpos($sslCaEnv, ':\\') === 1) {
            $sslCa = $sslCaEnv;
        } else {
            $sslCa = dirname(__DIR__) . '/' . $sslCaEnv;
        }
    }
    if (!file_exists($sslCa)) {
        $def = dirname(__DIR__) . '/config/ca.pem';
        if (file_exists($def)) $sslCa = $def;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (!empty($sslCa) && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $pdo->exec("SET SESSION time_zone = '-04:00'");
    
    $stats['debug'][] = "✅ Conexión BD exitosa → $DB_HOST:$DB_PORT/$DB_NAME";
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => '❌ BD Connection failed: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// MIGRACIÓN AUTOMÁTICA DE ESQUEMA
// =========================================================================
function migrarEsquema(PDO $pdo, array &$stats): void {
    $mig = [];
    try {
        // Verificar/Crear tabla sincronizacion_log
        $tblExists = $pdo->query("SHOW TABLES LIKE 'sincronizacion_log'")->rowCount() > 0;
        
        if (!$tblExists) {
            $pdo->exec("CREATE TABLE sincronizacion_log (
                id_log INT AUTO_INCREMENT PRIMARY KEY,
                origen VARCHAR(100) NOT NULL,
                status ENUM('OK','PARCIAL','ERROR') NOT NULL DEFAULT 'OK',
                metodo_descarga VARCHAR(80) NULL,
                metodo_deteccion VARCHAR(80) NULL,
                total_leidos INT DEFAULT 0,
                lugares_insert INT DEFAULT 0,
                lugares_update INT DEFAULT 0,
                rutas_insert INT DEFAULT 0,
                rutas_update INT DEFAULT 0,
                paradas_insert INT DEFAULT 0,
                ruta_lugar_ok INT DEFAULT 0,
                ruta_parada_ok INT DEFAULT 0,
                error_msg TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_log_fecha (created_at DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci");
            $mig[] = "CREATE sincronizacion_log";
        } else {
            foreach (['metodo_descarga', 'metodo_deteccion'] as $col) {
                try {
                    $check = $pdo->query("SHOW COLUMNS FROM sincronizacion_log LIKE '$col'");
                    if ($check->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE sincronizacion_log ADD COLUMN $col VARCHAR(80) NULL");
                        $mig[] = "sincronizacion_log.$col";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e;
                }
            }
        }

        // Columnas en lugar_turistico
        $cols = $pdo->query("SHOW COLUMNS FROM lugar_turistico")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'grupo_umap'   => "ADD COLUMN grupo_umap VARCHAR(100) NULL",
            'id_umap'      => "ADD COLUMN id_umap VARCHAR(100) NULL",
            'icono_umap'   => "ADD COLUMN icono_umap VARCHAR(50) NULL",
            'color_hex'    => "ADD COLUMN color_hex CHAR(7) NULL",
            'uuid_capa'    => "ADD COLUMN uuid_capa VARCHAR(100) NULL",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE lugar_turistico $sqlAdd");
                $mig[] = "lugar_turistico.$col";
            }
        }

        // Columnas en ruta
        $cols = $pdo->query("SHOW COLUMNS FROM ruta")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'sentido'        => "ADD COLUMN sentido ENUM('IDA','VUELTA','NORMAL') NOT NULL DEFAULT 'NORMAL'",
            'id_umap'        => "ADD COLUMN id_umap VARCHAR(100) NULL",
            'id_grupo_umap'  => "ADD COLUMN id_grupo_umap VARCHAR(100) NULL",
            'coords_geojson' => "ADD COLUMN coords_geojson LONGTEXT NULL",
            'uuid_capa'      => "ADD COLUMN uuid_capa VARCHAR(100) NULL",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE ruta $sqlAdd");
                $mig[] = "ruta.$col";
            }
        }

        // Columnas en parada
        $cols = $pdo->query("SHOW COLUMNS FROM parada")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'id_umap' => "ADD COLUMN id_umap VARCHAR(100) NULL",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE parada $sqlAdd");
                $mig[] = "parada.$col";
            }
        }

        $stats['schema_migration'] = empty($mig) ? 'sin cambios' : implode(', ', $mig);
        $stats['debug'][] = "🔧 Esquema BD: " . $stats['schema_migration'];
    } catch (Throwable $e) {
        $stats['schema_migration'] = 'ERROR: ' . $e->getMessage();
        $stats['warnings'][] = '⚠️ Migración: ' . $e->getMessage();
    }
}
migrarEsquema($pdo, $stats);

// =========================================================================
// FUNCIONES DE DESCARGA
// =========================================================================

function buildUrlVariants(string $capaId): array {
    $mid = UMAP_MAP_ID;
    return [
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$capaId/data/",
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$capaId/data?format=geojson",
        "https://umap.openstreetmap.fr/es/datalayer/$mid/$capaId/?format=geojson",
        "https://umap.openstreetmap.fr/en/datalayer/$mid/$capaId/?format=geojson",
        "https://umap.openstreetmap.fr/es/map/_/$mid?data=$capaId&format=geojson",
        "https://umap.openstreetmap.fr/en/map/_/$mid?data=$capaId&format=geojson",
        "https://u.osmfr.org/m/$mid/datalayer/$capaId/?format=geojson",
        "https://umap.openstreetmap.de/de/datalayer/$mid/$capaId/?format=geojson",
    ];
}

function buildCurlHeaders(bool $includeCookie = true): array {
    static $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
    ];
    $h = [
        'Accept: application/geo+json, application/json, text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding: gzip, deflate, br',
        'Cache-Control: max-age=0',
        'Pragma: no-cache',
        'Sec-Ch-Ua: "Not)A;Brand";v="99", "Google Chrome";v="128", "Chromium";v="128"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1',
        'DNT: 1',
        'Referer: https://umap.openstreetmap.fr/',
    ];
    if ($includeCookie && !empty(UMAP_TOKEN)) {
        $h[] = 'Cookie: ' . UMAP_TOKEN;
    }
    return ['headers' => $h, 'ua' => $userAgents[random_int(0, count($userAgents) - 1)]];
}

function curlGetRaw(string $url, array $extraHeaders = [], int $timeout = UMAP_TIMEOUT, ?string $forcedUA = null): ?array {
    if (!function_exists('curl_init')) return null;
    $build = buildCurlHeaders();
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => $forcedUA ?: $build['ua'],
        CURLOPT_HTTPHEADER     => array_merge($build['headers'], $extraHeaders),
        CURLOPT_VERBOSE        => false,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    $size = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    curl_close($ch);
    if ($resp === false || empty($resp)) {
        return null;
    }
    return ['body' => $resp, 'code' => $code, 'content_type' => $ct, 'error' => $err, 'size' => $size];
}

function parseJsonIfValid(?string $raw): ?array {
    if ($raw === null || trim($raw) === '') return null;
    $data = json_decode($raw, true);
    if (!is_array($data)) return null;
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}

function isGeoJsonValid(?array $data): bool {
    if ($data === null) return false;
    if (!isset($data['type']) || $data['type'] !== 'FeatureCollection') return false;
    if (!isset($data['features']) || !is_array($data['features'])) return false;
    return true;
}

// ============================================================
// FUNCIÓN GENÉRICA PARA DESCARGAR CON WORKER (CORREGIDA)
// ============================================================
function pedirProxy(string $targetUrl, array &$stats, int $timeout = 30): ?array {
    $proxyUrl = UMAP_PROXY_WORKER . urlencode($targetUrl);
    
    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-AutoSync/14.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/geo+json, application/json, text/html, */*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
        ],
    ]);
    
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    $size = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    curl_close($ch);
    
    $stats['debug'][] = "  ↳ Worker: HTTP $code, " . round($size/1024, 1) . "KB, Content-Type: $ct";
    
    // ✅ CORRECCIÓN: Verificar que la respuesta sea JSON válido
    if ($code === 200 && $resp && strlen($resp) > 100) {
        // Intentar decodificar JSON
        $decoded = json_decode($resp, true);
        if ($decoded !== null && is_array($decoded)) {
            return $decoded;
        }
        // Si no es JSON, devolver null para que se use el siguiente método
        $stats['debug'][] = "  ↳ ⚠️ La respuesta no es JSON válido, usando siguiente método";
        return null;
    }
    
    return null;
}

// ============================================================
// FUNCIÓN HTTP GET SIMPLE
// ============================================================
function httpGet(string $url, int $timeout = 15): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-AutoSync/14.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/html, */*',
            'Accept-Language: es-ES,es;q=0.9',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && $resp) {
        return $resp;
    }
    return null;
}

// ============================================================
// 🚀 DETECCIÓN DINÁMICA DE CAPAS (CORREGIDA)
// ============================================================
function detectarCapasAutomaticamente(array &$stats): array {
    global $FALLBACK_UUIDS;
    
    $stats['debug'][] = "🔍 Detectando capas automáticamente del mapa #" . UMAP_MAP_ID;
    
    $capasEncontradas = [];
    $metodoUsado = 'ninguno';
    $debugEstructural = [];
    
    // ============================================================
    // MÉTODO 1: JSON del mapa completo
    // ============================================================
    $urlJson = "https://umap.openstreetmap.fr/es/map/rutaslapaz_" . UMAP_MAP_ID . "?format=json";
    $stats['debug'][] = "  ↳ Intentando (M1 - JSON): $urlJson";
    
    $content = pedirProxy($urlJson, $stats);
    $debugEstructural['M1'] = ['url' => $urlJson, 'status' => 'pendiente'];
    
    if ($content && is_array($content)) {
        $debugEstructural['M1']['status'] = 'success';
        $debugEstructural['M1']['keys'] = array_keys($content);
        $debugEstructural['M1']['type'] = 'array';
        
        $stats['debug'][] = "  📦 JSON válido: YES";
        $stats['debug'][] = "  📦 Keys raíz: " . implode(', ', array_keys($content));
        
        // ============================================================
        // BUSCAR CAPAS EN TODAS LAS ESTRUCTURAS POSIBLES
        // ============================================================
        
        // Estructura 1: properties.datalayers (la más común en uMap)
        if (isset($content['properties']['datalayers']) && is_array($content['properties']['datalayers'])) {
            $stats['debug'][] = "  📦 Buscando en properties.datalayers...";
            foreach ($content['properties']['datalayers'] as $layer) {
                if (isset($layer['uuid']) && isset($layer['name'])) {
                    $capasEncontradas[$layer['uuid']] = $layer['name'];
                    $stats['debug'][] = "    ✅ Capa: " . $layer['name'] . " [" . $layer['uuid'] . "]";
                } elseif (isset($layer['id']) && isset($layer['name'])) {
                    $capasEncontradas[$layer['id']] = $layer['name'];
                    $stats['debug'][] = "    ✅ Capa: " . $layer['name'] . " [" . $layer['id'] . "]";
                }
            }
        }
        
        // Estructura 2: datalayers directo
        if (empty($capasEncontradas) && isset($content['datalayers']) && is_array($content['datalayers'])) {
            $stats['debug'][] = "  📦 Buscando en datalayers (raíz)...";
            foreach ($content['datalayers'] as $layer) {
                $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
                $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
                if ($uuid) {
                    $capasEncontradas[$uuid] = $name;
                    $stats['debug'][] = "    ✅ Capa: $name [$uuid]";
                }
            }
        }
        
        // Estructura 3: layers (nombre alternativo)
        if (empty($capasEncontradas) && isset($content['layers']) && is_array($content['layers'])) {
            $stats['debug'][] = "  📦 Buscando en layers...";
            foreach ($content['layers'] as $layer) {
                $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
                $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
                if ($uuid) {
                    $capasEncontradas[$uuid] = $name;
                    $stats['debug'][] = "    ✅ Capa: $name [$uuid]";
                }
            }
        }
        
        // Estructura 4: Buscar recursivamente
        if (empty($capasEncontradas)) {
            $stats['debug'][] = "  📦 Buscando recursivamente en toda la estructura...";
            $recursiveResult = buscarCapasRecursivamente($content);
            if (!empty($recursiveResult)) {
                $capasEncontradas = $recursiveResult;
                foreach ($capasEncontradas as $uuid => $name) {
                    $stats['debug'][] = "    ✅ Capa (recursiva): $name [$uuid]";
                }
            }
        }
        
        // Si encontramos capas, registrar método
        if (!empty($capasEncontradas)) {
            $metodoUsado = 'M1_json';
            $debugEstructural['M1']['capas_encontradas'] = count($capasEncontradas);
            $stats['debug'][] = "  🤖 Detección dinámica exitosa desde JSON: " . count($capasEncontradas) . " capas encontradas.";
            $stats['metodo_deteccion'] = $metodoUsado;
            $stats['capas_detectadas'] = $capasEncontradas;
            $stats['json_debug'] = $debugEstructural;
            return $capasEncontradas;
        } else {
            $stats['debug'][] = "  📦 No se encontraron capas en la estructura JSON";
        }
    } else {
        $debugEstructural['M1']['status'] = 'failed';
        if ($content === null) {
            $debugEstructural['M1']['reason'] = 'No response (null) - posiblemente HTML en lugar de JSON';
        } elseif (!is_array($content)) {
            $debugEstructural['M1']['reason'] = 'Not an array';
            $debugEstructural['M1']['type'] = gettype($content);
        }
        $stats['debug'][] = "  📦 JSON válido: NO";
    }
    
    // ============================================================
    // MÉTODO 2: HTML scraping
    // ============================================================
    $urlHtml = "https://umap.openstreetmap.fr/es/map/rutaslapaz_" . UMAP_MAP_ID . "/";
    $stats['debug'][] = "  ↳ Intentando (M2 - HTML): $urlHtml";
    $html = httpGet($urlHtml);
    
    if ($html) {
        if (preg_match_all('/\/datalayer\/([a-f0-9\-]+)\//i', $html, $matches)) {
            foreach ($matches[1] as $uuid) {
                if (!isset($capasEncontradas[$uuid])) {
                    $capasEncontradas[$uuid] = 'Capa descubierta';
                    $stats['debug'][] = "    ✅ Capa (HTML): $uuid";
                }
            }
            if (!empty($capasEncontradas)) {
                $metodoUsado = 'M2_html';
                $stats['debug'][] = "  🌐 Detección dinámica exitosa desde HTML: " . count($capasEncontradas) . " capas encontradas.";
                $stats['metodo_deteccion'] = $metodoUsado;
                $stats['capas_detectadas'] = $capasEncontradas;
                $stats['json_debug'] = $debugEstructural;
                return $capasEncontradas;
            }
        }
    }
    
    // ============================================================
    // MÉTODO 3: API datalayers
    // ============================================================
    $datalayersUrl = "https://umap.openstreetmap.fr/api/0.1/map/" . UMAP_MAP_ID . "/datalayers/";
    $stats['debug'][] = "  ↳ Intentando (M3 - API): $datalayersUrl";
    $content = pedirProxy($datalayersUrl, $stats);
    
    if ($content && isset($content['datalayers']) && is_array($content['datalayers'])) {
        foreach ($content['datalayers'] as $layer) {
            $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
            $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
            if ($uuid) {
                $capasEncontradas[$uuid] = $name;
                $stats['debug'][] = "    ✅ Capa (API): $name [$uuid]";
            }
        }
        if (!empty($capasEncontradas)) {
            $metodoUsado = 'M3_api';
            $stats['debug'][] = "  🤖 Detección dinámica exitosa desde API: " . count($capasEncontradas) . " capas encontradas.";
            $stats['metodo_deteccion'] = $metodoUsado;
            $stats['capas_detectadas'] = $capasEncontradas;
            $stats['json_debug'] = $debugEstructural;
            return $capasEncontradas;
        }
    }
    
    // ============================================================
    // MÉTODO 4: FALLBACK - UUIDs predefinidos
    // ============================================================
    $stats['warnings'][] = "⚠️ Falló la autodetección dinámica. Usando lista de resguardo.";
    $metodoUsado = 'fallback_predefinido';
    $stats['metodo_deteccion'] = $metodoUsado;
    $stats['capas_detectadas'] = $FALLBACK_UUIDS;
    $stats['json_debug'] = $debugEstructural;
    
    foreach ($FALLBACK_UUIDS as $uuid => $nombre) {
        $stats['debug'][] = "  ✅ Capa de resguardo: $nombre [$uuid]";
    }
    
    return $FALLBACK_UUIDS;
}

/**
 * Busca capas recursivamente en cualquier estructura de array
 */
function buscarCapasRecursivamente(array $data, string $path = 'root'): array {
    $result = [];
    
    // Buscar en arrays que tienen uuid y name
    if (isset($data['uuid']) && isset($data['name']) && is_string($data['uuid'])) {
        $result[$data['uuid']] = $data['name'];
        return $result;
    }
    if (isset($data['id']) && isset($data['name']) && is_string($data['id'])) {
        $result[$data['id']] = $data['name'];
        return $result;
    }
    
    // Buscar en listas de objetos
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key) && isset($value['uuid']) && isset($value['name'])) {
                $result[$value['uuid']] = $value['name'];
            } elseif (is_numeric($key) && isset($value['id']) && isset($value['name'])) {
                $result[$value['id']] = $value['name'];
            } else {
                $sub = buscarCapasRecursivamente($value, $path . '.' . $key);
                $result = array_merge($result, $sub);
            }
        }
    }
    
    return $result;
}

// ============================================================
// FUNCIÓN PARA DESCARGAR CAPA CON MULTI-NIVEL (CORREGIDA)
// ============================================================
function descargarCapaMultiNivel(string $capaId, array &$stats): ?array {
    $stats['debug'][] = "  📥 Descargando capa: $capaId";
    
    // ============================================================
    // NIVEL 1: Worker de Cloudflare - Múltiples URLs
    // ============================================================
    $urlsParaProbar = [
        "https://umap.openstreetmap.fr/api/0.1/map/" . UMAP_MAP_ID . "/layer/$capaId/data/",
        "https://umap.openstreetmap.fr/es/datalayer/" . UMAP_MAP_ID . "/$capaId/?format=geojson",
        "https://umap.openstreetmap.fr/en/datalayer/" . UMAP_MAP_ID . "/$capaId/?format=geojson",
        "https://umap.openstreetmap.fr/es/map/_/" . UMAP_MAP_ID . "?data=$capaId&format=geojson",
        "https://umap.openstreetmap.fr/en/map/_/" . UMAP_MAP_ID . "?data=$capaId&format=geojson",
    ];
    
    foreach ($urlsParaProbar as $targetUrl) {
        $stats['debug'][] = "    ↳ Worker probando: " . parse_url($targetUrl, PHP_URL_PATH);
        $data = pedirProxy($targetUrl, $stats);
        if (isGeoJsonValid($data)) {
            $stats['metodo_usado'][$capaId] = 'L1-worker';
            return $data;
        }
    }
    
    // ============================================================
    // NIVEL 2: Worker + Cookie (si está configurada)
    // ============================================================
    if (!empty(UMAP_TOKEN)) {
        foreach ($urlsParaProbar as $targetUrl) {
            $proxyUrl = UMAP_PROXY_WORKER . urlencode($targetUrl);
            $ch = curl_init($proxyUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'TurismoLaPaz-AutoSync/14.0',
                CURLOPT_HTTPHEADER => [
                    'X-Proxy-Forward-Cookie: ' . UMAP_TOKEN,
                    'Accept: application/geo+json, application/json',
                ],
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && $resp) {
                $data = json_decode($resp, true);
                if (isGeoJsonValid($data)) {
                    $stats['metodo_usado'][$capaId] = 'L2-worker+cookie';
                    return $data;
                }
            }
        }
    }
    
    // ============================================================
    // NIVEL 3: Descarga DIRECTA (FALLBACK PRINCIPAL)
    // ============================================================
    foreach (buildUrlVariants($capaId) as $i => $url) {
        $stats['debug'][] = "    ↳ Directo probando variante $i";
        $r = curlGetRaw($url);
        if ($r !== null && $r['code'] === 200) {
            $data = parseJsonIfValid($r['body']);
            if (isGeoJsonValid($data)) {
                $stats['metodo_usado'][$capaId] = 'L3-directo';
                return $data;
            }
        }
    }
    
    // ============================================================
    // NIVEL 4: Caché LOCAL
    // ============================================================
    $file = CACHE_DIR . '/' . $capaId . '.json';
    if (is_file($file) && is_readable($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $data = parseJsonIfValid($raw);
            if (isGeoJsonValid($data)) {
                $stats['metodo_usado'][$capaId] = 'L4-cache_local';
                return $data;
            }
        }
    }
    
    // ============================================================
    // NIVEL 5: GitHub Raw
    // ============================================================
    if (!empty(GITHUB_RAW_BASE)) {
        $url = GITHUB_RAW_BASE . "/data/umap_cache/$capaId.json";
        $r = curlGetRaw($url, ['Cache-Control: no-cache'], 20);
        if ($r !== null && $r['code'] === 200) {
            $data = parseJsonIfValid($r['body']);
            if (isGeoJsonValid($data)) {
                $stats['metodo_usado'][$capaId] = 'L5-github_raw';
                return $data;
            }
        }
    }
    
    $stats['debug'][] = "  ❌ TODOS los métodos fallaron para capa $capaId";
    return null;
}

// =========================================================================
// FUNCIONES AUXILIARES DE NEGOCIO
// =========================================================================
function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    $ida    = str_contains($n, 'ida') || str_contains($n, 'ida ');
    $vuelta = str_contains($n, 'vuelta') || str_contains($n, 'vuelta ') || str_contains($n, 'vta');
    if ($ida && !$vuelta) return 'IDA';
    if ($vuelta && !$ida) return 'VUELTA';
    return 'NORMAL';
}

function detectar_tipo_ruta(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'minibus') || str_contains($n, 'minibús')) return 'minibus';
    if (str_contains($n, 'micro'))   return 'micro';
    if (str_contains($n, 'teleferico') || str_contains($n, 'teleférico')) return 'teleferico';
    return 'minibus';
}

function extraer_grupo_parentesis(string $nombre): array {
    preg_match_all('/\(([^)]+)\)/u', $nombre, $m);
    $result = [];
    foreach ($m[1] ?? [] as $g) {
        $g = trim($g);
        if ($g !== '') $result[] = $g;
    }
    return $result;
}

function limpiar_nombre_lugar(string $nombre): string {
    $nombre = preg_replace('/^(Minibus|minibus|Micro|micro|Teleferico|teleferico)\s*\d+\s*[-–]\s*/i', '', $nombre);
    $nombre = preg_replace('/\s*[-–]\s*(IDA|VUELTA|ID|VTA|Vta|Vuelta)\s*$/i', '', $nombre);
    $nombre = preg_replace('/\s*\([^)]*\)\s*/', '', $nombre);
    return trim($nombre);
}

// =========================================================================
// FUNCIÓN PARA EJECUTAR TRANSACCIÓN DE FORMA SEGURA
// =========================================================================
function ejecutarTransaccionSegura(PDO $pdo, array &$stats, array $capasConDatos): bool {
    $idLugaresPorGrupo = [];
    
    try {
        // ✅ 1. INICIAR TRANSACCIÓN
        $pdo->beginTransaction();
        $stats['transaction_active'] = true;
        $stats['debug'][] = "🔓 Transacción iniciada";
        
        // ✅ 2. DESACTIVAR FOREIGN KEY CHECKS PARA LIMPIEZA
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // ✅ 3. LIMPIAR TABLAS CON DELETE (NO TRUNCATE)
        $pdo->exec("DELETE FROM ruta_parada");
        $stats['debug'][] = "  🗑️ ruta_parada limpiada";
        
        $pdo->exec("DELETE FROM ruta_lugar");
        $stats['debug'][] = "  🗑️ ruta_lugar limpiada";
        
        $pdo->exec("DELETE FROM ruta");
        $stats['debug'][] = "  🗑️ ruta limpiada";
        
        $pdo->exec("DELETE FROM lugar_turistico");
        $stats['debug'][] = "  🗑️ lugar_turistico limpiada";
        
        $pdo->exec("DELETE FROM parada");
        $stats['debug'][] = "  🗑️ parada limpiada";
        
        // ✅ 4. REACTIVAR FOREIGN KEY CHECKS
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $stats['debug'][] = "🧹 Tablas limpiadas sin TRUNCATE";
        
        // ✅ 5. PREPARAR STATEMENTS CON UPSERT
        $stmtLugarUpsert = $pdo->prepare("
            INSERT INTO lugar_turistico (
                nombre, descripcion, latitud, longitud, categoria, 
                grupo_umap, id_umap, icono_umap, color_hex, uuid_capa, activo
            ) VALUES (
                :nombre, :descripcion, :latitud, :longitud, :categoria,
                :grupo_umap, :id_umap, :icono_umap, :color_hex, :uuid_capa, 1
            ) ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                descripcion = VALUES(descripcion),
                categoria = VALUES(categoria),
                grupo_umap = VALUES(grupo_umap),
                icono_umap = COALESCE(VALUES(icono_umap), icono_umap),
                color_hex = COALESCE(VALUES(color_hex), color_hex),
                uuid_capa = VALUES(uuid_capa),
                activo = 1,
                updated_at = NOW()
        ");
        
        $stmtRutaUpsert = $pdo->prepare("
            INSERT INTO ruta (
                nombre, descripcion, tipo, color_hex, sentido, 
                id_grupo_umap, coords_geojson, uuid_capa, activo
            ) VALUES (
                :nombre, :descripcion, :tipo, :color_hex, :sentido,
                :id_grupo_umap, :coords_geojson, :uuid_capa, 1
            ) ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                descripcion = VALUES(descripcion),
                tipo = VALUES(tipo),
                color_hex = VALUES(color_hex),
                sentido = VALUES(sentido),
                id_grupo_umap = VALUES(id_grupo_umap),
                coords_geojson = VALUES(coords_geojson),
                activo = 1,
                updated_at = NOW()
        ");
        
        $stmtParadaUpsert = $pdo->prepare("
            INSERT INTO parada (nombre, latitud, longitud, id_umap, activo)
            VALUES (:nombre, :latitud, :longitud, :id_umap, 1)
            ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                id_umap = VALUES(id_umap),
                activo = 1,
                updated_at = NOW()
        ");
        
        // ✅ 6. PROCESAR CADA CAPA
        foreach ($capasConDatos as $idx => $info) {
            $uuid = $info['uuid'];
            $nombreCapa = $info['nombre'] ?? $uuid;
            $geojson = $info['geojson'];
            $features = $geojson['features'] ?? [];
            $metodo = $stats['metodo_usado'][$uuid] ?? '?';
            
            $numCapa = $idx + 1;
            $totalCapas = count($capasConDatos);
            $stats['debug'][] = "🔨 Procesando capa $numCapa/$totalCapas: $nombreCapa → " . count($features) . " features";
            
            $grupoCapa = limpiar_nombre_lugar($nombreCapa);
            if (empty($grupoCapa)) $grupoCapa = $nombreCapa;
            
            $sentido = detectar_sentido($nombreCapa);
            $colorRuta = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
            $tipoRuta = detectar_tipo_ruta($nombreCapa);
            $idRutaActual = null;
            
            foreach ($features as $feature) {
                $gtype = $feature['geometry']['type'] ?? '';
                $coords = $feature['geometry']['coordinates'] ?? [];
                $props  = $feature['properties'] ?? [];
                
                // ---- PUNTO (Lugar Turístico) ----
                if ($gtype === 'Point' && !empty($coords) && count($coords) >= 2) {
                    $lat = (float)($coords[1] ?? 0);
                    $lng = (float)($coords[0] ?? 0);
                    if ($lat === 0.0 || $lng === 0.0) continue;
                    
                    $nombrePunto = trim($props['name'] ?? '') ?: $grupoCapa;
                    $nombrePunto = limpiar_nombre_lugar($nombrePunto);
                    if (empty($nombrePunto)) $nombrePunto = $grupoCapa;
                    
                    $descripcion = $props['description'] ?? '';
                    $categoria = $props['categoria'] ?? $props['category'] ?? null;
                    
                    $iconoUmap = null;
                    $colorUmap = null;
                    if (!empty($props['_umap_options'])) {
                        $iconoUmap = $props['_umap_options']['iconUrl'] ?? $props['_umap_options']['icon'] ?? null;
                        $colorUmap = $props['_umap_options']['color'] ?? null;
                    }
                    if (!empty($props['icon'])) $iconoUmap = $iconoUmap ?? $props['icon'];
                    if (!empty($props['marker-color'])) $colorUmap = $colorUmap ?? $props['marker-color'];
                    if (!empty($props['stroke'])) $colorUmap = $colorUmap ?? $props['stroke'];
                    
                    $stmtLugarUpsert->execute([
                        ':nombre' => $nombrePunto,
                        ':descripcion' => $descripcion,
                        ':latitud' => $lat,
                        ':longitud' => $lng,
                        ':categoria' => $categoria,
                        ':grupo_umap' => $grupoCapa,
                        ':id_umap' => $uuid,
                        ':icono_umap' => $iconoUmap,
                        ':color_hex' => $colorUmap,
                        ':uuid_capa' => $uuid,
                    ]);
                    
                    $selId = $pdo->prepare("SELECT id_lugar FROM lugar_turistico 
                        WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                    $selId->execute([$lat, $lng]);
                    $idLugar = $selId->fetchColumn();
                    
                    if ($idLugar) {
                        $idLugaresPorGrupo[$grupoCapa] = $idLugar;
                        if ($stmtLugarUpsert->rowCount() > 0) {
                            $stats['lugares_insert']++;
                        } else {
                            $stats['lugares_update']++;
                        }
                    }
                }
                
                // ---- LÍNEA (Ruta) ----
                if ($gtype === 'LineString' && count($coords) >= 2) {
                    $color = $colorRuta;
                    if (!empty($props['_umap_options']['color'])) {
                        $color = $props['_umap_options']['color'];
                    } elseif (!empty($props['stroke'])) {
                        $color = $props['stroke'];
                    }
                    $coordsJson = json_encode($coords, JSON_UNESCAPED_UNICODE);
                    
                    $stmtRutaUpsert->execute([
                        ':nombre' => $nombreCapa,
                        ':descripcion' => $props['description'] ?? '',
                        ':tipo' => $tipoRuta,
                        ':color_hex' => $color,
                        ':sentido' => $sentido,
                        ':id_grupo_umap' => $grupoCapa,
                        ':coords_geojson' => $coordsJson,
                        ':uuid_capa' => $uuid,
                    ]);
                    
                    $selRuta = $pdo->prepare("SELECT id_ruta FROM ruta WHERE uuid_capa = ? LIMIT 1");
                    $selRuta->execute([$uuid]);
                    $idRutaActual = $selRuta->fetchColumn();
                    
                    if (!$idRutaActual) {
                        $idRutaActual = $pdo->lastInsertId();
                    }
                    
                    if ($idRutaActual) {
                        if ($stmtRutaUpsert->rowCount() > 0) {
                            $stats['rutas_insert']++;
                        } else {
                            $stats['rutas_update']++;
                        }
                    }
                    
                    // Generar paradas y ruta_parada
                    if ($idRutaActual) {
                        $orden = 1;
                        $totalParadas = count($coords);
                        foreach ($coords as $coord) {
                            $lat = (float)($coord[1] ?? 0);
                            $lng = (float)($coord[0] ?? 0);
                            if ($lat === 0.0 || $lng === 0.0) continue;
                            
                            $nomParada = 'Parada ' . $orden . ' - ' . $grupoCapa;
                            $stmtParadaUpsert->execute([
                                ':nombre' => $nomParada,
                                ':latitud' => $lat,
                                ':longitud' => $lng,
                                ':id_umap' => $uuid,
                            ]);
                            
                            $selParada = $pdo->prepare("SELECT id_parada FROM parada 
                                WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                            $selParada->execute([$lat, $lng]);
                            $idParada = $selParada->fetchColumn();
                            
                            if ($idParada) {
                                if ($stmtParadaUpsert->rowCount() > 0) {
                                    $stats['paradas_insert']++;
                                } else {
                                    $stats['paradas_skip']++;
                                }
                                
                                $esInicio = ($orden === 1) ? 1 : 0;
                                $esFin    = ($orden === $totalParadas) ? 1 : 0;
                                $insRP = $pdo->prepare("INSERT IGNORE INTO ruta_parada
                                    (id_ruta, id_parada, orden, es_inicio, es_fin) VALUES (?,?,?,?,?)");
                                $insRP->execute([$idRutaActual, $idParada, $orden, $esInicio, $esFin]);
                                $stats['ruta_parada_ok']++;
                            }
                            $orden++;
                        }
                    }
                }
            }
            
            // Asociar ruta ↔ lugar turístico
            if ($idRutaActual !== null) {
                $grupos = array_unique([$grupoCapa]);
                $parentesis = extraer_grupo_parentesis($nombreCapa);
                foreach ($parentesis as $g) {
                    if (trim($g) !== '') $grupos[] = trim($g);
                }
                
                foreach ($grupos as $g) {
                    $g = trim($g);
                    if ($g === '') continue;
                    
                    $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico
                        WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
                    $sel->execute(["%$g%", "%$g%"]);
                    $idLugar = $sel->fetchColumn();
                    
                    if (!$idLugar && isset($idLugaresPorGrupo[$g])) {
                        $idLugar = $idLugaresPorGrupo[$g];
                    }
                    
                    if ($idLugar) {
                        $insRL = $pdo->prepare("INSERT IGNORE INTO ruta_lugar
                            (id_ruta, id_lugar, orden) VALUES (?,?,?)");
                        $insRL->execute([$idRutaActual, $idLugar, 1]);
                        $stats['ruta_lugar_ok']++;
                    }
                }
            }
        }
        
        // ✅ 7. COMMIT SEGURO
        if ($pdo->inTransaction()) {
            $pdo->commit();
            $stats['debug'][] = "💾 Transacción COMMIT confirmada";
        } else {
            $stats['warnings'][] = "⚠️ No hay transacción activa al intentar COMMIT";
            return false;
        }
        
        $stats['transaction_active'] = false;
        return true;
        
    } catch (Throwable $e) {
        // ✅ 8. ROLLBACK SEGURO
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
                $stats['debug'][] = "🔙 Transacción ROLLBACK ejecutado";
            } catch (Throwable $rollbackError) {
                $stats['warnings'][] = '⚠️ Error en rollback: ' . $rollbackError->getMessage();
            }
        }
        
        $stats['transaction_active'] = false;
        throw $e;
    }
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================
try {
    $stats['debug'][] = "🚀 Iniciando sincronización v14.0-worker-json-fix → uMap#" . UMAP_MAP_ID;
    
    // ============================================================
    // PASO 1: DETECTAR CAPAS DINÁMICAMENTE
    // ============================================================
    $capasDetectadas = detectarCapasAutomaticamente($stats);
    $stats['total_capas'] = count($capasDetectadas);
    
    // Mostrar resumen de capas detectadas
    $stats['debug'][] = "📊 Total capas detectadas: " . $stats['total_capas'];
    foreach ($capasDetectadas as $uuid => $nombre) {
        $stats['debug'][] = "  ✅ Capa: $nombre [$uuid]";
    }
    
    if ($stats['total_capas'] === 0) {
        throw new RuntimeException("❌ No se detectaron capas en el mapa #" . UMAP_MAP_ID);
    }
    
    // ============================================================
    // PASO 2: PREPARAR CACHÉ LOCAL
    // ============================================================
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0755, true);
    }
    
    // ============================================================
    // PASO 3: DESCARGAR CADA CAPA
    // ============================================================
    $capasConDatos = [];
    $metodoGlobal = [];
    
    $totalCapas = count($capasDetectadas);
    $capaIndex = 0;
    
    foreach ($capasDetectadas as $uuid => $nombreCapa) {
        $capaIndex++;
        $stats['debug'][] = "📥 Procesando capa $capaIndex/$totalCapas: $nombreCapa [$uuid]";
        $geojson = descargarCapaMultiNivel($uuid, $stats);
        
        if (isGeoJsonValid($geojson)) {
            $capasConDatos[] = [
                'uuid' => $uuid,
                'nombre' => $nombreCapa,
                'geojson' => $geojson
            ];
            $m = $stats['metodo_usado'][$uuid] ?? 'desconocido';
            if (!in_array($m, $metodoGlobal, true)) $metodoGlobal[] = $m;
            $stats['debug'][] = "  ✅ Capa $capaIndex/$totalCapas descargada correctamente";
        } else {
            $stats['warnings'][] = "⚠️ No se pudo descargar capa: $nombreCapa [$uuid]";
            $stats['debug'][] = "  ❌ Capa $capaIndex/$totalCapas FALLÓ";
        }
    }
    
    $stats['capas_ok'] = count($capasConDatos);
    foreach ($capasConDatos as $info) {
        $m = $stats['metodo_usado'][$info['uuid']] ?? 'desconocido';
        if (!str_starts_with($m, 'L1-')) $stats['capas_fallback']++;
    }
    
    if ($stats['capas_ok'] === 0) {
        throw new RuntimeException("❌ No se pudieron descargar datos para NINGUNA capa.");
    }
    
    $stats['debug'][] = "📊 Capas procesadas exitosamente: " . $stats['capas_ok'] . "/" . $stats['total_capas'];
    
    // ============================================================
    // PASO 4: EJECUTAR TRANSACCIÓN
    // ============================================================
    $transaccionExitosa = ejecutarTransaccionSegura($pdo, $stats, $capasConDatos);
    
    if (!$transaccionExitosa) {
        throw new RuntimeException("❌ La transacción falló");
    }
    
    // ============================================================
    // PASO 5: RESPUESTA FINAL
    // ============================================================
    $metodoPrincipal = implode('+', $metodoGlobal) ?: 'ninguno';
    $status = ($stats['capas_ok'] === $stats['total_capas']) ? 'OK'
            : ($stats['capas_ok'] > 0 ? 'PARCIAL' : 'ERROR');
    
    // Registrar log de sincronización
    try {
        $insLog = $pdo->prepare("INSERT INTO sincronizacion_log
            (origen, status, metodo_descarga, metodo_deteccion, total_leidos, 
             lugares_insert, lugares_update, rutas_insert, rutas_update, 
             paradas_insert, ruta_lugar_ok, ruta_parada_ok, error_msg)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $insLog->execute([
            'umap#' . UMAP_MAP_ID, 
            $status, 
            $metodoPrincipal,
            $stats['metodo_deteccion'] ?? 'desconocido',
            (int)$stats['total_capas'],
            (int)$stats['lugares_insert'], 
            (int)$stats['lugares_update'],
            (int)$stats['rutas_insert'],   
            (int)$stats['rutas_update'],
            (int)$stats['paradas_insert'],
            (int)$stats['ruta_lugar_ok'],  
            (int)$stats['ruta_parada_ok'],
            empty($stats['warnings']) ? null : implode(' | ', $stats['warnings']),
        ]);
    } catch (Throwable $e) {
        $stats['warnings'][] = '⚠️ Log BD: ' . $e->getMessage();
    }
    
    $response = [
        'success' => ($status !== 'ERROR'),
        'status'  => $status,
        'map_id'  => UMAP_MAP_ID,
        'version' => '14.0-worker-json-fix',
        'metodo_deteccion' => $stats['metodo_deteccion'] ?? 'desconocido',
        'metodo_descarga_principal' => $metodoPrincipal,
        'worker_used' => strpos($metodoPrincipal, 'L1-worker') !== false,
        'worker_url' => UMAP_PROXY_WORKER,
        'capas_detectadas' => $stats['capas_detectadas'],
        'total_capas_detectadas' => $stats['total_capas'],
        'capas_procesadas' => $stats['capas_ok'],
        'capas_fallidas' => $stats['total_capas'] - $stats['capas_ok'],
        'stats'   => [
            'total_capas'    => $stats['total_capas'],
            'capas_ok'       => $stats['capas_ok'],
            'capas_fallback' => $stats['capas_fallback'],
            'lugares_total'  => $stats['lugares_insert'] + $stats['lugares_update'],
            'lugares_insert' => $stats['lugares_insert'],
            'lugares_update' => $stats['lugares_update'],
            'rutas_total'    => $stats['rutas_insert'] + $stats['rutas_update'],
            'rutas_insert'   => $stats['rutas_insert'],
            'rutas_update'   => $stats['rutas_update'],
            'paradas_insert' => $stats['paradas_insert'],
            'paradas_skip'   => $stats['paradas_skip'],
            'ruta_lugar_ok'  => $stats['ruta_lugar_ok'],
            'ruta_parada_ok' => $stats['ruta_parada_ok'],
        ],
        'mensaje' => "{$status}: {$stats['capas_ok']}/{$stats['total_capas']} capas · "
                   . ($stats['lugares_insert'] + $stats['lugares_update']) . " lugares · "
                   . ($stats['rutas_insert']   + $stats['rutas_update'])   . " rutas · "
                   . $stats['paradas_insert'] . " paradas",
        'warnings' => $stats['warnings'],
        'debug'    => $stats['debug'],
        'json_debug' => $stats['json_debug'],
        'schema_migration' => $stats['schema_migration'],
        'transaction_active' => $stats['transaction_active'],
        'setup_checklist' => [
            'Worker_URL' => UMAP_PROXY_WORKER,
            'UMAP_PROXY_URL_configurado'  => !empty(UMAP_PROXY_URLS),
            'GITHUB_RAW_BASE_configurado' => !empty(GITHUB_RAW_BASE),
            'UMAP_TOKEN_configurado'      => !empty(UMAP_TOKEN),
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ];
    
    http_response_code($response['success'] ? 200 : 202);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    // 🔒 ROLLBACK DE SEGURIDAD
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
            $stats['debug'][] = "🔙 Rollback de seguridad ejecutado";
        } catch (Throwable $rollbackError) {
            // Silenciar errores de rollback
        }
        $stats['transaction_active'] = false;
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'ERROR',
        'error'   => $e->getMessage(),
        'error_at'=> $e->getFile() . ':' . $e->getLine(),
        'stats'   => $stats,
        'debug'   => $stats['debug'],
        'setup_hint' => [
            'Paso 1' => 'Actualiza el Worker de Cloudflare con el código nuevo',
            'Paso 2' => 'Prueba: ' . UMAP_PROXY_WORKER . 'https://umap.openstreetmap.fr/es/map/rutaslapaz_1451289?format=json',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}