<?php
require 'config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Corregir Museo Nacional de Historia Natural (id_lugar = 10)
    $stmt = $pdo->prepare("UPDATE lugar_turistico SET abierto_todos_los_dias = 0 WHERE id_lugar = 10");
    $stmt->execute();
    $filasMuseo = $stmt->rowCount();

    // Verificar el resultado
    $stmt = $pdo->prepare("SELECT id_lugar, nombre, abierto_todos_los_dias, horarios FROM lugar_turistico WHERE id_lugar = 10");
    $stmt->execute();
    $museo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Base de datos actualizada correctamente',
        'filas_afectadas' => $filasMuseo,
        'datos_actualizados' => $museo
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
