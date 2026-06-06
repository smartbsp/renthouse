-- MySQL dump 10.14  Distrib 5.5.57-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: lohastime
-- ------------------------------------------------------
-- Server version	5.5.68-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `renthouse_records`
--

DROP TABLE IF EXISTS `renthouse_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renthouse_records` (
  `id` bigint(20) NOT NULL,
  `billMonth` varchar(20) DEFAULT NULL,
  `startDate` varchar(20) DEFAULT NULL,
  `endDate` varchar(20) DEFAULT NULL,
  `totalBill` decimal(12,2) DEFAULT '0.00',
  `publicElec` decimal(12,2) DEFAULT '0.00',
  `baseFee` decimal(12,2) DEFAULT '0.00',
  `billingKwh` decimal(12,3) DEFAULT '0.000',
  `meterAprev` decimal(12,3) DEFAULT '0.000',
  `meterAcurr` decimal(12,3) DEFAULT '0.000',
  `meterA` decimal(12,3) DEFAULT '0.000',
  `meterBprev` decimal(12,3) DEFAULT '0.000',
  `meterBcurr` decimal(12,3) DEFAULT '0.000',
  `meterB` decimal(12,3) DEFAULT '0.000',
  `pricePerKwh` decimal(12,6) DEFAULT '0.000000',
  `costA` decimal(12,2) DEFAULT '0.00',
  `costB` decimal(12,2) DEFAULT '0.00',
  `period` varchar(50) DEFAULT NULL,
  `readingDate` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `meterDate` varchar(20) DEFAULT NULL,
  `meterDateNext` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renthouse_records`
--

LOCK TABLES `renthouse_records` WRITE;
/*!40000 ALTER TABLE `renthouse_records` DISABLE KEYS */;
INSERT INTO `renthouse_records` VALUES (1779414332643,'115/05','2026-03-16','2026-05-14',5640.00,601.90,40.00,1571.000,2956.000,3532.000,576.000,4702.000,5680.000,978.000,3.216000,2174.00,3466.00,'115/03/16~115/05/14','','2026-06-05 01:55:57','2026-05-15','2026-07-16'),(1779424261764,'114/09','2025-07-15','2025-09-14',9399.00,0.00,40.00,2025.000,382.600,1217.400,834.800,399.000,1813.100,1414.100,4.162000,3494.00,5905.00,'114/07/15~114/09/14','','2026-06-05 01:55:57','2025-09-15','2025-11-16'),(1779424406564,'114/11','2025-09-15','2025-11-16',7937.00,671.00,40.00,1941.000,1217.400,1895.900,678.500,1813.100,2921.200,1108.100,4.045000,3100.00,4837.00,'114/09/15~114/11/16','','2026-06-05 01:55:57','2025-11-17','2026-01-15'),(1779427821387,'115/03','2026-01-15','2026-03-15',4786.00,575.70,40.00,1414.000,2374.100,2956.300,582.200,3809.200,4702.600,893.400,2.826000,1953.00,2833.00,'115/01/15~115/03/15','','2026-06-05 01:55:57','2026-03-16','2026-05-17'),(1779429807664,'115/01','2025-11-17','2026-01-14',4733.00,591.30,40.00,1401.000,1895.900,2374.100,478.200,2921.200,3809.200,888.000,3.002000,1751.00,2982.00,'114/11/17~115/01/14','','2026-06-05 01:55:57','2026-01-15','2026-03-16');
/*!40000 ALTER TABLE `renthouse_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `renthouse_water_records`
--

DROP TABLE IF EXISTS `renthouse_water_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renthouse_water_records` (
  `id` bigint(20) NOT NULL,
  `billMonth` varchar(20) DEFAULT NULL,
  `startDate` varchar(20) DEFAULT NULL,
  `endDate` varchar(20) DEFAULT NULL,
  `totalBill` decimal(12,2) DEFAULT '0.00',
  `baseFee` decimal(12,2) DEFAULT '0.00',
  `billingKwh` decimal(12,3) DEFAULT '0.000',
  `wAprev` decimal(12,3) DEFAULT '0.000',
  `wAcurr` decimal(12,3) DEFAULT '0.000',
  `wBprev` decimal(12,3) DEFAULT '0.000',
  `wBcurr` decimal(12,3) DEFAULT '0.000',
  `readDate` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `wMeterPrev` decimal(12,3) DEFAULT NULL,
  `wMeterCurr` decimal(12,3) DEFAULT NULL,
  `wSubMeter` decimal(12,3) DEFAULT NULL,
  `wWaterFee` decimal(12,2) DEFAULT NULL,
  `wExtraFee` decimal(12,2) DEFAULT NULL,
  `readDateNext` varchar(20) DEFAULT NULL,
  `mode` varchar(8) DEFAULT NULL,
  `costA` decimal(12,2) DEFAULT NULL,
  `costB` decimal(12,2) DEFAULT NULL,
  `paid` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renthouse_water_records`
--

LOCK TABLES `renthouse_water_records` WRITE;
/*!40000 ALTER TABLE `renthouse_water_records` DISABLE KEYS */;
INSERT INTO `renthouse_water_records` VALUES (1780672061496,'114/10','2025-07-11','2025-09-09',602.00,71.40,58.000,0.000,0.000,0.000,0.000,'2025-09-09','2026-06-05 15:07:37',933.000,988.000,3.000,506.10,24.00,'2025-11-12','head',240.80,361.20,1),(1780672460511,'114/12','2025-09-10','2025-11-12',646.00,71.40,62.000,0.000,0.000,0.000,0.000,'2025-11-12','2026-06-05 15:14:16',988.000,1046.000,4.000,548.10,26.00,'2026-01-07','head',258.40,387.60,1),(1780673580249,'115/02','2025-11-13','2026-01-07',621.00,71.40,60.000,0.000,0.000,0.000,0.000,'2026-01-07','2026-06-05 15:32:56',1046.000,1102.000,4.000,525.00,25.00,'2026-03-11','head',248.40,372.60,1),(1780673669835,'115/04','2026-01-08','2026-03-11',730.00,71.40,69.000,0.000,0.000,0.000,0.000,'2026-03-11','2026-06-05 15:34:26',1102.000,1169.000,2.000,628.92,30.00,'2026-05-13','head',292.00,438.00,1),(1780674925910,'115/06','2026-03-12','2026-05-13',602.00,71.40,58.000,0.000,0.000,105.000,138.000,'2026-05-13','2026-06-05 15:55:22',1169.000,1225.000,2.000,506.10,24.00,'2026-07-10','head',240.80,361.20,0);
/*!40000 ALTER TABLE `renthouse_water_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `renthouse_tenants`
--

DROP TABLE IF EXISTS `renthouse_tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renthouse_tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` longtext,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renthouse_tenants`
--

LOCK TABLES `renthouse_tenants` WRITE;
/*!40000 ALTER TABLE `renthouse_tenants` DISABLE KEYS */;
INSERT INTO `renthouse_tenants` VALUES (1,'{\"A\":[{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\",\"subsidy\":true},{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"subsidy\":true}],\"B\":[{\"name\":\"吳爸爸\",\"id\":\"\",\"phone\":\"+886988243185\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameExt\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\"},{\"name\":\"吳妹妹\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false},{\"name\":\"吳媽媽\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false}],\"extSignerB_name\":\"吳岱昕\",\"extSignerB_id\":\"U121804471\",\"extSignerB_phone\":\"0906606861\",\"extSignerB_addr\":\"桃園市桃園區莊敬路一段210巷118號14F\",\"extSignerB_leaseStart\":\"2025-07-01\",\"extSignerB_leaseEnd\":\"2027-06-30\",\"extSignerA_leaseStart\":\"2026-06-11\",\"extSignerA_leaseEnd\":\"2026-06-26\",\"extSignerA_name\":\"我們\",\"finRentA\":\"20500\",\"finMgmtA\":\"2000\",\"extSignerB_subsidy\":true,\"finParkingA\":\"1500\",\"finDepositA\":\"45000\",\"finNoteA\":\"車位115\\/01才租所以沒算在保證金內\",\"finRentB\":\"20000\",\"finMgmtB\":\"2000\",\"finNoteB\":\"租金含車位\",\"finPayMethodA\":\"half\",\"finPayDatesA\":[\"2025-06-01\",\"2025-12-01\",\"2026-06-01\",\"2025-06-05\",\"2025-12-05\",\"2026-06-05\"],\"finPayDayA\":\"5\",\"finPayMethodB\":\"month\",\"finPayDayB\":\"10\",\"finPayDatesB\":[\"2026-02-05\",\"2025-08-05\",\"2026-03-05\",\"2025-09-05\",\"2026-04-05\",\"2025-10-05\",\"2026-05-05\",\"2025-11-05\",\"2025-12-05\",\"2026-01-05\",\"2025-07-05\",\"2025-07-10\",\"2026-02-10\",\"2025-08-10\",\"2026-03-10\",\"2025-09-10\",\"2026-04-10\",\"2025-10-10\",\"2025-11-10\",\"2025-12-10\",\"2026-01-10\",\"2026-05-10\"],\"finAgencyA\":\"\",\"finAgencyB\":\"0\",\"finParkingB\":\"0\",\"waterBillingMode\":\"head\"}','2026-06-05 17:07:50');
/*!40000 ALTER TABLE `renthouse_tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `renthouse_drafts`
--

DROP TABLE IF EXISTS `renthouse_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renthouse_drafts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` longtext,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renthouse_drafts`
--

LOCK TABLES `renthouse_drafts` WRITE;
/*!40000 ALTER TABLE `renthouse_drafts` DISABLE KEYS */;
INSERT INTO `renthouse_drafts` VALUES (1,'{\"startDate\":\"2026-01-15\",\"endDate\":\"2026-03-15\",\"totalBill\":\"4786\",\"billMonth\":\"115\\/03\",\"publicElec\":\"575.7\",\"baseFee\":\"40\",\"billingKwh\":\"1414\",\"avgPricePerKwh\":\"3.385\",\"meterDate\":\"2026-03-16\",\"meterDateNext\":\"2026-05-17\",\"meterAprev\":\"2374.1\",\"meterAcurr\":\"2956.3\",\"meterBprev\":\"3809.2\",\"meterBcurr\":\"4702.6\"}','2026-06-05 04:18:18');
/*!40000 ALTER TABLE `renthouse_drafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `renthouse_tenant_log`
--

DROP TABLE IF EXISTS `renthouse_tenant_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renthouse_tenant_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit` char(1) DEFAULT NULL,
  `tenant_index` int(11) DEFAULT NULL,
  `tenant_name` varchar(100) DEFAULT NULL,
  `data` longtext,
  `log_month` varchar(7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_unit` (`unit`),
  KEY `idx_name` (`tenant_name`),
  KEY `idx_month` (`log_month`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renthouse_tenant_log`
--

LOCK TABLES `renthouse_tenant_log` WRITE;
/*!40000 ALTER TABLE `renthouse_tenant_log` DISABLE KEYS */;
INSERT INTO `renthouse_tenant_log` VALUES (7,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\"}','2026-06','2026-06-04 15:56:46'),(9,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\"}','2026-06','2026-06-04 15:56:46'),(10,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\"}','2026-06','2026-06-04 15:57:00'),(12,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\"}','2026-06','2026-06-04 15:57:00'),(15,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\"}','2026-06','2026-06-04 16:12:33'),(17,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\"}','2026-06','2026-06-04 16:12:33'),(22,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\"}','2026-06','2026-06-04 16:36:12'),(23,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":false,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\"}','2026-06','2026-06-04 16:36:12'),(27,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\"}','2026-06','2026-06-04 16:44:07'),(29,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\"}','2026-06','2026-06-04 16:44:07'),(32,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"subsidy\":true}','2026-06','2026-06-05 00:13:38'),(36,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\",\"subsidy\":true}','2026-06','2026-06-05 00:13:38'),(38,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\",\"subsidy\":true}','2026-06','2026-06-05 00:54:19'),(39,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":true,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"subsidy\":true}','2026-06','2026-06-05 00:54:19'),(43,'A',-1,'我們','{\"name\":\"我們\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"\",\"leaseStart\":\"2026-06-11\",\"leaseEnd\":\"2026-06-26\",\"subsidy\":false,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 01:27:01'),(44,'B',-1,'吳岱昕','{\"name\":\"吳岱昕\",\"id\":\"U121804471\",\"phone\":\"0906606861\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"桃園市桃園區莊敬路一段210巷118號14F\",\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"subsidy\":true,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 01:27:01'),(45,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\",\"subsidy\":true}','2026-06','2026-06-05 01:43:15'),(46,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"subsidy\":true}','2026-06','2026-06-05 01:43:15'),(47,'B',0,'吳爸爸','{\"name\":\"吳爸爸\",\"id\":\"\",\"phone\":\"+886988243185\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameExt\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\"}','2026-06','2026-06-05 01:43:15'),(48,'A',-1,'我們','{\"name\":\"我們\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"\",\"leaseStart\":\"2026-06-11\",\"leaseEnd\":\"2026-06-26\",\"subsidy\":false,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 01:43:15'),(49,'B',1,'吳妹妹','{\"name\":\"吳妹妹\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false}','2026-06','2026-06-05 01:43:16'),(50,'B',2,'吳媽媽','{\"name\":\"吳媽媽\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false}','2026-06','2026-06-05 01:43:16'),(51,'B',-1,'吳岱昕','{\"name\":\"吳岱昕\",\"id\":\"U121804471\",\"phone\":\"0906606861\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"桃園市桃園區莊敬路一段210巷118號14F\",\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"subsidy\":true,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 01:43:16'),(52,'A',1,'黃士洧','{\"name\":\"黃士洧\",\"id\":\"P124262541\",\"phone\":\"0955091011\",\"line\":\"\",\"addr\":\"雲林縣東勢鄉嘉隆村守法街6號\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"subsidy\":true}','2026-06','2026-06-05 03:43:14'),(53,'A',0,'郭怡安','{\"name\":\"郭怡安\",\"id\":\"F229816062\",\"phone\":\"0976076648\",\"line\":\"\",\"addr\":\"新北市新莊區中港路423號2樓\",\"isSigner\":true,\"leaseStart\":\"2025-06-01\",\"leaseEnd\":\"2027-05-31\",\"commAddr\":\"新北市新莊區中港路390號2樓\",\"subsidy\":true}','2026-06','2026-06-05 03:43:14'),(54,'A',-1,'我們','{\"name\":\"我們\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"\",\"leaseStart\":\"2026-06-11\",\"leaseEnd\":\"2026-06-26\",\"subsidy\":false,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 03:43:14'),(55,'B',0,'吳爸爸','{\"name\":\"吳爸爸\",\"id\":\"\",\"phone\":\"+886988243185\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameExt\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\"}','2026-06','2026-06-05 03:43:14'),(56,'B',1,'吳妹妹','{\"name\":\"吳妹妹\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false}','2026-06','2026-06-05 03:43:14'),(57,'B',2,'吳媽媽','{\"name\":\"吳媽媽\",\"id\":\"\",\"phone\":\"\",\"line\":\"\",\"addr\":\"\",\"isSigner\":false,\"sameDates\":true,\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"sameExt\":false}','2026-06','2026-06-05 03:43:14'),(58,'B',-1,'吳岱昕','{\"name\":\"吳岱昕\",\"id\":\"U121804471\",\"phone\":\"0906606861\",\"line\":\"\",\"commAddr\":\"\",\"addr\":\"桃園市桃園區莊敬路一段210巷118號14F\",\"leaseStart\":\"2025-07-01\",\"leaseEnd\":\"2027-06-30\",\"subsidy\":true,\"isSigner\":true,\"isExtSigner\":true}','2026-06','2026-06-05 03:43:14');
/*!40000 ALTER TABLE `renthouse_tenant_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-06 14:29:52
