-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: mysql-3e660e89-atenciobonillayoselin-e9b2.g.aivencloud.com    Database: app_turistica_la_paz
-- ------------------------------------------------------
-- Server version	8.4.8

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria` (
  `id_auditoria` int NOT NULL AUTO_INCREMENT,
  `tabla_afectada` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro` int NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `datos_anteriores` text COLLATE utf8mb4_unicode_ci,
  `datos_nuevos` text COLLATE utf8mb4_unicode_ci,
  `id_usuario` int DEFAULT NULL,
  `fecha_cambio` datetime DEFAULT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,'usuario',1,'INSERT',NULL,'{\"rol\": \"admin\", \"email\": \"admin@turismolapaz.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}',1,'2026-09-22 19:13:45'),(2,'categoria_lugar',1,'INSERT',NULL,'{\"slug\": \"miradores\", \"icono\": \"fa-binoculars\", \"activo\": 1, \"nombre\": \"Miradores\", \"id_categoria\": 1}',NULL,'2026-09-22 21:32:48'),(3,'categoria_lugar',2,'INSERT',NULL,'{\"slug\": \"plazas\", \"icono\": \"fa-city\", \"activo\": 1, \"nombre\": \"Plazas\", \"id_categoria\": 2}',NULL,'2026-09-22 21:32:49'),(4,'categoria_lugar',3,'INSERT',NULL,'{\"slug\": \"iglesias\", \"icono\": \"fa-church\", \"activo\": 1, \"nombre\": \"Iglesias\", \"id_categoria\": 3}',NULL,'2026-09-22 21:32:50'),(5,'categoria_lugar',4,'INSERT',NULL,'{\"slug\": \"museos\", \"icono\": \"fa-landmark\", \"activo\": 1, \"nombre\": \"Museos\", \"id_categoria\": 4}',NULL,'2026-09-22 21:32:51'),(6,'categoria_lugar',5,'INSERT',NULL,'{\"slug\": \"mercados\", \"icono\": \"fa-store\", \"activo\": 1, \"nombre\": \"Mercados\", \"id_categoria\": 5}',NULL,'2026-09-22 21:32:52'),(7,'categoria_lugar',6,'INSERT',NULL,'{\"slug\": \"parques\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parques\", \"id_categoria\": 6}',NULL,'2026-09-22 21:32:53'),(8,'categoria_lugar',7,'INSERT',NULL,'{\"slug\": \"telefericos\", \"icono\": \"fa-cable-car\", \"activo\": 1, \"nombre\": \"Teleféricos\", \"id_categoria\": 7}',NULL,'2026-09-22 21:32:54'),(9,'categoria_lugar',8,'INSERT',NULL,'{\"slug\": \"pumas-katari\", \"icono\": \"fa-bus\", \"activo\": 1, \"nombre\": \"Pumas Katari\", \"id_categoria\": 8}',NULL,'2026-09-22 21:32:55'),(10,'categoria_lugar',9,'INSERT',NULL,'{\"slug\": \"naturaleza\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturaleza\", \"id_categoria\": 9}',NULL,'2026-09-22 21:32:56'),(11,'categoria_lugar',10,'INSERT',NULL,'{\"slug\": \"gastronomia\", \"icono\": \"fa-utensils\", \"activo\": 1, \"nombre\": \"Gastronomía\", \"id_categoria\": 10}',NULL,'2026-09-22 21:32:57'),(12,'usuario',2,'INSERT',NULL,'{\"rol\": \"usuario\", \"email\": \"prueba@test.com\", \"activo\": 1, \"nombre\": \"Prueba\", \"id_usuario\": 2}',2,'2026-09-23 05:41:25'),(13,'usuario',4,'INSERT',NULL,'{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 4}',4,'2026-09-23 05:47:07'),(14,'usuario',4,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 4}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 4}',4,'2026-09-23 05:47:07');
