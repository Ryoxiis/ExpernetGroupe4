-- =============================================================================
-- Projet Expernet — Schéma Relationnel et Données de Démonstration
-- Fichier : database.sql
-- Base de données : MySQL 8.0+ / 8.4 LTS
-- Encodage : utf8mb4 / utf8mb4_unicode_ci
-- Moteur : InnoDB
-- =============================================================================
-- Ce script unifié contient :
--   1. Configuration et création de la base de données
--   2. Verrous applicatifs et historique des migrations
--   3. Tables du Catalogue Public & Campus (migration JSON -> MySQL)
--   4. Tables de l'Espace Pédagogique (remplacement de GALIA)
--   5. Données d'initialisation et comptes de démonstration
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `expernet`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `expernet`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. MIGRATIONS ET VERROUS SYSTÈME
-- =============================================================================

DROP TABLE IF EXISTS portal_chat_tickets;
DROP TABLE IF EXISTS portal_chat_meetings;
DROP TABLE IF EXISTS portal_chat_files;
DROP TABLE IF EXISTS portal_chat_messages;
DROP TABLE IF EXISTS portal_chat_participants;
DROP TABLE IF EXISTS portal_chat_conversations;
DROP TABLE IF EXISTS portal_chat_access;
DROP TABLE IF EXISTS portal_chat_policies;

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `version` VARCHAR(100) NOT NULL,
  `applied_at` DATETIME NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique d exécution des migrations SQL';

DROP TABLE IF EXISTS `portal_locks`;
CREATE TABLE `portal_locks` (
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Verrous d exclusion mutuelle pour les opérations critiques';

-- =============================================================================
-- 2. CATALOGUE PUBLIC & CAMPUS (MIGRATION DEPUIS LES JSON)
-- =============================================================================

-- Catégories de formations
DROP TABLE IF EXISTS `catalog_categories`;
CREATE TABLE `catalog_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thématiques du catalogue de formation';

-- Formations du catalogue
DROP TABLE IF EXISTS `catalog_courses`;
CREATE TABLE `catalog_courses` (
  `id` INT UNSIGNED PRIMARY KEY COMMENT 'Identifiant source du catalogue',
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `path` VARCHAR(255) NOT NULL,
  `category` VARCHAR(120) NOT NULL,
  `duration` VARCHAR(100) NULL,
  `price` VARCHAR(150) NULL,
  `audience` TEXT NULL,
  `next_session` VARCHAR(100) NULL,
  `image` VARCHAR(255) NULL,
  `summary` TEXT NULL,
  `content_json` LONGTEXT NULL COMMENT 'Blocs éditoriaux structurés en JSON',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_catalog_courses_category` (`category`),
  INDEX `idx_catalog_courses_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catalogue public des formations Expernet';

-- Association formations / catégories (support des catégories multiples)
DROP TABLE IF EXISTS `catalog_course_category`;
CREATE TABLE `catalog_course_category` (
  `course_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`course_id`, `category_id`),
  CONSTRAINT `fk_course_cat_course` FOREIGN KEY (`course_id`) REFERENCES `catalog_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_course_cat_category` FOREIGN KEY (`category_id`) REFERENCES `catalog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liaison multi-catégories pour les formations';

-- Sessions publiques de formation
DROP TABLE IF EXISTS `catalog_public_sessions`;
CREATE TABLE `catalog_public_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `location` VARCHAR(180) NOT NULL DEFAULT 'Lieu à confirmer',
  `category` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_public_sessions_date` (`start_date`),
  CONSTRAINT `fk_public_sessions_course` FOREIGN KEY (`course_id`) REFERENCES `catalog_courses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions planifiées du planning public';

