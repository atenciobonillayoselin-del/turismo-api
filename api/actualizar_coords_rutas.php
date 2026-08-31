<?php
// api/actualizar_coords_rutas.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // ✅ Verificar que la columna existe
    $check = $pdo->query("SHOW COLUMNS FROM ruta LIKE 'coords_geojson'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ruta ADD COLUMN coords_geojson LONGTEXT COMMENT 'GeoJSON LineString serializado'");
        echo "✅ Columna coords_geojson creada\n";
    }

    // ✅ Obtener todas las rutas activas
    $rutas = $pdo->query("SELECT id_ruta, nombre FROM ruta WHERE activo = 1")->fetchAll();
    
    $actualizadas = 0;
    $errores = [];
    
    foreach ($rutas as $ruta) {
        $idRuta = $ruta['id_ruta'];
        
        // Obtener las coordenadas ordenadas
        $sql = "
            SELECT p.latitud, p.longitud
            FROM ruta_parada rp
            INNER JOIN parada p ON p.id_parada = rp.id_parada
            WHERE rp.id_ruta = ?
            ORDER BY rp.orden ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idRuta]);
        $puntos = $stmt->fetchAll();
        
        if (count($puntos) < 2) {
            $errores[] = "Ruta $idRuta tiene menos de 2 puntos";
            continue;
        }
        
        // Construir JSON con formato [longitud, latitud]
        $coords = [];
        foreach ($puntos as $p) {
            $coords[] = [(float)$p['longitud'], (float)$p['latitud']];
        }
        
        $coordsJson = json_encode($coords);
        
        // Actualizar
        $update = $pdo->prepare("UPDATE ruta SET coords_geojson = ? WHERE id_ruta = ?");
        $update->execute([$coordsJson, $idRuta]);
        $actualizadas++;
        
        echo "✅ Ruta {$ruta['nombre']} actualizada (" . count($puntos) . " puntos)\n";
    }
    
    echo "\n📊 Resumen: $actualizadas rutas actualizadas, " . count($errores) . " errores\n";
    if (!empty($errores)) {
        echo "⚠️ Errores: " . implode(", ", $errores) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>