<?php
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

date_default_timezone_set('Asia/Manila');
/**
 * Civentral Citizen API - Submit Community Ratings & Feedback
 * Endpoint: POST /api/citizen/submit-community-feedback.php
 */

error_reporting(0);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Locate DB config
require_once __DIR__ . '/../../config/database.php';

// Handle GET: retrieve community ratings summary or citizen ratings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDbConnection();
    try {
        $pdo->exec("ALTER TABLE `community_ratings` ADD COLUMN IF NOT EXISTS `attachment_url` VARCHAR(500) NULL;");
    } catch (Exception $e) {}
        $citizenEmail = trim($_GET['email'] ?? $_GET['citizen_email'] ?? '');

        if (!empty($citizenEmail)) {
            $stmt = $pdo->prepare("SELECT * FROM `community_ratings` WHERE `citizen_email` = :email ORDER BY `created_at` DESC LIMIT 20");
            $stmt->execute([':email' => $citizenEmail]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("SELECT * FROM `community_ratings` ORDER BY `created_at` DESC LIMIT 50");
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (ob_get_length()) ob_clean();
    echo json_encode([
            'status' => 'success',
            'count' => count($ratings),
            'data' => $ratings
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
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
    $pdo = getDbConnection();

    // Ensure table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `community_ratings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `feedback_ref` VARCHAR(50) UNIQUE NOT NULL,
            `citizen_name` VARCHAR(150) NOT NULL,
            `citizen_email` VARCHAR(150) NULL,
            `citizen_barangay` VARCHAR(100) NULL,
            `service_name` VARCHAR(150) NOT NULL,
            `transaction_ref` VARCHAR(100) NULL,
            `overall_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `quality_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `staff_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `comments` TEXT NULL,
            `sentiment` VARCHAR(30) NOT NULL DEFAULT 'Positive',
            `status` VARCHAR(30) NOT NULL DEFAULT 'Published',
            `attachment_url` VARCHAR(255) NULL,
            `admin_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $stmt = $pdo->prepare("
        INSERT INTO `community_ratings` (
            `feedback_ref`, `citizen_name`, `citizen_email`, `citizen_barangay`,
            `service_name`, `transaction_ref`, `overall_rating`, `quality_rating`,
            `staff_rating`, `comments`, `sentiment`, `attachment_url`, `status`, `created_at`
        ) VALUES (
            :feedback_ref, :citizen_name, :citizen_email, :citizen_barangay,
            :service_name, :transaction_ref, :overall_rating, :quality_rating,
            :staff_rating, :comments, :sentiment, :attachment_url, 'Published', NOW()
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
        ':attachment_url' => $attachmentUrl,
    ]);

    $submissionDate = date('M j, Y • g:i A');

    if (ob_get_length()) ob_clean();
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Your community feedback has been recorded successfully.',
        'data' => [
            'referenceNumber' => $feedbackRef,
            'submissionDate' => $submissionDate,
            'serviceName' => $serviceName,
            'overallRating' => $overallRating,
            'sentiment' => $sentiment
        ],
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save feedback: ' . $e->getMessage(),
    ]);
}
