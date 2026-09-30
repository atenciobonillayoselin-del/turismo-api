<?php
require 'config/database.php';

$stmt = $pdo->prepare("SELECT id_lugar, nombre, es_gratuito, costo, costo_nino, costo_adulto FROM lugar_turistico ORDER BY id_lugar");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
