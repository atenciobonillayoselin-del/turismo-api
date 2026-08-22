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
    echo json_encode(['success' => false, 'error' => 'Faltan datos: email, password y nombre son requeridos']);
    exit;
}

$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$nombre = $data['nombre'];
$firebaseUid = $data['firebase_uid'] ?? null;
$telefono = $data['telefono'] ?? '';  // ✅ NUEVO
$carnet = $data['carnet'] ?? '';      // ✅ NUEVO

try {
    // Verificar si el email ya existe
    $checkSql = "SELECT id_usuario FROM usuario WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }

    // Verificar si el nombre ya existe
    $checkNameSql = "SELECT id_usuario FROM usuario WHERE nombre = :nombre";
    $checkNameStmt = $pdo->prepare($checkNameSql);
    $checkNameStmt->execute([':nombre' => $nombre]);
    
    if ($checkNameStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya está en uso']);
        exit;
    }

    // Insertar nuevo usuario
    $sql = "INSERT INTO usuario (
                email, 
                password_hash, 
                nombre, 
                firebase_uid, 
                telefono,
                carnet,
                rol, 
                activo, 
                created_at, 
                last_login
            ) VALUES (
                :email, :password, :nombre, :firebase_uid, :telefono, :carnet, 'turista', 1, NOW(), NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':password' => $password,
        ':nombre' => $nombre,
        ':firebase_uid' => $firebaseUid,
        ':telefono' => $telefono,
        ':carnet' => $carnet
    ]);

    $idUsuario = $pdo->lastInsertId();

    // Generar token
    $token = bin2hex(random_bytes(32));
    
    // Guardar sesión
    $sqlToken = "INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo) 
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
    $stmtToken = $pdo->prepare($sqlToken);
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
            'carnet' => $carnet
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>