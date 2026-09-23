<?php
// api/crear_tabla_sesion.php
// ------------------------------------------------------------------
// SCRIPT DE UN SOLO USO: crea la tabla `usuario_sesion`, que login.php,
// registro.php, registro_google.php, perfil_usuario.php, logout.php,
// actualizar_email.php y eliminar_usuario.php necesitan para guardar
// y validar los tokens de sesión, pero que no existe todavía en la
// base de datos (no aparece en respaldo_completo.sql).
//
// Úsalo UNA sola vez visitando esta URL en el navegador (o con curl),
// tanto en local (http://localhost:8000/api/crear_tabla_sesion.php)
// como en Render, una vez desplegado
// (https://TU-SERVICIO.onrender.com/api/crear_tabla_sesion.php).
// Después puedes borrar este archivo del repo si quieres.
// ------------------------------------------------------------------

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `usuario_sesion` (
            `id_sesion` INT NOT NULL AUTO_INCREMENT,
            `id_usuario` INT NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_expiracion` DATETIME NOT NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_sesion`),
            UNIQUE KEY `token` (`token`),
            KEY `id_usuario` (`id_usuario`),
            CONSTRAINT `usuario_sesion_ibfk_1`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Tabla usuario_sesion creada (o ya existía). Ya puedes probar login/registro/completar perfil.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo crear la tabla: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
