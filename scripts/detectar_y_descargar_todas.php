<?php
/**
 * scripts/detectar_y_descargar_todas.php
 * -------------------------------------------------------------
 * DETECTA AUTOMÁTICAMENTE TODAS LAS CAPAS del mapa y las descarga
 * -------------------------------------------------------------
 * MODO DE USO:
 *   php scripts/detectar_y_descargar_todas.php
 *   php scripts/detectar_y_descargar_todas.php --force
 *   php scripts/detectar_y_descargar_todas.php --proxy "URL"
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo CLI.\n");
}

// ============================================================
// CONFIGURACIÓN
// ============================================================
$MAP_ID     = 1447967;   // Tu mapa real
$CACHE_DIR  = dirname(__DIR__) . '/data/umap_cache';
$FORCE      = in_array('--force', $argv, true);

$PROXY_URL = '';
$COOKIE    = '';
foreach ($argv as $i => $arg) {
    if ($arg === '--proxy' && isset($argv[$i+1]))   $PROXY_URL = trim($argv[$i+1]);
    if ($arg === '--cookie' && isset($argv[$i+1]))  $COOKIE    = trim($argv[$i+1]);
}

if (!is_dir($CACHE_DIR)) {
    @mkdir($CACHE_DIR, 0777, true);
    echo "📁 Creado directorio caché: $CACHE_DIR\n";
}

$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
];

// ============================================================
// 1. DETECTAR TODAS LAS CAPAS DEL MAPA
// ============================================================
echo "🔍 Detectando capas del mapa #$MAP_ID...\n";

// Usar la API pública de uMap para obtener las capas
$apiUrl = "https://umap.openstreetmap.fr/api/0.1/map/$MAP_ID/";
echo "   ↳ API: $apiUrl\n";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: es-ES,es;q=0.9',
        'Referer: https://umap.openstreetmap.fr/es/map/rutaslapaz_' . $MAP_ID,
    ],
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$capas = [];

if ($code === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data) && isset($data['datalayers'])) {
        echo "   ✅ API respondió correctamente\n";
        foreach ($data['datalayers'] as $layer) {
            $uuid = $layer['uuid'] ?? $layer['id'] ?? null;
            $name = $layer['name'] ?? $layer['title'] ?? 'Capa sin nombre';
            if ($uuid) {
                $capas[$uuid] = $name;
                echo "      📌 $name [$uuid]\n";
            }
        }
    }
}

// Si la API falló, usar la lista de respaldo que ya tenía
if (empty($capas)) {
    echo "   ⚠️ API no respondió, usando lista de respaldo\n";
    $capas = [
        '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA (Mirador Montículo)',
        '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA (Mirador Montículo)',
        '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA (Mirador Killi Killi)',
        'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA (Plaza Villarroel)',
        'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA (Plaza Villarroel)',
        '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA (Parque Laikakota)',
        '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA (Parque Laikakota)',
    ];
}

echo "\n📊 Total capas detectadas: " . count($capas) . "\n\n";

// ============================================================
// 2. DESCARGAR CADA CAPA
// ============================================================
function descargarCapa(string $url, string $proxy, string $cookie, array $uas): string|false {
    $target = $url;
    if ($proxy !== '') {
        $target = $proxy . urlencode($url);
    }

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => $uas[array_rand($uas)],
        CURLOPT_HTTPHEADER => [
            'Accept: application/geo+json, application/json, text/html,*/*',
            'Accept-Language: es-ES,es;q=0.9',
            'Referer: https://umap.openstreetmap.fr/es/map/rutaslapaz_1447967',
            'Origin: https://umap.openstreetmap.fr',
            $cookie !== '' ? 'Cookie: ' . $cookie : 'X-No-Cookie: 1',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp !== false && $code >= 200 && $code < 300 && strlen($resp) > 100) {
        return $resp;
    }
    return false;
}

$OK = 0;
$FAIL = 0;

foreach ($capas as $uuid => $nombre) {
    $destFile = "$CACHE_DIR/$uuid.json";
    echo "▶️ $nombre\n   ID: $uuid\n";

    if (!$FORCE && is_file($destFile) && filesize($destFile) > 500) {
        echo "   ⏭️  Ya existe, usando --force para sobrescribir\n" . ($FORCE ? "   🔄 Sobrescribiendo...\n" : "");
    }

    // Probar URLs
    $urls = [
        "https://umap.openstreetmap.fr/es/datalayer/$MAP_ID/$uuid/",
        "https://umap.openstreetmap.fr/en/datalayer/$MAP_ID/$uuid/",
        "https://umap.openstreetmap.fr/api/0.1/map/$MAP_ID/layer/$uuid/data/",
        "https://umap.openstreetmap.fr/es/map/_/$MAP_ID?data=$uuid&format=geojson",
    ];

    $descargado = false;
    foreach ($urls as $url) {
        echo "   ⬇️  probando: $url\n";
        $resp = descargarCapa($url, $PROXY_URL, $COOKIE, $USER_AGENTS);
        if ($resp !== false) {
            // Verificar que sea JSON válido
            $json = json_decode($resp, true);
            if (is_array($json) && isset($json['features'])) {
                $features = count($json['features']);
                file_put_contents($destFile, $resp);
                echo "   ✅ GUARDADO: $features features, " . round(strlen($resp)/1024,1) . " KB\n\n";
                $OK++;
                $descargado = true;
                break;
            }
        }
        sleep(1);
    }

    if (!$descargado) {
        echo "   ❌ FALLÓ\n\n";
        $FAIL++;
    }
}

// ============================================================
// RESUMEN FINAL
// ============================================================
echo "\n═══════════════════════════════════════════════\n";
echo "📊 RESUMEN: OK=$OK / FALLOS=$FAIL\n";

if ($OK > 0) {
    echo "\n✅ Ejecuta ahora:\n";
    echo "   php api/sincronizar.php\n";
}