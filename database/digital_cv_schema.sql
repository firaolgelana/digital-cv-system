-- ========================================================
-- DigiCV Database Schema (Frontend + Current PHP Compatible)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `digicv_db`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `digicv_db`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- 1) AUTHENTICATION AND USER STRUCTURE
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(120) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL UNIQUE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- FIXED TRIGGERS
-- --------------------------------------------------------

DROP TRIGGER IF EXISTS `trg_departments_before_insert`;
DROP TRIGGER IF EXISTS `trg_departments_before_update`;

DELIMITER $$

CREATE TRIGGER `trg_departments_before_insert`
BEFORE INSERT ON `departments`
FOR EACH ROW
BEGIN
    SET NEW.`name` = TRIM(NEW.`name`);

    IF NEW.`name` = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Department name cannot be empty.';
    END IF;
END$$

CREATE TRIGGER `trg_departments_before_update`
BEFORE UPDATE ON `departments`
FOR EACH ROW
BEGIN
    SET NEW.`name` = TRIM(NEW.`name`);

    IF NEW.`name` = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Department name cannot be empty.';
    END IF;
END$$

DELIMITER ;

CREATE TABLE IF NOT EXISTS `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `department_id` INT UNSIGNED NULL,
    `student_number` VARCHAR(50) NOT NULL UNIQUE,
    `address` VARCHAR(180) NULL,
    `portfolio_link` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_students_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT `fk_students_department`
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX `idx_students_department` (`department_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2) CV CORE
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cvs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `internal_title` VARCHAR(120) NOT NULL,
    `full_name` VARCHAR(120) NULL,
    `contact_email` VARCHAR(150) NULL,
    `contact_phone` VARCHAR(30) NULL,
    `location_address` VARCHAR(180) NULL,
    `target_headline` VARCHAR(150) NULL,
    `summary` TEXT NULL,
    `portfolio_link` VARCHAR(255) NULL,
    `status` ENUM('draft','pending','approved','rejected','changes_requested') NOT NULL DEFAULT 'draft',
    `submitted_at` DATETIME NULL,
    `review_note` TEXT NULL,
    `reviewed_at` DATETIME NULL,
    `reviewer_id` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cvs_student`
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT `fk_cvs_reviewer`
        FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX `idx_cvs_student` (`student_id`),
    INDEX `idx_cvs_status` (`status`),
    INDEX `idx_cvs_updated_at` (`updated_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_social_links` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `platform_name` VARCHAR(60) NOT NULL,
    `link_url` VARCHAR(255) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_social_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_social_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_education` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `institution` VARCHAR(150) NOT NULL,
    `degree` VARCHAR(150) NOT NULL,
    `start_period` VARCHAR(50) NULL,
    `end_period` VARCHAR(50) NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_education_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_education_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_experience` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `role_company` VARCHAR(180) NOT NULL,
    `start_period` VARCHAR(50) NULL,
    `end_period` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_experience_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_experience_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `proof_link` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_projects_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_projects_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_skills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `skill_name` VARCHAR(120) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_skills_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    UNIQUE KEY `uq_cv_skill` (`cv_id`,`skill_name`),
    INDEX `idx_cv_skills_name` (`skill_name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_certifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `cert_name` VARCHAR(180) NOT NULL,
    `cert_link` VARCHAR(255) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_certs_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_certs_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `doc_type` ENUM('certificate','portfolio','other') NOT NULL DEFAULT 'other',
    `doc_title` VARCHAR(180) NOT NULL,
    `doc_url` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_documents_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_documents_cv` (`cv_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3) REVIEW / QR / TRACKING
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cv_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `supervisor_id` INT UNSIGNED NOT NULL,
    `action_taken` ENUM('approved','rejected','requested_changes') NOT NULL,
    `review_note` TEXT NULL,
    `reviewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_reviews_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT `fk_cv_reviews_supervisor`
        FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_reviews_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cv_evaluations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL,
    `examiner_id` INT UNSIGNED NOT NULL,
    `evaluation_note` TEXT NOT NULL,
    `score` DECIMAL(5,2) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_cv_eval_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT `fk_cv_eval_examiner`
        FOREIGN KEY (`examiner_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_cv_eval_cv` (`cv_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `qr_codes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT UNSIGNED NOT NULL UNIQUE,
    `access_token` VARCHAR(120) NOT NULL UNIQUE,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,

    CONSTRAINT `fk_qr_codes_cv`
        FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_qr_codes_active` (`is_active`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `qr_access_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `qr_id` INT UNSIGNED NOT NULL,
    `recruiter_user_id` INT UNSIGNED NULL,
    `visitor_ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `referrer` VARCHAR(255) NULL,
    `scanned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_qr_access_qr`
        FOREIGN KEY (`qr_id`) REFERENCES `qr_codes`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT `fk_qr_access_recruiter`
        FOREIGN KEY (`recruiter_user_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX `idx_qr_access_qr` (`qr_id`),
    INDEX `idx_qr_access_scanned_at` (`scanned_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('system','review','approval','rejection','info') NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX `idx_notifications_user` (`user_id`),
    INDEX `idx_notifications_read` (`is_read`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4) SEED DATA
-- --------------------------------------------------------

INSERT INTO `roles` (`name`, `description`) VALUES
('student', 'Graduate student user'),
('supervisor', 'Academic supervisor'),
('examiner', 'Examiner / validator'),
('recruiter', 'Recruiter / HR user'),
('admin', 'System administrator')
ON DUPLICATE KEY UPDATE
`description` = VALUES(`description`);