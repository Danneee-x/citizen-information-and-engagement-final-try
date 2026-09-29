<?php
/**
 * Civentral Citizen API - Submit Community Ratings & Feedback
 * Endpoint: POST /api/citizen/submit-community-feedback.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Locate DB config
$dbConfigPath = __DIR__ . '/../../config/database.php';
if (!file_exists($dbConfigPath)) {
    $dbConfigPath = 'C:/xampp/htdocs/citizen-information-and-engagement-final-try/config/database.php';
}
if (file_exists($dbConfigPath)) {
    require_once $dbConfigPath;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$serviceName = trim($input['service_name'] ?? $input['serviceName'] ?? 'General City Hall & Customer Assistance');
$overallRating = max(1, min(5, (int)($input['overall_rating'] ?? $input['overallRating'] ?? 5)));
$qualityRating = max(1, min(5, (int)($input['quality_rating'] ?? $input['qualityRating'] ?? 5)));
$staffRating = max(1, min(5, (int)($input['staff_rating'] ?? $input['staffRating'] ?? 5)));
$comments = trim($input['comments'] ?? '');
$transactionRef = trim($input['transaction_ref'] ?? $input['referenceNumber'] ?? '');
$citizenName = trim($input['citizen_name'] ?? $input['citizenName'] ?? 'Anonymous Citizen');
$citizenEmail = trim($input['citizen_email'] ?? $input['citizenEmail'] ?? '');
$citizenBarangay = trim($input['citizen_barangay'] ?? $input['citizenBarangay'] ?? 'Barangay Central');

// Determine Sentiment
$avgRating = ($overallRating + $qualityRating + $staffRating) / 3;
if ($avgRating >= 3.8) {
    $sentiment = 'Positive';
} elseif ($avgRating >= 2.8) {
    $sentiment = 'Neutral';
} else {
    $sentiment = 'Negative';
}

// Generate Feedback Reference: CAL-FDB-YYYY-XXXX
$randNum = mt_rand(1000, 9999);
$feedbackRef = 'CAL-FDB-' . date('Y') . '-' . $randNum;

try {
    $pdo = null;
    if (function_exists('getDbConnection')) {
        try {
            $pdo = getDbConnection();
        } catch (Exception $e) {}
    }

    if (!$pdo) {
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=citizen_verification;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO `community_ratings` (
            `feedback_ref`, `citizen_name`, `citizen_email`, `citizen_barangay`,
            `service_name`, `transaction_ref`, `overall_rating`, `quality_rating`,
            `staff_rating`, `comments`, `sentiment`, `status`, `created_at`
        ) VALUES (
            :feedback_ref, :citizen_name, :citizen_email, :citizen_barangay,
            :service_name, :transaction_ref, :overall_rating, :quality_rating,
            :staff_rating, :comments, :sentiment, 'Published', NOW()
        )
    ");

    $stmt->execute([
        ':feedback_ref' => $feedbackRef,
        ':citizen_name' => $citizenName ?: 'Anonymous Citizen',
        ':citizen_email' => $citizenEmail ?: null,
        ':citizen_barangay' => $citizenBarangay ?: 'Barangay Central',
        ':service_name' => $serviceName,
        ':transaction_ref' => $transactionRef ?: null,
        ':overall_rating' => $overallRating,
        ':quality_rating' => $qualityRating,
        ':staff_rating' => $staffRating,
        ':comments' => $comments ?: null,
        ':sentiment' => $sentiment,
    ]);

    // Mirror to civentral_certificates if available
    try {
        if (function_exists('getCertificateDbConnection')) {
            $certPdo = getCertificateDbConnection();
            $certStmt = $certPdo->prepare("
                REPLACE INTO `community_ratings` (
                    `feedback_ref`, `citizen_name`, `citizen_email`, `citizen_barangay`,
                    `service_name`, `transaction_ref`, `overall_rating`, `quality_rating`,
                    `staff_rating`, `comments`, `sentiment`, `status`, `created_at`
                ) VALUES (
                    :feedback_ref, :citizen_name, :citizen_email, :citizen_barangay,
                    :service_name, :transaction_ref, :overall_rating, :quality_rating,
                    :staff_rating, :comments, :sentiment, 'Published', NOW()
                )
            ");
            $certStmt->execute([
                ':feedback_ref' => $feedbackRef,
                ':citizen_name' => $citizenName ?: 'Anonymous Citizen',
                ':citizen_email' => $citizenEmail ?: null,
                ':citizen_barangay' => $citizenBarangay ?: 'Barangay Central',
                ':service_name' => $serviceName,
                ':transaction_ref' => $transactionRef ?: null,
                ':overall_rating' => $overallRating,
                ':quality_rating' => $qualityRating,
                ':staff_rating' => $staffRating,
                ':comments' => $comments ?: null,
                ':sentiment' => $sentiment,
            ]);
        }
    } catch (Exception $e) {}

    $submissionDate = date('M j, Y • g:i A');

    echo json_encode([
        'status' => 'success',
        'message' => 'Your community feedback has been recorded successfully.',
        'data' => [
            'referenceNumber' => $feedbackRef,
            'submissionDate' => $submissionDate,
            'serviceName' => $serviceName,
            'overallRating' => $overallRating,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save feedback: ' . $e->getMessage(),
    ]);
}
