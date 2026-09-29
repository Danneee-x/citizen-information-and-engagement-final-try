<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$idCategory = trim($data['id_category'] ?? 'citizen_id');
$idTitle = trim($data['id_title'] ?? 'Caloocan ID Card');
$appType = trim($data['application_type'] ?? 'New Application');
$firstName = trim($data['first_name'] ?? '');
$middleName = trim($data['middle_name'] ?? '');
$lastName = trim($data['last_name'] ?? '');
$suffix = trim($data['suffix'] ?? '');
$gender = trim($data['gender'] ?? 'Male');
$birthdate = !empty($data['birthdate']) ? $data['birthdate'] : null;
$civilStatus = trim($data['civil_status'] ?? 'Single');
$contactNumber = trim($data['contact_number'] ?? '');
$email = trim($data['email'] ?? '');
$street = trim($data['street_address'] ?? '');
$barangay = trim($data['barangay'] ?? 'Barangay 171');
$district = trim($data['district'] ?? 'District 1');
$residentSince = trim($data['resident_since'] ?? '2015');
$issuingBureau = trim($data['issuing_bureau'] ?? 'Caloocan Civil Registry Bureau');
$primaryDocName = trim($data['primary_doc_name'] ?? 'Government Valid ID');
$claimOffice = trim($data['claim_office'] ?? 'Caloocan Main City Hall');
$turnaround = trim($data['estimated_turnaround'] ?? '3 to 5 Business Days');
$userId = !empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : null;

if (empty($firstName) || empty($lastName) || empty($contactNumber)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "First name, last name, and contact number are required."]);
    exit;
}

$prefixes = [
    'citizen_id' => 'CAL-CIT',
    'barangay_id' => 'CAL-BRGY',
    'solo_parent_id' => 'CAL-SP',
    'pwd_id' => 'CAL-PWD',
    'senior_citizen_id' => 'CAL-SR'
];

$prefix = $prefixes[$idCategory] ?? 'CAL-ID';
$refNo = !empty($data['reference_no']) ? trim($data['reference_no']) : ($prefix . '-2026-' . rand(1000, 9999));

try {
    $pdo = getCertificateDbConnection();

    $stmt = $pdo->prepare("
        INSERT INTO `id_issuance_applications` 
        (`reference_no`, `citizen_user_id`, `id_category`, `id_title`, `application_type`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `contact_number`, `email`, `street_address`, `barangay`, `district`, `resident_since`, `issuing_bureau`, `primary_doc_name`, `status`, `claim_office`, `estimated_turnaround`, `created_at`)
        VALUES 
        (:ref_no, :uid, :cat, :title, :app_type, :first, :middle, :last, :suffix, :gender, :bday, :civil, :contact, :email, :street, :brgy, :dist, :res_since, :bureau, :doc_name, 'Pending Review', :claim_office, :turnaround, NOW())
    ");

    $stmt->execute([
        ':ref_no' => $refNo,
        ':uid' => $userId,
        ':cat' => $idCategory,
        ':title' => $idTitle,
        ':app_type' => $appType,
        ':first' => $firstName,
        ':middle' => $middleName,
        ':last' => $lastName,
        ':suffix' => $suffix,
        ':gender' => $gender,
        ':bday' => $birthdate,
        ':civil' => $civilStatus,
        ':contact' => $contactNumber,
        ':email' => $email,
        ':street' => $street,
        ':brgy' => $barangay,
        ':dist' => $district,
        ':res_since' => $residentSince,
        ':bureau' => $issuingBureau,
        ':doc_name' => $primaryDocName,
        ':claim_office' => $claimOffice,
        ':turnaround' => $turnaround
    ]);

    // Mirror into citizen_verification DB if accessible
    try {
        $verPdo = getDbConnection();
        $verPdo->exec("REPLACE INTO `citizen_verification`.`id_issuance_applications` SELECT * FROM `civentral_certificates`.`id_issuance_applications` WHERE `reference_no` = '{$refNo}';");
    } catch (Exception $mirrorEx) {}

    echo json_encode([
        "status" => "success",
        "message" => "ID application successfully filed.",
        "data" => [
            "reference_no" => $refNo,
            "id_category" => $idCategory,
            "id_title" => $idTitle,
            "application_type" => $appType,
            "applicant_name" => trim("{$firstName} {$lastName}"),
            "status" => "Pending Review",
            "claim_office" => $claimOffice,
            "estimated_turnaround" => $turnaround,
            "created_at" => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database insertion error: " . $e->getMessage()]);
}
