<?php
// api/logout.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once __DIR__ . '/_auth.php';

$token = obtenerTokenDeRequest();

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
    exit;
}

try {
    // Solo se cierra ESTA sesión (otros dispositivos siguen activos)
    $pdo->prepare("UPDATE usuario_sesion SET activo = 0 WHERE token = ?")->execute([$token]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cerrar sesión: ' . $e->getMessage()]);
}
