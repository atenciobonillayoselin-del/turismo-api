<?php
/**
 * scripts/subir_final.php
 * CON CONTRASEÑA DIRECTA - SUBE GEOJSONS A AIVEN
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo CLI.\n");
}

// ============================================================
// CONEXIÓN DIRECTA A AIVEN
// ============================================================
$DB_HOST = 'mysql-3c89e575-turismo-la-paz.d.aivencloud.com';
$DB_PORT = 23909;
$DB_NAME = 'defaultdb';
$DB_USER = 'avnadmin';
$DB_PASS = 'AVNS_l6o3iZfQycKDeBAGO4c';

try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'");
    echo "✅ Conexión a Aiven exitosa\n\n";
} catch (PDOException $e) {
    die("❌ Error conectando a Aiven: " . $e->getMessage() . "\n");
}

$CACHE_DIR = dirname(__DIR__) . '/data/umap_cache';

// VERIFICAR ARCHIVOS
if (!is_dir($CACHE_DIR)) {
    die("❌ Directorio $CACHE_DIR no existe\n");
}

$archivos = glob("$CACHE_DIR/*.json");
if (empty($archivos)) {
    die("❌ No hay archivos .json en $CACHE_DIR\n");
}

echo "📊 Archivos a procesar: " . count($archivos) . "\n";
foreach ($archivos as $f) {
    echo "   📄 " . basename($f) . "\n";
}
echo "\n";

// LIMPIAR BD
echo "🧹 LIMPIANDO BASE DE DATOS...\n";
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM ruta_parada");
    $pdo->exec("DELETE FROM ruta_lugar");
    $pdo->exec("DELETE FROM lugar_turistico");
    $pdo->exec("DELETE FROM ruta");
    $pdo->exec("DELETE FROM parada");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Base de datos limpiada\n\n";
} catch (PDOException $e) {
    die("❌ Error limpiando BD: " . $e->getMessage() . "\n");
}

// PREPARAR CONSULTAS
$stmtLugar = $pdo->prepare("
    INSERT INTO lugar_turistico (
        nombre, descripcion, latitud, longitud, categoria, 
        grupo_umap, id_umap, icono_umap, color_hex, uuid_capa, activo
    ) VALUES (
        :nombre, :descripcion, :latitud, :longitud, :categoria,
        :grupo_umap, :id_umap, :icono_umap, :color_hex, :uuid_capa, 1
    )
");

$stmtRuta = $pdo->prepare("
    INSERT INTO ruta (
        nombre, descripcion, tipo, color_hex, sentido, 
        id_grupo_umap, coords_geojson, uuid_capa, activo
    ) VALUES (
        :nombre, :descripcion, :tipo, :color_hex, :sentido,
        :id_grupo_umap, :coords_geojson, :uuid_capa, 1
    )
");

$stmtParada = $pdo->prepare("
    INSERT INTO parada (nombre, latitud, longitud, id_umap, activo)
    VALUES (:nombre, :latitud, :longitud, :id_umap, 1)
");

$stmtRutaParada = $pdo->prepare("
    INSERT INTO ruta_parada (id_ruta, id_parada, orden, es_inicio, es_fin)
    VALUES (:id_ruta, :id_parada, :orden, :es_inicio, :es_fin)
");

$stmtRutaLugar = $pdo->prepare("
    INSERT INTO ruta_lugar (id_ruta, id_lugar, orden)
    VALUES (:id_ruta, :id_lugar, 1)
");

// PROCESAR ARCHIVOS
$stats = [
    'rutas' => 0,
    'paradas' => 0,
    'lugares' => 0,
    'ruta_lugar' => 0,
    'ruta_parada' => 0,
];

foreach ($archivos as $rutaArchivo) {
    $nombreArchivo = basename($rutaArchivo);
    echo "📥 Procesando: $nombreArchivo\n";
    
    $contenido = file_get_contents($rutaArchivo);
    if (!$contenido) {
        echo "   ❌ No se pudo leer el archivo\n\n";
        continue;
    }
    
    $geojson = json_decode($contenido, true);
    if (!is_array($geojson) || !isset($geojson['features'])) {
        echo "   ❌ JSON inválido o sin features\n\n";
        continue;
    }
    
    $features = $geojson['features'];
    $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    $nombreBase = str_replace('_geojson', '', $nombreBase);
    $nombreBase = str_replace('__', ' - ', $nombreBase);
    $nombreBase = str_replace('_', ' ', $nombreBase);
    $nombreCapa = ucwords($nombreBase);
    
    $partes = explode(' - ', $nombreBase);
    $grupoLugar = end($partes);
    $grupoLugar = ucwords(str_replace('_', ' ', $grupoLugar));
    
    $sentido = (stripos($nombreCapa, 'vuelta') !== false) ? 'VUELTA' : 'IDA';
    $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
    
    echo "   📍 Lugar: $grupoLugar\n";
    echo "   📍 Ruta: $nombreCapa\n";
    echo "   📍 Features: " . count($features) . "\n";
    
    $idRuta = null;
    $coordsRuta = [];
    
    foreach ($features as $feature) {
        $tipo = $feature['geometry']['type'] ?? '';
        $coords = $feature['geometry']['coordinates'] ?? [];
        $props = $feature['properties'] ?? [];
        
        if ($tipo === 'Point' && count($coords) >= 2) {
            $lat = (float) $coords[1];
            $lng = (float) $coords[0];
            if ($lat != 0 && $lng != 0) {
                try {
                    $stmtLugar->execute([
                        ':nombre' => $grupoLugar,
                        ':descripcion' => $props['description'] ?? '',
                        ':latitud' => $lat,
                        ':longitud' => $lng,
                        ':categoria' => $props['categoria'] ?? 'Atracción turística',
                        ':grupo_umap' => $grupoLugar,
                        ':id_umap' => pathinfo($nombreArchivo, PATHINFO_FILENAME),
                        ':icono_umap' => $props['icon'] ?? 'star',
                        ':color_hex' => $props['color'] ?? '#E74C3C',
                        ':uuid_capa' => pathinfo($nombreArchivo, PATHINFO_FILENAME),
                    ]);
                    $stats['lugares']++;
                    echo "      ✅ Lugar: $grupoLugar\n";
                } catch (PDOException $e) {}
            }
        }
        
        if ($tipo === 'LineString' && count($coords) >= 2) {
            $coordsRuta = $coords;
            $coordsJson = json_encode($coords);
            try {
                $stmtRuta->execute([
                    ':nombre' => $nombreCapa,
                    ':descripcion' => $props['description'] ?? '',
                    ':tipo' => 'minibus',
                    ':color_hex' => $props['color'] ?? $color,
                    ':sentido' => $sentido,
                    ':id_grupo_umap' => $grupoLugar,
                    ':coords_geojson' => $coordsJson,
                    ':uuid_capa' => pathinfo($nombreArchivo, PATHINFO_FILENAME),
                ]);
                $idRuta = $pdo->lastInsertId();
                if (!$idRuta) {
                    $stmtFind = $pdo->prepare("SELECT id_ruta FROM ruta WHERE uuid_capa = ?");
                    $stmtFind->execute([pathinfo($nombreArchivo, PATHINFO_FILENAME)]);
                    $idRuta = $stmtFind->fetchColumn();
                }
                $stats['rutas']++;
                echo "      ✅ Ruta: $nombreCapa (ID: $idRuta)\n";
            } catch (PDOException $e) {
                echo "      ❌ Error ruta: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }
    
    if ($idRuta && !empty($coordsRuta)) {
        $total = count($coordsRuta);
        foreach ($coordsRuta as $i => $coord) {
            $lat = (float) $coord[1];
            $lng = (float) $coord[0];
            $orden = $i + 1;
            if ($lat == 0 || $lng == 0) continue;
            try {
                $stmtParada->execute([
                    ':nombre' => "Parada $orden - $grupoLugar",
                    ':latitud' => $lat,
                    ':longitud' => $lng,
                    ':id_umap' => pathinfo($nombreArchivo, PATHINFO_FILENAME),
                ]);
                $idParada = $pdo->lastInsertId();
                if (!$idParada) {
                    $stmtFind = $pdo->prepare("SELECT id_parada FROM parada WHERE latitud = ? AND longitud = ?");
                    $stmtFind->execute([$lat, $lng]);
                    $idParada = $stmtFind->fetchColumn();
                }
                if ($idParada) {
                    $stmtRutaParada->execute([
                        ':id_ruta' => $idRuta,
                        ':id_parada' => $idParada,
                        ':orden' => $orden,
                        ':es_inicio' => ($orden === 1) ? 1 : 0,
                        ':es_fin' => ($orden === $total) ? 1 : 0,
                    ]);
                    $stats['ruta_parada']++;
                }
            } catch (PDOException $e) {}
        }
        echo "      ✅ Paradas: " . count($coordsRuta) . "\n";
        
        try {
            $stmtFindLugar = $pdo->prepare("SELECT id_lugar FROM lugar_turistico WHERE grupo_umap = ? LIMIT 1");
            $stmtFindLugar->execute([$grupoLugar]);
            $idLugar = $stmtFindLugar->fetchColumn();
            if ($idLugar) {
                $stmtRutaLugar->execute([
                    ':id_ruta' => $idRuta,
                    ':id_lugar' => $idLugar,
                ]);
                $stats['ruta_lugar']++;
                echo "      ✅ Asociado a lugar ID: $idLugar\n";
            }
        } catch (PDOException $e) {}
    }
    echo "\n";
}

echo "\n═══════════════════════════════════════════════\n";
echo "📊 RESUMEN FINAL:\n";
echo "   🗺️  Rutas insertadas: " . $stats['rutas'] . "\n";
echo "   📍  Lugares insertados: " . $stats['lugares'] . "\n";
echo "   🅿️  Paradas insertadas: " . $stats['paradas'] . "\n";
echo "   🔗  Relaciones ruta-lugar: " . $stats['ruta_lugar'] . "\n";
echo "   🔗  Relaciones ruta-parada: " . $stats['ruta_parada'] . "\n";
echo "\n✅ COMPLETADO!\n";