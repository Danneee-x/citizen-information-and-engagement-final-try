<?php
// Prevent session lock issues
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 1. CORS Configuration
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Load .env if available
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

// 3. Database Connection Factory
function getDbConnection() {
    $dbName = getenv('DB_NAME') ?: 'citizen_verification';
    $isLocal = (PHP_OS_FAMILY === 'Windows') || (!file_exists('/.dockerenv') && empty(getenv('DOKPLOY')) && empty(getenv('DOCKER')));

    if ($isLocal) {
        $candidates = [
            [
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: 3306,
                'user' => getenv('DB_USER') ?: 'root',
                'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
                'desc' => 'Localhost XAMPP Default (root)'
            ],
            [
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
                'desc' => 'Localhost (civentral_user)'
            ]
        ];
    } else {
        $candidates = [
            [
                'host' => getenv('DB_HOST') ?: '',
                'port' => getenv('DB_PORT') ?: 3306,
                'user' => getenv('DB_USER') ?: '',
                'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
                'desc' => 'Dokploy Environment Config'
            ],
            [
                'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
                'desc' => 'Dokploy Internal Docker Mesh (citizenregistry)'
            ],
            [
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'pass' => '',
                'desc' => 'Localhost Fallback'
            ]
        ];
    }

    $lastError = '';
    foreach ($candidates as $cand) {
        if (empty($cand['host'])) continue;

        try {
            $dsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cand['user'], $cand['pass'], [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $pdo->exec("USE `{$dbName}`;");
            return ['pdo' => $pdo, 'target' => $cand['desc']];
        } catch (\Exception $e) {
            $lastError = $cand['desc'] . ': ' . $e->getMessage();
        }
    }

    throw new \Exception("Database connection failure: " . $lastError);
}

// 4. Handle GET: Query Concerns & Aggregated Stats
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        // Aggregate KPIs
        $totalStmt = $pdo->query("SELECT COUNT(*) FROM `citizen_concerns`");
        $totalTickets = (int)$totalStmt->fetchColumn();

        $newStmt = $pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `status` IN ('New', 'Under Review')");
        $newUnrouted = (int)$newStmt->fetchColumn();

        $urgentStmt = $pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `priority` IN ('Urgent', 'High')");
        $urgentCount = (int)$urgentStmt->fetchColumn();

        $anonStmt = $pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `is_anonymous` = 1");
        $anonCount = (int)$anonStmt->fetchColumn();

        // Filters
        $where = [];
        $params = [];

        if (!empty($_GET['status'])) {
            $where[] = "`status` = :status";
            $params[':status'] = $_GET['status'];
        }
        if (!empty($_GET['category'])) {
            $where[] = "`category` = :category";
            $params[':category'] = $_GET['category'];
        }
        if (!empty($_GET['priority'])) {
            $where[] = "`priority` = :priority";
            $params[':priority'] = $_GET['priority'];
        }
        if (!empty($_GET['barangay'])) {
            $where[] = "`barangay` LIKE :barangay";
            $params[':barangay'] = '%' . $_GET['barangay'] . '%';
        }
        if (!empty($_GET['search'])) {
            $s = '%' . trim($_GET['search']) . '%';
            $where[] = "(`ticket_number` LIKE :s1 OR `title` LIKE :s2 OR `description` LIKE :s3 OR `location` LIKE :s4 OR `citizen_name` LIKE :s5)";
            $params[':s1'] = $s;
            $params[':s2'] = $s;
            $params[':s3'] = $s;
            $params[':s4'] = $s;
            $params[':s5'] = $s;
        }

        $sql = "SELECT * FROM `citizen_concerns`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY `concern_id` DESC";

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'kpis' => [
                'total_tickets' => $totalTickets,
                'new_unrouted' => $newUnrouted,
                'urgent_tickets' => $urgentCount,
                'anonymous_count' => $anonCount
            ],
            'count' => count($rows),
            'concerns' => $rows
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 5. Handle POST/PATCH: Update Concern (Status, Department, Priority, Notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    try {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $data = !empty($json) ? $json : $_POST;

        $ticketId = $data['ticket_number'] ?? ($data['id'] ?? null);
        $concernId = !empty($data['concern_id']) ? (int)$data['concern_id'] : null;

        if (empty($ticketId) && empty($concernId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ticket number or concern ID is required.']);
            exit;
        }

        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        $updates = [];
        $params = [];

        if (isset($data['status'])) {
            $updates[] = "`status` = :status";
            $params[':status'] = $data['status'];
            if (in_array($data['status'], ['Resolved', 'Closed'])) {
                $updates[] = "`resolved_at` = NOW()";
            }
        }
        if (isset($data['assigned_department'])) {
            $updates[] = "`assigned_department` = :assigned_dept";
            $params[':assigned_dept'] = $data['assigned_department'];
        }
        if (isset($data['priority'])) {
            $updates[] = "`priority` = :priority";
            $params[':priority'] = $data['priority'];
        }
        if (isset($data['resolution_notes'])) {
            $updates[] = "`resolution_notes` = :resolution_notes";
            $params[':resolution_notes'] = $data['resolution_notes'];
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No fields provided to update.']);
            exit;
        }

        $sql = "UPDATE `citizen_concerns` SET " . implode(", ", $updates) . " WHERE ";
        if ($concernId) {
            $sql .= "`concern_id` = :cid";
            $params[':cid'] = $concernId;
        } else {
            $sql .= "`ticket_number` = :tnum";
            $params[':tnum'] = $ticketId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Fetch updated record
        $fetchSql = "SELECT * FROM `citizen_concerns` WHERE " . ($concernId ? "`concern_id` = :id" : "`ticket_number` = :id");
        $fetchStmt = $pdo->prepare($fetchSql);
        $fetchStmt->execute([':id' => $concernId ?: $ticketId]);
        $updated = $fetchStmt->fetch();

        echo json_encode([
            'status' => 'success',
            'message' => 'Concern ticket updated successfully.',
            'concern' => $updated
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
