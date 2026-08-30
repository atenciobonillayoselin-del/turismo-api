<?php
/**
 * scripts/descargar_capas_local.php
 * -------------------------------------------------------------
 * SCRIPT LOCAL DE RESCATE - se ejecuta en TU PC (Laragon, XAMPP, etc.)
 * No bloquea IPs porque usamos TU navegador / tu red doméstica.
 * -------------------------------------------------------------
 * MODO DE USO:
 *   1) php scripts/descargar_capas_local.php
 *   2) php scripts/descargar_capas_local.php --force     (sobrescribe .json)
 *   3) php scripts/descargar_capas_local.php --proxy "https://tu-worker.workers.dev/?url="
 *   4) php scripts/descargar_capas_local.php --cookie "sessionid=xxxxx; csrftoken=yyyy"
 *
 * POSTERIORMENTE:
 *   git add data/umap_cache/*.json
 *   git commit -m "cache(umap): 7 capas descargadas manualmente"
 *   git push origin main
 *
 * El sincronizar.php en Render.com COGERÁ ESTOS .json via NIVEL 5 (GitHub Raw).
 */

declare(strict_types=1);

// ============================================================
// CONFIGURACIÓN
// ============================================================
$MAP_ID     = 873950;   // ⚠️ CORRECTO (no 1447967)
$CACHE_DIR  = dirname(__DIR__) . '/data/umap_cache';
$FORCE      = in_array('--force', $argv, true);

$CAPAS = [
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA, Mirador Montículo',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA, Mirador Montículo',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA, Mirador Killi Killi',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA, Plaza Villarroel',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA, Plaza Villarroel',
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA, Parque Laikakota',
    '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA, Parque Laikakota',
];

// ============================================================
// CONSOLA CLI SOLO
// ============================================================
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo se permite ejecución desde línea de comandos (CLI).\n");
}

$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
];

// Leer args CLI: --proxy URL --cookie "sessionid=..."
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

function buildUrls(int $mid, string $cid): array {
    return [
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$cid/data/",
        "https://umap.openstreetmap.fr/api/0.1/map/$mid/layer/$cid/data?format=geojson",
        "https://umap.openstreetmap.fr/es/map/_/$mid?data=$cid&format=geojson",
        "https://umap.openstreetmap.fr/en/map/_/$mid?data=$cid&format=geojson",
        "https://umap.openstreetmap.fr/es/datalayer/$mid/$cid/?format=geojson",
        "https://u.osmfr.org/m/$mid/datalayer/$cid/?format=geojson",
    ];
}

function esJsonValidoCapa(string $body): bool {
    if (strlen($body) < 100) return false;
    $json = json_decode($body, true);
    if (!is_array($json)) return false;
    if (!isset($json['type']) || strtolower((string)$json['type']) !== 'featurecollection') return false;
    return isset($json['features']) && is_array($json['features']);
}

function descargar(string $url, string $proxy, string $cookie, array $uas): string|false {
    global $MAP_ID;
    $target = $url;
    if ($proxy !== '') {
        // Proxy espera ?url=ENCODED
        $sep = (str_contains($proxy, '?') ? '&' : '?');
        $target = $proxy . urlencode($url);
    }

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_ENCODING       => 'gzip, deflate, br',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => $uas[array_rand($uas)],
        CURLOPT_HTTPHEADER     => [
            'Accept: application/geo+json, application/json, text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: max-age=0',
            'Pragma: no-cache',
            'Referer: https://umap.openstreetmap.fr/en/map/la-paz-turistico_' . $MAP_ID,
            'Origin: https://umap.openstreetmap.fr',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            $cookie !== '' ? 'Cookie: ' . $cookie : 'X-No-Cookie: 1',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp !== false && $code >= 200 && $code < 300 && strlen($resp) > 100) {
        return $resp;
    }
    if ($resp === false && $err !== '') {
        file_put_contents('php://stderr', "    [curl err] $err (HTTP/$code)\n");
    }
    return false;
}

// ============================================================
// BUCLE PRINCIPAL
// ============================================================
$OK   = 0;
$FAIL = 0;
$SKIP = 0;

echo "🚀 Descargando " . count($CAPAS) . " capas de uMap (MAP#$MAP_ID)\n";
echo "   Proxy: " . ($PROXY_URL === '' ? 'DIRECTO' : $PROXY_URL) . "\n";
echo "   Cookie: " . ($COOKIE === '' ? 'NO (mapa público)' : '✓ Configurada (sesión)') . "\n";
echo "   Forzar sobrescritura: " . ($FORCE ? 'SÍ' : 'NO') . "\n\n";

foreach ($CAPAS as $cid => $nombre) {
    $destFile = "$CACHE_DIR/$cid.json";
    echo "▶️ $nombre\n   ID : $cid\n";

    if (!$FORCE && is_file($destFile) && filesize($destFile) > 1000) {
        echo "   ⏭️  Saltando (ya existe y --force no activo), size=" . round(filesize($destFile)/1024,1) . "KB\n\n";
        $SKIP++;
        continue;
    }

    $urls    = buildUrls($MAP_ID, $cid);
    $encontrado = false;
    foreach ($urls as $u) {
        echo "   ⬇️  probando $u\n";
        $intentosOk = false;
        for ($i = 1; $i <= 3 && !$intentosOk; $i++) {
            $resp = descargar($u, $PROXY_URL, $COOKIE, $USER_AGENTS);
            if ($resp !== false && esJsonValidoCapa($resp)) {
                $ok = file_put_contents($destFile, $resp);
                if ($ok !== false) {
                    echo "   ✅ GUARDADO → $cid.json (" . round(strlen($resp)/1024,1) . " KB)\n      Features: " . count(json_decode($resp, true)['features']) . "\n\n";
                    $OK++;
                    $encontrado = true;
                    break 2;
                }
            }
            echo "     ❌ intento $i/3 falló, esperando 2s...\n";
            sleep(2);
        }
    }

    if (!$encontrado) {
        echo "   ❌ CRÍTICO: No se pudo descargar esta capa (¿URL equivocada o cookie inválida?)\n\n";
        $FAIL++;
    }
}

// ============================================================
// RESUMEN FINAL
// ============================================================
echo "\n═══════════════════════════════════════════════\n";
echo "📊 RESUMEN FINAL: OK=$OK / FALLOS=$FAIL / SALTADOS=$SKIP\n";

if ($OK === count($CAPAS)) {
    echo "\n🎉 TODO DESCARGADO. Ahora subelo a GitHub:\n\n";
    echo "     git add data/umap_cache/*.json\n";
    echo "     git commit -m \"cache(umap): 7 capas GeoJSON descargadas localmente\"\n";
    echo "     git push origin main\n";
    echo "\n💡 Luego el sincronizar.php de Render usará NIVEL 5 (GitHub Raw) automáticamente.\n";
    exit(0);
}

echo "❌ Faltan capas. Soluciones:\n";
echo "   1) El mapa es PRIVADO? Agrega:  --cookie \"sessionid=XXX; csrftoken=YYY\"\n";
echo "   2) IP bloqueada? Usa proxy:    --proxy \"https://tu-worker.workers.dev/?url=\"\n";
echo "   3) Recuerda: MAP_ID correcto = 873950\n";
exit(1);
