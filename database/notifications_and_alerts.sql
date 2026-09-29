-- ==============================================================================
-- CIVENTRAL CITIZEN ENGAGEMENT PLATFORM - NOTIFICATIONS & ALERTS SUBSYSTEM
-- Target Databases: `citizen_verification` & `civentral_certificates`
-- ==============================================================================

USE `citizen_verification`;

CREATE TABLE IF NOT EXISTS `broadcast_alerts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `alert_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique alert code, e.g. CCN-ALT-2026-0001',
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Broadcast' COMMENT 'Emergency Alert, Domain Update, Broadcast, General Announcement, Health Advisory, Curfew / Ordinance, Event',
  `priority` ENUM('Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
  `channels` VARCHAR(150) NOT NULL DEFAULT 'In-App, Push',
  `target_audience` VARCHAR(150) NOT NULL DEFAULT 'All Registered Citizens',
  `target_barangay` VARCHAR(150) NOT NULL DEFAULT 'All Barangays',
  `sender_name` VARCHAR(150) NOT NULL DEFAULT 'City Central Command & Public Info Bureau',
  `sender_role` VARCHAR(150) NOT NULL DEFAULT 'Public Information Officer',
  `status` ENUM('Delivered', 'Sent', 'Scheduled', 'Draft') NOT NULL DEFAULT 'Delivered',
  `recipients_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `delivered_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `pending_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `attachment_url` VARCHAR(500) NULL,
  `scheduled_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_category` (`category`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

USE `civentral_certificates`;

CREATE TABLE IF NOT EXISTS `broadcast_alerts` LIKE `citizen_verification`.`broadcast_alerts`;
