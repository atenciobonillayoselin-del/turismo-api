<?php
require 'config/database.php';

$stmt = $pdo->prepare("SELECT id_lugar, nombre, costo, costo_nino, costo_adulto, costo_estudiante, costo_tercera_edad, costo_adulto_mayor, costo_extranjero, es_gratuito FROM lugar_turistico WHERE id_lugar = 2");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
