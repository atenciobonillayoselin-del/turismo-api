<?php
// scripts/debug_umap_fullmap.php - DESCARGAR MAPA COMPLETO (1 solo request)
// Prueba TODAS las variantes conocidas de "descargar mapa entero geojson"
$SLUG = 'la-paz-turistico';
$MAP  = 873950;

$urls = [
  '?format=geojson - es/map/SLUG_ID'    => "https://umap.openstreetmap.fr/es/map/{$SLUG}_{$MAP}?format=geojson",
  '?format=geojson - en/map/SLUG_ID'    => "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?format=geojson",
  '?format=geojson - /m/ID'             => "https://umap.openstreetmap.fr/m/{$MAP}?format=geojson",
  '?format=geojson - /es/m/ID'          => "https://umap.openstreetmap.fr/es/m/{$MAP}?format=geojson",
  'umap JSON config /en/map/SLUG_ID?json'=> "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?json",
  'umap JSON config /m/ID?json'         => "https://umap.openstreetmap.fr/m/{$MAP}?json",
  'api 0.1 map definition /api/0.1/map/'=> "https://umap.openstreetmap.fr/api/0.1/map/$MAP/",
  'oembed format=json'                  => "https://umap.openstreetmap.fr/en/map/{$SLUG}_{$MAP}?format=json",
  'legacy umap gist-like /m/ID.geojson' => "https://umap.openstreetmap.fr/m/{$MAP}.geojson",
];

$UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

foreach ($urls as $name => $url) {
    echo str_repeat('═', 110)."\n";
    echo "▶ $name\n";
    echo "  URL $url\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/geo+json, application/vnd.geo+json, application/json, */*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Referer: https://umap.openstreetmap.fr/en/map/'.$SLUG.'_'.$MAP,
            'Origin: https://umap.openstreetmap.fr',
            'Cache-Control: no-cache',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $sz   = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $fin  = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    echo "  ➡️ HTTP=$code | Size=".round($sz/1024,1)."KB | Content-Type=$ct\n";
    if ($fin !== $url) echo "  🔀 redirect final → $fin\n";

    if (is_string($resp) && $resp !== '') {
        $sep = "\r\n\r\n";
        $pos = strpos($resp, $sep);
        if ($pos === false) { $sep = "\n\n"; $pos = strpos($resp, $sep); }
        $body = $pos === false ? $resp : substr($resp, $pos + strlen($sep));
        $j = json_decode($body, true);
        $isGeo = is_array($j) && isset($j['type']) && stripos($j['type'],'FeatureCollection')!==false;
        $isMapCfg = is_array($j) && (isset($j['map']) || isset($j['datalayers']) || isset($j['properties']) || isset($j['slug']));

        if ($isGeo) {
            $n = count($j['features'] ?? []);
            echo "  🎉 GEOJSON VÁLIDO ($n features!) → PRUEBA ESTA URL FINAL\n";
            // Save it
            $safe = preg_replace('#[^a-zA-Z0-9_\-]+#','_', $name);
            $f = dirname(__DIR__)."/data/umap_cache/fullmap_{$MAP}_{$safe}.json";
            @mkdir(dirname($f), 0777, true);
            file_put_contents($f, $body);
            echo "  💾 Guardado en $f\n";

            // ¿Contiene la capa? Busca cualquier property con el UUID
            $hay = false;
            foreach ($j['features'] ?? [] as $f2) {
                $p = json_encode($f2['properties']??[], JSON_UNESCAPED_UNICODE);
                foreach (['8bfdeb7b','1131cb1a','34f4c3be','ce66785e','fa904f68','0a5a5bfc','291c212e'] as $needle) {
                    if (stripos($p, $needle)!==false) { $hay = true; break 2; }
                }
            }
            if ($hay) echo "  ✅ Contiene IDs UUID de capas! Perfecto.\n";
            else      echo "  ℹ️  Revisar properties - no hay UUIDs (capa puede estar en _storage_options)\n";
        } elseif ($isMapCfg) {
            echo "  🔧 Config JSON de mapa (no FeatureCollection) (keys=".implode(',',array_keys($j)).")\n";
            if (isset($j['datalayers']) && is_array($j['datalayers'])) {
                echo "  👉 datalayers ENCONTRADOS (count=".count($j['datalayers'])."). Aquí tienes los IDs REALES para /datalayer/ID/\n";
                foreach ($j['datalayers'] as $idx => $dl) {
                    $dlId   = $dl['id']   ?? $dl['pk']   ?? $dl['_pk']   ?? '?';
                    $dlName = $dl['name'] ?? $dl['title'] ?? ($dl['options']->name ?? $dl['options']['name'] ?? '?');
                    $dlUuid = $dl['uuid'] ?? '?';
                    echo "    [$idx] id=$dlId | uuid=$dlUuid | nombre=\"$dlName\"\n";
                }
            }
            $safe = preg_replace('#[^a-zA-Z0-9_\-]+#','_', $name);
            $f = dirname(__DIR__)."/data/umap_cache/mapconfig_{$MAP}_{$safe}.json";
            @mkdir(dirname($f), 0777, true);
            file_put_contents($f, $body);
            echo "  💾 Guardado en $f\n";
        } else {
            $head = substr(trim(strip_tags($body)), 0, 300);
            echo "  📦 Primeros 300 chars (sin tags HTML): $head\n";
        }
    }
    echo "\n";
}
