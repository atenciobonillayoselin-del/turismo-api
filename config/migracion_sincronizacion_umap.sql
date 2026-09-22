-- =========================================================================
-- MIGRACIÓN MANUAL: Agregar columnas faltantes para sincronización uMap
-- Ejecuta esto SI la migración automática en sincronizar.php falla.
-- Base de datos: defaultdb  (o app_turistica_la_paz en local)
-- =========================================================================

-- 1. Lugar Turístico: agregar columnas de trazabilidad con uMap
ALTER TABLE lugar_turistico
    ADD COLUMN IF NOT EXISTS grupo_umap   VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS id_umap      VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS icono_umap   VARCHAR(50)  NULL,
    ADD COLUMN IF NOT EXISTS color_hex    CHAR(7)      NULL;

-- 2. Ruta: agregar columnas de sentido, IDs de uMap y coordenadas serializadas
ALTER TABLE ruta
    ADD COLUMN IF NOT EXISTS sentido        ENUM('IDA','VUELTA','NORMAL') NOT NULL DEFAULT 'NORMAL',
    ADD COLUMN IF NOT EXISTS id_umap        VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS id_grupo_umap  VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS coords_geojson LONGTEXT     NULL COMMENT 'GeoJSON LineString serializado';

-- 3. Parada: agregar trazabilidad con uMap
ALTER TABLE parada
    ADD COLUMN IF NOT EXISTS id_umap VARCHAR(100) NULL;

-- 4. Tabla de log de sincronizaciones (si no existe)
CREATE TABLE IF NOT EXISTS sincronizacion_log (
    id_log           INT AUTO_INCREMENT PRIMARY KEY,
    origen           VARCHAR(100) NOT NULL,
    status           ENUM('OK','PARCIAL','ERROR') NOT NULL DEFAULT 'OK',
    metodo_descarga  VARCHAR(50)  NULL,
    total_leidos     INT DEFAULT 0,
    lugares_insert   INT DEFAULT 0,
    lugares_update   INT DEFAULT 0,
    rutas_insert     INT DEFAULT 0,
    rutas_update     INT DEFAULT 0,
    paradas_insert   INT DEFAULT 0,
    ruta_lugar_ok    INT DEFAULT 0,
    ruta_parada_ok   INT DEFAULT 0,
    error_msg        TEXT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_fecha (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 5. Índices para búsquedas rápidas por ID de uMap
ALTER TABLE lugar_turistico ADD INDEX IF NOT EXISTS idx_lugar_id_umap (id_umap);
ALTER TABLE ruta            ADD INDEX IF NOT EXISTS idx_ruta_id_umap    (id_umap);
ALTER TABLE parada          ADD INDEX IF NOT EXISTS idx_parada_id_umap  (id_umap);
ALTER TABLE lugar_turistico ADD INDEX IF NOT EXISTS idx_lugar_grupo     (grupo_umap);
ALTER TABLE ruta            ADD INDEX IF NOT EXISTS idx_ruta_grupo      (id_grupo_umap);
