<?php
// diagnostico.php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $kmlUrl = 'https://drive.google.com/uc?export=download&id=13SoAxhs7sP-GIW-SKdfVRv_186C6Lj7r';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $kmlUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $kmlReal = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || $kmlReal === false) {
        throw new Exception('No se pudo descargar el KML. Código: ' . $httpCode);
    }
    
    // ===== BUSCAR TODOS LOS NOMBRES DE CARPETAS =====
    preg_match_all('/<Folder>.*?<name>(.*?)<\/name>/s', $kmlReal, $folderNames);
    
    // ===== BUSCAR TODOS LOS NOMBRES DE PLACEMARK CON LineString =====
    preg_match_all('/<Placemark>.*?<name>(.*?)<\/name>.*?<LineString>/s', $kmlReal, $pmNames);
    
    echo json_encode([
        'success' => true,
        'carpetas' => $folderNames[1] ?? [],
        'placemarks_con_ruta' => $pmNames[1] ?? [],
        'total_carpetas' => count($folderNames[1] ?? []),
        'total_placemarks' => count($pmNames[1] ?? [])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>