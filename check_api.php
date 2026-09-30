<?php
require 'config/database.php';

$sql = "SELECT lt.id_lugar, lt.nombre, lt.descripcion, lt.descripcion_corta, lt.latitud, lt.longitud,
               lt.direccion, lt.calificacion, lt.costo, lt.costo_nino, lt.costo_adulto, lt.costo_tercera_edad,
               lt.es_gratuito, lt.abierto_todos_los_dias, lt.horarios, lt.tipo_transporte,
               lt.activo, lt.created_at, lt.updated_at, lt.id_categoria,
               lt.edad_max_nino, lt.costo_extranjero,
               cl.nombre as categoria, cl.slug as categoria_slug, cl.icono as categoria_icono
        FROM lugar_turistico lt
        LEFT JOIN categoria_lugar cl ON lt.id_categoria = cl.id_categoria
        WHERE lt.id_lugar = 2";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$lugar = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($lugar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
