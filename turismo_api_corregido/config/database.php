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
//   PDO_HOST      = tu-host de Aiven (ej: mysql-xxx.aivencloud.com)
//   PDO_PORT      = puerto de Aiven (ej: 23909)
//   PDO_DATABASE  = app_turistica_la_paz (o defaultdb)
//   PDO_USERNAME  = avnadmin
//   PDO_PASSWORD  = tu-password de Aiven
//   PDO_SSL_CA    = config/ca.pem  (ruta al certificado CA dentro del repo)
//
// ✅ IMPORTANTE: Descarga el certificado CA desde Aiven Console
//    y guardalo como config/ca.pem en este mismo repositorio.
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
    // Si la ruta es relativa, convertirla a absoluta desde la raiz del proyecto
    if (strpos($sslCaEnv, '/') === 0 || strpos($sslCaEnv, ':\\') === 1) {
        $sslCa = $sslCaEnv;
    } else {
        $sslCa = dirname(__DIR__) . '/' . $sslCaEnv;
    }
}

// Si no existe el certificado configurado, buscar ca.pem junto a este archivo
if (empty($sslCa) || !file_exists($sslCa)) {
    $defaultCa = __DIR__ . '/ca.pem';
    if (file_exists($defaultCa)) {
        $sslCa = $defaultCa;
    }
}

try {
    // ✅ Construir DSN
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    // ✅ Si hay certificado CA (Aiven), agregar SSL al DSN
    if (!empty($sslCa) && file_exists($sslCa)) {
        $dsn .= ";sslmode=verify-ca;sslrootcert=" . escapeshellarg($sslCa);
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Opciones adicionales para SSL (compatibilidad)
    if (!empty($sslCa) && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        // ✅ Constante correcta para verificar certificado del servidor
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
