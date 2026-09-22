<?php
/**
 * crear_admin.php - Crea un usuario administrador en la base de datos
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST']);
    exit;
}

try {
    // Generar credenciales aleatorias seguras
    $email = 'admin@turismolapaz.com';
    $password = generateSecurePassword();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $nombre = 'Administrador';
    
    // Verificar si ya existe un admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existe = $stmt->fetch();
    
    if ($existe) {
        // Actualizar contraseña si ya existe
        $stmt = $pdo->prepare("UPDATE users SET password = ?, name = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $nombre, $email]);
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Usuario administrador actualizado',
            'email' => $email,
            'password' => $password,
            'accion' => 'actualizado'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Crear nuevo usuario admin
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$email, $passwordHash, $nombre]);
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Usuario administrador creado exitosamente',
            'email' => $email,
            'password' => $password,
            'accion' => 'creado'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateSecurePassword($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
