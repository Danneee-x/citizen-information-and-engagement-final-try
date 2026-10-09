<?php
require_once __DIR__ . '/../src/Session.php';
startSecureSession();

// Record logout time via API instead of local database
if (isset($_SESSION['login_id']) || isset($_SESSION['session_id'])) {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    require_once __DIR__ . '/../config/proxy.php';
    $apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
    $loginHistoryUrl = rtrim($apiBaseUrl, '/') . '/login-history.php';

    try {
        // Update logout time in login history via API
        if (isset($_SESSION['login_id'])) {
            proxyRequest($loginHistoryUrl, 'PATCH', [
                'login_id'    => $_SESSION['login_id'],
                'logout_time' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $e) {
        // Ignore error and proceed to logout
    }
}

// Clear persistent auth cookie
clearAdminAuthCookie();

// Clear and destroy server session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    // Also clear default PHPSESSID if any
    setcookie('PHPSESSID', '', time() - 42000, '/', '', false, true);
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out | Civentral</title>
    <script>
        try {
            localStorage.removeItem('civentral_user_session');
        } catch(e) {}
        window.location.replace('../login.php');
    </script>
</head>
<body style="font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; color: #475569;">
    <p>Signing out safely, redirecting to login...</p>
</body>
</html>
