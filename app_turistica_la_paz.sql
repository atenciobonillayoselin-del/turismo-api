-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 23-08-2026 a las 01:46:14
-- Versión del servidor: 8.0.45
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `app_turistica_la_paz`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int NOT NULL,
  `tabla_afectada` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `id_registro` int NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_spanish_ci NOT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `usuario_cambio` varchar(60) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla de auditoría para registrar todos los cambios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_busqueda`
--

CREATE TABLE `historial_busqueda` (
  `id_busqueda` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `id_lugar` int DEFAULT NULL,
  `id_ruta` int DEFAULT NULL,
  `query_texto` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `latitud_usuario` decimal(10,8) DEFAULT NULL,
  `longitud_usuario` decimal(11,8) DEFAULT NULL,
  `fecha_busqueda` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Registro de búsquedas de usuarios para análisis';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lugar_turistico`
--

CREATE TABLE `lugar_turistico` (
  `id_lugar` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_spanish_ci,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `categoria` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `calificacion` decimal(3,2) DEFAULT NULL,
  `horario` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `abierto_ahora` tinyint(1) DEFAULT NULL,
  `imagen_url` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `panorama_url` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `audio_guia_url` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `galeria_urls` json DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parada`
--

CREATE TABLE `parada` (
  `id_parada` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `es_terminal` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Puntos de abordaje y bajada de las rutas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta`
--

CREATE TABLE `ruta` (
  `id_ruta` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_spanish_ci,
  `tipo` enum('micro','minibus','teleferico','otros') COLLATE utf8mb4_spanish_ci NOT NULL,
  `color_hex` char(7) COLLATE utf8mb4_spanish_ci DEFAULT '#0066CC',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta_lugar`
--

CREATE TABLE `ruta_lugar` (
  `id_ruta` int NOT NULL,
  `id_lugar` int NOT NULL,
  `orden` int DEFAULT NULL,
  `distancia_km` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Relación N:M entre rutas y lugares turísticos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta_parada`
--

CREATE TABLE `ruta_parada` (
  `id_ruta` int NOT NULL,
  `id_parada` int NOT NULL,
  `orden` int NOT NULL,
  `es_inicio` tinyint(1) DEFAULT '0',
  `es_fin` tinyint(1) DEFAULT '0',
  `tiempo_estimado` time DEFAULT NULL,
  `distancia_metros` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Relación N:M entre rutas y paradas con orden de recorrido';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sincronizacion_google`
--

CREATE TABLE `sincronizacion_google` (
  `id_sincronizacion` int NOT NULL,
  `fecha_sincronizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archivo_kml_url` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `estado` enum('PENDIENTE','PROCESANDO','COMPLETADO','ERROR') COLLATE utf8mb4_spanish_ci DEFAULT 'PENDIENTE',
  `rutas_agregadas` int DEFAULT '0',
  `rutas_actualizadas` int DEFAULT '0',
  `paradas_agregadas` int DEFAULT '0',
  `paradas_actualizadas` int DEFAULT '0',
  `mensaje_error` text COLLATE utf8mb4_spanish_ci,
  `usuario_ejecuto` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Registro de sincronizaciones con Google My Maps';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL,
  `email` varchar(80) COLLATE utf8mb4_spanish_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre` varchar(60) COLLATE utf8mb4_spanish_ci NOT NULL,
  `rol` enum('admin','editor','turista') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'turista',
  `activo` tinyint(1) DEFAULT '1',
  `firebase_uid` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `foto_perfil` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `carnet` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `perfil_completo` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Usuarios de la aplicación con roles específicos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_sesion`
--

CREATE TABLE `usuario_sesion` (
  `id_sesion` int NOT NULL,
  `id_usuario` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_auditoria_tabla_registro` (`tabla_afectada`,`id_registro`),
  ADD KEY `idx_auditoria_fecha` (`fecha_cambio` DESC);

--
-- Indices de la tabla `historial_busqueda`
--
ALTER TABLE `historial_busqueda`
  ADD PRIMARY KEY (`id_busqueda`),
  ADD KEY `fk_hb_usuario` (`id_usuario`),
  ADD KEY `fk_hb_lugar` (`id_lugar`),
  ADD KEY `fk_hb_ruta` (`id_ruta`);

--
-- Indices de la tabla `lugar_turistico`
--
ALTER TABLE `lugar_turistico`
  ADD PRIMARY KEY (`id_lugar`),
  ADD UNIQUE KEY `uq_lugar_coordenadas` (`latitud`,`longitud`),
  ADD KEY `idx_lugar_coordenadas` (`latitud`,`longitud`),
  ADD KEY `idx_lugar_categoria` (`categoria`),
  ADD KEY `idx_lugar_activo` (`activo`);

--
-- Indices de la tabla `parada`
--
ALTER TABLE `parada`
  ADD PRIMARY KEY (`id_parada`),
  ADD UNIQUE KEY `uq_parada_coordenadas` (`latitud`,`longitud`),
  ADD KEY `idx_parada_coordenadas` (`latitud`,`longitud`),
  ADD KEY `idx_parada_activo` (`activo`);

--
-- Indices de la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD PRIMARY KEY (`id_ruta`),
  ADD UNIQUE KEY `uq_ruta_nombre` (`nombre`);

--
-- Indices de la tabla `ruta_lugar`
--
ALTER TABLE `ruta_lugar`
  ADD PRIMARY KEY (`id_ruta`,`id_lugar`),
  ADD UNIQUE KEY `uq_rl_orden` (`id_ruta`,`orden`),
  ADD KEY `fk_rl_lugar` (`id_lugar`);

--
-- Indices de la tabla `ruta_parada`
--
ALTER TABLE `ruta_parada`
  ADD PRIMARY KEY (`id_ruta`,`id_parada`,`orden`),
  ADD UNIQUE KEY `uq_rp_orden` (`id_ruta`,`orden`),
  ADD KEY `fk_rp_parada` (`id_parada`);

--
-- Indices de la tabla `sincronizacion_google`
--
ALTER TABLE `sincronizacion_google`
  ADD PRIMARY KEY (`id_sincronizacion`),
  ADD KEY `fk_sg_usuario` (`usuario_ejecuto`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD UNIQUE KEY `firebase_uid` (`firebase_uid`);

--
-- Indices de la tabla `usuario_sesion`
--
ALTER TABLE `usuario_sesion`
  ADD PRIMARY KEY (`id_sesion`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_busqueda`
--
ALTER TABLE `historial_busqueda`
  MODIFY `id_busqueda` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lugar_turistico`
--
ALTER TABLE `lugar_turistico`
  MODIFY `id_lugar` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parada`
--
ALTER TABLE `parada`
  MODIFY `id_parada` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ruta`
--
ALTER TABLE `ruta`
  MODIFY `id_ruta` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sincronizacion_google`
--
ALTER TABLE `sincronizacion_google`
  MODIFY `id_sincronizacion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario_sesion`
--
ALTER TABLE `usuario_sesion`
  MODIFY `id_sesion` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_busqueda`
--
ALTER TABLE `historial_busqueda`
  ADD CONSTRAINT `fk_hb_lugar` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hb_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hb_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ruta_lugar`
--
ALTER TABLE `ruta_lugar`
  ADD CONSTRAINT `fk_rl_lugar` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rl_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `ruta_parada`
--
ALTER TABLE `ruta_parada`
  ADD CONSTRAINT `fk_rp_parada` FOREIGN KEY (`id_parada`) REFERENCES `parada` (`id_parada`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `sincronizacion_google`
--
ALTER TABLE `sincronizacion_google`
  ADD CONSTRAINT `fk_sg_usuario` FOREIGN KEY (`usuario_ejecuto`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_sesion`
--
ALTER TABLE `usuario_sesion`
  ADD CONSTRAINT `usuario_sesion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
