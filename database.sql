-- MariaDB dump 10.19  Distrib 10.11.7-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: primatech_db
-- ------------------------------------------------------
-- Server version	10.11.7-MariaDB-4

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
-- Table structure for table `absence`
--

DROP TABLE IF EXISTS `absence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `type_absence_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `acte_id` int(11) DEFAULT NULL,
  `approuve_par_id` int(11) DEFAULT NULL,
  `cree_par_id` int(11) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `duree` decimal(6,2) NOT NULL,
  `unite` varchar(20) NOT NULL DEFAULT 'JOURS',
  `motif` longtext NOT NULL,
  `statut` smallint(6) NOT NULL DEFAULT 0,
  `date_decision` datetime DEFAULT NULL,
  `commentaire_decision` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_765AE0C930FCF5AA` (`type_absence_id`),
  KEY `IDX_765AE0C9C33F7837` (`document_id`),
  KEY `IDX_765AE0C9A767B8C7` (`acte_id`),
  KEY `IDX_765AE0C95ED9CBB3` (`approuve_par_id`),
  KEY `IDX_765AE0C9FC29C013` (`cree_par_id`),
  KEY `idx_absence_statut` (`statut`),
  KEY `idx_absence_dates` (`date_debut`,`date_fin`),
  KEY `idx_absence_institution` (`institution_id`),
  KEY `idx_absence_employe` (`employe_id`),
  CONSTRAINT `FK_765AE0C910405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_765AE0C91B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_765AE0C930FCF5AA` FOREIGN KEY (`type_absence_id`) REFERENCES `type_absence` (`id`),
  CONSTRAINT `FK_765AE0C95ED9CBB3` FOREIGN KEY (`approuve_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_765AE0C9A767B8C7` FOREIGN KEY (`acte_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_765AE0C9C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_765AE0C9FC29C013` FOREIGN KEY (`cree_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absence`
--

LOCK TABLES `absence` WRITE;
/*!40000 ALTER TABLE `absence` DISABLE KEYS */;
/*!40000 ALTER TABLE `absence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `absence_log`
--

DROP TABLE IF EXISTS `absence_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absence_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `absence_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_99C161C42DFF238F` (`absence_id`),
  KEY `IDX_99C161C4A76ED395` (`user_id`),
  KEY `idx_absence_log_action` (`action`),
  KEY `idx_absence_log_created_at` (`created_at`),
  CONSTRAINT `FK_99C161C42DFF238F` FOREIGN KEY (`absence_id`) REFERENCES `absence` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_99C161C4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absence_log`
--

