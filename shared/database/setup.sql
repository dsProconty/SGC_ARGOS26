-- MySQL dump 10.13  Distrib 8.4.8, for Win64 (x86_64)
--
-- Host: localhost    Database: sgc_argos
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
-- Table structure for table `cartera`
--

DROP TABLE IF EXISTS `cartera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cartera` (
  `car_id` int NOT NULL AUTO_INCREMENT,
  `car_fecha_inicio` date DEFAULT NULL,
  `car_fecha_fin` date DEFAULT NULL,
  `car_fecha_ingreso` date DEFAULT NULL,
  `car_estado` varchar(50) NOT NULL DEFAULT 'sin_gestion',
  `car_tipo` varchar(5) NOT NULL,
  `cli_valor_pagar` decimal(10,2) DEFAULT '0.00',
  `cli_id` int NOT NULL,
  PRIMARY KEY (`car_id`),
  KEY `fk_cartera_cliente` (`cli_id`),
  CONSTRAINT `fk_cartera_cliente` FOREIGN KEY (`cli_id`) REFERENCES `cliente` (`cli_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartera`
--

LOCK TABLES `cartera` WRITE;
/*!40000 ALTER TABLE `cartera` DISABLE KEYS */;
INSERT INTO `cartera` VALUES (1,'2025-12-01','2025-12-31','2026-01-01','pendiente','30',110.00,1),(2,'2025-12-01','2025-12-31','2026-01-01','pendiente','30',180.00,2),(3,'2025-12-01','2025-12-31','2026-01-01','sin_gestion','30',220.00,3),(4,'2025-12-01','2025-12-31','2026-01-01','cobrada','30',250.00,4),(5,'2025-12-01','2025-12-31','2026-01-01','sin_gestion','30',140.00,5),(6,'2025-12-01','2025-12-31','2026-01-01','compromiso','30',180.00,6),(7,'2025-12-01','2025-12-31','2026-01-01','cobrada','30',130.00,7),(8,'2025-12-01','2025-12-31','2026-01-01','cobrada','30',330.00,8),(9,'2025-12-01','2025-12-31','2026-01-01','cobrada','30',180.00,9),(10,'2025-12-01','2025-12-31','2026-01-01','pendiente','30',240.00,10),(11,'2025-11-01','2025-11-30','2026-01-01','pendiente','60',180.00,1),(12,'2025-11-01','2025-11-30','2026-01-01','sin_gestion','60',240.00,2),(13,'2025-11-01','2025-11-30','2026-01-01','cobrada','60',300.00,3),(14,'2025-11-01','2025-11-30','2026-01-01','sin_gestion','60',370.00,4),(15,'2025-11-01','2025-11-30','2026-01-01','compromiso','60',190.00,5),(16,'2025-10-01','2025-10-31','2026-01-01','sin_gestion','90',300.00,6),(17,'2025-10-01','2025-10-31','2026-01-01','pendiente','90',180.00,7),(18,'2025-10-01','2025-10-31','2026-01-01','sin_gestion','90',450.00,8),(19,'2025-10-01','2025-10-31','2026-01-01','cobrada','90',250.00,9),(20,'2025-10-01','2025-10-31','2026-01-01','sin_gestion','90',330.00,10),(21,NULL,'2025-09-30','2026-01-01','sin_gestion','91',450.00,3),(22,NULL,'2025-09-30','2026-01-01','pendiente','91',450.00,4),(23,NULL,'2025-09-30','2026-01-01','sin_gestion','91',590.00,8);
/*!40000 ALTER TABLE `cartera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `cli_id` int NOT NULL AUTO_INCREMENT,
  `cli_descripcion` varchar(255) NOT NULL,
  `cli_ciudad` varchar(100) DEFAULT NULL,
  `cli_contacto` varchar(150) DEFAULT NULL,
  `cli_email` varchar(150) DEFAULT NULL,
  `cli_email2` varchar(150) DEFAULT NULL,
  `cli_telefono` varchar(50) DEFAULT NULL,
  `cli_telefono2` varchar(50) DEFAULT NULL,
  `cli_dia_corte` varchar(5) DEFAULT '0',
  `cli_tipo_beneficio` varchar(15) DEFAULT NULL COMMENT 'Porcentaje | Cupo',
  `cli_valor_beneficio` decimal(10,2) DEFAULT NULL COMMENT 'Monto del cupo si tipo=Cupo',
  `cli_tipo_cartera` varchar(5) DEFAULT NULL COMMENT '30 | 60 | 90 | 90+',
  PRIMARY KEY (`cli_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'CONSTRUCTORA ANDINA S.A.','Quito','Roberto Andrade','randrade@constructoraandina.com','contabilidad@constructoraandina.com','022345678','0991122334','15',NULL,NULL,NULL),(2,'TRANSPORTE RAPIDO CIA. LTDA.','Quito','Fernanda Vasquez','fvasquez@transporterapido.com','pagos@transporterapido.com','022456789','0992233445','1',NULL,NULL,NULL),(3,'DISTRIBUIDORA EL SOL S.A.','Guayaquil','Miguel Torres','mtorres@distribuidoraelsol.com','finanzas@distribuidoraelsol.com','042567890','0993344556','20',NULL,NULL,NULL),(4,'EMPRESA MINERA PACIFICO S.A.','Cuenca','Patricia Rios','prios@minerapacifico.com','cuentas@minerapacifico.com','072678901','0994455667','10',NULL,NULL,NULL),(5,'LOGISTICA NORTE CIA. LTDA.','Ibarra','Andres Morales','amorales@logisticanorte.com','admin@logisticanorte.com','062789012','0995566778','5',NULL,NULL,NULL),(6,'AGROPECUARIA LA ESPERANZA S.A.','Latacunga','Carmen Salinas','csalinas@agropecuaria.com','pagos@agropecuaria.com','032890123','0996677889','25',NULL,NULL,NULL),(7,'IMPORTADORA TEXTIL GLOBAL S.A.','Quito','Diego Paredes','dparedes@textilglobal.com','contable@textilglobal.com','022901234','0997788990','15',NULL,NULL,NULL),(8,'SERVICIOS INDUSTRIALES S.A.','Ambato','Lucia Herrera','lherrera@serviciosindustriales.com','finanzas@serviciosindustriales.com','032012345','0998899001','1',NULL,NULL,NULL),(9,'CONSTRUCTORA DEL AUSTRO CIA. LTDA.','Cuenca','Esteban Cardenas','ecardenas@constructoraaustro.com','pagos@constructoraaustro.com','072123456','0999900112','20',NULL,NULL,NULL),(10,'COMERCIAL LOS ANDES S.A.','Riobamba','Veronica Castillo','vcastillo@comercialosandes.com','admin@comercialosandes.com','032234567','0990011223','10',NULL,NULL,NULL),(11,'','','','',NULL,'',NULL,'',NULL,NULL,NULL),(13,'Empresa Demo S.A.','Guayaquil','Contacto Demo','demo@empresa.com',NULL,NULL,NULL,'15','Cupo',500.00,'30');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codigo_gift_card`
--

DROP TABLE IF EXISTS `codigo_gift_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `codigo_gift_card` (
  `cgc_id` int NOT NULL AUTO_INCREMENT,
  `lgc_id` int NOT NULL COMMENT 'FK lote_gift_card',
  `cgc_codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'C├│digo alfanum├®rico ├║nico',
  `cgc_cupo_inicial` decimal(10,2) NOT NULL COMMENT 'Saldo inicial',
  `cgc_cupo_disponible` decimal(10,2) NOT NULL COMMENT 'Saldo disponible actual',
  `cgc_estado` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo' COMMENT 'activo | consumido | vencido | anulado',
  `cgc_fecha_activacion` datetime NOT NULL COMMENT 'Activaci├│n autom├ítica al generar',
  `cgc_fecha_uso` datetime DEFAULT NULL COMMENT 'Fecha de consumo (NULL si no usado)',
  `cgc_fecha_caducidad` date DEFAULT NULL COMMENT 'Fecha de caducidad del código',
  PRIMARY KEY (`cgc_id`),
  UNIQUE KEY `uk_cgc_codigo` (`cgc_codigo`),
  KEY `idx_cgc_lgc_id` (`lgc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codigo_gift_card`
--

LOCK TABLES `codigo_gift_card` WRITE;
/*!40000 ALTER TABLE `codigo_gift_card` DISABLE KEYS */;
INSERT INTO `codigo_gift_card` VALUES (1,1,'9904E36D4053',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(2,1,'AB6325D03FC7',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(3,1,'2F20D1152DBD',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(4,1,'849F2997CC4A',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(5,1,'ACF155204DA7',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(6,1,'C1EC787EAFBE',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(7,1,'8424FC9F500E',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(8,1,'B1DF2C28430C',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(9,1,'B55D97434590',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(10,1,'FA619BF79FC2',50.00,50.00,'activo','2026-03-20 22:14:20',NULL,NULL),(11,2,'BE0C71C1612A',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(12,2,'68B06AF80269',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(13,2,'CA59F63D520D',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(14,2,'78717A8ABB89',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(15,2,'C803CC3DBC51',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(16,2,'EF43F9945B1D',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(17,2,'135CD2039E73',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(18,2,'BB36238F4D83',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(19,2,'727F653FC0C3',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(20,2,'F349AAA63B65',50.00,50.00,'activo','2026-03-21 02:48:01',NULL,'2026-03-31'),(21,3,'D5DA2D990FB0',50.00,0.00,'consumido','2026-03-23 15:21:46','2026-03-24 15:48:19','2026-03-26'),(22,3,'3EBD70E1474C',50.00,30.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(23,3,'C088565D9310',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(24,3,'DE625889CD3F',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(25,3,'34B850296235',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(26,3,'14600FF5D2EA',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(27,3,'C7ADCAE690C0',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(28,3,'A58F0145C202',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(29,3,'9EF4AEA25EC6',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(30,3,'A62520E244D1',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(31,3,'EEB991F9CB27',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(32,3,'30CAEB40B7EC',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(33,3,'281B0D76CE27',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(34,3,'71F07E6D167A',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(35,3,'674CF935B89E',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(36,3,'FCB3BBF69557',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(37,3,'0E845960620D',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(38,3,'F1C94396B2A6',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(39,3,'5F2DE2FB231E',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26'),(40,3,'28232124EDC3',50.00,50.00,'activo','2026-03-23 15:21:46',NULL,'2026-03-26');
/*!40000 ALTER TABLE `codigo_gift_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compromiso`
--

DROP TABLE IF EXISTS `compromiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compromiso` (
  `com_id` int NOT NULL,
  `com_monto` decimal(10,2) DEFAULT '0.00',
  `com_fecha` date DEFAULT NULL,
  `com_estado` varchar(50) DEFAULT 'pendiente',
  PRIMARY KEY (`com_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compromiso`
--

LOCK TABLES `compromiso` WRITE;
/*!40000 ALTER TABLE `compromiso` DISABLE KEYS */;
INSERT INTO `compromiso` VALUES (1,80.00,'2026-03-15','pendiente'),(2,140.00,'2026-03-10','pendiente'),(3,220.00,'2026-02-28','pendiente');
/*!40000 ALTER TABLE `compromiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumo`
--

DROP TABLE IF EXISTS `consumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consumo` (
  `con_id` int NOT NULL AUTO_INCREMENT,
  `con_fecha` date DEFAULT NULL,
  `con_hora` time DEFAULT NULL,
  `con_numero_tarjeta` varchar(50) DEFAULT NULL,
  `con_valor_neto` decimal(10,2) DEFAULT '0.00',
  `con_iva` decimal(10,2) DEFAULT '0.00',
  `con_valor_total` decimal(10,2) DEFAULT '0.00',
  `con_autorizacion` varchar(100) DEFAULT NULL,
  `con_estado` varchar(50) DEFAULT 'pendiente',
  `con_descripcion` varchar(200) DEFAULT NULL,
  `id_transaccion` varchar(100) DEFAULT NULL,
  `loc_id` int DEFAULT NULL,
  `per_id` int DEFAULT NULL,
  `id_user` int DEFAULT NULL COMMENT 'FK usuario - cajero que registr├│ la venta',
  `con_monto_convenio` decimal(10,2) DEFAULT NULL COMMENT 'Monto cargado al convenio',
  `con_monto_externo` decimal(10,2) DEFAULT NULL COMMENT 'Monto pago externo (pago mixto)',
  `con_otp_validado` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 si OTP fue validado',
  `con_voucher_impreso` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 si voucher fue impreso',
  `con_giftcard_codigo` varchar(50) DEFAULT NULL COMMENT 'Código de gift card usado',
  `con_monto_giftcard` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Monto pagado con gift card',
  PRIMARY KEY (`con_id`),
  KEY `fk_consumo_local` (`loc_id`),
  KEY `fk_consumo_personal` (`per_id`),
  CONSTRAINT `fk_consumo_local` FOREIGN KEY (`loc_id`) REFERENCES `local` (`loc_id`),
  CONSTRAINT `fk_consumo_personal` FOREIGN KEY (`per_id`) REFERENCES `personal` (`per_id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumo`
--

LOCK TABLES `consumo` WRITE;
/*!40000 ALTER TABLE `consumo` DISABLE KEYS */;
INSERT INTO `consumo` VALUES (122,'2025-09-05','08:30:00','4543****3456',133.93,16.07,150.00,'AUT-S01','pendiente',NULL,'TXN-S01',1,5,NULL,NULL,NULL,0,0,NULL,0.00),(123,'2025-09-10','10:00:00','4543****3456',107.14,12.86,120.00,'AUT-S02','pendiente',NULL,'TXN-S02',2,5,NULL,NULL,NULL,0,0,NULL,0.00),(124,'2025-09-15','09:00:00','4543****7890',89.29,10.71,100.00,'AUT-S03','pendiente',NULL,'TXN-S03',3,6,NULL,NULL,NULL,0,0,NULL,0.00),(125,'2025-09-20','14:00:00','4543****7890',71.43,8.57,80.00,'AUT-S04','pendiente',NULL,'TXN-S04',1,6,NULL,NULL,NULL,0,0,NULL,0.00),(126,'2025-09-06','08:00:00','4554****4567',178.57,21.43,200.00,'AUT-S05','pendiente',NULL,'TXN-S05',4,7,NULL,NULL,NULL,0,0,NULL,0.00),(127,'2025-09-12','12:00:00','4554****4567',133.93,16.07,150.00,'AUT-S06','pendiente',NULL,'TXN-S06',5,7,NULL,NULL,NULL,0,0,NULL,0.00),(128,'2025-09-18','15:00:00','4554****8901',89.29,10.71,100.00,'AUT-S07','pendiente',NULL,'TXN-S07',4,8,NULL,NULL,NULL,0,0,NULL,0.00),(129,'2025-09-08','07:30:00','4598****8901',214.29,25.71,240.00,'AUT-S08','pendiente',NULL,'TXN-S08',5,15,NULL,NULL,NULL,0,0,NULL,0.00),(130,'2025-09-22','13:00:00','4598****8901',178.57,21.43,200.00,'AUT-S09','pendiente',NULL,'TXN-S09',6,15,NULL,NULL,NULL,0,0,NULL,0.00),(131,'2025-09-25','16:00:00','4598****2345',133.93,16.07,150.00,'AUT-S10','pendiente',NULL,'TXN-S10',5,16,NULL,NULL,NULL,0,0,NULL,0.00),(132,'2025-10-04','08:15:00','4576****6789',107.14,12.86,120.00,'AUT-O01','pendiente',NULL,'TXN-O01',1,11,NULL,NULL,NULL,0,0,NULL,0.00),(133,'2025-10-12','14:30:00','4576****6789',89.29,10.71,100.00,'AUT-O02','pendiente',NULL,'TXN-O02',2,11,NULL,NULL,NULL,0,0,NULL,0.00),(134,'2025-10-20','09:45:00','4576****0123',71.43,8.57,80.00,'AUT-O03','pendiente',NULL,'TXN-O03',3,12,NULL,NULL,NULL,0,0,NULL,0.00),(135,'2025-10-06','11:00:00','4587****7890',62.50,7.50,70.00,'AUT-O04','pendiente',NULL,'TXN-O04',1,13,NULL,NULL,NULL,0,0,NULL,0.00),(136,'2025-10-14','15:15:00','4587****7890',53.57,6.43,60.00,'AUT-O05','pendiente',NULL,'TXN-O05',2,13,NULL,NULL,NULL,0,0,NULL,0.00),(137,'2025-10-22','08:30:00','4587****1234',44.64,5.36,50.00,'AUT-O06','pendiente',NULL,'TXN-O06',3,14,NULL,NULL,NULL,0,0,NULL,0.00),(138,'2025-10-05','07:30:00','4598****8901',160.71,19.29,180.00,'AUT-O07','pendiente',NULL,'TXN-O07',4,15,NULL,NULL,NULL,0,0,NULL,0.00),(139,'2025-10-15','13:00:00','4598****2345',133.93,16.07,150.00,'AUT-O08','pendiente',NULL,'TXN-O08',5,16,NULL,NULL,NULL,0,0,NULL,0.00),(140,'2025-10-25','10:00:00','4598****2345',107.14,12.86,120.00,'AUT-O09','pendiente',NULL,'TXN-O09',6,16,NULL,NULL,NULL,0,0,NULL,0.00),(141,'2025-10-07','09:00:00','4509****9012',89.29,10.71,100.00,'AUT-O10','pendiente',NULL,'TXN-O10',4,17,NULL,NULL,NULL,0,0,NULL,0.00),(142,'2025-10-16','14:45:00','4509****9012',71.43,8.57,80.00,'AUT-O11','pendiente',NULL,'TXN-O11',5,17,NULL,NULL,NULL,0,0,NULL,0.00),(143,'2025-10-24','11:30:00','4509****3456',62.50,7.50,70.00,'AUT-O12','pendiente',NULL,'TXN-O12',6,18,NULL,NULL,NULL,0,0,NULL,0.00),(144,'2025-10-08','08:00:00','4510****0123',116.07,13.93,130.00,'AUT-O13','pendiente',NULL,'TXN-O13',4,19,NULL,NULL,NULL,0,0,NULL,0.00),(145,'2025-10-18','12:15:00','4510****0123',98.21,11.79,110.00,'AUT-O14','pendiente',NULL,'TXN-O14',5,19,NULL,NULL,NULL,0,0,NULL,0.00),(146,'2025-10-28','16:00:00','4510****4567',80.36,9.64,90.00,'AUT-O15','pendiente',NULL,'TXN-O15',6,20,NULL,NULL,NULL,0,0,NULL,0.00),(147,'2025-11-04','08:30:00','4521****1234',53.57,6.43,60.00,'AUT-N01','pendiente',NULL,'TXN-N01',1,1,NULL,NULL,NULL,0,0,NULL,0.00),(148,'2025-11-12','10:15:00','4521****1234',62.50,7.50,70.00,'AUT-N02','pendiente',NULL,'TXN-N02',2,1,NULL,NULL,NULL,0,0,NULL,0.00),(149,'2025-11-20','14:45:00','4521****5678',44.64,5.36,50.00,'AUT-N03','pendiente',NULL,'TXN-N03',3,2,NULL,NULL,NULL,0,0,NULL,0.00),(150,'2025-11-06','07:30:00','4532****2345',89.29,10.71,100.00,'AUT-N04','pendiente',NULL,'TXN-N04',1,3,NULL,NULL,NULL,0,0,NULL,0.00),(151,'2025-11-14','11:00:00','4532****2345',71.43,8.57,80.00,'AUT-N05','pendiente',NULL,'TXN-N05',2,3,NULL,NULL,NULL,0,0,NULL,0.00),(152,'2025-11-22','15:30:00','4532****6789',53.57,6.43,60.00,'AUT-N06','pendiente',NULL,'TXN-N06',3,4,NULL,NULL,NULL,0,0,NULL,0.00),(153,'2025-11-05','09:30:00','4543****3456',107.14,12.86,120.00,'AUT-N07','pendiente',NULL,'TXN-N07',1,5,NULL,NULL,NULL,0,0,NULL,0.00),(154,'2025-11-15','14:00:00','4543****3456',89.29,10.71,100.00,'AUT-N08','pendiente',NULL,'TXN-N08',2,5,NULL,NULL,NULL,0,0,NULL,0.00),(155,'2025-11-25','10:30:00','4543****7890',71.43,8.57,80.00,'AUT-N09','pendiente',NULL,'TXN-N09',3,6,NULL,NULL,NULL,0,0,NULL,0.00),(156,'2025-11-08','08:00:00','4554****4567',133.93,16.07,150.00,'AUT-N10','pendiente',NULL,'TXN-N10',1,7,NULL,NULL,NULL,0,0,NULL,0.00),(157,'2025-11-18','12:30:00','4554****4567',107.14,12.86,120.00,'AUT-N11','pendiente',NULL,'TXN-N11',2,7,NULL,NULL,NULL,0,0,NULL,0.00),(158,'2025-11-28','15:00:00','4554****8901',89.29,10.71,100.00,'AUT-N12','pendiente',NULL,'TXN-N12',3,8,NULL,NULL,NULL,0,0,NULL,0.00),(159,'2025-11-10','07:45:00','4565****5678',71.43,8.57,80.00,'AUT-N13','pendiente',NULL,'TXN-N13',1,9,NULL,NULL,NULL,0,0,NULL,0.00),(160,'2025-11-20','13:30:00','4565****5678',53.57,6.43,60.00,'AUT-N14','pendiente',NULL,'TXN-N14',2,9,NULL,NULL,NULL,0,0,NULL,0.00),(161,'2025-11-28','10:00:00','4565****9012',44.64,5.36,50.00,'AUT-N15','pendiente',NULL,'TXN-N15',3,10,NULL,NULL,NULL,0,0,NULL,0.00),(162,'2025-12-03','08:30:00','4521****1234',53.57,6.43,60.00,'AUT-D01','pendiente',NULL,'TXN-D01',1,1,NULL,NULL,NULL,0,0,NULL,0.00),(163,'2025-12-10','10:15:00','4521****5678',44.64,5.36,50.00,'AUT-D02','pendiente',NULL,'TXN-D02',2,2,NULL,NULL,NULL,0,0,NULL,0.00),(164,'2025-12-05','07:30:00','4532****2345',89.29,10.71,100.00,'AUT-D03','pendiente',NULL,'TXN-D03',3,3,NULL,NULL,NULL,0,0,NULL,0.00),(165,'2025-12-15','11:00:00','4532****6789',71.43,8.57,80.00,'AUT-D04','pendiente',NULL,'TXN-D04',1,4,NULL,NULL,NULL,0,0,NULL,0.00),(166,'2025-12-04','09:30:00','4543****3456',107.14,12.86,120.00,'AUT-D05','pendiente',NULL,'TXN-D05',2,5,NULL,NULL,NULL,0,0,NULL,0.00),(167,'2025-12-18','14:00:00','4543****7890',89.29,10.71,100.00,'AUT-D06','pendiente',NULL,'TXN-D06',3,6,NULL,NULL,NULL,0,0,NULL,0.00),(168,'2025-12-06','08:00:00','4554****4567',133.93,16.07,150.00,'AUT-D07','pendiente',NULL,'TXN-D07',1,7,NULL,NULL,NULL,0,0,NULL,0.00),(169,'2025-12-16','12:30:00','4554****8901',89.29,10.71,100.00,'AUT-D08','pendiente',NULL,'TXN-D08',2,8,NULL,NULL,NULL,0,0,NULL,0.00),(170,'2025-12-07','07:45:00','4565****5678',71.43,8.57,80.00,'AUT-D09','pendiente',NULL,'TXN-D09',3,9,NULL,NULL,NULL,0,0,NULL,0.00),(171,'2025-12-20','13:30:00','4565****9012',53.57,6.43,60.00,'AUT-D10','pendiente',NULL,'TXN-D10',1,10,NULL,NULL,NULL,0,0,NULL,0.00),(172,'2025-12-08','11:00:00','4576****6789',89.29,10.71,100.00,'AUT-D11','pendiente',NULL,'TXN-D11',2,11,NULL,NULL,NULL,0,0,NULL,0.00),(173,'2025-12-18','15:00:00','4576****0123',71.43,8.57,80.00,'AUT-D12','pendiente',NULL,'TXN-D12',3,12,NULL,NULL,NULL,0,0,NULL,0.00),(174,'2025-12-09','08:30:00','4587****7890',62.50,7.50,70.00,'AUT-D13','pendiente',NULL,'TXN-D13',1,13,NULL,NULL,NULL,0,0,NULL,0.00),(175,'2025-12-19','10:00:00','4587****1234',53.57,6.43,60.00,'AUT-D14','pendiente',NULL,'TXN-D14',2,14,NULL,NULL,NULL,0,0,NULL,0.00),(176,'2025-12-10','07:30:00','4598****8901',160.71,19.29,180.00,'AUT-D15','pendiente',NULL,'TXN-D15',3,15,NULL,NULL,NULL,0,0,NULL,0.00),(177,'2025-12-22','13:00:00','4598****2345',133.93,16.07,150.00,'AUT-D16','pendiente',NULL,'TXN-D16',1,16,NULL,NULL,NULL,0,0,NULL,0.00),(178,'2025-12-11','09:00:00','4509****9012',89.29,10.71,100.00,'AUT-D17','pendiente',NULL,'TXN-D17',2,17,NULL,NULL,NULL,0,0,NULL,0.00),(179,'2025-12-21','14:45:00','4509****3456',71.43,8.57,80.00,'AUT-D18','pendiente',NULL,'TXN-D18',3,18,NULL,NULL,NULL,0,0,NULL,0.00),(180,'2025-12-12','08:00:00','4510****0123',116.07,13.93,130.00,'AUT-D19','pendiente',NULL,'TXN-D19',1,19,NULL,NULL,NULL,0,0,NULL,0.00),(181,'2025-12-23','11:00:00','4510****4567',98.21,11.79,110.00,'AUT-D20','pendiente',NULL,'TXN-D20',2,20,NULL,NULL,NULL,0,0,NULL,0.00),(182,'2026-03-01','08:30:00','4521****1234',53.57,6.43,60.00,'AUT-M01','pendiente',NULL,'TXN-M01',1,1,NULL,NULL,NULL,0,0,NULL,0.00),(183,'2026-03-03','10:00:00','4521****5678',44.64,5.36,50.00,'AUT-M02','pendiente',NULL,'TXN-M02',2,2,NULL,NULL,NULL,0,0,NULL,0.00),(184,'2026-03-01','09:15:00','4532****2345',89.29,10.71,100.00,'AUT-M03','pendiente',NULL,'TXN-M03',3,3,NULL,NULL,NULL,0,0,NULL,0.00),(185,'2026-03-03','14:30:00','4532****6789',71.43,8.57,80.00,'AUT-M04','pendiente',NULL,'TXN-M04',1,4,NULL,NULL,NULL,0,0,NULL,0.00),(186,'2026-03-02','08:00:00','4543****3456',133.93,16.07,150.00,'AUT-M05','pendiente',NULL,'TXN-M05',2,5,NULL,NULL,NULL,0,0,NULL,0.00),(187,'2026-03-04','11:00:00','4543****7890',107.14,12.86,120.00,'AUT-M06','pendiente',NULL,'TXN-M06',3,6,NULL,NULL,NULL,0,0,NULL,0.00),(188,'2026-03-01','07:45:00','4554****4567',178.57,21.43,200.00,'AUT-M07','pendiente',NULL,'TXN-M07',4,7,NULL,NULL,NULL,0,0,NULL,0.00),(189,'2026-03-03','12:00:00','4554****8901',133.93,16.07,150.00,'AUT-M08','pendiente',NULL,'TXN-M08',5,8,NULL,NULL,NULL,0,0,NULL,0.00),(190,'2026-03-02','09:30:00','4565****5678',71.43,8.57,80.00,'AUT-M09','pendiente',NULL,'TXN-M09',4,9,NULL,NULL,NULL,0,0,NULL,0.00),(191,'2026-03-04','15:00:00','4565****9012',53.57,6.43,60.00,'AUT-M10','pendiente',NULL,'TXN-M10',6,10,NULL,NULL,NULL,0,0,NULL,0.00),(192,'2026-03-02','10:00:00','4576****6789',89.29,10.71,100.00,'AUT-M11','pendiente',NULL,'TXN-M11',5,11,NULL,NULL,NULL,0,0,NULL,0.00),(193,'2026-03-04','13:00:00','4576****0123',71.43,8.57,80.00,'AUT-M12','pendiente',NULL,'TXN-M12',6,12,NULL,NULL,NULL,0,0,NULL,0.00),(194,'2026-03-03','08:30:00','4587****7890',62.50,7.50,70.00,'AUT-M13','pendiente',NULL,'TXN-M13',1,13,NULL,NULL,NULL,0,0,NULL,0.00),(195,'2026-03-05','10:00:00','4587****1234',53.57,6.43,60.00,'AUT-M14','pendiente',NULL,'TXN-M14',2,14,NULL,NULL,NULL,0,0,NULL,0.00),(196,'2026-03-01','07:30:00','4598****8901',214.29,25.71,240.00,'AUT-M15','pendiente',NULL,'TXN-M15',7,15,NULL,NULL,NULL,0,0,NULL,0.00),(197,'2026-03-03','13:00:00','4598****2345',178.57,21.43,200.00,'AUT-M16','pendiente',NULL,'TXN-M16',8,16,NULL,NULL,NULL,0,0,NULL,0.00),(198,'2026-03-02','09:00:00','4509****9012',89.29,10.71,100.00,'AUT-M17','pendiente',NULL,'TXN-M17',7,17,NULL,NULL,NULL,0,0,NULL,0.00),(199,'2026-03-04','14:00:00','4509****3456',71.43,8.57,80.00,'AUT-M18','pendiente',NULL,'TXN-M18',8,18,NULL,NULL,NULL,0,0,NULL,0.00),(200,'2026-03-01','08:00:00','4510****0123',116.07,13.93,130.00,'AUT-M19','pendiente',NULL,'TXN-M19',3,19,NULL,NULL,NULL,0,0,NULL,0.00),(201,'2026-03-05','11:00:00','4510****4567',98.21,11.79,110.00,'AUT-M20','pendiente',NULL,'TXN-M20',2,20,NULL,NULL,NULL,0,0,NULL,0.00),(202,'0000-00-00','00:00:00','',0.00,0.00,0.00,'','pendiente',NULL,'',13,21,NULL,NULL,NULL,0,0,NULL,0.00),(203,'2026-03-17','22:57:18',NULL,100.00,0.00,100.00,NULL,'pendiente',NULL,NULL,NULL,22,1,100.00,0.00,0,1,NULL,0.00),(204,'2026-03-18','21:06:13',NULL,400.00,0.00,601.19,NULL,'pendiente',NULL,NULL,NULL,22,1,400.00,201.19,0,1,NULL,0.00),(205,'2026-03-24','15:15:01',NULL,10.00,0.00,15.00,NULL,'pendiente',NULL,NULL,NULL,22,1,10.00,5.00,0,1,NULL,0.00),(206,'2026-03-24','15:15:35',NULL,10.00,0.00,10.00,NULL,'pendiente',NULL,NULL,NULL,22,1,10.00,0.00,0,1,NULL,0.00),(207,'2026-03-24','15:48:19',NULL,50.00,0.00,50.00,NULL,'pendiente','dd',NULL,NULL,NULL,1,0.00,0.00,0,1,'D5DA2D990FB0',50.00),(208,'2026-03-24','20:30:15',NULL,20.00,0.00,20.00,NULL,'pendiente','adasd',NULL,NULL,NULL,1,0.00,0.00,0,1,'3EBD70E1474C',20.00);
/*!40000 ALTER TABLE `consumo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_cuenta`
--

DROP TABLE IF EXISTS `estado_cuenta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_cuenta` (
  `ec_id` int NOT NULL AUTO_INCREMENT,
  `cli_id` int NOT NULL COMMENT 'FK cliente',
  `ec_periodo_inicio` date NOT NULL COMMENT 'Inicio del per├¡odo facturado',
  `ec_periodo_fin` date NOT NULL COMMENT 'Fecha de corte',
  `ec_monto_total` decimal(10,2) NOT NULL COMMENT 'Total consumos del per├¡odo',
  `ec_archivo_pdf` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ruta del PDF generado',
  `ec_fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ec_fecha_envio` datetime DEFAULT NULL COMMENT 'NULL si a├║n no enviado',
  `ec_estado_envio` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente | enviado | error',
  `ec_reintentos` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ec_id`),
  KEY `idx_ec_cli_id` (`cli_id`),
  KEY `idx_ec_periodo` (`ec_periodo_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_cuenta`
--

LOCK TABLES `estado_cuenta` WRITE;
/*!40000 ALTER TABLE `estado_cuenta` DISABLE KEYS */;
INSERT INTO `estado_cuenta` VALUES (1,13,'2026-03-01','2026-03-31',701.19,NULL,'2026-03-23 10:28:45',NULL,'pendiente',0);
/*!40000 ALTER TABLE `estado_cuenta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gestion`
--

DROP TABLE IF EXISTS `gestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gestion` (
  `ges_id` int NOT NULL AUTO_INCREMENT,
  `ges_fecha` datetime DEFAULT NULL,
  `ges_tipo_gestion` varchar(50) DEFAULT NULL,
  `ges_tipo_contacto` varchar(50) DEFAULT NULL,
  `ges_respuesta` varchar(50) DEFAULT NULL,
  `ges_contacto` varchar(100) DEFAULT NULL,
  `ges_email_contacto` varchar(150) DEFAULT NULL,
  `ges_observacion` text,
  `us_id` int DEFAULT NULL,
  `car_id` int DEFAULT NULL,
  `pag_id` int DEFAULT NULL,
  `com_id` int DEFAULT NULL,
  PRIMARY KEY (`ges_id`),
  KEY `fk_gestion_usuario` (`us_id`),
  KEY `fk_gestion_cartera` (`car_id`),
  KEY `fk_gestion_pago` (`pag_id`),
  KEY `fk_gestion_compromiso` (`com_id`),
  CONSTRAINT `fk_gestion_cartera` FOREIGN KEY (`car_id`) REFERENCES `cartera` (`car_id`),
  CONSTRAINT `fk_gestion_compromiso` FOREIGN KEY (`com_id`) REFERENCES `compromiso` (`com_id`),
  CONSTRAINT `fk_gestion_pago` FOREIGN KEY (`pag_id`) REFERENCES `pago` (`pag_id`),
  CONSTRAINT `fk_gestion_usuario` FOREIGN KEY (`us_id`) REFERENCES `usuario` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gestion`
--

LOCK TABLES `gestion` WRITE;
/*!40000 ALTER TABLE `gestion` DISABLE KEYS */;
INSERT INTO `gestion` VALUES (1,'2025-12-22 10:30:00','telefonica','contactado','pago','0994455667',NULL,'Cliente realizo pago total sin inconvenientes.',1,4,1,NULL),(2,'2026-01-05 09:00:00','telefonica','no_contactado','no_contactado','0992233445',NULL,'No contesto, se dejo mensaje de voz.',2,2,NULL,NULL),(3,'2026-01-08 11:30:00','telefonica','contactado','pendiente','0992233445',NULL,'Indica que realizara pago la proxima semana.',3,2,NULL,NULL),(4,'2026-01-10 14:00:00','telefonica','contactado','compromiso','0996677889',NULL,'Cliente se compromete a pagar el 15 de marzo.',4,6,NULL,1),(5,'2026-01-12 10:00:00','email','contactado','notificacion',NULL,'lherrera@serviciosindustriales.com','Se envio notificacion formal de deuda pendiente.',1,8,NULL,NULL),(6,'2025-12-18 09:30:00','telefonica','contactado','pago','0993344556',NULL,'Pago recibido mediante transferencia.',2,13,2,NULL),(7,'2025-11-12 15:00:00','telefonica','contactado','pago','0999900112',NULL,'Cliente cancelo deuda completa.',3,19,4,NULL),(8,'2026-01-15 11:00:00','telefonica','contactado','compromiso','0995566778',NULL,'Acuerdo de pago para el 10 de marzo.',4,15,NULL,2),(9,'2026-01-20 09:00:00','email','contactado','notificacion',NULL,'prios@minerapacifico.com','Notificacion de deuda con mas de 90 dias de mora.',1,22,NULL,NULL),(10,'2026-02-01 10:30:00','telefonica','contactado','compromiso','0994455667',NULL,'Cliente acepta compromiso de pago para fin de febrero.',2,22,NULL,3),(11,'2025-11-30 16:00:00','telefonica','contactado','pago','0993344556',NULL,'Abono parcial recibido.',3,13,3,NULL),(12,'2026-03-05 13:17:39','telefonica','contactado','notificacion','099066663',NULL,'Prueba consumo',1,9,NULL,NULL),(13,'2026-03-05 13:21:14','telefonica','contactado','pago','1929211',NULL,'Prubaa paog',1,9,5,NULL),(14,'2026-03-05 14:09:35','telefonica','contactado','pago','561615',NULL,'abono 120',1,8,6,NULL),(15,'2026-03-05 14:20:40','telefonica','contactado','pago','1505156',NULL,'Prueba',1,7,7,NULL),(16,'2026-03-09 14:56:53','telefonica','contactado','no_contactado','0990332225',NULL,'No contactada',1,1,NULL,NULL);
/*!40000 ALTER TABLE `gestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `local`
--

DROP TABLE IF EXISTS `local`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local` (
  `loc_id` int NOT NULL,
  `loc_direccion` varchar(255) DEFAULT NULL,
  `mar_id` int NOT NULL,
  `loc_provincia` varchar(10) DEFAULT NULL COMMENT 'sierra | costa | oriente',
  `loc_activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=activo, 0=inactivo',
  PRIMARY KEY (`loc_id`),
  KEY `fk_local_marca` (`mar_id`),
  CONSTRAINT `fk_local_marca` FOREIGN KEY (`mar_id`) REFERENCES `marca` (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `local`
--

LOCK TABLES `local` WRITE;
/*!40000 ALTER TABLE `local` DISABLE KEYS */;
INSERT INTO `local` VALUES (1,'Av. Amazonas y Naciones Unidas',1,NULL,1),(2,'Av. 6 de Diciembre y Colon',1,NULL,1),(3,'Av. Republica y Eloy Alfaro',1,NULL,1),(4,'Av. Shyris y Portugal',2,NULL,1),(5,'Av. de la Prensa y Av. del Maestro',2,NULL,1),(6,'Calle Inaquito y Japon',2,NULL,1),(7,'Av. Occidental y Mariana de Jesus',3,NULL,1),(8,'Panamericana Norte Km. 4',3,NULL,1),(9,'Av. Simon Bolivar y Granados',4,NULL,1),(10,'Av. Interoceanica Km. 12',4,NULL,1),(11,'Av. Gonzalez Suarez y 12 de Octubre',5,NULL,1),(12,'Av. de los Granados y Eloy Alfaro',5,NULL,1),(13,'',6,NULL,1);
/*!40000 ALTER TABLE `local` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lote_gift_card`
--

DROP TABLE IF EXISTS `lote_gift_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lote_gift_card` (
  `lgc_id` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL COMMENT 'FK usuario - cliente GiftCard',
  `lgc_cantidad` int NOT NULL COMMENT 'Cantidad de c├│digos en el lote',
  `lgc_cupo_codigo` decimal(10,2) NOT NULL COMMENT 'Cupo por c├│digo',
  `lgc_fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha generaci├│n',
  `lgc_periodo_facturacion` date NOT NULL COMMENT 'Per├¡odo de corte para facturaci├│n',
  PRIMARY KEY (`lgc_id`),
  KEY `idx_lgc_id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lote_gift_card`
--

LOCK TABLES `lote_gift_card` WRITE;
/*!40000 ALTER TABLE `lote_gift_card` DISABLE KEYS */;
INSERT INTO `lote_gift_card` VALUES (1,1,10,50.00,'2026-03-20 17:14:20','2026-03-29'),(2,1,10,50.00,'2026-03-20 21:48:01','2026-03-21'),(3,1,20,50.00,'2026-03-23 10:21:46','2026-03-30');
/*!40000 ALTER TABLE `lote_gift_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marca`
--

DROP TABLE IF EXISTS `marca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marca` (
  `mar_id` int NOT NULL,
  `mar_descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`mar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marca`
--

LOCK TABLES `marca` WRITE;
/*!40000 ALTER TABLE `marca` DISABLE KEYS */;
INSERT INTO `marca` VALUES (1,'Pizza Hut'),(2,'Pizza Hut'),(3,'Pizza Hut'),(4,'Fridays'),(5,'Fridays'),(6,'');
/*!40000 ALTER TABLE `marca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `pag_id` int NOT NULL,
  `pag_monto` decimal(10,2) DEFAULT '0.00',
  `pag_fecha` datetime DEFAULT NULL,
  `pag_observacion` text,
  PRIMARY KEY (`pag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (1,180.00,'2026-03-01 10:30:00','Pago completo transferencia bancaria'),(2,350.00,'2026-03-02 14:00:00','Pago completo cheque'),(3,150.00,'2026-02-20 09:15:00','Abono parcial efectivo'),(4,180.00,'2026-02-10 11:00:00','Pago total deposito'),(5,0.00,'2026-03-05 14:21:16','prueba pago'),(6,120.00,'2026-03-05 14:21:16','abono 120'),(7,130.00,'2026-03-05 14:20:40','Prueba');
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal`
--

DROP TABLE IF EXISTS `personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal` (
  `per_id` int NOT NULL AUTO_INCREMENT,
  `per_nombre` varchar(200) DEFAULT NULL,
  `per_documento` varchar(50) DEFAULT NULL,
  `cli_id` int NOT NULL,
  `per_correo` varchar(150) DEFAULT NULL COMMENT 'Email para OTP',
  `per_estado` varchar(10) NOT NULL DEFAULT 'activo' COMMENT 'activo | bloqueado | inactivo',
  `per_cupo_asignado` decimal(10,2) DEFAULT NULL COMMENT 'Cupo total asignado al empleado',
  `per_cupo_disponible` decimal(10,2) DEFAULT NULL COMMENT 'Cupo restante disponible',
  PRIMARY KEY (`per_id`),
  KEY `fk_personal_cliente` (`cli_id`),
  CONSTRAINT `fk_personal_cliente` FOREIGN KEY (`cli_id`) REFERENCES `cliente` (`cli_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal`
--

LOCK TABLES `personal` WRITE;
/*!40000 ALTER TABLE `personal` DISABLE KEYS */;
INSERT INTO `personal` VALUES (1,'Roberto Andrade Mora','1701234567',1,NULL,'activo',NULL,NULL),(2,'Sandra Quito Lema','1702345678',1,NULL,'activo',NULL,NULL),(3,'Fernanda Vasquez Paz','1703456789',2,NULL,'activo',NULL,NULL),(4,'Luis Carrillo Navas','1704567890',2,NULL,'activo',NULL,NULL),(5,'Miguel Torres Abad','0901234567',3,NULL,'activo',NULL,NULL),(6,'Rosa Intriago Velez','0902345678',3,NULL,'activo',NULL,NULL),(7,'Patricia Rios Urgiles','0101234567',4,NULL,'activo',NULL,NULL),(8,'Marco Orellana Tapia','0102345678',4,NULL,'activo',NULL,NULL),(9,'Andres Morales Pinto','1005678901',5,NULL,'activo',NULL,NULL),(10,'Silvia Enriquez Lagos','1006789012',5,NULL,'activo',NULL,NULL),(11,'Carmen Salinas Tigse','0501234567',6,NULL,'activo',NULL,NULL),(12,'Jorge Guanoluisa Ante','0502345678',6,NULL,'activo',NULL,NULL),(13,'Diego Paredes Rueda','1707890123',7,NULL,'activo',NULL,NULL),(14,'Natalia Espin Coro','1708901234',7,NULL,'activo',NULL,NULL),(15,'Lucia Herrera Freire','1801234567',8,NULL,'activo',NULL,NULL),(16,'Hernan Moreta Bonilla','1802345678',8,NULL,'activo',NULL,NULL),(17,'Esteban Cardenas Pulla','0103456789',9,NULL,'activo',NULL,NULL),(18,'Cristina Deleg Sarmiento','0104567890',9,NULL,'activo',NULL,NULL),(19,'Veronica Castillo Chavez','0601234567',10,NULL,'activo',NULL,NULL),(20,'Ramiro Naranjo Aldaz','0602345678',10,NULL,'activo',NULL,NULL),(21,'','',11,NULL,'activo',NULL,NULL),(22,'Juan Pérez Torres','0931804355',13,NULL,'activo',500.00,480.00);
/*!40000 ALTER TABLE `personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name_user` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `permisos_acceso` varchar(50) NOT NULL DEFAULT 'Operador' COMMENT 'Super Admin | Operador | cajero | cliente_giftcard | empresa_cliente',
  `status` varchar(20) NOT NULL DEFAULT 'activo',
  `loc_id` int DEFAULT NULL COMMENT 'FK local - sucursal asignada al cajero',
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin','0192023a7bbd73250516f069df18b500','Administrador General','admin@sgcargos.com','0991234567',NULL,'Super Admin','activo',NULL),(2,'supervisor','0192023a7bbd73250516f069df18b500','Carlos Mendoza','cmendoza@sgcargos.com','0992345678',NULL,'Supervisor','activo',NULL),(3,'operador1','0192023a7bbd73250516f069df18b500','Maria Lopez','mlopez@sgcargos.com','0993456789',NULL,'Operador','activo',NULL),(4,'operador2','0192023a7bbd73250516f069df18b500','Juan Perez','jperez@sgcargos.com','0994567890',NULL,'Operador','activo',NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_diferida`
--

DROP TABLE IF EXISTS `venta_diferida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta_diferida` (
  `vd_id` int NOT NULL AUTO_INCREMENT,
  `per_id` int NOT NULL COMMENT 'FK personal',
  `id_user` int NOT NULL COMMENT 'FK usuario - operador que registr├│',
  `vd_descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descripci├│n libre del producto',
  `vd_monto_total` decimal(10,2) NOT NULL COMMENT 'Valor total a financiar',
  `vd_num_cuotas` int NOT NULL COMMENT 'N├║mero total de cuotas',
  `vd_cuotas_pagadas` int NOT NULL DEFAULT '0' COMMENT 'Cuotas cobradas hasta la fecha',
  `vd_monto_cuota` decimal(10,2) NOT NULL COMMENT 'Valor fijo por cuota',
  `vd_fecha_inicio` date NOT NULL COMMENT 'Per├¡odo de corte en que inicia el cobro',
  `vd_estado` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo' COMMENT 'activo | completado | cancelado',
  PRIMARY KEY (`vd_id`),
  KEY `idx_vd_per_id` (`per_id`),
  KEY `idx_vd_id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_diferida`
--

LOCK TABLES `venta_diferida` WRITE;
/*!40000 ALTER TABLE `venta_diferida` DISABLE KEYS */;
INSERT INTO `venta_diferida` VALUES (1,22,1,'lspyop 2000',2000.00,12,2,166.67,'2026-03-21','activo'),(2,22,1,'lpatop',1000.00,50,50,20.00,'2026-03-19','completado');
/*!40000 ALTER TABLE `venta_diferida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sgc_argos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-25 10:23:37
