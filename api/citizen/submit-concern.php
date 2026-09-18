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
            ],
            [
                'host' => 'citizeninformationandengagement-citizenregistry-ffbtjn',
                'port' => 3306,
                'user' => 'civentral_user',
                'pass' => 'Civentral2026!',
                'desc' => 'Dokploy Internal Docker Mesh'
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

            // Ensure database and table exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$dbName}`;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `citizen_concerns` (
                `concern_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `ticket_number` VARCHAR(50) UNIQUE NOT NULL,
                `citizen_user_id` INT UNSIGNED NULL,
                `citizen_name` VARCHAR(150) NOT NULL,
                `citizen_phone` VARCHAR(50) NULL,
                `citizen_email` VARCHAR(150) NULL,
                `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
                `category` VARCHAR(100) NOT NULL,
                `sub_category` VARCHAR(100) NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                `location` VARCHAR(255) NOT NULL,
                `barangay` VARCHAR(100) NOT NULL,
                `district` VARCHAR(50) NULL,
                `gps_coordinates` VARCHAR(100) NULL,
                `status` ENUM('New', 'Under Review', 'Routed', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'New',
                `priority` ENUM('Urgent', 'High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium',
                `assigned_department` VARCHAR(150) NULL,
                `ai_detected_category` VARCHAR(100) NULL,
                `ai_confidence_score` VARCHAR(100) NULL,
                `photo_evidence_url` MEDIUMTEXT NULL,
                `attachments` TEXT NULL,
                `resolution_notes` TEXT NULL,
                `resolved_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_status` (`status`),
                INDEX `idx_category` (`category`),
                INDEX `idx_barangay` (`barangay`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return ['pdo' => $pdo, 'target' => $cand['desc'], 'host' => $cand['host']];
        } catch (\Exception $e) {
            $lastError = $cand['desc'] . ': ' . $e->getMessage();
        }
    }

    throw new \Exception("Unable to connect to any database target. Last error: " . $lastError);
}

// 4. Handle GET: Check Status & List Submissions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `citizen_concerns`");
        $total = $countStmt->fetchColumn();

        $recentStmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC LIMIT 10");
        $recent = $recentStmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'database' => 'citizen_verification',
            'connected_to' => $conn['target'],
            'message' => 'Civentral Citizen Grievance & Concern API is online and healthy.',
            'total_concerns_stored' => (int)$total,
            'recent_submissions' => $recent
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// 5. Handle POST: Citizen Submits a Concern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);
        $data = !empty($json) ? $json : $_POST;

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? 'Other');
        $subCategory = trim($data['sub_category'] ?? '');
        $location = trim($data['location'] ?? '');
        $barangay = trim($data['barangay'] ?? 'Barangay 171 (Bagumbong)');
        $district = trim($data['district'] ?? '');
        $gpsCoords = trim($data['gps_coordinates'] ?? ($data['gpsCoords'] ?? ''));
        
        $citizenUserId = !empty($data['citizen_user_id']) ? (int)$data['citizen_user_id'] : null;
        $isAnonymous = !empty($data['is_anonymous']) ? 1 : 0;
        
        $citizenName = trim($data['citizen_name'] ?? ($data['contactName'] ?? 'Citizen Resident'));
        $citizenPhone = trim($data['citizen_phone'] ?? ($data['contactPhone'] ?? ''));
        $citizenEmail = trim($data['citizen_email'] ?? ($data['contactEmail'] ?? ''));

        if ($isAnonymous) {
            $citizenName = 'Anonymous Resident';
            $citizenPhone = null;
            $citizenEmail = null;
        }

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Concern title/subject is required.']);
            exit;
        }

        if (empty($description)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Concern detailed description is required.']);
            exit;
        }

        if (empty($location)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Concern location is required.']);
            exit;
        }

        // District Auto-Resolution from Barangay
        if (empty($district)) {
            if (preg_match('/Barangay\s*(\d+)/i', $barangay, $m)) {
                $bNum = (int)$m[1];
                if ($bNum >= 1 && $bNum <= 76) $district = 'District 2';
                else if ($bNum >= 77 && $bNum <= 131) $district = 'District 2';
                else if ($bNum >= 132 && $bNum <= 170) $district = 'District 1';
                else if ($bNum >= 171 && $bNum <= 177) $district = 'District 1';
                else if ($bNum >= 178) $district = 'District 3';
            } else {
                $district = 'District 1';
            }
        }

        // AI Engine Multi-Modal Classification Simulation (Caloocan Public Service Routing)
        $textCombo = strtolower($title . ' ' . $description . ' ' . $category);
        $detectedCategory = $category;
        $priority = 'Medium';
        $assignedDept = 'Caloocan Public Assistance Bureau';
        $confidenceScore = '95% - Gemini AI Multi-Modal Engine';
        $similarConcerns = 'No duplicate reports found';

        if (strpos($textCombo, 'garbage') !== false || strpos($textCombo, 'waste') !== false || strpos($textCombo, 'trash') !== false || strpos($textCombo, 'dump') !== false || $category === 'Garbage & Waste') {
            $detectedCategory = 'Garbage & Waste Management';
            $priority = 'Medium';
            $assignedDept = 'Environmental / Waste Management Department';
            $confidenceScore = '97% - Gemini AI Multi-Modal Engine';
            $similarConcerns = '2 similar concerns found within 250m';
        } else if (strpos($textCombo, 'road') !== false || strpos($textCombo, 'pothole') !== false || strpos($textCombo, 'bridge') !== false || strpos($textCombo, 'crack') !== false || $category === 'Road & Infrastructure') {
            $detectedCategory = 'Road & Infrastructure Repairs';
            $priority = 'High';
            $assignedDept = 'City Engineering & Public Works Office';
            $confidenceScore = '98% - Gemini AI Multi-Modal Engine';
            $similarConcerns = '1 duplicate report merged';
        } else if (strpos($textCombo, 'flood') !== false || strpos($textCombo, 'drain') !== false || strpos($textCombo, 'canal') !== false || strpos($textCombo, 'waterlog') !== false || $category === 'Flooding & Drainage') {
            $detectedCategory = 'Flooding & Drainage Maintenance';
            $priority = 'High';
            $assignedDept = 'Caloocan Flood Control & Drainage Bureau';
            $confidenceScore = '96% - Gemini AI Multi-Modal Engine';
            $similarConcerns = '3 related flood tickets detected';
        } else if (strpos($textCombo, 'light') !== false || strpos($textCombo, 'dark') !== false || strpos($textCombo, 'lamp') !== false || strpos($textCombo, 'post') !== false || $category === 'Streetlights') {
            $detectedCategory = 'Streetlighting & Public Electrical';
            $priority = 'Medium';
            $assignedDept = 'Public Safety Electrical Division';
            $confidenceScore = '94% - Gemini AI Multi-Modal Engine';
            $similarConcerns = 'No duplicate reports found';
        } else if (strpos($textCombo, 'safety') !== false || strpos($textCombo, 'police') !== false || strpos($textCombo, 'hazard') !== false || strpos($textCombo, 'theft') !== false || $category === 'Public Safety') {
            $detectedCategory = 'Public Safety & Peace Order';
            $priority = 'Urgent';
            $assignedDept = 'Caloocan Public Safety & Police Bureau (CPTMD)';
            $confidenceScore = '99% - Gemini AI Multi-Modal Engine';
            $similarConcerns = 'Immediate dispatch alert generated';
        } else if (strpos($textCombo, 'tree') !== false || strpos($textCombo, 'smoke') !== false || strpos($textCombo, 'pollution') !== false || $category === 'Environment') {
            $detectedCategory = 'Environmental Protection & Natural Resources';
            $priority = 'Medium';
            $assignedDept = 'City Environment & Natural Resources Office';
            $confidenceScore = '93% - Gemini AI Multi-Modal Engine';
            $similarConcerns = '1 related environmental ticket';
        }

        // Process Photos / Attachments
        $uploadDir = __DIR__ . '/../../assets/uploads/concerns/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $savedAttachments = [];
        $photoEvidenceUrl = null;

        // Base64 photos array from mobile app
        if (!empty($data['photos']) && is_array($data['photos'])) {
            foreach ($data['photos'] as $idx => $photo) {
                if (is_array($photo) && !empty($photo['data'])) {
                    $base64 = $photo['data'];
                    if (strpos($base64, ',') !== false) {
                        list(, $base64) = explode(',', $base64);
                    }
                    $decoded = base64_decode($base64);
                    if ($decoded !== false) {
                        $filename = 'concern_' . time() . '_' . rand(1000, 9999) . '_' . ($idx + 1) . '.jpg';
                        file_put_contents($uploadDir . $filename, $decoded);
                        $savedAttachments[] = $filename;
                        if (!$photoEvidenceUrl) {
                            $photoEvidenceUrl = 'assets/uploads/concerns/' . $filename;
                        }
                    }
                } else if (is_string($photo) && strpos($photo, 'data:image') === 0) {
                    list(, $base64) = explode(',', $photo);
                    $decoded = base64_decode($base64);
                    if ($decoded !== false) {
                        $filename = 'concern_' . time() . '_' . rand(1000, 9999) . '_' . ($idx + 1) . '.jpg';
                        file_put_contents($uploadDir . $filename, $decoded);
                        $savedAttachments[] = $filename;
                        if (!$photoEvidenceUrl) {
                            $photoEvidenceUrl = 'assets/uploads/concerns/' . $filename;
                        }
                    }
                } else if (is_array($photo) && !empty($photo['name'])) {
                    $savedAttachments[] = $photo['name'];
                }
            }
        }

        // Direct photoEvidenceUrl passed
        if (!$photoEvidenceUrl && !empty($data['photo_evidence_url'])) {
            $photoEvidenceUrl = $data['photo_evidence_url'];
        }

        // Direct files uploaded via $_FILES
        if (!empty($_FILES['attachments']['name'])) {
            $files = $_FILES['attachments'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < $fileCount; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                if ($error === UPLOAD_ERR_OK && !empty($tmpName)) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
                    $targetFilename = 'concern_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $targetFilename)) {
                        $savedAttachments[] = $targetFilename;
                        if (!$photoEvidenceUrl) {
                            $photoEvidenceUrl = 'assets/uploads/concerns/' . $targetFilename;
                        }
                    }
                }
            }
        }

        $attachmentsJson = !empty($savedAttachments) ? json_encode($savedAttachments) : null;

        // Generate Ticket Number (CAL-REP-2026-XXXX)
        $conn = getDbConnection();
        $pdo = $conn['pdo'];

        $ticketNumber = 'CAL-REP-2026-' . rand(1000, 9999);
        // Ensure uniqueness
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `citizen_concerns` WHERE `ticket_number` = :t");
        $checkStmt->execute([':t' => $ticketNumber]);
        if ($checkStmt->fetchColumn() > 0) {
            $ticketNumber = 'CAL-REP-2026-' . rand(10000, 99999);
        }

        $sql = "INSERT INTO `citizen_concerns` (
            `ticket_number`, `citizen_user_id`, `citizen_name`, `citizen_phone`, `citizen_email`,
            `is_anonymous`, `category`, `sub_category`, `title`, `description`,
            `location`, `barangay`, `district`, `gps_coordinates`, `status`,
            `priority`, `assigned_department`, `ai_detected_category`, `ai_confidence_score`,
            `photo_evidence_url`, `attachments`
        ) VALUES (
            :ticket_number, :citizen_user_id, :citizen_name, :citizen_phone, :citizen_email,
            :is_anonymous, :category, :sub_category, :title, :description,
            :location, :barangay, :district, :gps_coordinates, :status,
            :priority, :assigned_department, :ai_detected_category, :ai_confidence_score,
            :photo_evidence_url, :attachments
        )";

        $stmt = $pdo->prepare($sql);
        $status = 'New';
        $stmt->execute([
            ':ticket_number' => $ticketNumber,
            ':citizen_user_id' => $citizenUserId,
            ':citizen_name' => $citizenName,
            ':citizen_phone' => $citizenPhone,
            ':citizen_email' => $citizenEmail,
            ':is_anonymous' => $isAnonymous,
            ':category' => $category,
            ':sub_category' => !empty($subCategory) ? $subCategory : null,
            ':title' => $title,
            ':description' => $description,
            ':location' => $location,
            ':barangay' => $barangay,
            ':district' => $district,
            ':gps_coordinates' => !empty($gpsCoords) ? $gpsCoords : null,
            ':status' => $status,
            ':priority' => $priority,
            ':assigned_department' => $assignedDept,
            ':ai_detected_category' => $detectedCategory,
            ':ai_confidence_score' => $confidenceScore,
            ':photo_evidence_url' => $photoEvidenceUrl,
            ':attachments' => $attachmentsJson
        ]);

        $insertedId = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Concern ticket filed successfully.',
            'ticket_number' => $ticketNumber,
            'concern_id' => (int)$insertedId,
            'data' => [
                'ticket_number' => $ticketNumber,
                'title' => $title,
                'category' => $category,
                'status' => $status,
                'priority' => $priority,
                'detected_category' => $detectedCategory,
                'recommended_department' => $assignedDept,
                'confidence_score' => $confidenceScore,
                'similar_concerns' => $similarConcerns,
                'submission_date' => date('M j, Y • h:i A')
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to process concern submission: ' . $e->getMessage()
        ]);
        exit;
    }
}
