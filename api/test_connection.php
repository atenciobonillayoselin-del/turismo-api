<?php
// api/test_connection.php - Prueba de conexion a MySQL Aiven + Worker + health check general
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
    'worker_test' => [
        'url' => 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/',
        'status' => 'PENDIENTE',
        'response' => null
    ]
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
    $result['checks'][] = "ℹ️  Directorio caché uMap no existe (se usará GitHub Raw o Worker)";
}

// ---- 🚀 PRUEBA DEL WORKER DE CLOUDFLARE ----
try {
    $workerTestUrl = 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/';
    $ch = curl_init($workerTestUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-HealthCheck/4.0',
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $result['worker_test']['http_code'] = $code;
    if ($code === 200 && strlen($resp) > 50) {
        $json = json_decode($resp, true);
        $result['worker_test']['status'] = '✅ OK';
        $result['worker_test']['response'] = $json;
        $result['checks'][] = "✅ Worker Cloudflare OK (HTTP 200)";
    } else {
        $result['worker_test']['status'] = "❌ FALLÓ (HTTP $code)";
        $result['worker_test']['error'] = $error;
        $result['checks'][] = "⚠️ Worker Cloudflare NO RESPONDE (HTTP $code) - verifica que esté desplegado";
        $result['success'] = false;
    }
} catch (Exception $e) {
    $result['worker_test']['status'] = '❌ EXCEPCIÓN';
    $result['worker_test']['error'] = $e->getMessage();
    $result['checks'][] = "⚠️ Error al probar Worker: " . $e->getMessage();
}

// ---- PRUEBA DE CAPA ESPECÍFICA VIA WORKER ----
try {
    $testCapaId = '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc';
    $targetUrl = "https://umap.openstreetmap.fr/api/0.1/map/1451289/layer/$testCapaId/data/";
    $proxyUrl = 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/?url=' . urlencode($targetUrl);
    
    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && strlen($resp) > 500) {
        $json = json_decode($resp, true);
        $features = isset($json['features']) ? count($json['features']) : 0;
        $result['checks'][] = "✅ Worker puede descargar capas: $features features (HTTP 200)";
        $result['worker_test']['capa_test'] = [
            'id' => $testCapaId,
            'features' => $features,
            'size_kb' => round(strlen($resp)/1024, 1)
        ];
    } else {
        $result['checks'][] = "⚠️ Worker NO PUEDE descargar capas (HTTP $code) - verifica el Worker";
    }
} catch (Exception $e) {
    $result['checks'][] = "⚠️ Error en prueba de capa: " . $e->getMessage();
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
        CURLOPT_USERAGENT => 'TurismoLaPaz-HealthCheck/4.0'
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

// ---- RESUMEN DEL WORKER ----
if ($result['worker_test']['status'] === '✅ OK') {
    $result['worker_summary'] = [
        'status' => '✅ OPERATIVO',
        'url' => 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/',
        'uso' => 'El Worker está funcionando correctamente y puede descargar capas de uMap'
    ];
} else {
    $result['worker_summary'] = [
        'status' => '❌ NO OPERATIVO',
        'url' => 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/',
        'solucion' => 'Verifica que el Worker esté desplegado en Cloudflare Workers & Pages'
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);