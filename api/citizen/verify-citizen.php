<?php
/**
 * Endpoint: /api/citizen/verify-citizen.php
 * Handles Citizen Verification Form submissions and status checks.
 */

// 1. CORS Configuration
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

// Helper to save base64 uploaded photos to server disk
if (!function_exists('saveBase64Image')) {
    function saveBase64Image($dataUrl, $prefix = 'photo') {
        if (empty($dataUrl)) return null;
        $dataUrl = trim($dataUrl);

        if (strpos($dataUrl, 'http://') === 0 || strpos($dataUrl, 'https://') === 0 || strpos($dataUrl, 'assets/') === 0 || strpos($dataUrl, '/uploads/') === 0) {
            return $dataUrl;
        }

        $ext = 'jpg';
        $data = null;

        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
            $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
        } elseif (strlen($dataUrl) > 100 && preg_match('/^[a-zA-Z0-9\/+=\s]+$/', $dataUrl)) {
            $data = $dataUrl;
        }

        if ($data !== null) {
            $decoded = base64_decode(trim($data));
            if ($decoded !== false && strlen($decoded) > 0) {
                $filename = $prefix . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;

                $targetDirs = [
                    __DIR__ . '/../../assets/uploads/verifications/',
                    'C:/xampp/htdocs/civentral-citizen-information-and-engagement/assets/uploads/verifications/',
                    'C:/xampp/htdocs/citizen-information-and-engagement-final-try/assets/uploads/verifications/',
                    'C:/xampp/htdocs/citizen-backend/assets/uploads/verifications/'
                ];

                foreach ($targetDirs as $dir) {
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    @file_put_contents($dir . $filename, $decoded);
                }

                return 'assets/uploads/verifications/' . $filename;
            }
        }

        return $dataUrl;
    }
}

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failure: " . $e->getMessage()]);
    exit;
}

