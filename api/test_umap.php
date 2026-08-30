<?php
header('Content-Type: application/json');

$url = 'https://umap.openstreetmap.fr/es/datalayer/1447967/8bfdeb7b-421c-4ff6-9643-53c75c3a88bc/?format=geojson';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: application/geo+json, application/json',
        'Accept-Language: es-ES,es;q=0.9',
        'Referer: https://umap.openstreetmap.fr/',
        'Origin: https://umap.openstreetmap.fr',
    ],
]);

$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$info = curl_getinfo($ch);
curl_close($ch);

echo json_encode([
    'success' => $code === 200,
    'http_code' => $code,
    'ip' => $info['primary_ip'] ?? 'desconocida',
    'response_length' => strlen($resp),
    'preview' => substr($resp, 0, 500),
    'error' => curl_error($ch) ?? null,
]);