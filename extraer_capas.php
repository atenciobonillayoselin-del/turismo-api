<?php
/**
 * extraer_capas.php - Extrae capas individuales de un GeoJSON completo
 * 
 * Uso: php extraer_capas.php
 * 
 * Busca automáticamente cualquier archivo .geojson en la raíz
 */

echo "📂 EXTRAYENDO CAPAS DE GEOJSON COMPLETO\n";
echo str_repeat("═", 60) . "\n\n";

// Buscar archivos GeoJSON en la raíz
$archivosGeo = glob(__DIR__ . '/*.geojson');
$archivosJson = glob(__DIR__ . '/*.json');

// Filtrar los que no son de la carpeta data
$archivos = array_merge($archivosGeo, $archivosJson);
$archivos = array_filter($archivos, function($f) {
    $nombre = basename($f);
    // Excluir archivos de la carpeta data
    return !str_contains($f, 'data/') && !str_contains($f, 'umap_cache');
});

if (empty($archivos)) {
    echo "❌ No se encontró ningún archivo .geojson o .json en la raíz.\n";
    echo "📌 Asegúrate de tener el archivo 'rutaslapaz (4).geojson' o similar.\n";
    exit(1);
}

echo "📄 Archivos encontrados:\n";
foreach ($archivos as $i => $f) {
    $nombre = basename($f);
    $tamano = round(filesize($f) / 1024, 1);
    echo "  [$i] $nombre ($tamano KB)\n";
}

// Si hay más de uno, preguntar cuál usar
$archivo = $archivos[0];
if (count($archivos) > 1) {
    echo "\n📌 Usando: " . basename($archivo) . " (primer archivo)\n";
    echo "   Si quieres otro, ejecuta: php extraer_capas.php --archivo=nombre.geojson\n";
}

// Verificar si se pasó un archivo específico
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--archivo=')) {
        $nombre = str_replace('--archivo=', '', $arg);
        foreach ($archivos as $f) {
            if (basename($f) === $nombre) {
                $archivo = $f;
                break;
            }
        }
        echo "📌 Usando archivo específico: $nombre\n";
    }
}

if (!file_exists($archivo)) {
    die("❌ No se encuentra el archivo: $archivo\n");
}

echo "\n📄 Procesando archivo: " . basename($archivo) . "\n";

// Leer el archivo
$contenido = file_get_contents($archivo);
$data = json_decode($contenido, true);

if (!$data || !isset($data['features'])) {
    die("❌ El archivo no es un GeoJSON válido (no tiene 'features')\n");
}

echo "📊 Total de features: " . count($data['features']) . "\n";

// ============================================================
// MAPEO DE CAPAS - IDs UUID
// ============================================================
$capas = [
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA (Mirador Montículo)',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA (Mirador Montículo)',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA (Mirador Killi Killi)',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA (Plaza Villarroel)',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA (Plaza Villarroel)',
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA (Parque Laikakota)',
    '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA (Parque Laikakota)',
];

// ============================================================
// EXTRAER CAPAS
// ============================================================
$cacheDir = __DIR__ . '/data/umap_cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
    echo "📁 Creado directorio: $cacheDir\n";
}

$capasEncontradas = [];
$featureCount = 0;

foreach ($data['features'] as $feature) {
    $featureCount++;
    
    // Determinar a qué capa pertenece según el orden
    // Cada capa tiene 2 features: Point + LineString
    $capaIndex = floor(($featureCount - 1) / 2);
    
    if ($capaIndex < count($capas)) {
        $capaIds = array_keys($capas);
        $capaId = $capaIds[$capaIndex] ?? null;
        
        if ($capaId) {
            if (!isset($capasEncontradas[$capaId])) {
                $capasEncontradas[$capaId] = [];
            }
            $capasEncontradas[$capaId][] = $feature;
        }
    }
}

// Guardar cada capa como archivo independiente
echo "\n📁 Guardando capas:\n";
$guardadas = 0;

foreach ($capas as $id => $nombre) {
    $features = $capasEncontradas[$id] ?? [];
    
    if (empty($features)) {
        echo "  ⚠️ $nombre: No se encontraron features\n";
        continue;
    }
    
    $capaJson = [
        'type' => 'FeatureCollection',
        'features' => $features
    ];
    
    $outputFile = $cacheDir . $id . '.json';
    file_put_contents($outputFile, json_encode($capaJson, JSON_PRETTY_PRINT));
    
    echo "  ✅ $nombre: " . count($features) . " features guardados\n";
    $guardadas++;
}

// ============================================================
// VERIFICAR ARCHIVOS CREADOS
// ============================================================
echo "\n🔍 Verificando archivos guardados:\n";
$archivosGuardados = glob($cacheDir . '*.json');

foreach ($archivosGuardados as $f) {
    $nombre = basename($f);
    $contenido = file_get_contents($f);
    $json = json_decode($contenido, true);
    $features = isset($json['features']) ? count($json['features']) : 0;
    $size = round(filesize($f) / 1024, 1);
    
    $estado = ($features > 0) ? '✅' : '❌';
    echo "  $estado $nombre ($features features, $size KB)\n";
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "📊 RESUMEN: $guardadas capas guardadas\n";
echo "📂 Ubicación: " . realpath($cacheDir) . "\n";

if ($guardadas > 0) {
    echo "\n🚀 PRÓXIMO PASO: Ejecuta sincronizar.php\n";
    echo "   php api/sincronizar.php\n";
} else {
    echo "\n❌ No se guardó ninguna capa. Verifica el archivo GeoJSON.\n";
}