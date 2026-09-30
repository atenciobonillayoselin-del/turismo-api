<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener todos los lugares activos
    $stmt = $pdo->prepare("
        SELECT lt.id_lugar, lt.nombre, lt.latitud, lt.longitud
        FROM lugar_turistico lt
        WHERE lt.activo = 1
        ORDER BY lt.id_lugar ASC
    ");
    $stmt->execute();
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar cuáles tienen modelos 3D
    foreach ($lugares as &$lugar) {
        $lugarId = $lugar['id_lugar'];
        
        $stmt3d = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = '3d' AND activo = 1
        ");
        $stmt3d->execute([$lugarId]);
        $count = $stmt3d->fetch(PDO::FETCH_ASSOC);
        
        $lugar['tiene_modelo3d'] = $count['total'] > 0;
        $lugar['cantidad_modelos3d'] = (int)$count['total'];
    }
    unset($lugar);

    echo json_encode([
        'success' => true,
        'total_lugares' => count($lugares),
        'con_modelo3d' => count(array_filter($lugares, fn($l) => $l['tiene_modelo3d'])),
        'sin_modelo3d' => count(array_filter($lugares, fn($l) => !$l['tiene_modelo3d'])),
        'data' => $lugares,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
