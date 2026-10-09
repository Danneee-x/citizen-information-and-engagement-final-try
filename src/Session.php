<?php
/**
 * Global Civentral Secure Session & Auth Persistence Engine
 * 
 * Guarantees:
 * 1. Dedicated session cookie name 'CIVENTRAL_ADMIN_SESS' to prevent collisions with civentral.tech / API.
 * 2. 7-day persistence with Lax/Host-only cookies.
 * 3. Dedicated persistent sessions directory (/var/www/html/sessions or system temp).
 * 4. HMAC-SHA256 signed backup auth cookie ('CIVENTRAL_ADMIN_AUTH') so sessions auto-heal if dropped.
 */

if (!function_exists('getAuthCookieSecret')) {
    function getAuthCookieSecret(): string {
        $key = getenv('APP_SECRET_KEY') ?: getenv('APP_KEY') ?: '';
        if (!empty($key)) {
            return $key;
        }
        return 'civentral_admin_super_secret_salt_2026_x89a01b';
    }
}

if (!function_exists('persistAdminAuthCookie')) {
    function persistAdminAuthCookie(array $userData): void {
        $secret = getAuthCookieSecret();
        $payload = [
            'user_id' => $userData['user_id'] ?? null,
            'employee_id' => $userData['employee_id'] ?? null,
            'email' => $userData['email'] ?? null,
            'first_name' => $userData['first_name'] ?? 'Admin',
            'last_name' => $userData['last_name'] ?? 'User',
            'role_id' => $userData['role_id'] ?? 1,
            'role_name' => $userData['role_name'] ?? 'Administrator',
            'role_prefix' => $userData['role_prefix'] ?? 'ADM',
            'is_superadmin' => !empty($userData['is_superadmin']),
            'exp' => time() + 604800 // 7 days
        ];

        $json = json_encode($payload);
        $sig = hash_hmac('sha256', $json, $secret);
        $token = base64_encode($json . '||' . $sig);

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        if (!headers_sent()) {
            setcookie('CIVENTRAL_ADMIN_AUTH', $token, [
                'expires' => time() + 604800,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE['CIVENTRAL_ADMIN_AUTH'] = $token;
    }
}

if (!function_exists('clearAdminAuthCookie')) {
    function clearAdminAuthCookie(): void {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        if (!headers_sent()) {
            setcookie('CIVENTRAL_ADMIN_AUTH', '', [
                'expires' => time() - 86400,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        unset($_COOKIE['CIVENTRAL_ADMIN_AUTH']);
    }
}

if (!function_exists('verifyAndRestoreSession')) {
    function verifyAndRestoreSession(): bool {
        // If session is already actively populated with an authenticated user, nothing to restore
        if (!empty($_SESSION['user_id']) || !empty($_SESSION['employee_id']) || !empty($_SESSION['current_user_details'])) {
            return true;
        }

        $token = $_COOKIE['CIVENTRAL_ADMIN_AUTH'] ?? null;
        if (empty($token)) {
            return false;
        }

        $secret = getAuthCookieSecret();
        $raw = base64_decode($token, true);
        if (!$raw || strpos($raw, '||') === false) {
            return false;
        }

        list($json, $sig) = explode('||', $raw, 2);
        $expectedSig = hash_hmac('sha256', $json, $secret);
        if (!hash_equals($expectedSig, $sig)) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['exp']) || $data['exp'] < time()) {
            return false;
        }

        // Restore active session state
        $_SESSION['user_id'] = $data['user_id'] ?? 1;
        $_SESSION['employee_id'] = $data['employee_id'] ?? 'ADMIN';
        $_SESSION['email'] = $data['email'] ?? 'admin@civentral.gov.ph';
        $_SESSION['first_name'] = $data['first_name'] ?? 'Admin';
        $_SESSION['last_name'] = $data['last_name'] ?? 'User';
        $_SESSION['role_id'] = $data['role_id'] ?? 1;
        $_SESSION['role_name'] = $data['role_name'] ?? 'Administrator';
        $_SESSION['role_prefix'] = $data['role_prefix'] ?? 'ADM';
        $_SESSION['is_superadmin'] = !empty($data['is_superadmin']) || in_array(strtoupper($data['role_prefix'] ?? ''), ['SA', 'SADM', 'ADM', 'ADMIN']);
        $_SESSION['LAST_ACTIVITY'] = time();

        // Restore granted resources cache
        $_SESSION['user_granted_resources'] = [
            'citizen registry', 'citizen', 'feedback and grievance', 'feedback', 'grievance',
            'barangay certificate & id issuance', 'barangay certificate', 'certificate', 'id issuance',
            'public consultation & survey', 'public consultation', 'survey',
            'notifications & alerts', 'notifications and alerts', 'alert',
            'user management', 'role & permissions', 'role and permissions', 'roles', 'permissions',
            'audit logs system', 'audit'
        ];

        return true;
    }
}

if (!function_exists('startSecureSession')) {
    function startSecureSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            verifyAndRestoreSession();
            return;
        }

        // Dedicated session save path if writable
        $sessDir = __DIR__ . '/../sessions';
        if (!is_dir($sessDir)) {
            @mkdir($sessDir, 0777, true);
        }
        if (is_dir($sessDir) && is_writable($sessDir)) {
            @ini_set('session.save_path', $sessDir);
        }

        $lifetime = 604800; // 7 days
        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.cookie_lifetime', (string)$lifetime);

        // Dedicated session cookie name for the admin portal
        session_name('CIVENTRAL_ADMIN_SESS');

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        verifyAndRestoreSession();
    }
}
