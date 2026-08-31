<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$mapa = [
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' => 'Parque Laikakota',
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' => 'Mirador Montículo',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9' => 'Mirador Montículo (Vuelta)',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd' => 'Mirador Killi Killi',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' => 'Plaza Villarroel',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5' => 'Plaza Villarroel (Vuelta)',
    '291c212e-44db-4460-b84e-773bcfede107' => 'Parque Laikakota (Vuelta)',
    'cota_cota_838' => 'Laguna Cota Cota',
];

try {
    $pdo->exec("SET SQL_SAFE_UPDATES = 0");

    $sqlUpdate = "UPDATE lugar_turistico
        SET nombre = CASE uuid_capa
            WHEN :uuid0 THEN :nombre0
            WHEN :uuid1 THEN :nombre1
            WHEN :uuid2 THEN :nombre2
            WHEN :uuid3 THEN :nombre3
            WHEN :uuid4 THEN :nombre4
            WHEN :uuid5 THEN :nombre5
            WHEN :uuid6 THEN :nombre6
            WHEN :uuid7 THEN :nombre7
            ELSE nombre
        END
        WHERE uuid_capa IN (:uuid0, :uuid1, :uuid2, :uuid3, :uuid4, :uuid5, :uuid6, :uuid7)";

    $params = [
        ':uuid0' => array_key_first($mapa),
        ':nombre0' => current($mapa),
    ];

    $keys = array_keys($mapa);
    $values = array_values($mapa);
    foreach ($keys as $i => $uuid) {
        $params[":uuid$i"] = $uuid;
        $params[":nombre$i"] = $values[$i];
    }

    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute($params);

    $pdo->exec("DELETE FROM ruta_lugar");
    $pdo->exec("INSERT INTO ruta_lugar (id_ruta, id_lugar, orden)
        SELECT r.id_ruta, l.id_lugar, 1
        FROM ruta r
        INNER JOIN lugar_turistico l ON r.uuid_capa = l.uuid_capa
        WHERE r.activo = 1 AND l.activo = 1");

    $stmtCheck = $pdo->query("SELECT id_lugar, nombre, uuid_capa FROM lugar_turistico WHERE uuid_capa IN ('0a5a5bfc-8c95-4fea-8400-3a8438a2b533','8bfdeb7b-421c-4ff6-9643-53c75c3a88bc','1131cb1a-631f-4d7b-8f33-f46a469366f9','34f4c3be-3ec9-400b-9b82-c3be983df2dd','ce66785e-ee35-4de4-b5d8-3ab0d57e1e47','fa904f68-9ee2-4e12-b3a4-8406f357def5','291c212e-44db-4460-b84e-773bcfede107','cota_cota_838') ORDER BY id_lugar");
    $lugares = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    $stmtRel = $pdo->query("SELECT COUNT(*) AS total FROM ruta_lugar");
    $rel = $stmtRel->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Nombres y relaciones reparadas',
        'lugares_actualizados' => count($lugares),
        'data' => $lugares,
        'ruta_lugar_total' => (int)($rel['total'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
