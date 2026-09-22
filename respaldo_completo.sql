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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,'usuario',1,'INSERT',NULL,'{\"rol\": \"admin\", \"email\": \"admin@turismo.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}',1,'2026-09-19 08:56:44'),(2,'usuario',1,'UPDATE','{\"rol\": \"admin\", \"email\": \"admin@turismo.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}','{\"rol\": \"admin\", \"email\": \"admin@turismo.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}',1,'2026-09-19 09:00:08'),(3,'lugar_turistico',1,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}',NULL,'2026-09-19 09:19:19'),(4,'categoria_lugar',2,'DELETE','{\"slug\": \"parque\", \"icono\": \"heroicon-o-leaf\", \"activo\": 1, \"nombre\": \"Parque\", \"id_categoria\": 2}',NULL,NULL,'2026-09-19 10:06:33'),(5,'categoria_lugar',3,'DELETE','{\"slug\": \"museo\", \"icono\": \"heroicon-o-academic-cap\", \"activo\": 1, \"nombre\": \"Museo\", \"id_categoria\": 3}',NULL,NULL,'2026-09-19 10:06:44'),(6,'categoria_lugar',4,'DELETE','{\"slug\": \"iglesia\", \"icono\": \"heroicon-o-building-office\", \"activo\": 1, \"nombre\": \"Iglesia\", \"id_categoria\": 4}',NULL,NULL,'2026-09-19 10:06:56'),(7,'categoria_lugar',5,'DELETE','{\"slug\": \"plaza\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Plaza\", \"id_categoria\": 5}',NULL,NULL,'2026-09-19 10:07:07'),(8,'categoria_lugar',6,'DELETE','{\"slug\": \"mercado\", \"icono\": \"heroicon-o-shopping-bag\", \"activo\": 1, \"nombre\": \"Mercado\", \"id_categoria\": 6}',NULL,NULL,'2026-09-19 10:07:17'),(9,'categoria_lugar',7,'DELETE','{\"slug\": \"restaurante\", \"icono\": \"heroicono-utensils\", \"activo\": 1, \"nombre\": \"Restaurant\", \"id_categoria\": 7}',NULL,NULL,'2026-09-19 10:07:28'),(10,'categoria_lugar',8,'DELETE','{\"slug\": \"zona-natural\", \"icono\": \"heroicon-o-globe-alt\", \"activo\": 1, \"nombre\": \"Zona Natural\", \"id_categoria\": 8}',NULL,NULL,'2026-09-19 10:07:39'),(11,'categoria_lugar',9,'INSERT',NULL,'{\"slug\": \"plazas\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Plazas\", \"id_categoria\": 9}',NULL,'2026-09-19 10:08:49'),(12,'categoria_lugar',1,'DELETE','{\"slug\": \"mirador\", \"icono\": \"heroicon-o-camera\", \"activo\": 1, \"nombre\": \"Mirador\", \"id_categoria\": 1}',NULL,NULL,'2026-09-19 10:12:27'),(13,'categoria_lugar',9,'DELETE','{\"slug\": \"plazas\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Plazas\", \"id_categoria\": 9}',NULL,NULL,'2026-09-19 10:12:41'),(14,'categoria_lugar',1,'INSERT',NULL,'{\"slug\": \"mirador\", \"icono\": \"heroicon-o-camera\", \"activo\": 1, \"nombre\": \"Mirador\", \"id_categoria\": 1}',NULL,'2026-09-19 10:14:14'),(15,'categoria_lugar',2,'INSERT',NULL,'{\"slug\": \"plazas\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Plazas\", \"id_categoria\": 2}',NULL,'2026-09-19 10:14:14'),(16,'categoria_lugar',3,'INSERT',NULL,'{\"slug\": \"parque\", \"icono\": \"heroicon-o-leaf\", \"activo\": 1, \"nombre\": \"Parque\", \"id_categoria\": 3}',NULL,'2026-09-19 10:14:14'),(17,'categoria_lugar',4,'INSERT',NULL,'{\"slug\": \"museo\", \"icono\": \"heroicon-o-academic-cap\", \"activo\": 1, \"nombre\": \"Museo\", \"id_categoria\": 4}',NULL,'2026-09-19 10:14:14'),(18,'categoria_lugar',5,'INSERT',NULL,'{\"slug\": \"iglesia\", \"icono\": \"heroicon-o-building-office\", \"activo\": 1, \"nombre\": \"Iglesia\", \"id_categoria\": 5}',NULL,'2026-09-19 10:14:14'),(19,'categoria_lugar',6,'INSERT',NULL,'{\"slug\": \"naturaleza\", \"icono\": \"heroicon-o-globe-alt\", \"activo\": 1, \"nombre\": \"Naturaleza\", \"id_categoria\": 6}',NULL,'2026-09-19 10:14:14'),(20,'categoria_lugar',7,'INSERT',NULL,'{\"slug\": \"hospitales\", \"icono\": \"heroicon-o-plus-circle\", \"activo\": 1, \"nombre\": \"Hospitales\", \"id_categoria\": 7}',NULL,'2026-09-19 10:14:14'),(21,'categoria_lugar',8,'INSERT',NULL,'{\"slug\": \"universidades\", \"icono\": \"heroicon-o-academic-cap\", \"activo\": 1, \"nombre\": \"Universidades\", \"id_categoria\": 8}',NULL,'2026-09-19 10:14:14'),(22,'categoria_lugar',9,'INSERT',NULL,'{\"slug\": \"comedores\", \"icono\": \"heroicon-o-cake\", \"activo\": 1, \"nombre\": \"Comedores\", \"id_categoria\": 9}',NULL,'2026-09-19 10:14:14'),(23,'categoria_lugar',10,'INSERT',NULL,'{\"slug\": \"calles-atractivas\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Calles Atractivas\", \"id_categoria\": 10}',NULL,'2026-09-19 10:14:14'),(24,'categoria_lugar',11,'INSERT',NULL,'{\"slug\": \"cines\", \"icono\": \"heroicon-o-film\", \"activo\": 1, \"nombre\": \"Cines\", \"id_categoria\": 11}',NULL,'2026-09-19 10:14:14'),(25,'categoria_lugar',12,'INSERT',NULL,'{\"slug\": \"telefericos\", \"icono\": \"heroicon-o-truck\", \"activo\": 1, \"nombre\": \"Teleféricos\", \"id_categoria\": 12}',NULL,'2026-09-19 10:14:14'),(26,'categoria_lugar',13,'INSERT',NULL,'{\"slug\": \"pumas\", \"icono\": \"heroicon-o-truck\", \"activo\": 1, \"nombre\": \"Pumas\", \"id_categoria\": 13}',NULL,'2026-09-19 10:14:14'),(27,'categoria_lugar',10,'UPDATE','{\"slug\": \"calles-atractivas\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Calles Atractivas\", \"id_categoria\": 10}','{\"slug\": \"calles-tradicionales\", \"icono\": \"heroicon-o-map\", \"activo\": 1, \"nombre\": \"Calles Tradicionales\", \"id_categoria\": 10}',NULL,'2026-09-19 10:16:16'),(28,'lugar_turistico',1,'UPDATE','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": null}','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}',NULL,'2026-09-19 13:26:00'),(29,'lugar_turistico',1,'UPDATE','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}',NULL,'2026-09-22 07:05:26'),(30,'lugar_turistico',1,'UPDATE','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}','{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739607, \"descripcion\": null, \"id_categoria\": 1}',NULL,'2026-09-22 08:04:13'),(31,'ruta',1,'INSERT',NULL,'{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": null, \"id_ruta\": 1, \"sentido\": \"NORMAL\"}',NULL,'2026-09-22 08:19:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_lugar`
--

LOCK TABLES `categoria_lugar` WRITE;
/*!40000 ALTER TABLE `categoria_lugar` DISABLE KEYS */;
INSERT INTO `categoria_lugar` VALUES (1,'Mirador','mirador','heroicon-o-camera',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(2,'Plazas','plazas','heroicon-o-map',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(3,'Parque','parque','heroicon-o-leaf',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(4,'Museo','museo','heroicon-o-academic-cap',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(5,'Iglesia','iglesia','heroicon-o-building-office',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(6,'Naturaleza','naturaleza','heroicon-o-globe-alt',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(7,'Hospitales','hospitales','heroicon-o-plus-circle',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(8,'Universidades','universidades','heroicon-o-academic-cap',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(9,'Comedores','comedores','heroicon-o-cake',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(10,'Calles Tradicionales','calles-tradicionales','heroicon-o-map',1,'2026-09-19 10:14:14','2026-09-19 10:16:16'),(11,'Cines','cines','heroicon-o-film',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(12,'Teleféricos','telefericos','heroicon-o-truck',1,'2026-09-19 10:14:14','2026-09-19 10:14:14'),(13,'Pumas','pumas','heroicon-o-truck',1,'2026-09-19 10:14:14','2026-09-19 10:14:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_multimedia`
--

LOCK TABLES `lugar_multimedia` WRITE;
/*!40000 ALTER TABLE `lugar_multimedia` DISABLE KEYS */;
INSERT INTO `lugar_multimedia` VALUES (1,1,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/1.webp',NULL,NULL,NULL,1,1,'2026-09-22 08:04:09','2026-09-22 08:04:09'),(2,1,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/3.webp',NULL,NULL,NULL,16,1,'2026-09-22 08:04:09','2026-09-22 08:04:09'),(3,1,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/360.jpg',NULL,NULL,NULL,1,1,'2026-09-22 08:04:10','2026-09-22 08:04:10'),(4,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_aym.mp3',NULL,'aymara',0,0,1,'2026-09-22 08:05:42','2026-09-22 08:05:42'),(5,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_eng.mp3',NULL,'ingles',0,0,1,'2026-09-22 08:05:42','2026-09-22 08:05:42'),(6,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_esp.mp3',NULL,'español',0,0,1,'2026-09-22 08:05:43','2026-09-22 08:05:43');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_turistico`
--

LOCK TABLES `lugar_turistico` WRITE;
/*!40000 ALTER TABLE `lugar_turistico` DISABLE KEYS */;
INSERT INTO `lugar_turistico` VALUES (1,'Mirador Killi Killi',NULL,NULL,1,-16.49549300,-68.12739607,NULL,NULL,0.00,0.00,0.00,0.00,1,1,1,'[{\"dias\": [0, 1, 2, 3, 4, 5, 6], \"cerrado\": false, \"hora_cierre\": \"23:59\", \"hora_apertura\": \"00:00\"}]','todos','2026-09-19 09:19:17','2026-09-22 08:04:10');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_09_19_000000_create_admin_user',1),(5,'2026_09_22_053641_add_tipo_to_lugar_foto_table',2),(6,'2026_09_22_053725_remove_fields_from_lugar_turistico_table',2),(7,'2026_09_22_053906_migrate_and_drop_lugar_imagen_360_table',2),(10,'2026_09_22_064807_add_horarios_to_lugar_turistico_table',3),(11,'2026_09_22_064949_create_lugar_multimedia_table',4),(12,'2026_09_22_065206_migrate_audio_guia_to_lugar_multimedia_and_drop_old_tables',4),(13,'2026_09_22_065419_drop_lugar_foto_table',4),(14,'2026_09_22_072317_add_ida_vuelta_fields_to_ruta_table',5),(15,'2026_09_22_074328_add_abierto_todos_los_dias_to_lugar_turistico_table',6),(16,'2026_09_22_080044_reset_lugar_multimedia_table',7),(17,'2026_09_22_081809_make_nombre_nullable_in_ruta_table',8);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruta`
--

LOCK TABLES `ruta` WRITE;
/*!40000 ALTER TABLE `ruta` DISABLE KEYS */;
INSERT INTO `ruta` VALUES (1,NULL,'204','Sindicato Pedro Domingo Murillo','minibus','NORMAL',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'-16.465362,-68.111871;-16.464909,-68.111029;-16.465097,-68.110927;-16.465333,-68.110793;-16.465207,-68.110586;-16.465207,-68.110506;-16.465606,-68.110578;-16.465868,-68.110704;-16.466607,-68.111418;-16.466848,-68.111678;-16.467178,-68.112182;-16.467381,-68.112515;-16.467520,-68.112818;-16.467740,-68.113443;-16.467807,-68.113617;-16.467931,-68.113837;-16.468028,-68.113955;-16.468279,-68.114179;-16.468510,-68.114395;-16.468657,-68.114524;-16.468745,-68.114634;-16.468807,-68.114756;-16.468794,-68.114864;-16.468799,-68.115024;-16.468754,-68.115167;-16.468684,-68.115271;-16.468472,-68.115469;-16.468376,-68.115594;-16.468329,-68.115733;-16.468313,-68.115855;-16.468331,-68.115965;-16.468397,-68.116124;-16.468499,-68.116239;-16.468610,-68.116327;-16.468751,-68.116380;-16.468938,-68.116398;-16.469127,-68.116352;-16.469446,-68.116181;-16.469719,-68.116062;-16.469832,-68.116043;-16.470325,-68.116136;-16.470462,-68.116158;-16.471032,-68.116164;-16.471513,-68.116242;-16.471706,-68.116268;-16.471972,-68.116329;-16.472156,-68.116396;-16.473195,-68.117093;-16.473603,-68.117383;-16.474005,-68.117654;-16.474213,-68.117792;-16.474463,-68.117939;-16.474591,-68.118013;-16.474678,-68.118048;-16.474665,-68.118104;-16.474668,-68.118162;-16.474686,-68.118230;-16.474724,-68.118292;-16.474785,-68.118338;-16.474847,-68.118358;-16.474919,-68.118366;-16.474991,-68.118350;-16.475069,-68.118289;-16.475383,-68.118485;-16.476133,-68.118957;-16.476828,-68.119319;-16.477255,-68.119581;-16.478198,-68.120176;-16.478440,-68.120320;-16.478445,-68.120357;-16.478471,-68.120394;-16.478525,-68.120400;-16.478562,-68.120384;-16.479280,-68.120815;-16.479691,-68.121047;-16.479916,-68.121161;-16.480402,-68.121342;-16.480887,-68.121519;-16.481340,-68.121704;-16.481680,-68.121812;-16.481857,-68.121866;-16.481908,-68.121854;-16.482101,-68.121889;-16.482250,-68.121981;-16.482630,-68.122326;-16.483013,-68.122624;-16.483449,-68.122920;-16.483711,-68.123058;-16.484193,-68.123265;-16.484555,-68.123402;-16.485208,-68.123640;-16.485328,-68.123693;-16.485699,-68.123956;-16.485960,-68.124094;-16.486153,-68.124170;-16.486548,-68.124279;-16.486880,-68.124386;-16.487173,-68.124515;-16.487713,-68.124770;-16.488670,-68.125172;-16.488765,-68.125164;-16.489157,-68.125545;-16.489257,-68.125588;-16.489373,-68.125581;-16.489898,-68.125381;-16.490858,-68.125282;-16.491489,-68.125199;-16.492232,-68.125172;-16.492708,-68.125199;-16.492875,-68.125134;-16.493163,-68.124939;-16.493356,-68.124952;-16.493466,-68.125023;-16.493675,-68.125191;-16.494054,-68.125513;-16.494361,-68.125769;-16.494432,-68.125804;-16.494501,-68.125818;-16.494600,-68.125822;-16.495004,-68.125800;-16.495200,-68.125816;-16.495534,-68.125931;-16.495796,-68.126030\',\n ',NULL,1,'#0066CC','#0066CC','#FF6600','2026-09-22 08:19:48','2026-09-22 08:19:48');
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
INSERT INTO `ruta_lugar` VALUES (1,1,NULL,NULL,'2026-09-22 08:19:52','2026-09-22 08:19:52');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
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
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `firebase_uid` (`firebase_uid`),
  UNIQUE KEY `carnet` (`carnet`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin@turismo.com','$2y$12$TUynj9N6YWUwaYKgzfGITOJlv45Ttj7OvufMICVUJyN.a3/CTcesa','Administrador','','','admin_1789808202',NULL,1,'admin',1,'2026-09-19 08:56:42','2026-09-19 09:00:08');
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-22  5:50:39
