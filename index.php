<?php
// index.php - Punto de entrada para Render
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API de Turismo La Paz funcionando correctamente',
    'version' => '1.0.0',
    'endpoints' => [
        'test' => '/api/test_connection.php',
        'login' => '/api/login.php',
        'registro' => '/api/registro.php',
        'registro_google' => '/api/registro_google.php',
        'actualizar_usuario' => '/api/actualizar_usuario.php',
        'perfil_usuario' => '/api/perfil_usuario.php',
        'lugares' => '/api/lugares.php',
        'rutas' => '/api/rutas.php',
    ]
]);
?>