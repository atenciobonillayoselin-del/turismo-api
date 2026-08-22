<?php
// api/verificar_nombre.php
require_once '../config/database.php';

header('Content-Type: application/json');

$nombre = $_GET['nombre'] ?? '';

if (empty($nombre)) {
    echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
    exit;
}

try {
    $sql = "SELECT id_usuario FROM usuario WHERE nombre = :nombre AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nombre' => $nombre]);
    $existe = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'disponible' => !$existe,
        'mensaje' => $existe ? 'Nombre no disponible' : 'Nombre disponible'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>