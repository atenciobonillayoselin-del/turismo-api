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
    'documentacion' => [
        'sincronizacion' => [
            'descripcion' => 'Sincroniza datos de uMap → MySQL (anti-bloqueo 403 con 4 niveles de fallback)',
            'endpoint'    => '/api/sincronizar.php',
            'metodo'      => 'GET | POST',
            'estrategia'  => [
                'NIVEL 1' => 'Descarga DIRECTA a umap.openstreetmap.fr',
                'NIVEL 2' => 'Proxy Cloudflare Worker (env UMAP_PROXY_URL)',
                'NIVEL 3' => 'Caché LOCAL: data/umap_cache/*.json (actualizado por GitHub Action cada 15 min)',
                'NIVEL 4' => 'Caché REMOTA en GitHub Raw (env GITHUB_RAW_BASE)',
            ],
            'env_vars' => [
                'UMAP_PROXY_URL'  => 'https://tu-worker.workers.dev/?url=',
                'GITHUB_RAW_BASE' => 'https://raw.githubusercontent.com/USUARIO/REPO/BRANCH',
                'UMAP_TOKEN'      => 'Cookie de sesión uMap (opcional)',
            ],
        ],
        'github_action' => [
            'descripcion' => 'Workflow que descarga GeoJSONs de uMap cada 15 min y actualiza el caché del repo',
            'archivo'     => '.github/workflows/sincronizar-umap.yml',
            'cron'        => '*/15 * * * *',
            'opcional'    => 'Agrega Secret RENDER_SYNC_URL para auto-trigger /api/sincronizar.php después de commit',
        ],
    ],
    'endpoints' => [
        'Diagnóstico' => [
            'GET /'                          => 'Esta información',
            'GET /api/test_connection.php'   => 'Prueba conexión MySQL + extensiones PHP',
            'GET /api/sincronizar.php'       => '🔄 SINCRONIZAR uMap → MySQL (usa estrategia anti-403)',
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
    'mapa_uMap' => [
        'id'        => 1447967,
        'url'       => 'https://umap.openstreetmap.fr/es/map/rutaslapaz_1447967',
        'capas_cantidad' => 7,
        'capas_ids' => [
            '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA (Mirador Montículo)',
            '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA (Mirador Montículo)',
            '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA (Mirador Killi Killi)',
            'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA (Plaza Villarroel)',
            'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA (Plaza Villarroel)',
            '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA (Parque Laikakota)',
            '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA (Parque Laikakota)',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
