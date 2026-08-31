<?php
/**
 * scripts/subir_geojson_manual.php
 * 
 * SUBE LOS GEOJSONS QUE YA TIENES EN data/umap_cache/ DIRECTAMENTE A LA BD
 * SIN INTENTAR DESCARGAR NADA DE UMAP
 * 
 * MODO DE USO:
 *   php scripts/subir_geojson_manual.php
 *   php scripts/subir_geojson_manual.php --archivo=NOMBRE.json   (solo uno)
 *   php scripts/subir_geojson_manual.php --limpiar               (limpia BD antes de subir)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo CLI.\n");
}

require_once __DIR__ . '/../config/database.php';

$CACHE_DIR = dirname(__DIR__) . '/data/umap_cache';
$LIMPIAR = in_array('--limpiar', $argv, true);
$ARCHIVO_ESPECIFICO = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--archivo=') === 0) {
        $ARCHIVO_ESPECIFICO = substr($arg, 10);
    }
}

// ============================================================
// 1. VERIFICAR ARCHIVOS
// ============================================================
if (!is_dir($CACHE_DIR)) {
    die("❌ Directorio $CACHE_DIR no existe\n");
}

if ($ARCHIVO_ESPECIFICO) {
    $archivos = [$ARCHIVO_ESPECIFICO];
    if (!file_exists("$CACHE_DIR/$ARCHIVO_ESPECIFICO")) {
        die("❌ Archivo $ARCHIVO_ESPECIFICO no existe en $CACHE_DIR\n");
    }
} else {
    $archivos = glob("$CACHE_DIR/*.json");
    if (empty($archivos)) {
        die("❌ No hay archivos .json en $CACHE_DIR\n");
    }
    // Convertir a solo nombres
    $archivos = array_map('basename', $archivos);
}

echo "📊 Archivos a procesar: " . count($archivos) . "\n";
foreach ($archivos as $f) {
    echo "   📄 $f\n";
}
echo "\n";

// ============================================================
// 2. LIMPIAR BD (si se solicita)
// ============================================================
if ($LIMPIAR) {
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
}

// ============================================================
// 3. PREPARAR STATEMENTS
// ============================================================
try {
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

} catch (PDOException $e) {
    die("❌ Error preparando consultas: " . $e->getMessage() . "\n");
}

// ============================================================
// 4. PROCESAR CADA ARCHIVO
// ============================================================
$stats = [
    'rutas' => 0,
    'paradas' => 0,
    'lugares' => 0,
    'ruta_lugar' => 0,
    'ruta_parada' => 0,
];

foreach ($archivos as $nombreArchivo) {
    $uuid = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    $rutaArchivo = "$CACHE_DIR/$nombreArchivo";
    
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
    $nombreCapa = $geojson['name'] ?? $uuid;
    
    // Extraer nombre del lugar (entre paréntesis)
    preg_match('/\(([^)]+)\)/', $nombreCapa, $matches);
    $grupoLugar = $matches[1] ?? $nombreCapa;
    $nombreLimpio = trim(preg_replace('/\s*\([^)]*\)\s*/', '', $nombreCapa));
    
    echo "   📍 Lugar: $grupoLugar\n";
    echo "   📍 Ruta: $nombreLimpio\n";
    echo "   📍 Features: " . count($features) . "\n";
    
    $sentido = (stripos($nombreCapa, 'VUELTA') !== false) ? 'VUELTA' : 'IDA';
    $color = ($sentido === 'VUELTA') ? '#2980B9' : '#E74C3C';
    $idRuta = null;
    
    foreach ($features as $feature) {
        $tipo = $feature['geometry']['type'] ?? '';
        $coords = $feature['geometry']['coordinates'] ?? [];
        $props = $feature['properties'] ?? [];
        
        // --- PUNTO (Lugar Turístico) ---
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
                        ':id_umap' => $uuid,
                        ':icono_umap' => $props['icon'] ?? 'star',
                        ':color_hex' => $props['color'] ?? '#E74C3C',
                        ':uuid_capa' => $uuid,
                    ]);
                    $stats['lugares']++;
                    echo "      ✅ Lugar: $grupoLugar ($lat, $lng)\n";
                } catch (PDOException $e) {
                    // Puede que ya exista, ignorar
                }
            }
        }
        
        // --- LÍNEA (Ruta) ---
        if ($tipo === 'LineString' && count($coords) >= 2) {
            $coordsJson = json_encode($coords);
            
            try {
                $stmtRuta->execute([
                    ':nombre' => $nombreLimpio,
                    ':descripcion' => $props['description'] ?? '',
                    ':tipo' => 'minibus',
                    ':color_hex' => $props['color'] ?? $color,
                    ':sentido' => $sentido,
                    ':id_grupo_umap' => $grupoLugar,
                    ':coords_geojson' => $coordsJson,
                    ':uuid_capa' => $uuid,
                ]);
                $idRuta = $pdo->lastInsertId();
                if (!$idRuta) {
                    // Buscar si ya existe
                    $stmtFind = $pdo->prepare("SELECT id_ruta FROM ruta WHERE uuid_capa = ?");
                    $stmtFind->execute([$uuid]);
                    $idRuta = $stmtFind->fetchColumn();
                }
                $stats['rutas']++;
                echo "      ✅ Ruta: $nombreLimpio (ID: $idRuta)\n";
            } catch (PDOException $e) {
                echo "      ❌ Error ruta: " . $e->getMessage() . "\n";
                continue;
            }
            
            // --- CREAR PARADAS ---
            if ($idRuta) {
                $total = count($coords);
                foreach ($coords as $i => $coord) {
                    $lat = (float) $coord[1];
                    $lng = (float) $coord[0];
                    $orden = $i + 1;
                    
                    if ($lat == 0 || $lng == 0) continue;
                    
                    try {
                        $stmtParada->execute([
                            ':nombre' => "Parada $orden - $grupoLugar",
                            ':latitud' => $lat,
                            ':longitud' => $lng,
                            ':id_umap' => $uuid,
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
                    } catch (PDOException $e) {
                        // Ignorar errores de paradas duplicadas
                    }
                }
                echo "      ✅ Paradas: " . count($coords) . "\n";
                
                // --- ASOCIAR RUTA CON LUGAR ---
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
                } catch (PDOException $e) {
                    // Ignorar
                }
            }
        }
    }
    echo "\n";
}

// ============================================================
// 5. RESUMEN FINAL
// ============================================================
echo "\n═══════════════════════════════════════════════\n";
echo "📊 RESUMEN FINAL:\n";
echo "   🗺️  Rutas insertadas: " . $stats['rutas'] . "\n";
echo "   📍  Lugares insertados: " . $stats['lugares'] . "\n";
echo "   🅿️  Paradas insertadas: " . $stats['paradas'] . "\n";
echo "   🔗  Relaciones ruta-lugar: " . $stats['ruta_lugar'] . "\n";
echo "   🔗  Relaciones ruta-parada: " . $stats['ruta_parada'] . "\n";
echo "\n✅ COMPLETADO!\n";