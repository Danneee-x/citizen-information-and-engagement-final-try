<?php
/**
 * Endpoint: POST /api/admin/review-citizen.php
 * Approves, Returns for Correction, or Rejects a Citizen Verification submission.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$verificationId   = intval($data['verification_id'] ?? 0);
$action           = trim(strtolower($data['action'] ?? '')); // 'approve', 'reject', or 'return'
$reviewedBy       = trim($data['reviewed_by'] ?? 'Admin');
$rejectionReason  = trim($data['rejection_reason'] ?? '');
$adminActionNotes = trim($data['admin_action_notes'] ?? ($data['notes'] ?? ''));

$validActions = ['approve', 'approved', 'reject', 'rejected', 'return', 'returned', 'rework', 'returned_for_correction'];

if ($verificationId <= 0 || !in_array($action, $validActions)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Please provide valid verification_id and action ('approve', 'return', or 'reject')."
    ]);
    exit;
}

if (in_array($action, ['approve', 'approved'])) {
    $newStatus = 'Approved';
} elseif (in_array($action, ['return', 'returned', 'rework', 'returned_for_correction'])) {
    $newStatus = 'Returned_For_Correction';
} else {
    $newStatus = 'Rejected';
}

$pdo = getDbConnection();

try {
    // 1. Fetch verification entry
    $vStmt = $pdo->prepare("SELECT * FROM citizen_verifications WHERE verification_id = ? LIMIT 1");
    $vStmt->execute([$verificationId]);
    $verif = $vStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verif) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Verification record not found."]);
        exit;
    }

    $citizenUserId = (int)$verif['citizen_user_id'];
    $fullName = trim("{$verif['first_name']} {$verif['middle_name']} {$verif['last_name']} {$verif['suffix']}");

    $citizenIdNumber = $verif['citizen_id_number'];
    $qrCodeToken     = $verif['qr_code_token'];
    $qrCodeImageUrl  = $verif['qr_code_image_url'];

    $baseUrl = rtrim(getenv('APP_URL') ?: 'https://api-citizen.civentral.tech', '/');
    $formatFullImageUrl = function($url) use ($baseUrl) {
        if (empty($url) || !is_string($url)) return null;
        $url = trim($url);
        if (empty($url)) return null;

        if (preg_match('#/(?:assets/)?uploads/verifications/([^/?]+)#', $url, $m)) {
            return $baseUrl . '/uploads/verifications/' . $m[1];
        }

        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        $clean = ltrim($url, '/');
        if (strpos($clean, 'assets/uploads/') === 0) {
            $clean = substr($clean, strlen('assets/'));
        }
        return $baseUrl . '/' . $clean;
    };

    // 2. Action Specific Processing
    if ($newStatus === 'Approved') {
        // Generate persistent Citizen ID number if none exists
        if (empty($citizenIdNumber)) {
            $year = date('Y');
            $citizenIdNumber = 'CAL-' . $year . '-' . str_pad($verificationId, 6, '0', STR_PAD_LEFT);
        }

        // Generate scannable QR Token
        if (empty($qrCodeToken)) {
            $tokenPayload = "CIVENTRAL:ID={$citizenIdNumber}|UID={$citizenUserId}|NAME={$fullName}|DATE=" . date('Ymd');
            $qrCodeToken  = hash('sha256', $tokenPayload);
        }

        // Standard QR code URL (compatible with web and mobile barcode rendering)
        if (empty($qrCodeImageUrl)) {
            $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode("CIVENTRAL-CITIZEN-ID:{$citizenIdNumber}|TOKEN:{$qrCodeToken}");
        }

        $photo1x1Full   = $formatFullImageUrl($verif['photo_1x1_url'] ?? null);
        $signatureFull  = $formatFullImageUrl($verif['signature_photo_url'] ?? null);
        $idFrontFull    = $formatFullImageUrl($verif['id_front_photo_url'] ?? null);
        $selfieFull     = $formatFullImageUrl($verif['selfie_photo_url'] ?? null);

        $updStmt = $pdo->prepare("UPDATE citizen_verifications SET 
            verification_status = 'Approved',
            citizen_id_number = :citizen_id_number,
            qr_code_token = :qr_code_token,
            qr_code_image_url = :qr_code_image_url,
            photo_1x1_url = COALESCE(:photo_1x1_url, photo_1x1_url),
            signature_photo_url = COALESCE(:signature_photo_url, signature_photo_url),
            id_front_photo_url = COALESCE(:id_front_photo_url, id_front_photo_url),
            selfie_photo_url = COALESCE(:selfie_photo_url, selfie_photo_url),
            reviewed_by = :reviewed_by,
            reviewed_at = NOW(),
            admin_action_notes = :notes
            WHERE verification_id = :id");

        $updStmt->execute([
            ':citizen_id_number'   => $citizenIdNumber,
            ':qr_code_token'       => $qrCodeToken,
            ':qr_code_image_url'   => $qrCodeImageUrl,
            ':photo_1x1_url'       => $photo1x1Full,
            ':signature_photo_url' => $signatureFull,
            ':id_front_photo_url'  => $idFrontFull,
            ':selfie_photo_url'    => $selfieFull,
            ':reviewed_by'         => $reviewedBy,
            ':notes'               => !empty($adminActionNotes) ? $adminActionNotes : 'Application officially verified and approved.',
            ':id'                  => $verificationId
        ]);

        // Sync citizen_users status
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

        // Record in id_cards_issued if table exists
        try {
            $idCardStmt = $pdo->prepare("INSERT INTO id_cards_issued (
                card_control_no, application_id, reference_no, citizen_name, id_category,
                barangay, date_issued, date_expiry, qr_security_hash, issued_by, status
            ) VALUES (
                :card_no, :app_id, :ref_no, :name, 'citizen_id',
                :brgy, NOW(), DATE_ADD(NOW(), INTERVAL 5 YEAR), :qr_hash, :issued_by, 'Active'
            ) ON DUPLICATE KEY UPDATE 
                status = 'Active',
                qr_security_hash = VALUES(qr_security_hash),
                date_issued = NOW()");

            $idCardStmt->execute([
                ':card_no'   => $citizenIdNumber,
                ':app_id'    => $verificationId,
                ':ref_no'    => 'VER-' . str_pad($verificationId, 4, '0', STR_PAD_LEFT),
                ':name'      => $fullName,
                ':brgy'      => $verif['barangay'],
                ':qr_hash'   => $qrCodeToken,
                ':issued_by' => $reviewedBy
            ]);
        } catch (Exception $cardEx) {
            // Ignore if id_cards_issued table structure varies
        }

    } elseif ($newStatus === 'Returned_For_Correction') {
        $notes = !empty($adminActionNotes) ? $adminActionNotes : ($rejectionReason ?: 'Application returned for document correction.');

        $updStmt = $pdo->prepare("UPDATE citizen_verifications SET 
            verification_status = 'Returned_For_Correction',
            admin_action_notes = :notes,
            rejection_reason = :notes_rej,
            reviewed_by = :reviewed_by,
            reviewed_at = NOW()
            WHERE verification_id = :id");

        $updStmt->execute([
            ':notes'       => $notes,
            ':notes_rej'   => $notes,
            ':reviewed_by' => $reviewedBy,
            ':id'          => $verificationId
        ]);

    } else { // Rejected
        $reason = !empty($rejectionReason) ? $rejectionReason : ($adminActionNotes ?: 'Application rejected.');

        $updStmt = $pdo->prepare("UPDATE citizen_verifications SET 
            verification_status = 'Rejected',
            rejection_reason = :reason,
            admin_action_notes = :reason_notes,
            reviewed_by = :reviewed_by,
            reviewed_at = NOW()
            WHERE verification_id = :id");

        $updStmt->execute([
            ':reason'       => $reason,
            ':reason_notes' => $reason,
            ':reviewed_by'  => $reviewedBy,
            ':id'           => $verificationId
        ]);
    }

    http_response_code(200);
    echo json_encode([
        "status"              => "success",
        "message"             => "Citizen verification has been marked as {$newStatus}.",
        "verification_id"     => $verificationId,
        "verification_status" => $newStatus,
        "citizen_user_id"     => $citizenUserId,
        "citizen_id_number"   => $citizenIdNumber,
        "qr_code_token"       => $qrCodeToken,
        "qr_code_image_url"   => $qrCodeImageUrl,
        "photo_1x1_url"       => isset($photo1x1Full) ? $photo1x1Full : $formatFullImageUrl($verif['photo_1x1_url'] ?? null),
        "signature_photo_url" => isset($signatureFull) ? $signatureFull : $formatFullImageUrl($verif['signature_photo_url'] ?? null),
        "reviewed_at"         => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
