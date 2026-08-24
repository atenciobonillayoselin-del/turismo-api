<?php
// api/sincronizar.php
// ✅ SINCRONIZACIÓN AUTOMÁTICA desde uMap
// ✅ Lee NOMBRE y DESCRIPCIÓN de las capas en uMap
// ✅ Guarda todo automáticamente en la base de datos

require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function logDebug($msg) {
    error_log("[sincronizar] " . $msg);
}

logDebug("=== INICIO SINCRONIZACIÓN uMap AUTOMÁTICA ===");

// ============================================================
// CONFIGURACIÓN DE uMap
// ============================================================
const UMAP_ID = '1447967';
const UMAP_BASE = 'https://umap.openstreetmap.fr/en/datalayer/';

// ✅ SOLO LOS IDs DE LAS CAPAS - TODO LO DEMÁS SE LEE DE uMap
const DATALAYER_IDS = [
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
    '291c212e-44db-4460-b84e-773bcfede107',
    // ✅ Agrega nuevos IDs aquí y TODO se leerá automáticamente
];

// ============================================================
// FUNCIÓN: DESCARGAR GEOJSON DESDE uMap
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
// FUNCIÓN: OBTENER METADATOS DE LA CAPA (nombre, descripción)
// ============================================================
function obtenerMetadatosCapa($geoJSON) {
    $properties = $geoJSON['properties'] ?? [];
    
    // ✅ Leer nombre de la capa desde uMap
    $nombre = trim($properties['name'] ?? '');
    if (empty($nombre)) {
        $nombre = 'Ruta sin nombre';
    }
    
    // ✅ Leer descripción de la capa desde uMap
    $descripcion = trim($properties['description'] ?? '');
    
    logDebug("📋 Metadatos: nombre='$nombre', descripcion='$descripcion'");
    
    return [
        'nombre' => $nombre,
        'descripcion' => $descripcion
    ];
}

// ============================================================
// FUNCIÓN: DETECTAR COLOR SEGÚN NOMBRE
// ============================================================
function detectarColor($nombre) {
    $nombreLower = strtolower($nombre);
    
    // 🔴 IDA → Rojo
    if (strpos($nombreLower, 'ida') !== false) {
        return '#E74C3C';
    }
    
    // 🔵 VUELTA o DEVUELTA → Azul
    if (strpos($nombreLower, 'vuelta') !== false || strpos($nombreLower, 'devuelta') !== false) {
        return '#2980B9';
    }
    
    // 🟢 Por defecto → Verde
    return '#27AE60';
}

// ============================================================
// FUNCIÓN: PROCESAR FEATURECOLLECTION
// ============================================================
function procesarFeatureCollection($geoJSON, $metadatos) {
    $rutas = [];
    $features = $geoJSON['features'] ?? [];
    $nombreCapa = $metadatos['nombre'];
    $descripcionCapa = $metadatos['descripcion'];
    $color = detectarColor($nombreCapa);
    
    foreach ($features as $feature) {
        $geometry = $feature['geometry'] ?? null;
        
        if (!$geometry || !isset($geometry['type'])) continue;
        
        if ($geometry['type'] === 'LineString') {
            // ── RUTA ──
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
                $rutas[] = [
                    'nombre' => $nombreCapa,           // ✅ Desde uMap
                    'descripcion' => $descripcionCapa,  // ✅ Desde uMap
                    'color' => $color,                  // ✅ Detectado automáticamente
                    'coordenadas' => $puntos
                ];
                logDebug("  🗺️ Ruta: $nombreCapa (" . count($puntos) . " puntos)");
                if (!empty($descripcionCapa)) {
                    logDebug("  📝 Descripción: $descripcionCapa");
                }
            }
        }
    }
    
    return $rutas;
}

// ============================================================
// FUNCIÓN: GUARDAR EN BASE DE DATOS
// ============================================================
function guardarRutas($rutas, $pdo) {
    $rutasAgregadas = 0;
    $paradasAgregadas = 0;
    $paradasActualizadas = 0;
    
    // Limpiar datos anteriores
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta");
    
    foreach ($rutas as $ruta) {
        $nombre = $ruta['nombre'];
        $descripcion = $ruta['descripcion'] ?? '';
        $color = $ruta['color'] ?? '#27AE60';
        
        // INSERTAR RUTA (con descripción)
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
        if (!empty($descripcion)) {
            logDebug("   📝 Desc: $descripcion");
        }
        
        // INSERTAR PARADAS
        $orden = 1;
        $totalPuntos = count($ruta['coordenadas']);
        
        foreach ($ruta['coordenadas'] as $punto) {
            // Buscar si la parada ya existe (por coordenadas)
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
        'paradas_actualizadas' => $paradasActualizadas
    ];
}

// ============================================================
// EJECUCIÓN PRINCIPAL
// ============================================================
try {
    $todasLasRutas = [];
    $errores = [];
    $metadatosCaps = [];
    
    foreach (DATALAYER_IDS as $datalayerId) {
        logDebug("📥 Procesando capa: $datalayerId");
        
        $geoJSON = descargarGeoJSON($datalayerId);
        if ($geoJSON === null) {
            $errores[] = "No se pudo descargar capa: $datalayerId";
            continue;
        }
        
        // ✅ Obtener nombre y descripción desde uMap
        $metadatos = obtenerMetadatosCapa($geoJSON);
        $metadatosCaps[] = $metadatos;
        
        // ✅ Procesar la capa con los metadatos
        $rutas = procesarFeatureCollection($geoJSON, $metadatos);
        $todasLasRutas = array_merge($todasLasRutas, $rutas);
    }
    
    if (empty($todasLasRutas)) {
        throw new Exception('No se encontraron rutas en las capas de uMap');
    }
    
    $resultado = guardarRutas($todasLasRutas, $pdo);
    
    // ✅ Preparar resumen de metadatos para el debug
    $resumenMetadatos = [];
    foreach ($metadatosCaps as $meta) {
        $resumenMetadatos[] = [
            'nombre' => $meta['nombre'],
            'descripcion' => $meta['descripcion'] ?: '(sin descripción)'
        ];
    }
    
    $response = [
        'success' => true,
        'rutas_agregadas' => $resultado['rutas_agregadas'],
        'paradas_agregadas' => $resultado['paradas_agregadas'],
        'paradas_actualizadas' => $resultado['paradas_actualizadas'],
        'mensaje' => 'Sincronización uMap completada exitosamente',
        'debug' => [
            'capas_procesadas' => count(DATALAYER_IDS),
            'rutas_encontradas' => count($todasLasRutas),
            'metadatos_capas' => $resumenMetadatos,
            'nombres_rutas' => array_column($todasLasRutas, 'nombre'),
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