<?php
/**
 * scripts/test_worker.php - Prueba TODAS las capas via Worker de Cloudflare
 * 
 * Uso: php scripts/test_worker.php
 */

$WORKER = 'https://umap-proxy-turismo.atenciobonillayoselin.workers.dev/?url=';
$MAP_ID = 1451289;

$capas = [
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Minibus 254 - IDA (Mirador Montículo)',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Minibus 254 - VUELTA (Mirador Montículo)',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Minibus 204 - IDA (Mirador Killi Killi)',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Minibus 889 - IDA (Plaza Villarroel)',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Minibus 889 - VUELTA (Plaza Villarroel)',
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Minibus 364 - IDA (Parque Laikakota)',
    '291c212e-44db-4460-b84e-773bcfede107' => 'Minibus 364 - VUELTA (Parque Laikakota)',
];

echo "🧪 Probando Worker de Cloudflare con todas las capas...\n";
echo "Worker: $WORKER\n";
echo "Mapa ID: $MAP_ID\n";
echo str_repeat("═", 80) . "\n\n";

$ok = 0;
$fail = 0;

foreach ($capas as $cid => $nombre) {
    // CAMBIO CLAVE AQUÍ: Usar /es/datalayer/{MAP_ID}/{LAYER_UUID}/
    $url = "https://umap.openstreetmap.fr/es/datalayer/$MAP_ID/$cid/";
    $proxyUrl = $WORKER . urlencode($url);
    
    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TurismoLaPaz-Test/4.0',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $size = strlen($resp);
    curl_close($ch);
    
    $json = json_decode($resp, true);
    $features = 0;
    $isValid = false;
    
    if (is_array($json) && isset($json['type']) && $json['type'] === 'FeatureCollection') {
        $features = isset($json['features']) ? count($json['features']) : 0;
        $isValid = ($features > 0);
    }
    
    $status = ($code === 200 && $isValid) ? '✅' : '❌';
    if ($code === 200 && $isValid) $ok++;
    else $fail++;
    
    echo sprintf(
        "%-45s | HTTP %d | %7d bytes | %4d features | %s\n",
        $nombre,
        $code,
        $size,
        $features,
        $status
    );
}

echo "\n" . str_repeat("═", 80) . "\n";
echo "📊 RESUMEN: OK=$ok / FALLOS=$fail / TOTAL=" . count($capas) . "\n";

if ($ok === count($capas)) {
    echo "\n🎉 TODAS LAS CAPAS FUNCIONAN CORRECTAMENTE VIA WORKER!\n";
    echo "El sistema de sincronización usará este método como prioridad #1.\n";
} else {
    echo "\n⚠️ Algunas capas fallaron. Verifica:\n";
    echo "  1) El Worker está desplegado en Cloudflare\n";
    echo "  2) La URL del Worker es correcta\n";
    echo "  3) El mapa es público o tienes la cookie de sesión configurada\n";
}