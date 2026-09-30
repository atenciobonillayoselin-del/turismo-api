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
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,'usuario',1,'INSERT',NULL,'{\"rol\": \"admin\", \"email\": \"admin@turismolapaz.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}',1,'2026-09-22 19:13:45'),(2,'categoria_lugar',1,'INSERT',NULL,'{\"slug\": \"miradores\", \"icono\": \"fa-binoculars\", \"activo\": 1, \"nombre\": \"Miradores\", \"id_categoria\": 1}',NULL,'2026-09-22 21:32:48'),(3,'categoria_lugar',2,'INSERT',NULL,'{\"slug\": \"plazas\", \"icono\": \"fa-city\", \"activo\": 1, \"nombre\": \"Plazas\", \"id_categoria\": 2}',NULL,'2026-09-22 21:32:49'),(4,'categoria_lugar',3,'INSERT',NULL,'{\"slug\": \"iglesias\", \"icono\": \"fa-church\", \"activo\": 1, \"nombre\": \"Iglesias\", \"id_categoria\": 3}',NULL,'2026-09-22 21:32:50'),(5,'categoria_lugar',4,'INSERT',NULL,'{\"slug\": \"museos\", \"icono\": \"fa-landmark\", \"activo\": 1, \"nombre\": \"Museos\", \"id_categoria\": 4}',NULL,'2026-09-22 21:32:51'),(6,'categoria_lugar',5,'INSERT',NULL,'{\"slug\": \"mercados\", \"icono\": \"fa-store\", \"activo\": 1, \"nombre\": \"Mercados\", \"id_categoria\": 5}',NULL,'2026-09-22 21:32:52'),(7,'categoria_lugar',6,'INSERT',NULL,'{\"slug\": \"parques\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parques\", \"id_categoria\": 6}',NULL,'2026-09-22 21:32:53'),(8,'categoria_lugar',7,'INSERT',NULL,'{\"slug\": \"telefericos\", \"icono\": \"fa-cable-car\", \"activo\": 1, \"nombre\": \"Teleféricos\", \"id_categoria\": 7}',NULL,'2026-09-22 21:32:54'),(9,'categoria_lugar',8,'INSERT',NULL,'{\"slug\": \"pumas-katari\", \"icono\": \"fa-bus\", \"activo\": 1, \"nombre\": \"Pumas Katari\", \"id_categoria\": 8}',NULL,'2026-09-22 21:32:55'),(10,'categoria_lugar',9,'INSERT',NULL,'{\"slug\": \"naturaleza\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturaleza\", \"id_categoria\": 9}',NULL,'2026-09-22 21:32:56'),(11,'categoria_lugar',10,'INSERT',NULL,'{\"slug\": \"gastronomia\", \"icono\": \"fa-utensils\", \"activo\": 1, \"nombre\": \"Gastronomía\", \"id_categoria\": 10}',NULL,'2026-09-22 21:32:57'),(26,'categoria_lugar',3,'UPDATE','{\"slug\": \"iglesias\", \"icono\": \"fa-church\", \"activo\": 1, \"nombre\": \"Iglesias\", \"id_categoria\": 3}','{\"slug\": \"parquess\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parquess\", \"id_categoria\": 3}',NULL,'2026-09-24 08:16:21'),(27,'categoria_lugar',5,'UPDATE','{\"slug\": \"mercados\", \"icono\": \"fa-store\", \"activo\": 1, \"nombre\": \"Mercados\", \"id_categoria\": 5}','{\"slug\": \"iglesias\", \"icono\": \"fa-church\", \"activo\": 1, \"nombre\": \"Iglesias\", \"id_categoria\": 5}',NULL,'2026-09-24 08:17:37'),(28,'categoria_lugar',6,'UPDATE','{\"slug\": \"parques\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parques\", \"id_categoria\": 6}','{\"slug\": \"naturalezas\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturalezas\", \"id_categoria\": 6}',NULL,'2026-09-24 08:18:53'),(29,'categoria_lugar',3,'UPDATE','{\"slug\": \"parquess\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parquess\", \"id_categoria\": 3}','{\"slug\": \"parques\", \"icono\": \"fa-tree\", \"activo\": 1, \"nombre\": \"Parques\", \"id_categoria\": 3}',NULL,'2026-09-24 08:19:33'),(30,'categoria_lugar',7,'UPDATE','{\"slug\": \"telefericos\", \"icono\": \"fa-cable-car\", \"activo\": 1, \"nombre\": \"Teleféricos\", \"id_categoria\": 7}','{\"slug\": \"hospitales\", \"icono\": \"fa-hospital\", \"activo\": 1, \"nombre\": \"Hospitales\", \"id_categoria\": 7}',NULL,'2026-09-24 08:20:47'),(31,'categoria_lugar',8,'UPDATE','{\"slug\": \"pumas-katari\", \"icono\": \"fa-bus\", \"activo\": 1, \"nombre\": \"Pumas Katari\", \"id_categoria\": 8}','{\"slug\": \"universidades\", \"icono\": \"fa-graduation-cap\", \"activo\": 1, \"nombre\": \"Universidades\", \"id_categoria\": 8}',NULL,'2026-09-24 08:21:53'),(32,'categoria_lugar',9,'UPDATE','{\"slug\": \"naturaleza\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturaleza\", \"id_categoria\": 9}','{\"slug\": \"comedores\", \"icono\": \"fa-utensils\", \"activo\": 1, \"nombre\": \"Comedores\", \"id_categoria\": 9}',NULL,'2026-09-24 08:23:19'),(33,'categoria_lugar',10,'UPDATE','{\"slug\": \"gastronomia\", \"icono\": \"fa-utensils\", \"activo\": 1, \"nombre\": \"Gastronomía\", \"id_categoria\": 10}','{\"slug\": \"calles-tradicionales\", \"icono\": \"fa-road\", \"activo\": 1, \"nombre\": \"Calles Tradicionales\", \"id_categoria\": 10}',NULL,'2026-09-24 08:24:17'),(34,'categoria_lugar',6,'UPDATE','{\"slug\": \"naturalezas\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturalezas\", \"id_categoria\": 6}','{\"slug\": \"naturaleza\", \"icono\": \"fa-leaf\", \"activo\": 1, \"nombre\": \"Naturaleza\", \"id_categoria\": 6}',NULL,'2026-09-24 08:25:01'),(35,'categoria_lugar',11,'INSERT',NULL,'{\"slug\": \"cines\", \"icono\": \"fa-film\", \"activo\": 1, \"nombre\": \"Cines\", \"id_categoria\": 11}',NULL,'2026-09-24 08:25:44'),(36,'categoria_lugar',12,'INSERT',NULL,'{\"slug\": \"telefericos\", \"icono\": \"fa-train\", \"activo\": 1, \"nombre\": \"Telefericos\", \"id_categoria\": 12}',NULL,'2026-09-24 08:26:20'),(37,'categoria_lugar',13,'INSERT',NULL,'{\"slug\": \"pumas-katari\", \"icono\": \"fa-bus\", \"activo\": 1, \"nombre\": \"Pumas Katari\", \"id_categoria\": 13}',NULL,'2026-09-24 08:26:52'),(39,'lugar_turistico',1,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Mirador Killi Killi\", \"latitud\": -16.49549300, \"id_lugar\": 1, \"longitud\": -68.12739600, \"descripcion\": null, \"id_categoria\": 1}',NULL,'2026-09-24 08:39:20'),(40,'ruta',1,'INSERT',NULL,'{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": null, \"id_ruta\": 1, \"sentido\": \"NORMAL\"}',NULL,'2026-09-24 08:43:01'),(43,'usuario',2,'DELETE','{\"rol\": \"usuario\", \"email\": \"prueba@test.com\", \"activo\": 1, \"nombre\": \"Prueba\", \"id_usuario\": 2}',NULL,2,'2026-09-24 09:01:36'),(44,'usuario',4,'DELETE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 4}',NULL,4,'2026-09-24 09:01:36'),(45,'usuario',1,'UPDATE','{\"rol\": \"admin\", \"email\": \"admin@turismolapaz.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}','{\"rol\": \"admin\", \"email\": \"admin@turismolapaz.com\", \"activo\": 1, \"nombre\": \"Administrador\", \"id_usuario\": 1}',1,'2026-09-24 09:03:44'),(46,'usuario',2,'INSERT',NULL,'{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 05:11:40'),(47,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 05:13:49'),(48,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 14:13:57'),(49,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 14:13:59'),(50,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 14:20:24'),(51,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-24 14:35:13'),(52,'ruta',1,'UPDATE','{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": null, \"id_ruta\": 1, \"sentido\": \"NORMAL\"}','{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": \"Sindicato Pedro Domingo Murillo\", \"id_ruta\": 1, \"sentido\": \"NORMAL\"}',NULL,'2026-09-25 05:58:48'),(53,'ruta',1,'UPDATE','{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": \"Sindicato Pedro Domingo Murillo\", \"id_ruta\": 1, \"sentido\": \"NORMAL\"}','{\"tipo\": \"minibus\", \"activo\": 1, \"nombre\": \"\", \"id_ruta\": 1, \"sentido\": \"NORMAL\"}',NULL,'2026-09-25 05:59:06'),(54,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 02:02:39'),(55,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 02:02:41'),(56,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 02:12:11'),(57,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 02:12:13'),(58,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 04:18:41'),(59,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 04:24:49'),(60,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 04:24:51'),(61,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 04:44:23'),(62,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 04:44:25'),(63,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 14:38:54'),(64,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 14:38:56'),(65,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 15:07:54'),(66,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 15:07:56'),(67,'lugar_turistico',2,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Parques Laikakota\", \"latitud\": -16.50251900, \"id_lugar\": 2, \"longitud\": -68.12459000, \"descripcion\": null, \"id_categoria\": 3}',NULL,'2026-09-25 20:00:50'),(68,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 17:18:57'),(69,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 17:18:59'),(70,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 17:30:49'),(71,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-25 17:30:51'),(72,'lugar_turistico',2,'UPDATE','{\"activo\": 1, \"nombre\": \"Parques Laikakota\", \"latitud\": -16.50251900, \"id_lugar\": 2, \"longitud\": -68.12459000, \"descripcion\": null, \"id_categoria\": 3}','{\"activo\": 1, \"nombre\": \"Parque Laikakota\", \"latitud\": -16.50251900, \"id_lugar\": 2, \"longitud\": -68.12459000, \"descripcion\": null, \"id_categoria\": 3}',NULL,'2026-09-25 21:55:47'),(73,'lugar_turistico',3,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Plaza Murillo\", \"latitud\": -16.49580000, \"id_lugar\": 3, \"longitud\": -68.13350000, \"descripcion\": null, \"id_categoria\": 2}',NULL,'2026-09-26 02:56:25'),(74,'lugar_turistico',4,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Laguna Cota Cota\", \"latitud\": -16.54167700, \"id_lugar\": 4, \"longitud\": -68.06484100, \"descripcion\": null, \"id_categoria\": 6}',NULL,'2026-09-26 03:08:44'),(75,'lugar_turistico',5,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Iglesia San Francisco\", \"latitud\": -16.49650000, \"id_lugar\": 5, \"longitud\": -68.13730000, \"descripcion\": null, \"id_categoria\": 5}',NULL,'2026-09-26 03:42:44'),(76,'lugar_turistico',6,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Plaza San Francisco\", \"latitud\": -16.49608000, \"id_lugar\": 6, \"longitud\": -68.13711000, \"descripcion\": null, \"id_categoria\": 2}',NULL,'2026-09-26 03:48:05'),(77,'lugar_turistico',7,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Plaza Villarroel \", \"latitud\": -16.48385000, \"id_lugar\": 7, \"longitud\": -68.12185000, \"descripcion\": null, \"id_categoria\": 2}',NULL,'2026-09-26 03:57:08'),(78,'lugar_turistico',4,'UPDATE','{\"activo\": 1, \"nombre\": \"Laguna Cota Cota\", \"latitud\": -16.54167700, \"id_lugar\": 4, \"longitud\": -68.06484100, \"descripcion\": null, \"id_categoria\": 6}','{\"activo\": 1, \"nombre\": \"Laguna Cota Cota\", \"latitud\": -16.54167700, \"id_lugar\": 4, \"longitud\": -68.06484100, \"descripcion\": null, \"id_categoria\": 6}',NULL,'2026-09-26 04:46:32'),(79,'lugar_turistico',2,'UPDATE','{\"activo\": 1, \"nombre\": \"Parque Laikakota\", \"latitud\": -16.50251900, \"id_lugar\": 2, \"longitud\": -68.12459000, \"descripcion\": null, \"id_categoria\": 3}','{\"activo\": 1, \"nombre\": \"Parque Laikakota\", \"latitud\": -16.50251900, \"id_lugar\": 2, \"longitud\": -68.12459000, \"descripcion\": null, \"id_categoria\": 3}',NULL,'2026-09-26 06:21:54'),(80,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 02:46:18'),(81,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 02:46:23'),(82,'lugar_turistico',4,'UPDATE','{\"activo\": 1, \"nombre\": \"Laguna Cota Cota\", \"latitud\": -16.54167700, \"id_lugar\": 4, \"longitud\": -68.06484100, \"descripcion\": null, \"id_categoria\": 6}','{\"activo\": 1, \"nombre\": \"Laguna Cota Cota\", \"latitud\": -16.54167700, \"id_lugar\": 4, \"longitud\": -68.06484100, \"descripcion\": null, \"id_categoria\": 6}',NULL,'2026-09-26 06:48:43'),(83,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 02:50:57'),(84,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 02:51:02'),(85,'lugar_turistico',8,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Museo Nacional de Arte\", \"latitud\": -16.49578000, \"id_lugar\": 8, \"longitud\": -68.13419000, \"descripcion\": null, \"id_categoria\": 4}',NULL,'2026-09-26 07:06:20'),(86,'lugar_turistico',9,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Museo Tambo Quirquincho\", \"latitud\": -16.49359000, \"id_lugar\": 9, \"longitud\": -68.13808000, \"descripcion\": null, \"id_categoria\": 4}',NULL,'2026-09-26 07:14:01'),(87,'lugar_turistico',10,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Museo Nacional de Historia Natural\", \"latitud\": -16.53898000, \"id_lugar\": 10, \"longitud\": -68.07075000, \"descripcion\": null, \"id_categoria\": 4}',NULL,'2026-09-26 07:19:09'),(88,'lugar_turistico',11,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Calle Jaén\", \"latitud\": -16.49500000, \"id_lugar\": 11, \"longitud\": -68.13500000, \"descripcion\": null, \"id_categoria\": 10}',NULL,'2026-09-26 07:24:40'),(89,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 03:54:04'),(90,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 03:54:06'),(91,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:04:58'),(92,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:05:00'),(93,'lugar_turistico',10,'UPDATE','{\"activo\": 1, \"nombre\": \"Museo Nacional de Historia Natural\", \"latitud\": -16.53898000, \"id_lugar\": 10, \"longitud\": -68.07075000, \"descripcion\": null, \"id_categoria\": 4}','{\"activo\": 1, \"nombre\": \"Museo Nacional de Historia Natural\", \"latitud\": -16.53898000, \"id_lugar\": 10, \"longitud\": -68.07075000, \"descripcion\": null, \"id_categoria\": 4}',NULL,'2026-09-26 04:08:26'),(94,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:14:59'),(95,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:15:01'),(96,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:45:24'),(97,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-26 04:45:26'),(98,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 06:52:37'),(99,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 06:52:40'),(100,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 07:01:11'),(101,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 07:01:13'),(102,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 09:19:01'),(103,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 09:24:57'),(104,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 09:25:48'),(105,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 14:58:51'),(106,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 14:58:53'),(107,'lugar_turistico',12,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}',NULL,'2026-09-28 19:41:43'),(108,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 15:50:35'),(109,'lugar_turistico',13,'INSERT',NULL,'{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}',NULL,'2026-09-28 19:54:22'),(110,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 15:55:47'),(111,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 15:55:49'),(112,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 16:53:54'),(113,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 16:53:56'),(114,'lugar_turistico',12,'UPDATE','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}',NULL,'2026-09-28 17:14:57'),(115,'lugar_turistico',13,'UPDATE','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}',NULL,'2026-09-28 17:14:57'),(116,'lugar_turistico',12,'UPDATE','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}',NULL,'2026-09-28 17:21:16'),(117,'lugar_turistico',13,'UPDATE','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}',NULL,'2026-09-28 17:21:16'),(118,'lugar_turistico',12,'UPDATE','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}',NULL,'2026-09-28 17:33:59'),(119,'lugar_turistico',13,'UPDATE','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}',NULL,'2026-09-28 17:33:59'),(120,'lugar_turistico',12,'UPDATE','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}','{\"activo\": 1, \"nombre\": \"Hospital Arcoiris\", \"latitud\": -16.48425000, \"id_lugar\": 12, \"longitud\": -68.12036000, \"descripcion\": null, \"id_categoria\": 7}',NULL,'2026-09-28 17:36:00'),(121,'lugar_turistico',13,'UPDATE','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}','{\"activo\": 1, \"nombre\": \"Universidad UPEA\", \"latitud\": -16.50000000, \"id_lugar\": 13, \"longitud\": -68.18000000, \"descripcion\": null, \"id_categoria\": 8}',NULL,'2026-09-28 17:36:00'),(122,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 22:49:46'),(123,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 22:54:37'),(124,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 22:54:52'),(125,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 22:54:54'),(126,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-28 23:59:43'),(127,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 00:28:18'),(128,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 04:28:33'),(129,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 00:28:54'),(130,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:03:15'),(131,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:03:55'),(132,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:18:27'),(133,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:18:29'),(134,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:20:18'),(135,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 05:23:29'),(136,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:23:53'),(137,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:32:27'),(138,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:33:46'),(139,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:41:35'),(140,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:44:14'),(141,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:44:46'),(142,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 05:45:02'),(143,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:48:37'),(144,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 05:51:39'),(145,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:51:47'),(146,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:54:17'),(147,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:55:43'),(148,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:58:36'),(149,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:58:58'),(150,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:59:32'),(151,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:59:36'),(152,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:59:40'),(153,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:59:45'),(154,'usuario',2,'UPDATE','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}','{\"rol\": \"usuario\", \"email\": \"atenciobonillayoselin@gmail.com\", \"activo\": 1, \"nombre\": \"Atencio Bonilla Yoselin\", \"id_usuario\": 2}',2,'2026-09-29 01:59:48');
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
INSERT INTO `categoria_lugar` VALUES (1,'Miradores','miradores','fa-binoculars',1,'2026-09-22 21:32:48','2026-09-22 21:32:48'),(2,'Plazas','plazas','fa-city',1,'2026-09-22 21:32:49','2026-09-22 21:32:49'),(3,'Parques','parques','fa-tree',1,'2026-09-22 21:32:50','2026-09-24 08:19:33'),(4,'Museos','museos','fa-landmark',1,'2026-09-22 21:32:51','2026-09-22 21:32:51'),(5,'Iglesias','iglesias','fa-church',1,'2026-09-22 21:32:52','2026-09-24 08:17:37'),(6,'Naturaleza','naturaleza','fa-leaf',1,'2026-09-22 21:32:53','2026-09-24 08:25:01'),(7,'Hospitales','hospitales','fa-hospital',1,'2026-09-22 21:32:54','2026-09-24 08:20:47'),(8,'Universidades','universidades','fa-graduation-cap',1,'2026-09-22 21:32:55','2026-09-24 08:21:53'),(9,'Comedores','comedores','fa-utensils',1,'2026-09-22 21:32:56','2026-09-24 08:23:19'),(10,'Calles Tradicionales','calles-tradicionales','fa-road',1,'2026-09-22 21:32:57','2026-09-24 08:24:17'),(11,'Cines','cines','fa-film',1,'2026-09-24 08:25:44','2026-09-24 08:25:44'),(12,'Telefericos','telefericos','fa-train',1,'2026-09-24 08:26:20','2026-09-24 08:26:20'),(13,'Pumas Katari','pumas-katari','fa-bus',1,'2026-09-24 08:26:52','2026-09-24 08:26:52');
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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_multimedia`
--

LOCK TABLES `lugar_multimedia` WRITE;
/*!40000 ALTER TABLE `lugar_multimedia` DISABLE KEYS */;
INSERT INTO `lugar_multimedia` VALUES (1,1,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/1.webp',NULL,NULL,NULL,1,1,'2026-09-24 08:39:17','2026-09-24 08:39:17'),(2,1,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/3.webp',NULL,NULL,NULL,2,1,'2026-09-24 08:39:18','2026-09-24 08:39:18'),(3,1,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/mirador/killikilli/360.jpg',NULL,NULL,NULL,1,1,'2026-09-24 08:39:20','2026-09-24 08:39:20'),(4,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_esp.mp3',NULL,'español',0,0,1,'2026-09-24 08:39:23','2026-09-24 08:39:23'),(5,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_eng.mp3',NULL,'ingles',0,0,1,'2026-09-24 08:39:24','2026-09-24 08:39:24'),(6,1,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/mirador/killikilli/audio_aym.mp3',NULL,'aymara',0,0,1,'2026-09-24 08:39:24','2026-09-24 08:39:24'),(7,2,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/parque/ikakota/1.webp',NULL,NULL,NULL,1,1,'2026-09-25 20:00:46','2026-09-25 20:00:46'),(8,2,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/parque/ikakota/2.webp',NULL,NULL,NULL,2,1,'2026-09-25 20:00:47','2026-09-25 20:00:47'),(9,2,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/parque/ikakota/3.webp',NULL,NULL,NULL,3,1,'2026-09-25 20:00:47','2026-09-25 20:00:47'),(10,2,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/parque/ikakota/360.jpg',NULL,NULL,NULL,1,1,'2026-09-25 20:00:48','2026-09-25 20:00:48'),(11,2,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/parque/ikakota/audio_esp.mp3',NULL,'español',0,0,1,'2026-09-25 20:00:48','2026-09-25 20:00:48'),(12,2,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/parque/ikakota/audio_eng.mp3',NULL,'ingles',0,0,1,'2026-09-25 20:00:48','2026-09-25 20:00:48'),(13,3,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/murillo/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 02:56:22','2026-09-26 02:56:22'),(14,3,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/murillo/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 02:56:23','2026-09-26 02:56:23'),(15,3,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/murillo/3.webp',NULL,NULL,NULL,3,1,'2026-09-26 02:56:23','2026-09-26 02:56:23'),(16,3,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/murillo/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 02:56:23','2026-09-26 02:56:23'),(17,3,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/plazas/murillo/audio_esp.mp3',NULL,'español',0,1,1,'2026-09-26 02:56:24','2026-09-26 02:56:24'),(18,3,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/plazas/murillo/audio_eng.mp3',NULL,'ingles',0,2,1,'2026-09-26 02:56:24','2026-09-28 11:00:25'),(19,3,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/plazas/murillo/audio_aym.mp3',NULL,'aymara',0,3,1,'2026-09-26 02:56:25','2026-09-26 02:56:25'),(20,4,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/naturaleza/lagunacotacota/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 03:08:42','2026-09-26 03:08:42'),(21,4,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/naturaleza/lagunacotacota/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 03:08:42','2026-09-26 03:08:42'),(22,4,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/naturaleza/lagunacotacota/3.webp',NULL,NULL,NULL,3,1,'2026-09-26 03:08:43','2026-09-26 03:08:43'),(23,4,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/naturaleza/lagunacotacota/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 03:08:44','2026-09-26 03:08:44'),(24,4,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/naturaleza/lagunadecotacota/audio_esp.mp3',NULL,'español',0,1,1,'2026-09-26 03:08:46','2026-09-26 03:08:46'),(25,4,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/naturaleza/lagunadecotacota/audio_eng.mp3',NULL,'ingles',0,2,1,'2026-09-26 03:08:47','2026-09-26 03:08:47'),(26,4,'audio','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/audio/naturaleza/lagunadecotacota/audio_aym.mp3',NULL,'aymara',0,3,1,'2026-09-26 03:08:47','2026-09-26 03:08:47'),(27,5,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/iglesia/sanfrancisco/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 03:42:42','2026-09-26 03:42:42'),(28,5,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/iglesia/sanfrancisco/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 03:42:42','2026-09-26 03:42:42'),(29,5,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/iglesia/sanfrancisco/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 03:42:42','2026-09-26 03:42:42'),(30,6,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/sanfrancisco/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 03:48:02','2026-09-26 03:48:02'),(31,6,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/sanfrancisco/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 03:48:03','2026-09-26 03:48:03'),(32,6,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/sanfrancisco/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 03:48:03','2026-09-26 03:48:03'),(33,7,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/villarruel/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 03:57:06','2026-09-26 03:57:06'),(34,7,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/villarruel/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 03:57:06','2026-09-26 03:57:06'),(35,7,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/villarruel/3.webp',NULL,NULL,NULL,3,1,'2026-09-26 03:57:07','2026-09-26 03:57:07'),(36,7,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/plazas/villarruel/360.jpeg',NULL,NULL,NULL,1,1,'2026-09-26 03:57:07','2026-09-26 04:05:43'),(37,8,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldelarte/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 07:06:20','2026-09-26 07:06:20'),(38,8,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldelarte/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 07:06:21','2026-09-26 07:06:21'),(39,8,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldelarte/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 07:06:21','2026-09-26 07:06:21'),(40,9,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/tamboquirquincho/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 07:14:01','2026-09-26 07:14:01'),(41,9,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/tamboquirquincho/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 07:14:02','2026-09-26 07:14:02'),(42,9,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/tamboquirquincho/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 07:14:02','2026-09-26 07:14:02'),(43,10,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldehistorianatural/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 07:19:09','2026-09-26 07:19:09'),(44,10,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldehistorianatural/2.webp',NULL,NULL,NULL,2,1,'2026-09-26 07:19:10','2026-09-26 07:19:10'),(45,10,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/museo/nacionaldehistorianatural/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 07:19:10','2026-09-26 07:19:10'),(46,11,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/calles-tradicionales/callejaen/1.webp',NULL,NULL,NULL,1,1,'2026-09-26 07:24:40','2026-09-26 07:24:40'),(47,11,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/calles-tradicionales/callejaen/2.webp',NULL,NULL,NULL,0,1,'2026-09-26 07:24:41','2026-09-26 07:24:41'),(48,11,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/calles-tradicionales/callejaen/360.jpg',NULL,NULL,NULL,1,1,'2026-09-26 07:24:41','2026-09-26 07:24:41'),(49,3,'3d','https://jssqzumoakhtytjhgldm.supabase.co/storage/v1/object/public/turismolapaz3d/3D/plazas/murillo/plaza_murillo.glb',NULL,NULL,NULL,1,1,'2026-09-28 12:43:59','2026-09-28 12:43:59'),(50,12,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/hospitales/arcoiris/hospitalarcoiris1.webp',NULL,NULL,NULL,1,1,'2026-09-28 19:41:41','2026-09-28 19:41:41'),(51,12,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/hospitales/arcoiris/hospitalarcoiris2.webp',NULL,NULL,NULL,2,1,'2026-09-28 19:41:41','2026-09-28 19:41:41'),(52,12,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/hospitales/arcoiris/hospitalarcoiris3.webp',NULL,NULL,NULL,3,1,'2026-09-28 19:41:41','2026-09-28 19:41:41'),(53,12,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/hospitales/arcoiris/a360.jpg',NULL,NULL,NULL,1,1,'2026-09-28 19:41:42','2026-09-29 02:48:24'),(54,13,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/universidades/upea/uni_upea1.webp',NULL,NULL,NULL,1,1,'2026-09-28 19:54:20','2026-09-28 19:54:20'),(55,13,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/universidades/upea/uni_upea2.webp',NULL,NULL,NULL,2,1,'2026-09-28 19:54:20','2026-09-28 19:54:20'),(56,13,'imagen','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/universidades/upea/uni_upea3.webp',NULL,NULL,NULL,3,1,'2026-09-28 19:54:20','2026-09-28 19:54:20'),(57,13,'360','https://aflmhhkehkclvsfyqzaw.supabase.co/storage/v1/object/public/multimedia-turismo-lapaz/360/universidades/upea/Principal_UPEA_360.jpeg',NULL,NULL,NULL,1,1,'2026-09-28 19:54:20','2026-09-28 19:54:20');
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
  `es_gratuito` tinyint(1) DEFAULT '1',
  `condiciones_gratis` text COLLATE utf8mb4_unicode_ci,
  `tabla_precios` text COLLATE utf8mb4_unicode_ci,
  `abierto_todos_los_dias` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `horarios` json DEFAULT NULL,
  `tipo_transporte` enum('micro','minibus','trufi','teleferico','puma_katari','todos','ninguno') COLLATE utf8mb4_unicode_ci DEFAULT 'todos',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `costo_descripcion` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_lugar`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `lugar_turistico_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_lugar` (`id_categoria`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lugar_turistico`
--

LOCK TABLES `lugar_turistico` WRITE;
/*!40000 ALTER TABLE `lugar_turistico` DISABLE KEYS */;
INSERT INTO `lugar_turistico` VALUES (1,'Mirador Killi Killi',NULL,NULL,1,-16.49549300,-68.12739600,NULL,NULL,0.00,1,NULL,NULL,1,1,'[{\"dias\": [0, 1, 2, 3, 4, 5, 6], \"cerrado\": false, \"hora_cierre\": \"23:59\", \"hora_apertura\": \"00:00\"}]','todos','2026-09-24 08:39:16','2026-09-24 08:39:16',NULL),(2,'Parque Laikakota',NULL,NULL,3,-16.50251900,-68.12459000,NULL,NULL,0.00,0,NULL,'Mayores de 10 a 64 años: 3.50 Bs\nMenores de 4 a 9 años: 1.00 Bs\nAdultos Mayores con presentacion de CI: Gratis',0,1,'[{\"dias\": [3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"19:00\", \"hora_apertura\": \"10:00\"}, {\"dias\": [6, 0, 7], \"cerrado\": false, \"hora_cierre\": \"19:00\", \"hora_apertura\": \"10:00\"}]','todos','2026-09-25 20:00:46','2026-09-26 06:21:53',NULL),(3,'Plaza Murillo',NULL,NULL,2,-16.49580000,-68.13350000,NULL,NULL,0.00,1,NULL,NULL,1,1,'[{\"dias\": [0, 1, 2, 3, 4, 5, 6, 7], \"cerrado\": false, \"hora_cierre\": \"23:59\", \"hora_apertura\": \"00:00\"}]','todos','2026-09-26 02:56:22','2026-09-26 02:56:22',NULL),(4,'Laguna Cota Cota',NULL,NULL,6,-16.54167700,-68.06484100,NULL,NULL,0.00,0,NULL,'Mayores de 10 a 64 años: 3.00 Bs\nMenores de 4 a 9 años: 1.00 Bs\nAdultos Mayores con presentacion de CI: Gratis',0,1,'[{\"dias\": [3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"17:00\", \"hora_apertura\": \"09:00\"}, {\"dias\": [6, 0, 7], \"cerrado\": false, \"hora_cierre\": \"20:00\", \"hora_apertura\": \"09:00\"}]','todos','2026-09-26 03:08:41','2026-09-26 06:48:43',NULL),(5,'Iglesia San Francisco',NULL,NULL,5,-16.49650000,-68.13730000,NULL,NULL,0.00,1,NULL,NULL,0,1,'[{\"dias\": [1, 2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"12:30\", \"hora_apertura\": \"06:30\"}, {\"dias\": [1, 2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"20:00\", \"hora_apertura\": \"16:00\"}, {\"dias\": [6, 0, 7], \"cerrado\": false, \"hora_cierre\": \"13:00\", \"hora_apertura\": \"06:30\"}, {\"dias\": [6, 0, 7], \"cerrado\": false, \"hora_cierre\": \"20:00\", \"hora_apertura\": \"18:30\"}]','todos','2026-09-26 03:42:41','2026-09-26 03:42:41',NULL),(6,'Plaza San Francisco',NULL,NULL,2,-16.49608000,-68.13711000,NULL,NULL,0.00,1,NULL,NULL,1,1,'[{\"dias\": [0, 1, 2, 3, 4, 5, 6, 7], \"cerrado\": false, \"hora_cierre\": \"23:59\", \"hora_apertura\": \"00:00\"}]','todos','2026-09-26 03:48:02','2026-09-26 03:48:02',NULL),(7,'Plaza Villarroel ',NULL,NULL,2,-16.48385000,-68.12185000,NULL,NULL,0.00,1,NULL,NULL,1,1,'[{\"dias\": [0, 1, 2, 3, 4, 5, 6, 7], \"cerrado\": false, \"hora_cierre\": \"23:59\", \"hora_apertura\": \"00:00\"}]','todos','2026-09-26 03:57:05','2026-09-26 03:57:05',NULL),(8,'Museo Nacional de Arte',NULL,NULL,4,-16.49578000,-68.13419000,NULL,NULL,0.00,0,'Todos los martes el ingreso es gratis para todo público\nEl primer viernes de cada mes abre gratis por la noche (18:00 a 22:00)','Nacionales: 5.00 Bs\nExtranjeros: 35.00 Bs\nUniversitarios tienen beneficio de 2x1: Una entrada de Bs 5 y entran dos\nMenores de 18 años, adultos mayores y personas con discapacidad: Gratis',1,1,'[{\"dias\": [2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"19:00\", \"hora_apertura\": \"09:00\"}, {\"dias\": [6], \"cerrado\": false, \"hora_cierre\": \"17:00\", \"hora_apertura\": \"09:00\"}, {\"dias\": [0], \"cerrado\": false, \"hora_cierre\": \"13:00\", \"hora_apertura\": \"09:00\"}]','todos','2026-09-26 07:06:20','2026-09-26 07:06:20',NULL),(9,'Museo Tambo Quirquincho',NULL,NULL,4,-16.49359000,-68.13808000,NULL,NULL,0.00,0,NULL,'Adultos generales: 10.00 Bs \nEstudiantes y niños: 2.00 Bs',1,1,'[{\"dias\": [2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"12:15\", \"hora_apertura\": \"09:15\"}, {\"dias\": [2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"18:45\", \"hora_apertura\": \"14:45\"}, {\"dias\": [6], \"cerrado\": false, \"hora_cierre\": \"16:45\", \"hora_apertura\": \"09:15\"}]','todos','2026-09-26 07:14:01','2026-09-26 07:14:01',NULL),(10,'Museo Nacional de Historia Natural',NULL,NULL,4,-16.53898000,-68.07075000,NULL,NULL,0.00,0,NULL,'Adultos: 5.00 Bs\nNiños y estudiantes: 2.00 Bs',0,1,'[{\"dias\": [1, 2, 3, 4, 5, 6], \"cerrado\": false, \"hora_cierre\": \"16:00\", \"hora_apertura\": \"09:00\"}]','todos','2026-09-26 07:19:09','2026-09-26 08:08:26',NULL),(11,'Calle Jaén',NULL,NULL,10,-16.49500000,-68.13500000,NULL,NULL,0.00,1,NULL,NULL,1,1,'[]','todos','2026-09-26 07:24:40','2026-09-26 07:24:40',NULL),(12,'Hospital Arcoiris',NULL,NULL,7,-16.48425000,-68.12036000,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'[]','todos','2026-09-28 19:41:40','2026-09-28 21:36:00',NULL),(13,'Universidad UPEA',NULL,NULL,8,-16.50000000,-68.18000000,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'[{\"dias\": [1, 2, 3, 4, 5], \"cerrado\": false, \"hora_cierre\": \"18:00\", \"hora_apertura\": \"08:30\"}, {\"dias\": [6], \"cerrado\": false, \"hora_cierre\": \"14:00\", \"hora_apertura\": \"09:00\"}]','todos','2026-09-28 19:54:19','2026-09-28 21:36:00',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_25_064801_drop_unused_columns_from_ruta_table',1),(2,'2026_09_25_192125_add_edad_max_nino_and_costo_extranjero_to_lugar_turistico_table',2),(3,'2026_09_26_042608_add_costo_descripcion_to_lugar_turistico_table',3),(4,'2026_09_26_043519_modify_costo_fields_to_text_and_add_new_costos',4),(5,'2026_09_26_120000_add_condiciones_gratis_to_lugar_turistico_table',5),(6,'2026_09_26_130000_replace_cost_fields_with_tabla_precios',6);
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
  `numero_ruta` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('micro','minibus','trufi','teleferico','puma_katari','otros') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sentido` enum('IDA','VUELTA','NORMAL') COLLATE utf8mb4_unicode_ci DEFAULT 'NORMAL',
  `destino` int DEFAULT NULL,
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
INSERT INTO `ruta` VALUES (1,'204','Sindicato Pedro Domingo Murillo','minibus','NORMAL',1,'-16.465362,-68.111871;-16.464909,-68.111029;-16.465097,-68.110927;-16.465333,-68.110793;-16.465207,-68.110586;-16.465207,-68.110506;-16.465606,-68.110578;-16.465868,-68.110704;-16.466607,-68.111418;-16.466848,-68.111678;-16.467178,-68.112182;-16.467381,-68.112515;-16.467520,-68.112818;-16.467740,-68.113443;-16.467807,-68.113617;-16.467931,-68.113837;-16.468028,-68.113955;-16.468279,-68.114179;-16.468510,-68.114395;-16.468657,-68.114524;-16.468745,-68.114634;-16.468807,-68.114756;-16.468794,-68.114864;-16.468799,-68.115024;-16.468754,-68.115167;-16.468684,-68.115271;-16.468472,-68.115469;-16.468376,-68.115594;-16.468329,-68.115733;-16.468313,-68.115855;-16.468331,-68.115965;-16.468397,-68.116124;-16.468499,-68.116239;-16.468610,-68.116327;-16.468751,-68.116380;-16.468938,-68.116398;-16.469127,-68.116352;-16.469446,-68.116181;-16.469719,-68.116062;-16.469832,-68.116043;-16.470325,-68.116136;-16.470462,-68.116158;-16.471032,-68.116164;-16.471513,-68.116242;-16.471706,-68.116268;-16.471972,-68.116329;-16.472156,-68.116396;-16.473195,-68.117093;-16.473603,-68.117383;-16.474005,-68.117654;-16.474213,-68.117792;-16.474463,-68.117939;-16.474591,-68.118013;-16.474678,-68.118048;-16.474665,-68.118104;-16.474668,-68.118162;-16.474686,-68.118230;-16.474724,-68.118292;-16.474785,-68.118338;-16.474847,-68.118358;-16.474919,-68.118366;-16.474991,-68.118350;-16.475069,-68.118289;-16.475383,-68.118485;-16.476133,-68.118957;-16.476828,-68.119319;-16.477255,-68.119581;-16.478198,-68.120176;-16.478440,-68.120320;-16.478445,-68.120357;-16.478471,-68.120394;-16.478525,-68.120400;-16.478562,-68.120384;-16.479280,-68.120815;-16.479691,-68.121047;-16.479916,-68.121161;-16.480402,-68.121342;-16.480887,-68.121519;-16.481340,-68.121704;-16.481680,-68.121812;-16.481857,-68.121866;-16.481908,-68.121854;-16.482101,-68.121889;-16.482250,-68.121981;-16.482630,-68.122326;-16.483013,-68.122624;-16.483449,-68.122920;-16.483711,-68.123058;-16.484193,-68.123265;-16.484555,-68.123402;-16.485208,-68.123640;-16.485328,-68.123693;-16.485699,-68.123956;-16.485960,-68.124094;-16.486153,-68.124170;-16.486548,-68.124279;-16.486880,-68.124386;-16.487173,-68.124515;-16.487713,-68.124770;-16.488670,-68.125172;-16.488765,-68.125164;-16.489157,-68.125545;-16.489257,-68.125588;-16.489373,-68.125581;-16.489898,-68.125381;-16.490858,-68.125282;-16.491489,-68.125199;-16.492232,-68.125172;-16.492708,-68.125199;-16.492875,-68.125134;-16.493163,-68.124939;-16.493356,-68.124952;-16.493466,-68.125023;-16.493675,-68.125191;-16.494054,-68.125513;-16.494361,-68.125769;-16.494432,-68.125804;-16.494501,-68.125818;-16.494600,-68.125822;-16.495004,-68.125800;-16.495200,-68.125816;-16.495534,-68.125931;-16.495796,-68.126030\',\n    ',NULL,1,'#0066CC','#0066CC','#FF6600','2026-09-24 08:42:57','2026-09-25 05:59:06');
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
INSERT INTO `ruta_lugar` VALUES (1,1,0,0.00,'2026-09-24 22:14:26','2026-09-24 22:14:26');
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
  UNIQUE KEY `unique_carnet_rol` (`carnet`,`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin@turismolapaz.com','$2y$12$S0zbptUTDu56Ps16KCKQle.WUHc6DCkt.Va7HrnXpxkLNt6bo3.ri','Administrador','71907637','13763484','admin_6ab2d364d9f17',NULL,1,'admin',1,'2026-09-22 19:13:40','2026-09-24 09:03:40',NULL),(2,'atenciobonillayoselin@gmail.com',NULL,'Atencio Bonilla Yoselin','','','dPG5nDdhajOe6zUmBVwOghbs1i73',NULL,1,'usuario',1,'2026-09-24 09:11:40','2026-09-29 05:59:48','https://lh3.googleusercontent.com/a/ACg8ocLWTGyldc1quxzGLtu9qYJGhvglF--x5PRG06go2Ap5s575btS9=s96-c');
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
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_sesion`
--

LOCK TABLES `usuario_sesion` WRITE;
/*!40000 ALTER TABLE `usuario_sesion` DISABLE KEYS */;
INSERT INTO `usuario_sesion` VALUES (1,2,'0650b0cc8b5189b709b7ccf9c76b668b282ea5fb6f92149ce1a059b50009c06a','2026-09-23 05:41:25','2026-10-23 05:41:25',0),(2,2,'07376dad9008a13e62408c013424bf147be1702e69b400922f297bb35d16d0dc','2026-09-24 05:11:40','2026-10-24 05:11:40',0),(3,2,'ccbff2d3763e84eaac9b10cd216cee9fff0425a34ed740cdefbea4c5a8a62c13','2026-09-24 14:13:57','2026-10-24 14:13:57',0),(4,2,'ff272471b8f5e47f1cc70de3753279ea776f0b88f41e83c2d3cb53ff1da1667e','2026-09-24 14:13:59','2026-10-24 14:13:59',0),(5,2,'3d39cc9cc38538de4e09a6ac7424ca679021e38ffc980cb0511d67145eaff53f','2026-09-24 14:20:24','2026-10-24 14:20:24',0),(6,2,'bdd6f33bc952e535173b1b35fc414bc3770f352415ff99cbc3bfdac35c1bc1bc','2026-09-24 14:35:13','2026-10-24 14:35:13',0),(7,2,'fed8b36be7132108e921be155e727cc9dc5af6cfffab34438e3046be6c9eb7db','2026-09-25 02:02:40','2026-10-25 02:02:40',0),(8,2,'24f6bbc0d2206c064ec13806e2e48b983aba7ecbcbc8c16aab04b7a94eb1a880','2026-09-25 02:02:41','2026-10-25 02:02:41',0),(9,2,'ce99c12c7cc9f852369555433cb257b2518a7c8b5bc5c7f34fc273603e567df0','2026-09-25 02:12:11','2026-10-25 02:12:11',0),(10,2,'aa785f3d79c58e3446b5818b83bd985bf07d9ee32d4c1036cb75ba6bb47d2f19','2026-09-25 02:12:13','2026-10-25 02:12:13',0),(11,2,'cf7137f93c0dbe88275a0d553ef93050682088f3ee4791ba2538ac9a65d38d50','2026-09-25 04:18:41','2026-10-25 04:18:41',0),(12,2,'1227f9949c799a7de81ce8c74a62bf58fcf438a6d1f51e55e8e42fa615be7af4','2026-09-25 04:24:49','2026-10-25 04:24:49',0),(13,2,'46c396b7d64da1de776ed6a2654e04909c5b7f295689dd24cca776a84c2e1517','2026-09-25 04:24:51','2026-10-25 04:24:51',0),(14,2,'95f935d0c182ad675cd1d05e7262390d7829f2cae1a0d1edc7d0321e40decd42','2026-09-25 04:44:24','2026-10-25 04:44:24',0),(15,2,'6c1e9f6e9ba1f28443f1f5cb8df159f92398a3d7d94ea2dd81220cb6041160e7','2026-09-25 04:44:26','2026-10-25 04:44:26',0),(16,2,'5f72bbcd75e4b8587daa9ac5dc7b327df7c34a1bd925f896b8ea6bd9213b075a','2026-09-25 14:38:55','2026-10-25 14:38:55',0),(17,2,'db4a117d66698daa9e52b9c09aabdd57421e2c6655e23fff3ae46a884e93083f','2026-09-25 14:38:57','2026-10-25 14:38:57',0),(18,2,'d65c0f00f0825d903f4d0edc59a2a6260deec427e938aff324e5fc9b77d831be','2026-09-25 15:07:54','2026-10-25 15:07:54',0),(19,2,'d2af34724093dba4a25d06d30cfee3ac4041a93a43bdbbea4f58e36543388014','2026-09-25 15:07:56','2026-10-25 15:07:56',0),(20,2,'cc191dd6d5fb049b77ffe6813e4bc9ca756c3531c231d46aac84f96943b4c902','2026-09-25 17:18:57','2026-10-25 17:18:57',0),(21,2,'6bbed8b49535b76fe3c68ce42c98d4e65dc26ae67fba6571817556fddc7bf514','2026-09-25 17:18:59','2026-10-25 17:18:59',0),(22,2,'924678bb7227b6fbdfe802d76358dc2dd3303a1ba7899ddf7b9b48c87087d722','2026-09-25 17:30:49','2026-10-25 17:30:49',0),(23,2,'d4c9675eae22b1c8c061fa6912d89c7468fa5fc24b0c17238a3a5a5ba87172c9','2026-09-25 17:30:51','2026-10-25 17:30:51',0),(24,2,'561ae98aaea7d4d3e38213a316e30d07458381f61831e6d5d7ee95b422966de5','2026-09-26 02:46:19','2026-10-26 02:46:19',0),(25,2,'21bbec3e23abb7971a25a838215a29ff135046a41fea11b40014a473b0aecc92','2026-09-26 02:46:24','2026-10-26 02:46:24',0),(26,2,'db79c78effbcf8720d8a73efe05aa2317fba0ea55dcd03d94ad55f11811606f1','2026-09-26 02:50:58','2026-10-26 02:50:58',0),(27,2,'dd15dfa8e3c937fc9d88a96b87e709020dc29cd5e51b819e63da533750e63b82','2026-09-26 02:51:03','2026-10-26 02:51:03',0),(28,2,'d58eaa895728560762fadbd7664962780aecd96089455a731e044ca5eb46619e','2026-09-26 03:54:04','2026-10-26 03:54:04',0),(29,2,'635c5c4aab1c149fb17d230a08589794c3a73c331ea03c0e883dcdb558f4f78b','2026-09-26 03:54:06','2026-10-26 03:54:06',0),(30,2,'f1b890a5575e5c16447a3903632ac1ae00ce235d79e48e8a721996863d742da0','2026-09-26 04:04:58','2026-10-26 04:04:58',0),(31,2,'152e26f38a6c8e2cafc84884fc5690768d3b10d3a3e3f2a033261536c3f6a8c1','2026-09-26 04:05:01','2026-10-26 04:05:01',0),(32,2,'176addede7b25d6f46970e6076024043750fbd97e3c345def9f5f4f017cd0f97','2026-09-26 04:15:00','2026-10-26 04:15:00',0),(33,2,'3189bcde67976820202fa0bd08fdc69737f3c8df4d4fac81434c575fa614b496','2026-09-26 04:15:02','2026-10-26 04:15:02',0),(34,2,'fcbff48a3c8d2bbb38702e45cb306f91714e68162fbc0cb336b6a33d5550c726','2026-09-26 04:45:25','2026-10-26 04:45:25',0),(35,2,'9723e618841a05e9bbdcadec466f0388bcf6b0dc07b0989d8b40df184d6be671','2026-09-26 04:45:27','2026-10-26 04:45:27',0),(36,2,'b6c8fec63b89c6702cb26188023bfce0411e76c5d85d5411718fae6b78b13df0','2026-09-28 06:52:38','2026-10-28 06:52:38',0),(37,2,'dbb3f1a9b9575a369c26ac7580efc609136e6f0724b07366c3ca65f7eef8e1ba','2026-09-28 06:52:40','2026-10-28 06:52:40',0),(38,2,'592c05b14b1ba5bc2bb2c33e225fd6b23bf7773961cda869b272944704e0d4eb','2026-09-28 07:01:11','2026-10-28 07:01:11',0),(39,2,'a40bc6944f115ad2f9c885b7987173436c52a4a8c6d59882ed7df964a7b383cf','2026-09-28 07:01:13','2026-10-28 07:01:13',0),(40,2,'b24123e5e1dd09ac5c14e64c84eb98093e3712ed2bf93de7f5e90c93b5e790bf','2026-09-28 09:19:01','2026-10-28 09:19:01',0),(41,2,'88b6bf0c1e5bff8659a4e1db5aae5a5c2aacd329be485ce565c31a8f3eb68360','2026-09-28 09:24:57','2026-10-28 09:24:57',0),(42,2,'0a4b352ea5203e5908631674f8a1d0284660381cb68ad35213def683e7cc3cd0','2026-09-28 09:25:48','2026-10-28 09:25:48',0),(43,2,'fd66287a26dc7668896d34f79a593fe448233b50c6ebde22e1ee00eaee38deeb','2026-09-28 14:58:51','2026-10-28 14:58:51',0),(44,2,'213504055793b01c3faac321318cf39766668ab9074d668b1712122a29a4dfb9','2026-09-28 14:58:53','2026-10-28 14:58:53',0),(45,2,'94d83fbe32fc45fd4d7ef17b3175faab0c816afba2b4b7c90c5fa76f4c93aa8e','2026-09-28 15:50:35','2026-10-28 15:50:35',0),(46,2,'ba0f14aa89be27a89a643ce272da76675bf898f1a71b6d465a0f06e54b1ec662','2026-09-28 15:55:47','2026-10-28 15:55:47',0),(47,2,'2da93949c7df9a3f2b645eab0544256479c11c45a94b77fd1852a4cb24dcdcac','2026-09-28 15:55:49','2026-10-28 15:55:49',0),(48,2,'17e6d79d36a887336ff14caaa952c1f1d61b785e7e925c4dfa7e4679ec7dc31b','2026-09-28 16:53:54','2026-10-28 16:53:54',0),(49,2,'f18ef6837b0313d510ffbdb1e969feb76304780f6d312ce556922cf82ac2fe2c','2026-09-28 16:53:56','2026-10-28 16:53:56',0),(50,2,'ef5d083919ccede66b96e56d033927eb81ff5d4f496805652269e0fa75966816','2026-09-28 22:49:46','2026-10-28 22:49:46',0),(51,2,'c9bbc74cc15d495da98c09b8c96e7913a22622c515326a7b0488b98fd51ad1ec','2026-09-28 22:54:38','2026-10-28 22:54:38',0),(52,2,'dc39b75fdda5737afdaf17dcc85e852b1cdc77b94b81838ac733faf34cc0c853','2026-09-28 22:54:53','2026-10-28 22:54:53',0),(53,2,'a173f0ac260909faae49424222cc09da676bbdc4931748b176a4e258dce1f0ce','2026-09-28 22:54:55','2026-10-28 22:54:55',0),(54,2,'78c210e43053ef12c0d53bd64eaf95d89dc300558b472b6025a7fc78c3892c09','2026-09-28 23:59:43','2026-10-28 23:59:43',0),(55,2,'ff28f999747e714fe30275340110e1128b3afdc0d173c55348c7944404395b2c','2026-09-29 00:28:18','2026-10-29 00:28:18',0),(56,2,'64a369284f2265ead5aff78c80d08900109d47a84c48ab0c9327e92717d0b520','2026-09-29 00:28:54','2026-10-29 00:28:54',0),(57,2,'703b212892d6f05570f164ed97a7bceb349397a7f37e5d17d037c1f05a626a9c','2026-09-29 01:03:15','2026-10-29 01:03:15',0),(58,2,'cdc4914e04df5eb29986627a896925212abfb0f91cb48777267b024384850c55','2026-09-29 01:03:55','2026-10-29 01:03:55',0),(59,2,'a481e3a0472db8654fba452adac91e69e4f86949b31e693e45cb47153e463314','2026-09-29 01:18:27','2026-10-29 01:18:27',0),(60,2,'38a28746ccb6fcb9550e3b8689c92e773baaf7353627ccc2f1befa51738c2439','2026-09-29 01:18:29','2026-10-29 01:18:29',0),(61,2,'fa546679371c37b8477de61b3f91edf7c81b06f6aa881363b86ead0e0fce6ce7','2026-09-29 01:20:19','2026-10-29 01:20:19',0),(62,2,'17d09af04698adf28db6d9660b8fad066541e901935fa75e3bf58ade1bb501f5','2026-09-29 01:23:53','2026-10-29 01:23:53',0),(63,2,'92dbe26436f7cc9ed5eb40c14d55d95e4f259e2009763bcc8b61ea3cfb53774d','2026-09-29 01:32:28','2026-10-29 01:32:28',0),(64,2,'f19d00b4f5e5cd7296b104aab157c55007dabb67503ec67a26aa7618f2184ab7','2026-09-29 01:33:46','2026-10-29 01:33:46',0),(65,2,'317b6c3fb47254dd452871957f8c907b84c9980f6a3fb2b0725c0aa84cfed72a','2026-09-29 01:44:14','2026-10-29 01:44:14',0),(66,2,'a4d1c3dd4d47669813358097370e305a584429491b5d33238e5d0ed1443b3afc','2026-09-29 01:44:46','2026-10-29 01:44:46',0),(67,2,'7bf746cd2fa38c49e9243e9f42acc70bbc54f4b84d0a6ce8c396d7a8e2d2e7d3','2026-09-29 01:48:37','2026-10-29 01:48:37',0),(68,2,'ba1623f4cfafe306d601737794e99a63838b61273f0dc4573ad6bb37822db16e','2026-09-29 01:54:17','2026-10-29 01:54:17',0),(69,2,'7e2b86bd23301b84ad4d35f043b868d5d806ddb7905fac8fd508a279508ccb88','2026-09-29 01:58:37','2026-10-29 01:58:37',0),(70,2,'90a57a720d8075050c0122280fa74aba8572c9d7b755da9f4fe08ea7bd75dd4f','2026-09-29 01:58:58','2026-10-29 01:58:58',0),(71,2,'2e43928da4ee3398dd9bc698b69924be74d204030b01dc309cbf7f70ffe98f59','2026-09-29 01:59:32','2026-10-29 01:59:32',0),(72,2,'7cd7fcf51a34737883f495d00c71f09d177ec6e258f5fb40d91fad7087b69ae1','2026-09-29 01:59:37','2026-10-29 01:59:37',0),(73,2,'e58dd35869aa11dffd2c9137bef452313977184e20c0349ad92a87aaa6082195','2026-09-29 01:59:40','2026-10-29 01:59:40',0),(74,2,'c7c6c02130cf113d037f772f5bd406959831154141890ced37385e34194566d2','2026-09-29 01:59:45','2026-10-29 01:59:45',0),(75,2,'fcdd7fc19cd798790b2e59ab1215488197d58f93e25902181ffef4ec93ab8413','2026-09-29 01:59:48','2026-10-29 01:59:48',1);
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

-- Dump completed on 2026-09-29  2:04:27
