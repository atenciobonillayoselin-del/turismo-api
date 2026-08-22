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

// ===== FUNCIÓN DE LOG =====
function logDebug($msg) {
    error_log("[actualizar_usuario] " . $msg);
}

logDebug("=== INICIO DE ACTUALIZACIÓN ===");

// ===== LEER DATOS =====
$input = file_get_contents('php://input');
logDebug("Input recibido: " . $input);

$data = json_decode($input, true);
logDebug("Datos decodificados: " . json_encode($data));

// Si no hay datos en JSON, intentar con POST normal
if (!$data) {
    $data = $_POST;
    logDebug("Usando POST data: " . json_encode($data));
}

// ===== OBTENER TOKEN =====
$headers = getallheaders();
logDebug("Headers: " . json_encode($headers));

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

// ===== VALIDAR DATOS =====
if (!isset($data['email']) || !isset($data['nombre'])) {
    logDebug("❌ Faltan datos: email o nombre");
    echo json_encode([
        'success' => false, 
        'error' => 'Faltan datos: email y nombre son requeridos',
        'received' => $data
    ]);
    exit;
}

$email = trim($data['email']);
$nombre = trim($data['nombre']);
$telefono = trim($data['telefono'] ?? '');
$carnet = trim($data['carnet'] ?? '');

logDebug("Procesando: email=$email, nombre=$nombre, telefono=$telefono, carnet=$carnet");

try {
    // ===== VERIFICAR CONEXIÓN A BD =====
    if (!$pdo) {
        logDebug("❌ Error de conexión a BD");
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
        exit;
    }

    // ===== VERIFICAR TOKEN (si se envió) =====
    if ($token) {
        $query = "SELECT id_usuario FROM usuario_sesion WHERE token = ? AND activo = 1 AND fecha_expiracion > NOW()";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$token]);
        $sesion = $stmt->fetch();
        
        if (!$sesion) {
            logDebug("❌ Token inválido o expirado");
            echo json_encode(['success' => false, 'error' => 'Sesión inválida o expirada']);
            exit;
        }
        logDebug("✅ Token válido, ID usuario: " . $sesion['id_usuario']);
    } else {
        logDebug("⚠️ Continuando sin token (modo prueba)");
    }

    // ===== VERIFICAR SI EL USUARIO EXISTE =====
    $checkSql = "SELECT id_usuario FROM usuario WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':email' => $email]);
    $usuario = $checkStmt->fetch();
    
    if (!$usuario) {
        logDebug("❌ Usuario no encontrado: $email");
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    logDebug("✅ Usuario encontrado, ID: " . $usuario['id_usuario']);

    // ===== VERIFICAR SI LAS COLUMNAS EXISTEN =====
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM usuario")->fetchAll(PDO::FETCH_COLUMN);
        logDebug("Columnas en tabla usuario: " . json_encode($columns));
        
        if (!in_array('telefono', $columns)) {
            logDebug("⚠️ Columna 'telefono' no existe, creándola...");
            $pdo->exec("ALTER TABLE usuario ADD COLUMN telefono VARCHAR(20) NULL DEFAULT NULL");
            logDebug("✅ Columna 'telefono' creada");
        }
        
        if (!in_array('carnet', $columns)) {
            logDebug("⚠️ Columna 'carnet' no existe, creándola...");
            $pdo->exec("ALTER TABLE usuario ADD COLUMN carnet VARCHAR(20) NULL DEFAULT NULL");
            logDebug("✅ Columna 'carnet' creada");
        }
        
        if (!in_array('updated_at', $columns)) {
            logDebug("⚠️ Columna 'updated_at' no existe, creándola...");
            $pdo->exec("ALTER TABLE usuario ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            logDebug("✅ Columna 'updated_at' creada");
        }
    } catch (PDOException $e) {
        logDebug("⚠️ Error al verificar/crear columnas: " . $e->getMessage());
        // Continuamos de todas formas
    }

    // ===== ACTUALIZAR USUARIO =====
    $sql = "UPDATE usuario SET 
            nombre = :nombre, 
            telefono = :telefono, 
            carnet = :carnet
            WHERE email = :email";
    
    logDebug("SQL: " . $sql);
    logDebug("Params: nombre=$nombre, telefono=$telefono, carnet=$carnet, email=$email");
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':nombre' => $nombre,
        ':telefono' => $telefono,
        ':carnet' => $carnet,
        ':email' => $email
    ]);

    if ($result) {
        logDebug("✅ Usuario actualizado correctamente");
        
        // Obtener datos actualizados
        $query = "SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet FROM usuario WHERE email = ?";
        $stmt = $pdo->prepare($query);
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
                'carnet' => $userData['carnet'] ?? ''
            ]
        ]);
    } else {
        logDebug("❌ Error al ejecutar UPDATE");
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el usuario']);
    }

} catch (PDOException $e) {
    logDebug("❌ PDOException: " . $e->getMessage());
    logDebug("❌ Código: " . $e->getCode());
    echo json_encode([
        'success' => false, 
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    logDebug("❌ Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>