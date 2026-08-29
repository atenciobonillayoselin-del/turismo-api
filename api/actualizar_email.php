<?php
// api/actualizar_email.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit(0);
}

function logDebug($msg) {
  error_log("[actualizar_email] " . $msg);
}

logDebug("=== INICIO DE ACTUALIZACIÓN DE EMAIL ===");

$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
  $token = str_replace('Bearer ', '', $headers['Authorization']);
  logDebug("Token recibido: " . substr($token, 0, 20) . "...");
} else if (isset($headers['authorization'])) {
  $token = str_replace('Bearer ', '', $headers['authorization']);
  logDebug("Token recibido (lowercase): " . substr($token, 0, 20) . "...");
} else {
  logDebug("⚠️ No se recibió token de autorización");
}

$input = file_get_contents('php://input');
logDebug("Input recibido: " . $input);

$data = json_decode($input, true);
logDebug("Datos decodificados: " . json_encode($data));

if (!$data) {
  $data = $_POST;
  logDebug("Usando POST data: " . json_encode($data));
}

if (!$token) {
  echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
  exit;
}

if (!isset($data['email'])) {
  logDebug("❌ Faltan datos: email");
  echo json_encode([
    'success' => false,
    'error' => 'Faltan datos: email es requerido',
    'received' => $data
  ]);
  exit;
}

$newEmail = trim($data['email']);

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
  logDebug("❌ Email inválido: $newEmail");
  echo json_encode(['success' => false, 'error' => 'Email inválido']);
  exit;
}

logDebug("Procesando actualización de email a: $newEmail");

try {
  if (!$pdo) {
    logDebug("❌ Error de conexión a BD");
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
  }

  // Verificar token y obtener usuario actual
  $query = "SELECT id_usuario, email FROM usuario_sesion s 
            JOIN usuario u ON s.id_usuario = u.id_usuario 
            WHERE s.token = ? AND s.activo = 1 AND s.fecha_expiracion > NOW()";
  $stmt = $pdo->prepare($query);
  $stmt->execute([$token]);
  $usuario = $stmt->fetch();

  if (!$usuario) {
    logDebug("❌ Token inválido o expirado");
    echo json_encode(['success' => false, 'error' => 'Sesión inválida o expirada']);
    exit;
  }

  $currentEmail = $usuario['email'];
  $userId = $usuario['id_usuario'];

  logDebug("✅ Token válido, ID usuario: $userId, email actual: $currentEmail");

  // Verificar que el nuevo email no esté en uso por otro usuario
  $checkEmail = "SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?";
  $stmtCheck = $pdo->prepare($checkEmail);
  $stmtCheck->execute([$newEmail, $userId]);
  
  if ($stmtCheck->fetch()) {
    logDebug("❌ El email ya está en uso por otro usuario");
    echo json_encode(['success' => false, 'error' => 'El email ya está en uso por otro usuario']);
    exit;
  }

  // Actualizar email
  $updateSql = "UPDATE usuario SET email = ?, updated_at = NOW() WHERE id_usuario = ?";
  $stmtUpdate = $pdo->prepare($updateSql);
  $result = $stmtUpdate->execute([$newEmail, $userId]);

  if ($result) {
    logDebug("✅ Email actualizado correctamente");

    // Obtener datos actualizados del usuario
    $queryUser = "SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet, perfil_completo 
                  FROM usuario WHERE id_usuario = ?";
    $stmtUser = $pdo->prepare($queryUser);
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
      'success' => true,
      'mensaje' => 'Email actualizado correctamente',
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
  } else {
    logDebug("❌ Error al ejecutar UPDATE");
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el email']);
  }

} catch (PDOException $e) {
  logDebug("❌ PDOException: " . $e->getMessage());
  echo json_encode([
    'success' => false,
    'error' => 'Error en la base de datos: ' . $e->getMessage()
  ]);
} catch (Exception $e) {
  logDebug("❌ Exception: " . $e->getMessage());
  echo json_encode([
    'success' => false,
    'error' => 'Error: ' . $e->getMessage()
  ]);
}
?>