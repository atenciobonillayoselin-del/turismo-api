<?php
// api/eliminar_usuario.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit(0);
}

function logDebug($msg) {
  error_log("[eliminar_usuario] " . $msg);
}

logDebug("=== INICIO DE ELIMINACIÓN DE USUARIO ===");

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

if (!$token) {
  echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
  exit;
}

try {
  if (!$pdo) {
    logDebug("❌ Error de conexión a BD");
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
  }

  // Verificar token y obtener usuario
  $query = "SELECT id_usuario FROM usuario_sesion WHERE token = ? AND activo = 1 AND fecha_expiracion > NOW()";
  $stmt = $pdo->prepare($query);
  $stmt->execute([$token]);
  $sesion = $stmt->fetch();

  if (!$sesion) {
    logDebug("❌ Token inválido o expirado");
    echo json_encode(['success' => false, 'error' => 'Sesión inválida o expirada']);
    exit;
  }

  $userId = $sesion['id_usuario'];
  logDebug("✅ Token válido, ID usuario: $userId");

  // Iniciar transacción
  $pdo->beginTransaction();

  try {
    // Eliminar sesiones del usuario
    $deleteSesiones = "DELETE FROM usuario_sesion WHERE id_usuario = ?";
    $stmtSesiones = $pdo->prepare($deleteSesiones);
    $stmtSesiones->execute([$userId]);
    logDebug("✅ Sesiones eliminadas");

    // Eliminar usuario
    $deleteUsuario = "DELETE FROM usuario WHERE id_usuario = ?";
    $stmtUsuario = $pdo->prepare($deleteUsuario);
    $stmtUsuario->execute([$userId]);
    logDebug("✅ Usuario eliminado");

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
      'success' => true,
      'mensaje' => 'Usuario eliminado correctamente'
    ]);
    logDebug("✅ Usuario eliminado correctamente");

  } catch (Exception $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    logDebug("❌ Error durante la eliminación, rollback realizado: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al eliminar usuario: ' . $e->getMessage()]);
  }

} catch (PDOException $e) {
  logDebug("❌ PDOException: " . $e->getMessage());
  echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
  logDebug("❌ Exception: " . $e->getMessage());
  echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>