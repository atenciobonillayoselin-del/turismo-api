<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar si hay modelos 3D en la base de datos
    $stmt = $pdo->prepare("
        SELECT lm.id_lugar, lt.nombre, lm.tipo, lm.url, lm.descripcion, lm.orden, lm.activo
        FROM lugar_multimedia lm
        LEFT JOIN lugar_turistico lt ON lm.id_lugar = lt.id_lugar
        WHERE lm.tipo = '3d'
        ORDER BY lm.id_lugar, lm.orden
    ");
    $stmt->execute();
    $modelos3d = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => count($modelos3d),
        'data' => $modelos3d,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
