<?php
// scripts/debug_umap_http.php - Ver QUÉ código HTTP devuelve uMap exacto
$tests = [
    'API oficial /api/0.1/'   => 'https://umap.openstreetmap.fr/api/0.1/map/873950/layer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/data/',
    'Legacy datalayer es/'    => 'https://umap.openstreetmap.fr/es/datalayer/873950/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson',
    'Legacy datalayer en/'    => 'https://umap.openstreetmap.fr/en/datalayer/873950/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson',
    'Legacy MAPA_?data='      => 'https://umap.openstreetmap.fr/en/map/_/873950?data=8bfdeb7b-421c-4ff6-9643-53c75c3a88bc&format=geojson',
    'Mirrors u.osmfr.org'     => 'https://u.osmfr.org/m/873950/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson',
    'Mirror DE'               => 'https://umap.openstreetmap.de/de/datalayer/873950/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson',
];

$UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

foreach ($tests as $name => $url) {
    echo str_repeat("═", 100)."\n";
    echo "▶ $name\n";
    echo "  URL : $url\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/geo+json, application/json, */*',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Referer: https://umap.openstreetmap.fr/en/map/la-paz-turistico_873950',
            'Origin: https://umap.openstreetmap.fr',
            'Cache-Control: no-cache',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $sz   = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $fin  = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "  ➡️ HTTP: $code  | Size: $sz bytes | Content-Type: $ct\n";
    echo "  🔀 Final URL after redirects: $fin\n";
    if ($err !== '')  echo "  ❌ CURL ERROR: $err\n";

    // Mostrar primeros 600 bytes de body (sin headers)
    if (is_string($resp) && $resp !== '') {
        $sep = "\r\n\r\n";
        $pos = strpos($resp, $sep);
        if ($pos === false) $sep = "\n\n";
        $pos = $pos !== false ? $pos : strpos($resp, $sep);
        $body = $pos === false ? $resp : substr($resp, $pos + strlen($sep));
        $preview = trim(substr($body, 0, 700));
        echo "  📦 Primeros 700 bytes BODY:\n  ".str_replace("\n","\n  ",$preview)."\n";

        // ¿Es JSON válido?
        $j = json_decode($body, true);
        if (is_array($j)) {
            echo "  ✅ JSON VÁLIDO. Raíz type = ".($j['type'] ?? 'N/A')." | features=".(is_countable($j['features']??null)?count($j['features']):'N/A')."\n";
        } elseif ($code >= 200 && $code < 300 && $sz > 100) {
            echo "  ⚠️  HTTP 2xx pero NO JSON válido\n";
        }
    }
    echo "\n";
}