// 2. Handle GET: System Health & Status Check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `citizen_verifications`");
        $total = $countStmt->fetchColumn();

        $recentStmt = $pdo->query("SELECT verification_id, citizen_user_id, first_name, last_name, verification_status, submitted_at FROM `citizen_verifications` ORDER BY `verification_id` DESC LIMIT 10");
        $recent = $recentStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'database' => 'citizen_verification',
            'message' => 'Civentral Citizen Verification API is online and healthy.',
            'total_verifications_stored' => (int)$total,
            'recent_submissions' => $recent
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 3. Handle POST: Citizen Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data && !empty($rawInput)) {
        $clean = mb_convert_encoding($rawInput, 'UTF-8', 'UTF-8');
        $data = json_decode($clean, true);
    }
    if (!$data && !empty($_POST)) {
        $data = $_POST;
    }

    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON payload received.',
            'debug' => json_last_error_msg()
        ]);
        exit;
    }

    // Extract & sanitize fields
    $firstName     = trim($data['first_name'] ?? '');
    $lastName      = trim($data['last_name'] ?? '');
    $middleName    = trim($data['middle_name'] ?? '') ?: null;
    $suffix        = trim($data['suffix'] ?? '') ?: null;
    $sex           = $data['sex'] ?? 'Male';
    $placeOfBirth  = trim($data['place_of_birth'] ?? '');
    $birthDate     = $data['birth_date'] ?? '2000-01-01';
    $civilStatus   = $data['civil_status'] ?? 'Single';
    $employment    = $data['employment_status'] ?? 'Employed';
    $occupation    = $data['occupation'] ?? 'Other';
    $education     = $data['educational_attainment'] ?? 'College Graduate';
    $district      = $data['district'] ?? 'District 1';
    $barangay      = $data['barangay'] ?? '';
    $streetAddress = trim($data['street_address'] ?? '');
    $yearsResident = intval($data['years_resident'] ?? 1);
    $validIdType   = $data['valid_id_type'] ?? 'PhilSys National ID';
    $validIdNumber = trim($data['valid_id_number'] ?? '');
    $rawIdFront    = $data['id_front_photo_url'] ?? null;
    $rawSelfie     = $data['selfie_photo_url'] ?? null;
    $citizenUserId = intval($data['citizen_user_id'] ?? 0);

    if (empty($firstName) || empty($lastName) || empty($barangay) || empty($validIdNumber)) {
        http_response_code(422);
        echo json_encode([
            "status"  => "error",
            "message" => "Required fields missing: First Name, Last Name, Barangay, and Valid ID Number are required."
        ]);
        exit;
    }

    try {
        // Ensure citizen_users table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `citizen_users` (
            `citizen_user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `first_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100) DEFAULT NULL,
            `has_no_middle_name` TINYINT(1) NOT NULL DEFAULT 0,
            `last_name` VARCHAR(100) NOT NULL,
            `suffix` VARCHAR(20) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `mobile_number` VARCHAR(20) DEFAULT NULL,
            `password` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
            `registry_completed` TINYINT(1) NOT NULL DEFAULT 0,
            `failed_attempts` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            `last_login` DATETIME DEFAULT NULL,
            `biometric_enabled` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`citizen_user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Guarantee foreign key constraint fk_verif_user exists in citizen_users
        if ($citizenUserId > 0) {
            $userCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE citizen_user_id = ?");
            $userCheck->execute([$citizenUserId]);
            if (!$userCheck->fetch()) {
                $email = !empty($data['email']) ? trim($data['email']) : (strtolower(preg_replace('/[^a-z0-9]/', '', $firstName . '.' . $lastName)) . '_' . $citizenUserId . '@citizen.local');
                $phone = !empty($data['phone']) ? trim($data['phone']) : ('09' . str_pad($citizenUserId, 9, '0', STR_PAD_LEFT));

                $emailCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE email = ?");
                $emailCheck->execute([$email]);
                if ($emailCheck->fetch()) {
                    $email = 'user_' . $citizenUserId . '_' . time() . '@citizen.local';
                }

                $phoneCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE mobile_number = ?");
                $phoneCheck->execute([$phone]);
                if ($phoneCheck->fetch()) {
                    $phone = '09' . substr(str_shuffle('0123456789'), 0, 9);
                }

                $insUser = $pdo->prepare("INSERT INTO citizen_users (
                    citizen_user_id, first_name, middle_name, last_name, suffix, email, mobile_number, status, registry_completed
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', 0)");
                $insUser->execute([
                    $citizenUserId,
                    $firstName,
                    $middleName,
                    $lastName,
                    $suffix,
                    $email,
                    $phone
                ]);
            }
        } else {
            // Find user by name or create a new user entry
            $findUser = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE first_name = ? AND last_name = ? LIMIT 1");
            $findUser->execute([$firstName, $lastName]);
            $existing = $findUser->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $citizenUserId = (int)$existing['citizen_user_id'];
            } else {
                $email = !empty($data['email']) ? trim($data['email']) : (strtolower(preg_replace('/[^a-z0-9]/', '', $firstName . '.' . $lastName)) . '_' . time() . '@citizen.local');
                $phone = !empty($data['phone']) ? trim($data['phone']) : ('09' . substr(str_shuffle('0123456789'), 0, 9));
                $insUser = $pdo->prepare("INSERT INTO citizen_users (
                    first_name, middle_name, last_name, suffix, email, mobile_number, status, registry_completed
                ) VALUES (?, ?, ?, ?, ?, ?, 'Active', 0)");
                $insUser->execute([$firstName, $middleName, $lastName, $suffix, $email, $phone]);
                $citizenUserId = (int)$pdo->lastInsertId();
            }
        }

        // Process and save photos
        $idFrontSavedPath = saveBase64Image($rawIdFront, 'id_front');
        $selfieSavedPath  = saveBase64Image($rawSelfie, 'selfie');

        // Insert verification row
        $stmt = $pdo->prepare("INSERT INTO citizen_verifications (
            citizen_user_id, first_name, middle_name, last_name, suffix, sex,
            place_of_birth, birth_date, civil_status, employment_status, occupation,
            educational_attainment, district, barangay, street_address, years_resident,
            valid_id_type, valid_id_number, id_front_photo_url, selfie_photo_url, verification_status
        ) VALUES (
            :citizen_user_id, :first_name, :middle_name, :last_name, :suffix, :sex,
            :place_of_birth, :birth_date, :civil_status, :employment_status, :occupation,
            :educational_attainment, :district, :barangay, :street_address, :years_resident,
            :valid_id_type, :valid_id_number, :id_front_photo_url, :selfie_photo_url, 'Pending'
        )");

        $stmt->execute([
            ':citizen_user_id'         => $citizenUserId,
            ':first_name'              => $firstName,
            ':middle_name'             => $middleName,
            ':last_name'               => $lastName,
            ':suffix'                  => $suffix,
            ':sex'                     => $sex,
            ':place_of_birth'          => $placeOfBirth,
            ':birth_date'              => $birthDate,
            ':civil_status'            => $civilStatus,
            ':employment_status'       => $employment,
            ':occupation'              => $occupation,
            ':educational_attainment'  => $education,
            ':district'                => $district,
            ':barangay'                => $barangay,
            ':street_address'          => $streetAddress,
            ':years_resident'          => $yearsResident,
            ':valid_id_type'           => $validIdType,
            ':valid_id_number'         => $validIdNumber,
            ':id_front_photo_url'      => $idFrontSavedPath,
            ':selfie_photo_url'        => $selfieSavedPath,
        ]);

        $verificationId = (int)$pdo->lastInsertId();

        // Check for duplicates
        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM citizen_verifications WHERE valid_id_number = :id_num AND verification_id != :curr_id");
        $dupStmt->execute([
            ':id_num' => $validIdNumber,
            ':curr_id' => $verificationId
        ]);
        $dupCount = (int)$dupStmt->fetchColumn();

        if ($dupCount > 0) {
            $updateDup = $pdo->prepare("UPDATE citizen_verifications SET is_duplicate = 1, duplicate_notes = :notes WHERE verification_id = :id");
            $updateDup->execute([
                ':notes' => "Potential duplicate ID number matched {$dupCount} other verification record(s).",
                ':id' => $verificationId
            ]);
        }

        http_response_code(200);
        echo json_encode([
            "status"             => "success",
            "message"            => "Citizen verification submitted successfully and is under review.",
            "verification_id"    => $verificationId,
            "verification_status"=> "Pending",
            "reference_number"   => 'VER-' . str_pad($verificationId, 4, '0', STR_PAD_LEFT),
            "id_front_saved"     => !empty($idFrontSavedPath),
            "selfie_saved"       => !empty($selfieSavedPath)
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Database insertion error: " . $e->getMessage()
        ]);
        exit;
    }
}
