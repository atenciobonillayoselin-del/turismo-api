<?php
// api/registro_google.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

function logDebug($msg) {
    error_log("[registro_google] " . $msg);
}

logDebug("=== INICIO DE REGISTRO GOOGLE ===");

$data = json_decode(file_get_contents('php://input'), true);
logDebug("Datos recibidos: " . json_encode($data));

if (!$data) {
    logDebug("ERROR: Datos inválidos");
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$email = $data['email'] ?? '';
$nombre = $data['nombre'] ?? '';
$firebaseUid = $data['firebase_uid'] ?? '';
$photoUrl = $data['photo_url'] ?? '';
$telefono = $data['telefono'] ?? '';
$carnet = $data['carnet'] ?? '';
$perfilCompleto = isset($data['perfil_completo']) ? (int)$data['perfil_completo'] : 0;

if (empty($email) || empty($nombre) || empty($firebaseUid)) {
    logDebug("ERROR: Faltan datos");
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

try {
    // Verificar si la tabla existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'usuario'");
    if ($checkTable->rowCount() == 0) {
        logDebug("ERROR: Tabla 'usuario' no existe");
        echo json_encode(['success' => false, 'error' => 'Tabla usuario no existe']);
        exit;
    }

    // Verificar/crear columnas necesarias
    $columns = ['telefono', 'carnet', 'perfil_completo', 'foto_perfil'];
    foreach ($columns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM usuario LIKE '$col'");
        if ($check->rowCount() == 0) {
            $type = $col === 'perfil_completo' ? 'TINYINT(1) NOT NULL DEFAULT 0' : 'VARCHAR(255) NULL DEFAULT NULL';
            $pdo->exec("ALTER TABLE usuario ADD COLUMN $col $type");
            logDebug("✅ Campo $col creado");
        }
    }

    // Buscar usuario existente
    $query = "SELECT * FROM usuario WHERE firebase_uid = ? OR email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$firebaseUid, $email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        // ✅ PRESERVAR nombre personalizado
        $nombreFinal = $nombre;
        if (!empty($usuarioExistente['nombre']) && $usuarioExistente['nombre'] != 'Usuario') {
            $nombreFinal = $usuarioExistente['nombre'];
            logDebug("✅ Preservando nombre: $nombreFinal");
        }

        // ✅ PRESERVAR perfil_completo si ya estaba en 1
        $perfilFinal = ($usuarioExistente['perfil_completo'] == 1) ? 1 : $perfilCompleto;
        logDebug("✅ perfil_completo final: $perfilFinal");

        $query = "UPDATE usuario SET
                  nombre = ?,
                  email = ?,
                  firebase_uid = ?,
                  foto_perfil = ?,
                  telefono = ?,
                  carnet = ?,
                  perfil_completo = ?,
                  last_login = NOW(),
                  activo = 1
                  WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $nombreFinal, $email, $firebaseUid, $photoUrl,
            $telefono, $carnet, $perfilFinal, $usuarioExistente['id_usuario']
        ]);

        $userId = $usuarioExistente['id_usuario'];
        $isNew = false;
        logDebug("✅ Usuario actualizado: $email, perfil_completo: $perfilFinal");

    } else {
        // CREAR NUEVO USUARIO
        $query = "INSERT INTO usuario (
                    email, nombre, firebase_uid, foto_perfil,
                    telefono, carnet, perfil_completo,
                    password_hash, rol, activo, created_at, last_login
                  ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, '', 'turista', 1, NOW(), NOW()
                  )";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $email, $nombre, $firebaseUid, $photoUrl,
            $telefono, $carnet, $perfilCompleto
        ]);

        $userId = $pdo->lastInsertId();
        $isNew = true;
        logDebug("✅ Nuevo usuario creado: $email, perfil_completo: $perfilCompleto");
    }

    // Generar token
    $token = bin2hex(random_bytes(32));

    // Desactivar sesiones anteriores
    $pdo->prepare("UPDATE usuario_sesion SET activo = 0 WHERE id_usuario = ?")
        ->execute([$userId]);

    // Guardar nueva sesión
    $stmt = $pdo->prepare("INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo)
                           VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)");
    $stmt->execute([$userId, $token]);

    // Obtener datos finales CON TODOS LOS CAMPOS
    $query = "SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet, perfil_completo 
              FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ Asegurarse de que perfil_completo sea un entero (0 o 1)
    $perfilCompletoFinal = isset($usuario['perfil_completo']) ? (int)$usuario['perfil_completo'] : 0;

    $response = [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $usuario['id_usuario'],
            'email' => $usuario['email'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'foto_perfil' => $usuario['foto_perfil'] ?? $photoUrl,
            'telefono' => $usuario['telefono'] ?? '',
            'carnet' => $usuario['carnet'] ?? '',
            'perfil_completo' => $perfilCompletoFinal
        ],
        'is_new' => $isNew
    ];

    logDebug("RESPUESTA: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    logDebug("❌ PDOException: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
} catch (Exception $e) {
    logDebug("❌ Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>