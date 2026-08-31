SET SQL_SAFE_UPDATES = 0;

UPDATE lugar_turistico
SET nombre = CASE uuid_capa
    WHEN '0a5a5bfc-8c95-4fea-8400-3a8438a2b533' THEN 'Parque Laikakota'
    WHEN '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc' THEN 'Mirador Montículo'
    WHEN '1131cb1a-631f-4d7b-8f33-f46a469366f9' THEN 'Mirador Montículo (Vuelta)'
    WHEN '34f4c3be-3ec9-400b-9b82-c3be983df2dd' THEN 'Mirador Killi Killi'
    WHEN 'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47' THEN 'Plaza Villarroel'
    WHEN 'fa904f68-9ee2-4e12-b3a4-8406f357def5' THEN 'Plaza Villarroel (Vuelta)'
    WHEN '291c212e-44db-4460-b84e-773bcfede107' THEN 'Parque Laikakota (Vuelta)'
    WHEN 'cota_cota_838' THEN 'Laguna Cota Cota'
    ELSE nombre
END
WHERE uuid_capa IN (
    '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
    '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
    '1131cb1a-631f-4d7b-8f33-f46a469366f9',
    '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
    'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
    'fa904f68-9ee2-4e12-b3a4-8406f357def5',
    '291c212e-44db-4460-b84e-773bcfede107',
    'cota_cota_838'
);

DELETE FROM ruta_lugar;

INSERT INTO ruta_lugar (id_ruta, id_lugar, orden)
SELECT r.id_ruta, l.id_lugar, 1
FROM ruta r
JOIN lugar_turistico l ON r.uuid_capa = l.uuid_capa
WHERE r.activo = 1 AND l.activo = 1;

SELECT id_lugar, nombre, uuid_capa
FROM lugar_turistico
WHERE uuid_capa IN (
  '0a5a5bfc-8c95-4fea-8400-3a8438a2b533',
  '8bfdeb7b-421c-4ff6-9643-53c75c3a88bc',
  '1131cb1a-631f-4d7b-8f33-f46a469366f9',
  '34f4c3be-3ec9-400b-9b82-c3be983df2dd',
  'ce66785e-ee35-4de4-b5d8-3ab0d57e1e47',
  'fa904f68-9ee2-4e12-b3a4-8406f357def5',
  '291c212e-44db-4460-b84e-773bcfede107',
  'cota_cota_838'
)
ORDER BY id_lugar;

SELECT COUNT(*) AS total_ruta_lugar FROM ruta_lugar;
