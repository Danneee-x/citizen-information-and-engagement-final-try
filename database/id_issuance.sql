-- ==============================================================================
-- CIVENTRAL CITIZEN ENGAGEMENT PLATFORM - ID ISSUANCE SUBSYSTEM DATABASE
-- Target Databases: `civentral_certificates` & `citizen_verification`
-- Compatible with: MySQL 5.7+, MySQL 8.0+, MariaDB 10.4+, and Dokploy Cloud MySQL
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. DATABASE INITIALIZATION
-- ------------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `civentral_certificates` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `citizen_verification` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `civentral_certificates`;

-- ------------------------------------------------------------------------------
-- 2. TABLE STRUCTURE: id_issuance_applications
-- Main applications filed from Citizen Mobile App & City Hall Walk-in Counters
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `id_issuance_applications`;

CREATE TABLE `id_issuance_applications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Application ref code (e.g. CAL-CIT-2026-4412)',
  `citizen_user_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK referencing citizen_users if authenticated',
  
  -- ID Classification matching Citizen App categories
  `id_category` ENUM('citizen_id', 'barangay_id', 'solo_parent_id', 'pwd_id', 'senior_citizen_id') NOT NULL DEFAULT 'citizen_id',
  `id_title` VARCHAR(150) NOT NULL DEFAULT 'Caloocan Citizen Unified ID Card',
  `application_type` ENUM('New Application', 'Renewal', 'Replacement') NOT NULL DEFAULT 'New Application',
  
  -- Applicant Demographics & Civil Registry Data
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL DEFAULT '',
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) NULL DEFAULT '',
  `gender` VARCHAR(20) NOT NULL DEFAULT 'Male',
  `birthdate` DATE NULL DEFAULT NULL,
  `civil_status` VARCHAR(50) NOT NULL DEFAULT 'Single',
  `contact_number` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NULL DEFAULT NULL,
  
  -- Residency & Geographic Address
  `street_address` VARCHAR(255) NOT NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `district` VARCHAR(50) NOT NULL DEFAULT 'District 1',
  `resident_since` VARCHAR(50) NOT NULL DEFAULT '2015',
  
  -- Administrative Bureau Routing
  `issuing_bureau` VARCHAR(200) NOT NULL DEFAULT 'Caloocan Civil Registry & Identity Management Bureau',
  `claim_office` VARCHAR(200) NOT NULL DEFAULT 'Caloocan Main City Hall - Window 6',
  `estimated_turnaround` VARCHAR(100) NOT NULL DEFAULT '3 to 5 Business Days',
  
  -- Uploaded Document Requirements & Photos (Data URLs or S3/Upload paths)
  `primary_doc_name` VARCHAR(150) NULL DEFAULT 'Valid Identification Document',
  `primary_doc_url` MEDIUMTEXT NULL DEFAULT NULL,
  `photo_2x2_url` MEDIUMTEXT NULL DEFAULT NULL,
  `support_doc_name` VARCHAR(150) NULL DEFAULT NULL,
  `support_doc_url` MEDIUMTEXT NULL DEFAULT NULL,
  
  -- Multi-Stage Verification & Production Lifecycle
  `status` ENUM(
    'Pending Review',
    'Under Review',
    'Approved',
    'Ready for Release',
    'Claimed',
    'Rejected'
  ) NOT NULL DEFAULT 'Pending Review',
  
  -- Administrative Review & Sign-Off
  `review_notes` TEXT NULL DEFAULT NULL,
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `reviewed_by` VARCHAR(100) NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `released_by` VARCHAR(100) NULL DEFAULT NULL,
  `released_at` DATETIME NULL DEFAULT NULL,
  
  -- System Timestamps
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_id_category` (`id_category`),
  INDEX `idx_app_status` (`status`),
  INDEX `idx_barangay` (`barangay`),
  INDEX `idx_ref_no` (`reference_no`),
  INDEX `idx_user_id` (`citizen_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TABLE STRUCTURE: id_cards_issued
-- Permanent Ledger of Printed & Released Physical/Digital ID Credentials
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `id_cards_issued`;

CREATE TABLE `id_cards_issued` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `card_control_no` VARCHAR(60) NOT NULL UNIQUE COMMENT 'Unique embossed ID serial number',
  `application_id` INT UNSIGNED NOT NULL COMMENT 'FK to id_issuance_applications',
  `reference_no` VARCHAR(50) NOT NULL,
  `citizen_name` VARCHAR(150) NOT NULL,
  `id_category` VARCHAR(50) NOT NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `date_issued` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_expiry` DATE NULL DEFAULT NULL,
  `qr_security_hash` VARCHAR(128) NOT NULL COMMENT 'Cryptographic hash for City Hall scanner verification',
  `issued_by` VARCHAR(100) NOT NULL DEFAULT 'ID Production Desk Officer',
  `status` ENUM('Active', 'Expired', 'Revoked', 'Lost') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_control_no` (`card_control_no`),
  INDEX `idx_card_status` (`status`),
  CONSTRAINT `fk_issued_application` 
    FOREIGN KEY (`application_id`) 
    REFERENCES `id_issuance_applications` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABLE STRUCTURE: id_issuance_audit_logs
-- Immutable History of Status Transitions & Review Events
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `id_issuance_audit_logs`;

CREATE TABLE `id_issuance_audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'e.g. SUBMITTED, REVIEW_STARTED, APPROVED, REJECTED, CLAIMED',
  `previous_status` VARCHAR(50) NULL DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `officer_name` VARCHAR(100) NOT NULL,
  `remarks` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_audit_app_id` (`application_id`),
  CONSTRAINT `fk_audit_application` 
    FOREIGN KEY (`application_id`) 
    REFERENCES `id_issuance_applications` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. INITIAL SEED DATA
-- Tables initialized in clean state for production.
-- ------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------
-- 6. DUAL-DATABASE SYNCHRONIZATION
-- Mirror tables into `citizen_verification` database for unified local & Dokploy access
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `citizen_verification`.`id_issuance_audit_logs`;
DROP TABLE IF EXISTS `citizen_verification`.`id_cards_issued`;
DROP TABLE IF EXISTS `citizen_verification`.`id_issuance_applications`;

CREATE TABLE `citizen_verification`.`id_issuance_applications` LIKE `civentral_certificates`.`id_issuance_applications`;
CREATE TABLE `citizen_verification`.`id_cards_issued` LIKE `civentral_certificates`.`id_cards_issued`;
CREATE TABLE `citizen_verification`.`id_issuance_audit_logs` LIKE `civentral_certificates`.`id_issuance_audit_logs`;


SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- END OF ID ISSUANCE SUBSYSTEM DATABASE SCHEMA
-- ==============================================================================

