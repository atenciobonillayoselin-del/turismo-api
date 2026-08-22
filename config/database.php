<?php
// config/database.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// ✅ CONFIGURACIÓN PARA RENDER.COM
// ============================================================

// Variables de entorno (Render las proporciona automáticamente)
$host = getenv('PDO_HOST') ?: 'localhost';
$dbname = getenv('PDO_DATABASE') ?: 'app_turistica_la_paz';
$username = getenv('PDO_USERNAME') ?: 'root';
$password = getenv('PDO_PASSWORD') ?: '';

// Para desarrollo local (Laragon)
if ($host === 'localhost' && empty($password)) {
    // Laragon usa contraseña vacía por defecto
    $password = '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>