<?php
/**
 * Civentral - Delete Broadcast Alert API
 * Endpoint: POST /api/admin/delete-alert.php
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

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Support JSON or Form Data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$alertId = trim($input['alert_id'] ?? $input['alertId'] ?? '');

if ($id <= 0 && empty($alertId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Alert ID is required to delete.']);
    exit;
}

try {
    // Check if alert exists and get attachment if any
    $selectStmt = $pdo->prepare("SELECT id, alert_id, attachment_url FROM `broadcast_alerts` WHERE id = :id OR alert_id = :alert_id LIMIT 1");
    $selectStmt->execute([
        ':id' => $id,
        ':alert_id' => $alertId
    ]);
    $alert = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$alert) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Broadcast alert record not found.']);
        exit;
    }

    $actualId = (int)$alert['id'];
    $actualAlertId = $alert['alert_id'];

    // Delete record from primary database
    $delStmt = $pdo->prepare("DELETE FROM `broadcast_alerts` WHERE id = :id OR alert_id = :alert_id");
    $delStmt->execute([
        ':id' => $actualId,
        ':alert_id' => $actualAlertId
    ]);

    // Delete from certificate mirror database if present
    try {
        $certPdo = getCertificateDbConnection();
        $certDel = $certPdo->prepare("DELETE FROM `broadcast_alerts` WHERE id = :id OR alert_id = :alert_id");
        $certDel->execute([
            ':id' => $actualId,
            ':alert_id' => $actualAlertId
        ]);
    } catch (Exception $e) {
        // Ignore mirror deletion errors
    }

    // Optional: remove attachment file if stored locally
    if (!empty($alert['attachment_url'])) {
        $filePath = __DIR__ . '/../../' . ltrim($alert['attachment_url'], '/');
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Broadcast alert deleted successfully.',
        'data' => [
            'id' => $actualId,
            'alert_id' => $actualAlertId
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete alert: ' . $e->getMessage()]);
}
