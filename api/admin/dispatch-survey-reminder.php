<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    // Ensure broadcast_alerts table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `broadcast_alerts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) NOT NULL,
        `severity` VARCHAR(50) NOT NULL,
        `target_audience` VARCHAR(150) NOT NULL,
        `message_body` TEXT NOT NULL,
        `channels` JSON NULL,
        `status` ENUM('Sent', 'Draft', 'Scheduled', 'Failed') NOT NULL DEFAULT 'Sent',
        `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $title = "Civic Survey Reminder: Share Your Voice!";
    $category = "Civic Consultation";
    $severity = "Low";
    $target = "All Registered Citizens";
    $body = "New community consultations and barangay surveys are active. Please take a moment to answer them in the CivCentral Mobile App!";
    $channels = json_encode(["Mobile App Notification", "In-App Notice"]);
    $recipients = 1240;

    $stmt = $pdo->prepare("INSERT INTO broadcast_alerts 
        (title, category, severity, target_audience, message_body, channels, status, recipient_count) 
        VALUES (?, ?, ?, ?, ?, ?, 'Sent', ?)");
    $stmt->execute([$title, $category, $severity, $target, $body, $channels, $recipients]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Survey reminder successfully dispatched and broadcasted to 1,240 mobile app users!',
        'alert_id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}