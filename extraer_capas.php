<?php
/**
 * Extrae las capas individuales del GeoJSON completo
 * de uMap y las guarda en data/umap_cache/
 * 
 * Busca archivos .geojson en:
 * - Raíz del proyecto (*.geojson)
 * - Carpeta geojson/ (geojson/*.geojson)
 */

// IDs de las capas (según el orden en el archivo)
$capas = [
    [
        'id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
        'nombre' => 'Minibus 254 IDA'
    ],
    [
        'id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9',
        'nombre' => 'Minibus 254 VUELTA'
    ],
    [
        'id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
        'nombre' => 'Minibus 204 IDA'
    ],
    [
        'id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
        'nombre' => 'Minibus 889 IDA'
    ],
    [
        'id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5',
        'nombre' => 'Minibus 889 VUELTA'
    ],
    [
        'id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
        'nombre' => 'Minibus 364 IDA'
    ],
    [
        'id' => '291c212e-44db-4460-b84e-773bcfede107',
        'nombre' => 'Minibus 364 VUELTA'
    ]
];

// ============================================================
// 🔍 BUSCAR ARCHIVOS GEOJSON EN RAÍZ Y EN CARPETA geojson/
// ============================================================
$archivos = array_merge(
    glob('*.geojson'),
    glob('geojson/*.geojson'),
    glob('geojson/*.json')  // Por si acaso
);

if (empty($archivos)) {
    die("❌ No se encontraron archivos .geojson en la raíz o en la carpeta geojson/\n");
}

echo "📂 Archivos GeoJSON encontrados:\n";
foreach ($archivos as $i => $f) {
    $size = round(filesize($f) / 1024, 1);
    echo "  [$i] $f (" . $size . " KB)\n";
}

echo "\nEscribe el número del archivo a procesar (0-" . (count($archivos)-1) . "): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$index = intval(trim($line));

if (!isset($archivos[$index])) {
    die("❌ Archivo no válido\n");
}

$archivo = $archivos[$index];
echo "\n📄 Procesando archivo: $archivo\n";

// ============================================================
// 📥 LEER EL ARCHIVO
// ============================================================
$json = file_get_contents($archivo);
$data = json_decode($json, true);

if (!$data || !isset($data['features'])) {
    die("❌ El archivo no es un GeoJSON válido\n");
}

// Crear directorio de caché si no existe
$cacheDir = __DIR__ . '/data/umap_cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

echo "📊 Total de features en el archivo: " . count($data['features']) . "\n\n";

// ============================================================
// 📊 EXTRAER CAPAS
// ============================================================
$featureCount = 0;
$capasEncontradas = [];

foreach ($data['features'] as $feature) {
    $featureCount++;
    
    // Determinar a qué capa pertenece según su geometría
    // Usamos el orden de aparición para asignar
    $capaIndex = floor(($featureCount - 1) / 2); // 2 features por capa (Point + LineString)
    
    if ($capaIndex < count($capas)) {
        $capaId = $capas[$capaIndex]['id'];
        if (!isset($capasEncontradas[$capaId])) {
            $capasEncontradas[$capaId] = [
                'features' => [],
                'nombre' => $capas[$capaIndex]['nombre']
            ];
        }
        $capasEncontradas[$capaId]['features'][] = $feature;
    }
}

// ============================================================
// 💾 GUARDAR CADA CAPA
// ============================================================
echo "📁 Guardando capas:\n";
$guardadas = 0;

foreach ($capas as $capa) {
    $id = $capa['id'];
    $nombre = $capa['nombre'];
    
    if (!isset($capasEncontradas[$id])) {
        echo "  ⚠️ $nombre ($id): No se encontraron features\n";
        continue;
    }
    
    $features = $capasEncontradas[$id]['features'];
    
    // Crear FeatureCollection para esta capa
    $capaJson = [
        'type' => 'FeatureCollection',
        'features' => $features
    ];
    
    $outputFile = $cacheDir . $id . '.json';
    file_put_contents($outputFile, json_encode($capaJson, JSON_PRETTY_PRINT));
    
    echo "  ✅ $nombre ($id): " . count($features) . " features guardados\n";
    $guardadas++;
}

echo "\n✅ $guardadas capas guardadas en: " . realpath($cacheDir) . "\n";

// ============================================================
// 🔍 VERIFICAR ARCHIVOS GUARDADOS
// ============================================================
echo "\n🔍 Verificando archivos guardados:\n";
$archivosGuardados = glob($cacheDir . '*.json');
foreach ($archivosGuardados as $f) {
    $nombre = basename($f);
    // Saltar .gitkeep y README.md
    if ($nombre === '.gitkeep' || $nombre === 'README.md') continue;
    
    $contenido = file_get_contents($f);
    $test = json_decode($contenido, true);
    if ($test && isset($test['features'])) {
        echo "  ✅ " . $nombre . " - " . count($test['features']) . " features\n";
    } else {
        echo "  ❌ " . $nombre . " - INVÁLIDO\n";
    }
}