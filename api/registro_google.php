<?php
// api/registro_google.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
    logDebug("ERROR: Faltan datos requeridos");
    echo json_encode([
        'success' => false, 
        'error' => 'Faltan datos requeridos: email, nombre y firebase_uid son obligatorios'
    ]);
    exit;
}

try {
    // Verificar conexión a BD
    if (!$pdo) {
        logDebug("ERROR: No hay conexión a la base de datos");
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
        exit;
    }

    // Verificar/crear columnas necesarias
    $columns = ['telefono', 'carnet', 'perfil_completo', 'foto_perfil'];
    foreach ($columns as $col) {
        try {
            $check = $pdo->query("SHOW COLUMNS FROM usuario LIKE '$col'");
            if ($check->rowCount() == 0) {
                $type = $col === 'perfil_completo' ? 'TINYINT(1) NOT NULL DEFAULT 0' : 'VARCHAR(255) NULL DEFAULT NULL';
                $pdo->exec("ALTER TABLE usuario ADD COLUMN $col $type");
                logDebug("✅ Campo $col creado");
            }
        } catch (Exception $e) {
            logDebug("⚠️ Error al verificar/crear columna $col: " . $e->getMessage());
        }
    }

    // BUSCAR USUARIO EXISTENTE
    $query = "SELECT * FROM usuario WHERE firebase_uid = ? OR email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$firebaseUid, $email]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        logDebug("📌 Usuario existente encontrado: ID " . $usuarioExistente['id_usuario']);
        
        // Preservar nombre personalizado si existe
        $nombreFinal = $nombre;
        if (!empty($usuarioExistente['nombre']) && $usuarioExistente['nombre'] != 'Usuario') {
            $nombreFinal = $usuarioExistente['nombre'];
            logDebug("✅ Preservando nombre existente: $nombreFinal");
        }

        // Preservar perfil_completo si ya estaba en 1
        $perfilFinal = ($usuarioExistente['perfil_completo'] == 1) ? 1 : $perfilCompleto;
        logDebug("✅ perfil_completo final: $perfilFinal");

        // ACTUALIZAR USUARIO EXISTENTE
        // IMPORTANTE: telefono / carnet / foto solo se pisan si la app envía un
        // valor NO vacío. Antes se guardaban '' en cada login y se perdían los
        // datos que el usuario o el admin ya habían cargado.
        $query = "UPDATE usuario SET
                  nombre = ?,
                  firebase_uid = ?,
                  foto_perfil = COALESCE(NULLIF(?, ''), foto_perfil),
                  telefono = COALESCE(NULLIF(?, ''), telefono),
                  carnet = COALESCE(NULLIF(?, ''), carnet),
                  perfil_completo = ?,
                  activo = 1
                  WHERE id_usuario = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $nombreFinal,
            $firebaseUid,
            $photoUrl,
            $telefono,
            $carnet,
            $perfilFinal,
            $usuarioExistente['id_usuario']
        ]);

        $userId = $usuarioExistente['id_usuario'];
        $isNew = false;
        logDebug("✅ Usuario actualizado correctamente");

    } else {
        logDebug("📌 Creando nuevo usuario");
        
        // CREAR NUEVO USUARIO
        $query = "INSERT INTO usuario (
                    email, nombre, firebase_uid, foto_perfil,
                    telefono, carnet, perfil_completo,
                    password, rol, activo, created_at
                  ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, NULL, 'usuario', 1, NOW()
                  )";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $email, 
            $nombre, 
            $firebaseUid, 
            $photoUrl,
            $telefono !== '' ? $telefono : null,
            $carnet !== '' ? $carnet : null,
            $perfilCompleto
        ]);

        $userId = $pdo->lastInsertId();
        $isNew = true;
        logDebug("✅ Nuevo usuario creado con ID: $userId");
    }

    // GENERAR TOKEN
    $token = bin2hex(random_bytes(32));

    // Antes se desactivaban TODAS las sesiones del usuario en cada login, así que
    // cualquier login repetido (o dos dispositivos) invalidaba el token guardado
    // en el teléfono y la app pedía iniciar sesión de nuevo.
    // Ahora se conservan las 5 sesiones activas más recientes.
    $pdo->prepare("UPDATE usuario_sesion SET activo = 0
                    WHERE id_usuario = ? AND activo = 1
                      AND id_sesion NOT IN (
                          SELECT id_sesion FROM (
                              SELECT id_sesion FROM usuario_sesion
                               WHERE id_usuario = ? AND activo = 1
                               ORDER BY id_sesion DESC LIMIT 4
                          ) t
                      )")
        ->execute([$userId, $userId]);

    // Guardar nueva sesión
    $stmt = $pdo->prepare("INSERT INTO usuario_sesion (id_usuario, token, fecha_creacion, fecha_expiracion, activo)
                           VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)");
    $stmt->execute([$userId, $token]);

    // OBTENER DATOS FINALES DEL USUARIO
    $query = "SELECT id_usuario, email, nombre, rol, foto_perfil, telefono, carnet, perfil_completo 
              FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        logDebug("❌ Error: No se encontró el usuario después de guardar");
        echo json_encode(['success' => false, 'error' => 'Error al recuperar los datos del usuario']);
        exit;
    }

    // ✅ Asegurar que perfil_completo sea 0 o 1
    $perfilCompletoFinal = isset($usuario['perfil_completo']) ? (int)$usuario['perfil_completo'] : 0;

    // ✅ RESPONDER CON TODOS LOS DATOS
    $response = [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => (int)$usuario['id_usuario'],
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

    logDebug("✅ RESPUESTA ENVIADA: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    logDebug("❌ PDOException: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    logDebug("❌ Exception: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>