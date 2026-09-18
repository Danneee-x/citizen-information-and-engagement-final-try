<?php
/**
 * Endpoint: GET /api/external/check-verification.php
 * Inter-Subsystem API: Allows other CIVentral subsystems to verify if a citizen is legitimately verified.
 * Query Params: ?citizen_user_id=1001 OR ?valid_id_number=1234-5678-9012
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

$userId   = intval($_GET['citizen_user_id'] ?? 0);
$idNumber = trim($_GET['valid_id_number'] ?? '');

if ($userId <= 0 && empty($idNumber)) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Please provide citizen_user_id or valid_id_number to check verification status."
    ]);
    exit;
}

$pdo = getDbConnection();

try {
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT verification_id, citizen_user_id, first_name, middle_name, last_name, suffix, district, barangay, valid_id_type, valid_id_number, verification_status, reviewed_at 
            FROM citizen_verifications 
            WHERE citizen_user_id = ? AND verification_status = 'Approved' 
            ORDER BY submitted_at DESC 
            LIMIT 1");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT verification_id, citizen_user_id, first_name, middle_name, last_name, suffix, district, barangay, valid_id_type, valid_id_number, verification_status, reviewed_at 
            FROM citizen_verifications 
            WHERE valid_id_number = ? AND verification_status = 'Approved' 
            ORDER BY submitted_at DESC 
            LIMIT 1");
        $stmt->execute([$idNumber]);
    }

    $verifiedCitizen = $stmt->fetch();

    if ($verifiedCitizen) {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "is_verified"         => true,
            "verification_status" => "Approved",
            "citizen_details"     => [
                "citizen_user_id" => (int)$verifiedCitizen['citizen_user_id'],
                "full_name"       => trim("{$verifiedCitizen['first_name']} {$verifiedCitizen['middle_name']} {$verifiedCitizen['last_name']} {$verifiedCitizen['suffix']}"),
                "district"        => $verifiedCitizen['district'],
                "barangay"        => $verifiedCitizen['barangay'],
                "valid_id_type"   => $verifiedCitizen['valid_id_type'],
                "verified_date"   => $verifiedCitizen['reviewed_at']
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "is_verified"         => false,
            "verification_status" => "Unverified",
            "message"             => "Citizen is not verified or verification is still pending/rejected."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
