<?php
require 'config/database.php';

echo "=== VERIFICACIÓN COMPLETA ===\n\n";

// 1. Verificar base de datos
echo "1. BASE DE DATOS (Laikakota):\n";
$stmt = $pdo->prepare("SELECT es_gratuito, costo, costo_nino, costo_adulto, costo_estudiante, costo_tercera_edad, costo_adulto_mayor, costo_extranjero FROM lugar_turistico WHERE id_lugar = 2");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "es_gratuito: " . ($result['es_gratuito'] ? 'true' : 'false') . "\n";
echo "costo: " . $result['costo'] . "\n";
echo "costo_nino: " . $result['costo_nino'] . "\n";
echo "costo_adulto: " . $result['costo_adulto'] . "\n";
echo "costo_estudiante: " . ($result['costo_estudiante'] ?? 'null') . "\n";
echo "costo_tercera_edad: " . $result['costo_tercera_edad'] . "\n";
echo "costo_adulto_mayor: " . ($result['costo_adulto_mayor'] ?? 'null') . "\n";
echo "costo_extranjero: " . $result['costo_extranjero'] . "\n\n";

// 2. Verificar API
echo "2. API (lugares.php - Laikakota):\n";
$sql = "SELECT lt.id_lugar, lt.nombre, lt.es_gratuito, lt.costo, lt.costo_nino, lt.costo_adulto, lt.costo_estudiante, lt.costo_tercera_edad, lt.costo_adulto_mayor, lt.costo_extranjero
        FROM lugar_turistico lt
        WHERE lt.id_lugar = 2";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "es_gratuito: " . ($result['es_gratuito'] ? 'true' : 'false') . "\n";
echo "costo: " . $result['costo'] . "\n";
echo "costo_nino: " . $result['costo_nino'] . "\n";
echo "costo_adulto: " . $result['costo_adulto'] . "\n";
echo "costo_estudiante: " . ($result['costo_estudiante'] ?? 'null') . "\n";
echo "costo_tercera_edad: " . $result['costo_tercera_edad'] . "\n";
echo "costo_adulto_mayor: " . ($result['costo_adulto_mayor'] ?? 'null') . "\n";
echo "costo_extranjero: " . $result['costo_extranjero'] . "\n\n";

echo "=== FIN VERIFICACIÓN ===\n";
