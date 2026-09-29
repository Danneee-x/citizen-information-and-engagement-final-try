<?php
/**
 * Civentral Citizen API - Get Real Notifications & Alerts from Database
 * Endpoint: GET/POST /api/citizen/get-notifications.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Locate database configuration
$dbConfigPath = __DIR__ . '/../../config/database.php';
if (!file_exists($dbConfigPath)) {
    $dbConfigPath = 'C:/xampp/htdocs/citizen-information-and-engagement-final-try/config/database.php';
}

if (file_exists($dbConfigPath)) {
    require_once $dbConfigPath;
}

// Function to format relative timestamp
function formatRelativeTime($datetimeStr) {
    if (empty($datetimeStr)) return 'Just now';
    $timestamp = strtotime($datetimeStr);
    if (!$timestamp) return 'Just now';
    
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = (int)($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hrs = (int)($diff / 3600);
        return $hrs . ' hour' . ($hrs > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = (int)($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Function to normalize category to mobile app badges
function normalizeCategory($cat) {
    $c = strtolower(trim($cat));
    if (strpos($c, 'emergency') !== false) {
        return 'Emergency Alert';
    }
    if (strpos($c, 'domain') !== false || strpos($c, 'permit') !== false || strpos($c, 'status') !== false || strpos($c, 'clearance') !== false) {
        return 'Domain Update';
    }
    if (strpos($c, 'health') !== false || strpos($c, 'medical') !== false) {
        return 'Health Advisory';
    }
    if (strpos($c, 'curfew') !== false || strpos($c, 'ordinance') !== false) {
        return 'Curfew / Ordinance';
    }
    if (strpos($c, 'event') !== false) {
        return 'Event Advisory';
    }
    return 'Broadcast';
}

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

    $stmt = $pdo->query("
        SELECT 
            `id`, `alert_id`, `title`, `body`, `category`, `priority`,
            `channels`, `target_audience`, `target_barangay`,
            `sender_name`, `sender_role`, `status`,
            `recipients_count`, `attachment_url`, `created_at`
        FROM `broadcast_alerts`
        WHERE `status` IN ('Delivered', 'Sent')
        ORDER BY `created_at` DESC
        LIMIT 50
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $alerts = [];
    foreach ($rows as $r) {
        // Strip HTML tags for preview body if formatted, keeping clean message
        $plainBody = trim(strip_tags($r['body']));
        if (empty($plainBody)) {
            $plainBody = $r['body'];
        }

        $alerts[] = [
            'id' => $r['alert_id'] ?: 'ALT-' . $r['id'],
            'numericId' => (int)$r['id'],
            'title' => $r['title'],
            'body' => $plainBody,
            'bodyHtml' => $r['body'],
            'category' => normalizeCategory($r['category']),
            'rawCategory' => $r['category'],
            'priority' => $r['priority'] ?: 'Normal',
            'timestamp' => formatRelativeTime($r['created_at']),
            'createdAt' => $r['created_at'],
            'sender' => $r['sender_name'] ?: 'Caloocan Public Information Office',
            'attachmentUrl' => $r['attachment_url'],
            'isRead' => false,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $alerts,
        'count' => count($alerts),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch notifications: ' . $e->getMessage(),
        'data' => [],
    ]);
}
