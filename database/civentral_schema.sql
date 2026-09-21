-- Civentral Master Database Schema
-- Databases: `citizen_verification` and `civentral_certificates`

CREATE DATABASE IF NOT EXISTS `citizen_verification` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `civentral_certificates` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- citizen_verification tables
-- --------------------------------------------------------
USE `citizen_verification`;

CREATE TABLE IF NOT EXISTS `citizens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) DEFAULT '',
    `last_name` VARCHAR(100) NOT NULL,
    `suffix` VARCHAR(20) DEFAULT '',
    `birthdate` DATE DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT 'Male',
    `civil_status` VARCHAR(50) DEFAULT 'Single',
    `contact_number` VARCHAR(50) DEFAULT '',
    `email` VARCHAR(150) DEFAULT '',
    `address` TEXT DEFAULT NULL,
    `barangay` VARCHAR(100) DEFAULT '',
    `id_type` VARCHAR(100) DEFAULT '',
    `id_number` VARCHAR(100) DEFAULT '',
    `id_photo_url` TEXT DEFAULT NULL,
    `selfie_photo_url` TEXT DEFAULT NULL,
    `verification_status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `rejection_reason` TEXT DEFAULT NULL,
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (`verification_status`),
    INDEX idx_email (`email`),
    INDEX idx_barangay (`barangay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `citizen_concerns` (
    `concern_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) UNIQUE NOT NULL,
    `citizen_user_id` INT UNSIGNED NULL,
    `citizen_name` VARCHAR(150) NOT NULL DEFAULT 'Anonymous Resident',
    `citizen_phone` VARCHAR(50) NULL,
    `citizen_email` VARCHAR(150) NULL,
    `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
    `category` VARCHAR(100) NOT NULL,
    `sub_category` VARCHAR(100) NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255) NOT NULL DEFAULT '',
    `barangay` VARCHAR(100) NOT NULL DEFAULT '',
    `district` VARCHAR(50) NULL DEFAULT 'District 1',
    `gps_coordinates` VARCHAR(100) NULL,
    `status` ENUM('New', 'Under Review', 'Routed', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'New',
    `priority` ENUM('Urgent', 'High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium',
    `assigned_department` VARCHAR(150) NULL,
    `ai_detected_category` VARCHAR(100) NULL,
    `ai_confidence_score` VARCHAR(100) NULL,
    `photo_evidence_url` MEDIUMTEXT NULL,
    `attachments` TEXT NULL,
    `resolution_notes` TEXT NULL,
    `resolved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (`status`),
    INDEX idx_category (`category`),
    INDEX idx_priority (`priority`),
    INDEX idx_barangay (`barangay`),
    INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- civentral_certificates tables
-- --------------------------------------------------------
USE `civentral_certificates`;

CREATE TABLE IF NOT EXISTS `certificate_requests` (
    `request_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reference_no` VARCHAR(50) NOT NULL UNIQUE,
    `citizen_user_id` INT UNSIGNED NULL,
    `citizen_name` VARCHAR(150) NOT NULL,
    `contact_number` VARCHAR(50) NULL,
    `email` VARCHAR(150) NULL,
    `street_address` VARCHAR(255) NOT NULL,
    `barangay` VARCHAR(100) NOT NULL,
    `district` VARCHAR(50) NULL DEFAULT 'District 1',
    `civil_status` VARCHAR(50) NULL DEFAULT 'Single',
    `resident_since` VARCHAR(50) NULL DEFAULT '2015',
    `certificate_type` VARCHAR(100) NOT NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `purpose_details` TEXT NULL,
    `additional_notes` TEXT NULL,
    `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('Pending', 'Paid', 'Waived') NOT NULL DEFAULT 'Pending',
    `or_number` VARCHAR(50) NULL,
    `uploaded_documents` TEXT NULL,
    `status` ENUM('Pending', 'Under Review', 'Approved', 'Ready for Release', 'Released', 'Rejected') NOT NULL DEFAULT 'Pending',
    `encoded_by` VARCHAR(100) NOT NULL DEFAULT 'Citizen Mobile App',
    `verification_notes` TEXT NULL,
    `rejection_reason` TEXT NULL,
    `approved_by` VARCHAR(100) NULL,
    `approved_at` DATETIME NULL,
    `released_by` VARCHAR(100) NULL,
    `released_at` DATETIME NULL,
    `reprint_count` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_cert_type` (`certificate_type`),
    INDEX `idx_barangay` (`barangay`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `issued_certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_control_no` VARCHAR(50) NOT NULL UNIQUE,
    `request_id` INT UNSIGNED NOT NULL,
    `reference_no` VARCHAR(50) NOT NULL,
    `citizen_id` VARCHAR(50) NULL,
    `citizen_name` VARCHAR(150) NOT NULL,
    `certificate_type` VARCHAR(100) NOT NULL,
    `purpose` VARCHAR(255) NULL,
    `or_number` VARCHAR(50) NULL,
    `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `released_by` VARCHAR(100) NOT NULL DEFAULT 'Admin Staff',
    `date_released` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reprint_count` INT NOT NULL DEFAULT 0,
    `security_seal_hash` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_control_no` (`certificate_control_no`),
    INDEX `idx_ref_no` (`reference_no`),
    INDEX `idx_released` (`date_released`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `certificate_payments` (
    `payment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `or_number` VARCHAR(50) NOT NULL,
    `reference_no` VARCHAR(50) NOT NULL,
    `citizen_name` VARCHAR(150) NOT NULL,
    `certificate_type` VARCHAR(100) NOT NULL,
    `amount_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('Pending', 'Paid', 'Waived') NOT NULL DEFAULT 'Paid',
    `cashier_name` VARCHAR(100) NOT NULL DEFAULT 'Barangay Treasury Desk',
    `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_or` (`or_number`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_pstatus` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fallback mirrors in citizen_verification
CREATE TABLE IF NOT EXISTS `citizen_verification`.`certificate_requests` LIKE `civentral_certificates`.`certificate_requests`;
CREATE TABLE IF NOT EXISTS `citizen_verification`.`issued_certificates` LIKE `civentral_certificates`.`issued_certificates`;
CREATE TABLE IF NOT EXISTS `citizen_verification`.`certificate_payments` LIKE `civentral_certificates`.`certificate_payments`;