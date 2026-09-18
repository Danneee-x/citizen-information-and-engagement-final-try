<?php
/**
 * CIVentral Citizen Information & Engagement Subsystem API Gateway
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode([
    "subsystem"   => "CIVentral Citizen Information & Engagement API Gateway",
    "status"      => "online",
    "version"     => "1.1.0",
    "endpoints"   => [
        "Mobile App (Citizen Verification)" => [
            "POST /api/citizen/verify-citizen.php"      => "Submits citizen verification details, ID, and photos",
            "GET  /api/citizen/verification-status.php" => "Checks if citizen is pending, approved, or rejected"
        ],
        "Mobile App (Report a Concern & Grievances)" => [
            "POST /api/citizen/submit-concern.php"      => "Submits citizen concern details, location, and photos",
            "GET  /api/citizen/submit-concern.php"       => "Health check and recent filed concern tickets"
        ],
        "Web Admin Side (Review & Verification)" => [
            "GET  /api/admin/verifications.php"   => "Lists all verification submissions for Admin review",
            "POST /api/admin/review-citizen.php"  => "Approves or Rejects a verification submission",
            "GET  /api/admin/dashboard-stats.php" => "Dashboard counters for verified and pending citizens"
        ],
        "Web Admin Side (Grievance & Ticket Routing)" => [
            "GET  /api/admin/concerns.php"        => "Lists grievance tickets with filters and aggregated KPIs",
            "POST /api/admin/concerns.php"        => "Updates ticket status (In Progress, Resolved, Closed) and department routing"
        ],
        "Inter-Subsystem Shared API (For Other Groups)" => [
            "GET  /api/external/check-verification.php" => "Allows other CIVentral subsystems to verify a citizen by ID"
        ]
    ],
    "timestamp"   => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);