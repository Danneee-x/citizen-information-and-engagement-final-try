<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getCertificateDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failure: ' . $e->getMessage()]);
    exit;
}

// 1. GET: Fetch list of requests or metrics
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

        $where = [];
        $params = [];

        if ($status) {
            $where[] = "`status` = :status";
            $params[':status'] = $status;
        }
        if ($type) {
            $where[] = "`certificate_type` LIKE :type";
            $params[':type'] = "%{$type}%";
        }
        if ($search) {
            $s = "%{$search}%";
            $where[] = "(`reference_no` LIKE :s1 OR `citizen_name` LIKE :s2 OR `purpose` LIKE :s3 OR `barangay` LIKE :s4)";
            $params[':s1'] = $s;
            $params[':s2'] = $s;
            $params[':s3'] = $s;
            $params[':s4'] = $s;
        }

        $sql = "SELECT * FROM `certificate_requests`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY `request_id` DESC LIMIT {$limit}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Metrics
        $total = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests`")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Pending', 'Under Review')")->fetchColumn();
        $ready = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` = 'Ready for Release'")->fetchColumn();
        $releasedToday = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` = 'Released' AND DATE(`released_at`) = CURDATE()")->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'kpis' => [
                'total_requests' => $total,
                'pending_processing' => $pending,
                'ready_for_release' => $ready,
                'released_today' => $releasedToday
            ],
            'count' => count($rows),
            'requests' => $rows
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 2. POST: Execute Action (Approve, Reject, Release, Reprint)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $data = !empty($json) ? $json : $_POST;

        $action = $data['action'] ?? '';
        $refNo = $data['reference_no'] ?? ($data['id'] ?? null);
        $staffName = $data['staff_name'] ?? 'Liza Dy (Civil Registry Staff)';

        if (empty($refNo)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Reference number is required.']);
            exit;
        }

        // Fetch target request
        $findStmt = $pdo->prepare("SELECT * FROM `certificate_requests` WHERE `reference_no` = :ref LIMIT 1");
        $findStmt->execute([':ref' => $refNo]);
        $target = $findStmt->fetch();

        if (!$target) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => "Certificate request {$refNo} not found."]);
            exit;
        }

        if ($action === 'approve') {
            $updateSql = "UPDATE `certificate_requests` SET 
                `status` = 'Ready for Release',
                `approved_by` = :staff,
                `approved_at` = NOW(),
                `verification_notes` = :notes
                WHERE `reference_no` = :ref";
            $uStmt = $pdo->prepare($updateSql);
            $uStmt->execute([
                ':staff' => $staffName,
                ':notes' => $data['notes'] ?? 'Cleared & verified by civil registry officer',
                ':ref' => $refNo
            ]);

            echo json_encode(['status' => 'success', 'message' => "Request {$refNo} approved and marked Ready for Release."]);
            exit;
        }

        if ($action === 'reject') {
            $reason = trim($data['reason'] ?? 'Incomplete or unverified documentation');
            $updateSql = "UPDATE `certificate_requests` SET 
                `status` = 'Rejected',
                `rejection_reason` = :reason,
                `verification_notes` = :reason
                WHERE `reference_no` = :ref";
            $uStmt = $pdo->prepare($updateSql);
            $uStmt->execute([
                ':reason' => $reason,
                ':ref' => $refNo
            ]);

            echo json_encode(['status' => 'success', 'message' => "Request {$refNo} has been rejected."]);
            exit;
        }

        if ($action === 'release' || $action === 'issue') {
            // Generate official certificate control prefix
            $type = $target['certificate_type'];
            $prefix = 'BRG';
            if (stripos($type, 'Clearance') !== false) $prefix = 'CLR';
            else if (stripos($type, 'Indigency') !== false) $prefix = 'IND';
            else if (stripos($type, 'Residency') !== false) $prefix = 'RES';
            else if (stripos($type, 'Jobseeker') !== false) $prefix = 'JOB';
            else if (stripos($type, 'Business') !== false) $prefix = 'BUS';

            $controlNo = "{$prefix}-2026-" . str_pad((string)mt_rand(100, 9999), 4, '0', STR_PAD_LEFT);

            // Generate OR number if paid
            $fee = (float)$target['fee_amount'];
            $isWaived = ($fee == 0.00) || stripos($type, 'Indigency') !== false || stripos($type, 'Jobseeker') !== false;
            $orNo = $isWaived ? "WAIVED-{$prefix}" : ("OR-" . mt_rand(984000, 984999));
            $pStatus = $isWaived ? ($fee == 0 ? 'Waived (Indigent)' : 'Waived') : 'Paid';

            // 1. Update certificate_requests
            $upSql = "UPDATE `certificate_requests` SET 
                `status` = 'Released',
                `released_by` = :staff,
                `released_at` = NOW(),
                `or_number` = :or_no,
                `payment_status` = :pstatus
                WHERE `reference_no` = :ref";
            $stmtUp = $pdo->prepare($upSql);
            $stmtUp->execute([
                ':staff' => $staffName,
                ':or_no' => $orNo,
                ':pstatus' => $isWaived ? 'Waived' : 'Paid',
                ':ref' => $refNo
            ]);

            // 2. Insert into issued_certificates
            $insCert = "INSERT INTO `issued_certificates` (
                `certificate_control_no`, `request_id`, `reference_no`, `citizen_id`,
                `citizen_name`, `certificate_type`, `purpose`, `or_number`,
                `fee_amount`, `released_by`, `date_released`, `security_seal_hash`
            ) VALUES (
                :control_no, :req_id, :ref_no, :citizen_id,
                :citizen_name, :cert_type, :purpose, :or_no,
                :fee_amount, :released_by, NOW(), :seal
            )";
            $stmtCert = $pdo->prepare($insCert);
            $sealHash = strtoupper(substr(md5($refNo . time()), 0, 16));
            $stmtCert->execute([
                ':control_no' => $controlNo,
                ':req_id' => $target['request_id'],
                ':ref_no' => $refNo,
                ':citizen_id' => 'CTZ-2026-' . mt_rand(100, 999),
                ':citizen_name' => $target['citizen_name'],
                ':cert_type' => $target['certificate_type'],
                ':purpose' => $target['purpose'],
                ':or_no' => $orNo,
                ':fee_amount' => $fee,
                ':released_by' => $staffName,
                ':seal' => $sealHash
            ]);

            // 3. Insert into certificate_payments
            $insPay = "INSERT INTO `certificate_payments` (
                `or_number`, `reference_no`, `citizen_name`, `certificate_type`,
                `amount_due`, `amount_paid`, `payment_status`, `cashier_name`, `payment_date`
            ) VALUES (
                :or_no, :ref_no, :citizen_name, :cert_type,
                :amount_due, :amount_paid, :payment_status, :cashier, NOW()
            )";
            $stmtPay = $pdo->prepare($insPay);
            $stmtPay->execute([
                ':or_no' => $orNo,
                ':ref_no' => $refNo,
                ':citizen_name' => $target['citizen_name'],
                ':cert_type' => $target['certificate_type'],
                ':amount_due' => $fee,
                ':amount_paid' => $isWaived ? 0.00 : $fee,
                ':payment_status' => $pStatus,
                ':cashier' => 'Barangay Treasury Desk (John Cruz)'
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => "Certificate {$controlNo} issued and logged into audit registry.",
                'control_no' => $controlNo,
                'or_number' => $orNo
            ]);
            exit;
        }

        if ($action === 'reprint') {
            $controlNo = $data['control_no'] ?? null;
            if ($controlNo) {
                $pdo->prepare("UPDATE `issued_certificates` SET `reprint_count` = `reprint_count` + 1 WHERE `certificate_control_no` = :cno")->execute([':cno' => $controlNo]);
            }
            $pdo->prepare("UPDATE `certificate_requests` SET `reprint_count` = `reprint_count` + 1 WHERE `reference_no` = :ref")->execute([':ref' => $refNo]);

            echo json_encode(['status' => 'success', 'message' => "Reprint count logged for {$refNo}."]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
