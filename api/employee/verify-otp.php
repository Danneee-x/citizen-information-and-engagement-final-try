<?php
require_once __DIR__ . '/../../src/Session.php';
startSecureSession();

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
$otpCode = trim($input['otp'] ?? $input['otp_code'] ?? '');


if (empty($otpCode)) {
    respond([
        'status' => 'error',
        'message' => 'Please enter a valid 6-digit verification code.'
    ], 400);
}

$apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
$remoteUrl = rtrim($apiBaseUrl, '/') . '/verify-otp.php';

$result = proxyRequest($remoteUrl, 'POST', [
    'otp' => $otpCode
]);

if (isset($result['body']['status']) && $result['body']['status'] === 'success') {
    $user = $result['body']['user'] ?? [];
    $_SESSION['user_id'] = $user['user_id'] ?? null;
    $_SESSION['employee_id'] = $user['employee_id'] ?? null;
    $_SESSION['email'] = $user['email'] ?? ($user['employee_id'] ?? null);
    $_SESSION['first_name'] = $user['first_name'] ?? null;
    $_SESSION['last_name'] = $user['last_name'] ?? null;
    $_SESSION['role_id'] = $user['role_id'] ?? null;
    $_SESSION['role_name'] = $user['role_name'] ?? ($user['role'] ?? 'Administrator');
    $_SESSION['role_prefix'] = $user['role_prefix'] ?? 'ADM';
    $_SESSION['is_superadmin'] = !empty($user['is_superadmin']) || in_array(strtoupper($_SESSION['role_prefix']), ['SA', 'SADM', 'ADM', 'ADMIN']);
    $_SESSION['LAST_ACTIVITY'] = time();

    // Fetch full profile details from get-profile.php
    $profileUrl = rtrim($apiBaseUrl, '/') . '/get-profile.php';
    $profileResult = proxyRequest($profileUrl, 'GET', null);
    if (isset($profileResult['body']['status']) && $profileResult['body']['status'] === 'success' && !empty($profileResult['body']['data'])) {
        $pData = $profileResult['body']['data'];
        $_SESSION['current_user_details'] = $pData;
        if (!empty($pData['role_name'])) $_SESSION['role_name'] = $pData['role_name'];
        if (!empty($pData['role_prefix'])) $_SESSION['role_prefix'] = $pData['role_prefix'];
        if (isset($pData['is_superadmin'])) $_SESSION['is_superadmin'] = (bool)$pData['is_superadmin'];
    }

    // Pre-cache full admin modules into session so navigation never gets locked out
    $defaultModules = [
        'citizen registry', 'citizen', 'feedback and grievance', 'feedback', 'grievance',
        'barangay certificate & id issuance', 'barangay certificate', 'certificate', 'id issuance',
        'public consultation & survey', 'public consultation', 'survey',
        'notifications & alerts', 'notifications and alerts', 'alert',
        'user management', 'role & permissions', 'role and permissions', 'roles', 'permissions',
        'audit logs system', 'audit'
    ];
    $_SESSION['user_granted_resources'] = $defaultModules;
    persistAdminAuthCookie($_SESSION);
    $result['body']['user'] = $_SESSION;
}

respond($result['body'], $result['code']);
?>
