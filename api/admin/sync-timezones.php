<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();

    // 1. Ensure table has all columns
    $verifColumns = [
        'citizen_id_number' => "VARCHAR(30) NULL",
        'photo_1x1_url' => "VARCHAR(500) NULL",
        'signature_photo_url' => "VARCHAR(500) NULL",
        'qr_code_token' => "VARCHAR(255) NULL",
        'qr_code_image_url' => "VARCHAR(500) NULL",
        'admin_action_notes' => "TEXT NULL"
    ];
    foreach ($verifColumns as $col => $colDef) {
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM `citizen_verifications` LIKE '$col'")->fetch();
            if (!$checkCol) {
                $pdo->exec("ALTER TABLE `citizen_verifications` ADD COLUMN `$col` $colDef");
            }
        } catch (Exception $e) {}
    }

    // 2. Sync UTC timestamps to Philippine Standard Time (+8h) if needed
    // Records submitted before 2026-10-10 with hour <= 18 were UTC
    $affectedVerifs = $pdo->exec("
        UPDATE `citizen_verifications` 
        SET `submitted_at` = DATE_ADD(`submitted_at`, INTERVAL 8 HOUR) 
        WHERE `submitted_at` < '2026-10-10 00:00:00' AND `submitted_at` >= '2026-10-01 00:00:00'
    ");

    $affectedConcerns = $pdo->exec("
        UPDATE `citizen_concerns` 
        SET `created_at` = DATE_ADD(`created_at`, INTERVAL 8 HOUR) 
        WHERE `created_at` < '2026-10-10 00:00:00' AND `created_at` >= '2026-10-01 00:00:00'
    ");

    $affectedFeedback = $pdo->exec("
        UPDATE `community_ratings` 
        SET `created_at` = DATE_ADD(`created_at`, INTERVAL 8 HOUR) 
        WHERE `created_at` < '2026-10-10 00:00:00' AND `created_at` >= '2026-10-01 00:00:00'
    ");

    // 3. Fetch latest verifications to verify timestamps
    $stmt = $pdo->query("SELECT verification_id, first_name, last_name, verification_status, submitted_at FROM `citizen_verifications` ORDER BY verification_id DESC LIMIT 10");
    $verifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Timezones synchronized to Philippine Standard Time (Asia/Manila, UTC+8).',
        'server_time_pst' => date('Y-m-d H:i:s'),
        'affected_verifications' => $affectedVerifs,
        'affected_concerns' => $affectedConcerns,
        'affected_feedback' => $affectedFeedback,
        'recent_verifications' => $verifs
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
