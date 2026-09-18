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

// 1. Handle GET: Retrieve requests for a citizen user or by reference number
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $refNo = $_GET['reference_no'] ?? null;
        $citizenId = $_GET['citizen_user_id'] ?? null;
        $phone = $_GET['phone'] ?? null;

        $where = [];
        $params = [];

        if ($refNo) {
            $where[] = "`reference_no` = :ref";
            $params[':ref'] = $refNo;
        }
        if ($citizenId) {
            $where[] = "`citizen_user_id` = :cid";
            $params[':cid'] = (int)$citizenId;
        }
        if ($phone) {
            $where[] = "`contact_number` = :phone";
            $params[':phone'] = $phone;
        }

        $sql = "SELECT * FROM `certificate_requests`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY `request_id` DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'count' => count($rows),
            'data' => $rows
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 2. Handle POST: Submit new certificate request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $input = !empty($json) ? $json : $_POST;

        $citizenName = trim($input['applicant_name'] ?? ($input['citizen_name'] ?? ''));
        $certType = trim($input['certificate_type'] ?? ($input['cert_type'] ?? ''));
        $purpose = trim($input['purpose'] ?? '');

        if (empty($citizenName)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Applicant name is required.']);
            exit;
        }
        if (empty($certType)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Certificate type is required.']);
            exit;
        }
        if (empty($purpose)) {
            $purpose = 'General Personal / Identification Requirement';
        }

        $streetAddress = trim($input['street_address'] ?? ($input['address'] ?? 'Caloocan City'));
        $barangay = trim($input['barangay'] ?? 'Barangay 171 (Bagumbong)');
        $district = trim($input['district'] ?? 'District 1');
        $contactNumber = trim($input['contact_number'] ?? ($input['phone'] ?? ''));
        $email = trim($input['email'] ?? '');
        $civilStatus = trim($input['civil_status'] ?? 'Single');
        $residentSince = trim($input['resident_since'] ?? '2015');
        $purposeDetails = trim($input['purpose_details'] ?? '');
        $additionalNotes = trim($input['additional_notes'] ?? '');
        $citizenUserId = !empty($input['citizen_user_id']) ? (int)$input['citizen_user_id'] : null;
        $encodedBy = trim($input['encoded_by'] ?? 'Citizen Mobile App');

        // Document Fee calculation schedule
        $feeMap = [
            'Barangay Certificate' => 50.00,
            'Certificate of Residency' => 50.00,
            'Certificate of Indigency' => 0.00,
            'Barangay Clearance' => 75.00,
            'First-Time Jobseeker Certificate (RA 11261)' => 0.00,
            'Business Permit Clearance' => 200.00,
            'Certificate of Good Moral Character' => 50.00,
            'Other Available Certificates' => 50.00
        ];

        $feeAmount = 50.00;
        foreach ($feeMap as $key => $fee) {
            if (stripos($certType, $key) !== false || stripos($key, $certType) !== false) {
                $feeAmount = $fee;
                break;
            }
        }

        $isWaived = ($feeAmount == 0.00) || stripos($certType, 'Indigency') !== false || stripos($certType, 'Jobseeker') !== false;
        $paymentStatus = $isWaived ? 'Waived' : 'Pending';
        $orNumber = $isWaived ? ('WAIVED-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $certType), 0, 8))) : null;

        // Document attachments handling
        $uploadedDocs = $input['uploaded_documents'] ?? ($input['uploaded_files'] ?? []);
        if (is_array($uploadedDocs)) {
            $uploadedDocs = json_encode($uploadedDocs);
        }

        // Generate unique reference number CAL-DOC-2026-XXXX
        $randNum = mt_rand(1000, 9999);
        $refNo = "CAL-DOC-2026-{$randNum}";

        // Verify uniqueness
        $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM `certificate_requests` WHERE `reference_no` = :ref");
        $chkStmt->execute([':ref' => $refNo]);
        if ($chkStmt->fetchColumn() > 0) {
            $refNo = "CAL-DOC-2026-" . mt_rand(10000, 99999);
        }

        $insertSql = "INSERT INTO `certificate_requests` (
            `reference_no`, `citizen_user_id`, `citizen_name`, `contact_number`, `email`,
            `street_address`, `barangay`, `district`, `civil_status`, `resident_since`,
            `certificate_type`, `purpose`, `purpose_details`, `additional_notes`,
            `fee_amount`, `payment_status`, `or_number`, `uploaded_documents`,
            `status`, `encoded_by`
        ) VALUES (
            :reference_no, :citizen_user_id, :citizen_name, :contact_number, :email,
            :street_address, :barangay, :district, :civil_status, :resident_since,
            :certificate_type, :purpose, :purpose_details, :additional_notes,
            :fee_amount, :payment_status, :or_number, :uploaded_documents,
            'Pending', :encoded_by
        )";

        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':reference_no' => $refNo,
            ':citizen_user_id' => $citizenUserId,
            ':citizen_name' => $citizenName,
            ':contact_number' => $contactNumber,
            ':email' => $email,
            ':street_address' => $streetAddress,
            ':barangay' => $barangay,
            ':district' => $district,
            ':civil_status' => $civilStatus,
            ':resident_since' => $residentSince,
            ':certificate_type' => $certType,
            ':purpose' => $purpose,
            ':purpose_details' => $purposeDetails,
            ':additional_notes' => $additionalNotes,
            ':fee_amount' => $feeAmount,
            ':payment_status' => $paymentStatus,
            ':or_number' => $orNumber,
            ':uploaded_documents' => $uploadedDocs,
            ':encoded_by' => $encodedBy
        ]);

        $newId = $pdo->lastInsertId();

        // Also mirror to citizen_verification.certificate_requests if exists
        try {
            $mirrorPdo = getDbConnection();
            $mirrorStmt = $mirrorPdo->prepare($insertSql);
            $mirrorStmt->execute([
                ':reference_no' => $refNo,
                ':citizen_user_id' => $citizenUserId,
                ':citizen_name' => $citizenName,
                ':contact_number' => $contactNumber,
                ':email' => $email,
                ':street_address' => $streetAddress,
                ':barangay' => $barangay,
                ':district' => $district,
                ':civil_status' => $civilStatus,
                ':resident_since' => $residentSince,
                ':certificate_type' => $certType,
                ':purpose' => $purpose,
                ':purpose_details' => $purposeDetails,
                ':additional_notes' => $additionalNotes,
                ':fee_amount' => $feeAmount,
                ':payment_status' => $paymentStatus,
                ':or_number' => $orNumber,
                ':uploaded_documents' => $uploadedDocs,
                ':encoded_by' => $encodedBy
            ]);
        } catch (Exception $eMirror) {}

        echo json_encode([
            'status' => 'success',
            'message' => 'Certificate request successfully submitted.',
            'data' => [
                'request_id' => (int)$newId,
                'reference_no' => $refNo,
                'certificate_type' => $certType,
                'applicant_name' => $citizenName,
                'barangay' => $barangay,
                'status' => 'Pending',
                'fee_amount' => number_format($feeAmount, 2),
                'payment_status' => $paymentStatus,
                'submission_date' => date('M j, Y • h:i A'),
                'pickup_location' => "{$barangay} Barangay Hall - Clearance Counter"
            ]
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to process certificate request: ' . $e->getMessage()]);
        exit;
    }
}
