<?php
/**
 * Endpoint: GET /api/admin/verifications.php
 * Lists citizen verification submissions for the Admin Dashboard / Side Panel.
 * Query Params: ?status=Pending (default), Approved, Rejected, Returned_For_Correction, or all
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

$status = trim($_GET['status'] ?? 'Pending');
$limit  = intval($_GET['limit'] ?? 100);
$offset = intval($_GET['offset'] ?? 0);

$pdo = getDbConnection();

try {
    if ($status === 'all' || empty($status)) {
        $stmt = $pdo->prepare("SELECT v.*, u.email as user_email, u.mobile_number as user_mobile 
                               FROM citizen_verifications v 
                               LEFT JOIN citizen_users u ON v.citizen_user_id = u.citizen_user_id 
                               ORDER BY v.submitted_at DESC 
                               LIMIT :lim OFFSET :off");
    } else {
        $stmt = $pdo->prepare("SELECT v.*, u.email as user_email, u.mobile_number as user_mobile 
                               FROM citizen_verifications v 
                               LEFT JOIN citizen_users u ON v.citizen_user_id = u.citizen_user_id 
                               WHERE v.verification_status = :status 
                               ORDER BY v.submitted_at DESC 
                               LIMIT :lim OFFSET :off");
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    }

    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count totals
    $countStmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN verification_status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN verification_status = 'Under_Review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN verification_status = 'Returned_For_Correction' THEN 1 ELSE 0 END) as returned,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN verification_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM citizen_verifications");
    $stats = $countStmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "counts" => [
            "total"        => (int)($stats['total'] ?? 0),
            "pending"      => (int)($stats['pending'] ?? 0),
            "under_review" => (int)($stats['under_review'] ?? 0),
            "returned"     => (int)($stats['returned'] ?? 0),
            "approved"     => (int)($stats['approved'] ?? 0),
            "rejected"     => (int)($stats['rejected'] ?? 0)
        ],
        "count"  => count($results),
        "data"   => $results
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
