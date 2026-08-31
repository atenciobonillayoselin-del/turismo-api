<?php
/**
 * Extrae capas individuales de un GeoJSON completo
 * basado en el tipo de geometría y orden de aparición
 * 
 * ASUNCION: Cada capa tiene exactamente 2 features:
 *   - 1 Point (lugar turístico)
 *   - 1 LineString (ruta)
 */

// IDs de las capas en orden de aparición en el GeoJSON
$capas = [
    [
        'id' => '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
        'nombre' => 'Minibus 254 - IDA'
    ],
    [
        'id' => '1131cb1a-631f-4d7b-8f33-f46a469366f9',
        'nombre' => 'Minibus 254 - VUELTA'
    ],
    [
        'id' => '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
        'nombre' => 'Minibus 204 - IDA'
    ],
    [
        'id' => 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
        'nombre' => 'Minibus 889 - IDA'
    ],
    [
        'id' => 'fa904f68-9ee2-4e12-b3a4-8406f357def5',
        'nombre' => 'Minibus 889 - VUELTA'
    ],
    [
        'id' => '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
        'nombre' => 'Minibus 364 - IDA'
    ],
    [
        'id' => '291c212e-44db-4460-b84e-773bcfede107',
        'nombre' => 'Minibus 364 - VUELTA'
    ]
];

// Buscar el archivo
$archivos = array_merge(
    glob('data/umap_cache/geojson/*.geojson'),
    glob('*.geojson'),
    glob('geojson/*.geojson')
);

if (empty($archivos)) {
    die("❌ No se encontraron archivos .geojson\n");
}

echo "📂 Archivos GeoJSON encontrados:\n";
foreach ($archivos as $i => $f) {
    echo "  [$i] $f\n";
}

echo "\nEscribe el número del archivo (0-" . (count($archivos)-1) . "): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$index = intval(trim($line));

if (!isset($archivos[$index])) {
    die("❌ Archivo no válido\n");
}

$archivo = $archivos[$index];
echo "\n📄 Procesando: $archivo\n";

$json = file_get_contents($archivo);
$data = json_decode($json, true);

if (!$data || !isset($data['features'])) {
    die("❌ No es un GeoJSON válido\n");
}

$cacheDir = __DIR__ . '/data/umap_cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Separar features por tipo (Point vs LineString)
$points = [];
$lines = [];

foreach ($data['features'] as $feature) {
    $type = $feature['geometry']['type'] ?? '';
    if ($type === 'Point') {
        $points[] = $feature;
    } elseif ($type === 'LineString') {
        $lines[] = $feature;
    }
}

echo "📊 Puntos encontrados: " . count($points) . "\n";
echo "📊 Líneas encontradas: " . count($lines) . "\n";

// Asignar: cada capa tiene 1 Point + 1 LineString
$totalCapas = min(count($points), count($lines), count($capas));

for ($i = 0; $i < $totalCapas; $i++) {
    $capa = $capas[$i];
    $features = [];
    
    if (isset($points[$i])) {
        $features[] = $points[$i];
    }
    if (isset($lines[$i])) {
        $features[] = $lines[$i];
    }
    
    if (empty($features)) continue;
    
    $capaJson = [
        'type' => 'FeatureCollection',
        'features' => $features
    ];
    
    $outputFile = $cacheDir . $capa['id'] . '.json';
    file_put_contents($outputFile, json_encode($capaJson, JSON_PRETTY_PRINT));
    
    echo "  ✅ {$capa['nombre']} ({$capa['id']}): " . count($features) . " features\n";
}

echo "\n✅ Archivos guardados en: " . realpath($cacheDir) . "\n";