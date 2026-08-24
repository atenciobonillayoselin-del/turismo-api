<?php
// api/actualizar_usuario.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

function logDebug($msg) {
    error_log("[actualizar_usuario] " . $msg);
}

logDebug("=== INICIO DE ACTUALIZACIÓN ===");

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

if (!isset($data['email']) || !isset($data['nombre'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Faltan datos: email y nombre son requeridos'
    ]);
    exit;
}

$email = trim($data['email']);
$nombre = trim($data['nombre']);
$telefono = trim($data['telefono'] ?? '');
$carnet = trim($data['carnet'] ?? '');
$perfilCompleto = isset($data['perfil_completo']) ? (int)$data['perfil_completo'] : 0;

logDebug("Procesando: email=$email, nombre=$nombre, perfil_completo=$perfilCompleto");

try {
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Error de conexión a BD']);
        exit;
    }

    // Verificar/crear columnas
    $columns = ['telefono', 'carnet', 'perfil_completo'];
    foreach ($columns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM usuario LIKE '$col'");
        if ($check->rowCount() == 0) {
            $type = $col === 'perfil_completo' ? 'TINYINT(1) NOT NULL DEFAULT 0' : 'VARCHAR(255) NULL DEFAULT NULL';
            $pdo->exec("ALTER TABLE usuario ADD COLUMN $col $type");
            logDebug("✅ Campo $col creado");
        }
    }

    // Verificar si usuario existe
    $checkStmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    // ACTUALIZAR
    $sql = "UPDATE usuario SET
            nombre = :nombre,
            telefono = :telefono,
            carnet = :carnet,
            perfil_completo = :perfil_completo
            WHERE email = :email";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':telefono' => $telefono,
        ':carnet' => $carnet,
        ':perfil_completo' => $perfilCompleto,
        ':email' => $email
    ]);

    // Obtener datos actualizados
    $stmt = $pdo->prepare("SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet, perfil_completo 
                           FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Usuario actualizado correctamente',
        'user' => [
            'id' => $userData['id_usuario'],
            'email' => $userData['email'],
            'nombre' => $userData['nombre'],
            'rol' => $userData['rol'],
            'foto_perfil' => $userData['foto_perfil'] ?? '',
            'telefono' => $userData['telefono'] ?? '',
            'carnet' => $userData['carnet'] ?? '',
            'perfil_completo' => (int)($userData['perfil_completo'] ?? 0)
        ]
    ]);

} catch (PDOException $e) {
    logDebug("❌ Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
}
?>