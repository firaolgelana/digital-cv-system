-- ========================================================
-- DigiCV Comprehensive Database Schema
-- Matches strictly to the robust frontend logic properties
-- ========================================================

CREATE DATABASE IF NOT EXISTS `digicv_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digicv_db`;

-- 1. USERS & AUTHENTICATION
-- Core user entity housing the authentication credentials mapped generically
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'supervisor', 'examiner', 'recruiter', 'admin') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. ACADEMIC DEPARTMENTS
-- Preconfigured reference table for structural mapping
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. STUDENT PROFILES
-- Maps exclusively into the base User object holding persistent identity data
CREATE TABLE IF NOT EXISTS `student_profiles` (
    `user_id` INT PRIMARY KEY,
    `student_id_number` VARCHAR(50) NOT NULL UNIQUE,
    `department_id` INT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `address` VARCHAR(150) NULL,
    `portfolio_link` VARCHAR(255) NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
);

-- 4. CV ENTITIES
-- The absolute top-level identity for distinct Resume iterations.
-- (e.g. Software Engineer CV, Data Science CV, General Academic)
CREATE TABLE IF NOT EXISTS `cvs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `internal_title` VARCHAR(100) NOT NULL COMMENT 'e.g. Swe CV, Target Job',
    `target_headline` VARCHAR(150) NULL,
    `summary` TEXT NULL,
    `status` ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `student_profiles`(`user_id`) ON DELETE CASCADE
);

-- 5. CV SOCIAL MEDIA & LINKS ARRAY
CREATE TABLE IF NOT EXISTS `cv_social_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `platform_name` VARCHAR(50) NOT NULL,
    `link_url` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 6. CV EDUCATION HISTORY ARRAY
CREATE TABLE IF NOT EXISTS `cv_education` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `institution` VARCHAR(150) NOT NULL,
    `degree` VARCHAR(100) NOT NULL,
    `start_date` VARCHAR(50) NOT NULL COMMENT 'Month YYYY or YYYY',
    `end_date` VARCHAR(50) NOT NULL COMMENT 'Month YYYY or Present',
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 7. CV EXPERIENCE ARRAY
CREATE TABLE IF NOT EXISTS `cv_experience` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `role` VARCHAR(100) NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `start_date` VARCHAR(50) NOT NULL,
    `end_date` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 8. CV PROJECTS ARRAY
CREATE TABLE IF NOT EXISTS `cv_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `proof_link` VARCHAR(255) NULL,
    `description` TEXT NULL,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 9. CV CORE SKILLS
-- Split into atomic entries for Examiner Search querying natively!
CREATE TABLE IF NOT EXISTS `cv_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `skill_name` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 10. CV CERTIFICATIONS ARRAY
CREATE TABLE IF NOT EXISTS `cv_certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `cert_name` VARCHAR(150) NOT NULL,
    `cert_link` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 11. SUPERVISOR REVIEWS
-- Historical log of review decisions blocking or enabling QR mapping
CREATE TABLE IF NOT EXISTS `cv_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL,
    `supervisor_id` INT NOT NULL,
    `action_taken` ENUM('approved', 'rejected', 'requested_changes') NOT NULL,
    `review_note` TEXT NULL,
    `reviewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 12. DYNAMIC QR CODE MAPPING
-- Cryptographically maps an approved CV to a public token
CREATE TABLE IF NOT EXISTS `qr_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cv_id` INT NOT NULL UNIQUE COMMENT 'One active token per CV at a time',
    `access_token` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique hash payload for qrious.js',
    `is_active` BOOLEAN DEFAULT TRUE,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
);

-- 13. RECRUITER SCAN METRICS & ANALYTICS
CREATE TABLE IF NOT EXISTS `qr_scans_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `qr_id` INT NOT NULL,
    `recruiter_user_id` INT NULL COMMENT 'Nullable if scanned by public unauthenticated recruiter',
    `scanned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`qr_id`) REFERENCES `qr_codes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recruiter_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
