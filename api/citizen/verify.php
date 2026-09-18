<?php
// Prevent session lock issues
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 1. CORS Configuration (Allow mobile apps, local dev, and cloud web admin)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

    // List of candidate database hosts in priority order
    $candidates = [
        // Candidate 1: From Environment Variables (Dokploy container configuration)
        [
            'host' => getenv('DB_HOST') ?: '',
            'port' => getenv('DB_PORT') ?: 3306,
            'user' => getenv('DB_USER') ?: '',
            'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
            'desc' => 'Dokploy Environment Config'
        ],
        // Candidate 2: Dokploy Internal Docker Network (same container host)
        [
            'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
            'port' => 3306,
            'user' => 'civentral_user',
            'pass' => 'Civentral2026!',
            'desc' => 'Dokploy Internal Docker Mesh (citizenregistry)'
        ],
        // Candidate 3: Dokploy Internal MySQL user
        [
            'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
            'port' => 3306,
            'user' => 'mysql',
            'pass' => 'm68xnwxsqv3urvon',
            'desc' => 'Dokploy Internal Docker (mysql user)'
        ],
        // Candidate 4: Localhost / 127.0.0.1
        [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'civentral_user',
            'pass' => 'Civentral2026!',
            'desc' => 'Localhost (civentral_user)'
        ],
        // Candidate 5: Local XAMPP root default
        [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => '',
            'desc' => 'Localhost XAMPP Default (root)'
        ],
        // Candidate 6: Remote Dokploy Domain (from outside server)
        [
            'host' => 'admin.civentral.tech',
            'port' => 3306,
            'user' => 'civentral_user',
            'pass' => 'Civentral2026!',
            'desc' => 'Dokploy Public Domain (admin.civentral.tech)'
        ]
    ];

    $lastError = '';
    foreach ($candidates as $cand) {
        if (empty($cand['host'])) continue;

        try {
            $dsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cand['user'], $cand['pass'], [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Ensure database and table exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$dbName}`;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `citizen_verifications` (
                `verification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `citizen_user_id` INT UNSIGNED NULL,
                `first_name` VARCHAR(100) NOT NULL,
                `middle_name` VARCHAR(100) NULL,
                `last_name` VARCHAR(100) NOT NULL,
                `suffix` VARCHAR(20) NULL,
                `sex` VARCHAR(20) NOT NULL,
                `place_of_birth` VARCHAR(255) NOT NULL,
                `birth_date` DATE NOT NULL,
                `civil_status` VARCHAR(50) NOT NULL,
                `employment_status` VARCHAR(100) NOT NULL,
                `occupation` VARCHAR(150) NOT NULL,
                `educational_attainment` VARCHAR(100) NOT NULL,
                `district` VARCHAR(50) NOT NULL,
                `barangay` VARCHAR(100) NOT NULL,
                `street_address` VARCHAR(255) NOT NULL,
                `years_resident` INT UNSIGNED NOT NULL,
                `valid_id_type` VARCHAR(100) NOT NULL,
                `valid_id_number` VARCHAR(100) NOT NULL,
                `id_front_photo_url` VARCHAR(500) NULL,
                `selfie_photo_url` VARCHAR(500) NULL,
                `verification_status` ENUM('Pending', 'Under_Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
                `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            return ['pdo' => $pdo, 'target' => $cand['desc'], 'host' => $cand['host']];
        } catch (\Exception $e) {
            $lastError = $cand['desc'] . ': ' . $e->getMessage();
        }
    }

    throw new \Exception("Unable to connect to any database target. Last error: " . $lastError);
}

// 4. Handle GET: Check Status & List Submissions (For Admin / Other Departments)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `citizen_verifications`");
        $total = $countStmt->fetchColumn();

        $recentStmt = $pdo->query("SELECT * FROM `citizen_verifications` ORDER BY `verification_id` DESC LIMIT 10");
        $recent = $recentStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'database' => 'citizen_verification',
            'connected_to' => $conn['target'],
            'message' => 'Civentral Citizen Verification API is online and healthy.',
            'total_verifications_stored' => (int)$total,
            'recent_submissions' => $recent
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 5. Handle POST: Submit Citizen Verification Record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload received.']);
        exit;
    }

    $required = ['first_name', 'last_name', 'street_address', 'barangay', 'valid_id_number'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Missing required field: {$field}"]);
            exit;
        }
    }

    try {
        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        $stmt = $pdo->prepare("INSERT INTO `citizen_verifications` (
            `citizen_user_id`,
            `first_name`,
            `middle_name`,
            `last_name`,
            `suffix`,
            `sex`,
            `place_of_birth`,
            `birth_date`,
            `civil_status`,
            `employment_status`,
            `occupation`,
            `educational_attainment`,
            `district`,
            `barangay`,
            `street_address`,
            `years_resident`,
            `valid_id_type`,
            `valid_id_number`,
            `id_front_photo_url`,
            `selfie_photo_url`,
            `verification_status`,
            `submitted_at`
        ) VALUES (
            :citizen_user_id,
            :first_name,
            :middle_name,
            :last_name,
            :suffix,
            :sex,
            :place_of_birth,
            :birth_date,
            :civil_status,
            :employment_status,
            :occupation,
            :educational_attainment,
            :district,
            :barangay,
            :street_address,
            :years_resident,
            :valid_id_type,
            :valid_id_number,
            :id_front_photo_url,
            :selfie_photo_url,
            'Pending',
            NOW()
        )");

        $stmt->execute([
            ':citizen_user_id'         => !empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : 1001,
            ':first_name'              => trim($data['first_name']),
            ':middle_name'             => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            ':last_name'               => trim($data['last_name']),
            ':suffix'                  => !empty($data['suffix']) ? trim($data['suffix']) : null,
            ':sex'                     => !empty($data['sex']) ? $data['sex'] : 'Male',
            ':place_of_birth'          => !empty($data['place_of_birth']) ? trim($data['place_of_birth']) : 'Caloocan City',
            ':birth_date'              => !empty($data['birth_date']) ? $data['birth_date'] : '2000-01-01',
            ':civil_status'            => !empty($data['civil_status']) ? $data['civil_status'] : 'Single',
            ':employment_status'       => !empty($data['employment_status']) ? $data['employment_status'] : 'Employed',
            ':occupation'              => !empty($data['occupation']) ? $data['occupation'] : 'Private Sector',
            ':educational_attainment'  => !empty($data['educational_attainment']) ? $data['educational_attainment'] : 'College',
            ':district'                => !empty($data['district']) ? $data['district'] : 'District 1',
            ':barangay'                => trim($data['barangay']),
            ':street_address'          => trim($data['street_address']),
            ':years_resident'          => !empty($data['years_resident']) ? (int)$data['years_resident'] : 1,
            ':valid_id_type'           => !empty($data['valid_id_type']) ? $data['valid_id_type'] : 'PhilSys National ID',
            ':valid_id_number'         => trim($data['valid_id_number']),
            ':id_front_photo_url'      => !empty($data['id_front_photo_url']) ? $data['id_front_photo_url'] : null,
            ':selfie_photo_url'        => !empty($data['selfie_photo_url']) ? $data['selfie_photo_url'] : null,
        ]);

        $insertedId = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Citizen verification submitted successfully.',
            'verification_id' => (int)$insertedId,
            'connected_to' => $conn['target'],
            'reference_number' => 'CAL-VERIF-' . date('Y') . '-' . str_pad($insertedId, 5, '0', STR_PAD_LEFT)
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database storage failure: ' . $e->getMessage()
        ]);
        exit;
    }
}
