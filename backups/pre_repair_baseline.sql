-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: corpor43_crm
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_action` (`action`),
  KEY `idx_activity_table` (`table_name`,`record_id`),
  KEY `idx_activity_created` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','on_leave') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`,`attendance_date`),
  KEY `idx_attendance_date` (`attendance_date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_audit`
--

DROP TABLE IF EXISTS `backup_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('full','partial','incremental') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` enum('in_progress','completed','failed') DEFAULT 'in_progress',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_audit`
--

LOCK TABLES `backup_audit` WRITE;
/*!40000 ALTER TABLE `backup_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communications`
--

DROP TABLE IF EXISTS `communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `communication_type` enum('email','phone_call','meeting','whatsapp','letter','site_visit','video_call','other') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `summary` text NOT NULL,
  `outcome` text DEFAULT NULL,
  `next_action` text DEFAULT NULL,
  `next_action_date` date DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `communication_date` datetime NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_communication_company` (`company_id`),
  KEY `idx_communication_date` (`communication_date`),
  KEY `idx_communication_type` (`communication_type`),
  CONSTRAINT `communications_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communications_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `company_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communications_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communications`
--

LOCK TABLES `communications` WRITE;
/*!40000 ALTER TABLE `communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) NOT NULL,
  `rc_number` varchar(50) DEFAULT NULL,
  `industry_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_position` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('prospect','active','inactive','churned') DEFAULT 'prospect',
  `subscription_status` enum('free','basic','premium','elite') DEFAULT 'free',
  `subscription_start_date` date DEFAULT NULL,
  `subscription_end_date` date DEFAULT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `client_since` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_elite` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `industry_id` (`industry_id`),
  KEY `country_id` (`country_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_company_status` (`status`),
  KEY `idx_company_subscription` (`subscription_status`),
  KEY `idx_company_assigned` (`assigned_staff_id`),
  CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'PetroTech Nigeria Limited','RC 123456',1,'15 Admiralty Way, Lekki','Lagos','Lagos',1,'www.pettechnig.com','+234-901-234-5678','info@pettechnig.com','Ahmed Musa','CEO','+234-901-234-5679',NULL,'active','elite',NULL,NULL,3,'2022-01-15',NULL,1,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Ocean Drilling Services Ltd','RC 234567',1,'27 Trans Amadi Road','Port Harcourt','Rivers',1,'www.oceandrillingserv.com','+234-902-345-6789','contact@oceandrillingserv.com','Blessing Ojo','Operations Director','+234-902-345-6790',NULL,'active','premium',NULL,NULL,3,'2022-03-20',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Greenfield Energy Solutions','RC 345678',1,'Plot 42, Victoria Island','Lagos','Lagos',1,'www.greenfieldenergy.com.ng','+234-903-456-7890','info@greenfieldenergy.com.ng','Chukwudi Eze','Managing Director','+234-903-456-7891',NULL,'active','elite',NULL,NULL,2,'2021-06-10',NULL,1,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Atlantic Marine Services','RC 456789',10,'16 Dockyard Road, Apapa','Lagos','Lagos',1,'www.atlanticmarineserv.com','+234-904-567-8901','admin@atlanticmarineserv.com','Tunde Bakare','General Manager','+234-904-567-8902',NULL,'active','basic',NULL,NULL,4,'2023-01-05',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'Niger Delta Engineering Co.','RC 567890',1,'8 Aba Road, GRA','Port Harcourt','Rivers',1,'www.nigerdeltaeng.com','+234-905-678-9012','contact@nigerdeltaeng.com','Timi Alaibe','Technical Director','+234-905-678-9013',NULL,'active','premium',NULL,NULL,3,'2022-08-15',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'TechBridge Solutions Ltd','RC 678901',6,'100 Adeniran Ogunsanya','Lagos','Lagos',1,'www.techbridgesolutions.com','+234-906-789-0123','info@techbridgesolutions.com','Ngozi Okonkwo','CEO','+234-906-789-0124',NULL,'active','basic',NULL,NULL,4,'2023-05-20',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Delta Steel Construction','RC 789012',5,'5 Ekpan Road, Warri','Warri','Delta',1,'www.deltasteelcon.com','+234-907-890-1234','contact@deltasteelcon.com','Emeka Obi','Project Manager','+234-907-890-1235',NULL,'prospect','free',NULL,NULL,NULL,NULL,NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Agro Nigeria Ventures','RC 890123',9,'30 Aminu Kano Crescent','Abuja','FCT',1,'www.agroventng.com','+234-908-901-2345','info@agroventng.com','Fatima Abubakar','Director','+234-908-901-2346',NULL,'active','basic',NULL,NULL,4,'2023-02-10',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(9,'LogiTrans Nigeria Ltd','RC 901234',13,'45 Ladipo Road, Mushin','Lagos','Lagos',1,'www.logitransng.com','+234-909-012-3456','admin@logitransng.com','Adebowale Adesanya','Logistics Manager','+234-909-012-3457',NULL,'active','premium',NULL,NULL,3,'2022-11-01',NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(10,'Safety First Consultancy','RC 012345',14,'20 Awolowo Road, Ikoyi','Lagos','Lagos',1,'www.safetyfirstcon.com','+234-910-123-4567','info@safetyfirstcon.com','Olumide Dansu','Lead Consultant','+234-910-123-4568',NULL,'active','elite',NULL,NULL,2,'2021-03-15',NULL,1,1,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_contacts`
--

DROP TABLE IF EXISTS `company_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_company_contact` (`company_id`),
  CONSTRAINT `company_contacts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_contacts`
--

LOCK TABLES `company_contacts` WRITE;
/*!40000 ALTER TABLE `company_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(3) NOT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Nigeria','NG','+234','NGN','2026-08-11 12:39:31'),(2,'Ghana','GH','+233','GHS','2026-08-11 12:39:31'),(3,'South Africa','ZA','+27','ZAR','2026-08-11 12:39:31'),(4,'Kenya','KE','+254','KES','2026-08-11 12:39:31'),(5,'United Kingdom','GB','+44','GBP','2026-08-11 12:39:31'),(6,'United States','US','+1','USD','2026-08-11 12:39:31'),(7,'United Arab Emirates','AE','+971','AED','2026-08-11 12:39:31'),(8,'China','CN','+86','CNY','2026-08-11 12:39:31'),(9,'India','IN','+91','INR','2026-08-11 12:39:31'),(10,'Netherlands','NL','+31','EUR','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_reports`
--

DROP TABLE IF EXISTS `daily_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `time_started` time DEFAULT NULL,
  `time_finished` time DEFAULT NULL,
  `activities` text NOT NULL,
  `projects_worked` text DEFAULT NULL,
  `clients_contacted` text DEFAULT NULL,
  `issues_encountered` text DEFAULT NULL,
  `tomorrow_plan` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','approved') DEFAULT 'draft',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`,`report_date`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_report_date` (`report_date`),
  CONSTRAINT `daily_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_reports_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_reports`
--

LOCK TABLES `daily_reports` WRITE;
/*!40000 ALTER TABLE `daily_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `head_id` (`head_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Administration','Corporate administration and management',1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Compliance','Regulatory compliance and registration',2,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Business Development','Client acquisition and relationship management',3,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Operations','Daily business operations',4,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'Legal','Legal affairs and contracts',5,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'IT','Information Technology',6,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Marketing','Marketing and brand management',7,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Finance','Financial management',NULL,'active','2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` enum('certificate','license','permit','insurance','receipt','invoice','contract','letter','report','certificate_of_incorporation','tax_clearance','financial_statement','other') NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `reminder_days` int(11) DEFAULT 30,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `status` enum('active','archived','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_document_company` (`company_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_document_expiry` (`expiry_date`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `elite_members`
--

DROP TABLE IF EXISTS `elite_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `elite_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `membership_tier` enum('basic',' premium','elite','platinum') DEFAULT 'elite',
  `membership_start_date` date NOT NULL,
  `membership_end_date` date DEFAULT NULL,
  `status` enum('active','expired','suspended','cancelled') DEFAULT 'active',
  `benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits`)),
  `assigned_account_manager` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_account_manager` (`assigned_account_manager`),
  KEY `idx_elite_status` (`status`),
  KEY `idx_elite_company` (`company_id`),
  CONSTRAINT `elite_members_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `elite_members_ibfk_2` FOREIGN KEY (`assigned_account_manager`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `elite_members`
--

LOCK TABLES `elite_members` WRITE;
/*!40000 ALTER TABLE `elite_members` DISABLE KEYS */;
INSERT INTO `elite_members` VALUES (1,1,'elite','2024-01-01','2024-12-31','active',NULL,3,NULL,NULL,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,3,'platinum','2024-01-01','2024-12-31','active',NULL,2,NULL,NULL,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,10,'elite','2024-03-01','2025-02-28','active',NULL,3,NULL,NULL,0,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `elite_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `elite_support_requests`
--

DROP TABLE IF EXISTS `elite_support_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `elite_support_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elite_member_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `elite_member_id` (`elite_member_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_support_status` (`status`),
  CONSTRAINT `elite_support_requests_ibfk_1` FOREIGN KEY (`elite_member_id`) REFERENCES `elite_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `elite_support_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `elite_support_requests`
--

LOCK TABLES `elite_support_requests` WRITE;
/*!40000 ALTER TABLE `elite_support_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `elite_support_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_participants`
--

DROP TABLE IF EXISTS `event_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_user` (`event_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_participants`
--

LOCK TABLES `event_participants` WRITE;
/*!40000 ALTER TABLE `event_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('meeting','deadline','reminder','renewal','task','other') DEFAULT 'other',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `color` varchar(7) DEFAULT '#2563eb',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurrence_pattern` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_type` (`event_type`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `industries`
--

DROP TABLE IF EXISTS `industries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `industries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `industries`
--

LOCK TABLES `industries` WRITE;
/*!40000 ALTER TABLE `industries` DISABLE KEYS */;
INSERT INTO `industries` VALUES (1,'Oil & Gas','Petroleum exploration, production, and services','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Banking & Finance','Financial institutions and services','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Telecommunications','Telecom services and infrastructure','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Manufacturing','Industrial manufacturing','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'Construction','Building and construction','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'Information Technology','IT services and software','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Healthcare','Medical services and pharmaceuticals','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Education','Educational institutions','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(9,'Agriculture','Agricultural production and services','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(10,'Maritime','Marine and shipping services','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(11,'Power & Energy','Power generation and distribution','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(12,'Mining','Mineral exploration and extraction','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(13,'Logistics','Transportation and logistics','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(14,'Consulting','Business consulting services','active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(15,'Real Estate','Property development and management','active','2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `industries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','maternity','paternity','bereavement','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_leave_user` (`user_id`),
  KEY `idx_leave_status` (`status`),
  CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`created_at`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_attachments`
--

DROP TABLE IF EXISTS `meeting_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `meeting_attachments_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_attachments`
--

LOCK TABLES `meeting_attachments` WRITE;
/*!40000 ALTER TABLE `meeting_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_participants`
--

DROP TABLE IF EXISTS `meeting_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `external_name` varchar(100) DEFAULT NULL,
  `external_email` varchar(100) DEFAULT NULL,
  `is_external` tinyint(1) DEFAULT 0,
  `attendance_status` enum('invited','accepted','declined','attended','absent') DEFAULT 'invited',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_meeting_participants` (`meeting_id`),
  CONSTRAINT `meeting_participants_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_participants`
--

LOCK TABLES `meeting_participants` WRITE;
/*!40000 ALTER TABLE `meeting_participants` DISABLE KEYS */;
INSERT INTO `meeting_participants` VALUES (1,1,3,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(2,1,2,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(3,2,2,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(4,2,4,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(5,3,2,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(6,4,3,NULL,NULL,0,'accepted','2026-08-11 12:39:31'),(7,4,2,NULL,NULL,0,'accepted','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `meeting_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `meeting_type` enum('internal','client','regulator','vendor','other') DEFAULT 'internal',
  `company_id` int(11) DEFAULT NULL,
  `organizer_id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `action_items` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `meeting_link` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `organizer_id` (`organizer_id`),
  KEY `idx_meeting_date` (`meeting_date`),
  KEY `idx_meeting_status` (`status`),
  CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
INSERT INTO `meetings` VALUES (1,'Q2 Business Review','client',1,3,'PetroTech Head Office, Lekki','2026-08-14','10:00:00','12:00:00','Review Q2 performance and discuss upcoming registrations',NULL,NULL,'scheduled',NULL,NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Compliance Team Meeting','internal',NULL,2,'Conference Room A','2026-08-12','09:00:00','10:00:00','Weekly compliance team sync',NULL,NULL,'scheduled',NULL,NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'NUPRC Submission','regulator',4,2,'NUPRC Office, Abuja','2026-08-18','14:00:00','16:00:00','Submit vessel registration documents',NULL,NULL,'scheduled',NULL,NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Elite Member Review','client',3,3,'Virtual - Zoom','2026-08-16','11:00:00','12:00:00','Quarterly elite member status review',NULL,NULL,'scheduled',NULL,NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_reports`
--

DROP TABLE IF EXISTS `monthly_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `report_year` smallint(6) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `daily_reports_count` int(11) NOT NULL DEFAULT 0,
  `activities_summary` text DEFAULT NULL,
  `projects_summary` text DEFAULT NULL,
  `clients_summary` text DEFAULT NULL,
  `issues_summary` text DEFAULT NULL,
  `tasks_completed_count` int(11) NOT NULL DEFAULT 0,
  `tasks_outstanding_count` int(11) NOT NULL DEFAULT 0,
  `generated_at` datetime NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_month` (`user_id`,`report_year`,`report_month`),
  KEY `department_id` (`department_id`),
  KEY `generated_by` (`generated_by`),
  KEY `idx_monthly_period` (`report_year`,`report_month`),
  CONSTRAINT `monthly_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monthly_reports_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_reports`
--

LOCK TABLES `monthly_reports` WRITE;
/*!40000 ALTER TABLE `monthly_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `type` enum('email','in_app') DEFAULT 'in_app',
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
INSERT INTO `notification_templates` VALUES (1,'task_assigned','New Task Assigned','You have been assigned a new task: {task_title}. Due: {due_date}','in_app','[\"task_title\",\"due_date\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'registration_due','Registration Due Soon','Registration for {company_name} - {registration_type} is due on {due_date}','in_app','[\"company_name\",\"registration_type\",\"due_date\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'document_expiring','Document Expiring','Document {document_name} for {company_name} expires on {expiry_date}','in_app','[\"document_name\",\"company_name\",\"expiry_date\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'meeting_reminder','Meeting Reminder','You have a meeting tomorrow: {meeting_title} at {meeting_time}','in_app','[\"meeting_title\",\"meeting_time\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'weekly_report','Weekly Report Available','Your weekly report for {week_start} to {week_end} is ready for review','in_app','[\"week_start\",\"week_end\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `sent_email` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`),
  KEY `idx_notification_read` (`user_id`,`is_read`),
  KEY `idx_notification_created` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'task','New Task Assigned','You have been assigned: Follow up on NUPRC registration','/tasks/view?id=1',NULL,NULL,0,NULL,0,'2026-08-11 12:39:31'),(2,3,'meeting','Meeting Tomorrow','Reminder: Q2 Business Review with PetroTech','/meetings/view?id=1',NULL,NULL,0,NULL,0,'2026-08-11 12:39:31'),(3,2,'registration','Expiring Registration','PetroTech NUPRC registration expires in 30 days','/registrations/view?id=1',NULL,NULL,0,NULL,0,'2026-08-11 12:39:31');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opportunities`
--

DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opportunities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opportunity_title` varchar(200) NOT NULL,
  `opportunity_type` enum('vendor','government_tender','oil_gas','training','consulting','partnership','other') NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `status` enum('new','pursuing','submitted','won','lost','cancelled') DEFAULT 'new',
  `probability` int(11) DEFAULT 50,
  `result_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_opportunity_status` (`status`),
  KEY `idx_opportunity_type` (`opportunity_type`),
  KEY `idx_opportunity_deadline` (`deadline`),
  CONSTRAINT `opportunities_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ibfk_2` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opportunities`
--

LOCK TABLES `opportunities` WRITE;
/*!40000 ALTER TABLE `opportunities` DISABLE KEYS */;
INSERT INTO `opportunities` VALUES (1,'Shell Vendor Opportunity 2024','oil_gas','Shell Nigeria','Shell Nigeria vendor registration for marine services',4,3,5000000.00,'2026-09-10',NULL,'pursuing',60,NULL,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'NLNG Logistics Contract','oil_gas','NLNG Limited','NLNG is seeking logistics partners for 2024-2025',NULL,3,10000000.00,'2026-09-25',NULL,'new',40,NULL,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Government Power Project','government_tender','Federal Ministry of Power','Tender for power infrastructure consulting',NULL,4,2500000.00,'2026-10-10',NULL,'new',30,NULL,1,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `opportunities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_history`
--

DROP TABLE IF EXISTS `registration_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registration_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_registration_history` (`registration_id`),
  CONSTRAINT `registration_history_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registration_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_history`
--

LOCK TABLES `registration_history` WRITE;
/*!40000 ALTER TABLE `registration_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `registration_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_types`
--

DROP TABLE IF EXISTS `registration_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registration_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` enum('regulatory','corporate','compliance','certification','other') DEFAULT 'regulatory',
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `processing_time` int(11) DEFAULT 0,
  `validity_period` int(11) DEFAULT 365,
  `cost` decimal(15,2) DEFAULT 0.00,
  `renewable` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_types`
--

LOCK TABLES `registration_types` WRITE;
/*!40000 ALTER TABLE `registration_types` DISABLE KEYS */;
INSERT INTO `registration_types` VALUES (1,'NUPRC Registration','regulatory','Nigerian Upstream Petroleum Regulatory Commission registration for oil and gas companies',NULL,90,365,500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'NCDMB Registration','regulatory','Nigerian Content Development and Monitoring Board registration',NULL,60,365,300000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'NIPEX Registration','regulatory','Nigerian Petroleum Exchange registration for oil and gas operators',NULL,45,365,250000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Seplat JV Registration','corporate','Seplat Joint Venture vendor registration',NULL,30,730,100000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'NLNG Registration','corporate','Nigeria LNG Limited vendor registration',NULL,45,730,150000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'Aradel Registration','corporate','Aradel Energy vendor registration',NULL,30,730,100000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Chevron Registration','corporate','Chevron Nigeria Limited vendor registration',NULL,60,730,200000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Shell Registration','corporate','Shell Nigeria vendor registration',NULL,60,730,200000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(9,'NNPC Registration','regulatory','Nigerian National Petroleum Company registration',NULL,90,365,500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(10,'NSITF Registration','compliance','Nigerian Social Insurance Trust Fund registration',NULL,14,365,50000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(11,'PENCOM Registration','compliance','Pension Commission registration for employers',NULL,30,365,75000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(12,'ITF Registration','compliance','Industrial Training Fund registration',NULL,14,365,50000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(13,'FIRS Registration','compliance','Federal Inland Revenue Service tax registration',NULL,7,365,25000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(14,'DUNS Number','certification','Dun & Bradstreet D-U-N-S Number registration',NULL,30,365,100000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(15,'ISO 9001 Certification','certification','Quality Management System certification',NULL,180,1095,1500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(16,'ISO 14001 Certification','certification','Environmental Management System certification',NULL,180,1095,1500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(17,'ISO 45001 Certification','certification','Occupational Health and Safety Management System certification',NULL,180,1095,1500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(18,'Marine Vessel Registration','regulatory','Nigerian Maritime Administration and Safety Agency vessel registration',NULL,90,365,500000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(19,'Nigerian Navy Permit','regulatory','Nigerian Navy operational permit for marine vessels',NULL,60,365,300000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31'),(20,'Customs License','regulatory','Nigeria Customs Service license',NULL,30,365,200000.00,1,'active','2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `registration_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `registration_type_id` int(11) NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','awaiting_documents','submitted','processing','approved','rejected','expired','renewal_required') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `requirements_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements_checklist`)),
  `documents_received` text DEFAULT NULL,
  `documents_missing` text DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `regulator_reference` varchar(100) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registration_type_id` (`registration_type_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_registration_status` (`status`),
  KEY `idx_registration_company` (`company_id`),
  KEY `idx_registration_expiry` (`expiry_date`),
  KEY `idx_registration_assigned` (`assigned_staff_id`),
  CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`registration_type_id`) REFERENCES `registration_types` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `registrations_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registrations_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (1,1,1,NULL,'approved','high',2,NULL,NULL,NULL,'2022-02-01','2022-04-15',NULL,'2026-07-02',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,1,2,NULL,'approved','high',2,NULL,NULL,NULL,'2022-02-15','2022-04-01',NULL,'2026-07-02',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,1,3,NULL,'approved','medium',2,NULL,NULL,NULL,'2022-03-01','2022-04-15',NULL,'2026-07-02',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,2,1,NULL,'approved','high',2,NULL,NULL,NULL,'2022-04-01','2022-06-15',NULL,'2025-07-02',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,2,4,NULL,'submitted','medium',3,NULL,NULL,NULL,'2024-05-01','2024-06-15',NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,3,5,NULL,'approved','high',2,NULL,NULL,NULL,'2021-07-01','2021-09-01',NULL,'2025-07-02',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,3,15,NULL,'processing','high',2,NULL,NULL,NULL,'2024-01-15','2024-07-15',NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,4,18,NULL,'approved','high',2,NULL,NULL,NULL,'2023-02-01','2023-05-01',NULL,'2024-02-01',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(9,4,19,NULL,'pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(10,5,1,NULL,'approved','high',2,NULL,NULL,NULL,'2022-09-01','2022-11-15',NULL,'2025-09-01',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(11,5,6,NULL,'approved','medium',3,NULL,NULL,NULL,'2022-09-15','2022-10-30',NULL,'2024-09-15',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(12,6,11,NULL,'pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(13,6,12,NULL,'pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(14,7,1,NULL,'pending','low',3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(15,8,11,NULL,'approved','low',2,NULL,NULL,NULL,'2023-03-01','2023-03-15',NULL,'2024-03-01',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(16,9,13,NULL,'approved','low',2,NULL,NULL,NULL,'2022-11-15','2022-11-30',NULL,'2023-11-15',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(17,10,15,NULL,'approved','high',2,NULL,NULL,NULL,'2021-04-01','2021-10-01',NULL,'2024-04-01',NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(18,10,16,NULL,'processing','high',2,NULL,NULL,NULL,'2023-06-01','2023-12-01',NULL,NULL,NULL,NULL,NULL,0.00,'pending',1,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `query` text DEFAULT NULL,
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `created_by` int(11) DEFAULT NULL,
  `is_scheduled` tinyint(1) DEFAULT 0,
  `schedule_frequency` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','Administrator','Full system access','[\"*\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Compliance','Compliance Officer','Manages compliance and regulatory matters','[\"view_companies\",\"view_registrations\",\"manage_registrations\",\"view_documents\",\"manage_documents\",\"view_compliance\",\"manage_compliance\",\"view_tasks\",\"manage_tasks\",\"view_reports\",\"export_data\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Business Development','Business Development','Manages client relationships and opportunities','[\"view_companies\",\"manage_companies\",\"view_contacts\",\"manage_contacts\",\"view_communications\",\"manage_communications\",\"view_opportunities\",\"manage_opportunities\",\"view_tasks\",\"manage_tasks\",\"view_meetings\",\"manage_meetings\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'Operations','Operations','Handles daily operations','[\"view_companies\",\"view_registrations\",\"view_tasks\",\"manage_tasks\",\"view_documents\",\"upload_documents\",\"submit_reports\",\"view_calendar\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'Legal','Legal Officer','Manages legal matters and contracts','[\"view_companies\",\"view_documents\",\"manage_documents\",\"view_tasks\",\"manage_tasks\",\"view_compliance\",\"manage_compliance\",\"view_contracts\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'IT','IT Support','Technical support and system management','[\"view_settings\",\"manage_settings\",\"view_users\",\"manage_users\",\"view_logs\",\"export_data\",\"backup_system\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Marketing','Marketing','Marketing and communications','[\"view_companies\",\"view_contacts\",\"manage_contacts\",\"view_tasks\",\"manage_tasks\",\"view_meetings\",\"manage_meetings\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Management','Management','Executive oversight','[\"view_companies\",\"view_registrations\",\"view_tasks\",\"view_reports\",\"view_users\",\"view_compliance\",\"view_opportunities\",\"approve_reports\",\"export_data\"]',1,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','Corporate Setup Limited','string','Company name displayed in the application','2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'company_email','info@corporatesetup.com','string','Company email address','2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'company_phone','+234-800-CORPSETUP','string','Company phone number','2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'company_address','15 Admiralty Way, Lekki, Lagos','string','Company address','2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'currency','NGN','string','Default currency','2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'date_format','d-m-Y','string','Default date format','2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'timezone','Africa/Lagos','string','Application timezone','2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'items_per_page','20','integer','Default pagination items per page','2026-08-11 12:39:31','2026-08-11 12:39:31'),(9,'session_timeout','1800','integer','Session timeout in seconds','2026-08-11 12:39:31','2026-08-11 12:39:31'),(10,'max_file_upload','10485760','integer','Maximum file upload size in bytes','2026-08-11 12:39:31','2026-08-11 12:39:31'),(11,'enable_notifications','1','boolean','Enable email notifications','2026-08-11 12:39:31','2026-08-11 12:39:31'),(12,'enable_daily_reports','1','boolean','Enable daily activity reports','2026-08-11 12:39:31','2026-08-11 12:39:31'),(13,'logo_url','/assets/images/logo.png','string','Company logo URL','2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_assignees`
--

DROP TABLE IF EXISTS `task_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_assignees` (
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`task_id`,`user_id`),
  KEY `idx_task_assignees_user` (`user_id`),
  CONSTRAINT `task_assignees_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_assignees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_assignees`
--

LOCK TABLES `task_assignees` WRITE;
/*!40000 ALTER TABLE `task_assignees` DISABLE KEYS */;
INSERT INTO `task_assignees` VALUES (1,2,'2026-08-11 12:39:33'),(2,3,'2026-08-11 12:39:33'),(3,2,'2026-08-11 12:39:33'),(4,2,'2026-08-11 12:39:33'),(5,2,'2026-08-11 12:39:33'),(6,3,'2026-08-11 12:39:33'),(7,2,'2026-08-11 12:39:33'),(8,3,'2026-08-11 12:39:33');
/*!40000 ALTER TABLE `task_assignees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_attachments`
--

DROP TABLE IF EXISTS `task_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `task_attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_attachments`
--

LOCK TABLES `task_attachments` WRITE;
/*!40000 ALTER TABLE `task_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_comments`
--

DROP TABLE IF EXISTS `task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_task_comments` (`task_id`),
  CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_comments`
--

LOCK TABLES `task_comments` WRITE;
/*!40000 ALTER TABLE `task_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('general','registration','compliance','meeting','client','admin','other') DEFAULT 'general',
  `related_company_id` int(11) DEFAULT NULL,
  `related_registration_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled','on_hold') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `frequency` enum('once','daily','weekly','monthly','quarterly','yearly') DEFAULT 'once',
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `related_company_id` (`related_company_id`),
  KEY `related_registration_id` (`related_registration_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_task_assigned` (`assigned_to`),
  KEY `idx_task_status` (`status`),
  KEY `idx_task_due` (`due_date`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`related_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`related_registration_id`) REFERENCES `registrations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Follow up on NUPRC registration','Send required documents to NUPRC for PetroTech registration renewal','registration',1,1,2,1,'high','in_progress','2026-08-16',NULL,NULL,60,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'Client meeting - Ocean Drilling','Quarterly review meeting with Ocean Drilling Services','client',2,NULL,3,1,'medium','pending','2026-08-14',NULL,NULL,0,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'Submit NSITF documents','Collect and submit NSITF compliance documents for TechBridge','compliance',6,12,2,1,'medium','pending','2026-08-18',NULL,NULL,30,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'ISO certification audit preparation','Prepare all documentation for upcoming ISO 9001 audit','compliance',10,17,2,1,'high','in_progress','2026-08-25',NULL,NULL,75,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'Marine vessel registration renewal','Process renewal for Atlantic Marine vessel permit','registration',4,9,2,1,'high','pending','2026-08-21',NULL,NULL,20,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'New client onboarding','Complete onboarding process for Delta Steel Construction','client',7,NULL,3,1,'medium','in_progress','2026-09-01',NULL,NULL,40,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'Weekly compliance report','Submit weekly compliance status report','admin',NULL,NULL,2,1,'medium','in_progress','2026-08-11',NULL,NULL,80,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'Elite member monthly check-in','Monthly status check with Greenfield Energy','client',3,NULL,3,1,'medium','pending','2026-08-21',NULL,NULL,0,'once',NULL,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `must_change_password` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `role_id` (`role_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'CSL-001','System Administrator','admin@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-801-234-5678',NULL,1,1,'System Administrator','active',0,NULL,NULL,0,1,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(2,'CSL-002','John Okonkwo','john.okonkwo@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-802-345-6789',NULL,2,2,'Head of Compliance','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(3,'CSL-003','Mary Ibrahim','mary.ibrahim@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-803-456-7890',NULL,3,3,'Business Development Manager','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(4,'CSL-004','Peter Adeyemi','peter.adeyemi@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-804-567-8901',NULL,4,4,'Operations Manager','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(5,'CSL-005','Grace Nwankwo','grace.nwankwo@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-805-678-9012',NULL,5,5,'Legal Counsel','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(6,'CSL-006','Emeka Okafor','emeka.okafor@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-806-789-0123',NULL,6,6,'IT Manager','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(7,'CSL-007','Chioma Eze','chioma.eze@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-807-890-1234',NULL,7,7,'Marketing Manager','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31'),(8,'CSL-008','David Mohammed','david.mohammed@corporatesetup.com','$argon2id$v=19$m=65536,t=4,p=1$VGVzdERhdGE$OWIzMTIzYjRlNjYxYzJjMTg5NDBhYzNiMjAxZTg2YjA','+234-808-901-2345',NULL,8,1,'Managing Director','active',0,NULL,NULL,0,0,'2026-08-11 12:39:31','2026-08-11 12:39:31');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_active_registrations`
--

DROP TABLE IF EXISTS `vw_active_registrations`;
/*!50001 DROP VIEW IF EXISTS `vw_active_registrations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_active_registrations` AS SELECT
 1 AS `id`,
  1 AS `status`,
  1 AS `priority`,
  1 AS `expiry_date`,
  1 AS `company_name`,
  1 AS `registration_type`,
  1 AS `assigned_staff`,
  1 AS `days_to_expiry` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_expiring_soon`
--

DROP TABLE IF EXISTS `vw_expiring_soon`;
/*!50001 DROP VIEW IF EXISTS `vw_expiring_soon`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_expiring_soon` AS SELECT
 1 AS `id`,
  1 AS `company_name`,
  1 AS `registration_type`,
  1 AS `expiry_date`,
  1 AS `days_remaining` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_staff_workload`
--

DROP TABLE IF EXISTS `vw_staff_workload`;
/*!50001 DROP VIEW IF EXISTS `vw_staff_workload`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_staff_workload` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `department_id`,
  1 AS `assigned_companies`,
  1 AS `active_registrations`,
  1 AS `pending_tasks` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_active_registrations`
--

/*!50001 DROP VIEW IF EXISTS `vw_active_registrations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_active_registrations` AS select `r`.`id` AS `id`,`r`.`status` AS `status`,`r`.`priority` AS `priority`,`r`.`expiry_date` AS `expiry_date`,`c`.`company_name` AS `company_name`,`rt`.`name` AS `registration_type`,`u`.`name` AS `assigned_staff`,to_days(`r`.`expiry_date`) - to_days(curdate()) AS `days_to_expiry` from (((`registrations` `r` join `companies` `c` on(`r`.`company_id` = `c`.`id`)) join `registration_types` `rt` on(`r`.`registration_type_id` = `rt`.`id`)) left join `users` `u` on(`r`.`assigned_staff_id` = `u`.`id`)) where `r`.`status` not in ('rejected','expired') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_expiring_soon`
--

/*!50001 DROP VIEW IF EXISTS `vw_expiring_soon`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_expiring_soon` AS select `r`.`id` AS `id`,`c`.`company_name` AS `company_name`,`rt`.`name` AS `registration_type`,`r`.`expiry_date` AS `expiry_date`,to_days(`r`.`expiry_date`) - to_days(curdate()) AS `days_remaining` from ((`registrations` `r` join `companies` `c` on(`r`.`company_id` = `c`.`id`)) join `registration_types` `rt` on(`r`.`registration_type_id` = `rt`.`id`)) where `r`.`expiry_date` between curdate() and curdate() + interval 90 day and `r`.`status` = 'approved' order by `r`.`expiry_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_staff_workload`
--

/*!50001 DROP VIEW IF EXISTS `vw_staff_workload`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_staff_workload` AS select `u`.`id` AS `id`,`u`.`name` AS `name`,`u`.`department_id` AS `department_id`,count(distinct `c`.`id`) AS `assigned_companies`,count(distinct case when `r`.`status` in ('pending','submitted','processing') then `r`.`id` end) AS `active_registrations`,count(distinct case when `t`.`status` in ('pending','in_progress') then `t`.`id` end) AS `pending_tasks` from (((`users` `u` left join `companies` `c` on(`c`.`assigned_staff_id` = `u`.`id`)) left join `registrations` `r` on(`r`.`assigned_staff_id` = `u`.`id`)) left join `tasks` `t` on(`t`.`assigned_to` = `u`.`id`)) where `u`.`status` = 'active' group by `u`.`id`,`u`.`name`,`u`.`department_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-11 13:39:49
