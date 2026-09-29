-- ====================================================================
-- Civentral Public Consultation & Survey Schema Migration
-- Matches Mobile App Specifications (Rating, Likert, Multiple Choice, Multi-Selection, Yes/No, Short Answer, Long Commentary)
-- Supports both `citizen_verification` and `civentral_certificates`
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `citizen_verification` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `civentral_certificates` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `citizen_verification`;

-- 1. Public Surveys Table
CREATE TABLE IF NOT EXISTS `public_surveys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `survey_code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `short_description` TEXT NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `estimated_time` VARCHAR(50) NOT NULL DEFAULT '3 mins',
    `target_audience` VARCHAR(100) NOT NULL DEFAULT 'All Residents',
    `sub_target` VARCHAR(150) NULL,
    `open_date` DATE NOT NULL,
    `close_date` DATE NOT NULL,
    `privacy_setting` ENUM('Identified', 'Anonymous') NOT NULL DEFAULT 'Identified',
    `is_public_results` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('Draft', 'Open', 'Closing Soon', 'Completed', 'Closed') NOT NULL DEFAULT 'Open',
    `created_by` VARCHAR(150) NOT NULL DEFAULT 'City Administration',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_survey_status` (`status`),
    INDEX `idx_survey_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Survey Questions Table (supports all mobile app question types)
CREATE TABLE IF NOT EXISTS `survey_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `survey_id` INT NOT NULL,
    `question_order` INT NOT NULL DEFAULT 1,
    `title` TEXT NOT NULL,
    `question_type` ENUM(
        'multiple_choice',
        'multiple_selection',
        'yes_no',
        'rating_scale',
        'likert_scale',
        'short_answer',
        'long_answer'
    ) NOT NULL DEFAULT 'multiple_choice',
    `options_json` JSON NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`survey_id`) REFERENCES `public_surveys`(`id`) ON DELETE CASCADE,
    INDEX `idx_question_survey` (`survey_id`, `question_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Survey Responses Table
CREATE TABLE IF NOT EXISTS `survey_responses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `survey_id` INT NOT NULL,
    `citizen_id` INT NULL,
    `citizen_name` VARCHAR(150) NOT NULL DEFAULT 'Anonymous Citizen',
    `barangay` VARCHAR(100) NULL,
    `age_group` VARCHAR(50) NULL,
    `gender` VARCHAR(30) NULL,
    `answers_json` JSON NOT NULL,
    `overall_rating` DECIMAL(3,2) NULL,
    `commentary` TEXT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`survey_id`) REFERENCES `public_surveys`(`id`) ON DELETE CASCADE,
    INDEX `idx_response_survey` (`survey_id`),
    INDEX `idx_response_date` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Civic Consultations Table (matches mobile app Civic Consultations)
CREATE TABLE IF NOT EXISTS `civic_consultations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `background_info` TEXT NOT NULL,
    `objective` TEXT NOT NULL,
    `closing_date` DATE NOT NULL,
    `status` ENUM('Open', 'Closing Soon', 'Under Review', 'Closed / Outcome Published') NOT NULL DEFAULT 'Open',
    `created_by` VARCHAR(150) NOT NULL DEFAULT 'City Legal & Policy Board',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_consultation_status` (`status`),
    INDEX `idx_consultation_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Consultation Citizen Feedbacks (Stance: In Favor, Neutral, Against, Suggested Amendments)
CREATE TABLE IF NOT EXISTS `consultation_feedbacks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `citizen_id` INT NULL,
    `citizen_name` VARCHAR(150) NOT NULL DEFAULT 'Caloocan Resident',
    `barangay` VARCHAR(100) NULL,
    `stance` ENUM('In Favor', 'Neutral', 'Against', 'Suggested Amendments') NOT NULL DEFAULT 'In Favor',
    `commentary` TEXT NOT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `civic_consultations`(`id`) ON DELETE CASCADE,
    INDEX `idx_feedback_consultation` (`consultation_id`),
    INDEX `idx_feedback_stance` (`stance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Consultation Policy Outcomes Table (for closing consultations with published ordinances)
CREATE TABLE IF NOT EXISTS `consultation_outcomes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL UNIQUE,
    `key_findings` TEXT NOT NULL,
    `policy_outcome` TEXT NOT NULL,
    `ordinance_number` VARCHAR(100) NULL,
    `allocated_budget` VARCHAR(100) NULL,
    `transparency_score` VARCHAR(50) NOT NULL DEFAULT '100% Published & Verified',
    `pdf_filename` VARCHAR(255) NULL,
    `published_by` VARCHAR(150) NOT NULL DEFAULT 'City Council Secretariat',
    `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `civic_consultations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mirror tables in civentral_certificates
USE `civentral_certificates`;
CREATE TABLE IF NOT EXISTS `public_surveys` LIKE `citizen_verification`.`public_surveys`;
CREATE TABLE IF NOT EXISTS `survey_questions` LIKE `citizen_verification`.`survey_questions`;
CREATE TABLE IF NOT EXISTS `survey_responses` LIKE `citizen_verification`.`survey_responses`;
CREATE TABLE IF NOT EXISTS `civic_consultations` LIKE `citizen_verification`.`civic_consultations`;
CREATE TABLE IF NOT EXISTS `consultation_feedbacks` LIKE `citizen_verification`.`consultation_feedbacks`;
CREATE TABLE IF NOT EXISTS `consultation_outcomes` LIKE `citizen_verification`.`consultation_outcomes`;
