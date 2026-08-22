<?php
// test_db.php
require_once 'config/database.php';

try {
    // Probar conexión
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    echo "✅ Conexión a MySQL exitosa!\n";
    echo "Resultado: " . print_r($result, true);
    
    // Verificar tabla usuario
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuario'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'usuario' existe\n";
        
        // Contar usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuario");
        $total = $stmt->fetch();
        echo "📊 Total de usuarios: " . $total['total'] . "\n";
    } else {
        echo "❌ Tabla 'usuario' NO existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>