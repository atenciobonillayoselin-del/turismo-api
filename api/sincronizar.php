<?php
// api/sincronizar.php
// ✅ SINCRONIZACIÓN uMap → MySQL
// ✅ Guarda: Rutas (IDA/VUELTA), Paradas, Lugares Turísticos, Relaciones

require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function logDebug($msg) {
    error_log("[sincronizar] " . $msg);
}

logDebug("=== INICIO SINCRONIZACIÓN uMap ===");

// ============================================================
// CONFIGURACIÓN uMap
// ============================================================
const UMAP_ID = '1447967';
const UMAP_BASE = 'https://umap.openstreetmap.fr/en/datalayer/';

const DATALAYER_IDS = [
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533', // Minibus 364 - IDA
    '291c212e-44db-4460-b84e-773bcfede107', // Minibus 364 - VUELTA
    // Agrega más capas aquí cuando las crees
];

// ============================================================
// FUNCIÓN: DESCARGAR GeoJSON
// ============================================================
function descargarGeoJSON($datalayerId) {
    $url = UMAP_BASE . UMAP_ID . '/' . $datalayerId . '/';
    logDebug("📥 Descargando: $url");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $response === false) {
        logDebug("❌ Error HTTP $httpCode en capa $datalayerId");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['features'])) {
        logDebug("❌ GeoJSON inválido para capa $datalayerId");
        return null;
    }
    
    logDebug("✅ Capa descargada: " . count($data['features']) . " features");
    return $data;
}

// ============================================================
// FUNCIÓN: DETECTAR COLOR
// ============================================================
function detectarColor($nombre) {
    $nombreLower = strtolower($nombre);
    if (strpos($nombreLower, 'ida') !== false) {
        return '#E74C3C'; // 🔴 Rojo
    }
    if (strpos($nombreLower, 'vuelta') !== false || strpos($nombreLower, 'devuelta') !== false) {
        return '#2980B9'; // 🔵 Azul
    }
    return '#27AE60'; // 🟢 Verde por defecto
}

// ============================================================
// FUNCIÓN: PROCESAR FEATURECOLLECTION
// ============================================================
function procesarFeatureCollection($geoJSON) {
    $rutas = [];
    $lugares = [];
    $features = $geoJSON['features'] ?? [];
    $nombreCapa = $geoJSON['properties']['name'] ?? 'Capa sin nombre';
    $descripcionCapa = $geoJSON['properties']['description'] ?? '';
    
    foreach ($features as $feature) {
        $geometry = $feature['geometry'] ?? null;
        $properties = $feature['properties'] ?? [];
        $geomType = $geometry['type'] ?? null;
        
        if (!$geometry || !$geomType) continue;
        
        // ── OBTENER NOMBRE ──
        $nombre = trim($properties['name'] ?? $nombreCapa);
        if (empty($nombre)) $nombre = 'Sin nombre';
        
        // ── PROCESAR SEGÚN TIPO ──
        if ($geomType === 'LineString') {
            // ===== RUTA =====
            $coords = $geometry['coordinates'] ?? [];
            if (count($coords) < 2) continue;
            
            $puntos = [];
            foreach ($coords as $coord) {
                if (count($coord) >= 2) {
                    $puntos[] = [
                        'lat' => (float)$coord[1],
                        'lng' => (float)$coord[0]
                    ];
                }
            }
            
            if (count($puntos) > 0) {
                $color = detectarColor($nombre);
                $rutas[] = [
                    'nombre' => $nombre,
                    'descripcion' => $descripcionCapa,
                    'color' => $color,
                    'coordenadas' => $puntos,
                    'propiedades' => $properties
                ];
                logDebug("  🗺️ Ruta: $nombre (" . count($puntos) . " puntos)");
            }
        } elseif ($geomType === 'Point') {
            // ===== LUGAR TURÍSTICO =====
            $coord = $geometry['coordinates'] ?? [];
            if (count($coord) < 2) continue;
            
            $lat = (float)$coord[1];
            $lng = (float)$coord[0];
            
            // Obtener categoría
            $categoria = $properties['categoria'] ?? 'Atracción turística';
            $categoriaLower = strtolower($categoria);
            if (strpos($categoriaLower, 'mirador') !== false) $categoria = 'Mirador';
            elseif (strpos($categoriaLower, 'plaza') !== false) $categoria = 'Plaza';
            elseif (strpos($categoriaLower, 'parque') !== false) $categoria = 'Parque';
            elseif (strpos($categoriaLower, 'museo') !== false) $categoria = 'Museo';
            elseif (strpos($categoriaLower, 'iglesia') !== false) $categoria = 'Iglesia';
            elseif (strpos($categoriaLower, 'mercado') !== false) $categoria = 'Mercado';
            elseif (strpos($categoriaLower, 'naturaleza') !== false) $categoria = 'Naturaleza';
            
            $lugares[] = [
                'nombre' => $nombre,
                'descripcion' => $properties['description'] ?? '',
                'latitud' => $lat,
                'longitud' => $lng,
                'categoria' => $categoria,
                'direccion' => $properties['direccion'] ?? '',
                'imagen_url' => $properties['imagen_url'] ?? '',
                'panorama_url' => $properties['panorama_url'] ?? '',
                'propiedades' => $properties
            ];
            logDebug("  📍 Lugar: $nombre ($lat, $lng)");
        }
    }
    
    return ['rutas' => $rutas, 'lugares' => $lugares];
}