-- Articles et actualités
DROP TABLE IF EXISTS `catalog_articles`;
CREATE TABLE `catalog_articles` (
  `id` INT UNSIGNED PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `published_at` DATE NOT NULL,
  `category` VARCHAR(120) NOT NULL,
  `image` VARCHAR(255) NULL,
  `excerpt` TEXT NULL,
  `body` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_articles_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Articles et actualités publiques';

-- Demandes de devis
DROP TABLE IF EXISTS `catalog_quote_requests`;
CREATE TABLE `catalog_quote_requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `company` VARCHAR(150) NULL,
  `format` VARCHAR(100) NOT NULL COMMENT 'Individuel, Intra-entreprise, Sur mesure',
  `location` VARCHAR(100) NOT NULL COMMENT 'Le Port, Sainte-Clotilde, Mayotte, etc.',
  `course_title` VARCHAR(255) NULL,
  `message` TEXT NULL,
  `status` ENUM('new', 'in_progress', 'quoted', 'rejected', 'archived') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_quote_requests_status` (`status`),
  INDEX `idx_quote_requests_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Demandes de devis soumises via le site';

-- =============================================================================
-- 3. ESPACE PÉDAGOGIQUE (PORTAIL PRIVÉ REMPLAÇANT GALIA)
-- =============================================================================

-- Utilisateurs (Étudiants, Formateurs, Administrateurs)
DROP TABLE IF EXISTS `portal_users`;
CREATE TABLE `portal_users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL UNIQUE,
  `role` ENUM('student', 'trainer', 'admin') NOT NULL,
  `password_hash` VARCHAR(255) NULL COMMENT 'Hash bcrypt du mot de passe permanent',
  `status` ENUM('invited', 'active', 'disabled') NOT NULL DEFAULT 'invited',
  `temporary_hash` VARCHAR(255) NULL COMMENT 'Hash du secret temporaire à usage unique',
  `temporary_expires` DATETIME NULL COMMENT 'Expiration du secret temporaire (24h)',
  `activation_version` INT NOT NULL DEFAULT 1 COMMENT 'Incrémenté pour révoquer immédiatement les sessions',
  `mfa_secret` VARCHAR(255) NULL COMMENT 'Secret TOTP chiffré par sodium_crypto_secretbox',
  `mfa_last_counter` BIGINT NULL COMMENT 'Dernier compteur TOTP consommé contre le rejeu',
  `created_at` DATETIME NOT NULL,
  INDEX `idx_portal_users_role` (`role`),
  INDEX `idx_portal_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comptes utilisateurs du portail pédagogique';

-- Tentatives de connexion (Anti force brute)
DROP TABLE IF EXISTS `portal_login_attempts`;
CREATE TABLE `portal_login_attempts` (
  `fingerprint` CHAR(64) PRIMARY KEY COMMENT 'Empreinte SHA-256 IP ou identifiant',
  `attempts` INT NOT NULL,
  `last_attempt` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compteur de tentatives de connexion échouées';

-- Promotions / Cohortes
DROP TABLE IF EXISTS `portal_cohorts`;
CREATE TABLE `portal_cohorts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `starts_on` DATE NOT NULL,
  `ends_on` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Promotions et classes d étudiants';

-- Affectations des utilisateurs aux promotions
DROP TABLE IF EXISTS `portal_memberships`;
CREATE TABLE `portal_memberships` (
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`cohort_id`, `user_id`),
  CONSTRAINT `fk_memberships_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_memberships_user` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inscriptions des étudiants et formateurs aux promotions';

-- Séances de cours
DROP TABLE IF EXISTS `portal_sessions`;
CREATE TABLE `portal_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `trainer_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `mode` ENUM('onsite', 'remote', 'company') NOT NULL DEFAULT 'onsite',
  `location` VARCHAR(180) NOT NULL,
  `meeting_url` VARCHAR(500) NULL,
  INDEX `idx_sessions_schedule` (`starts_at`, `ends_at`),
  CONSTRAINT `fk_sessions_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sessions_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Séances de cours au planning';

-- Indisponibilités formateurs
DROP TABLE IF EXISTS `portal_unavailability`;
CREATE TABLE `portal_unavailability` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `trainer_id` BIGINT UNSIGNED NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `reason` VARCHAR(250) NOT NULL,
  INDEX `idx_unavailability_dates` (`starts_at`, `ends_at`),
  CONSTRAINT `fk_unavailability_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Périodes d indisponibilité déclarées par les formateurs';

-- Fenêtres d'émargement numérique (ouvertes par le formateur)
DROP TABLE IF EXISTS `portal_windows`;
CREATE TABLE `portal_windows` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `opened_by` BIGINT UNSIGNED NOT NULL,
  `opens_at` DATETIME NOT NULL,
  `closes_at` DATETIME NOT NULL,
  CONSTRAINT `fk_windows_session` FOREIGN KEY (`session_id`) REFERENCES `portal_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_windows_opened_by` FOREIGN KEY (`opened_by`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fenêtres temporelles d émargement pour une séance (max 15 min)';

-- Émargements et présences des étudiants
DROP TABLE IF EXISTS `portal_attendance`;
CREATE TABLE `portal_attendance` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `window_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('declared', 'validated', 'late', 'absent', 'excused') NOT NULL,
  `declared_at` DATETIME NULL,
  `updated_at` DATETIME NOT NULL,
  UNIQUE KEY `uniq_window_student` (`window_id`, `student_id`),
  CONSTRAINT `fk_attendance_window` FOREIGN KEY (`window_id`) REFERENCES `portal_windows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relevé des présences horodatées par période d émargement';

-- Devoirs et travaux
DROP TABLE IF EXISTS `portal_assignments`;
CREATE TABLE `portal_assignments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `trainer_id` BIGINT UNSIGNED NOT NULL,
  `session_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `due_at` DATETIME NOT NULL,
  `max_grade` DECIMAL(6,2) NOT NULL DEFAULT 20.00,
  CONSTRAINT `fk_assignments_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_session` FOREIGN KEY (`session_id`) REFERENCES `portal_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Devoirs demandés aux promotions';

-- Rendus des devoirs par les étudiants
DROP TABLE IF EXISTS `portal_submissions`;
CREATE TABLE `portal_submissions` (
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL COMMENT 'Texte explicatif ou lien de rendu',
  `submitted_at` DATETIME NOT NULL,
  PRIMARY KEY (`assignment_id`, `student_id`),
  CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `portal_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rendus des devoirs par les étudiants';

-- Notes et évaluations des devoirs
DROP TABLE IF EXISTS `portal_grades`;
CREATE TABLE `portal_grades` (
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `value` DECIMAL(6,2) NOT NULL,
  `comment` TEXT NOT NULL,
  `published` TINYINT NOT NULL DEFAULT 0 COMMENT '0: brouillon formateur, 1: visible étudiant',
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`assignment_id`, `student_id`),
  CONSTRAINT `fk_grades_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `portal_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes attribuées avec statut brouillon ou publié';

-- Documents et ressources pédagogiques
DROP TABLE IF EXISTS `portal_resources`;
CREATE TABLE `portal_resources` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `session_id` BIGINT UNSIGNED NULL,
  `author_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `stored_name` VARCHAR(100) NOT NULL COMMENT 'Nom sur disque dans storage/pedagogy/',
  `original_name` VARCHAR(190) NOT NULL COMMENT 'Nom du fichier d origine téléversé',
  `mime` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL,
  CONSTRAINT `fk_resources_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resources_session` FOREIGN KEY (`session_id`) REFERENCES `portal_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resources_author` FOREIGN KEY (`author_id`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ressources documentaires privées';

-- Messages échangés au sein de la promotion
DROP TABLE IF EXISTS `portal_messages`;
CREATE TABLE `portal_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `author_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  INDEX `idx_messages_created` (`cohort_id`, `created_at`),
  CONSTRAINT `fk_messages_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_author` FOREIGN KEY (`author_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fil d échanges et annonces par promotion';

-- Examens et jurys
DROP TABLE IF EXISTS `portal_exams`;
CREATE TABLE `portal_exams` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `location` VARCHAR(180) NOT NULL,
  CONSTRAINT `fk_exams_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Épreuves officielles et sessions d examen';

-- Membres du jury pour un examen
DROP TABLE IF EXISTS `portal_jury`;
CREATE TABLE `portal_jury` (
  `exam_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`exam_id`, `user_id`),
  CONSTRAINT `fk_jury_exam` FOREIGN KEY (`exam_id`) REFERENCES `portal_exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jury_user` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectation des jurys aux examens';

-- Compétences évaluées lors d'un examen
DROP TABLE IF EXISTS `portal_competencies`;
CREATE TABLE `portal_competencies` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `exam_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(250) NOT NULL,
  CONSTRAINT `fk_competencies_exam` FOREIGN KEY (`exam_id`) REFERENCES `portal_exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grille des compétences par examen';

-- Évaluations des compétences
DROP TABLE IF EXISTS `portal_evaluations`;
CREATE TABLE `portal_evaluations` (
  `competency_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `evaluator_id` BIGINT UNSIGNED NOT NULL,
  `level` ENUM('not_acquired', 'developing', 'acquired') NOT NULL,
  `comment` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`competency_id`, `student_id`),
  CONSTRAINT `fk_evaluations_comp` FOREIGN KEY (`competency_id`) REFERENCES `portal_competencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluations_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluations_evaluator` FOREIGN KEY (`evaluator_id`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Évaluations de compétences individuelles';

-- Journal d'activités / livret de suivi
DROP TABLE IF EXISTS `portal_journal`;
CREATE TABLE `portal_journal` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  CONSTRAINT `fk_journal_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_journal_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Livret de suivi et bilans des étudiants';

-- Enquêtes de satisfaction / avis étudiants
DROP TABLE IF EXISTS `portal_feedback`;
CREATE TABLE `portal_feedback` (
  `cohort_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `rating` INT NOT NULL COMMENT 'Note sur 5',
  `body` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`cohort_id`, `student_id`),
  CONSTRAINT `fk_feedback_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `portal_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_student` FOREIGN KEY (`student_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Évaluations de satisfaction par promotion';

-- Traçabilité et journal d'audit administratif
DROP TABLE IF EXISTS `portal_audit`;
CREATE TABLE `portal_audit` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `actor_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `detail` TEXT NOT NULL COMMENT 'JSON des valeurs avant/après ou métadonnées',
  `created_at` DATETIME NOT NULL,
  INDEX `idx_audit_actor` (`actor_id`),
  INDEX `idx_audit_action` (`action`),
  CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `portal_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal d audit immuable des actions sensibles';

-- =============================================================================
CREATE TABLE IF NOT EXISTS portal_chat_policies (
 cohort_id BIGINT UNSIGNED PRIMARY KEY, enabled TINYINT NOT NULL DEFAULT 1, direct_enabled TINYINT NOT NULL DEFAULT 1,
 files_enabled TINYINT NOT NULL DEFAULT 1, meetings_enabled TINYINT NOT NULL DEFAULT 1,
 FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_access (
 cohort_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, allowed TINYINT NOT NULL,
 PRIMARY KEY(cohort_id,user_id), FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id), FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_conversations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cohort_id BIGINT UNSIGNED NOT NULL,
 kind ENUM('cohort','direct','group') NOT NULL, direct_key VARCHAR(80) NOT NULL UNIQUE, title VARCHAR(120) NOT NULL,
 updated_at DATETIME NOT NULL, FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_participants (
 conversation_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, last_read_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(conversation_id,user_id), INDEX(user_id,conversation_id),
 FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id), FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL, sender_id BIGINT UNSIGNED NOT NULL,
 content TEXT NOT NULL, client_key VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE(sender_id,client_key), INDEX(conversation_id,id), FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id),
 FOREIGN KEY(sender_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, message_id BIGINT UNSIGNED NOT NULL UNIQUE, stored_name VARCHAR(100) NOT NULL,
 original_name VARCHAR(190) NOT NULL, mime VARCHAR(100) NOT NULL, size INT UNSIGNED NOT NULL,
 FOREIGN KEY(message_id) REFERENCES portal_chat_messages(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_meetings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL, creator_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(120) NOT NULL, starts_at DATETIME NOT NULL, room CHAR(48) NOT NULL UNIQUE,
 FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id), FOREIGN KEY(creator_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_tickets (
 token_hash CHAR(64) PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, activation_version INT NOT NULL, expires_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;

-- 4. JEUX DE DONNÉES DE DÉMONSTRATION (SEEDS)
-- =============================================================================

-- Enregistrement des migrations appliquées
INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
  ('001_pedagogy.sql', UTC_TIMESTAMP()),
  ('002_schedule_lock.sql', UTC_TIMESTAMP()),
  ('003_collaboration.sql', UTC_TIMESTAMP()),
  ('004_chat_groups.sql', UTC_TIMESTAMP());

-- Verrou de planification
INSERT INTO `portal_locks` (`name`) VALUES ('schedule')
  ON DUPLICATE KEY UPDATE `name` = `name`;

-- Catégories de formation catalogue
INSERT INTO `catalog_categories` (`id`, `name`, `slug`, `description`) VALUES
  (1, 'Bureautique', 'bureautique', 'Maîtrise des outils de bureautique et productivité (Excel, Word, Outlook, LibreOffice).'),
  (2, 'Développement', 'developpement', 'Conception logicielle, langages PHP, JavaScript, architectures web et frameworks.'),
  (3, 'Systèmes & Réseaux', 'systemes-et-reseaux', 'Administration des infrastructures systèmes, réseaux locaux, routage et virtualisation.'),
  (4, 'Cybersécurité', 'cybersecurite', 'Sécurisation des architectures, hygiène informatique, conformité et réponse aux incidents.'),
  (5, 'Google Workspace', 'google-workspace', 'Outils collaboratifs cloud : Sheets, Docs, Gmail, Drive et automatisation.'),
  (6, 'Management & Métiers', 'management', 'Gestion de projet, relation client, méthodologies agiles et encadrement d équipe.');

-- Exemples de formations phares du catalogue
INSERT INTO `catalog_courses` (`id`, `title`, `slug`, `path`, `category`, `duration`, `price`, `audience`, `next_session`, `image`, `summary`) VALUES
  (5428, 'Google Sheets Perfectionnement', 'formation-google-sheets-perfectionnement', 'formation/formation-google-sheets-perfectionnement/', 'Google Workspace', '2 jours', 'Inter : 550€ Intra : 1300€', 'Tout utilisateur souhaitant utiliser Google Sheets pour construire des tableaux et calculs avancés', 'À planifier', 'assets/images/content/bb6ba91a9fbf6b.png', 'Exploitez les fonctions avancées de calcul, de tableaux croisés et d automatisation de Google Sheets.'),
  (5401, 'Excel Initiation', 'excel-initiation', 'formation/excel-initiation/', 'Bureautique', '2 jours', 'Inter : 520€ Intra : 1200€', 'Débutants souhaitant concevoir et mettre en forme des tableaux de calcul sous Excel', '2026-11-04', 'assets/images/content/bb6ba91a9fbf6b.png', 'Acquérir les bases indispensables pour créer, manipuler et imprimer des feuilles de calcul simples.'),
  (5402, 'Excel Perfectionnement', 'excel-perfectionnement', 'formation/excel-perfectionnement/', 'Bureautique', '3 jours', 'Inter : 780€ Intra : 1800€', 'Pratiquants réguliers voulant maîtriser les formules logiques, de recherche et TCD', '2026-10-26', 'assets/images/content/bb6ba91a9fbf6b.png', 'Maîtriser les fonctions complexes (RECHERCHEX, INDEX/EQUIV), la validation de données et les tableaux croisés.'),
  (5430, 'Concevoir une application PHP', 'concevoir-une-application-php', 'formation/concevoir-une-application-php/', 'Développement', '5 jours', 'Inter : 1450€ Intra : 3500€', 'Développeurs souhaitant structurer des applications PHP modulaires, sécurisées et conformes aux bonnes pratiques', '2026-11-15', 'assets/images/content/42e887f4339e55.png', 'Architecture MVC, programmation orientée objet stricte, PDO sécurisé, tests automatisés et API REST.'),
  (5450, 'Développeur Web et Web Mobile (CDA)', 'developpeur-web-et-web-mobile', 'formation/developpeur-web-et-web-mobile/', 'Développement', '24 mois (Alternance)', 'Prise en charge OPCO / Entreprise', 'Candidats orientés développement informatique en alternance préparant un titre certifié', '2026-11-02', 'assets/images/content/42e887f4339e55.png', 'Formation diplômante complète couvrant le front-end moderne, le back-end PHP/Node, les bases SQL et la démarche DevOps.'),
  (5460, 'Sécurité des Réseaux Informatiques', 'securite-des-reseaux-informatiques', 'formation/securite-des-reseaux-informatiques/', 'Cybersécurité', '4 jours', 'Inter : 1600€ Intra : 3800€', 'Administrateurs réseau et techniciens support chargés du filtrage et de la protection des flux', '2026-11-20', 'assets/images/content/88b7d9db01b1b1.png', 'Mise en œuvre des pare-feu, segmentation VLAN, tunnels VPN sécurisés, supervision et gestion des alertes.');

-- Liaisons formations / catégories
INSERT INTO `catalog_course_category` (`course_id`, `category_id`) VALUES
  (5428, 5), (5401, 1), (5402, 1), (5430, 2), (5450, 2), (5460, 3), (5460, 4);

-- Sessions publiques
INSERT INTO `catalog_public_sessions` (`id`, `course_id`, `title`, `path`, `start_date`, `end_date`, `location`, `category`) VALUES
  (1, 5402, 'Excel Perfectionnement', 'formation/excel-perfectionnement/', '2026-10-26', '2026-10-28', 'Sainte-Clotilde', 'Bureautique'),
  (2, 5401, 'Excel Initiation', 'formation/excel-initiation/', '2026-11-04', '2026-11-05', 'Le Port', 'Bureautique'),
  (3, 5430, 'Concevoir une application PHP', 'formation/concevoir-une-application-php/', '2026-11-15', '2026-11-19', 'Sainte-Clotilde', 'Développement'),
  (4, 5460, 'Sécurité des Réseaux Informatiques', 'formation/securite-des-reseaux-informatiques/', '2026-11-20', '2026-11-23', 'Le Port', 'Cybersécurité');

-- Articles d'actualité
INSERT INTO `catalog_articles` (`id`, `title`, `slug`, `published_at`, `category`, `image`, `excerpt`, `body`) VALUES
  (1, 'L alternance chez Expernet : tremplin vers les métiers du numérique', 'alternance-expernet-tremplin-metiers-numerique', '2026-09-15', 'Campus', 'assets/images/content/9abf053229b1df.png', 'Découvrez comment le contrat d apprentissage et de professionnalisation permet de développer des compétences immédiatement opérationnelles à La Réunion.', '<p>L alternance représente l alliance parfaite entre théorie appliquée et pratique en entreprise...</p>'),
  (2, 'Cybersécurité en entreprise : les bonnes pratiques incontournables', 'cybersecurite-en-entreprise-bonnes-pratiques', '2026-10-01', 'Expertise', 'assets/images/content/88b7d9db01b1b1.png', 'Guide pratique pour sensibiliser vos collaborateurs et protéger votre système d information face aux nouvelles menaces.', '<p>Face à la recrudescence des cyberattaques à La Réunion, quelques mesures simples renforcent considérablement votre posture de sécurité...</p>');

-- Demandes de devis modèles
INSERT INTO `catalog_quote_requests` (`id`, `first_name`, `last_name`, `email`, `phone`, `company`, `format`, `location`, `course_title`, `message`, `status`) VALUES
  (1, 'Jean', 'Dupont', 'jean.dupont@entreprise-reunion.com', '0262 12 34 56', 'Société Bourbon IT', 'Former mon équipe', 'Sainte-Clotilde', 'Excel Perfectionnement', 'Besoin de former 5 collaborateurs sur les tableaux croisés dynamiques en novembre.', 'in_progress'),
  (2, 'Marie', 'Payet', 'marie.payet@innov-oi.re', '0692 98 76 54', 'Start-up Mascareignes', 'Un parcours sur mesure', 'Le Port', 'Concevoir une application PHP', 'Nous souhaitons moderniser une application interne vers PHP 8.3 et MVC.', 'new');

-- -----------------------------------------------------------------------------
-- Comptes de démonstration du portail pédagogique
-- Mot de passe par défaut pour tous les comptes actifs : Test-local-phrase-2026!
-- Hash bcrypt vérifié : $2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa
-- -----------------------------------------------------------------------------
INSERT INTO `portal_users` (`id`, `name`, `email`, `role`, `password_hash`, `status`, `temporary_hash`, `temporary_expires`, `activation_version`, `created_at`) VALUES
  (1, 'Camille Admin', 'admin.demo@expernet.re', 'admin', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', 'active', NULL, NULL, 1, UTC_TIMESTAMP()),
  (2, 'Alex Formateur', 'trainer.demo@expernet.re', 'trainer', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', 'active', NULL, NULL, 1, UTC_TIMESTAMP()),
  (3, 'Charlie Formateur', 'trainer2.demo@expernet.re', 'trainer', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', 'active', NULL, NULL, 1, UTC_TIMESTAMP()),
  (4, 'Sam Étudiant', 'student.demo@expernet.re', 'student', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', 'active', NULL, NULL, 1, UTC_TIMESTAMP()),
  (5, 'Lou Étudiante', 'student2.demo@expernet.re', 'student', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', 'active', NULL, NULL, 1, UTC_TIMESTAMP()),
  -- Compte invité pour tester le flux de première connexion avec mot de passe temporaire
  (6, 'Nouveau Invité', 'invite.demo@expernet.re', 'student', NULL, 'invited', '$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR), 1, UTC_TIMESTAMP());

-- Promotions
INSERT INTO `portal_cohorts` (`id`, `name`, `starts_on`, `ends_on`) VALUES
  (1, 'CDA · Promotion Horizon', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR)),
  (2, 'Réseaux · Promotion Azur', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR));

-- Affectations
INSERT INTO `portal_memberships` (`cohort_id`, `user_id`) VALUES
  (1, 2), -- Alex Formateur affecté à CDA Horizon
  (1, 4), -- Sam Étudiant affecté à CDA Horizon
  (1, 6), -- Nouveau Invité affecté à CDA Horizon
  (2, 3), -- Charlie Formateur affecté à Réseaux Azur
  (2, 5); -- Lou Étudiante affectée à Réseaux Azur

-- Séances au planning
INSERT INTO `portal_sessions` (`id`, `cohort_id`, `trainer_id`, `title`, `starts_at`, `ends_at`, `mode`, `location`, `meeting_url`) VALUES
  (1, 1, 2, 'Concevoir une application PHP', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR), 'remote', 'À distance · Teams Expernet', 'https://teams.microsoft.com/l/meetup-join/expernet-demo'),
  (2, 1, 2, 'Base de données et requêtes SQL', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY), INTERVAL 4 HOUR), 'onsite', 'Salle Océan (Sainte-Clotilde)', NULL),
  (3, 2, 3, 'Sécuriser un réseau d entreprise', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR), 'onsite', 'Salle Azur (Le Port)', NULL);

-- Période d'émargement ouverte pour la séance 1
INSERT INTO `portal_windows` (`id`, `session_id`, `opened_by`, `opens_at`, `closes_at`) VALUES
  (1, 1, 2, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE));

-- Émargement validé pour Sam Étudiant
INSERT INTO `portal_attendance` (`id`, `window_id`, `student_id`, `status`, `declared_at`, `updated_at`) VALUES
  (1, 1, 4, 'validated', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 MINUTE), UTC_TIMESTAMP());

-- Devoir demandé à la promotion CDA
INSERT INTO `portal_assignments` (`id`, `cohort_id`, `trainer_id`, `session_id`, `title`, `description`, `due_at`, `max_grade`) VALUES
  (1, 1, 2, 1, 'Architecture MVC et Schéma SQL', 'Modélisez la base de données relationnelle du portail pédagogique et documentez les endpoints de l API.', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 DAY), 20.00);

-- Rendu de Sam Étudiant
INSERT INTO `portal_submissions` (`assignment_id`, `student_id`, `body`, `submitted_at`) VALUES
  (1, 4, 'Voici le dépôt Git contenant le fichier database.sql et la documentation de l architecture : https://github.com/expernet/refonte-cda', UTC_TIMESTAMP());

-- Note attribuée et publiée
INSERT INTO `portal_grades` (`assignment_id`, `student_id`, `value`, `comment`, `published`, `updated_at`) VALUES
  (1, 4, 18.00, 'Excellent travail d architecture. Le schéma est parfaitement normalisé et les contraintes d intégrité sont rigoureuses.', 1, UTC_TIMESTAMP());

-- Message d'accueil sur le fil de la promotion
INSERT INTO `portal_messages` (`id`, `cohort_id`, `author_id`, `body`, `created_at`) VALUES
  (1, 1, 2, 'Bienvenue à tous dans votre nouvel espace pédagogique Expernet ! Vous y retrouverez votre planning, vos documents de cours et le suivi de vos présences.', UTC_TIMESTAMP());

-- Document modèle partagé
INSERT INTO `portal_resources` (`id`, `cohort_id`, `session_id`, `author_id`, `title`, `stored_name`, `original_name`, `mime`, `created_at`) VALUES
  (1, 1, 1, 2, 'Support de cours — Architecture PHP & SQL', 'guide-architecture.txt', 'guide-architecture.txt', 'text/plain', UTC_TIMESTAMP());

-- Examen officiel et jury
INSERT INTO `portal_exams` (`id`, `cohort_id`, `title`, `starts_at`, `location`) VALUES
  (1, 1, 'Épreuve Titre CDA — Session Finale 2026', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY), 'Centre d examen Expernet (Sainte-Clotilde)');

INSERT INTO `portal_jury` (`exam_id`, `user_id`) VALUES
  (1, 2), -- Alex Formateur
  (1, 3); -- Charlie Formateur

-- Compétences évaluées
INSERT INTO `portal_competencies` (`id`, `exam_id`, `label`) VALUES
  (1, 1, 'Développer une interface utilisateur web dynamique'),
  (2, 1, 'Créer une base de données relationnelle et développer des composants d accès aux données'),
  (3, 1, 'Développer la partie back-end d une application web sécurisée');

-- Évaluation continue
INSERT INTO `portal_evaluations` (`competency_id`, `student_id`, `evaluator_id`, `level`, `comment`, `updated_at`) VALUES
  (2, 4, 2, 'acquired', 'Schéma SQL propre, requêtes préparées PDO et gestion de la concurrence parfaitement maîtrisées.', UTC_TIMESTAMP());

-- Entrée de journal de bord
INSERT INTO `portal_journal` (`id`, `cohort_id`, `student_id`, `body`, `created_at`) VALUES
  (1, 1, 4, 'Finalisation de la modélisation de la base de données et tests des requêtes concurrentes d émargement.', UTC_TIMESTAMP());

-- Avis de satisfaction
INSERT INTO `portal_feedback` (`cohort_id`, `student_id`, `rating`, `body`, `updated_at`) VALUES
  (1, 4, 5, 'Interface claire, planning réactif et fonctionnalités d émargement très simples d utilisation.', UTC_TIMESTAMP());

-- Audit administratif
INSERT INTO `portal_audit` (`id`, `actor_id`, `action`, `entity_id`, `detail`, `created_at`) VALUES
  (1, 1, 'system.init', NULL, '{"event":"Initialisation de la base de données unifiée Expernet","version":"1.0"}', UTC_TIMESTAMP()),
  (2, 1, 'user.assigned', 4, '{"cohort_id":1,"role":"student","assigned_by":"Camille Admin"}', UTC_TIMESTAMP());


-- Comptes de test et conversations vides : les messages sont envoyés réellement par les utilisateurs.
-- BEGIN EXPERNET CHAT DEMO V1
INSERT INTO portal_cohorts(name,starts_on,ends_on) SELECT 'CDA · Promotion Horizon',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 YEAR) WHERE NOT EXISTS (SELECT 1 FROM portal_cohorts WHERE name='CDA · Promotion Horizon');
INSERT INTO portal_cohorts(name,starts_on,ends_on) SELECT 'Réseaux · Promotion Azur',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 YEAR) WHERE NOT EXISTS (SELECT 1 FROM portal_cohorts WHERE name='Réseaux · Promotion Azur');
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Lina Démo','chat01.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat01.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Noé Démo','chat02.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat02.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Maya Démo','chat03.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat03.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Eli Démo','chat04.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat04.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Inès Démo','chat05.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat05.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Sacha Démo','chat06.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat06.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Jade Démo','chat07.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat07.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat07.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Milo Démo','chat08.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat08.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat08.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Nora Démo','chat09.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat09.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Adam Démo','chat10.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat10.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Zoé Démo','chat11.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat11.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat11.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Léo Démo','chat12.demo@expernet.re','student','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat12.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat12.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Alice Formatrice Démo','chat13.demo@expernet.re','trainer','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat13.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Hugo Formateur Démo','chat14.demo@expernet.re','trainer','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat14.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat14.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Emma Formatrice Démo','chat15.demo@expernet.re','trainer','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat15.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_users(name,email,role,password_hash,status,activation_version,created_at) SELECT 'Robin Admin Démo','chat16.demo@expernet.re','admin','$2y$10$a/v1gDxksDbvBd.gYkKEHe2qfT7jqNETE/nZE75KEIPnx70qOpZNa','active',1,UTC_TIMESTAMP() WHERE NOT EXISTS (SELECT 1 FROM portal_users WHERE email='chat16.demo@expernet.re');
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),(SELECT id FROM portal_users WHERE email='chat16.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_memberships(cohort_id,user_id) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),(SELECT id FROM portal_users WHERE email='chat16.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'cohort',CONCAT('cohort:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon')),'CDA · Promotion Horizon',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),'cohort',CONCAT('cohort:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur')),'Réseaux · Promotion Azur',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'group','group:demo-chat-cda-projet','Projet PHP · Groupe démo',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-projet'),(SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-projet'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-projet'),(SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-projet'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-projet'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'group','group:demo-chat-cda-revision','Révisions SQL · Groupe démo',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-revision'),(SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-revision'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-revision'),(SELECT id FROM portal_users WHERE email='chat07.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-revision'),(SELECT id FROM portal_users WHERE email='chat08.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-cda-revision'),(SELECT id FROM portal_users WHERE email='chat14.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),'group','group:demo-chat-reseau','Atelier réseau · Groupe démo',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-reseau'),(SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-reseau'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-reseau'),(SELECT id FROM portal_users WHERE email='chat11.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-reseau'),(SELECT id FROM portal_users WHERE email='chat12.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key='group:demo-chat-reseau'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat02.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat01.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat13.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat03.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat04.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='CDA · Promotion Horizon'),':',LEAST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat05.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat06.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat10.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at) VALUES((SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),'direct',CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re'))),'Conversation privée',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE direct_key=direct_key;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
INSERT INTO portal_chat_participants(conversation_id,user_id) VALUES((SELECT id FROM portal_chat_conversations WHERE direct_key=CONCAT('direct:',(SELECT MIN(id) FROM portal_cohorts WHERE name='Réseaux · Promotion Azur'),':',LEAST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')),':',GREATEST((SELECT id FROM portal_users WHERE email='chat09.demo@expernet.re'),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')))),(SELECT id FROM portal_users WHERE email='chat15.demo@expernet.re')) ON DUPLICATE KEY UPDATE user_id=user_id;
-- END EXPERNET CHAT DEMO V1

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Fin du script database.sql
-- =============================================================================

