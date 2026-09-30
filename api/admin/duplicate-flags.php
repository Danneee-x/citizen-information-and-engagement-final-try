<?php
/**
 * Duplicate Flags API — CIVentral Admin
 * Handles: GET (fetch flags), POST (scan/merge/dismiss)
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

function ensureFlagTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `duplicate_flags` (
        `flag_id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `flag_code`         VARCHAR(30) NOT NULL UNIQUE,
        `verification_id_a` INT UNSIGNED NOT NULL,
        `verification_id_b` INT UNSIGNED NOT NULL,
        `matching_criteria` VARCHAR(150) NOT NULL,
        `match_confidence`  TINYINT UNSIGNED NOT NULL DEFAULT 95,
        `status`            ENUM('Pending','Merged','Dismissed') NOT NULL DEFAULT 'Pending',
        `resolution_notes`  TEXT NULL DEFAULT NULL,
        `master_record_id`  INT UNSIGNED NULL DEFAULT NULL,
        `resolved_by`       VARCHAR(150) NULL DEFAULT NULL,
        `resolved_at`       DATETIME NULL DEFAULT NULL,
        `detected_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_df_status`  (`status`),
        INDEX `idx_df_vid_a`   (`verification_id_a`),
        INDEX `idx_df_vid_b`   (`verification_id_b`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function scanAndStoreDuplicates(PDO $pdo): int {
    ensureFlagTable($pdo);
    $newCount = 0;

    $dupQuery = $pdo->query("
        SELECT v1.verification_id as vid_a, v2.verification_id as vid_b,
               v1.valid_id_number as id_a, v2.valid_id_number as id_b,
               v1.first_name as fn_a, v1.last_name as ln_a, v1.birth_date as dob_a
        FROM citizen_verifications v1
        JOIN citizen_verifications v2
          ON v1.verification_id < v2.verification_id
         AND (v1.valid_id_number = v2.valid_id_number
              OR (v1.first_name = v2.first_name AND v1.last_name = v2.last_name AND v1.birth_date = v2.birth_date))
        LIMIT 50
    ");
    $rows = $dupQuery->fetchAll(PDO::FETCH_ASSOC);

    $year = date('Y');
    foreach ($rows as $idx => $row) {
        $codeBase = 'DUP-' . $year . '-' . str_pad($row['vid_a'] . $row['vid_b'], 6, '0', STR_PAD_LEFT);
        $isIdMatch = ($row['id_a'] === $row['id_b']);
        $criteria  = $isIdMatch ? 'Same Government ID Number' : 'Same Name + Birthdate Match';
        $conf      = $isIdMatch ? 100 : 95;

        // Only insert if this pair hasn't been flagged yet
        $check = $pdo->prepare("SELECT flag_id FROM duplicate_flags WHERE verification_id_a = ? AND verification_id_b = ?");
        $check->execute([$row['vid_a'], $row['vid_b']]);
        if ($check->fetch()) continue; // already tracked

        $insert = $pdo->prepare("
            INSERT INTO duplicate_flags
                (flag_code, verification_id_a, verification_id_b, matching_criteria, match_confidence)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        // generate unique flag_code
        $flagCode = 'DUP-' . $year . '-' . str_pad($pdo->query("SELECT COUNT(*)+1 FROM duplicate_flags")->fetchColumn(), 4, '0', STR_PAD_LEFT);
        $insert->execute([$flagCode, $row['vid_a'], $row['vid_b'], $criteria, $conf]);
        $newCount++;
    }
    return $newCount;
}

try {
    $pdo = getDbConnection();
    ensureFlagTable($pdo);

    // ── GET: Fetch current flags + KPI counts ──────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pending   = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Pending'")->fetchColumn();
        $merged    = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Merged'")->fetchColumn();
        $dismissed = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Dismissed'")->fetchColumn();

        $flags = $pdo->query("
            SELECT f.*,
                   v1.first_name as fn_a, v1.last_name as ln_a, v1.birth_date as dob_a,
                   v1.street_address as addr_a, v1.barangay as brgy_a,
                   v1.valid_id_number as idnum_a, v1.civil_status as cs_a, v1.submitted_at as sub_a,
                   v2.first_name as fn_b, v2.last_name as ln_b, v2.birth_date as dob_b,
                   v2.street_address as addr_b, v2.barangay as brgy_b,
                   v2.valid_id_number as idnum_b, v2.civil_status as cs_b, v2.submitted_at as sub_b
            FROM duplicate_flags f
            LEFT JOIN citizen_verifications v1 ON f.verification_id_a = v1.verification_id
            LEFT JOIN citizen_verifications v2 ON f.verification_id_b = v2.verification_id
            ORDER BY f.status = 'Pending' DESC, f.detected_at DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'counts' => ['pending' => $pending, 'merged' => $merged, 'dismissed' => $dismissed],
            'flags'  => $flags
        ]);
        exit;
    }

    // ── POST: Actions ──────────────────────────────────────────────────────
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = trim($body['action'] ?? '');

    if ($action === 'scan') {
        $newlyFound = scanAndStoreDuplicates($pdo);
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Pending'")->fetchColumn();
        echo json_encode(['status' => 'success', 'new_found' => $newlyFound, 'pending' => $pending,
                          'message' => "Scan complete. {$newlyFound} new duplicate pair(s) flagged."]);
        exit;
    }

    if ($action === 'merge') {
        $flagCode       = trim($body['flag_code'] ?? '');
        $masterRecordId = (int)($body['master_record_id'] ?? 0);
        $resolvedBy     = trim($body['resolved_by'] ?? 'Admin');

        if (!$flagCode || !$masterRecordId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'flag_code and master_record_id are required.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE duplicate_flags
            SET status='Merged', master_record_id=?, resolved_by=?, resolved_at=NOW()
            WHERE flag_code=? AND status='Pending'
        ");
        $stmt->execute([$masterRecordId, $resolvedBy, $flagCode]);

        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Flag not found or already resolved.']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => "Records merged. Master: CTZ-" . str_pad($masterRecordId, 4, '0', STR_PAD_LEFT)]);
        exit;
    }

    if ($action === 'dismiss') {
        $flagCode  = trim($body['flag_code'] ?? '');
        $reason    = trim($body['reason'] ?? '');
        $resolvedBy= trim($body['resolved_by'] ?? 'Admin');

        if (!$flagCode || !$reason) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'flag_code and reason are required.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE duplicate_flags
            SET status='Dismissed', resolution_notes=?, resolved_by=?, resolved_at=NOW()
            WHERE flag_code=? AND status='Pending'
        ");
        $stmt->execute([$reason, $resolvedBy, $flagCode]);

        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Flag not found or already resolved.']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => "Flag {$flagCode} dismissed. Reason logged."]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Unknown action: {$action}"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
