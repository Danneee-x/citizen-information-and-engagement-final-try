<?php
// Suppress warnings / notices from polluting JSON API output
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

/**
 * API: POST /api/citizen/submit-consultation-feedback.php
 * Records a citizen's civic consultation feedback/opinion to MySQL.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../pages/public-consultation/consultation-db-helper.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    ConsultationDB::ensureTables();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data)) {
        $data = $_POST;
    }

    $consultationId = isset($data['consultation_id']) ? (int)$data['consultation_id'] : (isset($data['id']) ? (int)$data['id'] : 0);
    if ($consultationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid consultation_id.']);
        exit;
    }

    $stance = !empty($data['stance']) ? trim($data['stance']) : 'In Favor';
    $validStances = ['In Favor', 'Neutral', 'Against', 'Suggested Amendments'];
    if (!in_array($stance, $validStances)) {
        $stance = 'In Favor';
    }

    $commentary = !empty($data['commentary']) ? trim($data['commentary']) : '';
    if (empty($commentary)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Commentary is required.']);
        exit;
    }

    $citizenId   = !empty($data['citizen_id']) ? (int)$data['citizen_id'] : (!empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : null);
    $citizenName = !empty($data['citizen_name']) ? trim($data['citizen_name']) : 'Caloocan Resident';
    $barangay    = !empty($data['barangay']) ? trim($data['barangay']) : 'Barangay 176 (Bagong Silang)';

    $pdo = Database::getInstance()->getPdo();
    $stmt = $pdo->prepare("
        INSERT INTO `consultation_feedbacks` 
        (`consultation_id`, `citizen_id`, `citizen_name`, `barangay`, `stance`, `commentary`, `submitted_at`)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $consultationId,
        $citizenId,
        $citizenName,
        $barangay,
        $stance,
        $commentary
    ]);

    $insertId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'     => true,
        'status'      => 'success',
        'message'     => 'Consultation feedback recorded successfully.',
        'feedback_id' => $insertId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'error',
        'message' => 'Failed to record consultation feedback: ' . $e->getMessage()
    ]);
}
