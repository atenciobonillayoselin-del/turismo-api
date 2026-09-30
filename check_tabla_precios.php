<?php
require 'config/database.php';

$stmt = $pdo->prepare("SELECT id_lugar, nombre, tabla_precios FROM lugar_turistico WHERE id_lugar = 2");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "BBDD LOCAL (Laikakota):\n";
echo "tabla_precios: " . ($result['tabla_precios'] ?? 'null') . "\n";
echo "Longitud: " . strlen($result['tabla_precios'] ?? '') . " caracteres\n";
echo "\nContenido:\n";
echo $result['tabla_precios'] ?? 'null';