// ============================================================
// FUNCIÓN: GUARDAR EN BASE DE DATOS
// ============================================================
function guardarDatos($rutas, $lugares, $pdo) {
    $rutasAgregadas = 0;
    $paradasAgregadas = 0;
    $paradasActualizadas = 0;
    $lugaresAgregados = 0;
    $lugaresActualizados = 0;
    
    // ===== 1. GUARDAR LUGARES TURÍSTICOS =====
    $lugarIds = [];
    foreach ($lugares as $lugar) {
        $sql = "INSERT INTO lugar_turistico 
                (nombre, descripcion, latitud, longitud, categoria, direccion, imagen_url, panorama_url, activo) 
                VALUES (:nombre, :descripcion, :latitud, :longitud, :categoria, :direccion, :imagen_url, :panorama_url, 1)
                ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                descripcion = VALUES(descripcion),
                categoria = VALUES(categoria),
                direccion = VALUES(direccion),
                imagen_url = VALUES(imagen_url),
                panorama_url = VALUES(panorama_url),
                activo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $lugar['nombre'],
            ':descripcion' => $lugar['descripcion'],
            ':latitud' => $lugar['latitud'],
            ':longitud' => $lugar['longitud'],
            ':categoria' => $lugar['categoria'],
            ':direccion' => $lugar['direccion'],
            ':imagen_url' => $lugar['imagen_url'],
            ':panorama_url' => $lugar['panorama_url']
        ]);
        
        // Obtener ID del lugar
        $idLugar = $pdo->lastInsertId();
        if (!$idLugar) {
            // Buscar por coordenadas
            $check = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE latitud = :lat AND longitud = :lng");
            $check->execute([':lat' => $lugar['latitud'], ':lng' => $lugar['longitud']]);
            $row = $check->fetch();
            $idLugar = $row ? $row['id_lugar'] : null;
        }
        
        if ($idLugar) {
            $lugarIds[] = $idLugar;
            $lugaresAgregados++;
            logDebug("✅ Lugar guardado: {$lugar['nombre']} (ID: $idLugar)");
        }
    }
    
    // ===== 2. GUARDAR RUTAS Y PARADAS =====
    // Limpiar datos anteriores
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta");
    
    foreach ($rutas as $ruta) {
        $nombre = $ruta['nombre'];
        $descripcion = $ruta['descripcion'] ?? '';
        $color = $ruta['color'] ?? '#27AE60';
        $propiedades = $ruta['propiedades'] ?? [];
        
        // INSERTAR RUTA
        $sql = "INSERT INTO ruta (nombre, descripcion, tipo, color_hex, activo) 
                VALUES (:nombre, :descripcion, 'minibus', :color, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':color' => $color
        ]);
        $idRuta = $pdo->lastInsertId();
        $rutasAgregadas++;
        
        logDebug("✅ Ruta guardada: $nombre (ID: $idRuta)");
        
        // ===== RELACIONAR RUTA CON LUGAR =====
        // Buscar lugar_id en propiedades o por coincidencia de nombre
        $lugarId = null;
        
        // Primero: buscar en propiedades
        if (isset($propiedades['lugar_id'])) {
            $lugarId = (int)$propiedades['lugar_id'];
        } elseif (isset($propiedades['lugar'])) {
            // Buscar lugar por nombre
            $nombreLugar = $propiedades['lugar'];
            $check = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE nombre LIKE :nombre LIMIT 1");
            $check->execute([':nombre' => '%' . $nombreLugar . '%']);
            $row = $check->fetch();
            if ($row) $lugarId = $row['id_lugar'];
        }
        
        // Segundo: buscar por coincidencia de nombre
        if (!$lugarId) {
            $nombreLugar = str_replace(['Minibus', 'Micro', 'IDA', 'VUELTA', '-'], '', $nombre);
            $nombreLugar = trim($nombreLugar);
            if (!empty($nombreLugar)) {
                $check = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE nombre LIKE :nombre LIMIT 1");
                $check->execute([':nombre' => '%' . $nombreLugar . '%']);
                $row = $check->fetch();
                if ($row) $lugarId = $row['id_lugar'];
            }
        }
        
        // Tercero: si hay lugares guardados, asociar al primero (si no hay relación específica)
        if (!$lugarId && count($lugarIds) > 0) {
            $lugarId = $lugarIds[0];
        }
        
        if ($lugarId) {
            $sql = "INSERT INTO ruta_lugar (id_ruta, id_lugar, orden) VALUES (:id_ruta, :id_lugar, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_ruta' => $idRuta, ':id_lugar' => $lugarId]);
            logDebug("🔗 Ruta '$nombre' relacionada con lugar ID: $lugarId");
        }
        
        // ===== GUARDAR PARADAS =====
        $orden = 1;
        $totalPuntos = count($ruta['coordenadas']);
        
        foreach ($ruta['coordenadas'] as $punto) {
            // Buscar si la parada ya existe
            $checkSql = "SELECT id_parada FROM parada 
                         WHERE ABS(latitud - :lat) < 0.000001 
                         AND ABS(longitud - :lng) < 0.000001";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                ':lat' => $punto['lat'],
                ':lng' => $punto['lng']
            ]);
            $paradaExistente = $checkStmt->fetch();
            
            if ($paradaExistente) {
                $idParada = $paradaExistente['id_parada'];
                $paradasActualizadas++;
            } else {
                $sqlParada = "INSERT INTO parada (nombre, latitud, longitud, activo) 
                              VALUES (:nombre, :lat, :lng, 1)";
                $stmtParada = $pdo->prepare($sqlParada);
                $stmtParada->execute([
                    ':nombre' => 'Punto ' . $orden . ' de ' . $nombre,
                    ':lat' => $punto['lat'],
                    ':lng' => $punto['lng']
                ]);
                $idParada = $pdo->lastInsertId();
                $paradasAgregadas++;
            }
            
            // Relacionar ruta con parada
            $sqlRp = "INSERT INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin) 
                      VALUES (:id_ruta, :id_parada, :orden, :inicio, :fin)";
            $stmtRp = $pdo->prepare($sqlRp);
            $stmtRp->execute([
                ':id_ruta' => $idRuta,
                ':id_parada' => $idParada,
                ':orden' => $orden,
                ':inicio' => ($orden == 1) ? 1 : 0,
                ':fin' => ($orden == $totalPuntos) ? 1 : 0
            ]);
            $orden++;
        }
    }
    
    return [
        'rutas_agregadas' => $rutasAgregadas,
        'paradas_agregadas' => $paradasAgregadas,
        'paradas_actualizadas' => $paradasActualizadas,
        'lugares_agregados' => $lugaresAgregados,
        'lugares_actualizados' => $lugaresActualizados
    ];
}

