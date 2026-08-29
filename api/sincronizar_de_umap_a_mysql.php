<?php
// api/sincronizar_de_umap_a_mysql.php
// ⚠️ Este es el script que lee uMap y guarda en MySQL
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

const UMAP_ID = '1447967';
const UMAP_BASE = 'https://umap.openstreetmap.fr/en/datalayer/';

// 🟢 LISTA DE CAPAS A SINCRONIZAR
// Puedes obtener estos IDs desde la URL de cada capa en uMap
const DATALAYER_IDS = [
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', // Minibus 364 - IDA
    '291c212e-44db-4460-b84e-773bcfede107', // Minibus 364 - VUELTA
    // 🔴 AGREGAR NUEVAS CAPAS AQUÍ cuando las crees
];

function logDebug($msg) {
    error_log("[sincronizar_umap] " . $msg);
}

function descargarGeoJSON($datalayerId) {
    $url = UMAP_BASE . UMAP_ID . '/' . $datalayerId . '/';
    logDebug("📥 Descargando: $url");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TurismoLaPaz/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $response === false) {
        logDebug("❌ Error HTTP $httpCode");
        return null;
    }
    
    return json_decode($response, true);
}

try {
    $totalRutas = 0;
    $totalParadas = 0;
    $totalLugares = 0;
    
    foreach (DATALAYER_IDS as $datalayerId) {
        $data = descargarGeoJSON($datalayerId);
        if (!$data) continue;
        
        $features = $data['features'] ?? [];
        $nombreCapa = $data['properties']['name'] ?? 'Capa sin nombre';
        
        foreach ($features as $feature) {
            $geom = $feature['geometry'] ?? [];
            $props = $feature['properties'] ?? [];
            $type = $geom['type'] ?? '';
            
            if ($type === 'LineString') {
                // 🔴 ES UNA RUTA
                $coords = $geom['coordinates'] ?? [];
                if (count($coords) < 2) continue;
                
                $nombre = $props['name'] ?? $nombreCapa;
                $color = stripos($nombre, 'vuelta') !== false ? '#2980B9' : '#E74C3C';
                
                // Guardar ruta
                $sql = "INSERT INTO ruta (nombre, descripcion, tipo, color_hex, grupo_umap, activo)
                        VALUES (:nombre, :descripcion, 'minibus', :color, :grupo, 1)
                        ON DUPLICATE KEY UPDATE
                        nombre = VALUES(nombre),
                        descripcion = VALUES(descripcion),
                        color_hex = VALUES(color_hex)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $props['description'] ?? '',
                    ':color' => $color,
                    ':grupo' => $datalayerId . '_' . md5($nombre)
                ]);
                $idRuta = $pdo->lastInsertId();
                $totalRutas++;
                
                // Guardar paradas
                $orden = 1;
                foreach ($coords as $coord) {
                    $lat = (float)$coord[1];
                    $lng = (float)$coord[0];
                    
                    // Buscar parada existente
                    $check = $pdo->prepare("SELECT id_parada FROM parada 
                                            WHERE ABS(latitud - :lat) < 0.00001 
                                            AND ABS(longitud - :lng) < 0.00001");
                    $check->execute([':lat' => $lat, ':lng' => $lng]);
                    $existente = $check->fetch();
                    
                    if ($existente) {
                        $idParada = $existente['id_parada'];
                    } else {
                        $sql = "INSERT INTO parada (nombre, latitud, longitud, activo)
                                VALUES (:nombre, :lat, :lng, 1)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nombre' => 'Punto ' . $orden . ' de ' . $nombre,
                            ':lat' => $lat,
                            ':lng' => $lng
                        ]);
                        $idParada = $pdo->lastInsertId();
                        $totalParadas++;
                    }
                    
                    // Relacionar ruta con parada
                    $sql = "INSERT IGNORE INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin)
                            VALUES (:id_ruta, :id_parada, :orden, :inicio, :fin)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id_ruta' => $idRuta,
                        ':id_parada' => $idParada,
                        ':orden' => $orden,
                        ':inicio' => ($orden == 1) ? 1 : 0,
                        ':fin' => ($orden == count($coords)) ? 1 : 0
                    ]);
                    $orden++;
                }
            } elseif ($type === 'Point') {
                // 🟢 ES UN MARCADOR (lugar turístico)
                $coord = $geom['coordinates'] ?? [];
                if (count($coord) < 2) continue;
                
                $nombre = $props['name'] ?? 'Lugar sin nombre';
                $categoria = $props['categoria'] ?? 'Atracción turística';
                
                $sql = "INSERT INTO lugar_turistico (nombre, descripcion, latitud, longitud, categoria, grupo_umap, activo)
                        VALUES (:nombre, :descripcion, :lat, :lng, :categoria, :grupo, 1)
                        ON DUPLICATE KEY UPDATE
                        nombre = VALUES(nombre),
                        descripcion = VALUES(descripcion),
                        categoria = VALUES(categoria)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $props['description'] ?? '',
                    ':lat' => (float)$coord[1],
                    ':lng' => (float)$coord[0],
                    ':categoria' => $categoria,
                    ':grupo' => $props['grupo'] ?? $nombre
                ]);
                $totalLugares++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Sincronización completada',
        'rutas_procesadas' => $totalRutas,
        'paradas_agregadas' => $totalParadas,
        'lugares_procesados' => $totalLugares,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>