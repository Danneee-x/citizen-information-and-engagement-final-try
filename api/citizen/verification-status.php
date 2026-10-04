<?php
/**
 * Endpoint: GET /api/citizen/verification-status.php
 * Checks the verification status of a citizen by citizen_user_id or email.
 * Query Params: ?citizen_user_id=1001 (or ?id=1001, or ?email=user@domain.com)
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

$userId = intval($_GET['citizen_user_id'] ?? ($_GET['id'] ?? 0));
$email  = trim($_GET['email'] ?? '');

$pdo = getDbConnection();

try {
    // If email is provided, resolve the real user ID from citizen_users
    if (!empty($email)) {
        $uStmt = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE email = ? LIMIT 1");
        $uStmt->execute([$email]);
        $foundUid = $uStmt->fetchColumn();
        if ($foundUid) {
            $userId = (int)$foundUid;
        } else {
            // User does not exist in database -> Return Not_Submitted with null data immediately
            http_response_code(200);
            echo json_encode([
                "status"              => "success",
                "data"                => null,
                "verification_status" => "Not_Submitted",
                "is_verified"         => false,
                "message"             => "No verification record found for this citizen."
            ]);
            exit;
        }
    }

    // If citizen_user_id is missing, empty, or <= 0, DO NOT fall back to 1001 or any default test ID
    if ($userId <= 0) {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "data"                => null,
            "verification_status" => "Not_Submitted",
            "is_verified"         => false,
            "message"             => "Please provide a valid citizen_user_id or email."
        ]);
        exit;
    }

    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT 
            verification_id, citizen_id_number, citizen_user_id,
            first_name, middle_name, last_name, suffix, sex,
            place_of_birth, birth_date, civil_status, employment_status,
            occupation, educational_attainment, district, barangay, street_address,
            years_resident, valid_id_type, valid_id_number,
            id_front_photo_url, selfie_photo_url, photo_1x1_url, signature_photo_url,
            qr_code_token, qr_code_image_url,
            verification_status, rejection_reason, admin_action_notes,
            reviewed_by, reviewed_at, submitted_at, updated_at
            FROM citizen_verifications 
            WHERE citizen_user_id = ? 
            ORDER BY verification_id DESC 
            LIMIT 1");
        $stmt->execute([$userId]);
    } else {
        // Fallback search by email in citizen_users
        $stmt = $pdo->prepare("SELECT 
            v.verification_id, v.citizen_id_number, v.citizen_user_id,
            v.first_name, v.middle_name, v.last_name, v.suffix, v.sex,
            v.place_of_birth, v.birth_date, v.civil_status, v.employment_status,
            v.occupation, v.educational_attainment, v.district, v.barangay, v.street_address,
            v.years_resident, v.valid_id_type, v.valid_id_number,
            v.id_front_photo_url, v.selfie_photo_url, v.photo_1x1_url, v.signature_photo_url,
            v.qr_code_token, v.qr_code_image_url,
            v.verification_status, v.rejection_reason, v.admin_action_notes,
            v.reviewed_by, v.reviewed_at, v.submitted_at, v.updated_at
            FROM citizen_verifications v
            INNER JOIN citizen_users u ON v.citizen_user_id = u.citizen_user_id
            WHERE u.email = ?
            ORDER BY v.verification_id DESC 
            LIMIT 1");
        $stmt->execute([$email]);
    }

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $baseUrl = rtrim(getenv('APP_URL') ?: 'https://api-citizen.civentral.tech', '/');
        $formatUrl = function($url) use ($baseUrl) {
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

        $record['photo_1x1_url']       = $formatUrl($record['photo_1x1_url'] ?? null);
        $record['signature_photo_url'] = $formatUrl($record['signature_photo_url'] ?? null);
        $record['id_front_photo_url']  = $formatUrl($record['id_front_photo_url'] ?? null);
        $record['selfie_photo_url']    = $formatUrl($record['selfie_photo_url'] ?? null);

        $isVerified = ($record['verification_status'] === 'Approved');
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "is_verified"         => $isVerified,
            "verification_status" => $record['verification_status'],
            "citizen_id_number"   => $record['citizen_id_number'],
            "photo_1x1_url"       => $record['photo_1x1_url'],
            "signature_photo_url" => $record['signature_photo_url'],
            "qr_code_token"       => $record['qr_code_token'],
            "qr_code_image_url"   => $record['qr_code_image_url'],
            "rejection_reason"    => $record['rejection_reason'],
            "admin_action_notes"  => $record['admin_action_notes'],
            "data"                => $record
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status"              => "success",
            "data"                => null,
            "verification_status" => "Not_Submitted",
            "is_verified"         => false,
            "message"             => "No verification record found for this citizen."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
