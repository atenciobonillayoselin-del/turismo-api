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

// LOG para debugging
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

// Validaciones
if (empty($email) || empty($nombre) || empty($firebaseUid)) {
    logDebug("ERROR: Faltan datos - email: $email, nombre: $nombre, firebaseUid: $firebaseUid");
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

logDebug("Procesando: $email, $nombre, $firebaseUid, telefono: $telefono, carnet: $carnet, perfil_completo: $perfilCompleto");

try {
    // ===== PRIMERO: Verificar si la tabla existe =====
    $checkTable = $pdo->query("SHOW TABLES LIKE 'usuario'");
    if ($checkTable->rowCount() == 0) {
        logDebug("ERROR: Tabla 'usuario' no existe");
        echo json_encode(['success' => false, 'error' => 'Tabla usuario no existe en la base de datos']);
        exit;
    }

    // ===== VERIFICAR SI LOS CAMPOS EXISTEN =====
    $checkTelefono = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'telefono'");
    if ($checkTelefono->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuario ADD COLUMN telefono VARCHAR(20) NULL DEFAULT NULL");
        logDebug("✅ Campo telefono creado");
    }

    $checkCarnet = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'carnet'");
    if ($checkCarnet->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuario ADD COLUMN carnet VARCHAR(20) NULL DEFAULT NULL");
        logDebug("✅ Campo carnet creado");
    }

    $checkPerfilCompleto = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'perfil_completo'");
    if ($checkPerfilCompleto->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuario ADD COLUMN perfil_completo TINYINT(1) NOT NULL DEFAULT 0");
        logDebug("✅ Campo perfil_completo creado");
    }

    $checkField = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'foto_perfil'");
    if ($checkField->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuario ADD COLUMN foto_perfil VARCHAR(500) NULL DEFAULT NULL AFTER firebase_uid");
        logDebug("✅ Campo foto_perfil creado");
    }

    // ===== BUSCAR POR FIREBASE_UID O EMAIL =====
    $query = "SELECT * FROM usuario WHERE firebase_uid = ? OR email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$firebaseUid, $email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    logDebug("Usuario existente: " . json_encode($usuarioExistente));

    if ($usuarioExistente) {
        // ===== ACTUALIZAR USUARIO EXISTENTE =====
        // ✅ PRESERVAR nombre personalizado si el usuario ya completo su perfil
        $nombreFinal = $nombre;
        if (!empty($usuarioExistente['nombre']) && $usuarioExistente['nombre'] != 'Usuario') {
            $nombreFinal = $usuarioExistente['nombre'];
            logDebug("✅ Preservando nombre existente: $nombreFinal");
        }

        // ✅ PRESERVAR perfil_completo si ya estaba en 1
        $perfilFinal = ($usuarioExistente['perfil_completo'] == 1) ? 1 : $perfilCompleto;

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
        $result = $stmt->execute([
            $nombreFinal,
            $email,
            $firebaseUid,
            $photoUrl,
            $telefono,
            $carnet,
            $perfilFinal,
            $usuarioExistente['id_usuario']
        ]);

        if (!$result) {
            logDebug("ERROR al actualizar: " . json_encode($stmt->errorInfo()));
        }

        $userId = $usuarioExistente['id_usuario'];
        $isNew = false;

        logDebug("✅ Usuario actualizado: $email (UID: $firebaseUid) ID: $userId, perfil_completo: $perfilFinal, nombre: $nombreFinal");

    } else {
        // ===== CREAR NUEVO USUARIO =====
        $query = "INSERT INTO usuario (
                    email,
                    nombre,
                    firebase_uid,
                    foto_perfil,
                    telefono,
                    carnet,
                    perfil_completo,
                    password_hash,
                    rol,
                    activo,
                    created_at,
                    last_login
                  ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, '', 'turista', 1, NOW(), NOW()
                  )";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            $email,
            $nombre,
            $firebaseUid,
            $photoUrl,
            $telefono,
            $carnet,
            $perfilCompleto
        ]);

        if (!$result) {
            logDebug("ERROR al insertar: " . json_encode($stmt->errorInfo()));
            echo json_encode(['success' => false, 'error' => 'Error al insertar usuario: ' . json_encode($stmt->errorInfo())]);
            exit;
        }

        $userId = $pdo->lastInsertId();
        $isNew = true;

        logDebug("✅ Nuevo usuario creado: $email (UID: $firebaseUid) ID: $userId, perfil_completo: $perfilCompleto");
    }

    // ===== GENERAR TOKEN =====
    $token = bin2hex(random_bytes(32));

    // ===== GUARDAR SESIÓN =====
    $deactivateSql = "UPDATE usuario_sesion SET activo = 0 WHERE id_usuario = ?";
    $deactivateStmt = $pdo->prepare($deactivateSql);
    $deactivateStmt->execute([$userId]);

    $query = "INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo)
              VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$userId, $token]);

    if (!$result) {
        logDebug("ERROR al guardar sesión: " . json_encode($stmt->errorInfo()));
    }

    // ===== OBTENER DATOS DEL USUARIO =====
    $query = "SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet, perfil_completo FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    logDebug("Usuario final: " . json_encode($usuario));

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
            'perfil_completo' => (int)($usuario['perfil_completo'] ?? 0)
        ],
        'is_new' => $isNew
    ];

    logDebug("RESPUESTA: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    logDebug("❌ PDOException: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    logDebug("❌ Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
