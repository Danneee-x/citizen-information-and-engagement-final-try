<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getCertificateDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit;
}

// Ensure table exists with all required columns
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `id_issuance_applications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(50) NOT NULL UNIQUE,
        `citizen_user_id` INT UNSIGNED NULL,
        `id_category` VARCHAR(50) NOT NULL,
        `id_title` VARCHAR(150) NOT NULL,
        `application_type` ENUM('New Application', 'Renewal', 'Replacement') NOT NULL DEFAULT 'New Application',
        `first_name` VARCHAR(100) NOT NULL,
        `middle_name` VARCHAR(100) NULL DEFAULT '',
        `last_name` VARCHAR(100) NOT NULL,
        `suffix` VARCHAR(20) NULL DEFAULT '',
        `gender` VARCHAR(20) DEFAULT 'Male',
        `birthdate` DATE NULL,
        `civil_status` VARCHAR(50) DEFAULT 'Single',
        `contact_number` VARCHAR(50) NOT NULL,
        `email` VARCHAR(150) NULL,
        `street_address` VARCHAR(255) NOT NULL,
        `barangay` VARCHAR(100) NOT NULL,
        `district` VARCHAR(50) DEFAULT 'District 1',
        `resident_since` VARCHAR(50) DEFAULT '2015',
        `issuing_bureau` VARCHAR(200) NOT NULL,
        `claim_office` VARCHAR(200) NULL,
        `estimated_turnaround` VARCHAR(100) NULL,
        `card_serial_number` VARCHAR(100) NULL,
        `claimed_by_name` VARCHAR(150) NULL,
        `claim_notes` TEXT NULL,
        `verified_photo_id` TINYINT(1) DEFAULT 0,
        `verified_residency_proof` TINYINT(1) DEFAULT 0,
        `verified_claim_voucher` TINYINT(1) DEFAULT 0,
        `primary_doc_name` VARCHAR(150) NULL,
        `primary_doc_url` TEXT NULL,
        `photo_2x2_url` TEXT NULL,
        `support_doc_name` VARCHAR(150) NULL,
        `support_doc_url` TEXT NULL,
        `status` ENUM('Pending Review', 'Under Review', 'Processing & Verification', 'Approved', 'Ready to Print', 'Ready for Release', 'Claimed', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending Review',
        `review_notes` TEXT NULL,
        `rejection_reason` TEXT NULL,
        `reviewed_by` VARCHAR(100) NULL,
        `reviewed_at` DATETIME NULL,
        `released_by` VARCHAR(100) NULL,
        `released_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cat (`id_category`),
        INDEX idx_stat (`status`),
        INDEX idx_brgy (`barangay`),
        INDEX idx_uid (`citizen_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}

// 1. GET: Retrieve applications for a citizen or by reference number
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $refNo = trim($_GET['reference_no'] ?? '');
        $userId = !empty($_GET['citizen_user_id']) ? (int)$_GET['citizen_user_id'] : null;
        $email = trim($_GET['email'] ?? ($_GET['citizen_email'] ?? ''));

        $where = [];
        $params = [];

        if (!empty($refNo)) {
            $where[] = "`reference_no` = :ref";
            $params[':ref'] = $refNo;
        } elseif ($userId || !empty($email)) {
            $subWhere = [];
            if ($userId) {
                $subWhere[] = "`citizen_user_id` = :uid";
                $params[':uid'] = $userId;
            }
            if (!empty($email)) {
                $subWhere[] = "`email` = :email";
                $params[':email'] = $email;
            }
            $where[] = '(' . implode(' OR ', $subWhere) . ')';
        }

        $sql = "SELECT * FROM `id_issuance_applications`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY `id` DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'count' => count($records),
            'data' => $records
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 2. POST: Submit a new ID application from Citizen Mobile App or Portal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data && !empty($_POST)) {
            $data = $_POST;
        }

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No application payload received.']);
            exit;
        }

        $category = trim($data['id_category'] ?? 'barangay_id');
        $prefixes = [
            'citizen_id' => 'CAL-CIT',
            'barangay_id' => 'CAL-BRGY',
            'solo_parent_id' => 'CAL-SP',
            'pwd_id' => 'CAL-PWD',
            'senior_citizen_id' => 'CAL-SR'
        ];
        $prefix = $prefixes[$category] ?? 'CAL-ID';
        
        $refNo = !empty($data['reference_no']) ? trim($data['reference_no']) : ($prefix . '-2026-' . rand(1000, 9999));
        
        // Ensure uniqueness
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `reference_no` = :ref");
        $checkStmt->execute([':ref' => $refNo]);
        if ($checkStmt->fetchColumn() > 0) {
            $refNo = $prefix . '-2026-' . rand(1000, 9999);
        }

        $idTitles = [
            'citizen_id' => 'Caloocan Citizen Unified ID Card',
            'barangay_id' => 'Barangay Resident Identification Card',
            'solo_parent_id' => 'Solo Parent Welfare Identification Card (RA 11861)',
            'pwd_id' => 'Persons with Disability Card (Republic Act 10754)',
            'senior_citizen_id' => 'OSCA Senior Citizen Card (Republic Act 9994)'
        ];

        $bureaus = [
            'citizen_id' => 'Caloocan Civil Registry & Identity Management Bureau',
            'barangay_id' => 'Respective Barangay Executive Office & Secretariat',
            'solo_parent_id' => 'City Social Welfare & Development Office (CSWDO)',
            'pwd_id' => 'Persons with Disability Affairs Office (PDAO)',
            'senior_citizen_id' => 'Office of Senior Citizens Affairs (OSCA)'
        ];

        $offices = [
            'citizen_id' => 'Caloocan Main City Hall - Window 6',
            'barangay_id' => 'Local Barangay Hall - Administrative Records Desk',
            'solo_parent_id' => 'Caloocan CSWDO Office - Solo Parent Desk',
            'pwd_id' => 'PDAO Counter - City Hall Ground Floor',
            'senior_citizen_id' => 'OSCA Main Building - Caloocan City Complex'
        ];

        $turnarounds = [
            'citizen_id' => '3 to 5 Business Days',
            'barangay_id' => '1 to 2 Business Days',
            'solo_parent_id' => '5 to 7 Business Days',
            'pwd_id' => '3 to 5 Business Days',
            'senior_citizen_id' => '2 to 4 Business Days'
        ];

        $title = !empty($data['id_title']) ? trim($data['id_title']) : ($idTitles[$category] ?? 'Caloocan ID Card');
        $bureau = !empty($data['issuing_bureau']) ? trim($data['issuing_bureau']) : ($bureaus[$category] ?? 'Caloocan Municipal Office');
        $claimOffice = !empty($data['claim_office']) ? trim($data['claim_office']) : ($offices[$category] ?? 'Local Barangay Hall - Administrative Records Desk');
        $turnaround = !empty($data['estimated_turnaround']) ? trim($data['estimated_turnaround']) : ($turnarounds[$category] ?? '1 to 2 Business Days');

        $firstName = trim($data['first_name'] ?? '');
        $middleName = trim($data['middle_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $suffix = trim($data['suffix'] ?? '');
        $contactNumber = trim($data['contact_number'] ?? '');
        $streetAddress = trim($data['street_address'] ?? '');
        $barangay = trim($data['barangay'] ?? 'Barangay 171');

        if (empty($firstName) || empty($lastName) || empty($contactNumber)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'First name, last name, and contact number are required.']);
            exit;
        }

        $emergencyName = trim($data['emergency_contact_name'] ?? '');
        $emergencyPhone = trim($data['emergency_contact_phone'] ?? '');
        $emergencyRelation = trim($data['emergency_contact_relation'] ?? 'Next of Kin');
        $signatureUrl = trim($data['signature_url'] ?? '');
        $eSignatureName = trim($data['e_signature_name'] ?? '');
        $signatureMode = trim($data['signature_mode'] ?? 'upload');

        $stmt = $pdo->prepare("
            INSERT INTO `id_issuance_applications` 
            (`reference_no`, `citizen_user_id`, `id_category`, `id_title`, `application_type`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `contact_number`, `email`, `street_address`, `barangay`, `district`, `resident_since`, `issuing_bureau`, `claim_office`, `estimated_turnaround`, `primary_doc_name`, `primary_doc_url`, `photo_2x2_url`, `support_doc_name`, `support_doc_url`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `signature_url`, `e_signature_name`, `signature_mode`, `status`, `created_at`)
            VALUES
            (:ref_no, :uid, :cat, :title, :app_type, :first, :middle, :last, :suffix, :gender, :bday, :civil, :contact, :email, :street, :brgy, :dist, :res_since, :bureau, :claim_office, :turnaround, :doc_name, :doc_url, :photo_url, :sup_name, :sup_url, :em_name, :em_phone, :em_rel, :sig_url, :esig_name, :sig_mode, 'Pending Review', NOW())
        ");

        $stmt->execute([
            ':ref_no' => $refNo,
            ':uid' => !empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : null,
            ':cat' => $category,
            ':title' => $title,
            ':app_type' => !empty($data['application_type']) ? $data['application_type'] : 'New Application',
            ':first' => $firstName,
            ':middle' => $middleName,
            ':last' => $lastName,
            ':suffix' => $suffix,
            ':gender' => $data['gender'] ?? 'Male',
            ':bday' => !empty($data['birthdate']) ? $data['birthdate'] : null,
            ':civil' => $data['civil_status'] ?? 'Single',
            ':contact' => $contactNumber,
            ':email' => !empty($data['email']) ? trim($data['email']) : null,
            ':street' => $streetAddress,
            ':brgy' => $barangay,
            ':dist' => $data['district'] ?? 'District 1',
            ':res_since' => $data['resident_since'] ?? '2015',
            ':bureau' => $bureau,
            ':claim_office' => $claimOffice,
            ':turnaround' => $turnaround,
            ':doc_name' => $data['primary_doc_name'] ?? 'Proof of Residency (Min 6 Months)',
            ':doc_url' => $data['primary_doc_url'] ?? null,
            ':photo_url' => $data['photo_2x2_url'] ?? null,
            ':sup_name' => $data['support_doc_name'] ?? null,
            ':sup_url' => $data['support_doc_url'] ?? null,
            ':em_name' => $emergencyName ?: null,
            ':em_phone' => $emergencyPhone ?: null,
            ':em_rel' => $emergencyRelation ?: null,
            ':sig_url' => $signatureUrl ?: null,
            ':esig_name' => $eSignatureName ?: null,
            ':sig_mode' => $signatureMode ?: null,
        ]);

        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Application filed successfully.',
            'data' => [
                'id' => $newId,
                'reference_no' => $refNo,
                'claim_office' => $claimOffice,
                'estimated_turnaround' => $turnaround,
                'status' => 'Pending Review'
            ]
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save application: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
