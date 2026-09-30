<?php
require 'config/database.php';

$stmt = $pdo->prepare("SELECT id_lugar, nombre, costo, costo_nino, costo_adulto, costo_tercera_edad, es_gratuito, edad_max_nino, costo_extranjero, horarios FROM lugar_turistico WHERE nombre LIKE ?");
$stmt->execute(['%Laikakota%']);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
