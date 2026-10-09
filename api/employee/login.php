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

$employeeIdOrEmail = trim($input['employeeId'] ?? $input['email'] ?? $input['username'] ?? '');
$password = trim($input['password'] ?? '');
$recaptchaToken = trim($input['g-recaptcha-response'] ?? $input['recaptchaResponse'] ?? '');
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '';

if (empty($employeeIdOrEmail) || empty($password)) {
    respond([
        'status' => 'error',
        'message' => 'Please provide both Employee ID / Email and Password.'
    ], 400);
}

if (empty($recaptchaToken)) {
    respond([
        'status' => 'error',
        'message' => 'reCAPTCHA verification is required. Please check the robot checkbox.'
    ], 400);
}

// Verify token with Google API if secret key is present
if (!empty($recaptchaSecret)) {
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $postData = http_build_query([
        'secret'   => $recaptchaSecret,
        'response' => $recaptchaToken,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $verifyOptions = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                         "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData,
            'timeout' => 5
        ]
    ];

    $context = stream_context_create($verifyOptions);
    $verifyResponse = @file_get_contents($verifyUrl, false, $context);

    if ($verifyResponse !== false) {
        $responseData = json_decode($verifyResponse, true);
        if (empty($responseData['success'])) {
            respond([
                'status' => 'error',
                'message' => 'reCAPTCHA verification failed. Please try again.'
            ], 400);
        }
    }
}

// System Maintenance Check
if (strtolower($employeeIdOrEmail) === 'maintenance') {
    respond([
        'status' => 'maintenance',
        'message' => 'System maintenance is scheduled for Sunday, 11:00 PM–1:00 AM. Save drafts before then.'
    ], 503);
}

$apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
$remoteUrl = rtrim($apiBaseUrl, '/') . '/login.php';

$result = proxyRequest($remoteUrl, 'POST', [
    'employeeId' => $employeeIdOrEmail,
    'password' => $password,
    'g-recaptcha-response' => $recaptchaToken,
    'recaptchaResponse' => $recaptchaToken
]);

if (isset($result['body']['status']) && $result['body']['status'] === 'success') {
    $user = $result['body']['user'] ?? $result['body']['data'] ?? [];
    if (empty($user)) {
        $user = [
            'employee_id' => $employeeIdOrEmail,
            'email' => $employeeIdOrEmail,
            'role_name' => 'Administrator',
            'role_prefix' => 'ADM',
            'is_superadmin' => true
        ];
    }
    $_SESSION['user_id'] = $user['user_id'] ?? 1;
    $_SESSION['employee_id'] = $user['employee_id'] ?? $employeeIdOrEmail;
    $_SESSION['email'] = $user['email'] ?? $employeeIdOrEmail;
    $_SESSION['first_name'] = $user['first_name'] ?? 'Admin';
    $_SESSION['last_name'] = $user['last_name'] ?? 'User';
    $_SESSION['role_id'] = $user['role_id'] ?? 1;
    $_SESSION['role_name'] = $user['role_name'] ?? ($user['role'] ?? 'Administrator');
    $_SESSION['role_prefix'] = $user['role_prefix'] ?? 'ADM';
    $_SESSION['is_superadmin'] = !empty($user['is_superadmin']) || in_array(strtoupper($_SESSION['role_prefix']), ['SA', 'SADM', 'ADM', 'ADMIN']);
    $_SESSION['LAST_ACTIVITY'] = time();

    $_SESSION['user_granted_resources'] = [
        'citizen registry', 'citizen', 'feedback and grievance', 'feedback', 'grievance',
        'barangay certificate & id issuance', 'barangay certificate', 'certificate', 'id issuance',
        'public consultation & survey', 'public consultation', 'survey',
        'notifications & alerts', 'notifications and alerts', 'alert',
        'user management', 'role & permissions', 'role and permissions', 'roles', 'permissions',
        'audit logs system', 'audit'
    ];

    persistAdminAuthCookie($_SESSION);
    $result['body']['user'] = $_SESSION;
}

respond($result['body'], $result['code']);
?>
