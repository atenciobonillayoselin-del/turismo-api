<?php
// api/sincronizar.php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $kmlUrl = 'https://drive.google.com/uc?export=download&id=13SoAxhs7sP-GIW-SKdfVRv_186C6Lj7r';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $kmlUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $kmlReal = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $kmlReal === false) {
        throw new Exception('No se pudo descargar el KML. Código: ' . $httpCode);
    }
    
    // ===== 1. OBTENER TODAS LAS CARPETAS CON SU CONTENIDO =====
    preg_match_all('/<Folder>.*?<name>(.*?)<\/name>(.*?)<\/Folder>/s', $kmlReal, $folders);
    
    $rutas = [];
    $nombresVistos = [];
    
    // ===== LISTA DE RUTAS QUE SIEMPRE DEBEN GUARDARSE =====
    $rutasObligatorias = [
        'Minibus 394 - Laguna de cota cota ida',
        'Minibus 394 - Laguna de cota cota vuelta',
        'minibus 349 - plaza murillo ida',
        'minibus 349 - plaza murillo vuelta',
        'Minibus 820 - Calle Jaén ida',
        'Minibus 820 - Calle Jaén devuelta',  // ¡Esta es la que falta!
        'minibus 240 - san francisco ida',
        'minibus 240 - san francisco vuelta',
        'Minibus 308 - san pedro ida'
    ];
    
    foreach ($folders[1] as $index => $nombreCarpeta) {
        $nombre = trim($nombreCarpeta);
        $contenido = $folders[2][$index];
        
        // Buscar si dentro de la carpeta hay un LineString
        if (strpos($contenido, '<LineString>') !== false) {
            // Extraer coordenadas
            preg_match('/<coordinates>(.*?)<\/coordinates>/s', $contenido, $coordMatch);
            
            if (!empty($coordMatch[1])) {
                $coordsText = trim($coordMatch[1]);
                $puntos = explode(' ', $coordsText);
                $coordenadas = [];
                
                foreach ($puntos as $punto) {
                    $coord = explode(',', trim($punto));
                    if (count($coord) >= 2) {
                        $coordenadas[] = [
                            'lat' => floatval($coord[1]),
                            'lng' => floatval($coord[0])
                        ];
                    }
                }
                
                // ===== 1. VERIFICAR SI ESTÁ EN LA LISTA OBLIGATORIA =====
                $esObligatoria = in_array($nombre, $rutasObligatorias);
                
                // ===== 2. VERIFICAR POR PALABRAS CLAVE (solo si no es dirección) =====
                $tienePalabraClave = (
                    strpos(strtolower($nombre), 'minibus') !== false ||
                    strpos(strtolower($nombre), 'micro') !== false ||
                    strpos(strtolower($nombre), 'ida') !== false ||
                    strpos(strtolower($nombre), 'vuelta') !== false ||
                    strpos(strtolower($nombre), 'devuelta') !== false ||
                    strpos(strtolower($nombre), 'san francisco') !== false ||
                    strpos(strtolower($nombre), 'san pedro') !== false ||
                    strpos(strtolower($nombre), 'plaza murillo') !== false ||
                    strpos(strtolower($nombre), 'laguna de cota cota') !== false
                );
                
                // ===== 3. VERIFICAR SI ES DIRECCIÓN =====
                $esDireccion = (
                    preg_match('/^[A-Z]{4}\+[A-Z0-9]{2,4}/', $nombre) ||
                    strpos($nombre, 'GVGJ') !== false ||
                    strpos($nombre, 'GR2R') !== false ||
                    strpos($nombre, 'GVCC') !== false ||
                    strpos($nombre, 'FRWQ') !== false ||
                    strpos($nombre, 'C. Soldado') !== false ||
                    strpos($nombre, 'Plaza del Maestro') !== false
                );
                
                // ===== GUARDAR SI ES OBLIGATORIA O (TIENE PALABRA CLAVE Y NO ES DIRECCIÓN) =====
                $esValida = $esObligatoria || ($tienePalabraClave && !$esDireccion);
                
                if ($esValida && count($coordenadas) > 0 && !in_array($nombre, $nombresVistos)) {
                    $nombresVistos[] = $nombre;
                    $rutas[] = [
                        'nombre' => $nombre,
                        'coordenadas' => $coordenadas
                    ];
                }
            }
        }
    }
    
    // ===== LIMPIAR DATOS ANTERIORES =====
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta");
    
    // ===== Guardar en la base de datos =====
    $rutasAgregadas = 0;
    $paradasAgregadas = 0;
    
    foreach ($rutas as $ruta) {
        $sql = "INSERT INTO ruta (nombre, tipo, color_hex) VALUES (:nombre, 'minibus', '#E74C3C')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $ruta['nombre']]);
        $idRuta = $pdo->lastInsertId();
        $rutasAgregadas++;
        
        $orden = 1;
        foreach ($ruta['coordenadas'] as $punto) {
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
            } else {
                $sqlParada = "INSERT INTO parada (nombre, latitud, longitud) 
                              VALUES (:nombre, :lat, :lng)";
                $stmtParada = $pdo->prepare($sqlParada);
                $stmtParada->execute([
                    ':nombre' => 'Punto ' . $orden . ' de ' . $ruta['nombre'],
                    ':lat' => $punto['lat'],
                    ':lng' => $punto['lng']
                ]);
                $idParada = $pdo->lastInsertId();
                $paradasAgregadas++;
            }
            
            $sqlRp = "INSERT INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin) 
                      VALUES (:id_ruta, :id_parada, :orden, :inicio, :fin)";
            $stmtRp = $pdo->prepare($sqlRp);
            $stmtRp->execute([
                ':id_ruta' => $idRuta,
                ':id_parada' => $idParada,
                ':orden' => $orden,
                ':inicio' => ($orden == 1) ? 1 : 0,
                ':fin' => ($orden == count($ruta['coordenadas'])) ? 1 : 0
            ]);
            $orden++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'rutas_agregadas' => $rutasAgregadas,
        'paradas_agregadas' => $paradasAgregadas,
        'mensaje' => 'Sincronización completada',
        'debug' => [
            'rutas_encontradas' => count($rutas),
            'rutas_nombres' => array_column($rutas, 'nombre')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>