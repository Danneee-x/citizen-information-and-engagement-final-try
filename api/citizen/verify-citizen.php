<?php
/**
 * Endpoint: /api/citizen/verify-citizen.php
 * Handles Citizen Verification Form submissions, rework resubmissions, and status checks.
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

        $baseUrl = rtrim(getenv('APP_URL') ?: 'https://api-citizen.civentral.tech', '/');

        if (strpos($dataUrl, 'http://') === 0 || strpos($dataUrl, 'https://') === 0) {
            if (preg_match('#/(?:assets/)?uploads/verifications/([^/?]+)#', $dataUrl, $m)) {
                return $baseUrl . '/uploads/verifications/' . $m[1];
            }
            return $dataUrl;
        }

        if (strpos($dataUrl, 'assets/') === 0 || strpos($dataUrl, 'uploads/') === 0 || strpos($dataUrl, '/uploads/') === 0) {
            $clean = ltrim($dataUrl, '/');
            if (strpos($clean, 'assets/uploads/') === 0) {
                $clean = substr($clean, strlen('assets/'));
            }
            return $baseUrl . '/' . $clean;
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
                    '/var/www/html/uploads/verifications/',
                    __DIR__ . '/../../uploads/verifications/',
                    __DIR__ . '/../../assets/uploads/verifications/',
                    'C:/xampp/htdocs/citizen-backend/uploads/verifications/',
                    'C:/xampp/htdocs/citizen-backend/assets/uploads/verifications/',
                    'C:/xampp/htdocs/citizen-information-and-engagement-final-try/assets/uploads/verifications/'
                ];

                foreach ($targetDirs as $dir) {
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                        @chmod($dir, 0775);
                    }
                    if (is_dir($dir)) {
                        @file_put_contents($dir . $filename, $decoded);
                    }
                }

                return $baseUrl . '/uploads/verifications/' . $filename;
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

        $recentStmt = $pdo->query("SELECT verification_id, citizen_id_number, citizen_user_id, first_name, last_name, verification_status, submitted_at FROM `citizen_verifications` ORDER BY `verification_id` DESC LIMIT 10");
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
    $rawPhoto1x1   = $data['photo_1x1_url'] ?? null;
    $rawSignature  = $data['signature_photo_url'] ?? null;
    $citizenUserId = intval($data['citizen_user_id'] ?? 0);

    if (empty($firstName) || empty($lastName) || empty($barangay) || empty($validIdNumber)) {
        http_response_code(422);
        echo json_encode([
            "success" => false,
            "status"  => "error",
            "message" => "Required fields missing: First Name, Last Name, Barangay, and Valid ID Number are required."
        ]);
        exit;
    }

    $email = !empty($data['email']) ? trim($data['email']) : '';
    $phone = !empty($data['phone']) ? trim($data['phone']) : (!empty($data['mobile_number']) ? trim($data['mobile_number']) : '');

    try {
        // 1. If email is provided, resolve the real user ID from citizen_users
        if (!empty($email)) {
            $emailStmt = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE email = ? LIMIT 1");
            $emailStmt->execute([$email]);
            $foundUid = $emailStmt->fetchColumn();
            if ($foundUid) {
                $citizenUserId = (int)$foundUid;
            } else {
                // Register user in citizen_users first
                if (empty($phone)) {
                    $phone = '09' . substr(str_shuffle('0123456789'), 0, 9);
                }
                $insUser = $pdo->prepare("INSERT INTO citizen_users (
                    first_name, middle_name, last_name, suffix, email, mobile_number, status, registry_completed
                ) VALUES (?, ?, ?, ?, ?, ?, 'Active', 0)");
                $insUser->execute([$firstName, $middleName, $lastName, $suffix, $email, $phone]);
                $citizenUserId = (int)$pdo->lastInsertId();
            }
        } elseif ($citizenUserId > 0) {
            // Ensure user exists in citizen_users if citizenUserId > 0
            $userCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE citizen_user_id = ?");
            $userCheck->execute([$citizenUserId]);
            if (!$userCheck->fetch()) {
                $fallbackEmail = strtolower(preg_replace('/[^a-z0-9]/', '', $firstName . '.' . $lastName)) . '_' . $citizenUserId . '@citizen.local';
                $fallbackPhone = '09' . str_pad($citizenUserId, 9, '0', STR_PAD_LEFT);

                $emailCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE email = ?");
                $emailCheck->execute([$fallbackEmail]);
                if ($emailCheck->fetch()) {
                    $fallbackEmail = 'user_' . $citizenUserId . '_' . time() . '@citizen.local';
                }

                $phoneCheck = $pdo->prepare("SELECT citizen_user_id FROM citizen_users WHERE mobile_number = ?");
                $phoneCheck->execute([$fallbackPhone]);
                if ($phoneCheck->fetch()) {
                    $fallbackPhone = '09' . substr(str_shuffle('0123456789'), 0, 9);
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
                    $fallbackEmail,
                    $fallbackPhone
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
                $fallbackEmail = strtolower(preg_replace('/[^a-z0-9]/', '', $firstName . '.' . $lastName)) . '_' . time() . '@citizen.local';
                $fallbackPhone = !empty($phone) ? $phone : ('09' . substr(str_shuffle('0123456789'), 0, 9));
                $insUser = $pdo->prepare("INSERT INTO citizen_users (
                    first_name, middle_name, last_name, suffix, email, mobile_number, status, registry_completed
                ) VALUES (?, ?, ?, ?, ?, ?, 'Active', 0)");
                $insUser->execute([$firstName, $middleName, $lastName, $suffix, $fallbackEmail, $fallbackPhone]);
                $citizenUserId = (int)$pdo->lastInsertId();
            }
        }

        // Process and save photos & signatures
        $idFrontSavedPath   = saveBase64Image($rawIdFront, 'id_front');
        $selfieSavedPath    = saveBase64Image($rawSelfie, 'selfie');
        $photo1x1SavedPath  = saveBase64Image($rawPhoto1x1, 'photo_1x1');
        $signatureSavedPath = saveBase64Image($rawSignature, 'signature');

        // Check for existing active application for this citizen_user_id
        $dupCheckStmt = $pdo->prepare("SELECT verification_id, verification_status, id_front_photo_url, selfie_photo_url, photo_1x1_url, signature_photo_url 
            FROM citizen_verifications 
            WHERE citizen_user_id = ? 
            ORDER BY verification_id DESC LIMIT 1");
        $dupCheckStmt->execute([$citizenUserId]);
        $existingRecord = $dupCheckStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRecord) {
            $curStatus = $existingRecord['verification_status'];

            // 1. Pending or Under_Review: Reject duplicate submission with 409 Conflict
            if ($curStatus === 'Pending' || $curStatus === 'Under_Review') {
                http_response_code(409);
                echo json_encode([
                    "success"             => false,
                    "status"              => "error",
                    "message"             => "You already have an active application under review.",
                    "verification_status" => $curStatus,
                    "verification_id"     => (int)$existingRecord['verification_id']
                ]);
                exit;
            }

            // 2. Approved: Citizen is already registered
            if ($curStatus === 'Approved') {
                http_response_code(409);
                echo json_encode([
                    "success"             => false,
                    "status"              => "error",
                    "message"             => "Citizen is already verified and registered.",
                    "verification_status" => "Approved",
                    "verification_id"     => (int)$existingRecord['verification_id']
                ]);
                exit;
            }

            // 3. Returned_For_Correction: UPDATE existing record instead of inserting a duplicate row!
            if ($curStatus === 'Returned_For_Correction') {
                $finalIdFront   = $idFrontSavedPath ?: $existingRecord['id_front_photo_url'];
                $finalSelfie    = $selfieSavedPath ?: $existingRecord['selfie_photo_url'];
                $finalPhoto1x1  = $photo1x1SavedPath ?: $existingRecord['photo_1x1_url'];
                $finalSignature = $signatureSavedPath ?: $existingRecord['signature_photo_url'];

                $updateStmt = $pdo->prepare("UPDATE citizen_verifications SET
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    suffix = :suffix,
                    sex = :sex,
                    place_of_birth = :place_of_birth,
                    birth_date = :birth_date,
                    civil_status = :civil_status,
                    employment_status = :employment_status,
                    occupation = :occupation,
                    educational_attainment = :educational_attainment,
                    district = :district,
                    barangay = :barangay,
                    street_address = :street_address,
                    years_resident = :years_resident,
                    valid_id_type = :valid_id_type,
                    valid_id_number = :valid_id_number,
                    id_front_photo_url = :id_front_photo_url,
                    selfie_photo_url = :selfie_photo_url,
                    photo_1x1_url = :photo_1x1_url,
                    signature_photo_url = :signature_photo_url,
                    verification_status = 'Pending',
                    admin_action_notes = CONCAT(COALESCE(admin_action_notes, ''), '\n[Resubmitted by Applicant at ', NOW(), ']'),
                    submitted_at = NOW(),
                    updated_at = NOW()
                    WHERE verification_id = :verification_id");

                $updateStmt->execute([
                    ':first_name'             => $firstName,
                    ':middle_name'            => $middleName,
                    ':last_name'              => $lastName,
                    ':suffix'                 => $suffix,
                    ':sex'                    => $sex,
                    ':place_of_birth'         => $placeOfBirth,
                    ':birth_date'             => $birthDate,
                    ':civil_status'           => $civilStatus,
                    ':employment_status'      => $employment,
                    ':occupation'             => $occupation,
                    ':educational_attainment' => $education,
                    ':district'               => $district,
                    ':barangay'               => $barangay,
                    ':street_address'         => $streetAddress,
                    ':years_resident'         => $yearsResident,
                    ':valid_id_type'          => $validIdType,
                    ':valid_id_number'        => $validIdNumber,
                    ':id_front_photo_url'     => $finalIdFront,
                    ':selfie_photo_url'       => $finalSelfie,
                    ':photo_1x1_url'          => $finalPhoto1x1,
                    ':signature_photo_url'    => $finalSignature,
                    ':verification_id'        => $existingRecord['verification_id']
                ]);

                http_response_code(200);
                echo json_encode([
                    "success"             => true,
                    "status"              => "success",
                    "message"             => "Application corrections submitted successfully and is now pending review.",
                    "verification_id"     => (int)$existingRecord['verification_id'],
                    "verification_status" => "Pending",
                    "reference_number"    => 'VER-' . str_pad($existingRecord['verification_id'], 4, '0', STR_PAD_LEFT),
                    "is_resubmission"     => true,
                    "id_front_saved"      => !empty($finalIdFront),
                    "selfie_saved"        => !empty($finalSelfie),
                    "photo_1x1_saved"     => !empty($finalPhoto1x1),
                    "signature_saved"     => !empty($finalSignature)
                ]);
                exit;
            }
        }

        // 4. Fresh Application: Insert new row into citizen_verifications
        $stmt = $pdo->prepare("INSERT INTO citizen_verifications (
            citizen_user_id, first_name, middle_name, last_name, suffix, sex,
            place_of_birth, birth_date, civil_status, employment_status, occupation,
            educational_attainment, district, barangay, street_address, years_resident,
            valid_id_type, valid_id_number, id_front_photo_url, selfie_photo_url,
            photo_1x1_url, signature_photo_url, verification_status
        ) VALUES (
            :citizen_user_id, :first_name, :middle_name, :last_name, :suffix, :sex,
            :place_of_birth, :birth_date, :civil_status, :employment_status, :occupation,
            :educational_attainment, :district, :barangay, :street_address, :years_resident,
            :valid_id_type, :valid_id_number, :id_front_photo_url, :selfie_photo_url,
            :photo_1x1_url, :signature_photo_url, 'Pending'
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
            ':photo_1x1_url'           => $photo1x1SavedPath,
            ':signature_photo_url'     => $signatureSavedPath,
        ]);

        $verificationId = (int)$pdo->lastInsertId();

        // Check for duplicate valid_id_number
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
            "success"             => true,
            "status"              => "success",
            "message"             => "Citizen verification submitted successfully and is under review.",
            "verification_id"     => $verificationId,
            "verification_status" => "Pending",
            "reference_number"    => 'VER-' . str_pad($verificationId, 4, '0', STR_PAD_LEFT),
            "id_front_saved"      => !empty($idFrontSavedPath),
            "selfie_saved"        => !empty($selfieSavedPath),
            "photo_1x1_saved"     => !empty($photo1x1SavedPath),
            "signature_saved"     => !empty($signatureSavedPath)
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "status"  => "error",
            "message" => "Database insertion error: " . $e->getMessage()
        ]);
        exit;
    }
}
