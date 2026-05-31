-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: oasis_staging
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
-- Current Database: `oasis_staging`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `oasis_staging` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `oasis_staging`;

--
-- Table structure for table `animal_geofence`
--

DROP TABLE IF EXISTS `animal_geofence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animal_geofence` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_id` bigint(20) unsigned NOT NULL,
  `geofence_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `animal_geofence_animal_id_geofence_id_unique` (`animal_id`,`geofence_id`),
  KEY `animal_geofence_geofence_id_foreign` (`geofence_id`),
  CONSTRAINT `animal_geofence_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animal_geofence_geofence_id_foreign` FOREIGN KEY (`geofence_id`) REFERENCES `geofences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animal_geofence`
--

LOCK TABLES `animal_geofence` WRITE;
/*!40000 ALTER TABLE `animal_geofence` DISABLE KEYS */;
/*!40000 ALTER TABLE `animal_geofence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `animal_group_geofence`
--

DROP TABLE IF EXISTS `animal_group_geofence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animal_group_geofence` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_group_id` bigint(20) unsigned NOT NULL,
  `geofence_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `animal_group_geofence_animal_group_id_geofence_id_unique` (`animal_group_id`,`geofence_id`),
  KEY `animal_group_geofence_geofence_id_foreign` (`geofence_id`),
  CONSTRAINT `animal_group_geofence_animal_group_id_foreign` FOREIGN KEY (`animal_group_id`) REFERENCES `animal_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animal_group_geofence_geofence_id_foreign` FOREIGN KEY (`geofence_id`) REFERENCES `geofences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animal_group_geofence`
--

LOCK TABLES `animal_group_geofence` WRITE;
/*!40000 ALTER TABLE `animal_group_geofence` DISABLE KEYS */;
/*!40000 ALTER TABLE `animal_group_geofence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `animal_group_member`
--

DROP TABLE IF EXISTS `animal_group_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animal_group_member` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_group_id` bigint(20) unsigned NOT NULL,
  `animal_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `animal_group_member_animal_group_id_animal_id_unique` (`animal_group_id`,`animal_id`),
  KEY `animal_group_member_animal_id_foreign` (`animal_id`),
  CONSTRAINT `animal_group_member_animal_group_id_foreign` FOREIGN KEY (`animal_group_id`) REFERENCES `animal_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animal_group_member_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animal_group_member`
--

LOCK TABLES `animal_group_member` WRITE;
/*!40000 ALTER TABLE `animal_group_member` DISABLE KEYS */;
INSERT INTO `animal_group_member` VALUES (1,1,1,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(2,1,2,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(3,2,3,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(4,2,4,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(5,3,5,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(6,3,6,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(7,4,7,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(8,4,8,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(9,5,9,'2026-05-06 15:40:46','2026-05-06 15:40:46'),(10,5,10,'2026-05-06 15:40:46','2026-05-06 15:40:46');
/*!40000 ALTER TABLE `animal_group_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `animal_groups`
--

DROP TABLE IF EXISTS `animal_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animal_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `color` varchar(7) NOT NULL DEFAULT '#D4AF37',
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_groups_owner_id_foreign` (`owner_id`),
  CONSTRAINT `animal_groups_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animal_groups`
--

LOCK TABLES `animal_groups` WRITE;
/*!40000 ALTER TABLE `animal_groups` DISABLE KEYS */;
INSERT INTO `animal_groups` VALUES (1,'Northern Herd',NULL,'Camels grazing in the Al Wathba northern region',NULL,'#10b981',NULL,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(2,'Breeding Stock',NULL,'Premium breeding camels selected for lineage',NULL,'#3b82f6',NULL,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(3,'Working Herd',NULL,'Camels used for transportation and work',NULL,'#f59e0b',NULL,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(4,'Young Stock',NULL,'Young camels under 2 years old',NULL,'#8b5cf6',NULL,'2026-05-06 15:40:45','2026-05-06 15:40:45'),(5,'Show Camels',NULL,'Camels trained for camel racing and shows',NULL,'#ec4899',NULL,'2026-05-06 15:40:45','2026-05-06 15:40:45');
/*!40000 ALTER TABLE `animal_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `animals`
--

DROP TABLE IF EXISTS `animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `species` enum('Camel','Goat','Sheep') NOT NULL,
  `breed` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `color_markings` text DEFAULT NULL,
  `current_weight` decimal(8,2) DEFAULT NULL,
  `identification_photo` varchar(255) DEFAULT NULL,
  `baseline_temperature` decimal(4,1) DEFAULT NULL,
  `normal_heart_rate` int(11) DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `animals_animal_id_unique` (`animal_id`),
  KEY `animals_owner_id_index` (`owner_id`),
  KEY `animals_species_index` (`species`),
  KEY `animals_breed_index` (`breed`),
  CONSTRAINT `animals_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animals`
--

LOCK TABLES `animals` WRITE;
/*!40000 ALTER TABLE `animals` DISABLE KEYS */;
INSERT INTO `animals` VALUES (1,'OA-2026-0001',NULL,'Camel','Majaheem','2022-03-15','Male','White with dark spots on hump',650.00,NULL,38.5,45,2,'2026-05-06 15:40:43','2026-05-06 15:40:43'),(2,'OA-2026-0002',NULL,'Camel','Wadhah','2021-07-20','Female','Golden brown coat',580.00,NULL,38.2,42,3,'2026-05-06 15:40:43','2026-05-06 15:40:43'),(3,'OA-2026-0003',NULL,'Camel','Suhail','2023-01-10','Male','Dark brown, white legs',480.00,NULL,38.8,48,4,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(4,'OA-2026-0004',NULL,'Camel','Majaheem','2020-05-08','Female','Cream colored',620.00,NULL,38.4,40,6,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(5,'OA-2026-0005',NULL,'Camel','Wadhah','2023-06-22','Male','Grey with black mane',420.00,NULL,39.0,50,7,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(6,'OA-2026-0006',NULL,'Camel','Suhail','2021-11-30','Female','Black coat',550.00,NULL,38.3,44,2,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(7,'OA-2026-0007',NULL,'Goat','Boer','2024-02-14','Male','White with brown head',85.00,NULL,39.5,75,3,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(8,'OA-2026-0008',NULL,'Goat','Boer','2023-09-05','Female','Pure white',72.00,NULL,39.2,78,4,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(9,'OA-2026-0009',NULL,'Sheep','Awassi','2023-04-18','Male','White wool, black face',95.00,NULL,39.8,70,6,'2026-05-06 15:40:44','2026-05-06 15:40:44'),(10,'OA-2026-0010',NULL,'Sheep','Awassi','2022-12-25','Female','Cream colored wool',88.00,NULL,39.4,72,7,'2026-05-06 15:40:44','2026-05-06 15:40:44');
/*!40000 ALTER TABLE `animals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auctions`
--

DROP TABLE IF EXISTS `auctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auctions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `starting_price` decimal(12,2) NOT NULL,
  `current_price` decimal(12,2) DEFAULT NULL,
  `reserve_price` decimal(12,2) DEFAULT NULL,
  `status` enum('draft','active','ended','sold','cancelled') NOT NULL DEFAULT 'draft',
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `title` varchar(255) NOT NULL,
  `title_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`title_json`)),
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `winner_id` bigint(20) unsigned DEFAULT NULL,
  `payment_proof_url` varchar(255) DEFAULT NULL,
  `payment_expires_at` timestamp NULL DEFAULT NULL,
  `payment_verified_at` timestamp NULL DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `payment_notes` text DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `second_winner_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `auctions_verified_by_foreign` (`verified_by`),
  KEY `auctions_second_winner_id_foreign` (`second_winner_id`),
  KEY `auctions_animal_id_index` (`animal_id`),
  KEY `auctions_owner_id_index` (`owner_id`),
  KEY `auctions_status_index` (`status`),
  KEY `auctions_ends_at_index` (`ends_at`),
  KEY `auctions_winner_id_index` (`winner_id`),
  CONSTRAINT `auctions_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auctions_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auctions_second_winner_id_foreign` FOREIGN KEY (`second_winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `auctions_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `auctions_winner_id_foreign` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auctions`
--

LOCK TABLES `auctions` WRITE;
/*!40000 ALTER TABLE `auctions` DISABLE KEYS */;
INSERT INTO `auctions` VALUES (1,1,2,25000.00,26817.00,35000.00,'active','A well-bred Majaheem camel, perfect for breeding. Excellent lineage with proven fertility.',NULL,'Prime Majaheem Camel - OA-2026-0001',NULL,'2026-05-06 15:40:46','2026-05-11 15:40:46',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-05-06 15:40:46','2026-05-06 15:40:47'),(2,2,3,30000.00,32665.00,40000.00,'active','Experienced breeding female with two successful pregnancies. Great mothering qualities.',NULL,'Wadhah Breeding Female - OA-2026-0002',NULL,'2026-05-06 15:40:46','2026-05-13 15:40:46',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(3,3,4,45000.00,46357.00,NULL,'active','Young male camel with exceptional speed and stamina. Currently in training for upcoming season.',NULL,'Racing Suhail - OA-2026-0003',NULL,'2026-05-06 15:40:47','2026-05-09 15:40:47',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-05-06 15:40:47','2026-05-06 15:40:47');
/*!40000 ALTER TABLE `auctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bids`
--

DROP TABLE IF EXISTS `bids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `auction_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bidder_name` varchar(255) DEFAULT NULL,
  `bid_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_winning` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bids_auction_id_index` (`auction_id`),
  KEY `bids_user_id_index` (`user_id`),
  KEY `bids_amount_index` (`amount`),
  CONSTRAINT `bids_auction_id_foreign` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bids_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bids`
--

LOCK TABLES `bids` WRITE;
/*!40000 ALTER TABLE `bids` DISABLE KEYS */;
INSERT INTO `bids` VALUES (1,1,7,25898.00,'Ali Shepherd','2026-05-06 14:40:47',0,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(2,1,4,26817.00,'Saeed Al-Maktoum','2026-05-06 05:40:47',1,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(3,2,2,31867.00,'Khalid Al-Rashid','2026-05-06 14:40:47',0,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(4,2,6,32665.00,'Omar Shepherd','2026-05-05 21:40:47',0,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(5,3,7,45794.00,'Ali Shepherd','2026-05-06 14:40:47',0,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(6,3,2,46357.00,'Khalid Al-Rashid','2026-05-05 16:40:47',1,'2026-05-06 15:40:47','2026-05-06 15:40:47');
/*!40000 ALTER TABLE `bids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `breeds`
--

DROP TABLE IF EXISTS `breeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `breeds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `species_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `description` varchar(255) DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `breeds_species_id_foreign` (`species_id`),
  CONSTRAINT `breeds_species_id_foreign` FOREIGN KEY (`species_id`) REFERENCES `species` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `breeds`
--

LOCK TABLES `breeds` WRITE;
/*!40000 ALTER TABLE `breeds` DISABLE KEYS */;
INSERT INTO `breeds` VALUES (1,1,'Majaheem',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(2,1,'Wadhah',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(3,1,'Suhail',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(4,1,'Maqatir',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(5,1,'Shalal',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(6,2,'Boer',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(7,2,'Nubian',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(8,2,'Saanen',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(9,3,'Awassi',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(10,3,'Najdi',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(11,4,'Holstein',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(12,4,'Jersey',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(13,5,'Saluki',NULL,NULL,NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30');
/*!40000 ALTER TABLE `breeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `type` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `firmware_version` varchar(255) NOT NULL DEFAULT 'v2.4',
  `battery_level` int(11) NOT NULL DEFAULT 100,
  `signal_strength` int(11) DEFAULT NULL,
  `status` enum('online','offline','low_signal') NOT NULL DEFAULT 'offline',
  `update_interval` int(11) NOT NULL DEFAULT 15,
  `advanced_tracking` tinyint(1) NOT NULL DEFAULT 0,
  `animal_id` bigint(20) unsigned DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `gps_lat` decimal(10,7) DEFAULT NULL,
  `gps_lng` decimal(10,7) DEFAULT NULL,
  `last_ping` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_device_id_unique` (`device_id`),
  UNIQUE KEY `devices_serial_number_unique` (`serial_number`),
  KEY `devices_owner_id_index` (`owner_id`),
  KEY `devices_animal_id_index` (`animal_id`),
  KEY `devices_status_index` (`status`),
  CONSTRAINT `devices_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `devices_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,'IOT-001-A',NULL,NULL,NULL,NULL,'v2.4.1',95,NULL,'online',15,0,NULL,NULL,24.7136000,46.6753000,'2026-05-06 15:35:43','2026-05-06 15:40:43','2026-05-06 15:40:43'),(2,'IOT-002-B',NULL,NULL,NULL,NULL,'v2.4.1',82,NULL,'online',15,0,NULL,NULL,24.7146000,46.6763000,'2026-05-06 15:38:43','2026-05-06 15:40:43','2026-05-06 15:40:43'),(3,'IOT-003-C',NULL,NULL,NULL,NULL,'v2.4.0',14,NULL,'low_signal',15,0,NULL,NULL,24.7156000,46.6773000,'2026-05-06 15:25:43','2026-05-06 15:40:43','2026-05-06 15:40:43'),(4,'IOT-004-D',NULL,NULL,NULL,NULL,'v2.4.1',78,NULL,'online',15,0,NULL,NULL,24.7166000,46.6743000,'2026-05-06 15:39:43','2026-05-06 15:40:43','2026-05-06 15:40:43'),(5,'IOT-005-E',NULL,NULL,NULL,NULL,'v2.3.9',0,NULL,'offline',15,0,NULL,NULL,NULL,NULL,'2026-05-06 03:40:43','2026-05-06 15:40:43','2026-05-06 15:40:43'),(6,'IOT-006-F',NULL,NULL,NULL,NULL,'v2.4.1',91,NULL,'online',15,0,NULL,NULL,24.7126000,46.6783000,'2026-05-06 15:37:43','2026-05-06 15:40:43','2026-05-06 15:40:43');
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `geofence_alerts`
--

DROP TABLE IF EXISTS `geofence_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geofence_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `geofence_id` bigint(20) unsigned NOT NULL,
  `animal_id` bigint(20) unsigned NOT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `type` enum('entry','exit') NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `geofence_alerts_geofence_id_index` (`geofence_id`),
  KEY `geofence_alerts_animal_id_index` (`animal_id`),
  KEY `geofence_alerts_triggered_at_index` (`triggered_at`),
  KEY `geofence_alerts_type_index` (`type`),
  CONSTRAINT `geofence_alerts_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `geofence_alerts_geofence_id_foreign` FOREIGN KEY (`geofence_id`) REFERENCES `geofences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `geofence_alerts`
--

LOCK TABLES `geofence_alerts` WRITE;
/*!40000 ALTER TABLE `geofence_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `geofence_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `geofences`
--

DROP TABLE IF EXISTS `geofences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geofences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `coordinates` text NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#D4AF37',
  `alert_type` enum('entry','exit','both') NOT NULL DEFAULT 'both',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `geofences_owner_id_foreign` (`owner_id`),
  CONSTRAINT `geofences_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `geofences`
--

LOCK TABLES `geofences` WRITE;
/*!40000 ALTER TABLE `geofences` DISABLE KEYS */;
INSERT INTO `geofences` VALUES (1,'Main Paddock',NULL,'\"[[24.7136,46.6753],[24.7136,46.6853],[24.7036,46.6853],[24.7036,46.6753],[24.7136,46.6753]]\"','#22C55E','both',1,2,'2026-05-06 15:40:46','2026-05-06 15:40:46'),(2,'Racing Track Area',NULL,'\"[[24.72,46.68],[24.72,46.69],[24.71,46.69],[24.71,46.68],[24.72,46.68]]\"','#3B82F6','exit',1,3,'2026-05-06 15:40:46','2026-05-06 15:40:46'),(3,'Breeding Zone',NULL,'\"[[24.705,46.67],[24.705,46.68],[24.695,46.68],[24.695,46.67],[24.705,46.67]]\"','#A855F7','entry',1,4,'2026-05-06 15:40:46','2026-05-06 15:40:46'),(4,'Quarantine Area',NULL,'\"[[24.725,46.665],[24.725,46.67],[24.72,46.67],[24.72,46.665],[24.725,46.665]]\"','#EF4444','both',1,6,'2026-05-06 15:40:46','2026-05-06 15:40:46'),(5,'Watering Point',NULL,'\"[[24.718,46.672],[24.718,46.674],[24.716,46.674],[24.716,46.672],[24.718,46.672]]\"','#06B6D4','entry',1,7,'2026-05-06 15:40:46','2026-05-06 15:40:46');
/*!40000 ALTER TABLE `geofences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `languages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL,
  `name` varchar(255) NOT NULL,
  `native_name` varchar(255) NOT NULL,
  `direction` varchar(10) NOT NULL DEFAULT 'ltr',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `languages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'en','English','English','ltr',1,1,1,NULL,NULL),(2,'ar','Arabic','العربية','rtl',1,0,2,NULL,NULL),(3,'ur','Urdu','اردو','rtl',1,0,3,NULL,NULL),(4,'eu','Basque','Euskara','ltr',1,0,4,NULL,NULL);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_history`
--

DROP TABLE IF EXISTS `location_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `animal_id` bigint(20) unsigned DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `speed` decimal(6,2) DEFAULT NULL,
  `heading` decimal(5,2) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `location_history_animal_id_recorded_at_index` (`animal_id`,`recorded_at`),
  KEY `location_history_device_id_recorded_at_index` (`device_id`,`recorded_at`),
  CONSTRAINT `location_history_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `location_history_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_history`
--

LOCK TABLES `location_history` WRITE;
/*!40000 ALTER TABLE `location_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `record_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`title_json`)),
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `record_date` date NOT NULL,
  `veterinarian` varchar(255) DEFAULT NULL,
  `medication` varchar(255) DEFAULT NULL,
  `dosage` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `notes` varchar(255) DEFAULT NULL,
  `notes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes_json`)),
  `attachment_url` varchar(255) DEFAULT NULL,
  `next_follow_up` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_records_animal_id_index` (`animal_id`),
  KEY `medical_records_owner_id_index` (`owner_id`),
  KEY `medical_records_record_date_index` (`record_date`),
  CONSTRAINT `medical_records_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_records_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_records`
--

LOCK TABLES `medical_records` WRITE;
/*!40000 ALTER TABLE `medical_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_records` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_01_01_000000_create_users_table',1),(2,'2024_01_01_000001_create_animals_table',1),(3,'2024_01_01_000002_create_devices_table',1),(4,'2024_01_01_000003_create_languages_table',1),(5,'2024_01_01_000004_create_translations_table',1),(6,'2026_04_08_192557_create_location_history_table',1),(7,'2026_04_08_203013_add_owner_id_to_devices_table',1),(8,'2026_04_08_212037_create_geofences_table',1),(9,'2026_04_09_000001_create_auctions_table',1),(10,'2026_04_09_000002_create_bids_table',1),(11,'2026_04_09_173442_add_notification_fields_to_geofence_alerts',1),(12,'2026_04_09_174706_create_cache_table',1),(13,'2026_04_09_180000_add_coordinates_to_geofence_alerts',1),(14,'2026_04_09_181500_create_animal_groups_and_geofence_assignment',1),(15,'2026_04_09_190000_create_animal_group_geofence_table',1),(16,'2026_04_09_200000_add_payment_fields_to_auctions',1),(17,'2026_04_09_210000_create_subscription_tiers_table',1),(18,'2026_04_09_211000_add_subscription_to_users',1),(19,'2026_04_09_220000_make_animal_id_nullable',1),(20,'2026_04_11_000001_create_tasks_table',1),(21,'2026_04_11_230300_add_payment_fields_to_user_subscriptions',1),(22,'2026_04_11_234126_create_medical_records_table',1),(23,'2026_04_12_000000_create_vaccination_schedules_table',1),(24,'2026_04_12_000001_add_recurring_tasks',1),(25,'2026_04_12_100001_add_fields_to_vaccination_schedules_table',1),(26,'2026_04_12_184538_create_settings_table',1),(27,'2026_04_15_205344_create_species_table',1),(28,'2026_04_19_183528_create_permission_tables',1),(29,'2026_04_19_183803_create_personal_access_tokens_table',1),(30,'2026_04_19_222734_add_features_column',1),(31,'2026_04_20_000000_add_medical_records_tasks_to_subscription_tiers',1),(32,'2026_04_20_000001_create_task_logs_table',1),(33,'2026_04_20_180022_remove_device_id_from_animals_table',1),(34,'2026_04_20_add_name_to_animals',1),(35,'2026_04_25_000001_add_json_translation_columns',1),(36,'2026_04_26_000001_drop_role_column',1),(37,'2026_05_04_000000_create_password_reset_tokens_table',1),(38,'2026_05_05_000001_add_payment_fields_to_auctions_table',1),(39,'2026_05_05_000002_add_foreign_keys',1),(40,'2026_05_05_000003_add_indexes',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(2,'App\\Models\\User',3),(2,'App\\Models\\User',4),(2,'App\\Models\\User',6),(2,'App\\Models\\User',7),(3,'App\\Models\\User',3),(3,'App\\Models\\User',4),(4,'App\\Models\\User',6),(4,'App\\Models\\User',7),(5,'App\\Models\\User',5);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_reset_tokens_email_index` (`email`)
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
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'user_view','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(2,'user_create','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(3,'user_edit','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(4,'user_delete','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(5,'user_assign_role','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(6,'animal_view','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(7,'animal_create','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(8,'animal_edit','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(9,'animal_delete','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(10,'animal_view_health','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(11,'device_view','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(12,'device_create','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(13,'device_edit','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(14,'device_delete','web','2026-05-06 15:40:30','2026-05-06 15:40:30'),(15,'geofence_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(16,'geofence_create','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(17,'geofence_edit','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(18,'geofence_delete','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(19,'task_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(20,'task_create','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(21,'task_complete','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(22,'task_delete','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(23,'report_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(24,'report_export','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(25,'settings_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(26,'settings_edit','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(27,'medical_record_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(28,'medical_record_create','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(29,'medical_record_edit','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(30,'vaccination_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(31,'vaccination_create','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(32,'vaccination_edit','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(33,'auction_view','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(34,'auction_create','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(35,'auction_edit','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(36,'auction_bid','web','2026-05-06 15:40:31','2026-05-06 15:40:31');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `predefined_tasks`
--

DROP TABLE IF EXISTS `predefined_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `predefined_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`title_json`)),
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `task_type` enum('inspection','medical','feeding','movement','other') NOT NULL DEFAULT 'other',
  `animal_id` bigint(20) unsigned DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_type` enum('daily','weekly','monthly','custom') DEFAULT NULL,
  `recurrence_interval` int(11) NOT NULL DEFAULT 1,
  `recurrence_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurrence_days`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `predefined_tasks_owner_id_foreign` (`owner_id`),
  KEY `predefined_tasks_animal_id_foreign` (`animal_id`),
  CONSTRAINT `predefined_tasks_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `predefined_tasks_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `predefined_tasks`
--

LOCK TABLES `predefined_tasks` WRITE;
/*!40000 ALTER TABLE `predefined_tasks` DISABLE KEYS */;
INSERT INTO `predefined_tasks` VALUES (1,2,'Morning water check',NULL,'Ensure all water troughs are filled and clean for the herd',NULL,'high','feeding',NULL,1,'daily',1,NULL,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(2,3,'Evening feeding round',NULL,'Distribute feed supplements and check food supply',NULL,'medium','feeding',NULL,1,'daily',1,NULL,'2026-05-06 15:40:47','2026-05-06 15:40:47'),(3,4,'Weekly health inspection',NULL,'Check all animals for signs of illness or injury',NULL,'high','medical',NULL,1,'weekly',1,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(4,6,'Monthly vaccination check',NULL,'Review vaccination schedules and administer vaccines',NULL,'urgent','medical',NULL,1,'monthly',1,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(5,7,'Fence integrity check',NULL,'Inspect perimeter fencing for damage or weak spots',NULL,'medium','inspection',NULL,1,'weekly',1,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(6,2,'Device battery check',NULL,'Verify GPS tracking devices have adequate battery',NULL,'medium','inspection',NULL,1,'weekly',1,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48');
/*!40000 ALTER TABLE `predefined_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(6,2),(6,3),(6,4),(6,5),(7,1),(7,2),(7,4),(8,1),(8,2),(8,3),(9,1),(9,2),(10,1),(11,1),(11,2),(11,3),(11,4),(12,1),(12,2),(13,1),(13,2),(14,1),(14,2),(15,1),(15,2),(15,3),(15,4),(16,1),(16,2),(16,3),(17,1),(17,2),(17,3),(18,1),(18,2),(19,1),(19,2),(19,3),(19,4),(20,1),(20,2),(20,3),(20,4),(21,1),(21,2),(21,3),(21,4),(22,1),(22,2),(23,1),(23,2),(23,3),(24,1),(24,2),(25,1),(26,1),(27,1),(27,5),(28,1),(28,5),(29,1),(29,5),(30,1),(30,5),(31,1),(31,5),(32,1),(32,5),(33,1),(34,1),(35,1),(36,1);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(2,'Owner','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(3,'Manager','web','2026-05-06 15:40:31','2026-05-06 15:40:31'),(4,'Shepherd','web','2026-05-06 15:40:32','2026-05-06 15:40:32'),(5,'Doctor','web','2026-05-06 15:40:32','2026-05-06 15:40:32');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `species`
--

DROP TABLE IF EXISTS `species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `species` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `description` varchar(255) DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `species_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `species`
--

LOCK TABLES `species` WRITE;
/*!40000 ALTER TABLE `species` DISABLE KEYS */;
INSERT INTO `species` VALUES (1,'Camel',NULL,'Camels',NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(2,'Goat',NULL,'Goats',NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(3,'Sheep',NULL,'Sheep',NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(4,'Cow',NULL,'Cattle',NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30'),(5,'Dog',NULL,'Dogs',NULL,1,'2026-05-06 15:39:30','2026-05-06 15:39:30');
/*!40000 ALTER TABLE `species` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_tiers`
--

DROP TABLE IF EXISTS `subscription_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_tiers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name_json`)),
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trial_days` int(11) NOT NULL DEFAULT 0,
  `max_animals` int(11) NOT NULL DEFAULT 0,
  `max_devices` int(11) NOT NULL DEFAULT 0,
  `max_users` int(11) NOT NULL DEFAULT 0,
  `has_geofencing` tinyint(1) NOT NULL DEFAULT 0,
  `has_auctions` tinyint(1) NOT NULL DEFAULT 0,
  `has_advanced_reports` tinyint(1) NOT NULL DEFAULT 0,
  `has_medical_records` tinyint(1) NOT NULL DEFAULT 0,
  `has_tasks` tinyint(1) NOT NULL DEFAULT 0,
  `has_api_access` tinyint(1) NOT NULL DEFAULT 0,
  `has_ai_assistant` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_tiers_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_tiers`
--

LOCK TABLES `subscription_tiers` WRITE;
/*!40000 ALTER TABLE `subscription_tiers` DISABLE KEYS */;
INSERT INTO `subscription_tiers` VALUES (1,'Free',NULL,'free','Perfect for getting started with basic tracking',NULL,0.00,0.00,0,5,5,1,1,1,0,1,1,1,1,1,1,'2026-05-06 15:40:30','2026-05-06 15:40:30'),(2,'Starter',NULL,'starter','For small farms with essential features',NULL,99.00,990.00,14,20,20,3,1,1,0,1,1,1,1,2,1,'2026-05-06 15:40:30','2026-05-06 15:40:30'),(3,'Professional',NULL,'professional','For growing operations with advanced features',NULL,299.00,2990.00,30,100,100,10,1,1,1,1,1,1,1,3,1,'2026-05-06 15:40:30','2026-05-06 15:40:30'),(4,'Enterprise',NULL,'enterprise','Complete solution for large operations',NULL,799.00,7990.00,30,0,0,0,1,1,1,1,1,1,1,4,1,'2026-05-06 15:40:30','2026-05-06 15:40:30');
/*!40000 ALTER TABLE `subscription_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_logs`
--

DROP TABLE IF EXISTS `task_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `log_type` enum('note','photo','voice','location') NOT NULL DEFAULT 'note',
  `description` text DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `voice_note_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','verified') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_logs_task_id_foreign` (`task_id`),
  KEY `task_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `task_logs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_logs`
--

LOCK TABLES `task_logs` WRITE;
/*!40000 ALTER TABLE `task_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `animal_id` bigint(20) unsigned DEFAULT NULL,
  `geofence_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `title_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`title_json`)),
  `description` text DEFAULT NULL,
  `description_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description_json`)),
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `task_type` enum('inspection','medical','feeding','movement','other') NOT NULL DEFAULT 'other',
  `due_date` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_type` enum('daily','weekly','monthly','custom') DEFAULT NULL,
  `recurrence_interval` int(11) NOT NULL DEFAULT 1,
  `recurrence_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurrence_days`)),
  `next_due_date` timestamp NULL DEFAULT NULL,
  `is_predefined` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_animal_id_foreign` (`animal_id`),
  KEY `tasks_geofence_id_foreign` (`geofence_id`),
  KEY `tasks_assigned_to_status_index` (`assigned_to`,`status`),
  KEY `tasks_owner_id_status_index` (`owner_id`,`status`),
  KEY `tasks_due_date_index` (`due_date`),
  CONSTRAINT `tasks_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_geofence_id_foreign` FOREIGN KEY (`geofence_id`) REFERENCES `geofences` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,2,6,1,NULL,'Check OA-2026-0001 temperature',NULL,'Monitor for signs of heat stress',NULL,'urgent','in_progress','medical','2026-05-10 15:40:48',NULL,0,NULL,1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(2,3,7,2,NULL,'Fence inspection - North Paddock',NULL,'Check for any damage or loose wires',NULL,'medium','pending','inspection','2026-05-10 15:40:48',NULL,0,NULL,1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(3,4,6,3,NULL,'Move herd to South Pasture',NULL,'Relocate camels to fresh grazing area',NULL,'low','completed','movement','2026-05-11 15:40:48',NULL,0,NULL,1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(4,6,7,4,NULL,'Emergency water delivery',NULL,'Water tanks running low, arrange emergency delivery',NULL,'high','pending','feeding','2026-05-11 15:40:48',NULL,0,NULL,1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(5,7,6,5,NULL,'New shepherd orientation',NULL,'Train new team member on herd protocols',NULL,'medium','in_progress','other','2026-05-10 15:40:48',NULL,0,NULL,1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48'),(6,2,6,1,NULL,'Daily temperature monitoring',NULL,'Record body temperatures for all breeding camels',NULL,'high','pending','medical','2026-05-07 15:40:48',NULL,1,'daily',1,NULL,NULL,0,NULL,'2026-05-06 15:40:48','2026-05-06 15:40:48');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(100) NOT NULL,
  `key` varchar(255) NOT NULL,
  `language_code` varchar(3) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translations_group_key_language_code_unique` (`group`,`key`,`language_code`),
  KEY `translations_language_code_foreign` (`language_code`),
  CONSTRAINT `translations_language_code_foreign` FOREIGN KEY (`language_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=717 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translations`
--

LOCK TABLES `translations` WRITE;
/*!40000 ALTER TABLE `translations` DISABLE KEYS */;
INSERT INTO `translations` VALUES (1,'common','add','en','Add','2026-05-06 15:40:30','2026-05-06 15:40:30'),(2,'common','add','ar','إضافة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(3,'common','add','ur','شامل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(4,'common','add','eu','Gehitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(5,'common','edit','en','Edit','2026-05-06 15:40:30','2026-05-06 15:40:30'),(6,'common','edit','ar','تعديل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(7,'common','edit','ur','ترمیم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(8,'common','edit','eu','Editatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(9,'common','delete','en','Delete','2026-05-06 15:40:30','2026-05-06 15:40:30'),(10,'common','delete','ar','حذف','2026-05-06 15:40:30','2026-05-06 15:40:30'),(11,'common','delete','ur','حذف کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(12,'common','delete','eu','Ezabatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(13,'common','save','en','Save','2026-05-06 15:40:30','2026-05-06 15:40:30'),(14,'common','save','ar','حفظ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(15,'common','save','ur','محفوظ کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(16,'common','save','eu','Gorde','2026-05-06 15:40:30','2026-05-06 15:40:30'),(17,'common','cancel','en','Cancel','2026-05-06 15:40:30','2026-05-06 15:40:30'),(18,'common','cancel','ar','إلغاء','2026-05-06 15:40:30','2026-05-06 15:40:30'),(19,'common','cancel','ur','منسوخ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(20,'common','cancel','eu','Ezeztatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(21,'common','update','en','Update','2026-05-06 15:40:30','2026-05-06 15:40:30'),(22,'common','update','ar','تحديث','2026-05-06 15:40:30','2026-05-06 15:40:30'),(23,'common','update','ur','اپڈیٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(24,'common','update','eu','Eguneratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(25,'common','enable','en','Enable','2026-05-06 15:40:30','2026-05-06 15:40:30'),(26,'common','enable','ar','تفعيل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(27,'common','enable','ur','فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(28,'common','enable','eu','Gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(29,'common','disable','en','Disable','2026-05-06 15:40:30','2026-05-06 15:40:30'),(30,'common','disable','ar','تعطيل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(31,'common','disable','ur','غیر فعال','2026-05-06 15:40:30','2026-05-06 15:40:30'),(32,'common','disable','eu','Desgaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(33,'common','setDefault','en','Set Default','2026-05-06 15:40:30','2026-05-06 15:40:30'),(34,'common','setDefault','ar','تحديد افتراضي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(35,'common','setDefault','ur','ڈیفالٹ سیٹ کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(36,'common','setDefault','eu','Ezarri lehenetsia','2026-05-06 15:40:30','2026-05-06 15:40:30'),(37,'common','actions','en','Actions','2026-05-06 15:40:30','2026-05-06 15:40:30'),(38,'common','actions','ar','الإجراءات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(39,'common','actions','ur','کارروائیاں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(40,'common','actions','eu','Ekintzak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(41,'common','loading','en','Loading...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(42,'common','loading','ar','جاري التحميل...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(43,'common','loading','ur','لوڈ ہو رہا ہے...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(44,'common','loading','eu','Kargatzen...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(45,'common','code','en','Code','2026-05-06 15:40:30','2026-05-06 15:40:30'),(46,'common','code','ar','الرمز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(47,'common','code','ur','کوڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(48,'common','code','eu','Kodea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(49,'common','name','en','Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(50,'common','name','ar','الاسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(51,'common','name','ur','نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(52,'common','name','eu','Izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(53,'common','nativeName','en','Native Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(54,'common','nativeName','ar','الاسم الأصلي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(55,'common','nativeName','ur','本 地 نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(56,'common','nativeName','eu','Jatorrizko izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(57,'common','direction','en','Direction','2026-05-06 15:40:30','2026-05-06 15:40:30'),(58,'common','direction','ar','الاتجاه','2026-05-06 15:40:30','2026-05-06 15:40:30'),(59,'common','direction','ur','سمت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(60,'common','direction','eu','Norabidea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(61,'common','status','en','Status','2026-05-06 15:40:30','2026-05-06 15:40:30'),(62,'common','status','ar','الحالة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(63,'common','status','ur','حالت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(64,'common','status','eu','Egoera','2026-05-06 15:40:30','2026-05-06 15:40:30'),(65,'common','common.add','en','Add','2026-05-06 15:40:30','2026-05-06 15:40:30'),(66,'common','common.add','ar','إضافة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(67,'common','common.add','ur','شامل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(68,'common','common.add','eu','Gehitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(69,'common','common.edit','en','Edit','2026-05-06 15:40:30','2026-05-06 15:40:30'),(70,'common','common.edit','ar','تعديل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(71,'common','common.edit','ur','ترمیم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(72,'common','common.edit','eu','Editatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(73,'common','common.delete','en','Delete','2026-05-06 15:40:30','2026-05-06 15:40:30'),(74,'common','common.delete','ar','حذف','2026-05-06 15:40:30','2026-05-06 15:40:30'),(75,'common','common.delete','ur','حذف کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(76,'common','common.delete','eu','Ezabatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(77,'common','common.save','en','Save','2026-05-06 15:40:30','2026-05-06 15:40:30'),(78,'common','common.save','ar','حفظ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(79,'common','common.save','ur','محفوظ کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(80,'common','common.save','eu','Gorde','2026-05-06 15:40:30','2026-05-06 15:40:30'),(81,'common','common.cancel','en','Cancel','2026-05-06 15:40:30','2026-05-06 15:40:30'),(82,'common','common.cancel','ar','إلغاء','2026-05-06 15:40:30','2026-05-06 15:40:30'),(83,'common','common.cancel','ur','منسوخ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(84,'common','common.cancel','eu','Ezeztatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(85,'common','common.update','en','Update','2026-05-06 15:40:30','2026-05-06 15:40:30'),(86,'common','common.update','ar','تحديث','2026-05-06 15:40:30','2026-05-06 15:40:30'),(87,'common','common.update','ur','اپڈیٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(88,'common','common.update','eu','Eguneratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(89,'common','common.enable','en','Enable','2026-05-06 15:40:30','2026-05-06 15:40:30'),(90,'common','common.enable','ar','تفعيل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(91,'common','common.enable','ur','فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(92,'common','common.enable','eu','Gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(93,'common','common.disable','en','Disable','2026-05-06 15:40:30','2026-05-06 15:40:30'),(94,'common','common.disable','ar','تعطيل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(95,'common','common.disable','ur','غیر فعال','2026-05-06 15:40:30','2026-05-06 15:40:30'),(96,'common','common.disable','eu','Desgaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(97,'common','common.setDefault','en','Set Default','2026-05-06 15:40:30','2026-05-06 15:40:30'),(98,'common','common.setDefault','ar','تحديد افتراضي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(99,'common','common.setDefault','ur','ڈیفالٹ سیٹ کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(100,'common','common.setDefault','eu','Ezarri lehenetsia','2026-05-06 15:40:30','2026-05-06 15:40:30'),(101,'common','common.actions','en','Actions','2026-05-06 15:40:30','2026-05-06 15:40:30'),(102,'common','common.actions','ar','الإجراءات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(103,'common','common.actions','ur','کارروائیاں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(104,'common','common.actions','eu','Ekintzak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(105,'common','common.loading','en','Loading...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(106,'common','common.loading','ar','جاري التحميل...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(107,'common','common.loading','ur','لوڈ ہو رہا ہے...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(108,'common','common.loading','eu','Kargatzen...','2026-05-06 15:40:30','2026-05-06 15:40:30'),(109,'common','common.code','en','Code','2026-05-06 15:40:30','2026-05-06 15:40:30'),(110,'common','common.code','ar','الرمز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(111,'common','common.code','ur','کوڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(112,'common','common.code','eu','Kodea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(113,'common','common.name','en','Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(114,'common','common.name','ar','الاسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(115,'common','common.name','ur','نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(116,'common','common.name','eu','Izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(117,'common','common.nativeName','en','Native Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(118,'common','common.nativeName','ar','الاسم الأصلي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(119,'common','common.nativeName','ur','本 ��� نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(120,'common','common.nativeName','eu','Jatorrizko izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(121,'common','common.direction','en','Direction','2026-05-06 15:40:30','2026-05-06 15:40:30'),(122,'common','common.direction','ar','الاتجاه','2026-05-06 15:40:30','2026-05-06 15:40:30'),(123,'common','common.direction','ur','سمت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(124,'common','common.direction','eu','Norabidea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(125,'common','common.status','en','Status','2026-05-06 15:40:30','2026-05-06 15:40:30'),(126,'common','common.status','ar','الحالة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(127,'common','common.status','ur','حالت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(128,'common','common.status','eu','Egoera','2026-05-06 15:40:30','2026-05-06 15:40:30'),(129,'dashboard','title','en','Dashboard','2026-05-06 15:40:30','2026-05-06 15:40:30'),(130,'dashboard','title','ar','لوحة التحكم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(131,'dashboard','title','ur','ڈیش بورڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(132,'dashboard','title','eu','Azpiegitura','2026-05-06 15:40:30','2026-05-06 15:40:30'),(133,'dashboard','totalAnimals','en','Total Animals','2026-05-06 15:40:30','2026-05-06 15:40:30'),(134,'dashboard','totalAnimals','ar','إجمالي الحيوانات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(135,'dashboard','totalAnimals','ur','کل جانور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(136,'dashboard','totalAnimals','eu','Animalia kopurua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(137,'dashboard','activeAlerts','en','Active Alerts','2026-05-06 15:40:30','2026-05-06 15:40:30'),(138,'dashboard','activeAlerts','ar','التنبيهات النشطة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(139,'dashboard','activeAlerts','ur','فعال الرسلے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(140,'dashboard','activeAlerts','eu','Alerta aktiboak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(141,'dashboard','grazingZones','en','Grazing Zones','2026-05-06 15:40:30','2026-05-06 15:40:30'),(142,'dashboard','grazingZones','ar','مراعي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(143,'dashboard','grazingZones','ur','چرنے والے علاقے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(144,'dashboard','grazingZones','eu','Larreetan','2026-05-06 15:40:30','2026-05-06 15:40:30'),(145,'dashboard','pendingTasks','en','Pending Tasks','2026-05-06 15:40:30','2026-05-06 15:40:30'),(146,'dashboard','pendingTasks','ar','المهام المعلقة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(147,'dashboard','pendingTasks','ur','زیر کار کام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(148,'dashboard','pendingTasks','eu','Zereginak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(149,'animals','title','en','Animals','2026-05-06 15:40:30','2026-05-06 15:40:30'),(150,'animals','title','ar','الحيوانات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(151,'animals','title','ur','جانور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(152,'animals','title','eu','Animaliak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(153,'animals','addAnimal','en','Add Animal','2026-05-06 15:40:30','2026-05-06 15:40:30'),(154,'animals','addAnimal','ar','إضافة حيوان','2026-05-06 15:40:30','2026-05-06 15:40:30'),(155,'animals','addAnimal','ur','جانور شامل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(156,'animals','addAnimal','eu','Animalia gehitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(157,'animals','editAnimal','en','Edit Animal','2026-05-06 15:40:30','2026-05-06 15:40:30'),(158,'animals','editAnimal','ar','تعديل الحيوان','2026-05-06 15:40:30','2026-05-06 15:40:30'),(159,'animals','editAnimal','ur','جانور ترمیم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(160,'animals','editAnimal','eu','Animalia editatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(161,'animals','name','en','Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(162,'animals','name','ar','الاسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(163,'animals','name','ur','نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(164,'animals','name','eu','Izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(165,'animals','species','en','Species','2026-05-06 15:40:30','2026-05-06 15:40:30'),(166,'animals','species','ar','النوع','2026-05-06 15:40:30','2026-05-06 15:40:30'),(167,'animals','species','ur','قسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(168,'animals','species','eu','Speziea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(169,'animals','breed','en','Breed','2026-05-06 15:40:30','2026-05-06 15:40:30'),(170,'animals','breed','ar','السلالة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(171,'animals','breed','ur','نسل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(172,'animals','breed','eu','Arraza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(173,'animals','status','en','Status','2026-05-06 15:40:30','2026-05-06 15:40:30'),(174,'animals','status','ar','الحالة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(175,'animals','status','ur','حالت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(176,'animals','status','eu','Egoera','2026-05-06 15:40:30','2026-05-06 15:40:30'),(177,'animals','age','en','Age','2026-05-06 15:40:30','2026-05-06 15:40:30'),(178,'animals','age','ar','العمر','2026-05-06 15:40:30','2026-05-06 15:40:30'),(179,'animals','age','ur','عمر','2026-05-06 15:40:30','2026-05-06 15:40:30'),(180,'animals','age','eu','Adina','2026-05-06 15:40:30','2026-05-06 15:40:30'),(181,'animals','weight','en','Weight','2026-05-06 15:40:30','2026-05-06 15:40:30'),(182,'animals','weight','ar','الوزن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(183,'animals','weight','ur','وزن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(184,'animals','weight','eu','Pisua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(185,'devices','title','en','Devices','2026-05-06 15:40:30','2026-05-06 15:40:30'),(186,'devices','title','ar','الأجهزة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(187,'devices','title','ur','آلات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(188,'devices','title','eu','Gailuak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(189,'devices','addDevice','en','Add Device','2026-05-06 15:40:30','2026-05-06 15:40:30'),(190,'devices','addDevice','ar','إضافة جهاز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(191,'devices','addDevice','ur','آلہ شامل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(192,'devices','addDevice','eu','Gailua gehitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(193,'devices','deviceId','en','Device ID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(194,'devices','deviceId','ar','معرف الجهاز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(195,'devices','deviceId','ur','آلہ کی شناخت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(196,'devices','deviceId','eu','Gailuaren ID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(197,'devices','batteryLevel','en','Battery Level','2026-05-06 15:40:30','2026-05-06 15:40:30'),(198,'devices','batteryLevel','ar','مستوى البطارية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(199,'devices','batteryLevel','ur','بیٹری کی سطح','2026-05-06 15:40:30','2026-05-06 15:40:30'),(200,'devices','batteryLevel','eu','Bateria maila','2026-05-06 15:40:30','2026-05-06 15:40:30'),(201,'devices','firmware','en','Firmware','2026-05-06 15:40:30','2026-05-06 15:40:30'),(202,'devices','firmware','ar','البرنامج الثابت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(203,'devices','firmware','ur',' فرم ویئر','2026-05-06 15:40:30','2026-05-06 15:40:30'),(204,'devices','firmware','eu','Firmwarea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(205,'auth','login','en','Sign In','2026-05-06 15:40:30','2026-05-06 15:40:30'),(206,'auth','login','ar','تسجيل الدخول','2026-05-06 15:40:30','2026-05-06 15:40:30'),(207,'auth','login','ur','لاگ ان','2026-05-06 15:40:30','2026-05-06 15:40:30'),(208,'auth','login','eu','Saioa hasi','2026-05-06 15:40:30','2026-05-06 15:40:30'),(209,'auth','register','en','Sign Up','2026-05-06 15:40:30','2026-05-06 15:40:30'),(210,'auth','register','ar','التسجيل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(211,'auth','register','ur','رجسٹر','2026-05-06 15:40:30','2026-05-06 15:40:30'),(212,'auth','register','eu','Erregistratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(213,'auth','welcomeBack','en','Welcome Back','2026-05-06 15:40:30','2026-05-06 15:40:30'),(214,'auth','welcomeBack','ar','مرحباً بعودتك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(215,'auth','welcomeBack','ur','واپس خوش آمدید','2026-05-06 15:40:30','2026-05-06 15:40:30'),(216,'auth','welcomeBack','eu','Ongi itzuli','2026-05-06 15:40:30','2026-05-06 15:40:30'),(217,'auth','loginSubtitle','en','Sign in to continue monitoring your livestock','2026-05-06 15:40:30','2026-05-06 15:40:30'),(218,'auth','loginSubtitle','ar','سجل للمتابعة مراقبة ماشيتك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(219,'auth','loginSubtitle','ur','اپنے مویشیوں کی نگرانی جاری رکھنے کے لیے لاگ ان کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(220,'auth','loginSubtitle','eu','Saioa hasi zure abereak ikusten jarraitzeko','2026-05-06 15:40:30','2026-05-06 15:40:30'),(221,'auth','email','en','Email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(222,'auth','email','ar','البريد الإلكتروني','2026-05-06 15:40:30','2026-05-06 15:40:30'),(223,'auth','email','ur','ای میل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(224,'auth','email','eu','Posta elektronikoa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(225,'auth','password','en','Password','2026-05-06 15:40:30','2026-05-06 15:40:30'),(226,'auth','password','ar','كلمة المرور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(227,'auth','password','ur','پاس ورڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(228,'auth','password','eu','Pasahitza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(229,'auth','confirmPassword','en','Confirm Password','2026-05-06 15:40:30','2026-05-06 15:40:30'),(230,'auth','confirmPassword','ar','تأكيد كلمة المرور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(231,'auth','confirmPassword','ur','پاس ورڈ کی تصدیق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(232,'auth','confirmPassword','eu','Pasahitza berretsi','2026-05-06 15:40:30','2026-05-06 15:40:30'),(233,'auth','rememberMe','en','Remember me','2026-05-06 15:40:30','2026-05-06 15:40:30'),(234,'auth','rememberMe','ar','تذكرني','2026-05-06 15:40:30','2026-05-06 15:40:30'),(235,'auth','rememberMe','ur','مجھے یاد رکھیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(236,'auth','rememberMe','eu','Gogorau','2026-05-06 15:40:30','2026-05-06 15:40:30'),(237,'auth','forgotPassword','en','Forgot Password?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(238,'auth','forgotPassword','ar','هل نسيت كلمة المرور؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(239,'auth','forgotPassword','ur','پاس ورڈ بھول گئے؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(240,'auth','forgotPassword','eu','Pasahitza ahaztu?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(241,'auth','noAccount','en','Don\'t have an account?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(242,'auth','noAccount','ar','ليس لديك حساب؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(243,'auth','noAccount','ur','کیا آپ کا اکاؤنٹ نہیں ہے؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(244,'auth','noAccount','eu','Ez daukazu konturik?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(245,'auth','haveAccount','en','Already have an account?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(246,'auth','haveAccount','ar','لديك حساب بالفعل؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(247,'auth','haveAccount','ur','کیا آپ کا پہلے سے اکاؤنٹ ہے؟','2026-05-06 15:40:30','2026-05-06 15:40:30'),(248,'auth','haveAccount','eu','Daukazu dagoeneko konturik?','2026-05-06 15:40:30','2026-05-06 15:40:30'),(249,'auth','logout','en','Logout','2026-05-06 15:40:30','2026-05-06 15:40:30'),(250,'auth','logout','ar','تسجيل الخروج','2026-05-06 15:40:30','2026-05-06 15:40:30'),(251,'auth','logout','ur','لاگ آؤٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(252,'auth','logout','eu','Saioa amaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(253,'auth','enterEmail','en','Enter your email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(254,'auth','enterEmail','ar','أدخل بريدك الإلكتروني','2026-05-06 15:40:30','2026-05-06 15:40:30'),(255,'auth','enterEmail','ur','اپنا ایمیل درج کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(256,'auth','enterEmail','eu','Sartu zure posta elektronikoa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(257,'auth','enterPassword','en','Enter your password','2026-05-06 15:40:30','2026-05-06 15:40:30'),(258,'auth','enterPassword','ar','أدخل كلمة المرور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(259,'auth','enterPassword','ur','اپنا پاس ورڈ درج کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(260,'auth','enterPassword','eu','Sartu zure pasahitza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(261,'auth','emailRequired','en','Email is required','2026-05-06 15:40:30','2026-05-06 15:40:30'),(262,'auth','emailRequired','ar','البريد الإلكتروني مطلوب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(263,'auth','emailRequired','ur','ای میل ضروری ہے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(264,'auth','emailRequired','eu','Posta elektronikoa beharrezkoa da','2026-05-06 15:40:30','2026-05-06 15:40:30'),(265,'auth','invalidEmail','en','Enter a valid email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(266,'auth','invalidEmail','ar','أدخل بريد إلكتروني صالح','2026-05-06 15:40:30','2026-05-06 15:40:30'),(267,'auth','invalidEmail','ur','درست ایمیل درج کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(268,'auth','invalidEmail','eu','Sartu posta elektroniko bali bat','2026-05-06 15:40:30','2026-05-06 15:40:30'),(269,'auth','passwordRequired','en','Password is required','2026-05-06 15:40:30','2026-05-06 15:40:30'),(270,'auth','passwordRequired','ar','كلمة المرور مطلوبة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(271,'auth','passwordRequired','ur','پاس ورڈ ضروری ہے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(272,'auth','passwordRequired','eu','Pasahitza beharrezkoa da','2026-05-06 15:40:30','2026-05-06 15:40:30'),(273,'auth','passwordMinLength','en','Password must be at least 4 characters','2026-05-06 15:40:30','2026-05-06 15:40:30'),(274,'auth','passwordMinLength','ar','يجب أن تكون كلمة المرور 4 أحرف على الأقل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(275,'auth','passwordMinLength','ur','پاس ورڈ میں کم از کم 4 حروف ہونے چاہییے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(276,'auth','passwordMinLength','eu','Pasahitzak 4 karaktere izan behar ditu gutxieneko','2026-05-06 15:40:30','2026-05-06 15:40:30'),(277,'nav','dashboard','en','Dashboard','2026-05-06 15:40:30','2026-05-06 15:40:30'),(278,'nav','dashboard','ar','لوحة التحكم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(279,'nav','dashboard','ur','ڈیش بورڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(280,'nav','dashboard','eu','Azpiegitura','2026-05-06 15:40:30','2026-05-06 15:40:30'),(281,'nav','users','en','Users','2026-05-06 15:40:30','2026-05-06 15:40:30'),(282,'nav','users','ar','المستخدمون','2026-05-06 15:40:30','2026-05-06 15:40:30'),(283,'nav','users','ur','صارفین','2026-05-06 15:40:30','2026-05-06 15:40:30'),(284,'nav','users','eu','Erabiltzaileak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(285,'nav','animals','en','Animals','2026-05-06 15:40:30','2026-05-06 15:40:30'),(286,'nav','animals','ar','الحيوانات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(287,'nav','animals','ur','جانور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(288,'nav','animals','eu','Animaliak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(289,'nav','geofences','en','Geofences','2026-05-06 15:40:30','2026-05-06 15:40:30'),(290,'nav','geofences','ar','الأسوار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(291,'nav','geofences','ur','جغرافیائی حدود','2026-05-06 15:40:30','2026-05-06 15:40:30'),(292,'nav','geofences','eu','Geofenceak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(293,'nav','medicalRecords','en','Medical Records','2026-05-06 15:40:30','2026-05-06 15:40:30'),(294,'nav','medicalRecords','ar','السجلات الطبية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(295,'nav','medicalRecords','ur','میڈیکل ریکارڈز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(296,'nav','medicalRecords','eu','Eraso medikuak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(297,'nav','vaccinations','en','Vaccinations','2026-05-06 15:40:30','2026-05-06 15:40:30'),(298,'nav','vaccinations','ar','التلقيحات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(299,'nav','vaccinations','ur','ویکسینیشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(300,'nav','vaccinations','eu','Txertaketak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(301,'nav','devices','en','Devices','2026-05-06 15:40:30','2026-05-06 15:40:30'),(302,'nav','devices','ar','الأجهزة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(303,'nav','devices','ur','آلات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(304,'nav','devices','eu','Gailuak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(305,'nav','mapView','en','Map View','2026-05-06 15:40:30','2026-05-06 15:40:30'),(306,'nav','mapView','ar','عرض الخريطة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(307,'nav','mapView','ur','نقشہ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(308,'nav','mapView','eu','Mapa ikuspegia','2026-05-06 15:40:30','2026-05-06 15:40:30'),(309,'nav','auctions','en','Auctions','2026-05-06 15:40:30','2026-05-06 15:40:30'),(310,'nav','auctions','ar','المزادات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(311,'nav','auctions','ur','مزاد','2026-05-06 15:40:30','2026-05-06 15:40:30'),(312,'nav','auctions','eu','Molkak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(313,'nav','alerts','en','Alerts','2026-05-06 15:40:30','2026-05-06 15:40:30'),(314,'nav','alerts','ar','التنبيهات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(315,'nav','alerts','ur','الرسلے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(316,'nav','alerts','eu','Alertsak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(317,'nav','tasks','en','Tasks','2026-05-06 15:40:30','2026-05-06 15:40:30'),(318,'nav','tasks','ar','المهام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(319,'nav','tasks','ur','کام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(320,'nav','tasks','eu','Zereginak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(321,'nav','reports','en','Reports','2026-05-06 15:40:30','2026-05-06 15:40:30'),(322,'nav','reports','ar','التقارير','2026-05-06 15:40:30','2026-05-06 15:40:30'),(323,'nav','reports','ur','رپورٹس','2026-05-06 15:40:30','2026-05-06 15:40:30'),(324,'nav','reports','eu','Txostenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(325,'nav','addNewEntry','en','Add New','2026-05-06 15:40:30','2026-05-06 15:40:30'),(326,'nav','addNewEntry','ar','إضافة جديد','2026-05-06 15:40:30','2026-05-06 15:40:30'),(327,'nav','addNewEntry','ur','نیا انٹری','2026-05-06 15:40:30','2026-05-06 15:40:30'),(328,'nav','addNewEntry','eu','Gehitu berria','2026-05-06 15:40:30','2026-05-06 15:40:30'),(329,'nav','settings','en','Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(330,'nav','settings','ar','الإعدادات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(331,'nav','settings','ur','سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(332,'nav','settings','eu','Ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(333,'nav','profile','en','Profile','2026-05-06 15:40:30','2026-05-06 15:40:30'),(334,'nav','profile','ar','الملف الشخصي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(335,'nav','profile','ur','پروفائل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(336,'nav','profile','eu','Profila','2026-05-06 15:40:30','2026-05-06 15:40:30'),(337,'nav','team','en','Team','2026-05-06 15:40:30','2026-05-06 15:40:30'),(338,'nav','team','ar','الفريق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(339,'nav','team','ur','ٹیم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(340,'nav','team','eu','Taldea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(341,'settings','title','en','Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(342,'settings','title','ar','الإعدادات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(343,'settings','title','ur','سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(344,'settings','title','eu','Ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(345,'settings','account','en','Account','2026-05-06 15:40:30','2026-05-06 15:40:30'),(346,'settings','account','ar','الحساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(347,'settings','account','ur','اکاؤنٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(348,'settings','account','eu','Kontua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(349,'settings','notifications','en','Notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(350,'settings','notifications','ar','الإشعارات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(351,'settings','notifications','ur','الرسلے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(352,'settings','notifications','eu','Jakinarazpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(353,'settings','appSettings','en','App Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(354,'settings','appSettings','ar','إعدادات التطبيق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(355,'settings','appSettings','ur','ایپ سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(356,'settings','appSettings','eu','Apparen ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(357,'settings','about','en','About','2026-05-06 15:40:30','2026-05-06 15:40:30'),(358,'settings','about','ar','حول','2026-05-06 15:40:30','2026-05-06 15:40:30'),(359,'settings','about','ur','کے بارے میں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(360,'settings','about','eu','Honi buruz','2026-05-06 15:40:30','2026-05-06 15:40:30'),(361,'settings','pushNotifications','en','Push Notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(362,'settings','pushNotifications','ar','إشعارات فورية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(363,'settings','pushNotifications','ur','پش نوٹیفیکیشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(364,'settings','pushNotifications','eu','Push jakinarazpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(365,'settings','pushNotificationsSubtitle','en','Receive alerts on your device','2026-05-06 15:40:30','2026-05-06 15:40:30'),(366,'settings','pushNotificationsSubtitle','ar','تلقي التنبيهات على جهازك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(367,'settings','pushNotificationsSubtitle','ur','اپنے آلہ پر الرسلے وصول کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(368,'settings','pushNotificationsSubtitle','eu','Alertsak jaso zure gailuan','2026-05-06 15:40:30','2026-05-06 15:40:30'),(369,'settings','emailNotifications','en','Email Notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(370,'settings','emailNotifications','ar','إشعارات البريد الإلكتروني','2026-05-06 15:40:30','2026-05-06 15:40:30'),(371,'settings','emailNotifications','ur','ای میل نوٹیفیکیشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(372,'settings','emailNotifications','eu','Posta jakinarazpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(373,'settings','emailNotificationsSubtitle','en','Receive updates via email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(374,'settings','emailNotificationsSubtitle','ar','تلقي التحديثات عبر البريد','2026-05-06 15:40:30','2026-05-06 15:40:30'),(375,'settings','emailNotificationsSubtitle','ur','ای میل کے ذریعے اپڈیٹس حاصل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(376,'settings','emailNotificationsSubtitle','eu','Eguneratzeak posta bidali','2026-05-06 15:40:30','2026-05-06 15:40:30'),(377,'settings','darkMode','en','Dark Mode','2026-05-06 15:40:30','2026-05-06 15:40:30'),(378,'settings','darkMode','ar','الوضع الداكن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(379,'settings','darkMode','ur','ڈارک موڈ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(380,'settings','darkMode','eu','Ilun modua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(381,'settings','darkModeSubtitle','en','Use dark theme','2026-05-06 15:40:30','2026-05-06 15:40:30'),(382,'settings','darkModeSubtitle','ar','استخدم السمة الداكنة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(383,'settings','darkModeSubtitle','ur','ڈارک تھیم کا use کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(384,'settings','darkModeSubtitle','eu','Ilun gaia erabili','2026-05-06 15:40:30','2026-05-06 15:40:30'),(385,'settings','locationTracking','en','Location Tracking','2026-05-06 15:40:30','2026-05-06 15:40:30'),(386,'settings','locationTracking','ar','تتبع الموقع','2026-05-06 15:40:30','2026-05-06 15:40:30'),(387,'settings','locationTracking','ur','لوکیشن ٹریکنگ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(388,'settings','locationTracking','eu','Kokapena jarraitzea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(389,'settings','locationTrackingSubtitle','en','Track animal locations','2026-05-06 15:40:30','2026-05-06 15:40:30'),(390,'settings','locationTrackingSubtitle','ar','تتبع مواقع الحيوانات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(391,'settings','locationTrackingSubtitle','ur','جانورں کے مقامات ٹریک کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(392,'settings','locationTrackingSubtitle','eu','Animalien kokapena jarraitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(393,'settings','temperatureUnit','en','Temperature Unit','2026-05-06 15:40:30','2026-05-06 15:40:30'),(394,'settings','temperatureUnit','ar','وحدة الحرارة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(395,'settings','temperatureUnit','ur','درجہ حرارت کی اکائی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(396,'settings','temperatureUnit','eu','Tenperatura unitatea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(397,'settings','language','en','Language','2026-05-06 15:40:30','2026-05-06 15:40:30'),(398,'settings','language','ar','اللغة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(399,'settings','language','ur','زبان','2026-05-06 15:40:30','2026-05-06 15:40:30'),(400,'settings','language','eu','Hizkuntza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(401,'settings','languages','en','Languages','2026-05-06 15:40:30','2026-05-06 15:40:30'),(402,'settings','languages','ar','اللغات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(403,'settings','languages','ur','زبانیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(404,'settings','languages','eu','Hizkuntzak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(405,'settings','roles','en','Roles','2026-05-06 15:40:30','2026-05-06 15:40:30'),(406,'settings','roles','ar','الأدوار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(407,'settings','roles','ur','کردار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(408,'settings','roles','eu','Rolak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(409,'settings','roleSettings','en','Role Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(410,'settings','roleSettings','ar','إعدادات الأدوار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(411,'settings','roleSettings','ur','کردار کی سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(412,'settings','roleSettings','eu','Rol ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(413,'settings','roleDescription','en','Manage roles and permissions','2026-05-06 15:40:30','2026-05-06 15:40:30'),(414,'settings','roleDescription','ar','إدارة الأدوار والصلاحيات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(415,'settings','roleDescription','ur','کردار اور اجازتیں کا انتظام کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(416,'settings','roleDescription','eu','Rolak eta baimenak kudeatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(417,'settings','existingRoles','en','Existing Roles','2026-05-06 15:40:30','2026-05-06 15:40:30'),(418,'settings','existingRoles','ar','الأدوار الموجودة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(419,'settings','existingRoles','ur','موجودہ کردار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(420,'settings','existingRoles','eu','Dagoeneko rolak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(421,'settings','languageSettings','en','Language Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(422,'settings','languageSettings','ar','إعدادات اللغة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(423,'settings','languageSettings','ur','زبان کی سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(424,'settings','languageSettings','eu','Hizkuntza ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(425,'settings','languageDescription','en','Manage system languages and translations','2026-05-06 15:40:30','2026-05-06 15:40:30'),(426,'settings','languageDescription','ar','إدارة لغات النظام والترجمات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(427,'settings','languageDescription','ur','سسٹم کی زبانیں اور ترجمے کا انتظام کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(428,'settings','languageDescription','eu','Sistemaren hizkuntzak eta itzulpenak kudeatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(429,'settings','manageTranslations','en','Manage Translations','2026-05-06 15:40:30','2026-05-06 15:40:30'),(430,'settings','manageTranslations','ar','إدارة الترجمات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(431,'settings','manageTranslations','ur','ترجمے کا انتظام کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(432,'settings','manageTranslations','eu','Itzulpenak kudeatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(433,'settings','general','en','General','2026-05-06 15:40:30','2026-05-06 15:40:30'),(434,'settings','general','ar','عام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(435,'settings','general','ur','عام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(436,'settings','general','eu','Orokorra','2026-05-06 15:40:30','2026-05-06 15:40:30'),(437,'settings','smtp','en','Email (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(438,'settings','smtp','ar','البريد (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(439,'settings','smtp','ur','ای میل (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(440,'settings','smtp','eu',' posta (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(441,'settings','stripe','en','Payments (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(442,'settings','stripe','ar','المدفوعات (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(443,'settings','stripe','ur','ادائیگی (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(444,'settings','stripe','eu','Ordainketak (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(445,'settings','gemini','en','AI (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(446,'settings','gemini','ar','الذكاء الاصطناعي (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(447,'settings','gemini','ur','AI (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(448,'settings','gemini','eu','AI (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(449,'settings','whatsapp','en','WhatsApp','2026-05-06 15:40:30','2026-05-06 15:40:30'),(450,'settings','whatsapp','ar','واتساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(451,'settings','whatsapp','ur','واٹس ایپ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(452,'settings','whatsapp','eu','WhatsApp','2026-05-06 15:40:30','2026-05-06 15:40:30'),(453,'settings','twilio','en','SMS (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(454,'settings','twilio','ar','الرسائل (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(455,'settings','twilio','ur','ایس ایم ایس (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(456,'settings','twilio','eu','SMS (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(457,'settings','generalSettings','en','General Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(458,'settings','generalSettings','ar','الإعدادات العامة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(459,'settings','generalSettings','ur','عام سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(460,'settings','generalSettings','eu','Ezarpen orokorrak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(461,'settings','generalDescription','en','Configure basic platform settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(462,'settings','generalDescription','ar','تكوين إعدادات المنصة الأساسية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(463,'settings','generalDescription','ur','بنیادی پلیٹ فارم سیٹنگز کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(464,'settings','generalDescription','eu','Oinarrizko plataforma ezarpenak konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(465,'settings','platformName','en','Platform Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(466,'settings','platformName','ar','اسم المنصة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(467,'settings','platformName','ur','پلیٹ فارم کا نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(468,'settings','platformName','eu','Plataformaren izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(469,'settings','platformUrl','en','Platform URL','2026-05-06 15:40:30','2026-05-06 15:40:30'),(470,'settings','platformUrl','ar','رابط المنصة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(471,'settings','platformUrl','ur','پلیٹ فارم کا یو آر ایل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(472,'settings','platformUrl','eu','Plataformaren URLa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(473,'settings','adminEmail','en','Admin Email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(474,'settings','adminEmail','ar','بريد المسؤول','2026-05-06 15:40:30','2026-05-06 15:40:30'),(475,'settings','adminEmail','ur','ایڈمن ای میل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(476,'settings','adminEmail','eu','Admin posta','2026-05-06 15:40:30','2026-05-06 15:40:30'),(477,'settings','timezone','en','Timezone','2026-05-06 15:40:30','2026-05-06 15:40:30'),(478,'settings','timezone','ar','المنطقة الزمنية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(479,'settings','timezone','ur','ٹائم زون','2026-05-06 15:40:30','2026-05-06 15:40:30'),(480,'settings','timezone','eu','Ordu-eremua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(481,'settings','dateFormat','en','Date Format','2026-05-06 15:40:30','2026-05-06 15:40:30'),(482,'settings','dateFormat','ar','تنسيق التاريخ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(483,'settings','dateFormat','ur','تاریخ کی شکل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(484,'settings','dateFormat','eu','Data formatua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(485,'settings','defaultLanguage','en','Default Language','2026-05-06 15:40:30','2026-05-06 15:40:30'),(486,'settings','defaultLanguage','ar','اللغة الافتراضية','2026-05-06 15:40:30','2026-05-06 15:40:30'),(487,'settings','defaultLanguage','ur','ڈیفالٹ زبان','2026-05-06 15:40:30','2026-05-06 15:40:30'),(488,'settings','defaultLanguage','eu','Hizkuntza lehenetsia','2026-05-06 15:40:30','2026-05-06 15:40:30'),(489,'settings','saved','en','Settings saved','2026-05-06 15:40:30','2026-05-06 15:40:30'),(490,'settings','saved','ar','تم الحفظ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(491,'settings','saved','ur','سیٹنگز محفوظ ہو گئیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(492,'settings','saved','eu','Ezarpenak gordeak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(493,'settings','smtpSettings','en','Email Settings (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(494,'settings','smtpSettings','ar','إعدادات البريد','2026-05-06 15:40:30','2026-05-06 15:40:30'),(495,'settings','smtpSettings','ur','ای میل سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(496,'settings','smtpSettings','eu','Posta ezarpenak (SMTP)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(497,'settings','smtpDescription','en','Configure email server for notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(498,'settings','smtpDescription','ar','تكوين خادم البريد للإشعارات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(499,'settings','smtpDescription','ur','نوٹیفیکیشن کے لیے ای میل سرور کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(500,'settings','smtpDescription','eu','Jakinarazpenetarako posta zerbitzaria konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(501,'settings','smtpHost','en','SMTP Host','2026-05-06 15:40:30','2026-05-06 15:40:30'),(502,'settings','smtpHost','ar','مضيف SMTP','2026-05-06 15:40:30','2026-05-06 15:40:30'),(503,'settings','smtpHost','ur','SMTP_host','2026-05-06 15:40:30','2026-05-06 15:40:30'),(504,'settings','smtpHost','eu','SMTP ostalaria','2026-05-06 15:40:30','2026-05-06 15:40:30'),(505,'settings','port','en','Port','2026-05-06 15:40:30','2026-05-06 15:40:30'),(506,'settings','port','ar','المنفذ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(507,'settings','port','ur','پورٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(508,'settings','port','eu','Ataka','2026-05-06 15:40:30','2026-05-06 15:40:30'),(509,'settings','username','en','Username','2026-05-06 15:40:30','2026-05-06 15:40:30'),(510,'settings','username','ar','اسم المستخدم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(511,'settings','username','ur','یوزر نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(512,'settings','username','eu','Erabiltzaile izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(513,'settings','encryption','en','Encryption','2026-05-06 15:40:30','2026-05-06 15:40:30'),(514,'settings','encryption','ar','التشفير','2026-05-06 15:40:30','2026-05-06 15:40:30'),(515,'settings','encryption','ur','اینکرپشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(516,'settings','encryption','eu','Enkriptazioa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(517,'settings','fromEmail','en','From Email','2026-05-06 15:40:30','2026-05-06 15:40:30'),(518,'settings','fromEmail','ar','من البريد','2026-05-06 15:40:30','2026-05-06 15:40:30'),(519,'settings','fromEmail','ur','سے ای میل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(520,'settings','fromEmail','eu','Postatik','2026-05-06 15:40:30','2026-05-06 15:40:30'),(521,'settings','fromName','en','From Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(522,'settings','fromName','ar','من الاسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(523,'settings','fromName','ur','سے نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(524,'settings','fromName','eu','Izenetik','2026-05-06 15:40:30','2026-05-06 15:40:30'),(525,'settings','sendTest','en','Send Test','2026-05-06 15:40:30','2026-05-06 15:40:30'),(526,'settings','sendTest','ar','إرسال اختبار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(527,'settings','sendTest','ur','ٹیسٹ بھیجیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(528,'settings','sendTest','eu','Proba bidali','2026-05-06 15:40:30','2026-05-06 15:40:30'),(529,'settings','stripeSettings','en','Payment Settings (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(530,'settings','stripeSettings','ar','إعدادات الدفع','2026-05-06 15:40:30','2026-05-06 15:40:30'),(531,'settings','stripeSettings','ur','ادائیگی کی سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(532,'settings','stripeSettings','eu','Ordainketa ezarpenak (Stripe)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(533,'settings','stripeDescription','en','Configure payment processing','2026-05-06 15:40:30','2026-05-06 15:40:30'),(534,'settings','stripeDescription','ar','تكوين معالجة الدفع','2026-05-06 15:40:30','2026-05-06 15:40:30'),(535,'settings','stripeDescription','ur','ادائیگی کی پروسیسنگ کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(536,'settings','stripeDescription','eu','Ordainketa prozesamendua konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(537,'settings','enableStripe','en','Enable Payments','2026-05-06 15:40:30','2026-05-06 15:40:30'),(538,'settings','enableStripe','ar','تفعيل الدفع','2026-05-06 15:40:30','2026-05-06 15:40:30'),(539,'settings','enableStripe','ur','ادائیگی کو فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(540,'settings','enableStripe','eu','Ordainketak gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(541,'settings','publicKey','en','Public Key','2026-05-06 15:40:30','2026-05-06 15:40:30'),(542,'settings','publicKey','ar','المفتاح العام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(543,'settings','publicKey','ur','پبلک کی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(544,'settings','publicKey','eu','Giltza publikoa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(545,'settings','secretKey','en','Secret Key','2026-05-06 15:40:30','2026-05-06 15:40:30'),(546,'settings','secretKey','ar','المفتاح السري','2026-05-06 15:40:30','2026-05-06 15:40:30'),(547,'settings','secretKey','ur','سیکریٹ کی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(548,'settings','secretKey','eu','Giltza sekretua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(549,'settings','webhookSecret','en','Webhook Secret','2026-05-06 15:40:30','2026-05-06 15:40:30'),(550,'settings','webhookSecret','ar','سر webhook','2026-05-06 15:40:30','2026-05-06 15:40:30'),(551,'settings','webhookSecret','ur','ویب ہوک سیکریٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(552,'settings','webhookSecret','eu','Webhook sekretua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(553,'settings','geminiSettings','en','AI Settings (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(554,'settings','geminiSettings','ar','إعدادات الذكاء الاصطناعي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(555,'settings','geminiSettings','ur','AI سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(556,'settings','geminiSettings','eu','AI ezarpenak (Gemini)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(557,'settings','geminiDescription','en','Configure AI assistance','2026-05-06 15:40:30','2026-05-06 15:40:30'),(558,'settings','geminiDescription','ar','تكوين مساعدة الذكاء الاصطناعي','2026-05-06 15:40:30','2026-05-06 15:40:30'),(559,'settings','geminiDescription','ur','AI معاونت کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(560,'settings','geminiDescription','eu','AI laguntza konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(561,'settings','enableGemini','en','Enable AI Assistant','2026-05-06 15:40:30','2026-05-06 15:40:30'),(562,'settings','enableGemini','ar','تفعيل مساعد الذكاء','2026-05-06 15:40:30','2026-05-06 15:40:30'),(563,'settings','enableGemini','ur','AI اسسٹنٹ کو فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(564,'settings','enableGemini','eu','AI laguntzailea gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(565,'settings','geminiApiKey','en','API Key','2026-05-06 15:40:30','2026-05-06 15:40:30'),(566,'settings','geminiApiKey','ar','مفتاح API','2026-05-06 15:40:30','2026-05-06 15:40:30'),(567,'settings','geminiApiKey','ur','API کی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(568,'settings','geminiApiKey','eu','API giltza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(569,'settings','geminiModel','en','Model','2026-05-06 15:40:30','2026-05-06 15:40:30'),(570,'settings','geminiModel','ar','النموذج','2026-05-06 15:40:30','2026-05-06 15:40:30'),(571,'settings','geminiModel','ur','ماڈل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(572,'settings','geminiModel','eu','Eredua','2026-05-06 15:40:30','2026-05-06 15:40:30'),(573,'settings','whatsappSettings','en','WhatsApp Settings','2026-05-06 15:40:30','2026-05-06 15:40:30'),(574,'settings','whatsappSettings','ar','إعدادات واتساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(575,'settings','whatsappSettings','ur','واٹس ایپ سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(576,'settings','whatsappSettings','eu','WhatsApp ezarpenak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(577,'settings','whatsappDescription','en','Configure WhatsApp notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(578,'settings','whatsappDescription','ar','تكوين إشعارات واتساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(579,'settings','whatsappDescription','ur','واٹس ایپ نوٹیفیکیشن کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(580,'settings','whatsappDescription','eu','WhatsApp jakinarazpenak konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(581,'settings','enableWhatsapp','en','Enable WhatsApp','2026-05-06 15:40:30','2026-05-06 15:40:30'),(582,'settings','enableWhatsapp','ar','تفعيل واتساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(583,'settings','enableWhatsapp','ur','واٹس ایپ کو فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(584,'settings','enableWhatsapp','eu','WhatsApp gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(585,'settings','whatsappApiUrl','en','API URL','2026-05-06 15:40:30','2026-05-06 15:40:30'),(586,'settings','whatsappApiUrl','ar','رابط API','2026-05-06 15:40:30','2026-05-06 15:40:30'),(587,'settings','whatsappApiUrl','ur','API URL','2026-05-06 15:40:30','2026-05-06 15:40:30'),(588,'settings','whatsappApiUrl','eu','API URLa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(589,'settings','whatsappApiToken','en','API Token','2026-05-06 15:40:30','2026-05-06 15:40:30'),(590,'settings','whatsappApiToken','ar','رمز API','2026-05-06 15:40:30','2026-05-06 15:40:30'),(591,'settings','whatsappApiToken','ur','API ٹوکن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(592,'settings','whatsappApiToken','eu','API tokena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(593,'settings','whatsappPhoneId','en','Phone Number ID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(594,'settings','whatsappPhoneId','ar','معرف الرقم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(595,'settings','whatsappPhoneId','ur','فون نمبر آئیڈی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(596,'settings','whatsappPhoneId','eu','Telefono zenbakiaren IDa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(597,'settings','whatsappBusinessId','en','Business Account ID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(598,'settings','whatsappBusinessId','ar','معرف الحساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(599,'settings','whatsappBusinessId','ur','بزنس اکاؤنٹ آئیڈی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(600,'settings','whatsappBusinessId','eu','Negozio kontuaren IDa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(601,'settings','twilioSettings','en','SMS Settings (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(602,'settings','twilioSettings','ar','إعدادات الرسائل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(603,'settings','twilioSettings','ur','SMS سیٹنگز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(604,'settings','twilioSettings','eu','SMS ezarpenak (Twilio)','2026-05-06 15:40:30','2026-05-06 15:40:30'),(605,'settings','twilioDescription','en','Configure SMS notifications','2026-05-06 15:40:30','2026-05-06 15:40:30'),(606,'settings','twilioDescription','ar','تكوين إشعارات الرسائل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(607,'settings','twilioDescription','ur','SMS نوٹیفیکیشن کو ترتیب دیں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(608,'settings','twilioDescription','eu','SMS jakinarazpenak konfiguratu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(609,'settings','enableTwilio','en','Enable SMS','2026-05-06 15:40:30','2026-05-06 15:40:30'),(610,'settings','enableTwilio','ar','تفعيل الرسائل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(611,'settings','enableTwilio','ur','SMS کو فعال کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(612,'settings','enableTwilio','eu','SMS gaitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(613,'settings','twilioAccountSid','en','Account SID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(614,'settings','twilioAccountSid','ar','معرف الحساب','2026-05-06 15:40:30','2026-05-06 15:40:30'),(615,'settings','twilioAccountSid','ur','اکاؤنٹ SID','2026-05-06 15:40:30','2026-05-06 15:40:30'),(616,'settings','twilioAccountSid','eu','Kontu SIDa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(617,'settings','twilioAuthToken','en','Auth Token','2026-05-06 15:40:30','2026-05-06 15:40:30'),(618,'settings','twilioAuthToken','ar','رمز التحقق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(619,'settings','twilioAuthToken','ur','آتھ ٹوکن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(620,'settings','twilioAuthToken','eu','Auth tokena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(621,'settings','twilioPhoneNumber','en','Phone Number','2026-05-06 15:40:30','2026-05-06 15:40:30'),(622,'settings','twilioPhoneNumber','ar','رقم الهاتف','2026-05-06 15:40:30','2026-05-06 15:40:30'),(623,'settings','twilioPhoneNumber','ur','فون نمبر','2026-05-06 15:40:30','2026-05-06 15:40:30'),(624,'settings','twilioPhoneNumber','eu','Telefono zenbakia','2026-05-06 15:40:30','2026-05-06 15:40:30'),(625,'settings','exportDatabase','en','Export Database','2026-05-06 15:40:30','2026-05-06 15:40:30'),(626,'settings','exportDatabase','ar','تصدير قاعدة البيانات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(627,'settings','exportDatabase','ur','ڈیٹابیس ایکسپورٹ کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(628,'settings','exportDatabase','eu','Datu basea esportatu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(629,'users','title','en','Users','2026-05-06 15:40:30','2026-05-06 15:40:30'),(630,'users','title','ar','المستخدمون','2026-05-06 15:40:30','2026-05-06 15:40:30'),(631,'users','title','ur','صارفین','2026-05-06 15:40:30','2026-05-06 15:40:30'),(632,'users','title','eu','Erabiltzaileak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(633,'users','addUser','en','Add User','2026-05-06 15:40:30','2026-05-06 15:40:30'),(634,'users','addUser','ar','إضافة مستخدم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(635,'users','addUser','ur','صارف شامل کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(636,'users','addUser','eu','Erabiltzailea gehitu','2026-05-06 15:40:30','2026-05-06 15:40:30'),(637,'users','name','en','Name','2026-05-06 15:40:30','2026-05-06 15:40:30'),(638,'users','name','ar','الاسم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(639,'users','name','ur','نام','2026-05-06 15:40:30','2026-05-06 15:40:30'),(640,'users','name','eu','Izena','2026-05-06 15:40:30','2026-05-06 15:40:30'),(641,'users','role','en','Role','2026-05-06 15:40:30','2026-05-06 15:40:30'),(642,'users','role','ar','الدور','2026-05-06 15:40:30','2026-05-06 15:40:30'),(643,'users','role','ur','کردار','2026-05-06 15:40:30','2026-05-06 15:40:30'),(644,'users','role','eu','Funtzioa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(645,'users','subscription','en','Subscription','2026-05-06 15:40:30','2026-05-06 15:40:30'),(646,'users','subscription','ar','الاشتراك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(647,'users','subscription','ur','سبسکرپشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(648,'users','subscription','eu','Harpidetza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(649,'users','phone','en','Phone','2026-05-06 15:40:30','2026-05-06 15:40:30'),(650,'users','phone','ar','الهاتف','2026-05-06 15:40:30','2026-05-06 15:40:30'),(651,'users','phone','ur','فون','2026-05-06 15:40:30','2026-05-06 15:40:30'),(652,'users','phone','eu','Telefonoa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(653,'users','owner','en','Owner','2026-05-06 15:40:30','2026-05-06 15:40:30'),(654,'users','owner','ar','المالك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(655,'users','owner','ur','مالک','2026-05-06 15:40:30','2026-05-06 15:40:30'),(656,'users','owner','eu','Jabea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(657,'users','createdAt','en','Created','2026-05-06 15:40:30','2026-05-06 15:40:30'),(658,'users','createdAt','ar','تم الإنشاء','2026-05-06 15:40:30','2026-05-06 15:40:30'),(659,'users','createdAt','ur',' بنایا گیا','2026-05-06 15:40:30','2026-05-06 15:40:30'),(660,'users','createdAt','eu','Sortuta','2026-05-06 15:40:30','2026-05-06 15:40:30'),(661,'errors','unauthorized','en','Unauthorized','2026-05-06 15:40:30','2026-05-06 15:40:30'),(662,'errors','unauthorized','ar','غير مصرح','2026-05-06 15:40:30','2026-05-06 15:40:30'),(663,'errors','unauthorized','ur','غیر مجاز','2026-05-06 15:40:30','2026-05-06 15:40:30'),(664,'errors','unauthorized','eu','Baimendu gabe','2026-05-06 15:40:30','2026-05-06 15:40:30'),(665,'errors','serverError','en','Server Error','2026-05-06 15:40:30','2026-05-06 15:40:30'),(666,'errors','serverError','ar','خطأ في الخادم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(667,'errors','serverError','ur','سرور کی خرابی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(668,'errors','serverError','eu','Zerbitzarierrorea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(669,'errors','networkError','en','Network Error','2026-05-06 15:40:30','2026-05-06 15:40:30'),(670,'errors','networkError','ar','خطأ في الشبكة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(671,'errors','networkError','ur','نیٹ ورک کی خرابی','2026-05-06 15:40:30','2026-05-06 15:40:30'),(672,'errors','networkError','eu','Sare errorea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(673,'team','title','en','Team','2026-05-06 15:40:30','2026-05-06 15:40:30'),(674,'team','title','ar','الفريق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(675,'team','title','ur','ٹیم','2026-05-06 15:40:30','2026-05-06 15:40:30'),(676,'team','title','eu','Taldea','2026-05-06 15:40:30','2026-05-06 15:40:30'),(677,'team','teamMembers','en','Team Members','2026-05-06 15:40:30','2026-05-06 15:40:30'),(678,'team','teamMembers','ar','أعضاء الفريق','2026-05-06 15:40:30','2026-05-06 15:40:30'),(679,'team','teamMembers','ur','ٹیم ممبران','2026-05-06 15:40:30','2026-05-06 15:40:30'),(680,'team','teamMembers','eu','Taldekideak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(681,'alerts','title','en','Alerts','2026-05-06 15:40:30','2026-05-06 15:40:30'),(682,'alerts','title','ar','التنبيهات','2026-05-06 15:40:30','2026-05-06 15:40:30'),(683,'alerts','title','ur','الرسلے','2026-05-06 15:40:30','2026-05-06 15:40:30'),(684,'alerts','title','eu','Alertsak','2026-05-06 15:40:30','2026-05-06 15:40:30'),(685,'alerts','geofenceEntry','en','Geofence Entry','2026-05-06 15:40:30','2026-05-06 15:40:30'),(686,'alerts','geofenceEntry','ar','دخول المنطقة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(687,'alerts','geofenceEntry','ur','جیو فینس انٹری','2026-05-06 15:40:30','2026-05-06 15:40:30'),(688,'alerts','geofenceEntry','eu','Geofence sarrera','2026-05-06 15:40:30','2026-05-06 15:40:30'),(689,'alerts','geofenceExit','en','Geofence Exit','2026-05-06 15:40:30','2026-05-06 15:40:30'),(690,'alerts','geofenceExit','ar','خروج المنطقة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(691,'alerts','geofenceExit','ur','جیو فینس ایکسٹ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(692,'alerts','geofenceExit','eu','Geofence irteera','2026-05-06 15:40:30','2026-05-06 15:40:30'),(693,'alerts','temperature','en','Temperature','2026-05-06 15:40:30','2026-05-06 15:40:30'),(694,'alerts','temperature','ar','درجة الحرارة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(695,'alerts','temperature','ur','درجہ حرارت','2026-05-06 15:40:30','2026-05-06 15:40:30'),(696,'alerts','temperature','eu','Tenperatura','2026-05-06 15:40:30','2026-05-06 15:40:30'),(697,'alerts','deviceOffline','en','Device Offline','2026-05-06 15:40:30','2026-05-06 15:40:30'),(698,'alerts','deviceOffline','ar','الجهاز غير متصل','2026-05-06 15:40:30','2026-05-06 15:40:30'),(699,'alerts','deviceOffline','ur','آلہ آف لائن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(700,'alerts','deviceOffline','eu','Gailua konektatu gabe','2026-05-06 15:40:30','2026-05-06 15:40:30'),(701,'alerts','critical','en','Critical','2026-05-06 15:40:30','2026-05-06 15:40:30'),(702,'alerts','critical','ar','حرج','2026-05-06 15:40:30','2026-05-06 15:40:30'),(703,'alerts','critical','ur','فوقیہ','2026-05-06 15:40:30','2026-05-06 15:40:30'),(704,'alerts','critical','eu','Kritikoa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(705,'subscription','title','en','Subscription','2026-05-06 15:40:30','2026-05-06 15:40:30'),(706,'subscription','title','ar','الاشتراك','2026-05-06 15:40:30','2026-05-06 15:40:30'),(707,'subscription','title','ur','سبسکرپشن','2026-05-06 15:40:30','2026-05-06 15:40:30'),(708,'subscription','title','eu','Harpidetza','2026-05-06 15:40:30','2026-05-06 15:40:30'),(709,'subscription','active','en','Active','2026-05-06 15:40:30','2026-05-06 15:40:30'),(710,'subscription','active','ar','نشط','2026-05-06 15:40:30','2026-05-06 15:40:30'),(711,'subscription','active','ur','فعال','2026-05-06 15:40:30','2026-05-06 15:40:30'),(712,'subscription','active','eu','Aktiboa','2026-05-06 15:40:30','2026-05-06 15:40:30'),(713,'subscription','selectPlan','en','Select Plan','2026-05-06 15:40:30','2026-05-06 15:40:30'),(714,'subscription','selectPlan','ar','اختر الخطة','2026-05-06 15:40:30','2026-05-06 15:40:30'),(715,'subscription','selectPlan','ur','پلان انتخاب کریں','2026-05-06 15:40:30','2026-05-06 15:40:30'),(716,'subscription','selectPlan','eu','Plan bat aukeratu','2026-05-06 15:40:30','2026-05-06 15:40:30');
/*!40000 ALTER TABLE `translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_subscriptions`
--

DROP TABLE IF EXISTS `user_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tier_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `started_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_subscriptions_user_id_foreign` (`user_id`),
  KEY `user_subscriptions_tier_id_foreign` (`tier_id`),
  CONSTRAINT `user_subscriptions_tier_id_foreign` FOREIGN KEY (`tier_id`) REFERENCES `subscription_tiers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscriptions`
--

LOCK TABLES `user_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_subscriptions` DISABLE KEYS */;
INSERT INTO `user_subscriptions` VALUES (1,2,3,'pending_payment','2026-05-06 15:40:43',NULL,'2026-06-05 15:40:43',NULL,'monthly','bank_transfer','BT-69FB7D1B58630','2026-05-06 15:40:43','2026-05-06 15:40:43'),(2,3,2,'active','2026-05-06 15:40:43',NULL,'2026-05-20 15:40:43',NULL,'monthly','card','CARD-69FB7D1B58EF6','2026-05-06 15:40:43','2026-05-06 15:40:43'),(3,4,1,'active','2026-05-06 15:40:43',NULL,'2026-06-05 15:40:43',NULL,'monthly','card','CARD-69FB7D1B5A6C9','2026-05-06 15:40:43','2026-05-06 15:40:43'),(4,6,3,'pending_payment','2026-05-06 15:40:43',NULL,'2026-06-05 15:40:43',NULL,'monthly','bank_transfer','BT-69FB7D1B61B6F','2026-05-06 15:40:43','2026-05-06 15:40:43'),(5,7,4,'active','2026-05-06 15:40:43',NULL,'2026-06-05 15:40:43',NULL,'monthly','card','CARD-69FB7D1B622C6','2026-05-06 15:40:43','2026-05-06 15:40:43');
/*!40000 ALTER TABLE `user_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `subscription_tier_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_status` enum('Active','Pending','Suspended') NOT NULL DEFAULT 'Active',
  `managed_by` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_subscription_tier_id_index` (`subscription_tier_id`),
  KEY `users_managed_by_index` (`managed_by`),
  KEY `users_is_active_index` (`is_active`),
  KEY `users_language_index` (`language`),
  CONSTRAINT `users_managed_by_foreign` FOREIGN KEY (`managed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_subscription_tier_id_foreign` FOREIGN KEY (`subscription_tier_id`) REFERENCES `subscription_tiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@oasis.com',NULL,'$2y$12$zukMy8oIhmlEc2XJOC2ow./WkSA5yAEieUHpgINXwdodbYwbKa5AK','+201066746002',NULL,NULL,'en',2,'Active',NULL,1,NULL,'2026-05-06 15:40:41','2026-05-06 15:40:41'),(2,'Khalid Al-Rashid','khalid@oasis.com',NULL,'$2y$12$rD7N2utfBQ4qtGP9kDxs7eY/yWRg8zyYaKchDnzZvt0D1AUI05Iiy','+201066746002',NULL,NULL,'en',2,'Active',NULL,1,NULL,'2026-05-06 15:40:41','2026-05-06 15:40:41'),(3,'Ahmad Hassan','ahmad@oasis.com',NULL,'$2y$12$EfjqIpNUCZCGbngrpMQLAOmXbmMj9.NPPgyF9ct21jKNYmpBkIUj6','+201066746002',NULL,NULL,'en',2,'Active',NULL,1,NULL,'2026-05-06 15:40:42','2026-05-06 15:40:42'),(4,'Saeed Al-Maktoum','saeed@oasis.com',NULL,'$2y$12$kKXR/0.xRVdtO7Wuhiso1O1NujebvYLo65S9JkAVtpOk8nQNktEua','+201066746002',NULL,NULL,'en',2,'Active',NULL,1,NULL,'2026-05-06 15:40:42','2026-05-06 15:40:42'),(5,'Fatima Al-Said','fatima@oasis.com',NULL,'$2y$12$TIFkq3ueGEu4kXOQkCrUSe5vXWMJY4Nv1Z7MVpNG/KiVSmny84j0K','+201066746002',NULL,NULL,'en',1,'Active',NULL,1,NULL,'2026-05-06 15:40:42','2026-05-06 15:40:42'),(6,'Omar Shepherd','omar@oasis.com',NULL,'$2y$12$yX6eIk08YgapmsSJSI.KAOA6NmPJaRQuKnobi8fYr18gOdEeKyka.','+201066746003',NULL,NULL,'en',NULL,'Active',2,1,NULL,'2026-05-06 15:40:42','2026-05-06 15:40:43'),(7,'Ali Shepherd','ali@oasis.com',NULL,'$2y$12$MRuvBxd3BwL04XhvFOiwXOxtajYqdc4vwLEv.3Biaw0flF.6Mmr9i','+201066746004',NULL,NULL,'en',NULL,'Active',2,1,NULL,'2026-05-06 15:40:42','2026-05-06 15:40:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccination_schedules`
--

DROP TABLE IF EXISTS `vaccination_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaccination_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `animal_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `vaccine_name_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vaccine_name_json`)),
  `vaccination_type` varchar(255) NOT NULL DEFAULT 'routine',
  `vaccination_type_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vaccination_type_json`)),
  `manufacturer` varchar(255) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `dose_number` int(11) NOT NULL DEFAULT 1,
  `total_doses` int(11) NOT NULL DEFAULT 1,
  `scheduled_date` date NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `reminder_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_days` int(11) NOT NULL DEFAULT 3,
  `administered_date` date DEFAULT NULL,
  `veterinarian` varchar(255) DEFAULT NULL,
  `veterinarian_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`veterinarian_json`)),
  `clinic` varchar(255) DEFAULT NULL,
  `clinic_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clinic_json`)),
  `next_due_date` date DEFAULT NULL,
  `status` enum('scheduled','administered','overdue','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `notes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes_json`)),
  `attachment_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vaccination_schedules_animal_id_status_index` (`animal_id`,`status`),
  KEY `vaccination_schedules_owner_id_status_index` (`owner_id`,`status`),
  KEY `vaccination_schedules_scheduled_date_index` (`scheduled_date`),
  KEY `vaccination_schedules_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `vaccination_schedules_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vaccination_schedules_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vaccination_schedules_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccination_schedules`
--

LOCK TABLES `vaccination_schedules` WRITE;
/*!40000 ALTER TABLE `vaccination_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `vaccination_schedules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-06 19:45:47