/*!40000 ALTER TABLE `auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria_lugar`
--

DROP TABLE IF EXISTS `categoria_lugar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_lugar` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icono` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `unique_nombre` (`nombre`),
  UNIQUE KEY `unique_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_lugar`
--

LOCK TABLES `categoria_lugar` WRITE;
/*!40000 ALTER TABLE `categoria_lugar` DISABLE KEYS */;
INSERT INTO `categoria_lugar` VALUES (1,'Miradores','miradores','fa-binoculars',1,'2026-09-22 21:32:48','2026-09-22 21:32:48'),(2,'Plazas','plazas','fa-city',1,'2026-09-22 21:32:49','2026-09-22 21:32:49'),(3,'Iglesias','iglesias','fa-church',1,'2026-09-22 21:32:50','2026-09-22 21:32:50'),(4,'Museos','museos','fa-landmark',1,'2026-09-22 21:32:51','2026-09-22 21:32:51'),(5,'Mercados','mercados','fa-store',1,'2026-09-22 21:32:52','2026-09-22 21:32:52'),(6,'Parques','parques','fa-tree',1,'2026-09-22 21:32:53','2026-09-22 21:32:53'),(7,'Teleféricos','telefericos','fa-cable-car',1,'2026-09-22 21:32:54','2026-09-22 21:32:54'),(8,'Pumas Katari','pumas-katari','fa-bus',1,'2026-09-22 21:32:55','2026-09-22 21:32:55'),(9,'Naturaleza','naturaleza','fa-leaf',1,'2026-09-22 21:32:56','2026-09-22 21:32:56'),(10,'Gastronomía','gastronomia','fa-utensils',1,'2026-09-22 21:32:57','2026-09-22 21:32:57');
/*!40000 ALTER TABLE `categoria_lugar` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_categoria_lugar_insert` AFTER INSERT ON `categoria_lugar` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_nuevos, fecha_cambio)
    VALUES ('categoria_lugar', NEW.id_categoria, 'INSERT', 
            JSON_OBJECT(
                'id_categoria', NEW.id_categoria,
                'nombre', NEW.nombre,
                'slug', NEW.slug,
                'icono', NEW.icono,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_categoria_lugar_update` AFTER UPDATE ON `categoria_lugar` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos, fecha_cambio)
    VALUES ('categoria_lugar', NEW.id_categoria, 'UPDATE',
            JSON_OBJECT(
                'id_categoria', OLD.id_categoria,
                'nombre', OLD.nombre,
                'slug', OLD.slug,
                'icono', OLD.icono,
                'activo', OLD.activo
            ),
            JSON_OBJECT(
                'id_categoria', NEW.id_categoria,
                'nombre', NEW.nombre,
                'slug', NEW.slug,
                'icono', NEW.icono,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_categoria_lugar_delete` AFTER DELETE ON `categoria_lugar` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, fecha_cambio)
    VALUES ('categoria_lugar', OLD.id_categoria, 'DELETE',
            JSON_OBJECT(
                'id_categoria', OLD.id_categoria,
                'nombre', OLD.nombre,
                'slug', OLD.slug,
                'icono', OLD.icono,
                'activo', OLD.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorito`
--

DROP TABLE IF EXISTS `favorito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorito` (
  `id_usuario` int NOT NULL,
  `id_lugar` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`,`id_lugar`),
  KEY `id_lugar` (`id_lugar`),
  CONSTRAINT `favorito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `favorito_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorito`
--

LOCK TABLES `favorito` WRITE;
/*!40000 ALTER TABLE `favorito` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_busqueda`
--

DROP TABLE IF EXISTS `historial_busqueda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_busqueda` (
  `id_busqueda` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_lugar` int DEFAULT NULL,
  `id_ruta` int DEFAULT NULL,
  `id_parada` int DEFAULT NULL,
  `query_texto` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud_usuario` decimal(10,8) DEFAULT NULL,
  `longitud_usuario` decimal(11,8) DEFAULT NULL,
  `fecha_busqueda` datetime DEFAULT NULL,
  PRIMARY KEY (`id_busqueda`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_lugar` (`id_lugar`),
  KEY `id_ruta` (`id_ruta`),
  KEY `id_parada` (`id_parada`),
  CONSTRAINT `historial_busqueda_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `historial_busqueda_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE SET NULL,
  CONSTRAINT `historial_busqueda_ibfk_3` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE SET NULL,
  CONSTRAINT `historial_busqueda_ibfk_4` FOREIGN KEY (`id_parada`) REFERENCES `parada` (`id_parada`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_busqueda`
--

LOCK TABLES `historial_busqueda` WRITE;
/*!40000 ALTER TABLE `historial_busqueda` DISABLE KEYS */;
/*!40000 ALTER TABLE `historial_busqueda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_tramo`
--

DROP TABLE IF EXISTS `historial_tramo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_tramo` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_ruta` int DEFAULT NULL,
  `id_parada_origen` int DEFAULT NULL,
  `id_parada_destino` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `tiempo_estimado` time DEFAULT NULL,
  `distancia_km` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_ruta` (`id_ruta`),
  KEY `id_parada_origen` (`id_parada_origen`),
  KEY `id_parada_destino` (`id_parada_destino`),
  CONSTRAINT `historial_tramo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `historial_tramo_ibfk_2` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE SET NULL,
  CONSTRAINT `historial_tramo_ibfk_3` FOREIGN KEY (`id_parada_origen`) REFERENCES `parada` (`id_parada`) ON DELETE SET NULL,
  CONSTRAINT `historial_tramo_ibfk_4` FOREIGN KEY (`id_parada_destino`) REFERENCES `parada` (`id_parada`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_tramo`
--

LOCK TABLES `historial_tramo` WRITE;
/*!40000 ALTER TABLE `historial_tramo` DISABLE KEYS */;
/*!40000 ALTER TABLE `historial_tramo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lugar_multimedia`
--

DROP TABLE IF EXISTS `lugar_multimedia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lugar_multimedia` (
  `id_multimedia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_lugar` bigint unsigned NOT NULL,
  `tipo` enum('imagen','360','3d','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'imagen',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idioma` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duracion_seg` int DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_multimedia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_multimedia`
--

LOCK TABLES `lugar_multimedia` WRITE;
/*!40000 ALTER TABLE `lugar_multimedia` DISABLE KEYS */;
/*!40000 ALTER TABLE `lugar_multimedia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lugar_turistico`
--

DROP TABLE IF EXISTS `lugar_turistico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lugar_turistico` (
  `id_lugar` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `descripcion_corta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calificacion` decimal(3,2) DEFAULT '0.00',
  `costo` decimal(10,2) DEFAULT '0.00',
  `costo_nino` decimal(10,2) DEFAULT '0.00',
  `costo_adulto` decimal(10,2) DEFAULT '0.00',
  `costo_tercera_edad` decimal(10,2) DEFAULT '0.00',
  `es_gratuito` tinyint(1) DEFAULT '1',
  `abierto_todos_los_dias` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `horarios` json DEFAULT NULL,
  `tipo_transporte` enum('micro','minibus','trufi','teleferico','puma_katari','todos','ninguno') COLLATE utf8mb4_unicode_ci DEFAULT 'todos',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lugar`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `lugar_turistico_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_lugar` (`id_categoria`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_turistico`
--

LOCK TABLES `lugar_turistico` WRITE;
/*!40000 ALTER TABLE `lugar_turistico` DISABLE KEYS */;
/*!40000 ALTER TABLE `lugar_turistico` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_lugar_turistico_insert` AFTER INSERT ON `lugar_turistico` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_nuevos, fecha_cambio)
    VALUES ('lugar_turistico', NEW.id_lugar, 'INSERT',
            JSON_OBJECT(
                'id_lugar', NEW.id_lugar,
                'nombre', NEW.nombre,
                'descripcion', NEW.descripcion,
                'id_categoria', NEW.id_categoria,
                'latitud', NEW.latitud,
                'longitud', NEW.longitud,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_lugar_turistico_update` AFTER UPDATE ON `lugar_turistico` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos, fecha_cambio)
    VALUES ('lugar_turistico', NEW.id_lugar, 'UPDATE',
            JSON_OBJECT(
                'id_lugar', OLD.id_lugar,
                'nombre', OLD.nombre,
                'descripcion', OLD.descripcion,
                'id_categoria', OLD.id_categoria,
                'latitud', OLD.latitud,
                'longitud', OLD.longitud,
                'activo', OLD.activo
            ),
            JSON_OBJECT(
                'id_lugar', NEW.id_lugar,
                'nombre', NEW.nombre,
                'descripcion', NEW.descripcion,
                'id_categoria', NEW.id_categoria,
                'latitud', NEW.latitud,
                'longitud', NEW.longitud,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_lugar_turistico_delete` AFTER DELETE ON `lugar_turistico` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, fecha_cambio)
    VALUES ('lugar_turistico', OLD.id_lugar, 'DELETE',
            JSON_OBJECT(
                'id_lugar', OLD.id_lugar,
                'nombre', OLD.nombre,
                'descripcion', OLD.descripcion,
                'id_categoria', OLD.id_categoria,
                'latitud', OLD.latitud,
                'longitud', OLD.longitud,
                'activo', OLD.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parada`
--

DROP TABLE IF EXISTS `parada`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parada` (
  `id_parada` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `es_terminal` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_parada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parada`
--

LOCK TABLES `parada` WRITE;
/*!40000 ALTER TABLE `parada` DISABLE KEYS */;
/*!40000 ALTER TABLE `parada` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_parada_insert` AFTER INSERT ON `parada` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_nuevos, fecha_cambio)
    VALUES ('parada', NEW.id_parada, 'INSERT',
            JSON_OBJECT(
                'id_parada', NEW.id_parada,
                'nombre', NEW.nombre,
                'direccion', NEW.direccion,
                'latitud', NEW.latitud,
                'longitud', NEW.longitud,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_parada_update` AFTER UPDATE ON `parada` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos, fecha_cambio)
    VALUES ('parada', NEW.id_parada, 'UPDATE',
            JSON_OBJECT(
                'id_parada', OLD.id_parada,
                'nombre', OLD.nombre,
                'direccion', OLD.direccion,
                'latitud', OLD.latitud,
                'longitud', OLD.longitud,
                'activo', OLD.activo
            ),
            JSON_OBJECT(
                'id_parada', NEW.id_parada,
                'nombre', NEW.nombre,
                'direccion', NEW.direccion,
                'latitud', NEW.latitud,
                'longitud', NEW.longitud,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_parada_delete` AFTER DELETE ON `parada` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, fecha_cambio)
    VALUES ('parada', OLD.id_parada, 'DELETE',
            JSON_OBJECT(
                'id_parada', OLD.id_parada,
                'nombre', OLD.nombre,
                'direccion', OLD.direccion,
                'latitud', OLD.latitud,
                'longitud', OLD.longitud,
                'activo', OLD.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resena`
--

DROP TABLE IF EXISTS `resena`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resena` (
  `id_resena` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_lugar` int NOT NULL,
  `calificacion` tinyint NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_resena`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_lugar` (`id_lugar`),
  CONSTRAINT `resena_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `resena_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE CASCADE,
  CONSTRAINT `resena_chk_1` CHECK (((`calificacion` >= 1) and (`calificacion` <= 5)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resena`
--

LOCK TABLES `resena` WRITE;
/*!40000 ALTER TABLE `resena` DISABLE KEYS */;
/*!40000 ALTER TABLE `resena` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruta`
--

DROP TABLE IF EXISTS `ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruta` (
  `id_ruta` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_ruta` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('micro','minibus','trufi','teleferico','puma_katari','otros') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sentido` enum('IDA','VUELTA','NORMAL') COLLATE utf8mb4_unicode_ci DEFAULT 'NORMAL',
  `destino` int DEFAULT NULL,
  `frecuencia_min` int DEFAULT NULL,
  `horario_inicio` time DEFAULT NULL,
  `horario_fin` time DEFAULT NULL,
  `coords_geojson` text COLLATE utf8mb4_unicode_ci,
  `coords_geojson_ida` text COLLATE utf8mb4_unicode_ci,
  `coords_geojson_vuelta` text COLLATE utf8mb4_unicode_ci,
  `puntos_gps` text COLLATE utf8mb4_unicode_ci,
  `puntos_gps_ida` text COLLATE utf8mb4_unicode_ci,
  `puntos_gps_vuelta` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `color_hex` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#0066CC',
  `color_hex_ida` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#0066CC',
  `color_hex_vuelta` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#FF6600',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ruta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruta`
--

LOCK TABLES `ruta` WRITE;
/*!40000 ALTER TABLE `ruta` DISABLE KEYS */;
/*!40000 ALTER TABLE `ruta` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_ruta_insert` AFTER INSERT ON `ruta` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_nuevos, fecha_cambio)
    VALUES ('ruta', NEW.id_ruta, 'INSERT',
            JSON_OBJECT(
                'id_ruta', NEW.id_ruta,
                'nombre', NEW.nombre,
                'tipo', NEW.tipo,
                'sentido', NEW.sentido,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_ruta_update` AFTER UPDATE ON `ruta` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos, fecha_cambio)
    VALUES ('ruta', NEW.id_ruta, 'UPDATE',
            JSON_OBJECT(
                'id_ruta', OLD.id_ruta,
                'nombre', OLD.nombre,
                'tipo', OLD.tipo,
                'sentido', OLD.sentido,
                'activo', OLD.activo
            ),
            JSON_OBJECT(
                'id_ruta', NEW.id_ruta,
                'nombre', NEW.nombre,
                'tipo', NEW.tipo,
                'sentido', NEW.sentido,
                'activo', NEW.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_ruta_delete` AFTER DELETE ON `ruta` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, fecha_cambio)
    VALUES ('ruta', OLD.id_ruta, 'DELETE',
            JSON_OBJECT(
                'id_ruta', OLD.id_ruta,
                'nombre', OLD.nombre,
                'tipo', OLD.tipo,
                'sentido', OLD.sentido,
                'activo', OLD.activo
            ), NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ruta_lugar`
--

DROP TABLE IF EXISTS `ruta_lugar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruta_lugar` (
  `id_ruta` int NOT NULL,
  `id_lugar` int NOT NULL,
  `orden` int DEFAULT NULL,
  `distancia_km` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ruta`,`id_lugar`),
  KEY `id_lugar` (`id_lugar`),
  CONSTRAINT `ruta_lugar_ibfk_1` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE CASCADE,
  CONSTRAINT `ruta_lugar_ibfk_2` FOREIGN KEY (`id_lugar`) REFERENCES `lugar_turistico` (`id_lugar`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruta_lugar`
--

LOCK TABLES `ruta_lugar` WRITE;
/*!40000 ALTER TABLE `ruta_lugar` DISABLE KEYS */;
/*!40000 ALTER TABLE `ruta_lugar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruta_parada`
--

DROP TABLE IF EXISTS `ruta_parada`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruta_parada` (
  `id_ruta` int NOT NULL,
  `id_parada` int NOT NULL,
  `orden` int DEFAULT NULL,
  `es_inicio` tinyint(1) DEFAULT '0',
  `es_fin` tinyint(1) DEFAULT '0',
  `tiempo_estimado` time DEFAULT NULL,
  `distancia_metros` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ruta`,`id_parada`),
  KEY `id_parada` (`id_parada`),
  CONSTRAINT `ruta_parada_ibfk_1` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE CASCADE,
  CONSTRAINT `ruta_parada_ibfk_2` FOREIGN KEY (`id_parada`) REFERENCES `parada` (`id_parada`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruta_parada`
--

LOCK TABLES `ruta_parada` WRITE;
/*!40000 ALTER TABLE `ruta_parada` DISABLE KEYS */;
/*!40000 ALTER TABLE `ruta_parada` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sincronizacion_google`
--

DROP TABLE IF EXISTS `sincronizacion_google`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sincronizacion_google` (
  `id_sincronizacion` int NOT NULL AUTO_INCREMENT,
  `fecha_sincronizacion` datetime DEFAULT NULL,
  `archivo_kml_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('PENDIENTE','PROCESANDO','COMPLETADO','ERROR') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDIENTE',
  `rutas_agregadas` int DEFAULT '0',
  `rutas_actualizadas` int DEFAULT '0',
  `paradas_agregadas` int DEFAULT '0',
  `paradas_actualizadas` int DEFAULT '0',
  `mensaje_error` text COLLATE utf8mb4_unicode_ci,
  `id_usuario` int DEFAULT NULL,
  PRIMARY KEY (`id_sincronizacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `sincronizacion_google_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sincronizacion_google`
--

LOCK TABLES `sincronizacion_google` WRITE;
/*!40000 ALTER TABLE `sincronizacion_google` DISABLE KEYS */;
/*!40000 ALTER TABLE `sincronizacion_google` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarifa_tramo`
--

DROP TABLE IF EXISTS `tarifa_tramo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifa_tramo` (
  `id_tarifa` int NOT NULL AUTO_INCREMENT,
  `id_ruta` int NOT NULL,
  `zona_origen` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zona_destino` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `moneda` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'BOB',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarifa`),
  KEY `id_ruta` (`id_ruta`),
  CONSTRAINT `tarifa_tramo_ibfk_1` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarifa_tramo`
--

LOCK TABLES `tarifa_tramo` WRITE;
/*!40000 ALTER TABLE `tarifa_tramo` DISABLE KEYS */;
/*!40000 ALTER TABLE `tarifa_tramo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','admin@turismolapaz.com',NULL,'$2y$10$vb0YTWrHmAhMAfRr/U9U..zYPuBZIVEM/dTnSLB4DYa/tne42CuqO',NULL,'2026-09-22 18:51:22','2026-09-22 18:51:22');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carnet` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firebase_uid` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perfil_completo` tinyint(1) DEFAULT '0',
  `rol` enum('usuario','editor','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `firebase_uid` (`firebase_uid`),
  UNIQUE KEY `carnet` (`carnet`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin@turismolapaz.com','$2y$12$S0zbptUTDu56Ps16KCKQle.WUHc6DCkt.Va7HrnXpxkLNt6bo3.ri','Administrador',NULL,NULL,'admin_6ab2d364d9f17',NULL,0,'admin',1,'2026-09-22 19:13:40','2026-09-22 19:13:40',NULL),(2,'prueba@test.com',NULL,'Prueba','','','uid_prueba_123',NULL,0,'usuario',1,'2026-09-23 09:41:25','2026-09-23 09:41:25',''),(4,'atenciobonillayoselin@gmail.com',NULL,'Atencio Bonilla Yoselin','71907637','13763484','sinuid_a30c9c971dfd4f09',NULL,1,'usuario',1,'2026-09-23 09:47:07','2026-09-23 09:47:07',NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_usuario_insert` AFTER INSERT ON `usuario` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_nuevos, id_usuario, fecha_cambio)
    VALUES ('usuario', NEW.id_usuario, 'INSERT',
            JSON_OBJECT(
                'id_usuario', NEW.id_usuario,
                'email', NEW.email,
                'nombre', NEW.nombre,
                'rol', NEW.rol,
                'activo', NEW.activo
            ), NEW.id_usuario, NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_usuario_update` AFTER UPDATE ON `usuario` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos, id_usuario, fecha_cambio)
    VALUES ('usuario', NEW.id_usuario, 'UPDATE',
            JSON_OBJECT(
                'id_usuario', OLD.id_usuario,
                'email', OLD.email,
                'nombre', OLD.nombre,
                'rol', OLD.rol,
                'activo', OLD.activo
            ),
            JSON_OBJECT(
                'id_usuario', NEW.id_usuario,
                'email', NEW.email,
                'nombre', NEW.nombre,
                'rol', NEW.rol,
                'activo', NEW.activo
            ), NEW.id_usuario, NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`avnadmin`@`%`*/ /*!50003 TRIGGER `tr_usuario_delete` AFTER DELETE ON `usuario` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, datos_anteriores, id_usuario, fecha_cambio)
    VALUES ('usuario', OLD.id_usuario, 'DELETE',
            JSON_OBJECT(
                'id_usuario', OLD.id_usuario,
                'email', OLD.email,
                'nombre', OLD.nombre,
                'rol', OLD.rol,
                'activo', OLD.activo
            ), OLD.id_usuario, NOW());
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `usuario_sesion`
--

DROP TABLE IF EXISTS `usuario_sesion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_sesion` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `token` (`token`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `usuario_sesion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_sesion`
--

LOCK TABLES `usuario_sesion` WRITE;
/*!40000 ALTER TABLE `usuario_sesion` DISABLE KEYS */;
INSERT INTO `usuario_sesion` VALUES (1,2,'0650b0cc8b5189b709b7ccf9c76b668b282ea5fb6f92149ce1a059b50009c06a','2026-09-23 05:41:25','2026-10-23 05:41:25',1);
/*!40000 ALTER TABLE `usuario_sesion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-23  5:52:20
