-- ==============================================================================
-- CIVentral Citizen Verification Subsystem Schema
-- Target Database: citizen_verification
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `citizen_verification`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `citizen_verification`;

-- ------------------------------------------------------------------------------
-- CITIZEN VERIFICATIONS TABLE
-- Dedicated table for Citizen Identity, Residency, ID Photos, and Approval
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `citizen_verifications` (
  `verification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `citizen_user_id` INT UNSIGNED NOT NULL,

  -- Personal Information
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) NULL DEFAULT NULL,
  `sex` ENUM('Male', 'Female') NOT NULL,
  `place_of_birth` VARCHAR(255) NOT NULL,
  `birth_date` DATE NOT NULL,
  `civil_status` VARCHAR(100) NOT NULL,
  `employment_status` VARCHAR(100) NOT NULL,
  `occupation` VARCHAR(150) NOT NULL,
  `educational_attainment` VARCHAR(100) NOT NULL,

  -- Residency Details
  `district` VARCHAR(50) NOT NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `street_address` VARCHAR(255) NOT NULL,
  `years_resident` INT UNSIGNED NOT NULL DEFAULT 1,

  -- ID Proof & Photos
  `valid_id_type` VARCHAR(100) NOT NULL,
  `valid_id_number` VARCHAR(100) NOT NULL,
  `id_front_photo_url` LONGTEXT NULL DEFAULT NULL,
  `selfie_photo_url` LONGTEXT NULL DEFAULT NULL,

  -- Review & Approval State
  `verification_status` ENUM('Pending', 'Under_Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `reviewed_by` VARCHAR(100) NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_verif_user_id` (`citizen_user_id`),
  INDEX `idx_verif_status` (`verification_status`),
  INDEX `idx_verif_barangay` (`barangay`),
  INDEX `idx_verif_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
