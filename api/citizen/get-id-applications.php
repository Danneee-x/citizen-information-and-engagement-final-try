<?php
// Suppress warnings / notices from polluting JSON API output
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

// Proxy endpoint for get-id-applications.php
require_once __DIR__ . '/submit-id-application.php';
