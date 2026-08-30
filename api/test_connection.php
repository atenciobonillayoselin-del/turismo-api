<?php
// api/test_connection.php - Prueba de conexion a MySQL Aiven + health check general
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dbnameExpected = getenv('PDO_DATABASE') ?: 'defaultdb';
$result = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_name' => $_SERVER['SERVER_NAME'] ?? php_uname('n'),
    'timezone' => date_default_timezone_get(),
    'database_configured_name' => $dbnameExpected,
    'checks' => [],
    'extensions' => [],
    'env_vars' => [
        'PDO_HOST_SET'     => !empty(getenv('PDO_HOST')),
        'PDO_PORT_SET'     => !empty(getenv('PDO_PORT')),
        'PDO_DATABASE_SET' => !empty(getenv('PDO_DATABASE')),
        'PDO_USERNAME_SET' => !empty(getenv('PDO_USERNAME')),
        'PDO_PASSWORD_SET' => !empty(getenv('PDO_PASSWORD')),
        'PDO_SSL_CA_SET'   => !empty(getenv('PDO_SSL_CA')),
        'UMAP_PROXY_SET'   => !empty(getenv('UMAP_PROXY_URL')),
        'GITHUB_RAW_SET'   => !empty(getenv('GITHUB_RAW_BASE')),
        'UMAP_TOKEN_SET'   => !empty(getenv('UMAP_TOKEN')),
    ],
];

// ---- Extensiones PHP ----
foreach (['pdo','pdo_mysql','curl','json','mbstring','gd','zip','xml','openssl','mysqli'] as $ext) {
    if (extension_loaded($ext)) {
        $result['extensions'][] = "✅ $ext";
    } else {
        $result['extensions'][] = "❌ $ext";
        $result['success'] = false;
    }
}
if (extension_loaded('pdo_mysql')) {
    $result['checks'][] = '✅ Extension pdo_mysql disponible';
} else {
    $result['checks'][] = '❌ Extension pdo_mysql NO disponible';
    $result['success'] = false;
}

// ---- Certificado SSL CA ----
$sslCaCandidates = [
    getenv('PDO_SSL_CA') ?: '',
    dirname(__DIR__) . '/config/ca.pem',
    '/etc/ssl/certs/ca-certificates.crt',
];
$sslCaFinal = '';
foreach ($sslCaCandidates as $c) {
    if (!empty($c) && file_exists($c) && is_readable($c) && filesize($c) > 100) {
        $sslCaFinal = $c;
        break;
    }
}
if (!empty($sslCaFinal)) {
    $result['checks'][] = "✅ Certificado SSL CA OK: $sslCaFinal (" . round(filesize($sslCaFinal)/1024,1) . " KB)";
} else {
    $result['checks'][] = "⚠️  No se encontró cert CA, conexión sin SSL (MySQL Aiven requiere SSL)";
}

// ---- Composer autoload ----
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    $result['checks'][] = "✅ vendor/autoload.php existe";
} else {
    $result['checks'][] = "ℹ️  vendor/autoload.php ausente (no hay dependencias, es normal)";
}

// ---- Directorio caché uMap ----
$cacheDir = dirname(__DIR__) . '/data/umap_cache';
if (is_dir($cacheDir)) {
    $numJson = count(glob("$cacheDir/*.json") ?: []);
    $result['checks'][] = "📁 Directorio caché uMap OK: $numJson archivos .json";
} else {
    $result['checks'][] = "ℹ️  Directorio caché uMap no existe (se usará GitHub Raw o Proxy)";
}

// ---- BD ----
try {
    $_db_config_error_shown = false;
    require_once dirname(__DIR__) . '/config/database.php';

    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch()['version'];
    $result['checks'][] = "✅ Conexión MySQL exitosa (version: $version)";

    $stmt = $pdo->query("SELECT DATABASE() as currentdb");
    $dbActual = $stmt->fetch()['currentdb'];
    $result['checks'][] = "✅ Base de datos ACTUAL: '$dbActual'";

    $tables = ['usuario', 'lugar_turistico', 'ruta', 'parada', 'ruta_lugar', 'ruta_parada', 'sincronizacion_log'];
    foreach ($tables as $tbl) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $stmt->execute([$dbActual, $tbl]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if ($exists) {
            try {
                $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
                $result['checks'][] = "📋 Tabla `$tbl` existe ($cnt filas)";
            } catch (Throwable $e2) {
                $result['checks'][] = "📋 Tabla `$tbl` existe (no se pudo contar filas)";
            }
        } elseif ($tbl === 'sincronizacion_log') {
            $result['checks'][] = "ℹ️  Tabla `$tbl` NO existe (se crea automaticamente en sincronizar.php)";
        } else {
            $result['checks'][] = "❌ Tabla `$tbl` NO existe - DEBES ejecutar el SQL de migración";
            $result['success'] = false;
        }
    }

} catch (Throwable $e) {
    $result['success'] = false;
    $result['checks'][] = '❌ BD: ' . $e->getMessage();
    $result['db_hint'] = 'Verifica PDO_HOST, PDO_PORT, PDO_USERNAME, PDO_PASSWORD en Environment Variables Render.com';
}

// ---- CURL ----
if (function_exists('curl_init')) {
    $ch = curl_init('https://httpbin.org/get');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-HealthCheck/3.0'
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        $result['checks'][] = "✅ Conexión CURL exterior OK (httpbin.org respondió HTTP $code)";
    } else {
        $result['checks'][] = "⚠️  CURL exterior no confirmada (HTTP $code) - puede estar firewalled";
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
