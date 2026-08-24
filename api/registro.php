<?php
// api/registro.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password']) || !isset($data['nombre'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$nombre = $data['nombre'];
$firebaseUid = $data['firebase_uid'] ?? null;
$telefono = $data['telefono'] ?? '';
$carnet = $data['carnet'] ?? '';
$perfilCompleto = isset($data['perfil_completo']) ? (int)$data['perfil_completo'] : 1;

try {
    // Verificar email
    $checkStmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }

    // Verificar nombre
    $checkNameStmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre = :nombre");
    $checkNameStmt->execute([':nombre' => $nombre]);
    if ($checkNameStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya está en uso']);
        exit;
    }

    // INSERTAR
    $sql = "INSERT INTO usuario (
                email, password_hash, nombre, firebase_uid, telefono, carnet,
                perfil_completo, rol, activo, created_at, last_login
            ) VALUES (
                :email, :password, :nombre, :firebase_uid, :telefono, :carnet,
                :perfil_completo, 'turista', 1, NOW(), NOW()
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':password' => $password,
        ':nombre' => $nombre,
        ':firebase_uid' => $firebaseUid,
        ':telefono' => $telefono,
        ':carnet' => $carnet,
        ':perfil_completo' => $perfilCompleto
    ]);

    $idUsuario = $pdo->lastInsertId();

    // Token
    $token = bin2hex(random_bytes(32));
    $stmtToken = $pdo->prepare("INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo)
                                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)");
    $stmtToken->execute([$idUsuario, $token]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $idUsuario,
            'email' => $email,
            'nombre' => $nombre,
            'rol' => 'turista',
            'telefono' => $telefono,
            'carnet' => $carnet,
            'perfil_completo' => $perfilCompleto
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
}
?>