<?php
/**
 * Civentral Admin API - Bulk Citizen Actions
 * Endpoint: POST /api/admin/bulk-citizens.php
 * Handles: mark_validation, change_status, assign_household, archive
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

$action = trim($input['action'] ?? '');
$citizenIds = $input['citizen_ids'] ?? ($input['ids'] ?? []);

if (empty($citizenIds) || !is_array($citizenIds)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No citizen IDs provided.']);
    exit;
}

try {
    $pdo = getDbConnection();

    // Ensure helper columns exist in citizen_verifications
    $checkCols = $pdo->query("SHOW COLUMNS FROM citizen_verifications")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('household_id', $checkCols)) {
        $pdo->exec("ALTER TABLE citizen_verifications ADD COLUMN `household_id` VARCHAR(50) NULL");
    }
    if (!in_array('citizen_status', $checkCols)) {
        $pdo->exec("ALTER TABLE citizen_verifications ADD COLUMN `citizen_status` VARCHAR(50) DEFAULT 'Active'");
    }
    if (!in_array('is_archived', $checkCols)) {
        $pdo->exec("ALTER TABLE citizen_verifications ADD COLUMN `is_archived` TINYINT(1) DEFAULT 0");
    }

    $affected = 0;

    switch ($action) {
        case 'mark_validation':
            $stmt = $pdo->prepare("
                UPDATE citizen_verifications 
                SET citizen_status = 'Pending Validation',
                    admin_action_notes = CONCAT(COALESCE(admin_action_notes, ''), '\n[', NOW(), '] Marked for re-validation by staff.')
                WHERE citizen_id_number = :cid OR verification_id = :vid
            ");
            foreach ($citizenIds as $id) {
                $vid = is_numeric($id) ? (int)$id : 0;
                $stmt->execute([':cid' => (string)$id, ':vid' => $vid]);
                $affected += $stmt->rowCount();
            }
            $msg = count($citizenIds) . " citizen(s) marked for validation.";
            break;

        case 'change_status':
            $newStatus = trim($input['status'] ?? 'Active');
            $validStatuses = ['Active', 'Inactive', 'Senior Citizen', 'Pending Validation', 'Deceased', 'Transferred Out'];
            if (!in_array($newStatus, $validStatuses)) {
                $newStatus = 'Active';
            }

            $stmt = $pdo->prepare("
                UPDATE citizen_verifications 
                SET citizen_status = :status,
                    admin_action_notes = CONCAT(COALESCE(admin_action_notes, ''), '\n[', NOW(), '] Status changed to ', :status_note, ' by staff.')
                WHERE citizen_id_number = :cid OR verification_id = :vid
            ");
            foreach ($citizenIds as $id) {
                $vid = is_numeric($id) ? (int)$id : 0;
                $stmt->execute([':status' => $newStatus, ':status_note' => $newStatus, ':cid' => (string)$id, ':vid' => $vid]);
                $affected += $stmt->rowCount();
            }
            $msg = "Status changed to '{$newStatus}' for " . count($citizenIds) . " citizen(s).";
            break;

        case 'assign_household':
            $householdId = trim($input['household_id'] ?? '');
            if (empty($householdId)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Household ID is required.']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE citizen_verifications 
                SET household_id = :hh,
                    admin_action_notes = CONCAT(COALESCE(admin_action_notes, ''), '\n[', NOW(), '] Linked to household ', :hh_note, ' by staff.')
                WHERE citizen_id_number = :cid OR verification_id = :vid
            ");
            foreach ($citizenIds as $id) {
                $vid = is_numeric($id) ? (int)$id : 0;
                $stmt->execute([':hh' => $householdId, ':hh_note' => $householdId, ':cid' => (string)$id, ':vid' => $vid]);
                $affected += $stmt->rowCount();
            }
            $msg = count($citizenIds) . " citizen(s) linked to household {$householdId}.";
            break;

        case 'archive':
            $reason = trim($input['reason'] ?? 'Archived by administrator.');
            $stmt = $pdo->prepare("
                UPDATE citizen_verifications 
                SET is_archived = 1,
                    citizen_status = 'Archived',
                    admin_action_notes = CONCAT(COALESCE(admin_action_notes, ''), '\n[', NOW(), '] Archived: ', :reason)
                WHERE citizen_id_number = :cid OR verification_id = :vid
            ");
            foreach ($citizenIds as $id) {
                $vid = is_numeric($id) ? (int)$id : 0;
                $stmt->execute([':reason' => $reason, ':cid' => (string)$id, ':vid' => $vid]);
                $affected += $stmt->rowCount();
            }
            $msg = count($citizenIds) . " citizen record(s) archived successfully.";
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Unknown action '{$action}'."]);
            exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'action' => $action,
        'affected_count' => $affected,
        'total_selected' => count($citizenIds)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
