<?php
/**
 * Civentral Citizen API - Submit Community Ratings & Feedback
 * Endpoint: POST & GET /api/citizen/submit-community-feedback.php
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
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Helper to save base64 uploaded feedback attachments
if (!function_exists('saveFeedbackAttachment')) {
    function saveFeedbackAttachment($dataUrl, $prefix = 'feedback') {
        if (empty($dataUrl)) return null;
        $dataUrl = trim($dataUrl);

        $baseUrl = rtrim(getenv('API_BASE_URL') ?: (getenv('APP_URL') ?: 'https://api-citizen.civentral.tech'), '/');

        if (strpos($dataUrl, 'https://citizenship.civentral.tech') !== false) {
            $dataUrl = str_replace('https://citizenship.civentral.tech', $baseUrl, $dataUrl);
        }

        if (strpos($dataUrl, 'http://') === 0 || strpos($dataUrl, 'https://') === 0) {
            return $dataUrl;
        }

        if (strpos($dataUrl, 'assets/') === 0 || strpos($dataUrl, 'uploads/') === 0) {
            $clean = ltrim($dataUrl, '/');
            return $baseUrl . '/' . $clean;
        }

        $ext = 'jpg';
        $data = null;

        if (preg_match('/^data:(image\/(\w+)|application\/pdf);base64,/i', $dataUrl, $type)) {
            $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
            if (isset($type[2])) {
                $ext = strtolower($type[2]);
                if ($ext === 'jpeg') $ext = 'jpg';
                elseif ($ext === 'svg+xml') $ext = 'svg';
            } else {
                $ext = 'pdf';
            }
        } elseif (strlen($dataUrl) > 50) {
            $cleanedStr = preg_replace('/\s+/', '', $dataUrl);
            if (preg_match('/^[a-zA-Z0-9\/+=]+$/', $cleanedStr)) {
                $data = $cleanedStr;
            }
        }

        if ($data !== null) {
            $decoded = base64_decode(trim($data));
            if ($decoded !== false && strlen($decoded) > 0) {
                if ($ext === 'jpg') {
                    if (substr($decoded, 0, 4) === "%PDF") {
                        $ext = 'pdf';
                    } elseif (substr($decoded, 0, 8) === "\x89PNG\r\n\x1a\n") {
                        $ext = 'png';
                    } elseif (substr($decoded, 0, 4) === "RIFF" && substr($decoded, 8, 4) === "WEBP") {
                        $ext = 'webp';
                    } elseif (substr($decoded, 0, 3) === "GIF") {
                        $ext = 'gif';
                    }
                }

                $filename = $prefix . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;

                $targetDirs = [
                    '/var/www/html/uploads/feedback/',
                    '/var/www/html/assets/uploads/feedback/',
                    __DIR__ . '/../../uploads/feedback/',
                    __DIR__ . '/../../assets/uploads/feedback/',
                    'C:/xampp/htdocs/citizen-information-and-engagement-final-try/uploads/feedback/',
                    'C:/xampp/htdocs/citizen-information-and-engagement-final-try/assets/uploads/feedback/'
                ];

                $savedDir = 'uploads/feedback/';
                foreach ($targetDirs as $dir) {
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                        @chmod($dir, 0775);
                    }
                    if (is_dir($dir)) {
                        $writeRes = @file_put_contents($dir . $filename, $decoded);
                        if ($writeRes !== false && file_exists($dir . $filename)) {
                            $savedDir = (strpos($dir, 'assets/uploads') !== false) ? 'assets/uploads/feedback/' : 'uploads/feedback/';
                        }
                    }
                }

                return $baseUrl . '/' . $savedDir . $filename;
            }
        }

        return null;
    }
}

// Function to guarantee table schema exists & auto-heal missing columns
function ensureCommunityRatingsSchema(PDO $pdo) {
    // 1. Ensure base table exists
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
            `attachment_url` VARCHAR(500) NULL,
            `admin_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Safely add any columns that may be missing on older database schemas
    $columnsToAdd = [
        'attachment_url'   => 'VARCHAR(500) NULL',
        'citizen_barangay' => 'VARCHAR(100) NULL',
        'transaction_ref'  => 'VARCHAR(100) NULL',
        'quality_rating'   => 'TINYINT UNSIGNED NOT NULL DEFAULT 5',
        'staff_rating'     => 'TINYINT UNSIGNED NOT NULL DEFAULT 5',
        'sentiment'        => "VARCHAR(30) NOT NULL DEFAULT 'Positive'",
        'status'           => "VARCHAR(30) NOT NULL DEFAULT 'Published'",
        'admin_notes'      => 'TEXT NULL'
    ];

    foreach ($columnsToAdd as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE `community_ratings` ADD COLUMN `{$col}` {$definition};");
        } catch (Exception $e) {
            // Column already exists, safe to ignore
        }
    }
}

// Handle GET: retrieve community ratings summary or citizen ratings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDbConnection();
        ensureCommunityRatingsSchema($pdo);

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

// Handle POST: Submit Feedback
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
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
$qualityRating = max(1, min(5, (int)($input['quality_rating'] ?? $input['qualityRating'] ?? $overallRating)));
$staffRating = max(1, min(5, (int)($input['staff_rating'] ?? $input['staffRating'] ?? $overallRating)));
$comments = trim($input['comments'] ?? $input['feedbackComment'] ?? '');
$transactionRef = trim($input['transaction_ref'] ?? $input['referenceNumber'] ?? $input['ticket_number'] ?? '');
$citizenName = trim($input['citizen_name'] ?? $input['citizenName'] ?? 'Anonymous Citizen');
$citizenEmail = trim($input['citizen_email'] ?? $input['citizenEmail'] ?? '');
$citizenBarangay = trim($input['citizen_barangay'] ?? $input['citizenBarangay'] ?? 'Barangay Central');

// Process attachment if provided
$attachmentData = $input['attachment']['data'] ?? ($input['attachment_url'] ?? ($input['attachmentData'] ?? null));
$attachmentUrl = null;
if (!empty($attachmentData)) {
    $attachmentUrl = saveFeedbackAttachment($attachmentData, 'fdb_proof');
}

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
    ensureCommunityRatingsSchema($pdo);

    // Check if attachment_url exists in table
    $hasAttachmentCol = false;
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM `community_ratings` LIKE 'attachment_url'");
        $hasAttachmentCol = ($colCheck && $colCheck->rowCount() > 0);
    } catch (Exception $e) {}

    if ($hasAttachmentCol) {
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
    } else {
        // Fallback insert without attachment_url
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
    }

    $submissionDate = date('M j, Y • g:i A');

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Your community feedback has been recorded successfully.',
        'data' => [
            'referenceNumber' => $feedbackRef,
            'submissionDate' => $submissionDate,
            'serviceName' => $serviceName,
            'overallRating' => $overallRating,
            'qualityRating' => $qualityRating,
            'staffRating' => $staffRating,
            'sentiment' => $sentiment,
            'attachmentUrl' => $attachmentUrl
        ],
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save feedback: ' . $e->getMessage(),
    ]);
}
