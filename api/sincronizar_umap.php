<?php
/**
 * sincronizar_umap.php - VERSIÓN AUTOCONTENIDA
 * Sincroniza uMap con MySQL usando la URL correcta
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// =========================================================================
// CONFIGURACIÓN DE BD
// =========================================================================
$DB_HOST = getenv('PDO_HOST') ?: 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = getenv('PDO_PORT') ?: 23909;
$DB_NAME = getenv('PDO_DATABASE') ?: 'defaultdb';
$DB_USER = getenv('PDO_USERNAME') ?: 'avnadmin';
$DB_PASS = getenv('PDO_PASSWORD') ?: '';

if (empty($DB_PASS)) {
    echo json_encode(['success' => false, 'error' => 'PDO_PASSWORD no configurada']);
    exit;
}

// =========================================================================
// CONEXIÓN A BD
// =========================================================================
try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'BD: ' . $e->getMessage()]);
    exit;
}

// =========================================================================
// CONFIGURACIÓN DE UMAP
// =========================================================================
const UMAP_ID = '1447967';
const UMAP_BASE = 'https://umap.openstreetmap.fr/en/datalayer/';

// 🟢 LISTA DE CAPAS A SINCRONIZAR
const DATALAYER_IDS = [
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc', // Minibus 254 - IDA (Mirador Montículo)
    '1131cb1a-631f-4d7b-8f33-f46a469366f9', // Minibus 254 - VUELTA (Mirador Montículo)
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd', // Minibus 204 - IDA (MIRADOR KILLI KILLI)
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47', // Minibus 889 - IDA (PLAZA VILLAROEL)
    'fa904f68-9ee2-4e12-b3a4-8406f357def5', // Minibus 889 - VUELTA (PLAZA VILLAROEL)
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', // Minibus 364 - IDA (PARQUE LAICACOTA)
    '291c212e-44db-4460-b84e-773bcfede107', // Minibus 364 - VUELTA (PARQUE LAICACOTA)
];

$stats = [
    'total_capas' => 0,
    'rutas' => 0,
    'paradas' => 0,
    'lugares' => 0,
    'warnings' => [],
];

// =========================================================================
// FUNCIONES
// =========================================================================

function descargarGeoJSON($datalayerId) {
    $url = UMAP_BASE . UMAP_ID . '/' . $datalayerId . '/';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/geo+json, application/json',
        'Accept-Language: es-ES,es;q=0.9',
        'Referer: https://umap.openstreetmap.fr/',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

// =========================================================================
// EJECUCIÓN
// =========================================================================

try {
    // Limpiar tablas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE ruta_lugar");
    $pdo->exec("TRUNCATE TABLE ruta");
    $pdo->exec("TRUNCATE TABLE lugar_turistico");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    foreach (DATALAYER_IDS as $datalayerId) {
        $stats['total_capas']++;
        $data = descargarGeoJSON($datalayerId);
        
        if (!$data) {
            $stats['warnings'][] = "No se pudo descargar: $datalayerId";
            continue;
        }
        
        $features = $data['features'] ?? [];
        $nombreCapa = $data['properties']['name'] ?? 'Capa sin nombre';
        
        foreach ($features as $feature) {
            $geom = $feature['geometry'] ?? [];
            $props = $feature['properties'] ?? [];
            $type = $geom['type'] ?? '';
            
            if ($type === 'LineString') {
                // RUTA
                $coords = $geom['coordinates'] ?? [];
                if (count($coords) < 2) continue;
                
                $nombre = $props['name'] ?? $nombreCapa;
                $color = stripos($nombre, 'vuelta') !== false ? '#2980B9' : '#E74C3C';
                
                $sql = "INSERT INTO ruta (nombre, color_hex, id_umap, activo)
                        VALUES (:nombre, :color, :id_umap, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':color' => $color,
                    ':id_umap' => $datalayerId
                ]);
                $idRuta = $pdo->lastInsertId();
                $stats['rutas']++;
                
            } elseif ($type === 'Point') {
                // LUGAR
                $coord = $geom['coordinates'] ?? [];
                if (count($coord) < 2) continue;
                
                $nombre = $props['name'] ?? 'Lugar sin nombre';
                
                $sql = "INSERT INTO lugar_turistico 
                        (nombre, latitud, longitud, id_umap, activo)
                        VALUES (:nombre, :lat, :lng, :id_umap, 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':lat' => (float)$coord[1],
                    ':lng' => (float)$coord[0],
                    ':id_umap' => $datalayerId
                ]);
                $stats['lugares']++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Sincronización completada',
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}