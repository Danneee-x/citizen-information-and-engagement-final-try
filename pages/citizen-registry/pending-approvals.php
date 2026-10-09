<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

// Real Applications & Metrics Data from MySQL
require_once __DIR__ . '/../../config/database.php';

$apiBase = rtrim(getenv('API_BASE_URL') ?: 'https://api-citizen.civentral.tech', '/');

$applications = [];
$counts = [
    'total' => 0,
    'total_all' => 0,
    'pending' => 0,
    'under_review' => 0,
    'returned' => 0,
    'approved' => 0,
    'rejected' => 0,
    'awaiting' => 0,
    'assigned_to_me' => 0,
    'today_approved' => 0,
    'today_rejected' => 0,
];

$currentTab = strtolower(trim($_GET['tab'] ?? 'pending'));

try {
    $pdo = getDbConnection();

    // Ensure citizen_verifications table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `citizen_verifications` (
        `verification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `citizen_id_number` VARCHAR(30) NULL UNIQUE,
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
        `photo_1x1_url` VARCHAR(500) NULL,
        `signature_photo_url` VARCHAR(500) NULL,
        `qr_code_token` VARCHAR(255) NULL,
        `qr_code_image_url` VARCHAR(500) NULL,
        `verification_status` ENUM('Pending', 'Under_Review', 'Returned_For_Correction', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
        `reviewed_by` VARCHAR(100) NULL,
        `rejection_reason` TEXT NULL,
        `admin_action_notes` TEXT NULL,
        `reviewed_at` DATETIME NULL,
        `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Auto-migrate newly added columns if they don't exist yet
    $verifColumns = [
        'citizen_id_number' => "VARCHAR(30) NULL",
        'photo_1x1_url' => "VARCHAR(500) NULL",
        'signature_photo_url' => "VARCHAR(500) NULL",
        'qr_code_token' => "VARCHAR(255) NULL",
        'qr_code_image_url' => "VARCHAR(500) NULL",
        'admin_action_notes' => "TEXT NULL"
    ];
    foreach ($verifColumns as $col => $colDef) {
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM `citizen_verifications` LIKE '$col'")->fetch();
            if (!$checkCol) {
                $pdo->exec("ALTER TABLE `citizen_verifications` ADD COLUMN `$col` $colDef");
            }
        } catch (Exception $e) {
            // Ignore if column exists
        }
    }

    // Ensure ENUM includes Returned_For_Correction and Superseded
    try {
        $pdo->exec("ALTER TABLE `citizen_verifications` MODIFY COLUMN `verification_status` ENUM('Pending','Under_Review','Returned_For_Correction','Approved','Rejected','Superseded') NOT NULL DEFAULT 'Pending'");
    } catch (Exception $e) {}

    // Auto-resolve: If a citizen now has an Approved verification, automatically supersede any older Rejected or Returned_For_Correction records
    try {
        $pdo->exec("UPDATE citizen_verifications v_old
            JOIN citizen_verifications v_app ON (
                (v_old.citizen_user_id = v_app.citizen_user_id AND v_old.citizen_user_id > 0)
                OR (LOWER(TRIM(CONCAT(v_old.first_name, ' ', v_old.last_name))) = LOWER(TRIM(CONCAT(v_app.first_name, ' ', v_app.last_name))) AND v_old.barangay = v_app.barangay)
            )
            SET v_old.verification_status = 'Superseded', v_old.is_archived = 1
            WHERE v_app.verification_status = 'Approved'
              AND v_old.verification_id != v_app.verification_id
              AND v_old.verification_status IN ('Rejected', 'Returned_For_Correction')");
    } catch (Exception $e) {}

    // Fetch counts
    // Note: Rejected applications are superseded and excluded from Total Rejected if the citizen now has an Approved application
    $statsStmt = $pdo->query("SELECT 
        COUNT(CASE WHEN verification_status != 'Superseded' AND COALESCE(is_archived, 0) = 0 THEN 1 ELSE NULL END) as total_all,
        SUM(CASE WHEN verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN verification_status = 'Under_Review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN verification_status = 'Returned_For_Correction' AND COALESCE(is_archived, 0) = 0 THEN 1 ELSE 0 END) as returned,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN verification_status = 'Rejected' AND COALESCE(is_archived, 0) = 0 
            AND NOT EXISTS (
                SELECT 1 FROM citizen_verifications v_app 
                WHERE v_app.verification_status = 'Approved' 
                AND ((v_app.citizen_user_id = citizen_verifications.citizen_user_id AND citizen_verifications.citizen_user_id > 0)
                     OR (LOWER(TRIM(CONCAT(v_app.first_name, ' ', v_app.last_name))) = LOWER(TRIM(CONCAT(citizen_verifications.first_name, ' ', citizen_verifications.last_name))) AND v_app.barangay = citizen_verifications.barangay))
            ) THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN reviewed_by IS NOT NULL AND reviewed_by != '' AND reviewed_by != 'Unassigned' AND verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as assigned_to_me,
        SUM(CASE WHEN verification_status = 'Approved' AND DATE(reviewed_at) = CURDATE() THEN 1 ELSE 0 END) as today_approved,
        SUM(CASE WHEN verification_status = 'Rejected' AND COALESCE(is_archived, 0) = 0 
            AND DATE(reviewed_at) = CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM citizen_verifications v_app 
                WHERE v_app.verification_status = 'Approved' 
                AND ((v_app.citizen_user_id = citizen_verifications.citizen_user_id AND citizen_verifications.citizen_user_id > 0)
                     OR (LOWER(TRIM(CONCAT(v_app.first_name, ' ', v_app.last_name))) = LOWER(TRIM(CONCAT(citizen_verifications.first_name, ' ', citizen_verifications.last_name))) AND v_app.barangay = citizen_verifications.barangay))
            ) THEN 1 ELSE 0 END) as today_rejected,
        AVG(CASE WHEN reviewed_at IS NOT NULL AND submitted_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, submitted_at, reviewed_at) ELSE NULL END) as avg_proc_minutes
        FROM citizen_verifications");
    $dbStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $counts['total_all']      = (int)($dbStats['total_all'] ?? 0);
    $counts['total']          = (int)($dbStats['pending'] ?? 0);
    $counts['pending']        = (int)($dbStats['pending'] ?? 0);
    $counts['under_review']   = (int)($dbStats['under_review'] ?? 0);
    $counts['returned']       = (int)($dbStats['returned'] ?? 0);
    $counts['approved']       = (int)($dbStats['approved'] ?? 0);
    $counts['rejected']       = (int)($dbStats['rejected'] ?? 0);
    $counts['assigned_to_me'] = (int)($dbStats['assigned_to_me'] ?? 0);
    $counts['awaiting']       = $counts['pending'];
    $counts['today_approved'] = (int)($dbStats['today_approved'] ?? 0);
    $counts['today_rejected'] = (int)($dbStats['today_rejected'] ?? 0);

    $avgMinutes = (float)($dbStats['avg_proc_minutes'] ?? 0);
    if ($avgMinutes <= 0) {
        $avgProcessingTimeDisplay = '< 1 <span class="text-xs font-normal">hr</span>';
    } elseif ($avgMinutes < 60) {
        $avgProcessingTimeDisplay = round($avgMinutes) . ' <span class="text-xs font-normal">mins</span>';
    } elseif ($avgMinutes < 1440) {
        $avgProcessingTimeDisplay = round($avgMinutes / 60, 1) . ' <span class="text-xs font-normal">hrs</span>';
    } else {
        $avgProcessingTimeDisplay = round($avgMinutes / 1440, 1) . ' <span class="text-xs font-normal">days</span>';
    }

    // Tab-based Application Query
    if ($currentTab === 'returned') {
        $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status = 'Returned_For_Correction' AND COALESCE(is_archived, 0) = 0 ORDER BY updated_at DESC LIMIT 100");
    } elseif ($currentTab === 'approved') {
        $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status = 'Approved' ORDER BY reviewed_at DESC LIMIT 100");
    } elseif ($currentTab === 'rejected') {
        // Exclude applications where the citizen has already been accepted/approved in another registration
        $stmt = $pdo->query("SELECT v.* FROM citizen_verifications v 
            WHERE v.verification_status = 'Rejected' 
              AND COALESCE(v.is_archived, 0) = 0
              AND NOT EXISTS (
                  SELECT 1 FROM citizen_verifications v_app 
                  WHERE v_app.verification_status = 'Approved' 
                  AND ((v_app.citizen_user_id = v.citizen_user_id AND v.citizen_user_id > 0)
                       OR (LOWER(TRIM(CONCAT(v_app.first_name, ' ', v_app.last_name))) = LOWER(TRIM(CONCAT(v.first_name, ' ', v.last_name))) AND v_app.barangay = v.barangay))
              )
            ORDER BY v.reviewed_at DESC LIMIT 100");
    } elseif ($currentTab === 'all') {
        $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status != 'Superseded' ORDER BY submitted_at DESC LIMIT 100");
    } else {
        // Default: Pending review queue
        $currentTab = 'pending';
        $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status IN ('Pending', 'Under_Review') ORDER BY submitted_at DESC LIMIT 100");
    }

    $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbRows as $row) {
        $fullName = preg_replace('/\s+/', ' ', trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']} {$row['suffix']}"));
        $dt = !empty($row['submitted_at']) ? new DateTime($row['submitted_at']) : new DateTime();
        
        if ($row['verification_status'] === 'Under_Review') {
            $statusDisplay = 'Under Review';
        } elseif ($row['verification_status'] === 'Returned_For_Correction') {
            $statusDisplay = 'Returned for Correction';
        } else {
            $statusDisplay = $row['verification_status'];
        }

        $applications[] = [
            'id' => 'VER-' . str_pad($row['verification_id'], 4, '0', STR_PAD_LEFT),
            'raw_id' => $row['verification_id'],
            'citizen_user_id' => $row['citizen_user_id'] ?? 0,
            'citizen_id_number' => $row['citizen_id_number'] ?? '',
            'applicant' => $fullName,
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? '',
            'last_name' => $row['last_name'],
            'suffix' => $row['suffix'] ?? '',
            'sex' => $row['sex'] ?? 'Not Specified',
            'birth_date' => $row['birth_date'] ?? '',
            'civil_status' => $row['civil_status'] ?? '',
            'employment_status' => $row['employment_status'] ?? '',
            'occupation' => $row['occupation'] ?? '',
            'street_address' => $row['street_address'] ?? '',
            'years_resident' => $row['years_resident'] ?? 1,
            'valid_id_type' => $row['valid_id_type'] ?? 'Valid ID',
            'valid_id_number' => $row['valid_id_number'] ?? '',
            'id_front_photo_url' => $row['id_front_photo_url'] ?? '',
            'selfie_photo_url' => $row['selfie_photo_url'] ?? '',
            'photo_1x1_url' => $row['photo_1x1_url'] ?? '',
            'signature_photo_url' => $row['signature_photo_url'] ?? '',
            'qr_code_token' => $row['qr_code_token'] ?? '',
            'qr_code_image_url' => $row['qr_code_image_url'] ?? '',
            'rejection_reason' => $row['rejection_reason'] ?? '',
            'admin_action_notes' => $row['admin_action_notes'] ?? '',
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random',
            'date' => $dt->format('M d, Y'),
            'time' => $dt->format('h:i A'),
            'submitted_by' => 'Citizen Mobile App',
            'district' => $row['district'] ?? 'District 1',
            'barangay' => $row['barangay'] ?? '',
            'reviewer' => !empty($row['reviewed_by']) ? $row['reviewed_by'] : 'Unassigned',
            'reviewer_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($row['reviewed_by'] ?? 'Admin') . '&background=random',
            'reviewed_at' => !empty($row['reviewed_at']) ? (new DateTime($row['reviewed_at']))->format('M d, Y h:i A') : '',
            'reviewed_by' => $row['reviewed_by'] ?? '',
            'docs_count' => (!empty($row['photo_1x1_url']) || !empty($row['signature_photo_url'])) ? '+4' : '+2',
            'status' => $statusDisplay,
            'priority' => ($row['years_resident'] ?? 0) >= 5 ? 'High' : 'Medium',
            'selected' => false
        ];
    }
} catch (Exception $e) {
    error_log("Pending approvals fetch error: " . $e->getMessage());
}

function getAppStatusBadge($status) {
    switch ($status) {
        case 'Pending': return 'bg-amber-50 text-amber-600 border-amber-200/80';
        case 'Under Review': 
        case 'Under_Review': return 'bg-blue-50 text-blue-600 border-blue-200/80';
        case 'Returned for Correction':
        case 'Returned_For_Correction': return 'bg-orange-50 text-orange-600 border-orange-200/80';
        case 'Approved': return 'bg-emerald-50 text-emerald-600 border-emerald-200/80';
        case 'Rejected': return 'bg-red-50 text-red-600 border-red-200/80';
        case 'Superseded': return 'bg-slate-100 text-slate-500 border-slate-300';
        default: return 'bg-slate-50 text-slate-600 border-slate-200';
    }
}

function getPriorityBadge($priority) {
    switch ($priority) {
        case 'High': return 'bg-orange-50 text-orange-600 border-orange-200/80';
        case 'Medium': return 'bg-blue-50 text-blue-600 border-blue-200/80';
        case 'Low': return 'bg-slate-50 text-slate-500 border-slate-200';
        default: return 'bg-slate-50 text-slate-500 border-slate-200';
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)]">

    <!-- Breadcrumb Header -->
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">
        <span>Citizen Registry</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Citizen Verification Queue</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4 mb-6">
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="refreshQueue()" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i id="refreshQueueIcon" class="fa-solid fa-rotate text-[11px]"></i>
                <span>Refresh Queue</span>
            </button>
            <button onclick="exportPendingListCSV()" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-upload text-[11px]"></i>
                <span>Export List</span>
            </button>
            <a href="registered-citizens.php" class="px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-users text-[11px]"></i>
                <span>Registered Citizens</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <!-- Card 1: Pending -->
        <a href="?tab=pending" class="bg-white rounded-2xl p-3.5 border <?php echo ($currentTab === 'pending') ? 'border-amber-400 ring-2 ring-amber-100' : 'border-slate-100'; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-file-lines text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Pending<br>Review</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['pending']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-amber-600">
                <span>Action Queue</span>
            </div>
        </a>

        <!-- Card 2: Returned for Correction -->
        <a href="?tab=returned" class="bg-white rounded-2xl p-3.5 border <?php echo ($currentTab === 'returned') ? 'border-orange-400 ring-2 ring-orange-100' : 'border-slate-100'; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-rotate-left text-orange-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Returned for<br>Rework</p>
                    <h3 class="text-lg font-black text-orange-600 mt-0.5"><?php echo $counts['returned']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-orange-500">
                <span>Awaiting Citizen</span>
            </div>
        </a>

        <!-- Card 3: Approved Today -->
        <a href="?tab=approved" class="bg-white rounded-2xl p-3.5 border <?php echo ($currentTab === 'approved') ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-100'; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-check text-emerald-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Total<br>Approved</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['approved']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-emerald-600">
                <span>Today: <?php echo $counts['today_approved']; ?></span>
            </div>
        </a>

        <!-- Card 4: Rejected -->
        <a href="?tab=rejected" class="bg-white rounded-2xl p-3.5 border <?php echo ($currentTab === 'rejected') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-100'; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-xmark text-red-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Total<br>Rejected</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['rejected']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-red-500">
                <span>Today: <?php echo $counts['today_rejected']; ?></span>
            </div>
        </a>

        <!-- Card 5: All Records -->
        <a href="?tab=all" class="bg-white rounded-2xl p-3.5 border <?php echo ($currentTab === 'all') ? 'border-blue-400 ring-2 ring-blue-100' : 'border-slate-100'; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-list-check text-blue-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">All Submissions<br>Logged</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['total_all']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-blue-600">
                <span>Complete History</span>
            </div>
        </a>

        <!-- Card 6: Avg Proc Time -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-clock text-purple-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Avg. Processing<br>Time</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $avgProcessingTimeDisplay; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-slate-400">
                <span>Verified vs Submitted</span>
            </div>
        </div>
    </div>

    <!-- Queue Status Navigation Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2 mb-4">
        <a href="?tab=pending" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition <?php echo ($currentTab === 'pending') ? 'bg-[#0f53d1] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'; ?>">
            <i class="fa-regular fa-clock"></i>
            <span>Pending Review</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo ($currentTab === 'pending') ? 'bg-white/20 text-white' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>"><?php echo $counts['pending']; ?></span>
        </a>
        <a href="?tab=returned" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition <?php echo ($currentTab === 'returned') ? 'bg-orange-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'; ?>">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Returned for Correction</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo ($currentTab === 'returned') ? 'bg-white/20 text-white' : 'bg-orange-50 text-orange-700 border border-orange-200'; ?>"><?php echo $counts['returned']; ?></span>
        </a>
        <a href="?tab=approved" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition <?php echo ($currentTab === 'approved') ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'; ?>">
            <i class="fa-solid fa-check"></i>
            <span>Approved</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo ($currentTab === 'approved') ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>"><?php echo $counts['approved']; ?></span>
        </a>
        <a href="?tab=rejected" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition <?php echo ($currentTab === 'rejected') ? 'bg-red-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'; ?>">
            <i class="fa-solid fa-xmark"></i>
            <span>Rejected</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo ($currentTab === 'rejected') ? 'bg-white/20 text-white' : 'bg-red-50 text-red-700 border border-red-200'; ?>"><?php echo $counts['rejected']; ?></span>
        </a>
        <a href="?tab=all" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition <?php echo ($currentTab === 'all') ? 'bg-slate-800 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'; ?>">
            <i class="fa-solid fa-list-ul"></i>
            <span>All Submissions</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo ($currentTab === 'all') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'; ?>"><?php echo $counts['total_all']; ?></span>
        </a>
    </div>

    <!-- Main Content Area: Full-Width Search, Filters & Table -->
    <div class="w-full flex flex-col gap-5">

        <!-- Filter Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <!-- Top Search & Toggle -->
            <div id="filterSearchRow" class="flex flex-col md:flex-row gap-3 justify-between items-start md:items-center">
                <div class="flex-1 w-full flex items-center gap-2">
                    <div class="relative w-full">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input id="searchInputPending" type="text" placeholder="Search by Applicant Name, ID, Barangay, or Document Number..." class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1] block pl-10 pr-4 py-2.5 outline-none font-medium placeholder-slate-400">
                    </div>
                    <button id="searchBtnPending" class="shrink-0 px-4 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-search text-[10px]"></i>
                        <span>Search</span>
                    </button>
                </div>
                <button id="toggleFilterBtn" onclick="togglePendingFilterGrid()" class="shrink-0 flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold text-slate-700 bg-slate-100/70 hover:bg-slate-200/70 rounded-xl transition cursor-pointer">
                    <i class="fa-solid fa-sliders text-xs"></i>
                    <span id="toggleFilterBtnText">Show Filters</span>
                    <i id="toggleFilterBtnChevron" class="fa-solid fa-chevron-down text-[9px] ml-0.5"></i>
                </button>
            </div>

            <!-- Filters Grid (Hidden by Default) -->
            <div id="filterGrid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase">District</label>
                    <select id="districtFilterPending" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium cursor-pointer">
                        <option value="">All Districts</option>
                        <option value="District 1">District 1</option>
                        <option value="District 2">District 2</option>
                        <option value="District 3">District 3</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Valid ID Type</label>
                    <select id="idTypeFilterPending" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                        <option value="">All Document Types</option>
                        <option value="PhilSys National ID">PhilSys National ID</option>
                        <option value="Driver's License">Driver's License</option>
                        <option value="UMID">UMID</option>
                        <option value="Passport">Passport</option>
                    </select>
                </div>

                <div class="flex items-end justify-end">
                    <button id="clearFiltersBtnPending" onclick="clearFilters()" class="w-full py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            
            <!-- Table Header Actions Bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 border-b border-slate-100 gap-3">
                <span id="applicationsFoundText" class="text-xs font-bold text-slate-800"><?php echo count($applications); ?> applications in <?php echo ucfirst($currentTab); ?></span>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-medium">Viewing queue for:</span>
                    <span class="text-xs font-bold text-[#0f53d1] uppercase"><?php echo htmlspecialchars($currentTab); ?></span>
                </div>
            </div>

            <!-- Table Wrapper -->
            <div class="overflow-x-auto w-full custom-scrollbar">
                <table class="w-full text-left border-collapse whitespace-nowrap min-w-[950px]">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ref ID</th>
                                                        <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Applicant Name</th>
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Submitted</th>
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">District / Barangay</th>
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assets</th>
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Inspect</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="pendingTableBody">
                        <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-regular fa-folder-open text-4xl text-slate-300"></i>
                                    <p class="font-bold text-slate-700 text-sm">No records found in "<?php echo ucfirst($currentTab); ?>"</p>
                                    <p class="text-xs text-slate-400">Applications submitted from the citizen mobile app will appear here.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                        <tr onclick="selectPendingApplication(this)" 
                            class="pending-app-row hover:bg-slate-50/80 transition cursor-pointer" 
                            data-district="<?php echo htmlspecialchars($app['district']); ?>"
                            data-app='<?php echo htmlspecialchars(json_encode($app), ENT_QUOTES, "UTF-8"); ?>'
                            id="row-<?php echo $app['raw_id']; ?>">
                            <td class="p-3.5 text-xs font-bold text-slate-700"><?php echo $app['id']; ?></td>
                            <td class="p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <img src="<?php echo $app['avatar']; ?>" class="w-7 h-7 rounded-full border border-slate-200 shrink-0" alt="Avatar">
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block"><?php echo htmlspecialchars($app['applicant']); ?></span>
                                        <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($app['valid_id_type']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <div class="text-xs font-medium text-slate-700"><?php echo $app['date']; ?></div>
                                <div class="text-[10px] text-slate-400"><?php echo $app['time']; ?></div>
                            </td>
                            <td class="p-3.5 text-xs text-slate-600 font-medium">
                                <span class="font-bold text-slate-800"><?php echo htmlspecialchars($app['district']); ?></span>
                                <span class="block text-[10px] text-slate-400"><?php echo htmlspecialchars($app['barangay']); ?></span>
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center gap-1">
                                    <div class="w-6 h-6 rounded bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-500" title="Valid ID"><i class="fa-solid fa-id-card text-[10px]"></i></div>
                                    <div class="w-6 h-6 rounded bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-500" title="Selfie Photo"><i class="fa-solid fa-camera text-[10px]"></i></div>
                                    <?php if (!empty($app['photo_1x1_url'])): ?>
                                    <div class="w-6 h-6 rounded bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600" title="1x1 Photo"><i class="fa-regular fa-image text-[10px]"></i></div>
                                    <?php endif; ?>
                                    <?php if (!empty($app['signature_photo_url'])): ?>
                                    <div class="w-6 h-6 rounded bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600" title="Digital Signature"><i class="fa-solid fa-signature text-[10px]"></i></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <span id="badge-<?php echo $app['raw_id']; ?>" class="px-2 py-0.5 text-[10px] font-bold rounded-md border <?php echo getAppStatusBadge($app['status']); ?>">
                                    <?php echo $app['status']; ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="selectPendingApplication(this.closest('tr'))" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-[#0f53d1] hover:text-white flex items-center justify-center text-slate-500 transition cursor-pointer shadow-2xs" title="Inspect Application in Modal">
                                        <i class="fa-solid fa-expand text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="p-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span id="pendingPaginationInfo" class="text-xs text-slate-500 font-medium">Showing <?php echo count($applications); ?> entries</span>
                <div id="pendingPaginationControls" class="flex items-center gap-1 text-xs"></div>
            </div>

        </div>

    </div>

</main>

<!-- ============================================================================== -->
<!-- APPLICANT VERIFICATION & APPROVAL DETAILS MODAL                                -->
<!-- ============================================================================== -->
<div id="pendingDetailDrawer" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
        
        <!-- Modal Light Header with Back button, App ID, Status badge, and Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closePendingDrawer()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Verification Queue">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Application Review</h3>
                        <span id="drawerAppId" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">VER-0001</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Citizen Identity Verification & Credential Inspector</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="drawerAppStatus" class="px-2.5 py-1 text-[10px] font-bold rounded-md bg-amber-50 text-amber-600 border border-amber-200/80">Pending</span>
                <button type="button" onclick="closePendingDrawer()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center px-6 border-b border-slate-200 bg-slate-50/60 shrink-0">
            <button id="drawerTabReviewBtn" onclick="switchDrawerTab('review')" class="px-4 py-2.5 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1] transition cursor-pointer">Applicant Details</button>
            <button id="drawerTabActivityBtn" onclick="switchDrawerTab('activity')" class="px-4 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer flex items-center gap-1.5">
                <span>Audit History</span>
            </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
            
            <!-- Review Tab Content -->
            <div id="drawerTabReviewContent" class="space-y-4">
                
                <!-- Applicant Profile Header Card -->
                <div class="flex items-center gap-3.5 p-4 bg-slate-50/80 rounded-2xl border border-slate-100">
                    <div class="relative">
                        <img id="drawerApplicantAvatar" src="" class="w-14 h-14 rounded-full border border-slate-200 shrink-0 object-cover" alt="Applicant">
                        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white"></span>
                    </div>
                    <div>
                        <h4 id="drawerApplicantName" class="text-base font-black text-slate-900">Danny Espelita</h4>
                        <p class="text-xs text-slate-500 font-medium" id="drawerBarangayDistrict">Barangay 171, District 1</p>
                    </div>
                </div>


                <!-- Admin Action / Rework Notes Box (Shown when present) -->
                <div id="drawerAdminNotesBox" class="hidden p-4 rounded-2xl bg-orange-50 border border-orange-200 space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-orange-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="drawerAdminNotesTitle">Rework Notes / Decision Remarks</span>
                    </span>
                    <p id="drawerAdminNotesContent" class="text-xs text-orange-950 font-medium whitespace-pre-wrap"></p>
                </div>

                <!-- Personal Information Summary Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                    <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-user text-[#0f53d1]"></i>
                        <span>Personal Details</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Birthdate</span>
                            <span id="drawerBirthdate" class="font-bold text-slate-800 text-xs">May 15, 1998</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Sex / Gender</span>
                            <span id="drawerSex" class="font-bold text-slate-800 text-xs">Male</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Civil Status</span>
                            <span id="drawerCivilStatus" class="font-bold text-slate-800 text-xs">Single</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Residency Duration</span>
                            <span id="drawerYearsResident" class="font-bold text-slate-800 text-xs">12 year(s)</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Address</span>
                        <span id="drawerAddress" class="font-bold text-slate-800 block">Block 12 Lot 5, Sampaguita St.</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Valid ID Submitted</span>
                        <span id="drawerValidIdType" class="font-bold text-slate-800">PhilSys National ID</span>
                        <span id="drawerValidIdNumber" class="text-slate-500 font-mono text-[11px] ml-1">1234-5678-9012-3456</span>
                    </div>
                </div>

                <!-- Submitted Assets: 4 Cards (ID Front, Selfie, 1x1 Photo, Digital Signature) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase flex items-center gap-2">
                            <i class="fa-solid fa-id-card-clip text-indigo-600"></i>
                            <span>Verification Assets</span>
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400">4 Credentials</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <!-- Valid ID Front -->
                        <div class="flex flex-col items-center gap-1.5 text-center p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div id="drawerIdPhotoBox" class="w-full h-28 bg-slate-200 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                <i class="fa-solid fa-id-card text-2xl text-slate-400"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-700">Valid ID (Front)</span>
                        </div>

                        <!-- Selfie Verification -->
                        <div class="flex flex-col items-center gap-1.5 text-center p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div id="drawerSelfiePhotoBox" class="w-full h-28 bg-slate-200 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                <i class="fa-solid fa-camera text-2xl text-slate-400"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-700">Selfie Liveness</span>
                        </div>

                        <!-- 1x1 Photo -->
                        <div class="flex flex-col items-center gap-1.5 text-center p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div id="drawerPhoto1x1Box" class="w-full h-28 bg-slate-200 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                <i class="fa-regular fa-image text-2xl text-slate-400"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-700">1x1 ID Photo</span>
                        </div>

                        <!-- Digital Signature -->
                        <div class="flex flex-col items-center gap-1.5 text-center p-2.5 rounded-xl bg-white border border-slate-200">
                            <div id="drawerSignatureBox" class="w-full h-28 bg-white rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                <i class="fa-solid fa-signature text-2xl text-slate-400"></i>
                            </div>
                            <span class="text-[11px] font-bold text-slate-700">Digital Signature</span>
                        </div>
                    </div>
                </div>

                <!-- 1. Dedicated Rejection Record Panel (Displayed when app.status === 'Rejected') -->
                <div id="drawerRejectedNoticeBox" class="hidden p-4 rounded-2xl bg-rose-50/80 border border-rose-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-sm shadow-2xs">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-rose-950 uppercase tracking-wide">Application Formally Rejected</h4>
                                <p class="text-[10px] text-rose-600 font-medium">Archived decision • Action controls locked</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-rose-200/80 text-rose-800 border border-rose-300 shadow-2xs">
                            Concluded / Rejected
                        </span>
                    </div>

                    <!-- Rejection Reason Card -->
                    <div class="bg-white p-3.5 rounded-xl border border-rose-200 shadow-2xs space-y-1">
                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-500 block flex items-center gap-1.5">
                            <i class="fa-solid fa-ban text-[10px]"></i>
                            <span>Recorded Rejection Reason</span>
                        </span>
                        <p id="drawerRejectionReasonText" class="text-xs font-bold text-slate-800 whitespace-pre-wrap leading-relaxed">No formal reason specified.</p>
                    </div>

                    <!-- Reviewer and Timestamp -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[10px] text-rose-900 pt-1 border-t border-rose-200/60 font-medium">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-user-shield text-rose-500"></i>
                            <span>Reviewed by: <strong id="drawerRejectedByText" class="text-rose-950 font-bold">Admin</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-rose-700">
                            <i class="fa-regular fa-clock text-rose-500"></i>
                            <span id="drawerRejectedAtText"></span>
                        </div>
                    </div>

                    <p class="text-[10.5px] text-slate-500 bg-white/70 p-2.5 rounded-xl border border-rose-100 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-rose-400 mr-1"></i>
                        Staff decision actions are locked for this record because it has already been processed and rejected. If the citizen submits a new registration, it will arrive as a fresh submission in the <strong>Pending Review</strong> queue.
                    </p>
                </div>

                <!-- 2. Dedicated Approved Record Panel (Displayed when app.status === 'Approved') -->
                <div id="drawerApprovedNoticeBox" class="hidden p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm shadow-2xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-emerald-950 uppercase tracking-wide">Citizenship Verified</h4>
                                <p class="text-[10px] text-emerald-600 font-medium">Citizen Identity & Residency Authenticated</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-200/80 text-emerald-800 border border-emerald-300 shadow-2xs">
                            Verified Resident
                        </span>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 block">Resident Verification Ref</span>
                            <span id="drawerApprovedCitizenId" class="text-sm font-black font-mono text-emerald-950">VER-0001</span>
                        </div>
                        <div class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 flex items-center gap-1">
                            <i class="fa-solid fa-user-check text-xs"></i>
                            <span>Citizenship Approved</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[10px] text-emerald-900 pt-1 border-t border-emerald-200/60 font-medium">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-user-check text-emerald-600"></i>
                            <span>Approved by: <strong id="drawerApprovedByText" class="text-emerald-950 font-bold">Admin</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-emerald-700">
                            <i class="fa-regular fa-clock text-emerald-600"></i>
                            <span id="drawerApprovedAtText"></span>
                        </div>
                    </div>
                </div>

                <!-- 3. Dedicated Superseded Record Panel (Displayed when app.status === 'Superseded') -->
                <div id="drawerSupersededNoticeBox" class="hidden p-4 rounded-2xl bg-slate-100/90 border border-slate-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm shadow-2xs">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">Application Superseded</h4>
                                <p class="text-[10px] text-slate-500 font-medium">Replaced by subsequent approved registration</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-200 text-slate-700 border border-slate-300 shadow-2xs">
                            Archived / Superseded
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 bg-white p-3.5 rounded-xl border border-slate-200 leading-relaxed">
                        This prior registration submission was superseded after the citizen re-submitted their registration and was subsequently approved. It is retained strictly for historical audit trail purposes and has been removed from active rejected tallies.
                    </p>
                </div>

                <!-- 4. Staff Decision Action Buttons Grid (Displayed ONLY for pending/under review/rework applications) -->
                <div id="drawerDecisionActionsBox" class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Staff Decision Actions</span>
                    <div class="grid grid-cols-3 gap-2.5">
                        <button id="btnApproveApp" onclick="handleApproveApplication()" class="py-2.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-check"></i>
                            <span>Approve</span>
                        </button>

                        <button id="btnReturnApp" onclick="openDecisionModal('return')" class="py-2.5 px-2 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Return</span>
                        </button>

                        <button id="btnRejectApp" onclick="openDecisionModal('reject')" class="py-2.5 px-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-xmark"></i>
                            <span>Reject</span>
                        </button>
                    </div>
                </div>

            </div>
            
            <!-- Activity History Tab Content -->
            <div id="drawerTabActivityContent" class="hidden space-y-3.5 pt-1 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Application Audit Trail</span>
                    <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">System Logged</span>
                </div>
                <div id="drawerAuditTrailTimeline" class="relative pl-5 border-l-2 border-blue-100 space-y-4 ml-2">
                    <div class="relative">
                        <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-[#0f53d1] border-2 border-white ring-2 ring-blue-100"></span>
                        <p class="font-bold text-slate-800 text-xs">Application Received</p>
                        <p class="text-[10px] text-slate-400">Submitted by citizen via CivCentral Mobile App.</p>
                        <span id="drawerSubmittedTimestamp" class="text-[9px] font-medium text-slate-400">Mobile Submission</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Bottom Navigation (Back & Close Buttons) -->
        <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
            <button type="button" onclick="closePendingDrawer()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Verification Queue</span>
            </button>
            <button type="button" onclick="closePendingDrawer()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>

    </div>
</div>

<!-- DECISION MODAL: RETURN FOR CORRECTION OR REJECT -->
<div id="decisionModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div id="modalHeader" class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div id="modalIconBox" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                    <i id="modalIcon" class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <h3 id="modalTitle" class="text-sm font-black text-slate-800 tracking-tight">Return Application for Correction</h3>
                    <p id="modalSubtitle" class="text-xs text-slate-400 mt-0.5">The citizen will be notified to revise their submission.</p>
                </div>
            </div>
            <button onclick="closeDecisionModal()" class="text-slate-400 hover:text-slate-600 transition p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <span class="text-slate-500 font-medium">Applicant:</span>
                <span id="modalApplicantName" class="font-bold text-slate-800 ml-1">Danny Espelita</span>
                <span class="mx-2 text-slate-300">•</span>
                <span class="text-slate-500 font-medium">Ref:</span>
                <span id="modalApplicantRef" class="font-bold text-[#0f53d1] ml-1">VER-0001</span>
            </div>

            <!-- Quick Reason Tags for Return -->
            <div id="quickTagsContainer" class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Quick Feedback Templates</label>
                <div class="flex flex-wrap gap-1.5 text-xs">
                    <button type="button" onclick="appendModalTag('ID Photo is blurry or unreadable.')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition cursor-pointer text-[11px]">+ Blurry ID</button>
                    <button type="button" onclick="appendModalTag('1x1 Photo does not meet formal requirements (needs white background).')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition cursor-pointer text-[11px]">+ 1x1 Photo Issue</button>
                    <button type="button" onclick="appendModalTag('Digital signature is missing or illegible.')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition cursor-pointer text-[11px]">+ Illegible Signature</button>
                    <button type="button" onclick="appendModalTag('Address details require complete house / street number.')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition cursor-pointer text-[11px]">+ Incomplete Address</button>
                </div>
            </div>

            <!-- Detailed Notes Textarea -->
            <div class="space-y-1.5">
                <label id="modalNotesLabel" class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">Instructions for Applicant <span class="text-red-500">*</span></label>
                <textarea id="modalNotesTextarea" rows="4" placeholder="Detail the exact corrections or documents the applicant must re-submit..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1] font-medium resize-none"></textarea>
                <p id="modalNotesHelp" class="text-[10px] text-slate-400">These notes will be displayed directly to the applicant on their mobile app.</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button onclick="closeDecisionModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                Cancel
            </button>
            <button id="modalConfirmBtn" onclick="submitDecisionModal()" class="px-4 py-2 text-xs font-bold text-white rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-paper-plane text-[10px]"></i>
                <span id="modalConfirmText">Confirm Decision</span>
            </button>
        </div>
    </div>
</div>

<script>
let activeApp = null;
let currentModalMode = 'return'; // 'return' or 'reject'
const applications = <?php echo json_encode($applications); ?>;

function showToast(type, html) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        document.body.appendChild(toast);
    }
    toast.className = `fixed bottom-5 right-5 z-[9999] px-4 py-3 rounded-xl shadow-lg text-xs font-bold transition-all transform flex items-center gap-2 ${
        type === 'success' ? 'bg-emerald-600 text-white shadow-emerald-500/20' : 
        type === 'error' ? 'bg-rose-600 text-white shadow-rose-500/20' : 
        'bg-slate-900 text-white'
    }`;
    toast.innerHTML = html;
    toast.classList.remove('translate-y-10', 'opacity-0');
    setTimeout(() => {
        toast.classList.add('translate-y-10', 'opacity-0');
    }, 3500);
}

document.addEventListener('DOMContentLoaded', function () {
    const districtFilter = document.getElementById('districtFilterPending');
    const idTypeFilter = document.getElementById('idTypeFilterPending');
    const searchInput = document.getElementById('searchInputPending');
    const rows = document.querySelectorAll('tbody tr.pending-app-row');

    function applyFilters() {
        const selectedDistrict = districtFilter ? districtFilter.value.trim().toLowerCase() : '';
        const selectedIdType = idTypeFilter ? idTypeFilter.value.trim().toLowerCase() : '';
        const searchVal = searchInput ? searchInput.value.trim().toLowerCase() : '';

        rows.forEach(row => {
            const rowDataStr = row.getAttribute('data-app') || '';
            let appObj = {};
            try { appObj = JSON.parse(rowDataStr); } catch (e) {}

            const district = (appObj.district || '').toLowerCase();
            const idType = (appObj.valid_id_type || '').toLowerCase();
            const textContent = (appObj.applicant + ' ' + appObj.id + ' ' + appObj.barangay + ' ' + (appObj.citizen_id_number || '')).toLowerCase();

            const matchDistrict = !selectedDistrict || district.includes(selectedDistrict);
            const matchIdType = !selectedIdType || idType.includes(selectedIdType);
            const matchSearch = !searchVal || textContent.includes(searchVal);

            if (matchDistrict && matchIdType && matchSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (districtFilter) districtFilter.addEventListener('change', applyFilters);
    if (idTypeFilter) idTypeFilter.addEventListener('change', applyFilters);
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    window.clearFilters = function () {
        if (districtFilter) districtFilter.value = '';
        if (idTypeFilter) idTypeFilter.value = '';
        if (searchInput) searchInput.value = '';
        applyFilters();
    };
});

function togglePendingFilterGrid() {
    const grid = document.getElementById('filterGrid');
    const btnText = document.getElementById('toggleFilterBtnText');
    const chevron = document.getElementById('toggleFilterBtnChevron');
    if (grid.classList.contains('hidden')) {
        grid.classList.remove('hidden');
        btnText.textContent = 'Hide Filters';
        chevron.classList.add('rotate-180');
    } else {
        grid.classList.add('hidden');
        btnText.textContent = 'Show Filters';
        chevron.classList.remove('rotate-180');
    }
}

function selectPendingApplication(rowElement) {
    const rawData = rowElement.getAttribute('data-app');
    if (!rawData) return;
    const app = JSON.parse(rawData);
    const drawer = document.getElementById('pendingDetailDrawer');

    if (activeApp && activeApp.raw_id === app.raw_id && !drawer.classList.contains('hidden')) {
        closePendingDrawer();
        return;
    }

    activeApp = app;
    window.currentSelectedCitizen = app;
    document.querySelectorAll('.pending-app-row').forEach(r => {
        r.classList.remove('bg-blue-50/50');
    });
    rowElement.classList.add('bg-blue-50/50');

    // Populate drawer elements
    document.getElementById('drawerAppId').textContent = app.id;
    document.getElementById('drawerApplicantName').textContent = app.applicant;
    document.getElementById('drawerApplicantAvatar').src = app.avatar;
    
    const statusSpan = document.getElementById('drawerAppStatus');
    if (statusSpan) {
        statusSpan.textContent = app.status;
        statusSpan.className = 'px-2 py-0.5 text-[10px] font-bold rounded-md border ' + 
            (app.status === 'Approved' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
             app.status === 'Rejected' ? 'bg-red-50 text-red-600 border-red-200' :
             app.status === 'Returned for Correction' ? 'bg-orange-50 text-orange-600 border-orange-200' :
             'bg-amber-50 text-amber-600 border-amber-200/80');
    }

    document.getElementById('drawerBirthdate').textContent = app.birth_date || 'N/A';
    document.getElementById('drawerSex').textContent = app.sex || 'N/A';
    document.getElementById('drawerCivilStatus').textContent = app.civil_status || 'N/A';
    document.getElementById('drawerYearsResident').textContent = (app.years_resident || 1) + ' year(s)';
    document.getElementById('drawerBarangayDistrict').textContent = (app.barangay ? app.barangay + ', ' : '') + (app.district || '');
    document.getElementById('drawerAddress').textContent = app.street_address || 'No street address provided';
    document.getElementById('drawerValidIdType').textContent = app.valid_id_type || 'Valid ID';
    document.getElementById('drawerValidIdNumber').textContent = app.valid_id_number || 'N/A';
    document.getElementById('drawerSubmittedTimestamp').textContent = app.date + ' at ' + app.time;

    // Helper to format photo paths
    const API_BASE_URL = <?php echo json_encode($apiBase); ?>;
    function formatPhotoSrc(url) {
        if (!url || typeof url !== 'string') return null;
        url = url.trim();
        if (!url || url.startsWith('blob:')) return null;

        if (url.startsWith('http://') || url.startsWith('https://')) {
            if (url.includes('api-citizen.civentral.tech')) {
                url = url.replace('api-citizen.civentral.tech', window.location.host);
            }
            return url;
        }
        if (url.startsWith('data:image/')) return url;

        const cleanPath = url.replace(/^\/+/, '');
        const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        if (isLocal) {
            if (cleanPath.startsWith('assets/')) return '../../' + cleanPath;
            if (cleanPath.startsWith('uploads/')) return '../../assets/' + cleanPath;
            return '../../assets/' + cleanPath;
        }
        if (cleanPath.startsWith('uploads/')) {
            return '/assets/' + cleanPath;
        }
        if (cleanPath.startsWith('assets/')) {
            return '/' + cleanPath;
        }
        return '/assets/uploads/' + cleanPath;
    }

    function renderImageCard(boxId, photoUrl, fallbackIcon, altText) {
        const box = document.getElementById(boxId);
        if (!box) return;
        const src = formatPhotoSrc(photoUrl);
        if (src) {
            box.innerHTML = `<a href="${src}" target="_blank" title="Click to view full size"><img src="${src}" class="w-full h-full object-cover rounded-lg hover:opacity-90 transition cursor-pointer" alt="${altText}" onerror="this.parentElement.innerHTML='<div class=\\'text-center p-2 text-slate-400\\'><i class=\\'${fallbackIcon} text-xl mb-1\\'></i><span class=\\'block text-[8px]\\'>Unavailable</span></div>';" /></a>`;
        } else {
            box.innerHTML = `<div class="text-center p-2 text-slate-400"><i class="${fallbackIcon} text-xl mb-1"></i><span class="block text-[8px]">Not uploaded</span></div>`;
        }
    }

    renderImageCard('drawerIdPhotoBox', app.id_front_photo_url, 'fa-solid fa-id-card', 'Valid ID');
    renderImageCard('drawerSelfiePhotoBox', app.selfie_photo_url, 'fa-solid fa-camera', 'Selfie');
    renderImageCard('drawerPhoto1x1Box', app.photo_1x1_url, 'fa-regular fa-image', '1x1 Photo');
    renderImageCard('drawerSignatureBox', app.signature_photo_url, 'fa-solid fa-signature', 'Digital Signature');


    // Admin Action Notes / Rejection Reason (if any)
    const notesBox = document.getElementById('drawerAdminNotesBox');
    const notesContent = app.admin_action_notes || app.rejection_reason;
    if (notesContent) {
        notesBox.classList.remove('hidden');
        document.getElementById('drawerAdminNotesTitle').textContent = 
            app.status === 'Returned for Correction' ? 'Rework Instructions for Citizen' : 
            app.status === 'Rejected' ? 'Rejection Reason' : 'Admin Remarks';
        document.getElementById('drawerAdminNotesContent').textContent = notesContent;
    } else {
        notesBox.classList.add('hidden');
    }

    // Status-Specific Panels & Action Control Switching
    const actionsBox = document.getElementById('drawerDecisionActionsBox');
    const rejectedBox = document.getElementById('drawerRejectedNoticeBox');
    const approvedBox = document.getElementById('drawerApprovedNoticeBox');
    const supersededBox = document.getElementById('drawerSupersededNoticeBox');

    if (actionsBox) actionsBox.classList.add('hidden');
    if (rejectedBox) rejectedBox.classList.add('hidden');
    if (approvedBox) approvedBox.classList.add('hidden');
    if (supersededBox) supersededBox.classList.add('hidden');

    if (app.status === 'Rejected') {
        // Application is rejected: Disable decision actions and show clean, official rejection summary
        if (rejectedBox) {
            rejectedBox.classList.remove('hidden');
            const rejReason = app.rejection_reason || app.admin_action_notes || 'Application did not meet identity verification criteria.';
            document.getElementById('drawerRejectionReasonText').textContent = rejReason;
            document.getElementById('drawerRejectedByText').textContent = app.reviewer || app.reviewed_by || 'Admin';
            document.getElementById('drawerRejectedAtText').textContent = app.reviewed_at || (app.date + ' ' + app.time);
        }
    } else if (app.status === 'Approved') {
        // Application is approved: Hide decision buttons and show official credential panel
        if (approvedBox) {
            approvedBox.classList.remove('hidden');
            document.getElementById('drawerApprovedCitizenId').textContent = app.id || ('VER-' + String(app.raw_id).padStart(4, '0'));
            document.getElementById('drawerApprovedByText').textContent = app.reviewer || app.reviewed_by || 'Admin';
            document.getElementById('drawerApprovedAtText').textContent = app.reviewed_at || (app.date + ' ' + app.time);
        }
    } else if (app.status === 'Superseded') {
        // Application is superseded: Replaced by subsequent approved registration
        if (supersededBox) {
            supersededBox.classList.remove('hidden');
        }
    } else {
        // Active queue: Pending Review, Under Review, or Returned for Correction
        if (actionsBox) actionsBox.classList.remove('hidden');
    }

    // Dynamic Audit Trail Timeline Population
    const timeline = document.getElementById('drawerAuditTrailTimeline');
    if (timeline) {
        let eventsHtml = `
            <div class="relative">
                <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-[#0f53d1] border-2 border-white ring-2 ring-blue-100"></span>
                <p class="font-bold text-slate-800 text-xs">Application Received</p>
                <p class="text-[10px] text-slate-400">Submitted by citizen via CivCentral Mobile App.</p>
                <span class="text-[9px] font-medium text-slate-400">${app.date} at ${app.time}</span>
            </div>
        `;

        const reviewerName = app.reviewer && app.reviewer !== 'Unassigned' ? app.reviewer : (app.reviewed_by || 'Admin');

        if (app.status === 'Approved') {
            eventsHtml += `
                <div class="relative">
                    <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-emerald-600 border-2 border-white ring-2 ring-emerald-100"></span>
                    <p class="font-bold text-emerald-800 text-xs">Application Officially Approved</p>
                    <p class="text-[10px] text-slate-500">Citizen verified and official Citizen ID issued by ${reviewerName}.</p>
                    <span class="text-[9px] font-medium text-slate-400">${app.reviewed_at || 'Approved'}</span>
                </div>
            `;
        } else if (app.status === 'Rejected') {
            eventsHtml += `
                <div class="relative">
                    <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-rose-600 border-2 border-white ring-2 ring-rose-100"></span>
                    <p class="font-bold text-rose-800 text-xs">Application Formally Rejected</p>
                    <p class="text-[10px] text-slate-500">Reviewed by ${reviewerName}: "${app.rejection_reason || app.admin_action_notes || 'Discrepancy detected'}". Actions concluded.</p>
                    <span class="text-[9px] font-medium text-slate-400">${app.reviewed_at || 'Rejected'}</span>
                </div>
            `;
        } else if (app.status === 'Returned for Correction') {
            eventsHtml += `
                <div class="relative">
                    <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-orange-500 border-2 border-white ring-2 ring-orange-100"></span>
                    <p class="font-bold text-orange-800 text-xs">Returned for Correction</p>
                    <p class="text-[10px] text-slate-500">Citizen requested by ${reviewerName} to update credential documents.</p>
                    <span class="text-[9px] font-medium text-slate-400">${app.reviewed_at || 'Awaiting Correction'}</span>
                </div>
            `;
        } else if (app.status === 'Superseded') {
            eventsHtml += `
                <div class="relative">
                    <span class="absolute -left-[27px] top-0.5 w-3.5 h-3.5 rounded-full bg-slate-500 border-2 border-white ring-2 ring-slate-200"></span>
                    <p class="font-bold text-slate-700 text-xs">Application Superseded</p>
                    <p class="text-[10px] text-slate-500">Prior rejected attempt superseded after applicant re-submitted and was approved.</p>
                    <span class="text-[9px] font-medium text-slate-400">Archived Record</span>
                </div>
            `;
        }

        timeline.innerHTML = eventsHtml;
    }

    drawer.classList.remove('hidden');
    drawer.classList.add('flex');
}

function closePendingDrawer() {
    activeApp = null;
    document.querySelectorAll('.pending-app-row').forEach(r => {
        r.classList.remove('bg-blue-50/50');
    });
    const drawer = document.getElementById('pendingDetailDrawer');
    if (drawer) {
        drawer.classList.add('hidden');
        drawer.classList.remove('flex');
    }
}

function refreshQueue() {
    const icon = document.getElementById('refreshQueueIcon');
    if (icon) icon.classList.add('fa-spin');
    setTimeout(() => {
        window.location.reload();
    }, 350);
}

function exportPendingListCSV() {
    if (!applications || applications.length === 0) {
        alert('No applications in the queue to export.');
        return;
    }

    const headers = ['Ref ID', 'Citizen ID', 'Applicant Name', 'Sex', 'District', 'Barangay', 'Valid ID Type', 'Status', 'Date'];
    const rows = applications.map(app => [
        `"${app.id}"`,
        `"${app.citizen_id_number || ''}"`,
        `"${(app.applicant || '').replace(/"/g, '""')}"`,
        `"${app.sex || ''}"`,
        `"${app.district || ''}"`,
        `"${app.barangay || ''}"`,
        `"${app.valid_id_type || ''}"`,
        `"${app.status || ''}"`,
        `"${app.date}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const link = document.createElement("a");
    link.setAttribute("href", encodeURI(csvContent));
    link.setAttribute("download", `citizen_verifications_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// APPROVE APPLICATION
async function handleApproveApplication() {
    if (!activeApp) return;
    if (!confirm(`Are you sure you want to APPROVE citizenship verification for ${activeApp.applicant}?\n\nThis will verify their resident status and citizenship.`)) return;

    const btn = document.getElementById('btnApproveApp');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Approving...';
    }

    try {
        const res = await fetch('../../api/admin/review-citizen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                verification_id: activeApp.raw_id,
                citizen_user_id: activeApp.citizen_user_id,
                action: 'approve',
                reviewed_by: 'Admin'
            })
        });
        const result = await res.json();
        if (result.status === 'success') {
            activeApp.citizen_id_number = result.citizen_id_number;
            activeApp.qr_code_token = result.qr_code_token;
            activeApp.qr_code_image_url = result.qr_code_image_url;
            activeApp.status = 'Approved';
            
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Approved</span>';
                btn.className = 'py-2.5 px-2 bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center gap-1 cursor-default';
            }

            // Update drawer status badge if present
            const statusSpan = document.getElementById('drawerAppStatus');
            if (statusSpan) {
                statusSpan.textContent = 'Approved';
                statusSpan.className = 'px-2 py-0.5 text-[10px] font-bold rounded-md border bg-emerald-50 text-emerald-600 border-emerald-200';
            }

            // Switch to Approved status panel and lock action controls
            const actionsBox = document.getElementById('drawerDecisionActionsBox');
            if (actionsBox) actionsBox.classList.add('hidden');

            const approvedBox = document.getElementById('drawerApprovedNoticeBox');
            if (approvedBox) {
                approvedBox.classList.remove('hidden');
                document.getElementById('drawerApprovedCitizenId').textContent = activeApp.id || ('VER-' + String(activeApp.raw_id).padStart(4, '0'));
                document.getElementById('drawerApprovedByText').textContent = 'Admin';
                document.getElementById('drawerApprovedAtText').textContent = result.reviewed_at || 'Just now';
            }

            showToast('success', 'Citizenship verification successfully approved!');
        } else {
            alert('Error: ' + (result.message || 'Failed to approve application.'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Approve</span>';
            }
        }
    } catch (e) {
        alert('Network error connecting to API');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Approve</span>';
        }
    }
}

// DECISION MODAL CONTROLLERS (RETURN & REJECT)
function openDecisionModal(mode) {
    if (!activeApp) {
        alert('Please select an applicant from the table first.');
        return;
    }
    currentModalMode = mode;

    document.getElementById('modalApplicantName').textContent = activeApp.applicant;
    document.getElementById('modalApplicantRef').textContent = activeApp.id;

    const iconBox = document.getElementById('modalIconBox');
    const icon = document.getElementById('modalIcon');
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');
    const tagsContainer = document.getElementById('quickTagsContainer');
    const notesLabel = document.getElementById('modalNotesLabel');
    const textarea = document.getElementById('modalNotesTextarea');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    const confirmText = document.getElementById('modalConfirmText');

    textarea.value = '';

    if (mode === 'return') {
        iconBox.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-orange-100 text-orange-600';
        icon.className = 'fa-solid fa-rotate-left';
        title.textContent = 'Return Application for Correction';
        subtitle.textContent = 'The citizen will be notified to revise their information or re-upload documents.';
        tagsContainer.classList.remove('hidden');
        notesLabel.innerHTML = 'Required Corrections / Instructions <span class="text-red-500">*</span>';
        textarea.placeholder = 'Explain specifically which documents or fields need correction...';
        confirmBtn.className = 'px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5';
        confirmText.textContent = 'Send Return Request';
    } else {
        iconBox.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-red-100 text-red-600';
        icon.className = 'fa-solid fa-xmark';
        title.textContent = 'Permanent Application Rejection';
        subtitle.textContent = 'Citizen verification will be formally rejected.';
        tagsContainer.classList.add('hidden');
        notesLabel.innerHTML = 'Official Rejection Reason <span class="text-red-500">*</span>';
        textarea.placeholder = 'Provide the official administrative reason for rejecting this application...';
        confirmBtn.className = 'px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5';
        confirmText.textContent = 'Confirm Rejection';
    }

    document.getElementById('decisionModal').classList.remove('hidden');
}

function closeDecisionModal() {
    document.getElementById('decisionModal').classList.add('hidden');
}

function appendModalTag(text) {
    const textarea = document.getElementById('modalNotesTextarea');
    if (textarea.value.trim().length > 0) {
        textarea.value += '\n• ' + text;
    } else {
        textarea.value = '• ' + text;
    }
}

async function submitDecisionModal() {
    if (!activeApp) return;
    const textarea = document.getElementById('modalNotesTextarea');
    const notes = textarea.value.trim();

    if (!notes) {
        alert('Please enter instructions or reasons before proceeding.');
        textarea.focus();
        return;
    }

    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

    const payload = {
        verification_id: activeApp.raw_id,
        citizen_user_id: activeApp.citizen_user_id,
        action: currentModalMode, // 'return' or 'reject'
        admin_action_notes: notes,
        rejection_reason: notes,
        reviewed_by: 'Admin'
    };

    try {
        const res = await fetch('../../api/admin/review-citizen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            alert(currentModalMode === 'return' 
                ? `Application for ${activeApp.applicant} has been RETURNED for correction. The citizen will see your notes on their mobile dashboard.`
                : `Application for ${activeApp.applicant} has been REJECTED.`);
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Operation failed.'));
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-paper-plane text-[10px]"></i> Confirm';
        }
    } catch (e) {
        alert('Network error connecting to review API.');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fa-solid fa-paper-plane text-[10px]"></i> Confirm';
    }
}

function switchDrawerTab(tab) {
    const reviewBtn = document.getElementById('drawerTabReviewBtn');
    const activityBtn = document.getElementById('drawerTabActivityBtn');
    const reviewContent = document.getElementById('drawerTabReviewContent');
    const activityContent = document.getElementById('drawerTabActivityContent');

    if (tab === 'activity') {
        reviewBtn.className = 'px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer';
        activityBtn.className = 'px-4 py-2 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1] transition cursor-pointer flex items-center gap-1.5';
        reviewContent.classList.add('hidden');
        activityContent.classList.remove('hidden');
    } else {
        reviewBtn.className = 'px-4 py-2 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1] transition cursor-pointer';
        activityBtn.className = 'px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer flex items-center gap-1.5';
        reviewContent.classList.remove('hidden');
        activityContent.classList.add('hidden');
    }
}


// Modal dismiss listeners (Backdrop click and Escape key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const decModal = document.getElementById('decisionModal');
        if (decModal && !decModal.classList.contains('hidden')) {
            closeDecisionModal();
            return;
        }
        closePendingDrawer();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pendingDetailDrawer');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePendingDrawer();
            }
        });
    }

    const decModal = document.getElementById('decisionModal');
    if (decModal) {
        decModal.addEventListener('click', function(e) {
            if (e.target === decModal) {
                closeDecisionModal();
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
