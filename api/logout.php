<?php
// api/logout.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
    exit;
}

try {
    $query = "UPDATE usuario_sesion SET activo = 0 WHERE token = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$token]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cerrar sesión: ' . $e->getMessage()]);
}
?>