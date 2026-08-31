<?php
/**
 * renombrar_archivos.php - Renombra archivos GeoJSON a formato UUID
 */

$cacheDir = __DIR__ . '/data/umap_cache/';

$mapeo = [
    'minibus_204___ida__mirador_killi_killi_.geojson' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd.json',
    'minibus_254___ida__mirador_mont_culo_.geojson' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc.json',
    'minibus_254___vuelta__mirador_mont_culo_.geojson' => '1131cb1a-631f-4d7b-8f33-f46a469366f9.json',
    'minibus_364___ida__parque_laicacota_.geojson' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533.json',
    'minibus_364___vuelta__parque_laicacota_.geojson' => '291c212e-44db-4460-b84e-773bcfede107.json',
    'minibus_838___ida__laguna_cota_cota_.geojson' => 'cota_cota_838.json',
    'minibus_841___ida__mercado_de_las_brujas_.geojson' => 'mercado_brujas_841.json',
    'minibus_889___ida__plaza_villaroel_.geojson' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47.json',
    'minibus_889___vuelta__plaza_villaroel_.geojson' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5.json',
];

echo "📁 Renombrando archivos en: $cacheDir\n\n";

foreach ($mapeo as $original => $nuevo) {
    $rutaOriginal = $cacheDir . $original;
    $rutaNueva = $cacheDir . $nuevo;
    
    if (file_exists($rutaOriginal)) {
        // Verificar contenido
        $contenido = file_get_contents($rutaOriginal);
        $json = json_decode($contenido, true);
        
        if ($json && isset($json['features'])) {
            rename($rutaOriginal, $rutaNueva);
            echo "✅ $original → $nuevo\n";
        } else {
            echo "❌ $original no es GeoJSON válido\n";
        }
    } else {
        echo "⚠️ No encontrado: $original\n";
    }
}

echo "\n📂 Archivos finales:\n";
foreach (glob($cacheDir . '*.json') as $f) {
    $size = round(filesize($f) / 1024, 1);
    echo "  📄 " . basename($f) . " ($size KB)\n";
}