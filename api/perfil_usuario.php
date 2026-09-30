<?php
// api/perfil_usuario.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
// El perfil debe ser SIEMPRE fresco (lo edita el admin desde Filament)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once __DIR__ . '/_auth.php';

$token = obtenerTokenDeRequest();

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'code' => 'NO_TOKEN', 'error' => 'Token no proporcionado']);
    exit;
}

try {
    $usuario = usuarioDesdeToken($pdo, $token);

    if ($usuario) {
        echo json_encode(['success' => true, 'user' => usuarioComoJson($usuario)]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'code' => 'SESION_INVALIDA', 'error' => 'Sesión inválida o expirada']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'code' => 'DB_ERROR', 'error' => 'Error en BD: ' . $e->getMessage()]);
}
