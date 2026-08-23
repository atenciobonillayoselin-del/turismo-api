<?php
// api/login.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    $sql = "SELECT id_usuario, email, nombre, password_hash, rol, firebase_uid, perfil_completo
            FROM usuario
            WHERE email = :email AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
        exit;
    }

    $sqlUpdate = "UPDATE usuario SET last_login = NOW() WHERE id_usuario = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([':id' => $usuario['id_usuario']]);

    $token = bin2hex(random_bytes(32));

    $sqlToken = "INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
    $stmtToken = $pdo->prepare($sqlToken);
    $stmtToken->execute([$usuario['id_usuario'], $token]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $usuario['id_usuario'],
            'email' => $usuario['email'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'perfil_completo' => (int)($usuario['perfil_completo'] ?? 0)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
