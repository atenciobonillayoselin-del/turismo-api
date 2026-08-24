<?php
// api/perfil_usuario.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    $query = "SELECT
                u.id_usuario,
                u.email,
                u.nombre,
                u.rol,
                u.firebase_uid,
                u.foto_perfil,
                u.telefono,
                u.carnet,
                u.perfil_completo
              FROM usuario_sesion s
              JOIN usuario u ON s.id_usuario = u.id_usuario
              WHERE s.token = ? AND s.activo = 1 AND s.fecha_expiracion > NOW()";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $usuario['id_usuario'],
                'email' => $usuario['email'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol'],
                'firebase_uid' => $usuario['firebase_uid'],
                'foto_perfil' => $usuario['foto_perfil'] ?? '',
                'telefono' => $usuario['telefono'] ?? '',
                'carnet' => $usuario['carnet'] ?? '',
                'perfil_completo' => (int)($usuario['perfil_completo'] ?? 0)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Sesión inválida o expirada']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
}
?>