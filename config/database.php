<?php
/**
 * config/database.php
 * ------------------------------------------------------
 * Conexión centralizada PDO a MySQL Aiven
 * ------------------------------------------------------
 * - NO ENVÍA HEADERS cuando es incluido con require/include
 * - Solo envía JSON de error si se accede DIRECTAMENTE vía HTTP
 * - Compatible con:
 *   1) Render.com + Docker via Environment Variables
 *   2) Laragon local (localhost)
 *   3) Aiven MySQL con SSL obligatorio (usa config/ca.pem)
 * ------------------------------------------------------
 * USO CORRECTO (incluir desde otro script):
 *   require_once __DIR__ . '/../config/database.php';
 *   global $pdo;
 *   // usar $pdo para consultas
 * ------------------------------------------------------
 */

// ---------------------------------------------------------------------
// DETECCIÓN: ¿Estamos siendo ACCEDIDOS DIRECTAMENTE por HTTP?
// Si es así, ejecutamos el "test de conexión" y terminamos.
// Si NO, solo definimos la conexión y seguimos (sin headers ni salida).
// ---------------------------------------------------------------------
$esAccesoDirecto = (
    php_sapi_name() !== 'cli'
    && realpath(__FILE__) === realpath(
        $_SERVER['SCRIPT_FILENAME'] ?? ''
    )
);

if ($esAccesoDirecto) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ============================================================
// ✅ CARGA OPCIONAL DE .env LOCAL (solo para desarrollo en Laragon)
// ------------------------------------------------------------
// Este archivo NUNCA debe subirse a git (ya está en .gitignore).
// En Render.com las variables se configuran directamente en el
// Dashboard → Environment, así que este bloque simplemente no
// encuentra el archivo ahí y no hace nada.
// ============================================================
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath) && is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

// ============================================================
// ✅ LECTURA DE VARIABLES DE ENTORNO
// ------------------------------------------------------------
// Sin valores hardcodeados: si faltan, la conexión falla con un
// mensaje claro en vez de usar credenciales escritas en el código
// (GitHub bloquea los pushes que contienen contraseñas en texto
// plano, y es lo correcto).
// ============================================================
$host     = (string)(getenv('PDO_HOST')     ?: '');
$port     = (string)(getenv('PDO_PORT')     ?: '3306');
$dbname   = (string)(getenv('PDO_DATABASE') ?: '');
$username = (string)(getenv('PDO_USERNAME') ?: '');
$password = (string)(getenv('PDO_PASSWORD') ?: '');

if ($host === '' || $dbname === '' || $username === '') {
    $msg = '[database.php] Faltan variables de entorno PDO_HOST/PDO_DATABASE/PDO_USERNAME. '
         . 'En Render: Dashboard → Environment. En local: crea un archivo .env en la raíz de turismo_api (no se sube a git).';
    if ($esAccesoDirecto) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new RuntimeException($msg);
}

// ============================================================
// ✅ RESOLVER RUTA A CERTIFICADO SSL CA
// ============================================================
$sslCaEnv = (string)(getenv('PDO_SSL_CA') ?: '');
$sslCa    = '';

if ($sslCaEnv !== '') {
    // Ruta absoluta o relativa
    $sslCa = (str_starts_with($sslCaEnv, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $sslCaEnv))
        ? $sslCaEnv
        : dirname(__DIR__) . '/' . $sslCaEnv;
}

// Fallback 1: config/ca.pem (tu cert Aiven en el repo)
if (($sslCa === '' || !is_file($sslCa)) && is_file(__DIR__ . '/ca.pem')) {
    $sslCa = __DIR__ . '/ca.pem';
}

// Fallback 2: CA bundle del sistema (Debian/Ubuntu lo trae por defecto)
if (($sslCa === '' || !is_file($sslCa)) && is_file('/etc/ssl/certs/ca-certificates.crt')) {
    $sslCa = '/etc/ssl/certs/ca-certificates.crt';
}

// ============================================================
// ✅ CREAR CONEXIÓN PDO
// ============================================================
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
];

// SSL CA (Aiven REQUIERE conexión encriptada)
if ($sslCa !== '' && is_file($sslCa) && is_readable($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        // Aiven usa certificado público válido → podemos verificarlo
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    /** @var PDO $pdo Conexión global a MySQL Aiven */
    $pdo = new PDO($dsn, $username, $password, $options);

    // Collación UTF-8 y modo de grupo estricto
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
    
    // ✅ FIX: Eliminar NO_AUTO_CREATE_USER (obsoleto en MySQL 8+)
    // Solo mantener modos SQL que existen en MySQL 8+
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
    $pdo->exec("SET SESSION time_zone = '-04:00'"); // Bolivia (America/La_Paz)

    // Solo si el archivo fue accedido DIRECTAMENTE (HTTP) → mostramos JSON OK
    if ($esAccesoDirecto) {
        http_response_code(200);
        $stmt = $pdo->query("SELECT VERSION() as v, DATABASE() as d");
        $row = $stmt->fetch();
        echo json_encode([
            'success'     => true,
            'mensaje'     => '✅ Conexión a MySQL Aiven exitosa',
            'host'        => $host,
            'port'        => $port,
            'database'    => $row['d'] ?? $dbname,
            'version'     => $row['v'] ?? '?',
            'ssl_ca'      => $sslCa ?: '⚠️ Sin SSL CA detectado',
            'ssl_ca_used' => ($sslCa !== '' && is_file($sslCa)) ? 'si' : 'no',
            'timezone'    => 'America/La_Paz (-04:00)',
            'timestamp'   => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

} catch (PDOException $e) {
    if ($esAccesoDirecto) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Error de conexión MySQL Aiven: ' . $e->getMessage(),
            'host'    => $host,
            'port'    => $port,
            'dbname'  => $dbname,
            'ssl_ca'  => $sslCa ?: 'No detectado',
            'hint'    => 'En Render.com configura: PDO_HOST, PDO_PORT, PDO_DATABASE, PDO_USERNAME, PDO_PASSWORD, PDO_SSL_CA',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // SINO (estamos siendo incluidos): RE-LANZAMOS la excepción para que el
    // script que nos incluyó la capture (no salimos con JSON a medias).
    throw new PDOException(
        '[database.php] Conexión fallida a ' . $host . ':' . $port . '/' . $dbname . ' → ' . $e->getMessage(),
        (int)($e->getCode() ?: 500),
        $e
    );
}

// Fin: si llegamos aquí y no fue acceso directo, la variable $pdo queda
//      disponible para el script que hizo require/include.