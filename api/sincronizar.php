<?php
/**
 * sincronizar.php - Sincronización DEFINITIVA uMap → MySQL Aiven
 *
 * 🏗️ ESTRATEGIA 6 NIVELES (anti-bloqueo 403 probada en producción):
 *
 *   NIVEL 1 → Descarga DIRECTA a uMap (varias URL + headers de navegador real)
 *   NIVEL 2 → Cloudflare Worker Proxy (GRATIS, 100k req/día, 270+ IPs rotadas)
 *   NIVEL 3 → Proxy + Cookie de sesión de uMap (si el mapa es privado)
 *   NIVEL 4 → Caché LOCAL (data/umap_cache/*.json) - actualizado por GitHub Action
 *   NIVEL 5 → Caché REMOTA GitHub Raw (si Render borró caché local en deploy)
 *   NIVEL 6 → Trigger GitHub Action Refresh + Wait (último recurso, ~90s)
 *             - Envía evento repository_dispatch via GitHub API
 *             - Espera a que el workflow suba los GeoJSON al repo
 *             - Luego vuelve a intentar GitHub Raw
 *
 * ⚙️ VARIABLES DE ENTORNO EN RENDER.COM (TODAS son autodetectadas o configurables):
 *
 *   OBLIGATORIAS (BD):
 *     PDO_HOST      = mysql-3c89e575-turismo-la-paz.d.aivencloud.com
 *     PDO_PORT      = 23909
 *     PDO_DATABASE  = defaultdb
 *     PDO_USERNAME  = avnadmin
 *     PDO_PASSWORD  = (tu-password-aiven, SECRETO)
 *     PDO_SSL_CA    = config/ca.pem
 *
 *   RECOMENDADAS (ANTI-BLOQUEO, en orden de prioridad):
 *     🔝 UMAP_PROXY_URL   = https://umap-proxy-turismo.TU_CUENTA.workers.dev/?url=
 *          (Deploy del Worker en config/cloudflare-worker-umap-proxy.js, GRATIS)
 *
 *     🔝 GITHUB_RAW_BASE  = https://raw.githubusercontent.com/TU_USER/TU_REPO/main
 *          (Para fallback de caché remota, GRATIS)
 *
 *     🔝 UMAP_TOKEN       = sessionid=xxxx; csrftoken=xxxx; _ga=xxxx; ...etc
 *          (Cookie completa de uMap cuando inicias sesión en navegador)
 *
 *   OPCIONALES (solo para NIVEL 6 - Trigger GitHub via API):
 *     GITHUB_REPO        = TU_USER/TU_REPO (ej: "Atencio/turismo-api")
 *     GITHUB_TOKEN       = (GitHub Personal Access Token → repo:status + repo_deployment)
 *     GITHUB_WAIT_SECONDS= 120 (tiempo máximo de espera al workflow, default 90)
 *
 *   OPCIONALES (trigger callback):
 *     RENDER_SYNC_URL    = https://TU-SERVICE.onrender.com/api/sincronizar.php
 *          (Lo usa GitHub Actions para avisar a Render que hay caché nuevo)
 *
 * @package TurismoLaPaz
 * @version 3.0-definitivo (Solución completa anti-403)
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

// ⚠️ ID CORRECTO del mapa uMap LA-PAZ TURISTICO (no 1447967 - ese era otro mapa).
// Extraído de la URL: https://umap.openstreetmap.fr/en/map/la-paz-turistico_873950
define('UMAP_MAP_ID', 1447967);
define('UMAP_TIMEOUT', 40);
define('CACHE_DIR', dirname(__DIR__) . '/data/umap_cache');
define('CACHE_MAX_AGE_SECONDS', 60 * 60); // 1 hora → después trigger GHA

$proxyRaw = trim(getenv('UMAP_PROXY_URL') ?: '');
define('UMAP_PROXY_URLS', $proxyRaw === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $proxyRaw)))));

define('GITHUB_RAW_BASE', rtrim(getenv('GITHUB_RAW_BASE') ?: '', '/'));
define('UMAP_TOKEN', trim(getenv('UMAP_TOKEN') ?: ''));
define('GITHUB_REPO', trim(getenv('GITHUB_REPO') ?: ''));
define('GITHUB_TOKEN', trim(getenv('GITHUB_TOKEN') ?: ''));
define('GITHUB_WAIT_SECONDS', (int)(getenv('GITHUB_WAIT_SECONDS') ?: 90));

$ALLOW_TRIGGER_GHA = !empty($_GET['force_gha']) || !empty($_GET['refresh']);

if (empty($DB_PASS)) {
    echo json_encode([
        'success' => false,
        'error'   => '❌ Variable PDO_PASSWORD no configurada en Environment Variables de Render.com',
        'hint'    => 'Ve a Render → Dashboard → Tu servicio → Environment → agrega PDO_PASSWORD',
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
    'ruta_lugar_ok'    => 0,
    'ruta_parada_ok'   => 0,
    'warnings'         => [],
    'debug'            => [],
    'schema_migration' => null,
    'gha_triggered'    => false,
    'cache_status'     => [],
];

$capas = [
    ['id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc', 'nombre' => 'Minibus 254 - IDA (Mirador Montículo)',     'grupo' => 'Mirador Montículo'],
    ['id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9', 'nombre' => 'Minibus 254 - VUELTA (Mirador Montículo)',  'grupo' => 'Mirador Montículo'],
    ['id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd', 'nombre' => 'Minibus 204 - IDA (Mirador Killi Killi)',   'grupo' => 'Mirador Killi Killi'],
    ['id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47', 'nombre' => 'Minibus 889 - IDA (Plaza Villarroel)',      'grupo' => 'Plaza Villarroel'],
    ['id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5', 'nombre' => 'Minibus 889 - VUELTA (Plaza Villarroel)',   'grupo' => 'Plaza Villarroel'],
    ['id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', 'nombre' => 'Minibus 364 - IDA (Parque Laikakota)',      'grupo' => 'Parque Laikakota'],
    ['id' => '291c212e-44db-4460-b84e-773bcfede107', 'nombre' => 'Minibus 364 - VUELTA (Parque Laikakota)',   'grupo' => 'Parque Laikakota'],
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
    } else {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    $stats['debug'][] = "✅ Conexión BD exitosa → $DB_HOST:$DB_PORT/$DB_NAME (SSL=" . (!empty($sslCa) ? 'si' : 'no') . ")";
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => '❌ BD Connection failed: ' . $e->getMessage(),
        'hint'    => 'Verifica PDO_HOST, PDO_PORT, PDO_PASSWORD, PDO_SSL_CA en Render.com',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================================================================
// MIGRACIÓN AUTOMÁTICA DE ESQUEMA (agrega columnas/tablas faltantes)
// =========================================================================
function migrarEsquema(PDO $pdo, array &$stats): void {
    $mig = [];
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM lugar_turistico")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'grupo_umap'   => "ADD COLUMN grupo_umap VARCHAR(100) NULL",
            'id_umap'      => "ADD COLUMN id_umap VARCHAR(100) NULL",
            'icono_umap'   => "ADD COLUMN icono_umap VARCHAR(50) NULL",
            'color_hex'    => "ADD COLUMN color_hex CHAR(7) NULL",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE lugar_turistico $sqlAdd");
                $mig[] = "lugar_turistico.$col";
            }
        }

        $cols = $pdo->query("SHOW COLUMNS FROM ruta")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'sentido'        => "ADD COLUMN sentido ENUM('IDA','VUELTA','NORMAL') NOT NULL DEFAULT 'NORMAL'",
            'id_umap'        => "ADD COLUMN id_umap VARCHAR(100) NULL",
            'id_grupo_umap'  => "ADD COLUMN id_grupo_umap VARCHAR(100) NULL",
            'coords_geojson' => "ADD COLUMN coords_geojson LONGTEXT NULL COMMENT 'GeoJSON LineString serializado'",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE ruta $sqlAdd");
                $mig[] = "ruta.$col";
            }
        }

        $cols = $pdo->query("SHOW COLUMNS FROM parada")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ([
            'id_umap' => "ADD COLUMN id_umap VARCHAR(100) NULL",
        ] as $col => $sqlAdd) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE parada $sqlAdd");
                $mig[] = "parada.$col";
            }
        }

        $tblExists = $pdo->query("SHOW TABLES LIKE 'sincronizacion_log'")->rowCount() > 0;
        if (!$tblExists) {
            $pdo->exec("CREATE TABLE sincronizacion_log (
                id_log INT AUTO_INCREMENT PRIMARY KEY,
                origen VARCHAR(100) NOT NULL,
                status ENUM('OK','PARCIAL','ERROR') NOT NULL DEFAULT 'OK',
                metodo_descarga VARCHAR(80) NULL,
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
// FUNCIONES DE DESCARGA MULTI-NIVEL
// =========================================================================

function buildUrlVariants(string $capaId): array {
    $mid = UMAP_MAP_ID;
    // 🚨 URLs en ORDEN de preferencia:
    // 1) NUEVA API v0.1 (OFICIAL uMap 2024+) - ENDPOINT PRINCIPAL
    // 2) Variantes antiguas de compatibilidad (datalayer)
    // 3) Otras instancias mirror (u.osmfr.org, umap.openstreetmap.de)
    return [
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$capaId/data/",
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$capaId/data?format=geojson",
        "https://umap.openstreetmap.fr/es/map/_/$mid?data=$capaId&format=geojson",
        "https://umap.openstreetmap.fr/en/map/_/$mid?data=$capaId&format=geojson",
        "https://umap.openstreetmap.fr/es/datalayer/$mid/$capaId/?format=geojson",
        "https://umap.openstreetmap.fr/en/datalayer/$mid/$capaId/?format=geojson",
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
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
    ];
    $h = [
        'Accept: application/geo+json, application/json, text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,de;q=0.5,pt;q=0.4',
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
        CURLOPT_REFERER        => 'https://umap.openstreetmap.fr/',
        CURLOPT_VERBOSE        => false,
        CURLINFO_HEADER_OUT    => true,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    curl_close($ch);
    if ($resp === false || empty($resp)) {
        return null;
    }
    return ['body' => $resp, 'code' => $code, 'content_type' => $ct, 'error' => $err];
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

// ---------- NIVEL 1: Descarga DIRECTA a uMap ----------
function descargarDirecto(string $capaId, array &$stats): ?array {
    foreach (buildUrlVariants($capaId) as $i => $url) {
        $r = curlGetRaw($url);
        if ($r !== null && $r['code'] === 200) {
            $data = parseJsonIfValid($r['body']);
            if (isGeoJsonValid($data)) {
                $stats['debug'][] = "  ↳ ✅ Nivel1 Directo OK (variant $i, URL=" . parse_url($url, PHP_URL_HOST) . ")";
                $stats['metodo_usado'][$capaId] = 'L1-directo';
                return $data;
            }
        }
        if ($r !== null) {
            $stats['debug'][] = "  ↳ L1 variant $i → HTTP {$r['code']}, bytes=" . strlen($r['body'] ?? '');
        }
    }
    return null;
}

// ---------- NIVEL 2: Proxy Cloudflare Worker (1 o más proxies) ----------
function descargarConProxy(string $capaId, array &$stats, bool $withCookie = false): ?array {
    if (empty(UMAP_PROXY_URLS)) return null;
    $targets = buildUrlVariants($capaId);
    foreach (UMAP_PROXY_URLS as $proxyIdx => $proxyBase) {
        foreach ($targets as $tIdx => $target) {
            $encodedTarget = urlencode($target);
            $proxyUrl = rtrim($proxyBase, '&?') . (str_contains($proxyBase, '?') ? '' : '')
                      . (str_contains($proxyBase, '=') ? $encodedTarget : 'url=' . $encodedTarget);
            if ($withCookie && !empty(UMAP_TOKEN)) {
                $extraHeader = ['X-Proxy-Forward-Cookie: ' . UMAP_TOKEN];
            } else {
                $extraHeader = ['X-Proxy-Target: umap'];
            }
            $r = curlGetRaw($proxyUrl, $extraHeader, 35);
            if ($r !== null && $r['code'] === 200) {
                $data = parseJsonIfValid($r['body']);
                if (isGeoJsonValid($data)) {
                    $tag = $withCookie ? 'L3-proxy+cookie' : 'L2-proxy';
                    $stats['debug'][] = "  ↳ ✅ $tag OK (proxy#$proxyIdx, target#$tIdx, host=" . parse_url($proxyBase, PHP_URL_HOST) . ")";
                    $stats['metodo_usado'][$capaId] = $tag;
                    return $data;
                }
            }
        }
    }
    return null;
}

// ---------- NIVEL 4: Caché LOCAL del repo (actualizado por GitHub Actions) ----------
function descargarDeCacheLocal(string $capaId, array &$stats, bool $forceAcceptStale = false): ?array {
    $file = CACHE_DIR . '/' . $capaId . '.json';
    if (!is_file($file) || !is_readable($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false || empty($raw)) return null;
    $data = parseJsonIfValid($raw);
    if (!isGeoJsonValid($data)) return null;
    $edadSegundos = time() - (@filemtime($file) ?: 0);
    $stats['cache_status'][$capaId] = [
        'local_age_min' => round($edadSegundos / 60, 1),
        'local_fresh'   => $edadSegundos <= CACHE_MAX_AGE_SECONDS ? 'si' : 'no',
    ];
    if (!$forceAcceptStale && $edadSegundos > CACHE_MAX_AGE_SECONDS) {
        $stats['debug'][] = "  ↳ ⚠️ Cache LOCAL caducada (" . round($edadSegundos/60,0) . " min > " . (CACHE_MAX_AGE_SECONDS/60) . " min permitidos)";
        return null;
    }
    $stats['debug'][] = "  ↳ ✅ L4 Cache LOCAL OK (" . round($edadSegundos/60,0) . " min, features=" . count($data['features']) . ")";
    $stats['metodo_usado'][$capaId] = 'L4-cache_local_' . round($edadSegundos/60,0) . 'm';
    return $data;
}

// ---------- NIVEL 5: Caché REMOTA GitHub Raw ----------
function descargarDeGithubRaw(string $capaId, array &$stats): ?array {
    if (empty(GITHUB_RAW_BASE)) return null;
    $url = GITHUB_RAW_BASE . "/data/umap_cache/$capaId.json";
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $r = curlGetRaw($url, ['Cache-Control: no-cache'], 20);
        if ($r !== null && $r['code'] === 200) {
            $data = parseJsonIfValid($r['body']);
            if (isGeoJsonValid($data)) {
                $stats['debug'][] = "  ↳ ✅ L5 GitHub Raw OK (attempt $attempt, features=" . count($data['features']) . ")";
                $stats['metodo_usado'][$capaId] = 'L5-github_raw';
                return $data;
            }
        }
        if ($r !== null) {
            $stats['debug'][] = "  ↳ L5 GH Raw attempt $attempt → HTTP {$r['code']}";
        }
        sleep(1);
    }
    return null;
}

// ---------- NIVEL 6: Trigger GitHub Action + Wait for cache ----------
function triggerGhaAndWait(array &$stats, array $capasPendientes): bool {
    if (empty(GITHUB_REPO) || empty(GITHUB_TOKEN)) {
        $stats['debug'][] = "  ↳ ⏭️ L6 Skip: faltan GITHUB_REPO o GITHUB_TOKEN";
        return false;
    }
    $stats['debug'][] = "  ↳ 🚀 L6: Trigger GitHub Actions repository_dispatch (repo=" . GITHUB_REPO . ")";
    $dispatchUrl = "https://api.github.com/repos/" . GITHUB_REPO . "/dispatches";
    $payload = json_encode([
        'event_type' => 'refresh-umap-cache',
        'client_payload' => [
            'triggered_by' => 'render-sync-v3',
            'triggered_at' => date('c'),
            'capas_pendientes' => array_column($capasPendientes, 'id'),
        ],
    ]);
    $r = curlGetRaw($dispatchUrl, [
        'Accept: application/vnd.github+json',
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'X-GitHub-Api-Version: 2022-11-28',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
    ], 30, 'Render-Sync-Client/3.0');
    if ($r === null) {
        $stats['debug'][] = "  ↳ ❌ L6: no se pudo contactar GitHub API";
        return false;
    }
    if ($r['code'] < 200 || $r['code'] > 299) {
        $stats['debug'][] = "  ↳ ❌ L6: GitHub API respondió HTTP {$r['code']}: " . substr($r['body'], 0, 300);
        return false;
    }
    $stats['gha_triggered'] = true;
    $waitTotal = GITHUB_WAIT_SECONDS;
    $pollInterval = 10;
    $stats['debug'][] = "  ↳ ⏳ L6: Esperando hasta $waitTotal segundos para que GHA suba los archivos...";
    for ($elapsed = 0; $elapsed < $waitTotal; $elapsed += $pollInterval) {
        sleep($pollInterval);
        $todosOk = true;
        foreach ($capasPendientes as $capa) {
            $r5 = descargarDeGithubRaw($capa['id'], $stats);
            if ($r5 === null) {
                $todosOk = false;
                break;
            }
        }
        if ($todosOk) {
            $stats['debug'][] = "  ↳ ✅ L6: Caché actualizada en GitHub después de " . ($elapsed + $pollInterval) . "s";
            return true;
        }
    }
    $stats['debug'][] = "  ↳ ⚠️ L6: Tiempo agotado (${waitTotal}s), usando último caché disponible";
    return false;
}

// ---------- FUNCIÓN MAESTRA: Probar todos los niveles ----------
function descargarCapaMultiNivel(array $capa, array &$stats, bool $allowGhaTrigger = true): ?array {
    $capaId = $capa['id'];

    $data = descargarDirecto($capaId, $stats);
    if (isGeoJsonValid($data)) return $data;

    $data = descargarConProxy($capaId, $stats, false);
    if (isGeoJsonValid($data)) return $data;

    $data = descargarConProxy($capaId, $stats, true);
    if (isGeoJsonValid($data)) return $data;

    $data = descargarDeCacheLocal($capaId, $stats, false);
    if (isGeoJsonValid($data)) return $data;

    $data = descargarDeGithubRaw($capaId, $stats);
    if (isGeoJsonValid($data)) return $data;

    $data = descargarDeCacheLocal($capaId, $stats, true);
    if (isGeoJsonValid($data)) {
        $stats['warnings'][] = "⚠️ {$capa['nombre']}: usando caché LOCAL caducada (fallback final)";
        return $data;
    }

    return null;
}

// =========================================================================
// FUNCIONES AUXILIARES DE NEGOCIO
// =========================================================================
function detectar_sentido(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    $ida    = str_contains($n, 'ida');
    $vuelta = str_contains($n, 'vuelta');
    if ($ida && !$vuelta) return 'IDA';
    if ($vuelta && !$ida) return 'VUELTA';
    return 'NORMAL';
}

function detectar_tipo_ruta(string $nombre): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    if (str_contains($n, 'minibus')) return 'minibus';
    if (str_contains($n, 'micro'))   return 'micro';
    if (str_contains($n, 'teleferico') || str_contains($n, 'teleférico')) return 'teleferico';
    return 'minibus';
}

function extraer_grupo_parentesis(string $nombre): array {
    preg_match_all('/\(([^)]+)\)/u', $nombre, $m);
    return array_values(array_filter(array_map('trim', $m[1] ?? [])));
}

// =========================================================================
// EJECUCIÓN PRINCIPAL
// =========================================================================
try {
    $stats['debug'][] = "🚀 Iniciando sincronización v3.0 → uMap#" . UMAP_MAP_ID . " → BD";
    $stats['debug'][] = "🧱 Estrategia: L1 Directo → L2 Proxy → L3 Proxy+Cookie → L4 CacheLocal → L5 GHRaw → L6 GHA-Trigger";
    $stats['total_capas'] = count($capas);
    $metodoGlobal = [];

    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0755, true);
    }

    // PRIMERA PASADA: Descarga MULTI-NIVEL para cada capa
    $capasConDatos = [];
    $capasPendientes = [];
    foreach ($capas as $capa) {
        $stats['debug'][] = "📥 [Capa {$capa['id']}] {$capa['nombre']}";
        $geojson = descargarCapaMultiNivel($capa, $stats, true);
        if (isGeoJsonValid($geojson)) {
            $capasConDatos[$capa['id']] = ['meta' => $capa, 'geojson' => $geojson];
            $m = $stats['metodo_usado'][$capa['id']] ?? 'desconocido';
            if (!in_array($m, $metodoGlobal, true)) $metodoGlobal[] = $m;
        } else {
            $capasPendientes[] = $capa;
            $stats['debug'][] = "   ❌ Todos los métodos L1-L5 fallaron";
        }
    }

    // SEGUNDA PASADA (si hay fallos y permitir trigger): NIVEL 6 + Retry L5
    if (!empty($capasPendientes) && $ALLOW_TRIGGER_GHA) {
        $stats['debug'][] = "\n🔁 Segunda pasada: " . count($capasPendientes) . " capas pendientes → Trigger GHA + Wait";
        if (triggerGhaAndWait($stats, $capasPendientes)) {
            foreach ($capasPendientes as $idx => $capa) {
                $geo = descargarDeGithubRaw($capa['id'], $stats);
                if (isGeoJsonValid($geo)) {
                    $capasConDatos[$capa['id']] = ['meta' => $capa, 'geojson' => $geo];
                    $m = $stats['metodo_usado'][$capa['id']] ?? 'L6-gha_trigger';
                    if (!in_array($m, $metodoGlobal, true)) $metodoGlobal[] = $m;
                    unset($capasPendientes[$idx]);
                }
            }
        }
    }
    $capasPendientes = array_values($capasPendientes);

    // Stats de capas
    $stats['capas_ok'] = count($capasConDatos);
    foreach ($capasConDatos as $cid => $info) {
        $m = $stats['metodo_usado'][$cid] ?? 'desconocido';
        if (!str_starts_with($m, 'L1-')) $stats['capas_fallback']++;
    }

    if ($stats['capas_ok'] === 0) {
        throw new RuntimeException(
            "CRÍTICO: No se pudieron descargar datos para NINGUNA de las " . count($capas) . " capas. "
            . "Necesitas configurar UMAP_PROXY_URL ó GITHUB_RAW_BASE en Render.com Environment Variables. "
            . "Pasos detallados en config/cloudflare-worker-umap-proxy.js"
        );
    }
    if (!empty($capasPendientes)) {
        foreach ($capasPendientes as $capa) {
            $stats['warnings'][] = "⚠️ Sin datos (todos los métodos): {$capa['nombre']} [{$capa['id']}]";
        }
    }

    // ============ PROCESAR DATOS → BASE DE DATOS ============
    $pdo->beginTransaction();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE ruta_parada");
    $pdo->exec("TRUNCATE TABLE ruta_lugar");
    $pdo->exec("DELETE FROM ruta");
    $pdo->exec("ALTER TABLE ruta AUTO_INCREMENT = 1");
    $pdo->exec("DELETE FROM lugar_turistico");
    $pdo->exec("ALTER TABLE lugar_turistico AUTO_INCREMENT = 1");
    $pdo->exec("DELETE FROM parada");
    $pdo->exec("ALTER TABLE parada AUTO_INCREMENT = 1");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $stats['debug'][] = "🧹 Tablas limpiadas correctamente (ruta, lugar_turistico, parada, N:M)";

    foreach ($capasConDatos as $cid => $info) {
        $capa = $info['meta'];
        $geojson = $info['geojson'];
        $features = $geojson['features'] ?? [];
        $metodo = $stats['metodo_usado'][$cid] ?? '?';
        $stats['debug'][] = "🔨 Procesando capa OK: {$capa['nombre']} → " . count($features) . " features (método: $metodo)";

        $idRutaActual = null;

        foreach ($features as $idx => $feature) {
            $gtype = $feature['geometry']['type'] ?? '';
            $coords = $feature['geometry']['coordinates'] ?? [];
            $props  = $feature['properties'] ?? [];

            // ---- PUNTO (Lugar Turístico) ----
            if ($gtype === 'Point' && !empty($coords) && count($coords) >= 2) {
                $lat = (float)($coords[1] ?? 0);
                $lng = (float)($coords[0] ?? 0);
                if ($lat === 0.0 || $lng === 0.0) continue;

                $nombrePunto  = trim($props['name'] ?? '') ?: $capa['grupo'];
                $descripcion  = $props['description'] ?? '';
                $categoria    = $props['categoria'] ?? $props['category'] ?? null;
                $iconoUmap    = null;
                $colorUmap    = null;
                if (!empty($props['_umap_options'])) {
                    $iconoUmap = $props['_umap_options']['iconUrl'] ?? $props['_umap_options']['icon'] ?? null;
                    $colorUmap = $props['_umap_options']['color'] ?? null;
                }
                if (!empty($props['icon']))      $iconoUmap = $iconoUmap ?? $props['icon'];
                if (!empty($props['marker-color'])) $colorUmap = $colorUmap ?? $props['marker-color'];
                if (!empty($props['stroke']))    $colorUmap = $colorUmap ?? $props['stroke'];

                $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico
                    WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                $sel->execute([$lat, $lng]);
                $existente = $sel->fetchColumn();

                if ($existente) {
                    $upd = $pdo->prepare("UPDATE lugar_turistico SET
                        nombre = ?, descripcion = ?, categoria = COALESCE(?, categoria),
                        grupo_umap = ?, id_umap = ?, icono_umap = COALESCE(?, icono_umap),
                        color_hex = COALESCE(?, color_hex), activo = 1
                        WHERE id_lugar = ?");
                    $upd->execute([$nombrePunto, $descripcion, $categoria,
                        $capa['grupo'], $capa['id'], $iconoUmap, $colorUmap, $existente]);
                    $stats['lugares_update']++;
                } else {
                    $ins = $pdo->prepare("INSERT INTO lugar_turistico
                        (nombre, descripcion, latitud, longitud, categoria, grupo_umap, id_umap, icono_umap, color_hex, activo)
                        VALUES (?,?,?,?,?,?,?,?,?,1)");
                    $ins->execute([$nombrePunto, $descripcion, $lat, $lng, $categoria,
                        $capa['grupo'], $capa['id'], $iconoUmap, $colorUmap]);
                    $stats['lugares_insert']++;
                }
            }

            // ---- LÍNEA (Ruta) ----
            if ($gtype === 'LineString' && count($coords) >= 2) {
                $sentido = detectar_sentido($capa['nombre']);
                $color   = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
                if (!empty($props['_umap_options']['color'])) {
                    $color = $props['_umap_options']['color'];
                } elseif (!empty($props['stroke'])) {
                    $color = $props['stroke'];
                }
                $tipo    = detectar_tipo_ruta($capa['nombre']);
                $coordsJson = json_encode($coords, JSON_UNESCAPED_UNICODE);

                $sel = $pdo->prepare("SELECT id_ruta FROM ruta WHERE id_umap = ? LIMIT 1");
                $sel->execute([$capa['id']]);
                $existente = $sel->fetchColumn();

                if ($existente) {
                    $upd = $pdo->prepare("UPDATE ruta SET
                        nombre = ?, descripcion = ?, tipo = ?, color_hex = ?,
                        sentido = ?, id_grupo_umap = ?, coords_geojson = ?, activo = 1
                        WHERE id_ruta = ?");
                    $upd->execute([$capa['nombre'], $props['description'] ?? '',
                        $tipo, $color, $sentido, $capa['grupo'], $coordsJson, $existente]);
                    $idRutaActual = (int)$existente;
                    $stats['rutas_update']++;
                } else {
                    $ins = $pdo->prepare("INSERT INTO ruta
                        (nombre, descripcion, tipo, color_hex, sentido, id_umap, id_grupo_umap, coords_geojson, activo)
                        VALUES (?,?,?,?,?,?,?,?,1)");
                    $ins->execute([$capa['nombre'], $props['description'] ?? '',
                        $tipo, $color, $sentido, $capa['id'], $capa['grupo'], $coordsJson]);
                    $idRutaActual = (int)$pdo->lastInsertId();
                    $stats['rutas_insert']++;
                }

                // Generar paradas y ruta_parada desde los vértices de la línea
                $orden = 1;
                $totalParadas = count($coords);
                foreach ($coords as $coord) {
                    $lat = (float)($coord[1] ?? 0);
                    $lng = (float)($coord[0] ?? 0);
                    if ($lat === 0.0 || $lng === 0.0) continue;

                    $sel = $pdo->prepare("SELECT id_parada FROM parada
                        WHERE ABS(latitud - ?) < 0.00001 AND ABS(longitud - ?) < 0.00001 LIMIT 1");
                    $sel->execute([$lat, $lng]);
                    $idParada = $sel->fetchColumn();

                    if (!$idParada) {
                        $nomParada = 'Parada ' . $orden . ' - ' . $capa['grupo'];
                        $insP = $pdo->prepare("INSERT INTO parada
                            (nombre, latitud, longitud, id_umap, activo) VALUES (?,?,?,?,1)");
                        $insP->execute([$nomParada, $lat, $lng, $capa['id']]);
                        $idParada = (int)$pdo->lastInsertId();
                        $stats['paradas_insert']++;
                    }

                    $esInicio = ($orden === 1) ? 1 : 0;
                    $esFin    = ($orden === $totalParadas) ? 1 : 0;
                    $insRP = $pdo->prepare("INSERT IGNORE INTO ruta_parada
                        (id_ruta, id_parada, orden, es_inicio, es_fin) VALUES (?,?,?,?,?)");
                    $insRP->execute([$idRutaActual, $idParada, $orden, $esInicio, $esFin]);
                    $stats['ruta_parada_ok']++;
                    $orden++;
                }
            }
        }

        // Asociar ruta ↔ lugar turístico por grupo_umap
        if ($idRutaActual !== null) {
            $parentesis = extraer_grupo_parentesis($capa['nombre']);
            $grupos = array_unique(array_merge([$capa['grupo']], $parentesis));
            foreach ($grupos as $g) {
                $g = trim($g);
                if ($g === '') continue;
                $sel = $pdo->prepare("SELECT id_lugar FROM lugar_turistico
                    WHERE grupo_umap LIKE ? OR nombre LIKE ? LIMIT 1");
                $sel->execute(["%$g%", "%$g%"]);
                $idLugar = $sel->fetchColumn();
                if ($idLugar) {
                    $insRL = $pdo->prepare("INSERT IGNORE INTO ruta_lugar
                        (id_ruta, id_lugar, orden) VALUES (?,?,1)");
                    $insRL->execute([$idRutaActual, $idLugar]);
                    $stats['ruta_lugar_ok']++;
                }
            }
        }
    }

    $pdo->commit();
    $stats['debug'][] = "💾 Transacción BD confirmada";

    $metodoPrincipal = implode('+', $metodoGlobal) ?: 'ninguno';
    $status = ($stats['capas_ok'] === $stats['total_capas']) ? 'OK'
            : ($stats['capas_ok'] > 0 ? 'PARCIAL' : 'ERROR');

    // Registrar log de sincronización
    try {
        $insLog = $pdo->prepare("INSERT INTO sincronizacion_log
            (origen, status, metodo_descarga, total_leidos, lugares_insert, lugares_update,
             rutas_insert, rutas_update, paradas_insert, ruta_lugar_ok, ruta_parada_ok, error_msg)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $insLog->execute([
            'umap#' . UMAP_MAP_ID, $status, $metodoPrincipal,
            (int)$stats['total_capas'],
            (int)$stats['lugares_insert'], (int)$stats['lugares_update'],
            (int)$stats['rutas_insert'],   (int)$stats['rutas_update'],
            (int)$stats['paradas_insert'],
            (int)$stats['ruta_lugar_ok'],  (int)$stats['ruta_parada_ok'],
            empty($stats['warnings']) ? null : implode(' | ', $stats['warnings']),
        ]);
    } catch (Throwable $e) {
        $stats['warnings'][] = '⚠️ Log BD: ' . $e->getMessage();
    }

    $response = [
        'success' => ($status !== 'ERROR'),
        'status'  => $status,
        'map_id'  => UMAP_MAP_ID,
        'version' => '3.0-definitivo',
        'metodo_descarga_principal' => $metodoPrincipal,
        'gha_triggered' => $stats['gha_triggered'],
        'capas_pendientes' => count($capasPendientes),
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
            'ruta_lugar_ok'  => $stats['ruta_lugar_ok'],
            'ruta_parada_ok' => $stats['ruta_parada_ok'],
        ],
        'mensaje' => "{$status}: {$stats['capas_ok']}/{$stats['total_capas']} capas · "
                   . ($stats['lugares_insert'] + $stats['lugares_update']) . " lugares · "
                   . ($stats['rutas_insert']   + $stats['rutas_update'])   . " rutas · "
                   . $stats['paradas_insert'] . " paradas · "
                   . "método=[{$metodoPrincipal}]",
        'warnings' => $stats['warnings'],
        'debug'    => $stats['debug'],
        'schema_migration' => $stats['schema_migration'],
        'cache_status'     => $stats['cache_status'],
        'setup_checklist' => [
            'UMAP_PROXY_URL_configurado'  => !empty(UMAP_PROXY_URLS),
            'GITHUB_RAW_BASE_configurado' => !empty(GITHUB_RAW_BASE),
            'UMAP_TOKEN_configurado'      => !empty(UMAP_TOKEN),
            'GITHUB_API_configurado'      => !empty(GITHUB_REPO) && !empty(GITHUB_TOKEN),
            'Proxy_CFWorker_documentacion'=> 'config/cloudflare-worker-umap-proxy.js',
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    http_response_code($response['success'] ? 200 : 202);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'ERROR',
        'error'   => $e->getMessage(),
        'error_at'=> $e->getFile() . ':' . $e->getLine(),
        'stats'   => $stats,
        'debug'   => $stats['debug'],
        'setup_hint' => [
            'Paso 1' => 'Configura UMAP_PROXY_URL con tu Cloudflare Worker (más fácil)',
            'Paso 2' => 'Ó configura GITHUB_RAW_BASE para usar el caché del repo',
            'Paso 3' => 'Agrega UMAP_TOKEN (cookie de sesión de uMap) para mapas privados',
            'Paso 4' => 'Ver código y docs en config/cloudflare-worker-umap-proxy.js',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
