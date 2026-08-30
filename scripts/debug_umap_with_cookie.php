<?php
// scripts/debug_umap_with_cookie.php
// ============================================================
// PRUEBA DEFINITIVA - usa la COOKIE DE SESIÓN que extrajiste de tu navegador
// (sessionid=kxdkws9gw2jnzbbwyd4ncf48tlyyf0xc)
// ============================================================

$SESSIONID = 'kxdkws9gw2jnzbbwyd4ncf48tlyyf0xc';
$MAP  = 873950;
$SLUG = 'la-paz-turistico';

// Estas son TODAS las URLs que umap.openstreetmap.fr SABE generar.
// Incluimos 15 variantes (vistas en código fuente de umap Django):
$URLS = [
    // ========= 1) MAPA COMPLETO =========
    'MAP full ?format=geojson es'    => "https://umap.openstreetmap.fr/es/map/{$SLUG}_{$MAP}?format=geojson",
    'MAP full ?format=geojson en'    => "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?format=geojson",
    'MAP full /m/ID?format=geojson'  => "https://umap.openstreetmap.fr/m/{$MAP}?format=geojson",
    'MAP full ?format=json (umapcfg)'=> "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?format=json",
    'MAP full ?json (legacy umapcfg)'=> "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?json",

    // ========= 2) API 0.1 (versiones Django) =========
    'API v0.1 /map/ID/'              => "https://umap.openstreetmap.fr/api/0.1/map/{$MAP}/",
    'API v0.1 /map/ID/layer/'        => "https://umap.openstreetmap.fr/api/0.1/map/{$MAP}/layer/",
    'API v0.1 map/ID/datalayer/UUID/'=> "https://umap.openstreetmap.fr/api/0.1/map/{$MAP}/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/",
    'API v0.1 /datalayer/UUID/'      => "https://umap.openstreetmap.fr/api/0.1/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/",
    'API v0.2 /maps/ID.json'         => "https://umap.openstreetmap.fr/api/umap/v1/maps/{$MAP}.json",

    // ========= 3) DATALAYER (UUID corto = 8bfdeb7b-421c...) =========
    'DL /es/datalayer/ID_MAP/ID_LAYER/?f=geojson' => "https://umap.openstreetmap.fr/es/datalayer/{$MAP}/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson",
    'DL /en/datalayer/ID_LAYER/'               => "https://umap.openstreetmap.fr/en/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/",
    'DL /datalayer/ID_LAYER?f=geojson'         => "https://umap.openstreetmap.fr/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc?format=geojson",
    'DL /es/datalayer/ID_LAYER/geojson/'       => "https://umap.openstreetmap.fr/es/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/geojson/",

    // ========= 4) ?data= en URL del mapa (capa incrustada) =========
    'MAP /en/SLUG_ID?data=ID&format=geojson'   => "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?data=8bfdeb7b-421c-4ff6-9643-53c75c3a88bc&format=geojson",
    'MAP /m/ID?data=ID&format=geojson'         => "https://umap.openstreetmap.fr/m/{$MAP}?data=8bfdeb7b-421c-4ff6-9643-53c75c3a88bc&format=geojson",

    // ========= 5) Mirror u.osmfr.org =========
    'osmfr full /m/ID?format=geojson'          => "https://u.osmfr.org/m/{$MAP}?format=geojson",
    'osmfr /datalayer/ID_LAYER'                => "https://u.osmfr.org/m/{$MAP}/datalayer/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson",

    // ========= 6) oEmbed =========
    'oEmbed' => "https://umap.openstreetmap.fr/oembed/?url=".urlencode("https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}")."&format=json",
];

// Cookie COMPLETA (sessionid + csrftoken + referer)
$COOKIE = "sessionid={$SESSIONID}; csrftoken=UmFpZE9ySzBuQTFwWjRsMzRqT2lXQmRYdUlXSHJmTFNKMEhQbEtFRjV0SjIyZGZFbkFpME93c3BKVldBWW1paw==";
$CSRF   = 'UmFpZE9ySzBuQTFwWjRsMzRqT2lXQmRYdUlXSHJmTFNKMEhQbEtFRjV0SjIyZGZFbkFpME93c3BKVldBWW1paw==';

$UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

echo "🧪 PRUEBA DEFINITIVA: Map#$MAP + Cookie sessionid=$SESSIONID (32 char, típica Django)\n\n";

foreach ($URLS as $name => $url) {
    echo str_repeat("─",110)."\n";
    echo "▶ $name\n";
    echo "  $url\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 8,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            "Cookie: $COOKIE",
            "X-CSRFToken: $CSRF",
            'Accept: application/geo+json, application/json, text/html, */*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            "Referer: https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}",
            'Origin: https://umap.openstreetmap.fr',
            'Cache-Control: no-cache',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $sz   = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $fin  = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    echo "  → HTTP=$code | size=".round($sz/1024,1)."KB | ctype=$ct\n";
    if ($fin !== $url) echo "  ↳ URL final: $fin\n";

    if (is_string($resp) && $resp !== '') {
        $sep = "\r\n\r\n";
        $pos = strpos($resp, $sep);
        if ($pos===false) { $sep="\n\n"; $pos=strpos($resp,$sep); }
        $body = $pos===false ? $resp : substr($resp,$pos+strlen($sep));
        $j = json_decode($body, true);

        $geo = (is_array($j) && ($j['type']??'') === 'FeatureCollection');
        $cfg = (is_array($j) && (isset($j['map']) || isset($j['datalayers']) || isset($j['slug'])));

        if ($geo) {
            $nf = count($j['features'] ?? []);
            echo "  🎉🎉🎉 FEATURE COLLECTION VÁLIDA ($nf features)!!! ÉSTA ES LA URL BUENA!!\n";
            $f = dirname(__DIR__)."/data/umap_cache/_ENCONTRADO_".preg_replace('#[^a-z0-9]+#i','_', $name).".json";
            @mkdir(dirname($f),0777,true);
            file_put_contents($f, $body);
            echo "  guardado en $f\n";
        } elseif ($cfg) {
            echo "  🔧 Config de mapa: keys=".implode(',',array_keys($j))."\n";
            if (isset($j['datalayers'])) {
                echo "  👉 datalayers = ".json_encode($j['datalayers'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
            }
            $f = dirname(__DIR__)."/data/umap_cache/_CFG_".preg_replace('#[^a-z0-9]+#i','_', $name).".json";
            @mkdir(dirname($f),0777,true);
            file_put_contents($f, $body);
        } elseif ($code === 403 || $code === 401) {
            echo "  🔐 Acceso denegado. La cookie SESIÓN es INVÁLIDA / EXPIRÓ. Necesitas extraerla NUEVAMENTE de tu navegador (F12→Application→Cookies)\n";
            break 1;
        } elseif ($code === 200 && $sz>1000) {
            $head = substr(trim(strip_tags($body)), 0, 400);
            echo "  ℹ️  HTML 200. Quizás es el mapa renderizado (no JSON). Primeros chars: $head\n";
        } else {
            $head = substr(trim(strip_tags($body)),0,200);
            echo "  $head\n";
        }
    }
    echo "\n";
}
