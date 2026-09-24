<?php
/**
 * geojson_lugar_por_id.php - GeoJSON de un lugar turístico específico
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit(); }

$idLugar = isset($_GET['id_lugar']) ? (int) $_GET['id_lugar'] : 0;
$grupo   = isset($_GET['grupo'])    ? trim($_GET['grupo']) : '';

if ($idLugar <= 0 && empty($grupo)) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => 'Se requiere ?id_lugar=X o ?grupo=NombreDelLugar',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $where = ["lt.activo = 1"];
    $params = [];
    if ($idLugar > 0) {
        $where[] = "lt.id_lugar = :id";
        $params[':id'] = $idLugar;
    } else {
        $where[] = "lt.nombre LIKE :g1";
        $params[':g1'] = "%{$grupo}%";
    }

    $sql = "SELECT lt.id_lugar, lt.nombre, lt.descripcion, lt.descripcion_corta, lt.latitud, lt.longitud,
                   lt.direccion, lt.calificacion, lt.costo, lt.es_gratuito, lt.abierto_todos_los_dias, lt.horarios,
                   lt.tipo_transporte, lt.created_at, lt.updated_at, lt.activo,
                   cl.nombre as categoria, cl.slug as categoria_slug, cl.icono as categoria_icono
            FROM lugar_turistico lt
            LEFT JOIN categoria_lugar cl ON lt.id_categoria = cl.id_categoria
            WHERE " . implode(' AND ', $where) . "
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $data = [];
    if ($row) {
        $lat = (float)$row['latitud'];
        $lng = (float)$row['longitud'];
        if ($lat !== 0.0 && $lng !== 0.0) {
            $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/','',$row['nombre'])) ?: $row['nombre'];

            // Obtener multimedia del lugar
            $idLugarDb = (int)$row['id_lugar'];
            $stmtMedia = $pdo->prepare("SELECT tipo, url, descripcion FROM lugar_multimedia WHERE id_lugar = :id_lugar AND activo = 1 ORDER BY orden ASC");
            $stmtMedia->execute([':id_lugar' => $idLugarDb]);
            $mediaItems = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

            $panoramaUrl = '';
            $fotoUrl = '';
            $fotos = [];
            foreach ($mediaItems as $media) {
                if ($media['tipo'] === '360' && empty($panoramaUrl)) {
                    $panoramaUrl = $media['url'];
                }
                if ($media['tipo'] === 'imagen') {
                    if (empty($fotoUrl)) {
                        $fotoUrl = $media['url'];
                    }
                    $fotos[] = $media['url'];
                }
            }

            $data[] = [
                'id_lugar' => (int)$row['id_lugar'],
                'nombre' => $nombreLimpio,
                'descripcion' => $row['descripcion'] ?? '',
                'latitud' => $lat,
                'longitud' => $lng,
                'categoria' => $row['categoria'] ?? '',
                'foto_url' => $fotoUrl,
                'panorama_url' => $panoramaUrl,
                'direccion' => $row['direccion'] ?? '',
                'activo' => (int)$row['activo'],
                'fotos' => $fotos,
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'total' => 0,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}