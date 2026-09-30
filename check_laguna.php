<?php
require 'config/database.php';

$stmt = $pdo->prepare("SELECT id_lugar, nombre, es_gratuito, costo, costo_nino, costo_adulto FROM lugar_turistico WHERE id_lugar = 4");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Laguna Cota Cota (BBDD LOCAL):\n";
echo "es_gratuito: " . ($result['es_gratuito'] ? 'true' : 'false') . " (valor: " . $result['es_gratuito'] . ")\n";
echo "costo: " . $result['costo'] . "\n";
echo "costo_nino: " . $result['costo_nino'] . "\n";
echo "costo_adulto: " . $result['costo_adulto'] . "\n";
