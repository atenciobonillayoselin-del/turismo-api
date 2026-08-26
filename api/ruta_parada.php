<?php
// api/ruta_parada.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$idRuta = $_GET['id_ruta'] ?? $_GET['id'] ?? null;

if (!$idRuta) {
    echo json_encode([
        'success' => false,
        'error' => 'Se requiere id_ruta'
    ]);
    exit();
}

try {
    $sql = "SELECT 
                p.id_parada,
                p.nombre,
                p.latitud,
                p.longitud,
                rp.orden,
                rp.es_inicio,
                rp.es_fin
            FROM ruta_parada rp
            JOIN parada p ON rp.id_parada = p.id_parada
            WHERE rp.id_ruta = :id_ruta
            ORDER BY rp.orden ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_ruta' => $idRuta]);
    $paradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $paradas
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>