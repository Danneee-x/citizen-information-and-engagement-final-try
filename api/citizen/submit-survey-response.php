<?php
/**
 * API: POST /api/citizen/submit-survey-response.php
 * Records a citizen's completed questionnaire response to MySQL in Philippine Time.
 */

error_reporting(0);
ini_set('display_errors', '0');
ob_start();

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../pages/public-consultation/consultation-db-helper.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_length()) ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    ConsultationDB::ensureTables();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data)) {
        $data = $_POST;
    }

    $surveyId = isset($data['survey_id']) ? (int)$data['survey_id'] : (isset($data['id']) ? (int)$data['id'] : 0);
    if ($surveyId <= 0) {
        if (ob_get_length()) ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Missing or invalid survey_id.']);
        exit;
    }

    $citizenId   = !empty($data['citizen_id']) ? (int)$data['citizen_id'] : (!empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : null);
    $citizenName = !empty($data['citizen_name']) ? trim($data['citizen_name']) : 'Verified Citizen';
    $barangay    = !empty($data['barangay']) ? trim($data['barangay']) : 'Barangay 178 (Camarin)';
    $ageGroup    = !empty($data['age_group']) ? trim($data['age_group']) : null;
    $gender      = !empty($data['gender']) ? trim($data['gender']) : null;
    $commentary  = !empty($data['commentary']) ? trim($data['commentary']) : null;
    $rating      = isset($data['overall_rating']) && is_numeric($data['overall_rating']) ? (float)$data['overall_rating'] : null;

    $answers     = !empty($data['answers']) ? $data['answers'] : [];
    $answersJson = is_string($answers) ? $answers : json_encode($answers, JSON_UNESCAPED_UNICODE);

    $pdo = getDbConnection();
    $currentPst = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO `survey_responses` 
        (`survey_id`, `citizen_id`, `citizen_name`, `barangay`, `age_group`, `gender`, `answers_json`, `overall_rating`, `commentary`, `submitted_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $surveyId,
        $citizenId,
        $citizenName,
        $barangay,
        $ageGroup,
        $gender,
        $answersJson,
        $rating,
        $commentary,
        $currentPst
    ]);

    $insertId = (int)$pdo->lastInsertId();

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'     => true,
        'status'      => 'success',
        'message'     => 'Survey response recorded successfully.',
        'response_id' => $insertId,
        'submitted_at'=> $currentPst
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'error',
        'message' => 'Failed to record survey response: ' . $e->getMessage()
    ]);
}
