<?php
/**
 * index.php - Punto de entrada principal
 * Turismo La Paz API - Sincronización uMap → MySQL Aiven
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

echo json_encode([
    'success' => true,
    'nombre'  => 'API Turístico La Paz',
    'version' => '2.0.0',
    'fecha'   => date('Y-m-d H:i:s'),
    'endpoints' => [
        'Diagnóstico' => [
            'GET /'                          => 'Esta información',
            'GET /api/test_connection.php'   => 'Prueba conexión MySQL + extensiones PHP',
            'POST /api/vaciar_base_datos.php' => 'Vaciar todas las tablas de la base de datos',
        ],
        'Autenticación' => [
            'POST /api/registro.php'         => 'Crear usuario con email/password',
            'POST /api/registro_google.php'  => 'Crear/Login con Firebase UID',
            'POST /api/login.php'            => 'Iniciar sesión',
            'POST /api/logout.php'           => 'Cerrar sesión',
            'GET  /api/verificar_nombre.php' => 'Verificar si nombre está disponible',
        ],
        'Usuarios' => [
            'GET    /api/perfil_usuario.php'     => 'Obtener perfil',
            'PUT    /api/actualizar_usuario.php' => 'Actualizar perfil',
            'PUT    /api/actualizar_email.php'   => 'Cambiar email',
            'DELETE /api/eliminar_usuario.php'   => 'Desactivar cuenta',
        ],
        'Lugares Turísticos' => [
            'GET /api/lugares.php'                    => 'Lista JSON de lugares',
            'GET /api/geojson_lugares.php'           => 'GeoJSON FeatureCollection (puntos)',
            'GET /api/geojson_lugar_por_id.php?id=N'  => 'GeoJSON de un lugar por ID',
        ],
        'Rutas' => [
            'GET    /api/rutas.php'                            => 'CRUD de rutas (JSON)',
            'GET    /api/geojson_rutas.php'                    => 'GeoJSON de TODAS las rutas (LineStrings)',
            'GET    /api/geojson_ruta_por_id.php?id_ruta=N'    => 'GeoJSON de ruta por ID o nombre',
            'GET    /api/geojson_rutas_por_lugar.php?grupo=X'  => 'Rutas que pasan por un lugar/grupo',
            'GET    /api/ruta_lugar.php'                       => 'Relación ruta ↔ lugar (N:M)',
            'GET    /api/ruta_parada.php'                      => 'Relación ruta ↔ parada con orden',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
