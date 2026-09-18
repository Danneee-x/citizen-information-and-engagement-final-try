<?php
/**
 * Endpoint: POST /api/admin/review-citizen.php
 * Approves or Rejects a Citizen Verification submission.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Also support form post
    $data = $_POST;
}

$verificationId = intval($data['verification_id'] ?? 0);
$action         = trim(strtolower($data['action'] ?? '')); // 'approve' or 'reject'
$adminName      = $_SESSION['admin_user']['name'] ?? $_SESSION['user_name'] ?? 'Danny Espelita';
$reviewedBy     = !empty($data['reviewed_by']) ? trim($data['reviewed_by']) : $adminName;
$rejectionReason = trim($data['rejection_reason'] ?? '');

if ($verificationId <= 0 || !in_array($action, ['approve', 'approved', 'reject', 'rejected'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide valid verification_id and action ('approve' or 'reject')."]);
    exit;
}

$newStatus = in_array($action, ['approve', 'approved']) ? 'Approved' : 'Rejected';

$pdo = getDbConnection();

try {
    // 1. Fetch verification entry
    $vStmt = $pdo->prepare("SELECT * FROM citizen_verifications WHERE verification_id = ? LIMIT 1");
    $vStmt->execute([$verificationId]);
    $verif = $vStmt->fetch();

    if (!$verif) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Verification record not found."]);
        exit;
    }

    $citizenUserId = $verif['citizen_user_id'];

    // 2. Update verification entry
    $updStmt = $pdo->prepare("UPDATE citizen_verifications SET 
        verification_status = :status,
        reviewed_by = :reviewed_by,
        rejection_reason = :reason,
        reviewed_at = NOW()
        WHERE verification_id = :id");
    
    $updStmt->execute([
        ':status'      => $newStatus,
        ':reviewed_by' => $reviewedBy,
        ':reason'      => $newStatus === 'Rejected' ? $rejectionReason : null,
        ':id'          => $verificationId
    ]);

    // 3. If approved, safely sync citizen_users if table exists
    if ($newStatus === 'Approved' && !empty($citizenUserId)) {
        try {
            $tableExists = $pdo->query("SHOW TABLES LIKE 'citizen_users'")->fetch();
            if ($tableExists) {
                $userUpd = $pdo->prepare("UPDATE citizen_users SET 
                    registry_completed = 1,
                    first_name = :fname,
                    last_name = :lname,
                    updated_at = NOW()
                    WHERE citizen_user_id = :uid");
                $userUpd->execute([
                    ':fname' => $verif['first_name'],
                    ':lname' => $verif['last_name'],
                    ':uid'   => $citizenUserId
                ]);
            }
        } catch (Exception $userEx) {
            error_log("Optional citizen_users update skipped: " . $userEx->getMessage());
        }
    }

    http_response_code(200);
    echo json_encode([
        "status"              => "success",
        "message"             => "Citizen verification has been {$newStatus}.",
        "verification_id"     => $verificationId,
        "verification_status" => $newStatus,
        "citizen_user_id"     => (int)$citizenUserId,
        "reviewed_by"         => $reviewedBy,
        "reviewed_at"         => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
