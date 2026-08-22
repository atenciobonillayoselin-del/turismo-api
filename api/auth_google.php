<?php
// api/auth_google.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$email = $data['email'] ?? '';
$nombre = $data['nombre'] ?? '';
$firebaseUid = $data['firebase_uid'] ?? '';
$photoUrl = $data['photo_url'] ?? '';

// Validaciones
if (empty($email) || empty($nombre) || empty($firebaseUid)) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

try {
    // Verificar si el usuario ya existe por firebase_uid o email
    $query = "SELECT * FROM usuario WHERE firebase_uid = ? OR email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$firebaseUid, $email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        // Actualizar usuario existente
        $query = "UPDATE usuario SET 
                  nombre = ?, 
                  email = ?, 
                  firebase_uid = ?,
                  last_login = NOW(),
                  activo = 1
                  WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nombre, $email, $firebaseUid, $usuarioExistente['id_usuario']]);

        $userId = $usuarioExistente['id_usuario'];
        $isNew = false;
    } else {
        // Crear nuevo usuario (sin password_hash porque viene de Google)
        $query = "INSERT INTO usuario (email, nombre, firebase_uid, rol, activo, created_at, last_login) 
                  VALUES (?, ?, ?, 'turista', 1, NOW(), NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email, $nombre, $firebaseUid]);
        $userId = $pdo->lastInsertId();
        $isNew = true;
    }

    // Generar token para sesión
    $token = bin2hex(random_bytes(32));
    
    // Guardar token en usuario_sesion
    $query = "INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo) 
              VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $token]);

    // Obtener datos del usuario
    $query = "SELECT id_usuario, email, nombre, rol FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Registrar en tabla de sincronización
    $query = "INSERT INTO sincronizacion_google (fecha_sincronizacion, estado, usuario_ejecuto) 
              VALUES (NOW(), 'COMPLETADO', ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $usuario['id_usuario'],
            'email' => $usuario['email'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol']
        ],
        'is_new' => $isNew
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>