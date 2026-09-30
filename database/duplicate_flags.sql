-- ==============================================================================
-- DATABASE: citizen_verification
-- TABLE:    duplicate_flags
-- Purpose:  Persist detected duplicate citizen pairs and resolution outcomes
-- Run this via phpMyAdmin or MySQL CLI: mysql -u root citizen_verification < duplicate_flags.sql
-- ==============================================================================

USE `citizen_verification`;

CREATE TABLE IF NOT EXISTS `duplicate_flags` (
    `flag_id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `flag_code`         VARCHAR(30) NOT NULL UNIQUE,
    `verification_id_a` INT UNSIGNED NOT NULL,
    `verification_id_b` INT UNSIGNED NOT NULL,
    `matching_criteria` VARCHAR(150) NOT NULL,
    `match_confidence`  TINYINT UNSIGNED NOT NULL DEFAULT 95,
    `status`            ENUM('Pending','Merged','Dismissed') NOT NULL DEFAULT 'Pending',
    `resolution_notes`  TEXT NULL DEFAULT NULL,
    `master_record_id`  INT UNSIGNED NULL DEFAULT NULL,
    `resolved_by`       VARCHAR(150) NULL DEFAULT NULL,
    `resolved_at`       DATETIME NULL DEFAULT NULL,
    `detected_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_df_status`  (`status`),
    INDEX `idx_df_vid_a`   (`verification_id_a`),
    INDEX `idx_df_vid_b`   (`verification_id_b`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores auto-detected duplicate citizen record pairs and their resolution';
