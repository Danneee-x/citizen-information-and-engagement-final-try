<?php
/**
 * Endpoint: GET /api/citizen/verification-status.php
 * Checks the verification status of a citizen by citizen_user_id.
 * Query Params: ?citizen_user_id=1001 (or ?id=1001)
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

$userId = intval($_GET['citizen_user_id'] ?? ($_GET['id'] ?? 0));

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide a valid citizen_user_id."]);
    exit;
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare("SELECT verification_id, citizen_user_id, first_name, last_name, district, barangay, valid_id_type, verification_status, rejection_reason, submitted_at, reviewed_at 
        FROM citizen_verifications 
        WHERE citizen_user_id = ? 
        ORDER BY submitted_at DESC 
        LIMIT 1");
    $stmt->execute([$userId]);
    $record = $stmt->fetch();

    if ($record) {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "is_verified"         => ($record['verification_status'] === 'Approved'),
            "verification_status" => $record['verification_status'],
            "data"                => $record
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "is_verified"         => false,
            "verification_status" => "Not_Submitted",
            "message"             => "No verification record found for this citizen."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
