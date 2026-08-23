<?php
// api/test_connection.php - Prueba de conexion a MySQL
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'checks' => []
];

if (extension_loaded('pdo_mysql')) {
    $result['checks'][] = '✅ Extension pdo_mysql disponible';
} else {
    $result['checks'][] = '❌ Extension pdo_mysql NO disponible';
    $result['success'] = false;
}

try {
    require_once '../config/database.php';

    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch()['version'];
    $result['checks'][] = "✅ Conexion a MySQL exitosa (version: $version)";

    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'app_turistica_la_paz'");
    if ($stmt->fetch()) {
        $result['checks'][] = "✅ Base de datos app_turistica_la_paz existe";

        $stmt = $pdo->query("SHOW TABLES LIKE 'usuario'");
        if ($stmt->fetch()) {
            $result['checks'][] = "✅ Tabla usuario existe";

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario");
            $total = $stmt->fetch()['total'];
            $result['checks'][] = "📊 Total de usuarios en MySQL: $total";
        } else {
            $result['checks'][] = "❌ Tabla usuario NO existe";
            $result['success'] = false;
        }
    } else {
        $result['checks'][] = "❌ Base de datos app_turistica_la_paz NO existe";
        $result['success'] = false;
    }

} catch (Exception $e) {
    $result['success'] = false;
    $result['checks'][] = '❌ Error de conexion: ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
