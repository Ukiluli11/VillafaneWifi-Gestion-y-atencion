-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: villafane_wifi
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `villafane_wifi`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `villafane_wifi` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `villafane_wifi`;

--
-- Table structure for table `administrador`
--

DROP TABLE IF EXISTS `administrador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administrador` (
  `id_administrador` int(10) unsigned NOT NULL,
  `nivel_acceso` enum('total','restringido') NOT NULL DEFAULT 'total',
  `puede_gestionar_usuarios` tinyint(1) NOT NULL DEFAULT 1,
  `puede_configurar_planes` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_administrador`),
  CONSTRAINT `fk_administrador_usuario` FOREIGN KEY (`id_administrador`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrador`
--

LOCK TABLES `administrador` WRITE;
/*!40000 ALTER TABLE `administrador` DISABLE KEYS */;
INSERT INTO `administrador` VALUES (1,'total',1,1),(2,'total',1,1),(3,'total',1,1);
/*!40000 ALTER TABLE `administrador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente` (
  `id_cliente` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('DNI','CUIT','CUIL') NOT NULL,
  `numero_documento` varchar(20) NOT NULL,
  `nombre_razon_social` varchar(150) NOT NULL,
  `tipo_cliente` enum('particular','comercio') NOT NULL DEFAULT 'particular',
  `calle_contacto` varchar(100) DEFAULT NULL,
  `numero_contacto` varchar(10) DEFAULT NULL,
  `localidad_contacto` varchar(100) DEFAULT NULL,
  `telefono_whatsapp` varchar(20) DEFAULT NULL,
  `estado` enum('activo','suspendido','baja') NOT NULL DEFAULT 'activo',
  `recordatorios_whatsapp` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uq_cliente_documento` (`tipo_documento`,`numero_documento`),
  KEY `ix_cliente_busqueda` (`nombre_razon_social`,`numero_documento`),
  KEY `ix_cliente_whatsapp` (`telefono_whatsapp`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'DNI','30123456','Ana Gómez','particular','Belgrano','245','Villafañe','5493718123401','activo',0),(2,'DNI','27876543','Roberto Díaz','particular','San Martín','118','El Colorado','5493704123402','activo',0),(3,'CUIT','30712345678','Kiosco El Lapacho','comercio','Sarmiento','602','Villafañe','5493718123403','activo',0),(4,'CUIT','30765432109','Librería Norte','comercio','Rivadavia','411','Pirané','5493704123404','suspendido',0),(5,'DNI','33456789','Marta Benítez','particular','Moreno','87','Villa Dos Trece','5493718123405','activo',0),(6,'CUIT','30678901234','Panadería La Estación','comercio','Mitre','930','Villafañe','5493718123406','activo',0);
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprobante`
--

DROP TABLE IF EXISTS `comprobante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comprobante` (
  `id_comprobante` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` int(10) unsigned NOT NULL,
  `id_conversacion` int(10) unsigned DEFAULT NULL,
  `id_mensaje` int(10) unsigned DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamanio_bytes` bigint(20) unsigned DEFAULT NULL,
  `id_pago` int(10) unsigned DEFAULT NULL,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `hash_archivo` varchar(64) NOT NULL,
  `numero_operacion` varchar(50) DEFAULT NULL,
  `monto_ocr` decimal(10,2) DEFAULT NULL,
  `fecha_ocr` date DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `estado_validacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `fecha_hora_validacion` datetime DEFAULT NULL,
  `origen` enum('whatsapp','panel_manual') NOT NULL DEFAULT 'panel_manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `disco_archivo` varchar(50) NOT NULL DEFAULT 'local',
  `estado_ocr` varchar(30) NOT NULL DEFAULT 'sin_procesar',
  `error_ocr` varchar(255) DEFAULT NULL,
  `completitud_ocr` decimal(3,2) DEFAULT NULL,
  `intentos_duplicados` int(10) unsigned NOT NULL DEFAULT 0,
  `ultimo_duplicado_at` datetime DEFAULT NULL,
  `reenvio_ocr_pendiente` tinyint(1) NOT NULL DEFAULT 0,
  `aviso_resultado_pendiente` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_comprobante`),
  UNIQUE KEY `uq_comprobante_hash` (`hash_archivo`),
  UNIQUE KEY `uq_comprobante_mensaje` (`id_mensaje`),
  KEY `fk_comprobante_pago` (`id_pago`),
  KEY `ix_comprobante_operacion` (`numero_operacion`),
  KEY `ix_comprobante_estado` (`estado_validacion`),
  KEY `fk_comprobante_conversacion` (`id_conversacion`),
  KEY `ix_comprobante_cliente_fecha` (`id_cliente`,`fecha_recepcion`),
  KEY `fk_comprobante_usuario` (`id_usuario`),
  KEY `ix_comprobante_fecha_validacion` (`fecha_hora_validacion`),
  KEY `comprobante_avisos_pendientes_idx` (`id_cliente`,`aviso_resultado_pendiente`),
  CONSTRAINT `fk_comprobante_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_comprobante_conversacion` FOREIGN KEY (`id_conversacion`) REFERENCES `conversacion` (`id_conversacion`) ON DELETE SET NULL,
  CONSTRAINT `fk_comprobante_mensaje` FOREIGN KEY (`id_mensaje`) REFERENCES `mensaje` (`id_mensaje`) ON DELETE SET NULL,
  CONSTRAINT `fk_comprobante_pago` FOREIGN KEY (`id_pago`) REFERENCES `pago` (`id_pago`) ON DELETE SET NULL,
  CONSTRAINT `fk_comprobante_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobante`
--

LOCK TABLES `comprobante` WRITE;
/*!40000 ALTER TABLE `comprobante` DISABLE KEYS */;
INSERT INTO `comprobante` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'7a27dc5cd181bceb224b8abeafde8bc1628d41c75bff45c1cded88c84582660e','8294719284',16500.00,'2026-09-27',NULL,'pendiente',NULL,NULL,'whatsapp','2026-09-27 18:48:51','2026-09-27 18:48:51','local','sin_procesar',NULL,NULL,0,NULL,0,0),(2,2,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'c25bdb6ab4d4e2d3becd74f70dbe3ffe603c65cdccaa636386e55376683b12e9','7192840192',18000.00,'2026-07-10',NULL,'aprobado',NULL,NULL,'panel_manual','2026-09-27 18:48:51','2026-09-27 18:48:51','local','sin_procesar',NULL,NULL,0,NULL,0,0),(3,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1fc9d0f448f95161cbc581104207197fe57dee6913b951dcfe9f4ec111d0e4bf','10293847',12000.00,'2026-09-26',NULL,'rechazado','Comprobante ilegible, no coincide el CBU de la cuenta receptora informada.',NULL,'whatsapp','2026-09-27 18:48:51','2026-09-27 18:48:51','local','sin_procesar',NULL,NULL,0,NULL,0,0);
/*!40000 ALTER TABLE `comprobante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumo_whatsapp_mes`
--

DROP TABLE IF EXISTS `consumo_whatsapp_mes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumo_whatsapp_mes` (
  `id_telefono` varchar(80) NOT NULL,
  `periodo` char(7) NOT NULL,
  `servicios_reservados` int(10) unsigned NOT NULL DEFAULT 0,
  `plantillas_reservadas` int(10) unsigned NOT NULL DEFAULT 0,
  `dia_plantillas` date DEFAULT NULL,
  `plantillas_del_dia` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_telefono`,`periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumo_whatsapp_mes`
--

LOCK TABLES `consumo_whatsapp_mes` WRITE;
/*!40000 ALTER TABLE `consumo_whatsapp_mes` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumo_whatsapp_mes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversacion`
--

DROP TABLE IF EXISTS `conversacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversacion` (
  `id_conversacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` int(10) unsigned DEFAULT NULL,
  `id_usuario_atencion` int(10) unsigned DEFAULT NULL,
  `numero_whatsapp` varchar(20) NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_cierre` datetime DEFAULT NULL,
  `estado` enum('abierta','escalada','en_atencion','cerrada') NOT NULL DEFAULT 'abierta',
  `modo_atencion` enum('bot','humano') NOT NULL DEFAULT 'bot',
  `intencion_actual` varchar(50) DEFAULT NULL,
  `paso_actual` varchar(80) DEFAULT NULL,
  `contexto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contexto`)),
  `intentos_interpretacion` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `inicio_atencion` datetime DEFAULT NULL,
  `fin_atencion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_conversacion`),
  KEY `fk_conversacion_cliente` (`id_cliente`),
  KEY `fk_conversacion_usuario_atencion` (`id_usuario_atencion`),
  KEY `ix_conversacion_numero_estado` (`numero_whatsapp`,`estado`),
  KEY `ix_conversacion_estado_inicio` (`estado`,`fecha_hora_inicio`),
  CONSTRAINT `fk_conversacion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_conversacion_usuario_atencion` FOREIGN KEY (`id_usuario_atencion`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversacion`
--

LOCK TABLES `conversacion` WRITE;
/*!40000 ALTER TABLE `conversacion` DISABLE KEYS */;
INSERT INTO `conversacion` VALUES (1,1,6,'5493704123456','2026-11-19 19:44:04',NULL,'en_atencion','humano','consulta_saldo','atendido_por_operador','{\"origen\":\"WhatsApp Cloud API\",\"cliente_verificado\":true}',0,'2026-11-19 20:44:04',NULL);
/*!40000 ALTER TABLE `conversacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuenta_receptora`
--

DROP TABLE IF EXISTS `cuenta_receptora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuenta_receptora` (
  `id_cuenta` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('banco','mercado_pago','uala','otro') NOT NULL,
  `identificador` varchar(100) NOT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  PRIMARY KEY (`id_cuenta`),
  UNIQUE KEY `uq_cuenta_identificador` (`tipo`,`identificador`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuenta_receptora`
--

LOCK TABLES `cuenta_receptora` WRITE;
/*!40000 ALTER TABLE `cuenta_receptora` DISABLE KEYS */;
INSERT INTO `cuenta_receptora` VALUES (1,'Mercado Pago Villafañe Wifi','mercado_pago','villafanewifi.mp','activa'),(2,'Cuenta bancaria principal','banco','CBU-DEMO-0001','activa');
/*!40000 ALTER TABLE `cuenta_receptora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuota`
--

DROP TABLE IF EXISTS `cuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuota` (
  `id_cuota` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_servicio` int(10) unsigned NOT NULL,
  `id_pago` int(10) unsigned DEFAULT NULL,
  `periodo` char(7) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagada','vencida') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id_cuota`),
  UNIQUE KEY `uq_cuota_servicio_periodo` (`id_servicio`,`periodo`),
  KEY `fk_cuota_pago` (`id_pago`),
  KEY `ix_cuota_estado_vencimiento` (`estado`,`fecha_vencimiento`),
  CONSTRAINT `fk_cuota_pago` FOREIGN KEY (`id_pago`) REFERENCES `pago` (`id_pago`),
  CONSTRAINT `fk_cuota_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuota`
--

LOCK TABLES `cuota` WRITE;
/*!40000 ALTER TABLE `cuota` DISABLE KEYS */;
INSERT INTO `cuota` VALUES (1,1,1,'2026-07',18000.00,'2026-07-01','2026-07-10','pagada'),(2,1,NULL,'2026-08',18000.00,'2026-08-01','2026-08-10','vencida'),(3,1,3,'2026-09',100.00,'2026-09-01','2026-09-10','pagada'),(4,2,NULL,'2026-07',26000.00,'2026-07-01','2026-07-15','vencida'),(5,2,NULL,'2026-08',26000.00,'2026-08-01','2026-08-15','vencida'),(6,2,NULL,'2026-09',150.00,'2026-09-01','2026-09-15','vencida'),(7,3,2,'2026-07',42000.00,'2026-07-01','2026-07-05','pagada'),(8,3,NULL,'2026-08',42000.00,'2026-08-01','2026-08-05','vencida'),(9,3,4,'2026-09',200.00,'2026-09-01','2026-09-05','pagada'),(10,4,NULL,'2026-07',26000.00,'2026-07-01','2026-07-12','vencida'),(11,4,NULL,'2026-08',26000.00,'2026-08-01','2026-08-12','vencida'),(12,4,NULL,'2026-09',150.00,'2026-09-01','2026-09-12','vencida'),(13,5,NULL,'2026-07',18000.00,'2026-07-01','2026-07-20','vencida'),(14,5,NULL,'2026-08',18000.00,'2026-08-01','2026-08-20','vencida'),(15,5,NULL,'2026-09',100.00,'2026-09-01','2026-09-20','vencida'),(16,6,NULL,'2026-07',42000.00,'2026-07-01','2026-07-08','vencida'),(17,6,NULL,'2026-08',42000.00,'2026-08-01','2026-08-08','vencida'),(18,6,NULL,'2026-09',200.00,'2026-09-01','2026-09-08','vencida'),(19,1,NULL,'2026-10',100.00,'2026-10-01','2026-10-10','vencida'),(20,1,NULL,'2026-11',100.00,'2026-11-01','2026-11-10','vencida'),(21,2,NULL,'2026-10',150.00,'2026-10-01','2026-10-15','vencida'),(22,2,NULL,'2026-11',150.00,'2026-11-01','2026-11-15','vencida'),(23,3,NULL,'2026-10',200.00,'2026-10-01','2026-10-05','vencida'),(24,3,NULL,'2026-11',200.00,'2026-11-01','2026-11-05','vencida'),(25,4,NULL,'2026-10',150.00,'2026-10-01','2026-10-12','vencida'),(26,4,NULL,'2026-11',150.00,'2026-11-01','2026-11-12','vencida'),(27,5,NULL,'2026-10',100.00,'2026-10-01','2026-10-20','vencida'),(28,5,NULL,'2026-11',100.00,'2026-11-01','2026-11-20','pendiente'),(29,6,NULL,'2026-10',200.00,'2026-10-01','2026-10-08','vencida'),(30,6,NULL,'2026-11',200.00,'2026-11-01','2026-11-08','vencida');
/*!40000 ALTER TABLE `cuota` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleado`
--

DROP TABLE IF EXISTS `empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleado` (
  `id_empleado` int(10) unsigned NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `turno` enum('ma?ana','tarde','noche') DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  PRIMARY KEY (`id_empleado`),
  CONSTRAINT `fk_empleado_usuario` FOREIGN KEY (`id_empleado`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleado`
--

LOCK TABLES `empleado` WRITE;
/*!40000 ALTER TABLE `empleado` DISABLE KEYS */;
INSERT INTO `empleado` VALUES (4,'soporte',NULL,NULL,NULL),(6,'atencion_cliente',NULL,NULL,NULL);
/*!40000 ALTER TABLE `empleado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
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
-- Table structure for table `mensaje`
--

DROP TABLE IF EXISTS `mensaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensaje` (
  `id_mensaje` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_conversacion` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned DEFAULT NULL,
  `id_mensaje_externo` varchar(120) DEFAULT NULL,
  `clave_idempotencia` varchar(150) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `tipo` enum('texto','imagen','documento','audio','ubicacion','contacto','interactivo','notificacion','desconocido') NOT NULL DEFAULT 'texto',
  `contenido` text DEFAULT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `tipo_emisor` enum('cliente','bot','usuario_interno','sistema') NOT NULL,
  `estado_envio` enum('recibido','pendiente','enviado','entregado','leido','fallido') NOT NULL,
  PRIMARY KEY (`id_mensaje`),
  UNIQUE KEY `uq_mensaje_externo` (`id_mensaje_externo`),
  UNIQUE KEY `uq_mensaje_idempotencia` (`clave_idempotencia`),
  KEY `fk_mensaje_usuario` (`id_usuario`),
  KEY `ix_mensaje_conversacion_fecha` (`id_conversacion`,`fecha_hora`),
  CONSTRAINT `fk_mensaje_conversacion` FOREIGN KEY (`id_conversacion`) REFERENCES `conversacion` (`id_conversacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_mensaje_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensaje`
--

LOCK TABLES `mensaje` WRITE;
/*!40000 ALTER TABLE `mensaje` DISABLE KEYS */;
INSERT INTO `mensaje` VALUES (1,1,NULL,'wamid.HBgLM6aff7bb489ca1','msg_6aff7bb489ca2','2026-11-19 19:44:04','texto','Hola, buenas tardes. Quería consultar por el saldo de mi cuota de este mes.',NULL,'cliente','entregado'),(2,1,NULL,'wamid.HBgLM6aff7bb489ce8','msg_6aff7bb489ce9','2026-11-19 19:45:04','texto','¡Hola! Te comunicás con Villafañe Wifi. Tu cuota de Noviembre 2026 se encuentra al día. Saldo a pagar: $0.00.',NULL,'bot','leido'),(3,1,6,NULL,'msg_6aff7bb489d0a','2026-11-19 20:44:04','texto','Buenas tardes, le escribe el operador de guardia. ¿Puedo ayudarle en algo más con su conexión?',NULL,'usuario_interno','leido'),(4,1,NULL,'wamid.HBgLM6aff7bb489d1a','msg_6aff7bb489d1b','2026-11-19 22:29:04','texto','No, eso era todo. ¡Muchas gracias por la atención rápida!',NULL,'cliente','recibido');
/*!40000 ALTER TABLE `mensaje` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_03_062716_create_modulo_uno_tables',1),(2,'2026_09_27_120000_create_comprobante_table',2),(3,'2026_09_27_150000_create_modulo_dos_conversaciones_tables',3),(4,'2026_09_27_150500_upgrade_legacy_conversation_tables',3),(5,'2026_09_27_151000_create_ticket_table',3),(6,'2026_09_27_152000_add_whatsapp_context_to_comprobante_table',3),(7,'2026_10_01_180000_add_auditoria_to_comprobante_table',3),(8,'2026_10_17_225635_create_nota_interna_table',4),(9,'2026_10_20_032202_add_procesamiento_to_comprobante_table',4),(10,'2026_10_20_032933_create_jobs_table',4),(11,'2026_10_20_032934_create_failed_jobs_table',4),(12,'2026_10_20_063923_create_consumo_whatsapp_mes_table',4),(13,'2026_10_20_072342_permitir_tickets_sin_conversacion',4),(14,'2026_11_15_223504_add_alerta_duplicado_to_comprobante_table',4),(15,'2026_11_15_224630_add_reenvio_ocr_pendiente_to_comprobante_table',4),(16,'2026_11_15_225432_add_aviso_resultado_pendiente_to_comprobante_table',4),(17,'2026_11_16_004215_agregar_recordatorios_whatsapp_a_cliente',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nota_interna`
--

DROP TABLE IF EXISTS `nota_interna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nota_interna` (
  `id_nota` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `contenido` text NOT NULL,
  PRIMARY KEY (`id_nota`),
  KEY `fk_nota_usuario` (`id_usuario`),
  KEY `ix_nota_ticket_fecha` (`id_ticket`,`fecha_hora`),
  CONSTRAINT `fk_nota_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`) ON DELETE CASCADE,
  CONSTRAINT `fk_nota_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nota_interna`
--

LOCK TABLES `nota_interna` WRITE;
/*!40000 ALTER TABLE `nota_interna` DISABLE KEYS */;
INSERT INTO `nota_interna` VALUES (1,2,1,'2026-11-19 22:41:11','Revisión remota realizada desde la OLT. Niveles de señal óptica en -24 dBm.'),(2,2,1,'2026-11-19 22:41:11','Se programa visita a domicilio para verificar cable drop.'),(3,3,1,'2026-11-19 22:41:11','Instalación concluida. Potencia recibida: -19 dBm. Prueba de velocidad: 50 Mbps estables.'),(4,4,1,'2026-11-19 22:41:11','Ticket reabierto. Motivo: El cliente volvió a reportar caída de velocidad tras el cierre previo.');
/*!40000 ALTER TABLE `nota_interna` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago` (
  `id_pago` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_comprobante` int(10) unsigned DEFAULT NULL,
  `id_cuenta` int(10) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `medio_pago` enum('transferencia','efectivo','mercado_pago','uala','otro') NOT NULL,
  PRIMARY KEY (`id_pago`),
  UNIQUE KEY `uq_pago_comprobante` (`id_comprobante`),
  KEY `ix_pago_cuenta_fecha` (`id_cuenta`,`fecha`),
  CONSTRAINT `fk_pago_comprobante` FOREIGN KEY (`id_comprobante`) REFERENCES `comprobante` (`id_comprobante`) ON DELETE SET NULL,
  CONSTRAINT `fk_pago_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `cuenta_receptora` (`id_cuenta`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (1,2,1,'2026-07-10',18000.00,'transferencia'),(2,NULL,2,'2026-07-05',42000.00,'transferencia'),(3,NULL,1,'2026-09-10',100.00,'transferencia'),(4,NULL,2,'2026-09-05',200.00,'transferencia');
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan` (
  `id_plan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `velocidad` varchar(50) NOT NULL,
  `precio_vigente` decimal(10,2) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_plan`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan`
--

LOCK TABLES `plan` WRITE;
/*!40000 ALTER TABLE `plan` DISABLE KEYS */;
INSERT INTO `plan` VALUES (1,'Hogar 20','20 Mbps',100.00,'activo'),(2,'Hogar Plus 50','50 Mbps',150.00,'activo'),(3,'Comercio 100','100 Mbps',200.00,'activo');
/*!40000 ALTER TABLE `plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicio`
--

DROP TABLE IF EXISTS `servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicio` (
  `id_servicio` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_plan` int(10) unsigned NOT NULL,
  `id_cliente` int(10) unsigned NOT NULL,
  `calle_instalacion` varchar(100) NOT NULL,
  `numero_instalacion` varchar(10) DEFAULT NULL,
  `localidad_instalacion` varchar(100) NOT NULL,
  `dia_vencimiento` tinyint(3) unsigned NOT NULL,
  `proximo_vencimiento` date DEFAULT NULL,
  `fecha_alta` date NOT NULL,
  `ipv4` varchar(15) DEFAULT NULL,
  `mac` varchar(17) DEFAULT NULL,
  `estado` enum('activo','suspendido','baja') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_servicio`),
  KEY `fk_servicio_plan` (`id_plan`),
  KEY `ix_servicio_cliente_estado` (`id_cliente`,`estado`),
  CONSTRAINT `fk_servicio_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_servicio_plan` FOREIGN KEY (`id_plan`) REFERENCES `plan` (`id_plan`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicio`
--

LOCK TABLES `servicio` WRITE;
/*!40000 ALTER TABLE `servicio` DISABLE KEYS */;
INSERT INTO `servicio` VALUES (1,1,1,'Belgrano','245','Villafañe',10,NULL,'2026-03-19','192.168.10.11','02:00:00:00:00:11','activo'),(2,2,2,'San Martín','118','El Colorado',15,NULL,'2026-03-19','192.168.10.12','02:00:00:00:00:12','activo'),(3,3,3,'Sarmiento','602','Villafañe',5,NULL,'2026-03-19','192.168.10.13','02:00:00:00:00:13','activo'),(4,2,4,'Rivadavia','411','Pirané',12,NULL,'2026-03-19','192.168.10.14','02:00:00:00:00:14','suspendido'),(5,1,5,'Moreno','87','Villa Dos Trece',20,'2026-11-20','2026-03-19','192.168.10.15','02:00:00:00:00:15','activo'),(6,3,6,'Mitre','930','Villafañe',8,NULL,'2026-03-19','192.168.10.16','02:00:00:00:00:16','activo');
/*!40000 ALTER TABLE `servicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket` (
  `id_ticket` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_conversacion` int(10) unsigned DEFAULT NULL,
  `id_servicio` int(10) unsigned NOT NULL,
  `id_usuario_asignado` int(10) unsigned DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'tecnico',
  `descripcion` text NOT NULL,
  `estado` enum('pendiente','asignado','en_proceso','resuelto','cerrado','reabierto') NOT NULL DEFAULT 'pendiente',
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `fk_ticket_conversacion` (`id_conversacion`),
  KEY `fk_ticket_servicio` (`id_servicio`),
  KEY `fk_ticket_usuario` (`id_usuario_asignado`),
  KEY `ix_ticket_estado_fecha` (`estado`,`fecha_creacion`),
  CONSTRAINT `fk_ticket_conversacion` FOREIGN KEY (`id_conversacion`) REFERENCES `conversacion` (`id_conversacion`),
  CONSTRAINT `fk_ticket_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`),
  CONSTRAINT `fk_ticket_usuario` FOREIGN KEY (`id_usuario_asignado`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket`
--

LOCK TABLES `ticket` WRITE;
/*!40000 ALTER TABLE `ticket` DISABLE KEYS */;
INSERT INTO `ticket` VALUES (1,NULL,1,NULL,'2026-11-19 17:41:11','tecnico','Cliente reporta luz LOS parpadeando en rojo y sin acceso a internet.','pendiente',NULL,NULL),(2,NULL,2,1,'2026-11-18 22:41:11','tecnico','Microcortes reiterados en horas pico de la tarde. Pérdida de paquetes del 15%.','en_proceso','2026-11-19 04:41:11',NULL),(3,NULL,3,1,'2026-11-16 22:41:11','instalacion','Cambio de domicilio y reinstalación de equipo ONU en nueva dirección comercial.','resuelto','2026-11-17 22:41:11','2026-11-19 16:41:11'),(4,NULL,1,1,'2026-11-14 22:41:11','reclamo','Reclamo por degradación de velocidad contratada persistente los fines de semana.','reabierto','2026-11-15 22:41:11',NULL);
/*!40000 ALTER TABLE `ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) NOT NULL,
  `credencial` varchar(255) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_nombre` (`nombre_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin@villafanewifi.com','$2y$12$N9jnmoJz2aw9J0x2vIO57.2Y2E7I3/kh5j5gZQHbKatVmDZVBtCG2','activo'),(2,'admin agus','$2y$12$uhr/hPXILXBgAcw6JI4HK.R2CBuWfLd3xPDEWuYpx11fNs6SvmfSC','activo'),(3,'admin uli','$2y$12$jsZfPFR0EUZxxG.fukvd3O4ZYc6/m6BZXEBzLOfQtrTS9fKUfF2K6','activo'),(4,'tecnico_soporte','$2y$12$wsJmI2kkWx8F5ko.FFTOzu4Q/HJSdt0tq1Vit9/PjD2tIeXiFTghy','activo'),(6,'operador_atencion','$2y$12$/hlBEtT4.kPUNbl.JJnBcOHf4O86caXRKP7rqB81txWuOJFNf.zsG','activo');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'villafane_wifi'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-19 19:44:47