// ============================================================
// EJECUCIÓN PRINCIPAL
// ============================================================
try {
    $todasLasRutas = [];
    $todosLosLugares = [];
    $errores = [];
    
    foreach (DATALAYER_IDS as $datalayerId) {
        $geoJSON = descargarGeoJSON($datalayerId);
        if ($geoJSON === null) {
            $errores[] = "No se pudo descargar capa: $datalayerId";
            continue;
        }
        
        $resultado = procesarFeatureCollection($geoJSON);
        $todasLasRutas = array_merge($todasLasRutas, $resultado['rutas']);
        $todosLosLugares = array_merge($todosLosLugares, $resultado['lugares']);
    }
    
    if (empty($todasLasRutas) && empty($todosLosLugares)) {
        throw new Exception('No se encontraron datos en las capas de uMap');
    }
    
    $resultado = guardarDatos($todasLasRutas, $todosLosLugares, $pdo);
    
    $response = [
        'success' => true,
        'mensaje' => 'Sincronización uMap completada exitosamente',
        'rutas_agregadas' => $resultado['rutas_agregadas'],
        'paradas_agregadas' => $resultado['paradas_agregadas'],
        'paradas_actualizadas' => $resultado['paradas_actualizadas'],
        'lugares_agregados' => $resultado['lugares_agregados'],
        'lugares_actualizados' => $resultado['lugares_actualizados'],
        'debug' => [
            'capas_procesadas' => count(DATALAYER_IDS),
            'rutas_encontradas' => count($todasLasRutas),
            'lugares_encontrados' => count($todosLosLugares),
            'errores' => $errores
        ]
    ];
    
    logDebug("✅ Sincronización completada: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("❌ Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>