LOCK TABLES `absence_log` WRITE;
/*!40000 ALTER TABLE `absence_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `absence_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acte`
--

DROP TABLE IF EXISTS `acte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_acte_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `poste_id` int(11) DEFAULT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `affectation_id` int(11) DEFAULT NULL,
  `acte_parent_id` int(11) DEFAULT NULL,
  `cree_par_id` int(11) DEFAULT NULL,
  `numero` varchar(100) NOT NULL,
  `date_signature` date NOT NULL,
  `date_effet` date NOT NULL,
  `date_publication` date DEFAULT NULL,
  `objet` varchar(255) NOT NULL,
  `signataire_qualite` varchar(255) NOT NULL,
  `signataire_nom` varchar(255) DEFAULT NULL,
  `statut` varchar(50) NOT NULL,
  `contenu_resume` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9EC41326C33F7837` (`document_id`),
  KEY `IDX_9EC41326A9F322E2` (`acte_parent_id`),
  KEY `IDX_9EC41326FC29C013` (`cree_par_id`),
  KEY `idx_acte_institution` (`institution_id`),
  KEY `idx_acte_type` (`type_acte_id`),
  KEY `idx_acte_employe` (`employe_id`),
  KEY `idx_acte_poste` (`poste_id`),
  KEY `idx_acte_structure` (`structure_id`),
  KEY `idx_acte_affectation` (`affectation_id`),
  KEY `idx_acte_statut` (`statut`),
  KEY `idx_acte_numero` (`numero`),
  CONSTRAINT `FK_9EC4132610405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9EC413261B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC413262534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC413266D0ABA22` FOREIGN KEY (`affectation_id`) REFERENCES `affectation` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC413268F46D732` FOREIGN KEY (`type_acte_id`) REFERENCES `type_acte` (`id`),
  CONSTRAINT `FK_9EC41326A0905086` FOREIGN KEY (`poste_id`) REFERENCES `poste` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC41326A9F322E2` FOREIGN KEY (`acte_parent_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC41326C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9EC41326FC29C013` FOREIGN KEY (`cree_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acte`
--

LOCK TABLES `acte` WRITE;
/*!40000 ALTER TABLE `acte` DISABLE KEYS */;
/*!40000 ALTER TABLE `acte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affectation`
--

DROP TABLE IF EXISTS `affectation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affectation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `ancienne_structure_id` int(11) DEFAULT NULL,
  `ancien_service_id` int(11) DEFAULT NULL,
  `ancien_poste_id` int(11) DEFAULT NULL,
  `ancienne_fonction_id` int(11) DEFAULT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `poste_id` int(11) DEFAULT NULL,
  `fonction_id` int(11) DEFAULT NULL,
  `reference` varchar(50) NOT NULL,
  `type_mouvement` varchar(50) NOT NULL,
  `statut` varchar(50) NOT NULL,
  `date_decision` date NOT NULL,
  `date_effet` date NOT NULL,
  `date_prise_service` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `reference_acte` varchar(100) DEFAULT NULL,
  `motif` longtext DEFAULT NULL,
  `commentaire` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F4DD61D315B901B2` (`ancienne_structure_id`),
  KEY `IDX_F4DD61D362887706` (`ancien_service_id`),
  KEY `IDX_F4DD61D3EB66A8BD` (`ancien_poste_id`),
  KEY `IDX_F4DD61D36F7649CF` (`ancienne_fonction_id`),
  KEY `IDX_F4DD61D3ED5CA9E6` (`service_id`),
  KEY `IDX_F4DD61D357889920` (`fonction_id`),
  KEY `idx_affectation_institution` (`institution_id`),
  KEY `idx_affectation_employe` (`employe_id`),
  KEY `idx_affectation_poste` (`poste_id`),
  KEY `idx_affectation_structure` (`structure_id`),
  KEY `idx_affectation_statut` (`statut`),
  KEY `idx_affectation_type` (`type_mouvement`),
  CONSTRAINT `FK_F4DD61D310405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F4DD61D315B901B2` FOREIGN KEY (`ancienne_structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D31B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F4DD61D32534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D357889920` FOREIGN KEY (`fonction_id`) REFERENCES `fonction` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D362887706` FOREIGN KEY (`ancien_service_id`) REFERENCES `service` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D36F7649CF` FOREIGN KEY (`ancienne_fonction_id`) REFERENCES `fonction` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D3A0905086` FOREIGN KEY (`poste_id`) REFERENCES `poste` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D3EB66A8BD` FOREIGN KEY (`ancien_poste_id`) REFERENCES `poste` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F4DD61D3ED5CA9E6` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affectation`
--

LOCK TABLES `affectation` WRITE;
/*!40000 ALTER TABLE `affectation` DISABLE KEYS */;
/*!40000 ALTER TABLE `affectation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges`
--

DROP TABLE IF EXISTS `conges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) NOT NULL,
  `type_conge_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `date_diff` int(11) NOT NULL,
  `motif` longtext DEFAULT NULL,
  `statut` smallint(6) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `acte_id` int(11) DEFAULT NULL,
  `approuve_par_id` int(11) DEFAULT NULL,
  `commentaire_refus` longtext DEFAULT NULL,
  `date_approbation` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6327DE3A1B65292` (`employe_id`),
  KEY `IDX_6327DE3A753BDA5` (`type_conge_id`),
  KEY `idx_conges_institution` (`institution_id`),
  KEY `IDX_6327DE3AC33F7837` (`document_id`),
  KEY `IDX_6327DE3AA767B8C7` (`acte_id`),
  KEY `IDX_6327DE3A5ED9CBB3` (`approuve_par_id`),
  KEY `idx_conges_statut` (`statut`),
  KEY `idx_conges_dates` (`date_debut`,`date_fin`),
  CONSTRAINT `FK_6327DE3A10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6327DE3A1B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6327DE3A5ED9CBB3` FOREIGN KEY (`approuve_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6327DE3A753BDA5` FOREIGN KEY (`type_conge_id`) REFERENCES `type_conges` (`id`),
  CONSTRAINT `FK_6327DE3AA767B8C7` FOREIGN KEY (`acte_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6327DE3AC33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges`
--

LOCK TABLES `conges` WRITE;
/*!40000 ALTER TABLE `conges` DISABLE KEYS */;
INSERT INTO `conges` VALUES
(1,1,9,'2026-08-04','2026-08-19',15,'Congés d\'été en famille',0,'2026-07-25 18:53:18','2026-07-25 18:53:18','Congés payés',1,NULL,NULL,NULL,NULL,NULL),
(2,2,10,'2026-07-06','2026-07-16',10,'Repos médical prescrit',0,'2026-06-25 18:53:18','2026-06-25 18:53:18','Congé maladie',1,NULL,NULL,NULL,NULL,NULL),
(3,3,11,'2026-04-30','2026-05-01',1,'Voyage personnel',0,'2026-04-14 18:53:18','2026-04-14 18:53:18','Congé maternité',1,NULL,NULL,NULL,NULL,NULL),
(4,4,12,'2026-08-12','2026-08-21',9,'Événement familial',1,'2026-08-05 18:53:18','2026-08-08 18:53:18','Congé paternité',2,NULL,NULL,NULL,NULL,NULL),
(5,5,13,'2026-03-23','2026-03-24',1,'Formation continue',1,'2026-03-06 18:53:18','2026-03-10 18:53:18','Congé sans solde',3,NULL,NULL,NULL,NULL,NULL),
(6,6,14,'2026-10-23','2026-10-28',5,'Congé sans solde personnel',1,'2026-10-05 18:53:18','2026-10-12 18:53:18','Formation',3,NULL,NULL,NULL,NULL,NULL),
(7,7,15,'2026-05-24','2026-06-05',12,'Mission à l\'étranger',2,'2026-05-10 18:53:18','2026-05-13 18:53:18','Mission',4,NULL,NULL,NULL,NULL,NULL),
(8,8,9,'2026-05-23','2026-05-31',8,'Congés d\'été en famille',0,'2026-05-12 18:53:18','2026-05-12 18:53:18','Congés payés',5,NULL,NULL,NULL,NULL,NULL),
(9,9,10,'2026-06-28','2026-07-07',9,'Repos médical prescrit',1,'2026-06-01 18:53:18','2026-06-07 18:53:18','Congé maladie',5,NULL,NULL,NULL,NULL,NULL),
(10,10,11,'2026-04-17','2026-04-28',11,'Voyage personnel',1,'2026-03-18 18:53:18','2026-03-22 18:53:18','Congé maternité',6,NULL,NULL,NULL,NULL,NULL),
(11,11,12,'2026-09-14','2026-09-15',1,'Événement familial',2,'2026-08-15 18:53:18','2026-08-17 18:53:18','Congé paternité',1,NULL,NULL,NULL,NULL,NULL),
(12,12,13,'2026-04-22','2026-04-26',4,'Formation continue',2,'2026-04-09 18:53:18','2026-04-12 18:53:18','Congé sans solde',1,NULL,NULL,NULL,NULL,NULL),
(13,13,14,'2026-10-26','2026-11-08',13,'Congé sans solde personnel',1,'2026-10-13 18:53:18','2026-10-16 18:53:18','Formation',2,NULL,NULL,NULL,NULL,NULL),
(14,14,15,'2026-09-25','2026-09-29',4,'Mission à l\'étranger',2,'2026-08-26 18:53:18','2026-08-27 18:53:18','Mission',3,NULL,NULL,NULL,NULL,NULL),
(15,15,9,'2026-03-06','2026-03-07',1,'Congés d\'été en famille',2,'2026-02-23 18:53:18','2026-02-24 18:53:18','Congés payés',3,NULL,NULL,NULL,NULL,NULL),
(16,16,10,'2026-06-10','2026-06-18',8,'Repos médical prescrit',2,'2026-05-24 18:53:18','2026-05-29 18:53:18','Congé maladie',4,NULL,NULL,NULL,NULL,NULL),
(17,17,11,'2026-09-27','2026-10-09',12,'Voyage personnel',0,'2026-09-08 18:53:18','2026-09-08 18:53:18','Congé maternité',5,NULL,NULL,NULL,NULL,NULL),
(18,18,12,'2026-10-30','2026-11-13',14,'Événement familial',0,'2026-10-22 18:53:18','2026-10-22 18:53:18','Congé paternité',5,NULL,NULL,NULL,NULL,NULL),
(19,19,13,'2026-06-12','2026-06-20',8,'Formation continue',1,'2026-05-27 18:53:18','2026-05-30 18:53:18','Congé sans solde',6,NULL,NULL,NULL,NULL,NULL),
(20,20,14,'2026-11-20','2026-11-26',6,'Congé sans solde personnel',1,'2026-11-03 18:53:18','2026-11-07 18:53:18','Formation',1,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `conges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges_log`
--

DROP TABLE IF EXISTS `conges_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conges_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conges_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D4ABF347F50CF8EF` (`conges_id`),
  KEY `IDX_D4ABF347A76ED395` (`user_id`),
  CONSTRAINT `FK_D4ABF347A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_D4ABF347F50CF8EF` FOREIGN KEY (`conges_id`) REFERENCES `conges` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges_log`
--

LOCK TABLES `conges_log` WRITE;
/*!40000 ALTER TABLE `conges_log` DISABLE KEYS */;
INSERT INTO `conges_log` VALUES
(1,1,1,'create','Demande créée','Nouvelle demande de congé soumise','2026-07-25 18:53:18'),
(2,2,2,'create','Demande créée','Nouvelle demande de congé soumise','2026-06-25 18:53:18'),
(3,3,2,'create','Demande créée','Nouvelle demande de congé soumise','2026-04-14 18:53:18'),
(4,4,3,'create','Demande créée','Nouvelle demande de congé soumise','2026-08-05 18:53:18'),
(5,4,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-08-08 18:53:18'),
(6,5,4,'create','Demande créée','Nouvelle demande de congé soumise','2026-03-06 18:53:18'),
(7,5,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-03-10 18:53:18'),
(8,6,4,'create','Demande créée','Nouvelle demande de congé soumise','2026-10-05 18:53:18'),
(9,6,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-10-12 18:53:18'),
(10,7,5,'create','Demande créée','Nouvelle demande de congé soumise','2026-05-10 18:53:18'),
(11,7,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-05-13 18:53:18'),
(12,8,6,'create','Demande créée','Nouvelle demande de congé soumise','2026-05-12 18:53:18'),
(13,9,6,'create','Demande créée','Nouvelle demande de congé soumise','2026-06-01 18:53:18'),
(14,9,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-06-07 18:53:18'),
(15,10,7,'create','Demande créée','Nouvelle demande de congé soumise','2026-03-18 18:53:18'),
(16,10,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-03-22 18:53:18'),
(17,11,8,'create','Demande créée','Nouvelle demande de congé soumise','2026-08-15 18:53:18'),
(18,11,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-08-17 18:53:18'),
(19,12,8,'create','Demande créée','Nouvelle demande de congé soumise','2026-04-09 18:53:18'),
(20,12,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-04-12 18:53:18'),
(21,13,9,'create','Demande créée','Nouvelle demande de congé soumise','2026-10-13 18:53:18'),
(22,13,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-10-16 18:53:18'),
(23,14,10,'create','Demande créée','Nouvelle demande de congé soumise','2026-08-26 18:53:18'),
(24,14,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-08-27 18:53:18'),
(25,15,10,'create','Demande créée','Nouvelle demande de congé soumise','2026-02-23 18:53:18'),
(26,15,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-02-24 18:53:18'),
(27,16,11,'create','Demande créée','Nouvelle demande de congé soumise','2026-05-24 18:53:18'),
(28,16,1,'reject','Demande rejetée','La demande de congé a été rejetée - planification insuffisante','2026-05-29 18:53:18'),
(29,17,12,'create','Demande créée','Nouvelle demande de congé soumise','2026-09-08 18:53:18'),
(30,18,12,'create','Demande créée','Nouvelle demande de congé soumise','2026-10-22 18:53:18'),
(31,19,13,'create','Demande créée','Nouvelle demande de congé soumise','2026-05-27 18:53:18'),
(32,19,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-05-30 18:53:18'),
(33,20,14,'create','Demande créée','Nouvelle demande de congé soumise','2026-11-03 18:53:18'),
(34,20,1,'approve','Demande approuvée','La demande de congé a été approuvée par le manager','2026-11-07 18:53:18');
/*!40000 ALTER TABLE `conges_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES
('DoctrineMigrations\\Version20250917160000','2026-11-03 18:51:49',347),
('DoctrineMigrations\\Version20261001100943','2026-11-03 18:51:50',51),
('DoctrineMigrations\\Version20261001112017','2026-11-03 18:51:50',319),
('DoctrineMigrations\\Version20261001135311','2026-11-03 18:51:50',270),
('DoctrineMigrations\\Version20261001141457','2026-11-03 18:51:50',51),
('DoctrineMigrations\\Version20261001143414','2026-11-03 18:51:50',241),
('DoctrineMigrations\\Version20261001144611','2026-11-03 18:51:51',355),
('DoctrineMigrations\\Version20261001185239','2026-11-03 18:51:51',157),
('DoctrineMigrations\\Version20261001190641','2026-11-03 18:51:51',330),
('DoctrineMigrations\\Version20261001193647','2026-11-03 18:51:51',307),
('DoctrineMigrations\\Version20261001202125','2026-11-03 18:51:52',360),
('DoctrineMigrations\\Version20261001204259','2026-11-03 18:51:52',367),
('DoctrineMigrations\\Version20261001211406','2026-11-03 18:51:52',238);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `affectation_id` int(11) DEFAULT NULL,
  `ajoute_par_id` int(11) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `categorie` varchar(50) NOT NULL,
  `niveau_confidentialite` varchar(50) NOT NULL,
  `statut` varchar(50) NOT NULL,
  `fichier_nom_original` varchar(255) NOT NULL,
  `fichier_nom_stockage` varchar(255) NOT NULL,
  `type_mime` varchar(100) NOT NULL,
  `taille` int(11) NOT NULL,
  `fichier_hash` varchar(64) DEFAULT NULL,
  `date_document` date DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D8698A76DAA76F43` (`ajoute_par_id`),
  KEY `idx_document_institution` (`institution_id`),
  KEY `idx_document_employe` (`employe_id`),
  KEY `idx_document_affectation` (`affectation_id`),
  KEY `idx_document_categorie` (`categorie`),
  KEY `idx_document_statut` (`statut`),
  KEY `idx_document_confidentialite` (`niveau_confidentialite`),
  CONSTRAINT `FK_D8698A7610405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D8698A761B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D8698A766D0ABA22` FOREIGN KEY (`affectation_id`) REFERENCES `affectation` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D8698A76DAA76F43` FOREIGN KEY (`ajoute_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document`
--

LOCK TABLES `document` WRITE;
/*!40000 ALTER TABLE `document` DISABLE KEYS */;
/*!40000 ALTER TABLE `document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_version`
--

DROP TABLE IF EXISTS `document_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `cree_par_id` int(11) DEFAULT NULL,
  `numero_version` int(11) NOT NULL,
  `fichier_nom_original` varchar(255) NOT NULL,
  `fichier_nom_stockage` varchar(255) NOT NULL,
  `fichier_hash` varchar(64) DEFAULT NULL,
  `taille` int(11) NOT NULL,
  `type_mime` varchar(100) NOT NULL,
  `commentaire_version` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1B73751FFC29C013` (`cree_par_id`),
  KEY `idx_doc_version_document` (`document_id`),
  KEY `idx_doc_version_institution` (`institution_id`),
  KEY `idx_doc_version_numero` (`numero_version`),
  CONSTRAINT `FK_1B73751F10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1B73751FC33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1B73751FFC29C013` FOREIGN KEY (`cree_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_version`
--

LOCK TABLES `document_version` WRITE;
/*!40000 ALTER TABLE `document_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employe`
--

DROP TABLE IF EXISTS `employe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fonction_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `numero_telephone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `salaire` double NOT NULL,
  `statut` tinyint(1) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `adresse` longtext DEFAULT NULL,
  `matricule` varchar(255) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `structure_id` int(11) DEFAULT NULL,
  `lieu_naissance` varchar(255) DEFAULT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `nationalite` varchar(100) DEFAULT NULL,
  `date_prise_service` date DEFAULT NULL,
  `statut_administratif` varchar(50) DEFAULT NULL,
  `corps` varchar(100) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `echelon` varchar(20) DEFAULT NULL,
  `categorie` varchar(20) DEFAULT NULL,
  `regime` varchar(50) DEFAULT NULL,
  `situation_administrative` varchar(50) DEFAULT NULL,
  `poste_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F804D3B9A76ED395` (`user_id`),
  KEY `IDX_F804D3B957889920` (`fonction_id`),
  KEY `IDX_F804D3B9ED5CA9E6` (`service_id`),
  KEY `idx_employe_institution` (`institution_id`),
  KEY `idx_employe_structure` (`structure_id`),
  KEY `idx_employe_matricule` (`matricule`),
  KEY `idx_employe_poste` (`poste_id`),
  CONSTRAINT `FK_F804D3B910405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`),
  CONSTRAINT `FK_F804D3B92534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F804D3B957889920` FOREIGN KEY (`fonction_id`) REFERENCES `fonction` (`id`),
  CONSTRAINT `FK_F804D3B9A0905086` FOREIGN KEY (`poste_id`) REFERENCES `poste` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F804D3B9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_F804D3B9ED5CA9E6` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employe`
--

LOCK TABLES `employe` WRITE;
/*!40000 ALTER TABLE `employe` DISABLE KEYS */;
INSERT INTO `employe` VALUES
(1,1,1,1,1,'Damba','Dominique','1980-01-01','22 11 11 11','dominiquedamba@fake.io',500000,1,'2020-01-01','Avenue Charles de Gaulle, N\'Djamena','EMP0001',1,NULL,0,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(2,2,2,2,1,'Oumar','Jean','1980-01-01','66 22 22 22','jean.oumar1@fake.io',550000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0011',11,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(3,2,2,2,1,'Nom2','Prenom2','1980-01-01','66 22 22 22','employe2@fake.io',550000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0012',12,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(4,3,3,6,2,'Hissein','Marie','1980-01-01','77 33 33 33','marie.hissein2@fake.io',600000,1,'2020-01-01','Avenue Mobutu, N\'Djamena','EMP0021',21,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(5,4,4,8,3,'Mahamat','Paul','1980-01-01','99 44 44 44','paul.mahamat3@fake.io',650000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0031',31,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(6,4,4,8,3,'Nom4','Prenom4','1980-01-01','99 44 44 44','employe4@fake.io',650000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0032',32,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(7,5,5,11,4,'Abakar','Claire','1980-01-01','22 11 11 11','claire.abakar4@fake.io',700000,1,'2020-01-01','Avenue Charles de Gaulle, N\'Djamena','EMP0041',41,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(8,6,6,17,5,'Saleh','Ahmed','1980-01-01','66 22 22 22','ahmed.saleh5@fake.io',750000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0051',51,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(9,6,6,17,5,'Nom6','Prenom6','1980-01-01','66 22 22 22','employe6@fake.io',750000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0052',52,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(10,7,7,20,6,'Hassan','Fatima','1980-01-01','77 33 33 33','fatima.hassan6@fake.io',800000,1,'2020-01-01','Avenue Mobutu, N\'Djamena','EMP0061',61,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(11,8,8,2,1,'Ali','Pierre','1980-01-01','99 44 44 44','pierre.ali7@fake.io',850000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0071',71,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(12,8,8,2,1,'Nom8','Prenom8','1980-01-01','99 44 44 44','employe8@fake.io',850000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0072',72,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(13,9,9,4,2,'Abdel','Amina','1980-01-01','22 11 11 11','amina.abdel8@fake.io',900000,1,'2020-01-01','Avenue Charles de Gaulle, N\'Djamena','EMP0081',81,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(14,10,10,8,3,'Youssef','David','1980-01-01','66 22 22 22','david.youssef9@fake.io',950000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0091',91,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(15,10,10,8,3,'Nom10','Prenom10','1980-01-01','66 22 22 22','employe10@fake.io',950000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0092',92,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(16,11,11,13,4,'Ibrahim','Sarah','1980-01-01','77 33 33 33','sarah.ibrahim10@fake.io',1000000,1,'2020-01-01','Avenue Mobutu, N\'Djamena','EMP0101',101,NULL,0,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(17,12,12,17,5,'Adam','Michel','1980-01-01','99 44 44 44','michel.adam11@fake.io',1050000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0111',111,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(18,12,12,17,5,'Nom12','Prenom12','1980-01-01','99 44 44 44','employe12@fake.io',1050000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0112',112,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(19,13,1,18,6,'Moussa','Aïcha','1980-01-01','22 11 11 11','aïcha.moussa12@fake.io',1100000,1,'2020-01-01','Avenue Charles de Gaulle, N\'Djamena','EMP0121',121,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(20,14,2,2,1,'Idriss','François','1980-01-01','66 22 22 22','françois.idriss13@fake.io',1150000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0131',131,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(21,14,2,2,1,'Nom14','Prenom14','1980-01-01','66 22 22 22','employe14@fake.io',1150000,1,'2020-01-01','Quartier Chagoua, N\'Djamena','EMP0132',132,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(22,15,3,6,2,'Haroun','Khadija','1980-01-01','77 33 33 33','khadija.haroun14@fake.io',1200000,1,'2020-01-01','Avenue Mobutu, N\'Djamena','EMP0141',141,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(23,16,4,8,3,'Zakaria','Nicolas','1980-01-01','99 44 44 44','nicolas.zakaria15@fake.io',1250000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0151',151,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL),
(24,16,4,8,3,'Nom16','Prenom16','1980-01-01','99 44 44 44','employe16@fake.io',1250000,1,'2020-01-01','Quartier Ridina, N\'Djamena','EMP0152',152,NULL,1,NULL,NULL,NULL,NULL,NULL,'CONTRACTUEL',NULL,NULL,NULL,NULL,NULL,'EN_ACTIVITE',NULL);
/*!40000 ALTER TABLE `employe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fonction`
--

DROP TABLE IF EXISTS `fonction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fonction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fonction_institution` (`institution_id`),
  CONSTRAINT `FK_900D5BD10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fonction`
--

LOCK TABLES `fonction` WRITE;
/*!40000 ALTER TABLE `fonction` DISABLE KEYS */;
INSERT INTO `fonction` VALUES
(1,'Directeur Général','Description du poste de Directeur Général','directeur-général',NULL),
(2,'Directeur Adjoint','Description du poste de Directeur Adjoint','directeur-adjoint',NULL),
(3,'Chef de Service','Description du poste de Chef de Service','chef-de-service',NULL),
(4,'Manager','Description du poste de Manager','manager',NULL),
(5,'Secrétaire','Description du poste de Secrétaire','secrétaire',NULL),
(6,'Technicien','Description du poste de Technicien','technicien',NULL),
(7,'Médecin','Description du poste de Médecin','médecin',NULL),
(8,'Infirmier','Description du poste de Infirmier','infirmier',NULL),
(9,'Comptable','Description du poste de Comptable','comptable',NULL),
(10,'Agent RH','Description du poste de Agent RH','agent-rh',NULL),
(11,'Chauffeur','Description du poste de Chauffeur','chauffeur',NULL),
(12,'Agent de sécurité','Description du poste de Agent de sécurité','agent-de-sécurité',NULL);
/*!40000 ALTER TABLE `fonction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institution`
--

DROP TABLE IF EXISTS `institution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_institution_id` int(11) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `code` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `slug` varchar(255) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3A9F98E577153098` (`code`),
  KEY `IDX_3A9F98E546837012` (`type_institution_id`),
  KEY `idx_institution_code` (`code`),
  KEY `idx_institution_status` (`status`),
  KEY `IDX_3A9F98E553C59D72` (`responsable_id`),
  CONSTRAINT `FK_3A9F98E546837012` FOREIGN KEY (`type_institution_id`) REFERENCES `type_institution` (`id`),
  CONSTRAINT `FK_3A9F98E553C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institution`
--

LOCK TABLES `institution` WRITE;
/*!40000 ALTER TABLE `institution` DISABLE KEYS */;
INSERT INTO `institution` VALUES
(1,NULL,'Ministère de la Santé','Description de Ministère de la Santé - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,NULL,'Université de N\'Djamena','Description de Université de N\'Djamena - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,NULL,'Hôpital Central de N\'Djamena','Description de Hôpital Central de N\'Djamena - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(4,NULL,'Direction des Ressources Humaines','Description de Direction des Ressources Humaines - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(5,NULL,'Ministère de l\'Éducation','Description de Ministère de l\'Éducation - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(6,NULL,'Centre Hospitalier Régional','Description de Centre Hospitalier Régional - Institution spécialisée dans son domaine.','2026-11-03 18:53:10','2026-11-03 18:53:10',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `institution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interim_logs`
--

DROP TABLE IF EXISTS `interim_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interim_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interim_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A630EC4A76ED395` (`user_id`),
  KEY `idx_interim_log_interim` (`interim_id`),
  KEY `idx_interim_log_action` (`action`),
  CONSTRAINT `FK_A630EC429C96BD8` FOREIGN KEY (`interim_id`) REFERENCES `interims` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A630EC4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interim_logs`
--

LOCK TABLES `interim_logs` WRITE;
/*!40000 ALTER TABLE `interim_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `interim_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interims`
--

DROP TABLE IF EXISTS `interims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `titulaire_id` int(11) NOT NULL,
  `interimaire_id` int(11) NOT NULL,
  `poste_id` int(11) DEFAULT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `valide_par_id` int(11) DEFAULT NULL,
  `acte_id` int(11) DEFAULT NULL,
  `cree_par_id` int(11) DEFAULT NULL,
  `reference` varchar(50) NOT NULL,
  `type_delegation` varchar(50) NOT NULL,
  `perimetre` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`perimetre`)),
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `motif` longtext NOT NULL,
  `statut` smallint(6) NOT NULL DEFAULT 0,
  `date_validation` datetime DEFAULT NULL,
  `commentaire_validation` longtext DEFAULT NULL,
  `date_fin_effective` date DEFAULT NULL,
  `motif_fin` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_2F01497FA0905086` (`poste_id`),
  KEY `IDX_2F01497F2534008B` (`structure_id`),
  KEY `IDX_2F01497F6AF12ED9` (`valide_par_id`),
  KEY `IDX_2F01497FA767B8C7` (`acte_id`),
  KEY `IDX_2F01497FFC29C013` (`cree_par_id`),
  KEY `idx_interim_institution` (`institution_id`),
  KEY `idx_interim_titulaire` (`titulaire_id`),
  KEY `idx_interim_interimaire` (`interimaire_id`),
  KEY `idx_interim_statut` (`statut`),
  KEY `idx_interim_date_debut` (`date_debut`),
  KEY `idx_interim_date_fin` (`date_fin`),
  CONSTRAINT `FK_2F01497F10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2F01497F2534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2F01497F5CA839FE` FOREIGN KEY (`interimaire_id`) REFERENCES `employe` (`id`),
  CONSTRAINT `FK_2F01497F6AF12ED9` FOREIGN KEY (`valide_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2F01497FA0905086` FOREIGN KEY (`poste_id`) REFERENCES `poste` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2F01497FA10273AA` FOREIGN KEY (`titulaire_id`) REFERENCES `employe` (`id`),
  CONSTRAINT `FK_2F01497FA767B8C7` FOREIGN KEY (`acte_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2F01497FFC29C013` FOREIGN KEY (`cree_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interims`
--

LOCK TABLES `interims` WRITE;
/*!40000 ALTER TABLE `interims` DISABLE KEYS */;
/*!40000 ALTER TABLE `interims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission`
--

DROP TABLE IF EXISTS `mission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `chef_mission_id` int(11) DEFAULT NULL,
  `acte_id` int(11) DEFAULT NULL,
  `document_rapport_id` int(11) DEFAULT NULL,
  `valide_par_id` int(11) DEFAULT NULL,
  `cree_par_id` int(11) DEFAULT NULL,
  `reference` varchar(50) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `pays` varchar(100) NOT NULL DEFAULT 'Côte d''Ivoire',
  `lieu_precis` varchar(255) DEFAULT NULL,
  `itineraire` longtext DEFAULT NULL,
  `mode_transport` varchar(50) NOT NULL DEFAULT 'VEHICULE_SERVICE',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `duree_jours` int(11) NOT NULL DEFAULT 1,
  `budget_estime` decimal(12,2) DEFAULT NULL,
  `imputation_budgetaire` varchar(100) DEFAULT NULL,
  `prise_en_charge` varchar(50) NOT NULL DEFAULT 'TOTALEMENT_INSTITUTION',
  `statut` smallint(6) NOT NULL DEFAULT 0,
  `rapport_texte` longtext DEFAULT NULL,
  `date_rapport` datetime DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `commentaire_validation` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_9067F23C5907F2C3` (`chef_mission_id`),
  KEY `IDX_9067F23CA767B8C7` (`acte_id`),
  KEY `IDX_9067F23CBC386710` (`document_rapport_id`),
  KEY `IDX_9067F23C6AF12ED9` (`valide_par_id`),
  KEY `IDX_9067F23CFC29C013` (`cree_par_id`),
  KEY `idx_mission_statut` (`statut`),
  KEY `idx_mission_dates` (`date_debut`,`date_fin`),
  KEY `idx_mission_institution` (`institution_id`),
  KEY `idx_mission_reference` (`reference`),
  CONSTRAINT `FK_9067F23C10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9067F23C5907F2C3` FOREIGN KEY (`chef_mission_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9067F23C6AF12ED9` FOREIGN KEY (`valide_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9067F23CA767B8C7` FOREIGN KEY (`acte_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9067F23CBC386710` FOREIGN KEY (`document_rapport_id`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9067F23CFC29C013` FOREIGN KEY (`cree_par_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission`
--

LOCK TABLES `mission` WRITE;
/*!40000 ALTER TABLE `mission` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_log`
--

DROP TABLE IF EXISTS `mission_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_145AFFBCBE6CAE90` (`mission_id`),
  KEY `IDX_145AFFBCA76ED395` (`user_id`),
  KEY `idx_mission_log_action` (`action`),
  KEY `idx_mission_log_created_at` (`created_at`),
  CONSTRAINT `FK_145AFFBCA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_145AFFBCBE6CAE90` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_log`
--

LOCK TABLES `mission_log` WRITE;
/*!40000 ALTER TABLE `mission_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_participant`
--

DROP TABLE IF EXISTS `mission_participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mission_participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `acte_individuel_id` int(11) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'PARTICIPANT',
  `indemnite_journaliere` decimal(10,2) DEFAULT NULL,
  `frais_transport` decimal(10,2) DEFAULT NULL,
  `total_frais` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_B00021F0BE6CAE90` (`mission_id`),
  KEY `IDX_B00021F01B65292` (`employe_id`),
  KEY `IDX_B00021F0105F3AA4` (`acte_individuel_id`),
  KEY `idx_mission_participant_role` (`role`),
  CONSTRAINT `FK_B00021F0105F3AA4` FOREIGN KEY (`acte_individuel_id`) REFERENCES `acte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_B00021F01B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B00021F0BE6CAE90` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_participant`
--

LOCK TABLES `mission_participant` WRITE;
/*!40000 ALTER TABLE `mission_participant` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_participant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `occupation_poste`
--

DROP TABLE IF EXISTS `occupation_poste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `occupation_poste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poste_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `motif` varchar(50) DEFAULT NULL,
  `commentaire` longtext DEFAULT NULL,
  `is_actif` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_occ_poste_institution` (`institution_id`),
  KEY `idx_occ_poste_poste` (`poste_id`),
  KEY `idx_occ_poste_employe` (`employe_id`),
  KEY `idx_occ_poste_actif` (`is_actif`),
  CONSTRAINT `FK_7077F63D10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7077F63D1B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7077F63DA0905086` FOREIGN KEY (`poste_id`) REFERENCES `poste` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `occupation_poste`
--

LOCK TABLES `occupation_poste` WRITE;
/*!40000 ALTER TABLE `occupation_poste` DISABLE KEYS */;
/*!40000 ALTER TABLE `occupation_poste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organisation_history`
--

DROP TABLE IF EXISTS `organisation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `changed_by_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`details`)),
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_443E0748828AD0A0` (`changed_by_id`),
  KEY `idx_org_history_institution` (`institution_id`),
  KEY `idx_org_history_entity` (`entity_type`,`entity_id`),
  KEY `idx_org_history_action` (`action`),
  KEY `idx_org_history_created_at` (`created_at`),
  CONSTRAINT `FK_443E074810405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_443E0748828AD0A0` FOREIGN KEY (`changed_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organisation_history`
--

LOCK TABLES `organisation_history` WRITE;
/*!40000 ALTER TABLE `organisation_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `organisation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_setting`
--

DROP TABLE IF EXISTS `organization_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `app_name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `organization_type` varchar(50) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `secondary_logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(20) NOT NULL,
  `secondary_color` varchar(20) NOT NULL,
  `accent_color` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `locale` varchar(10) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `date_format` varchar(20) NOT NULL,
  `time_format` varchar(20) NOT NULL,
  `pdf_header` longtext DEFAULT NULL,
  `pdf_footer` longtext DEFAULT NULL,
  `enabled_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`enabled_modules`)),
  `is_installed` tinyint(1) NOT NULL,
  `installed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_setting`
--

LOCK TABLES `organization_setting` WRITE;
/*!40000 ALTER TABLE `organization_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `organization_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_token`
--

DROP TABLE IF EXISTS `password_reset_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `used_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_6B7BA4B6A76ED395` (`user_id`),
  KEY `idx_password_reset_token_hash` (`token_hash`),
  CONSTRAINT `FK_6B7BA4B6A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_token`
--

LOCK TABLES `password_reset_token` WRITE;
/*!40000 ALTER TABLE `password_reset_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pointage`
--

DROP TABLE IF EXISTS `pointage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pointage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `est_present` tinyint(1) NOT NULL,
  `motif_absence` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `date_entree` datetime NOT NULL,
  `date_sortie` datetime DEFAULT NULL,
  `commentaire` varchar(1000) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7591B201B65292` (`employe_id`),
  KEY `idx_pointage_institution` (`institution_id`),
  CONSTRAINT `FK_7591B2010405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7591B201B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pointage`
--

LOCK TABLES `pointage` WRITE;
/*!40000 ALTER TABLE `pointage` DISABLE KEYS */;
INSERT INTO `pointage` VALUES
(1,1,'2026-10-05','08:15:00','16:25:00',1,NULL,'Présent','2026-10-05 18:53:18','2026-10-05 16:25:00','Arrivé en retard - embouteillage',1),
(2,1,'2026-10-06','09:30:00','17:50:00',1,NULL,'En retard','2026-10-06 18:53:18','2026-10-06 17:50:00','Parti plus tôt - rendez-vous médical',1),
(3,1,'2026-10-07','07:45:00','16:15:00',1,NULL,'Présent','2026-10-07 18:53:18','2026-10-07 16:15:00','Journée complète de travail',1),
(4,1,'2026-10-08','08:00:00','16:40:00',1,NULL,'Présent','2026-10-08 18:53:18','2026-10-08 16:40:00','Formation externe',1),
(5,1,'2026-10-09','09:15:00','18:05:00',1,NULL,'En retard','2026-10-09 18:53:18','2026-10-09 18:05:00','Mission à l\'extérieur',1),
(6,1,'2026-10-12','09:00:00','17:20:00',1,NULL,'En retard','2026-10-12 18:53:18','2026-10-12 17:20:00','Parti plus tôt - rendez-vous médical',1),
(7,1,'2026-10-13','07:15:00','15:45:00',1,NULL,'Présent','2026-10-13 18:53:18','2026-10-13 15:45:00','Journée complète de travail',1),
(8,1,'2026-10-14','08:30:00','17:10:00',1,NULL,'Présent','2026-10-14 18:53:18','2026-10-14 17:10:00','Formation externe',1),
(9,1,'2026-10-15','09:45:00','18:35:00',1,NULL,'En retard','2026-10-15 18:53:18','2026-10-15 18:35:00','Mission à l\'extérieur',1),
(10,1,'2026-10-16','07:00:00','15:00:00',1,NULL,'Présent','2026-10-16 18:53:18','2026-10-16 15:00:00','Pointage normal',1),
(11,1,'2026-10-19','07:45:00','16:15:00',1,NULL,'Présent','2026-10-19 18:53:18','2026-10-19 16:15:00','Journée complète de travail',1),
(12,1,'2026-10-20','08:00:00','16:40:00',1,NULL,'Présent','2026-10-20 18:53:18','2026-10-20 16:40:00','Formation externe',1),
(13,1,'2026-10-21','09:15:00','18:05:00',1,NULL,'En retard','2026-10-21 18:53:18','2026-10-21 18:05:00','Mission à l\'extérieur',1),
(14,1,'2026-10-22','07:30:00','15:30:00',1,NULL,'Présent','2026-10-22 18:53:18','2026-10-22 15:30:00','Pointage normal',1),
(15,1,'2026-10-23','08:45:00','16:55:00',1,NULL,'Présent','2026-10-23 18:53:18','2026-10-23 16:55:00','Arrivé en retard - embouteillage',1),
(16,2,'2026-10-05','08:15:00','16:25:00',1,NULL,'Présent','2026-10-05 18:53:18','2026-10-05 16:25:00','Arrivé en retard - embouteillage',1),
(17,2,'2026-10-06','09:30:00','17:50:00',1,NULL,'En retard','2026-10-06 18:53:18','2026-10-06 17:50:00','Parti plus tôt - rendez-vous médical',1),
(18,2,'2026-10-07','07:45:00','16:15:00',1,NULL,'Présent','2026-10-07 18:53:18','2026-10-07 16:15:00','Journée complète de travail',1),
(19,2,'2026-10-08','08:00:00','16:40:00',1,NULL,'Présent','2026-10-08 18:53:18','2026-10-08 16:40:00','Formation externe',1),
(20,2,'2026-10-09','09:15:00','18:05:00',1,NULL,'En retard','2026-10-09 18:53:18','2026-10-09 18:05:00','Mission à l\'extérieur',1),
(21,2,'2026-10-12','09:00:00','17:20:00',1,NULL,'En retard','2026-10-12 18:53:18','2026-10-12 17:20:00','Parti plus tôt - rendez-vous médical',1),
(22,2,'2026-10-13','07:15:00','15:45:00',1,NULL,'Présent','2026-10-13 18:53:18','2026-10-13 15:45:00','Journée complète de travail',1),
(23,2,'2026-10-14','08:30:00','17:10:00',1,NULL,'Présent','2026-10-14 18:53:18','2026-10-14 17:10:00','Formation externe',1),
(24,2,'2026-10-15','09:45:00','18:35:00',1,NULL,'En retard','2026-10-15 18:53:18','2026-10-15 18:35:00','Mission à l\'extérieur',1),
(25,2,'2026-10-16','07:00:00','15:00:00',1,NULL,'Présent','2026-10-16 18:53:18','2026-10-16 15:00:00','Pointage normal',1),
(26,2,'2026-10-19','07:45:00','16:15:00',1,NULL,'Présent','2026-10-19 18:53:18','2026-10-19 16:15:00','Journée complète de travail',1),
(27,2,'2026-10-20','08:00:00','16:40:00',1,NULL,'Présent','2026-10-20 18:53:18','2026-10-20 16:40:00','Formation externe',1),
(28,2,'2026-10-21','09:15:00','18:05:00',1,NULL,'En retard','2026-10-21 18:53:18','2026-10-21 18:05:00','Mission à l\'extérieur',1),
(29,2,'2026-10-22','07:30:00','15:30:00',1,NULL,'Présent','2026-10-22 18:53:18','2026-10-22 15:30:00','Pointage normal',1),
(30,2,'2026-10-23','08:45:00','16:55:00',1,NULL,'Présent','2026-10-23 18:53:18','2026-10-23 16:55:00','Arrivé en retard - embouteillage',1),
(31,3,'2026-10-05','08:15:00','16:25:00',1,NULL,'Présent','2026-10-05 18:53:18','2026-10-05 16:25:00','Arrivé en retard - embouteillage',1),
(32,3,'2026-10-06','09:30:00','17:50:00',1,NULL,'En retard','2026-10-06 18:53:18','2026-10-06 17:50:00','Parti plus tôt - rendez-vous médical',1),
(33,3,'2026-10-07','07:45:00','16:15:00',1,NULL,'Présent','2026-10-07 18:53:18','2026-10-07 16:15:00','Journée complète de travail',1),
(34,3,'2026-10-08','08:00:00','16:40:00',1,NULL,'Présent','2026-10-08 18:53:18','2026-10-08 16:40:00','Formation externe',1),
(35,3,'2026-10-09','09:15:00','18:05:00',1,NULL,'En retard','2026-10-09 18:53:18','2026-10-09 18:05:00','Mission à l\'extérieur',1),
(36,3,'2026-10-12','09:00:00','17:20:00',1,NULL,'En retard','2026-10-12 18:53:18','2026-10-12 17:20:00','Parti plus tôt - rendez-vous médical',1),
(37,3,'2026-10-13','07:15:00','15:45:00',1,NULL,'Présent','2026-10-13 18:53:18','2026-10-13 15:45:00','Journée complète de travail',1),
(38,3,'2026-10-14','08:30:00','17:10:00',1,NULL,'Présent','2026-10-14 18:53:18','2026-10-14 17:10:00','Formation externe',1),
(39,3,'2026-10-15','09:45:00','18:35:00',1,NULL,'En retard','2026-10-15 18:53:18','2026-10-15 18:35:00','Mission à l\'extérieur',1),
(40,3,'2026-10-16','07:00:00','15:00:00',1,NULL,'Présent','2026-10-16 18:53:18','2026-10-16 15:00:00','Pointage normal',1),
(41,3,'2026-10-19','07:45:00','16:15:00',1,NULL,'Présent','2026-10-19 18:53:18','2026-10-19 16:15:00','Journée complète de travail',1),
(42,3,'2026-10-20','08:00:00','16:40:00',1,NULL,'Présent','2026-10-20 18:53:18','2026-10-20 16:40:00','Formation externe',1),
(43,3,'2026-10-21','09:15:00','18:05:00',1,NULL,'En retard','2026-10-21 18:53:18','2026-10-21 18:05:00','Mission à l\'extérieur',1),
(44,3,'2026-10-22','07:30:00','15:30:00',1,NULL,'Présent','2026-10-22 18:53:18','2026-10-22 15:30:00','Pointage normal',1),
(45,3,'2026-10-23','08:45:00','16:55:00',1,NULL,'Présent','2026-10-23 18:53:18','2026-10-23 16:55:00','Arrivé en retard - embouteillage',1),
(46,4,'2026-10-05','08:15:00','16:25:00',1,NULL,'Présent','2026-10-05 18:53:18','2026-10-05 16:25:00','Arrivé en retard - embouteillage',2),
(47,4,'2026-10-06','09:30:00','17:50:00',1,NULL,'En retard','2026-10-06 18:53:18','2026-10-06 17:50:00','Parti plus tôt - rendez-vous médical',2),
(48,4,'2026-10-07','07:45:00','16:15:00',1,NULL,'Présent','2026-10-07 18:53:18','2026-10-07 16:15:00','Journée complète de travail',2),
(49,4,'2026-10-08','08:00:00','16:40:00',1,NULL,'Présent','2026-10-08 18:53:18','2026-10-08 16:40:00','Formation externe',2),
(50,4,'2026-10-09','09:15:00','18:05:00',1,NULL,'En retard','2026-10-09 18:53:18','2026-10-09 18:05:00','Mission à l\'extérieur',2),
(51,4,'2026-10-12','09:00:00','17:20:00',1,NULL,'En retard','2026-10-12 18:53:18','2026-10-12 17:20:00','Parti plus tôt - rendez-vous médical',2),
(52,4,'2026-10-13','07:15:00','15:45:00',1,NULL,'Présent','2026-10-13 18:53:18','2026-10-13 15:45:00','Journée complète de travail',2),
(53,4,'2026-10-14','08:30:00','17:10:00',1,NULL,'Présent','2026-10-14 18:53:18','2026-10-14 17:10:00','Formation externe',2),
(54,4,'2026-10-15','09:45:00','18:35:00',1,NULL,'En retard','2026-10-15 18:53:18','2026-10-15 18:35:00','Mission à l\'extérieur',2),
(55,4,'2026-10-16','07:00:00','15:00:00',1,NULL,'Présent','2026-10-16 18:53:18','2026-10-16 15:00:00','Pointage normal',2),
(56,4,'2026-10-19','07:45:00','16:15:00',1,NULL,'Présent','2026-10-19 18:53:18','2026-10-19 16:15:00','Journée complète de travail',2),
(57,4,'2026-10-20','08:00:00','16:40:00',1,NULL,'Présent','2026-10-20 18:53:18','2026-10-20 16:40:00','Formation externe',2),
(58,4,'2026-10-21','09:15:00','18:05:00',1,NULL,'En retard','2026-10-21 18:53:18','2026-10-21 18:05:00','Mission à l\'extérieur',2),
(59,4,'2026-10-22','07:30:00','15:30:00',1,NULL,'Présent','2026-10-22 18:53:18','2026-10-22 15:30:00','Pointage normal',2),
(60,4,'2026-10-23','08:45:00','16:55:00',1,NULL,'Présent','2026-10-23 18:53:18','2026-10-23 16:55:00','Arrivé en retard - embouteillage',2),
(61,5,'2026-10-05','08:15:00','16:25:00',1,NULL,'Présent','2026-10-05 18:53:18','2026-10-05 16:25:00','Arrivé en retard - embouteillage',3),
(62,5,'2026-10-06','09:30:00','17:50:00',1,NULL,'En retard','2026-10-06 18:53:18','2026-10-06 17:50:00','Parti plus tôt - rendez-vous médical',3),
(63,5,'2026-10-07','07:45:00','16:15:00',1,NULL,'Présent','2026-10-07 18:53:18','2026-10-07 16:15:00','Journée complète de travail',3),
(64,5,'2026-10-08','08:00:00','16:40:00',1,NULL,'Présent','2026-10-08 18:53:18','2026-10-08 16:40:00','Formation externe',3),
(65,5,'2026-10-09','09:15:00','18:05:00',1,NULL,'En retard','2026-10-09 18:53:18','2026-10-09 18:05:00','Mission à l\'extérieur',3),
(66,5,'2026-10-12','09:00:00','17:20:00',1,NULL,'En retard','2026-10-12 18:53:18','2026-10-12 17:20:00','Parti plus tôt - rendez-vous médical',3),
(67,5,'2026-10-13','07:15:00','15:45:00',1,NULL,'Présent','2026-10-13 18:53:18','2026-10-13 15:45:00','Journée complète de travail',3),
(68,5,'2026-10-14','08:30:00','17:10:00',1,NULL,'Présent','2026-10-14 18:53:18','2026-10-14 17:10:00','Formation externe',3),
(69,5,'2026-10-15','09:45:00','18:35:00',1,NULL,'En retard','2026-10-15 18:53:18','2026-10-15 18:35:00','Mission à l\'extérieur',3),
(70,5,'2026-10-16','07:00:00','15:00:00',1,NULL,'Présent','2026-10-16 18:53:18','2026-10-16 15:00:00','Pointage normal',3),
(71,5,'2026-10-19','07:45:00','16:15:00',1,NULL,'Présent','2026-10-19 18:53:18','2026-10-19 16:15:00','Journée complète de travail',3),
(72,5,'2026-10-20','08:00:00','16:40:00',1,NULL,'Présent','2026-10-20 18:53:18','2026-10-20 16:40:00','Formation externe',3),
(73,5,'2026-10-21','09:15:00','18:05:00',1,NULL,'En retard','2026-10-21 18:53:18','2026-10-21 18:05:00','Mission à l\'extérieur',3),
(74,5,'2026-10-22','07:30:00','15:30:00',1,NULL,'Présent','2026-10-22 18:53:18','2026-10-22 15:30:00','Pointage normal',3),
(75,5,'2026-10-23','08:45:00','16:55:00',1,NULL,'Présent','2026-10-23 18:53:18','2026-10-23 16:55:00','Arrivé en retard - embouteillage',3),
(76,1,'2026-11-03','08:00:00',NULL,1,NULL,'Présent','2026-11-03 18:53:18',NULL,'Pointage du jour',1),
(77,2,'2026-11-03','08:15:00',NULL,1,NULL,'En retard','2026-11-03 18:53:18',NULL,'Pointage du jour',1),
(78,3,'2026-11-03','08:30:00',NULL,1,NULL,'En retard','2026-11-03 18:53:18',NULL,'Pointage du jour',1);
/*!40000 ALTER TABLE `pointage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poste`
--

DROP TABLE IF EXISTS `poste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `fonction_id` int(11) DEFAULT NULL,
  `occupant_actuel_id` int(11) DEFAULT NULL,
  `reference` varchar(50) NOT NULL,
  `intitule` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `competences_requises` longtext DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `statut` varchar(50) NOT NULL,
  `date_creation` date NOT NULL,
  `date_fermeture` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7C890FAB57889920` (`fonction_id`),
  KEY `IDX_7C890FAB6299F7C0` (`occupant_actuel_id`),
  KEY `idx_poste_institution` (`institution_id`),
  KEY `idx_poste_structure` (`structure_id`),
  KEY `idx_poste_service` (`service_id`),
  KEY `idx_poste_statut` (`statut`),
  KEY `idx_poste_reference` (`reference`),
  CONSTRAINT `FK_7C890FAB10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7C890FAB2534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C890FAB57889920` FOREIGN KEY (`fonction_id`) REFERENCES `fonction` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C890FAB6299F7C0` FOREIGN KEY (`occupant_actuel_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C890FABED5CA9E6` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poste`
--

LOCK TABLES `poste` WRITE;
/*!40000 ALTER TABLE `poste` DISABLE KEYS */;
/*!40000 ALTER TABLE `poste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presence`
--

DROP TABLE IF EXISTS `presence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) DEFAULT NULL,
  `date_presence` date DEFAULT NULL,
  `heure_entree` time DEFAULT NULL,
  `heure_sortie` time DEFAULT NULL,
  `statut` tinyint(1) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6977C7A51B65292` (`employe_id`),
  KEY `idx_presence_institution` (`institution_id`),
  CONSTRAINT `FK_6977C7A510405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6977C7A51B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presence`
--

LOCK TABLES `presence` WRITE;
/*!40000 ALTER TABLE `presence` DISABLE KEYS */;
/*!40000 ALTER TABLE `presence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `deleted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_audit_log`
--

DROP TABLE IF EXISTS `security_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_identifier` varchar(180) NOT NULL,
  `event` varchar(50) NOT NULL,
  `event_label` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `institution_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9E5F188AA76ED395` (`user_id`),
  KEY `idx_security_audit_log_event` (`event`),
  KEY `idx_security_audit_log_created_at` (`created_at`),
  KEY `idx_security_audit_log_user_identifier` (`user_identifier`),
  KEY `idx_security_audit_log_institution` (`institution_id`),
  CONSTRAINT `FK_9E5F188A10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9E5F188AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_audit_log`
--

LOCK TABLES `security_audit_log` WRITE;
/*!40000 ALTER TABLE `security_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service`
--

DROP TABLE IF EXISTS `service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `idx_service_institution` (`institution_id`),
  KEY `IDX_E19D9AD253C59D72` (`responsable_id`),
  KEY `idx_service_structure` (`structure_id`),
  KEY `idx_service_code` (`code`),
  KEY `idx_service_statut` (`statut`),
  CONSTRAINT `FK_E19D9AD210405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_E19D9AD22534008B` FOREIGN KEY (`structure_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E19D9AD253C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service`
--

LOCK TABLES `service` WRITE;
/*!40000 ALTER TABLE `service` DISABLE KEYS */;
INSERT INTO `service` VALUES
(1,1,'Administration','Description du service Administration de Ministère de la Santé',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(2,1,'Ressources Humaines','Description du service Ressources Humaines de Ministère de la Santé',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(3,1,'Comptabilité','Description du service Comptabilité de Ministère de la Santé',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(4,2,'Service Technique','Description du service Service Technique de Université de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(5,2,'Service Médical','Description du service Service Médical de Université de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(6,2,'Urgences','Description du service Urgences de Université de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(7,2,'Pédiatrie','Description du service Pédiatrie de Université de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(8,3,'Pédiatrie','Description du service Pédiatrie de Hôpital Central de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(9,3,'Chirurgie','Description du service Chirurgie de Hôpital Central de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(10,3,'Maintenance','Description du service Maintenance de Hôpital Central de N\'Djamena',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(11,4,'Sécurité','Description du service Sécurité de Direction des Ressources Humaines',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(12,4,'Administration','Description du service Administration de Direction des Ressources Humaines',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(13,4,'Ressources Humaines','Description du service Ressources Humaines de Direction des Ressources Humaines',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(14,4,'Comptabilité','Description du service Comptabilité de Direction des Ressources Humaines',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(15,5,'Comptabilité','Description du service Comptabilité de Ministère de l\'Éducation',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(16,5,'Service Technique','Description du service Service Technique de Ministère de l\'Éducation',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(17,5,'Service Médical','Description du service Service Médical de Ministère de l\'Éducation',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(18,6,'Urgences','Description du service Urgences de Centre Hospitalier Régional',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(19,6,'Pédiatrie','Description du service Pédiatrie de Centre Hospitalier Régional',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(20,6,'Chirurgie','Description du service Chirurgie de Centre Hospitalier Régional',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18'),
(21,6,'Maintenance','Description du service Maintenance de Centre Hospitalier Régional',NULL,NULL,NULL,'ACTIVE','2026-11-03 18:53:18','2026-11-03 18:53:18');
/*!40000 ALTER TABLE `service` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solde_conge`
--

DROP TABLE IF EXISTS `solde_conge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solde_conge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) NOT NULL,
  `type_conge_id` int(11) NOT NULL,
  `solde` decimal(10,2) NOT NULL,
  `updated_at` datetime NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `annee` int(11) DEFAULT NULL,
  `solde_acquis` decimal(10,2) NOT NULL DEFAULT 30.00,
  `solde_pris` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `IDX_EF1BB271B65292` (`employe_id`),
  KEY `IDX_EF1BB27753BDA5` (`type_conge_id`),
  KEY `idx_solde_conge_institution` (`institution_id`),
  KEY `idx_solde_conge_annee` (`annee`),
  CONSTRAINT `FK_EF1BB2710405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EF1BB271B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EF1BB27753BDA5` FOREIGN KEY (`type_conge_id`) REFERENCES `type_conges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solde_conge`
--

LOCK TABLES `solde_conge` WRITE;
/*!40000 ALTER TABLE `solde_conge` DISABLE KEYS */;
INSERT INTO `solde_conge` VALUES
(1,1,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(2,1,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(3,2,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(4,2,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(5,3,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(6,3,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(7,4,9,25.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(8,4,13,10.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(9,5,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(10,5,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(11,6,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(12,6,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(13,7,9,25.00,'2026-11-03 18:53:18',4,2026,30.00,0.00),
(14,7,13,10.00,'2026-11-03 18:53:18',4,2026,30.00,0.00),
(15,8,9,25.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(16,8,13,10.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(17,9,9,25.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(18,9,13,10.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(19,10,9,25.00,'2026-11-03 18:53:18',6,2026,30.00,0.00),
(20,10,13,10.00,'2026-11-03 18:53:18',6,2026,30.00,0.00),
(21,11,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(22,11,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(23,12,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(24,12,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(25,13,9,25.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(26,13,13,10.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(27,14,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(28,14,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(29,15,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(30,15,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(31,16,9,25.00,'2026-11-03 18:53:18',4,2026,30.00,0.00),
(32,16,13,10.00,'2026-11-03 18:53:18',4,2026,30.00,0.00),
(33,17,9,25.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(34,17,13,10.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(35,18,9,25.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(36,18,13,10.00,'2026-11-03 18:53:18',5,2026,30.00,0.00),
(37,19,9,25.00,'2026-11-03 18:53:18',6,2026,30.00,0.00),
(38,19,13,10.00,'2026-11-03 18:53:18',6,2026,30.00,0.00),
(39,20,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(40,20,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(41,21,9,25.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(42,21,13,10.00,'2026-11-03 18:53:18',1,2026,30.00,0.00),
(43,22,9,25.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(44,22,13,10.00,'2026-11-03 18:53:18',2,2026,30.00,0.00),
(45,23,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(46,23,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(47,24,9,25.00,'2026-11-03 18:53:18',3,2026,30.00,0.00),
(48,24,13,10.00,'2026-11-03 18:53:18',3,2026,30.00,0.00);
/*!40000 ALTER TABLE `solde_conge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `structure`
--

DROP TABLE IF EXISTS `structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'DIRECTION',
  `description` longtext DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_6F0137EA53C59D72` (`responsable_id`),
  KEY `idx_structure_institution` (`institution_id`),
  KEY `idx_structure_parent` (`parent_id`),
  KEY `idx_structure_code` (`code`),
  KEY `idx_structure_statut` (`statut`),
  CONSTRAINT `FK_6F0137EA10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6F0137EA53C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `employe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6F0137EA727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `structure` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `structure`
--

LOCK TABLES `structure` WRITE;
/*!40000 ALTER TABLE `structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_absence`
--

DROP TABLE IF EXISTS `type_absence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_absence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `description` longtext DEFAULT NULL,
  `unite` varchar(20) NOT NULL DEFAULT 'JOURS',
  `duree_max` decimal(6,2) DEFAULT NULL,
  `quota_annuel_max` decimal(6,2) DEFAULT NULL,
  `justificatif_requis` tinyint(1) NOT NULL DEFAULT 0,
  `remuneree` tinyint(1) NOT NULL DEFAULT 1,
  `couleur` varchar(7) DEFAULT '#17a2b8',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_type_absence_code` (`code`),
  KEY `idx_type_absence_institution` (`institution_id`),
  CONSTRAINT `FK_5E7B8F3C10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_absence`
--

LOCK TABLES `type_absence` WRITE;
/*!40000 ALTER TABLE `type_absence` DISABLE KEYS */;
INSERT INTO `type_absence` VALUES
(8,NULL,'CONCOURS_EXAMEN','Concours ou examen professionnel','Participation à des épreuves de concours ou examens professionnels officiels','JOURS',5.00,10.00,1,1,'#4e73df',1),
(9,NULL,'CONSULTATION_MEDICALE','Consultation médicale ponctuelle','Consultation ou examen médical spécialisé ne justifiant pas d\'arrêt de travail','HEURES',4.00,20.00,1,1,'#1cc88a',1),
(10,NULL,'EVENEMENT_FAMILIAL','Événement familial ponctuel','Absence exceptionnelle pour événement familial (mariage, naissance, décès)','JOURS',3.00,15.00,1,1,'#36b9cc',1),
(11,NULL,'CONVENANCE_PERSONNELLE','Convenance personnelle','Autorisation d\'absence pour convenance personnelle (non rémunérée)','JOURS',3.00,10.00,0,0,'#f6c23e',1),
(12,NULL,'FORMATION_COURTE','Formation ou séminaire de courte durée','Participation à une formation continue ponctuelle ou séminaire professionnel','JOURS',5.00,15.00,1,1,'#6f42c1',1),
(13,NULL,'ACTIVITE_SYNDICALE','Activité ou représentation syndicale','Exercice d\'un mandat ou participation à des réunions syndicales statutaires','JOURS',2.00,10.00,1,1,'#e74a3b',1),
(14,NULL,'AUTRE','Autre autorisation d\'absence','Autre motif exceptionnel soumis à appréciation de l\'autorité hiérarchique','JOURS',3.00,10.00,0,1,'#858796',1);
/*!40000 ALTER TABLE `type_absence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_acte`
--

DROP TABLE IF EXISTS `type_acte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_acte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_type_acte_code` (`code`),
  KEY `idx_type_acte_institution` (`institution_id`),
  CONSTRAINT `FK_49CBD62E10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_acte`
--

LOCK TABLES `type_acte` WRITE;
/*!40000 ALTER TABLE `type_acte` DISABLE KEYS */;
INSERT INTO `type_acte` VALUES
(11,NULL,'DECRET','Décret','Décret présidentiel ou ministériel',1),
(12,NULL,'ARRETE','Arrêté','Arrêté ministériel, préfectoral ou rectoral',1),
(13,NULL,'DECISION','Décision','Décision administrative ou directoriale',1),
(14,NULL,'NOTE_SERVICE','Note de service','Note de service ou circulaire interne',1),
(15,NULL,'ORDRE_MISSION','Ordre de mission','Ordre de mission officiel',1),
(16,NULL,'TITRE_CONGE','Titre de congé','Titre ou attestation d\'octroi de congé',1),
(17,NULL,'AUTORISATION_ABSENCE','Autorisation d\'absence','Autorisation formelle d\'absence',1),
(18,NULL,'PV_PRISE_SERVICE','Procès-verbal de prise de service','PV actant l\'installation ou la prise de fonction',1),
(19,NULL,'PV_CESSATION_SERVICE','Procès-verbal de cessation de service','PV de départ, radiation ou fin de fonction',1),
(20,NULL,'AUTRE','Autre acte administratif','Autre document ou acte à portée juridique',1);
/*!40000 ALTER TABLE `type_acte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_conges`
--

DROP TABLE IF EXISTS `type_conges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_conges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_deductible` tinyint(1) NOT NULL,
  `couleur` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `jours_par_an` int(11) NOT NULL DEFAULT 30,
  `justificatif_requis` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type_conges_code` (`code`),
  KEY `idx_type_conges_institution` (`institution_id`),
  CONSTRAINT `FK_40F0B50210405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_conges`
--

LOCK TABLES `type_conges` WRITE;
/*!40000 ALTER TABLE `type_conges` DISABLE KEYS */;
INSERT INTO `type_conges` VALUES
(9,'Congés payés','Congés annuels payés',1,'#28a745',1,NULL,'ANNUEL',30,0),
(10,'Congé maladie','Congé pour raisons médicales',0,'#dc3545',1,NULL,'ANNUEL',30,0),
(11,'Congé maternité','Congé de maternité',0,'#fd7e14',1,NULL,'ANNUEL',30,0),
(12,'Congé paternité','Congé de paternité',0,'#20c997',1,NULL,'ANNUEL',30,0),
(13,'Congé sans solde','Congé sans rémunération',1,'#6c757d',1,NULL,'ANNUEL',30,0),
(14,'Formation','Congé de formation professionnelle',0,'#007bff',1,NULL,'ANNUEL',30,0),
(15,'Mission','Mission professionnelle',0,'#17a2b8',1,NULL,'ANNUEL',30,0);
/*!40000 ALTER TABLE `type_conges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_institution`
--

DROP TABLE IF EXISTS `type_institution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_institution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_institution`
--

LOCK TABLES `type_institution` WRITE;
/*!40000 ALTER TABLE `type_institution` DISABLE KEYS */;
/*!40000 ALTER TABLE `type_institution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_id` int(11) DEFAULT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `totp_secret` varchar(255) DEFAULT NULL,
  `is_totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT '(DC2Type:json)' CHECK (json_valid(`backup_codes`)),
  `password_changed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_login_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  KEY `idx_user_institution` (`institution_id`),
  CONSTRAINT `FK_8D93D64910405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES
(1,1,'dominiquedamba@fake.io','[\"ROLE_SUPER_ADMIN\",\"ROLE_ADMIN\",\"ROLE_USER\"]','$2y$13$nZqepbh4n6iBBNAV8CL3UOhM0yhe2590J2g5xcJIc3E2Q3uU.nZ9y',NULL,'Dominique','Damba',1,'uuid-6aea2d96c8fd1','2026-11-03 18:53:10',NULL,0,'[]',NULL,NULL),
(2,1,'jean.oumar1@fake.io','[\"ROLE_USER\"]','$2y$13$hn.E2d0TRasAFPPqjahFee7pX73NmlWlDBGa2H4snOoDlMI9JI8d6',NULL,'Jean','Oumar',0,'uuid-6aea2d9749b6c','2026-11-03 18:53:11',NULL,0,'[]',NULL,NULL),
(3,2,'marie.hissein2@fake.io','[\"ROLE_USER\"]','$2y$13$QdpdlUdq4CtqSs/bsATT.OuUtF7lKrTsB5eT.Bj0u8a1WQ25/P8bW',NULL,'Marie','Hissein',1,'uuid-6aea2d97b698a','2026-11-03 18:53:11',NULL,0,'[]',NULL,NULL),
(4,3,'paul.mahamat3@fake.io','[\"ROLE_USER\"]','$2y$13$98tOfalkzD3OaK/KaMO2v.Vdg/95HYwcLj8A58dj/aMjCqD64HnTK',NULL,'Paul','Mahamat',1,'uuid-6aea2d982f681','2026-11-03 18:53:12',NULL,0,'[]',NULL,NULL),
(5,4,'claire.abakar4@fake.io','[\"ROLE_USER\"]','$2y$13$gPOGsVwiQyTCLgJ1EROV9.5afSqZpYxH9i12E9inI5ukpFl.F7Vkq',NULL,'Claire','Abakar',1,'uuid-6aea2d989ca5c','2026-11-03 18:53:12',NULL,0,'[]',NULL,NULL),
(6,5,'ahmed.saleh5@fake.io','[\"ROLE_USER\"]','$2y$13$7sV9bisWVUR3RhW6JiqYPe1sF6C0MhbGyawsSSewpCUwn8gh8CerC',NULL,'Ahmed','Saleh',1,'uuid-6aea2d9917e5f','2026-11-03 18:53:13',NULL,0,'[]',NULL,NULL),
(7,6,'fatima.hassan6@fake.io','[\"ROLE_USER\"]','$2y$13$iQtQmPTQehHo0ETKmHVIK.16dB/jcS1jHRFihT7l6Qn7dczbiT.ji',NULL,'Fatima','Hassan',0,'uuid-6aea2d9986be6','2026-11-03 18:53:13',NULL,0,'[]',NULL,NULL),
(8,1,'pierre.ali7@fake.io','[\"ROLE_USER\"]','$2y$13$NlwB1eKIe/UZrt82ADeSj.2XK5DZveuB0Hw06XcNJK2/XC.P4ev8C',NULL,'Pierre','Ali',1,'uuid-6aea2d9a0175d','2026-11-03 18:53:14',NULL,0,'[]',NULL,NULL),
(9,2,'amina.abdel8@fake.io','[\"ROLE_USER\"]','$2y$13$FJcLzPf9isn//M/G8RW5ZeKMQcQfDfkLkdAK9tSZqXcrfRFxcC9/W',NULL,'Amina','Abdel',1,'uuid-6aea2d9a6eedf','2026-11-03 18:53:14',NULL,0,'[]',NULL,NULL),
(10,3,'david.youssef9@fake.io','[\"ROLE_USER\"]','$2y$13$r43FVvdzPamemxhuoSGTIudRBdcwf.BEI8SVYXypUgCS1xI63PDFK',NULL,'David','Youssef',1,'uuid-6aea2d9adc002','2026-11-03 18:53:14',NULL,0,'[]',NULL,NULL),
(11,4,'sarah.ibrahim10@fake.io','[\"ROLE_USER\"]','$2y$13$WrsNJQm.IokSR6Ya8PcjW.uPpjfS5y5G26dR5AiPw9DMm1nyhwQ5C',NULL,'Sarah','Ibrahim',1,'uuid-6aea2d9b54beb','2026-11-03 18:53:15',NULL,0,'[]',NULL,NULL),
(12,5,'michel.adam11@fake.io','[\"ROLE_USER\"]','$2y$13$vGY2GPChGBci5QWuioZ9Luoyn1/w3h.VXVrG7X1h03o/3X0T/pJc2',NULL,'Michel','Adam',0,'uuid-6aea2d9bc6127','2026-11-03 18:53:15',NULL,0,'[]',NULL,NULL),
(13,6,'aïcha.moussa12@fake.io','[\"ROLE_USER\"]','$2y$13$Yow2p2KY1iPcOOfM6RBA0.gBRlQaETM9kfpGAVVjBX4B4YYTA2DHO',NULL,'Aïcha','Moussa',1,'uuid-6aea2d9c439e1','2026-11-03 18:53:16',NULL,0,'[]',NULL,NULL),
(14,1,'françois.idriss13@fake.io','[\"ROLE_USER\"]','$2y$13$BEwnM3RPe2Qy5yKwie4P2esmqVCWNRzNke.nUQKkTkgXI9BbU9wF.',NULL,'François','Idriss',1,'uuid-6aea2d9cb3e67','2026-11-03 18:53:16',NULL,0,'[]',NULL,NULL),
(15,2,'khadija.haroun14@fake.io','[\"ROLE_USER\"]','$2y$13$RArClwd9lCa95/2w3GQq.uDy/BbZNkWPYG656.zq2O5zS4Q6bRd16',NULL,'Khadija','Haroun',1,'uuid-6aea2d9d2d7da','2026-11-03 18:53:17',NULL,0,'[]',NULL,NULL),
(16,3,'nicolas.zakaria15@fake.io','[\"ROLE_USER\"]','$2y$13$py52T6VWPlapJy/qczHOQegmnlkhtyLLcx1xX4MX3Qb168AtY1CXK',NULL,'Nicolas','Zakaria',1,'uuid-6aea2d9d9a893','2026-11-03 18:53:17',NULL,0,'[]',NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visite`
--

DROP TABLE IF EXISTS `visite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employe_id` int(11) NOT NULL,
  `visiteur` varchar(255) NOT NULL,
  `arrivee` datetime NOT NULL,
  `depart` datetime DEFAULT NULL,
  `objet` varchar(255) DEFAULT NULL,
  `statut` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `commentaires` longtext DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `badge` varchar(255) DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B09C8CBB1B65292` (`employe_id`),
  KEY `idx_visite_institution` (`institution_id`),
  CONSTRAINT `FK_B09C8CBB10405986` FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B09C8CBB1B65292` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visite`
--

LOCK TABLES `visite` WRITE;
/*!40000 ALTER TABLE `visite` DISABLE KEYS */;
/*!40000 ALTER TABLE `visite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'primatech_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-03 18:55:24
