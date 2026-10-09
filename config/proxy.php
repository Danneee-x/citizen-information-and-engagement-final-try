<?php
if (function_exists('startSecureSession')) {
    startSecureSession();
} elseif (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

function proxyRequest($url, $method = 'POST', $body = null, $sendCookie = true) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    $headers = [
        'Content-Type: application/json'
    ];
    
    $remoteSessId = $_SESSION['remote_phpsessid'] ?? $_COOKIE['remote_phpsessid'] ?? $_COOKIE['PHPSESSID'] ?? null;
    if ($sendCookie && !empty($remoteSessId)) {
        $headers[] = 'Cookie: PHPSESSID=' . $remoteSessId;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'code' => 500,
            'body' => [
                'status' => 'error',
                'message' => 'Proxy request failed: ' . $error
            ]
        ];
    }
    
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $headerStr = substr($response, 0, $headerSize);
    $bodyStr = substr($response, $headerSize);
    
    // Only update remote session ID on successful requests (2xx) to prevent dropping auth on 401s/500s
    if ($httpCode >= 200 && $httpCode < 300) {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerStr, $matches);
        foreach ($matches[1] as $cookie) {
            $parts = explode('=', $cookie, 2);
            if (count($parts) === 2 && trim($parts[0]) === 'PHPSESSID') {
                $candidate = trim($parts[1]);
                if (!empty($candidate)) {
                    $_SESSION['remote_phpsessid'] = $candidate;
                }
            }
        }
    }
    
    return [
        'code' => $httpCode,
        'body' => json_decode($bodyStr, true) ?? $bodyStr
    ];
}
