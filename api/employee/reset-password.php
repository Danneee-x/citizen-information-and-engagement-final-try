<?php
// Prevent session lock issues during long DB queries
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 1. Dynamic CORS Configuration
$allowedOrigins = [
    'http://localhost',
    'http://localhost:80',
    'http://localhost:3000',
    'http://127.0.0.1',
    'http://127.0.0.1:80'
];

if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowedOrigins) || preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
    }
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight Handling
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/proxy.php';

// Response Helper
function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    respond([
        'status' => 'error',
        'message' => 'Method Not Allowed.'
    ], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$newPassword = trim($input['new_password'] ?? $input['password'] ?? '');
$confirmPassword = trim($input['confirm_password'] ?? $input['password_confirmation'] ?? $newPassword);

if (empty($newPassword)) {
    respond([
        'status' => 'error',
        'message' => 'Please provide your new password.'
    ], 422);
}

if (strlen($newPassword) < 8) {
    respond([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters in length.'
    ], 422);
}

if ($newPassword !== $confirmPassword) {
    respond([
        'status' => 'error',
        'message' => 'New password and confirmation do not match.'
    ], 422);
}

$apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
$remoteUrl = rtrim($apiBaseUrl, '/') . '/reset-password.php';

$payload = [
    'new_password' => $newPassword,
    'password' => $newPassword,
    'confirm_password' => $confirmPassword,
    'password_confirmation' => $confirmPassword
];

if (!empty($_SESSION['reset_identifier'])) {
    $payload['identifier'] = $_SESSION['reset_identifier'];
    $payload['email'] = $_SESSION['reset_identifier'];
}

$result = proxyRequest($remoteUrl, 'POST', $payload);

// If successful, clear reset session data
if (isset($result['body']['status']) && $result['body']['status'] === 'success' || !empty($result['body']['success'])) {
    unset($_SESSION['reset_identifier']);
    unset($_SESSION['remote_phpsessid']);
}

respond($result['body'], $result['code']);
