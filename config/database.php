<?php
// config/database.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// ✅ CONFIGURACIÓN CON VARIABLES DE ENTORNO (AIVEN / LARAGON)
// ============================================================
// En Render.com configura las variables de entorno:
//   PDO_HOST      = mysql-3c89e575-turismo-la-paz.d.aivencloud.com
//   PDO_PORT      = 23909
//   PDO_DATABASE  = app_turistica_la_paz
//   PDO_USERNAME  = avnadmin
//   PDO_PASSWORD  = tu-password-de-aiven
//   PDO_SSL_CA    = config/ca.pem
// ============================================================

$host = getenv('PDO_HOST') ?: 'localhost';
$port = getenv('PDO_PORT') ?: '3306';
$dbname = getenv('PDO_DATABASE') ?: 'app_turistica_la_paz';
$username = getenv('PDO_USERNAME') ?: 'root';
$password = getenv('PDO_PASSWORD') ?: '';

// ✅ Ruta al certificado CA (para Aiven SSL)
$sslCaEnv = getenv('PDO_SSL_CA') ?: '';
$sslCa = '';

if (!empty($sslCaEnv)) {
    if (strpos($sslCaEnv, '/') === 0 || strpos($sslCaEnv, ':\\') === 1) {
        $sslCa = $sslCaEnv;
    } else {
        $sslCa = dirname(__DIR__) . '/' . $sslCaEnv;
    }
}

if (empty($sslCa) || !file_exists($sslCa)) {
    $defaultCa = __DIR__ . '/ca.pem';
    if (file_exists($defaultCa)) {
        $sslCa = $defaultCa;
    }
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    if (!empty($sslCa) && file_exists($sslCa)) {
        $dsn .= ";sslmode=verify-ca;sslrootcert=" . escapeshellarg($sslCa);
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (!empty($sslCa) && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }
    }

    $pdo = new PDO($dsn, $username, $password, $options);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>