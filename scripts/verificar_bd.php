<?php
/**
 * scripts/verificar_bd.php - Consulta los registros en la BD
 */
require_once __DIR__ . '/../config/database.php'; // O la ruta a tu conexión DB

try {
    // Si usas PDO:
    // Ajusta 'rutas' o 'lugares' al nombre real de tu tabla en MySQL
    $stmt = $pdo->query("SELECT id, nombre, updated_at FROM rutas");
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 REGISTROS EN LA BASE DE DATOS:\n";
    echo str_repeat("═", 60) . "\n";
    echo sprintf("%-5s | %-35s | %-15s\n", "ID", "Nombre", "Última Actualización");
    echo str_repeat("─", 60) . "\n";

    foreach ($rutas as $row) {
        echo sprintf(
            "%-5s | %-35s | %-15s\n", 
            $row['id'], 
            substr($row['nombre'] ?? 'Sin nombre', 0, 35), 
            $row['updated_at'] ?? 'N/A'
        );
    }

    echo str_repeat("═", 60) . "\n";
    echo "Total de registros encontrados: " . count($rutas) . "\n";

} catch (Exception $e) {
    echo "❌ Error al consultar la BD: " . $e->getMessage() . "\n";
}