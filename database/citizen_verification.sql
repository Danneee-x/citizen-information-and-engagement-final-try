-- ==============================================================================
-- DATABASE: citizen_verification
-- Target System: Civentral Citizen Portal / Verification Module
-- Generated for: Local MySQL / phpMyAdmin import
-- Source Component: src/features/identity/screens/VerifyCitizenScreen.tsx
-- ==============================================================================

-- 1. DATABASE CREATION
CREATE DATABASE IF NOT EXISTS `citizen_verification`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `citizen_verification`;

-- ------------------------------------------------------------------------------
-- 2. TABLE STRUCTURE: citizen_users
-- Core Citizen Account & Authentication Entity
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `citizen_verifications`;
DROP TABLE IF EXISTS `citizen_users`;

CREATE TABLE `citizen_users` (
  `citizen_user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT NULL,
  `has_no_middle_name` TINYINT(1) NOT NULL DEFAULT 0,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) NULL DEFAULT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `mobile_number` VARCHAR(30) NULL DEFAULT NULL,
  `password` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('Pending', 'Active', 'Inactive', 'Locked', 'Archived') NOT NULL DEFAULT 'Active',
  `registry_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `last_login` DATETIME NULL DEFAULT NULL,
  `biometric_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- 3. TABLE STRUCTURE: citizen_verifications
-- Dedicated Multi-Step Citizen Verification Form & Civil Registry Data
-- ------------------------------------------------------------------------------
CREATE TABLE `citizen_verifications` (
  `verification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `citizen_user_id` INT UNSIGNED NOT NULL,

  -- STEP 1: Personal Information
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) NULL DEFAULT NULL,
  `sex` ENUM('Male', 'Female') NOT NULL,
  `place_of_birth` VARCHAR(255) NOT NULL,
  `birth_date` DATE NOT NULL,
  `civil_status` ENUM(
    'Single',
    'Married',
    'Widowed',
    'Separated',
    'Divorced / Annulled',
    'Common-Law / Live-In'
  ) NOT NULL,
  `employment_status` VARCHAR(100) NOT NULL,
  `occupation` VARCHAR(150) NOT NULL,
  `educational_attainment` VARCHAR(100) NOT NULL,

  -- STEP 2: Residency & District Details
  `district` VARCHAR(50) NOT NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `street_address` VARCHAR(255) NOT NULL,
  `years_resident` INT UNSIGNED NOT NULL,

  -- STEP 3: Valid ID & Biometrics (Liveness Check)
  `valid_id_type` VARCHAR(100) NOT NULL,
  `valid_id_number` VARCHAR(100) NOT NULL,
  `id_front_photo_url` VARCHAR(500) NULL DEFAULT NULL,
  `selfie_photo_url` VARCHAR(500) NULL DEFAULT NULL,

  -- Administration & Review Workflow
  `verification_status` ENUM('Pending', 'Under_Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `reviewed_by_employee_id` INT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_verif_user_id` (`citizen_user_id`),
  INDEX `idx_verif_status` (`verification_status`),
  INDEX `idx_verif_barangay` (`barangay`),
  INDEX `idx_verif_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_verif_user` FOREIGN KEY (`citizen_user_id`)
    REFERENCES `citizen_users` (`citizen_user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- 4. SEED DATA FOR TESTING
-- Pre-populated record matching citizen test profile
-- ------------------------------------------------------------------------------
INSERT INTO `citizen_users` (
  `citizen_user_id`,
  `first_name`,
  `middle_name`,
  `last_name`,
  `suffix`,
  `email`,
  `mobile_number`,
  `status`,
  `registry_completed`,
  `biometric_enabled`
) VALUES (
  1001,
  'Danny',
  'Toledano',
  'Espelita',
  'Jr.',
  'danny.espelita@civentral.ph',
  '09171234567',
  'Active',
  1,
  1
);

INSERT INTO `citizen_verifications` (
  `citizen_user_id`,
  `first_name`,
  `middle_name`,
  `last_name`,
  `suffix`,
  `sex`,
  `place_of_birth`,
  `birth_date`,
  `civil_status`,
  `employment_status`,
  `occupation`,
  `educational_attainment`,
  `district`,
  `barangay`,
  `street_address`,
  `years_resident`,
  `valid_id_type`,
  `valid_id_number`,
  `id_front_photo_url`,
  `selfie_photo_url`,
  `verification_status`
) VALUES (
  1001,
  'Danny',
  'Toledano',
  'Espelita',
  'Jr.',
  'Male',
  'Caloocan City',
  '1998-05-15',
  'Single',
  'Employed (Private Sector)',
  'Corporate / Office Employee',
  'College / Bachelor Degree Graduate',
  
  'District 1',
  'Barangay 171 (Bagumbong)',
  'Block 12 Lot 5, Sampaguita St.',
  12,
  'Philippine Identification System (PhilSys) National ID',
  '1234-5678-9012-3456',
  '/uploads/id_cards/id_1001_sample.jpg',
  '/uploads/selfies/selfie_1001_sample.jpg',
  'Pending'
);

