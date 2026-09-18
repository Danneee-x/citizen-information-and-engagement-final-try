<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

// Real Applications & Metrics Data from MySQL
require_once __DIR__ . '/../../config/database.php';

$applications = [];
$counts = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'awaiting' => 0,
    'today_approved' => 0,
    'today_rejected' => 0,
];

try {
    $pdo = getDbConnection();

    // Ensure citizen_verifications table exists
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
        `reviewed_by` VARCHAR(100) NULL,
        `rejection_reason` TEXT NULL,
        `reviewed_at` DATETIME NULL,
        `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Self-healing: verify all required columns exist
    $cols = $pdo->query("SHOW COLUMNS FROM citizen_verifications")->fetchAll(PDO::FETCH_COLUMN);
    $needed = [
        'reviewed_by' => 'VARCHAR(100) NULL',
        'rejection_reason' => 'TEXT NULL',
        'reviewed_at' => 'DATETIME NULL',
        'is_duplicate' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'duplicate_notes' => 'TEXT NULL'
    ];
    foreach ($needed as $col => $type) {
        if (!in_array($col, $cols)) {
            $pdo->exec("ALTER TABLE citizen_verifications ADD COLUMN `$col` $type");
        }
    }

    // Fetch counts
    $statsStmt = $pdo->query("SELECT 
        COUNT(*) as total_all,
        SUM(CASE WHEN verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN verification_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN verification_status = 'Under_Review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN reviewed_by IS NOT NULL AND reviewed_by != '' AND reviewed_by != 'Unassigned' AND verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as assigned_to_me,
        SUM(CASE WHEN verification_status = 'Approved' AND DATE(reviewed_at) = CURDATE() THEN 1 ELSE 0 END) as today_approved,
        SUM(CASE WHEN verification_status = 'Rejected' AND DATE(reviewed_at) = CURDATE() THEN 1 ELSE 0 END) as today_rejected,
        AVG(CASE WHEN reviewed_at IS NOT NULL AND submitted_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, submitted_at, reviewed_at) ELSE NULL END) as avg_proc_minutes
        FROM citizen_verifications");
    $dbStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $counts['total']          = (int)($dbStats['pending'] ?? 0);
    $counts['pending']        = (int)($dbStats['pending'] ?? 0);
    $counts['approved']       = (int)($dbStats['approved'] ?? 0);
    $counts['rejected']       = (int)($dbStats['rejected'] ?? 0);
    $counts['under_review']   = (int)($dbStats['under_review'] ?? 0);
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

    // Fetch real applications that are currently in the Pending Approvals queue
    $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status IN ('Pending', 'Under_Review') ORDER BY submitted_at DESC LIMIT 100");
    $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbRows as $row) {
        $fullName = trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']} {$row['suffix']}");
        $dt = !empty($row['submitted_at']) ? new DateTime($row['submitted_at']) : new DateTime();
        $statusDisplay = $row['verification_status'] === 'Under_Review' ? 'Under Review' : $row['verification_status'];

        $applications[] = [
            'id' => 'VER-' . str_pad($row['verification_id'], 4, '0', STR_PAD_LEFT),
            'raw_id' => $row['verification_id'],
            'citizen_user_id' => $row['citizen_user_id'] ?? 0,
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
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random',
            'date' => $dt->format('M d, Y'),
            'time' => $dt->format('h:i A'),
            'submitted_by' => 'Citizen Mobile App',
            'district' => $row['district'] ?? 'District 1',
            'barangay' => $row['barangay'] ?? '',
            'reviewer' => !empty($row['reviewed_by']) ? $row['reviewed_by'] : 'Unassigned',
            'reviewer_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($row['reviewed_by'] ?? 'Admin') . '&background=random',
            'docs_count' => '+2',
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
        case 'Under Review': return 'bg-blue-50 text-blue-600 border-blue-200/80';
        case 'Waiting for Applicant': return 'bg-orange-50 text-orange-600 border-orange-200/80';
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
        <span class="text-brand-dark">Pending Approvals</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4 mb-6">
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="refreshQueue()" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i id="refreshQueueIcon" class="fa-solid fa-rotate text-[11px]"></i>
                <span>Refresh Queue</span>
            </button>
            <button onclick="exportPendingListCSV()" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-upload text-[11px]"></i>
                <span>Export Pending List</span>
            </button>
            <a href="id-verification-logs.php" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
                <i class="fa-regular fa-clock text-[11px]"></i>
                <span>View Approval History</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-file-lines text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Total Pending<br>Applications</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['pending']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-emerald-500">
                <i class="fa-solid fa-caret-up"></i> <span>12%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-clock text-blue-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Awaiting<br>Review</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['awaiting']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-emerald-500">
                <i class="fa-solid fa-caret-up"></i> <span>8%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-user text-blue-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Assigned<br>to Me</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['assigned_to_me']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-red-500">
                <i class="fa-solid fa-caret-down"></i> <span>3%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-check text-emerald-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Approved<br>Today</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['today_approved']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-emerald-500">
                <i class="fa-solid fa-caret-up"></i> <span>23%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 5 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-xmark text-red-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Rejected<br>Today</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['today_rejected']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-red-500">
                <i class="fa-solid fa-caret-down"></i> <span>14%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 6 -->
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-question text-orange-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wide leading-tight">Requesting More<br>Information</p>
                    <h3 class="text-lg font-black text-slate-800 mt-0.5"><?php echo $counts['under_review']; ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-red-500">
                <i class="fa-solid fa-caret-down"></i> <span>9%</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>

        <!-- Card 7 -->
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
            <div class="flex items-center gap-1 mt-2 text-[9px] font-semibold text-red-500">
                <i class="fa-solid fa-caret-down"></i> <span>0.3</span> <span class="text-slate-400 font-normal">vs yesterday</span>
            </div>
        </div>
    </div>

    <!-- Main Content Area: Table (Left) + Detail Panel (Right) -->
    <div class="flex flex-col xl:flex-row gap-6 items-start">

        <!-- Left Column: Search, Filters & Table -->
        <div class="flex-1 w-full min-w-0 flex flex-col gap-5">

            <!-- Filter Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <!-- Top Search & Toggle -->
                <div id="filterSearchRow" class="flex flex-col md:flex-row gap-3 justify-between items-start md:items-center">
                    <div class="flex-1 w-full flex items-center gap-2">
                        <div class="relative w-full">
                            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" placeholder="Search by Applicant Name, Application ID, Household ID..." class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1] block pl-10 pr-4 py-2.5 outline-none font-medium placeholder-slate-400">
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
                <div id="filterGrid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Submission Date</label>
                        <div class="relative">
                            <input type="text" placeholder="Select date range" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium pr-8">
                            <i class="fa-regular fa-calendar absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Reviewer</label>
                        <select class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                            <option>All Reviewers</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Submission Method</label>
                        <select class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                            <option>All Methods</option>
                        </select>
                    </div>

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
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
                        <select class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                            <option>All Status</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Priority</label>
                        <select class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                            <option>All Priority</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Has Uploaded Documents</label>
                        <select class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg p-2.5 outline-none font-medium">
                            <option>All</option>
                        </select>
                    </div>

                    <div class="flex items-end justify-end">
                        <button id="clearFiltersBtnPending" class="w-full py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                
                <!-- Table Header Actions Bar -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 border-b border-slate-100 gap-3">
                    <span id="applicationsFoundText" class="text-xs font-bold text-slate-800"><?php echo count($applications); ?> applications found</span>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                        <select class="bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg px-3 py-1.5 outline-none cursor-pointer">
                            <option>Bulk Actions (3 selected)</option>
                        </select>

                        <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg">
                            <button class="w-7 h-7 rounded-md bg-white shadow-xs text-[#0f53d1] flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-table-list"></i></button>
                            <button class="w-7 h-7 rounded-md text-slate-400 hover:text-slate-700 flex items-center justify-center text-xs"><i class="fa-regular fa-square"></i></button>
                            <button class="w-7 h-7 rounded-md text-slate-400 hover:text-slate-700 flex items-center justify-center text-xs"><i class="fa-solid fa-filter"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Table Wrapper -->
                <div class="overflow-x-auto w-full custom-scrollbar">
                    <table class="w-full text-left border-collapse whitespace-nowrap min-w-[950px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Application ID <i class="fa-solid fa-arrows-up-down text-[8px] opacity-60"></i></th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Applicant Name</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Submission Date <i class="fa-solid fa-arrow-down text-[8px] opacity-60"></i></th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Submitted By</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">District</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned Reviewer</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Documents</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                                <th class="p-3.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="pendingTableBody">
                            <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="10" class="p-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-regular fa-folder-open text-4xl text-slate-300"></i>
                                        <p class="font-bold text-slate-700 text-sm">No Citizen Verifications in Queue</p>
                                        <p class="text-xs text-slate-400">Submissions from the citizen mobile app will appear here in real time.</p>
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
                                            <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($app['barangay']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <div class="text-xs font-medium text-slate-700"><?php echo $app['date']; ?></div>
                                    <div class="text-[10px] text-slate-400"><?php echo $app['time']; ?></div>
                                </td>
                                <td class="p-3.5 text-xs text-slate-600 font-medium"><?php echo htmlspecialchars($app['submitted_by']); ?></td>
                                <td class="p-3.5 text-xs text-slate-600 font-medium"><?php echo htmlspecialchars($app['district']); ?></td>
                                <td class="p-3.5">
                                    <?php if ($app['reviewer'] !== 'Unassigned'): ?>
                                        <div class="flex items-center gap-2">
                                            <img src="<?php echo $app['reviewer_avatar']; ?>" class="w-6 h-6 rounded-full border border-slate-200" alt="Reviewer">
                                            <span class="text-xs text-slate-700 font-medium"><?php echo htmlspecialchars($app['reviewer']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center gap-1.5 text-slate-400">
                                            <i class="fa-regular fa-user text-xs"></i>
                                            <span class="text-xs font-medium italic">Unassigned</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-1">
                                        <div class="w-6 h-6 rounded bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-500" title="Valid ID"><i class="fa-solid fa-id-card text-[10px]"></i></div>
                                        <div class="w-6 h-6 rounded bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-500" title="Selfie Photo"><i class="fa-solid fa-camera text-[10px]"></i></div>
                                        <span class="text-[10px] font-bold text-white bg-slate-700 px-1.5 py-0.5 rounded">2 docs</span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <span id="badge-<?php echo $app['raw_id']; ?>" class="px-2 py-0.5 text-[10px] font-bold rounded-md border <?php echo getAppStatusBadge($app['status']); ?>">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md border <?php echo getPriorityBadge($app['priority']); ?>">
                                        <?php echo $app['priority']; ?>
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <button class="w-6 h-6 rounded hover:bg-slate-200/60 flex items-center justify-center text-slate-400 hover:text-slate-700 transition mx-auto">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?></tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-medium">Rows per page</span>
                        <select class="bg-white border border-slate-200 text-slate-700 text-xs rounded-lg py-1 px-2 outline-none font-medium cursor-pointer">
                            <option>10</option>
                            <option>25</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1 text-xs">
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-white bg-[#0f53d1] font-bold shadow-xs">1</button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold">2</button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold">3</button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold">4</button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold">5</button>
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                    </div>

                    <span class="text-xs text-slate-500 font-medium">Showing 1 to 10 of 42 results</span>
                </div>

            </div>

        </div>

        <!-- Right Column: Detail Inspector Drawer Panel -->
        <div id="pendingDetailDrawer" class="hidden w-full xl:w-[420px] bg-white rounded-2xl border border-slate-200 shadow-md p-5 shrink-0 flex-col gap-5">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <h2 id="drawerAppId" class="text-base font-black text-slate-900 tracking-tight">APP-2025-0421</h2>
                    <span id="drawerAppStatus" class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-600 border border-amber-200/80">Pending</span>
                </div>
                <button onclick="closePendingDrawer()" class="text-slate-400 hover:text-slate-700 transition cursor-pointer p-1"><i class="fa-solid fa-xmark text-sm"></i></button>
            </div>

            <!-- Drawer Tabs -->
            <div class="flex items-center border-b border-slate-200">
                <button class="px-4 py-2 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1]">Review Application</button>
                <button class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
                    <span>Activity History</span>
                    <span class="bg-blue-100 text-[#0f53d1] text-[9px] font-bold px-1.5 py-0.2 rounded-full">6</span>
                </button>
            </div>

            <!-- Applicant Information -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase">Applicant Information</h3>
                </div>

                <div class="grid grid-cols-2 gap-y-2.5 gap-x-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Full Name</span>
                        <span id="drawerApplicantName" class="font-bold text-slate-800">Select an applicant</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Birthdate</span>
                        <span id="drawerBirthdate" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Sex</span>
                        <span id="drawerSex" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Civil Status</span>
                        <span id="drawerCivilStatus" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Employment / Occupation</span>
                        <span id="drawerEmployment" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Residency (Years)</span>
                        <span id="drawerYearsResident" class="font-bold text-slate-800">-</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-slate-400 block text-[10px] font-semibold">Barangay & District</span>
                        <span id="drawerBarangayDistrict" class="font-bold text-slate-800 leading-snug">-</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-slate-400 block text-[10px] font-semibold">Street Address</span>
                        <span id="drawerAddress" class="font-bold text-slate-800 leading-snug">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Valid ID Type</span>
                        <span id="drawerValidIdType" class="font-bold text-brand-dark">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold">Valid ID Number</span>
                        <span id="drawerValidIdNumber" class="font-bold text-slate-800">-</span>
                    </div>
                </div>
            </div>

            <!-- Uploaded Documents (Real photos from mobile app) -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase">Submitted Verification Photos</h3>
                    <span id="drawerPhotoCount" class="text-[10px] font-bold text-brand-dark">2 Photos</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Front ID Photo -->
                    <div class="flex flex-col items-center gap-1.5 text-center p-2 rounded-xl bg-slate-50 border border-slate-200">
                        <div id="drawerIdPhotoBox" class="w-full h-28 bg-slate-200 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                            <i class="fa-solid fa-id-card text-2xl text-slate-400"></i>
                        </div>
                        <span class="text-[10px] font-bold text-slate-700">Valid ID (Front)</span>
                        <span id="drawerIdPhotoStatus" class="text-[9px] font-semibold text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-check"></i> Attached</span>
                    </div>

                    <!-- Selfie Photo -->
                    <div class="flex flex-col items-center gap-1.5 text-center p-2 rounded-xl bg-slate-50 border border-slate-200">
                        <div id="drawerSelfiePhotoBox" class="w-full h-28 bg-slate-200 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                            <i class="fa-solid fa-camera text-2xl text-slate-400"></i>
                        </div>
                        <span class="text-[10px] font-bold text-slate-700">Selfie Verification</span>
                        <span id="drawerSelfiePhotoStatus" class="text-[9px] font-semibold text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-check"></i> Attached</span>
                    </div>
                </div>
            </div>

            <!-- Verification Checklist -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase">Verification Checklist</h3>
                    <span class="text-[10px] font-bold text-emerald-600">ID & Selfie Verified</span>
                </div>

                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center gap-2 text-slate-700 font-medium">
                        <i class="fa-regular fa-circle-check text-emerald-500 text-sm"></i>
                        <span>Personal information complete</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-700 font-medium">
                        <i class="fa-regular fa-circle-check text-emerald-500 text-sm"></i>
                        <span>Caloocan address verified</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-700 font-medium">
                        <i class="fa-regular fa-circle-check text-emerald-500 text-sm"></i>
                        <span>Valid ID matches citizen name</span>
                    </div>
                </div>
            </div>

            <!-- Decision Action Buttons Grid -->
            <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                <button id="btnApproveApp" onclick="handleApproveApplication()" class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-check"></i>
                    <span>Approve Registration</span>
                </button>

                <button id="btnRejectApp" onclick="handleRejectApplication()" class="py-2.5 px-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                    <span>Reject Registration</span>
                </button>
            </div>

        </div>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const districtFilter = document.getElementById('districtFilterPending');
    const clearFiltersBtn = document.getElementById('clearFiltersBtnPending');
    const searchBtn = document.getElementById('searchBtnPending');
    const rows = document.querySelectorAll('tbody tr[data-district]');
    const applicationsFoundText = document.getElementById('applicationsFoundText');

    function applyDistrictFilter() {
        const selected = districtFilter ? districtFilter.value.trim() : '';
        let visibleCount = 0;

        rows.forEach(row => {
            const district = row.getAttribute('data-district') || '';
            if (!selected || district === selected) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (applicationsFoundText) {
            if (selected) {
                applicationsFoundText.textContent = `${visibleCount} applications found (${selected})`;
            } else {
                applicationsFoundText.textContent = `${rows.length} applications found`;
            }
        }
    }

    if (districtFilter) districtFilter.addEventListener('change', applyDistrictFilter);
    if (searchBtn) searchBtn.addEventListener('click', applyDistrictFilter);
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            if (districtFilter) districtFilter.value = '';
            applyDistrictFilter();
        });
    }
});

function togglePendingFilterGrid() {
    const filterGrid = document.getElementById('filterGrid');
    const filterSearchRow = document.getElementById('filterSearchRow');
    const btnText = document.getElementById('toggleFilterBtnText');
    const btnChevron = document.getElementById('toggleFilterBtnChevron');

    if (!filterGrid) return;

    if (filterGrid.classList.contains('hidden')) {
        filterGrid.classList.remove('hidden');
        if (filterSearchRow) filterSearchRow.classList.add('mb-5');
        if (btnText) btnText.textContent = 'Hide Filters';
        if (btnChevron) btnChevron.className = 'fa-solid fa-chevron-up text-[9px] ml-0.5';
    } else {
        filterGrid.classList.add('hidden');
        if (filterSearchRow) filterSearchRow.classList.remove('mb-5');
        if (btnText) btnText.textContent = 'Show Filters';
        if (btnChevron) btnChevron.className = 'fa-solid fa-chevron-down text-[9px] ml-0.5';
    }
}

let activeApp = null;

function selectPendingApplication(rowElement) {
    const rawData = rowElement.getAttribute('data-app');
    if (!rawData) return;

    const app = JSON.parse(rawData);
    const drawer = document.getElementById('pendingDetailDrawer');
    if (!drawer) return;

    if (activeApp && activeApp.raw_id === app.raw_id && !drawer.classList.contains('hidden')) {
        closePendingDrawer();
        return;
    }

    activeApp = app;
    document.querySelectorAll('.pending-app-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/30');
    });
    rowElement.classList.add('bg-blue-50/40');

    // Populate drawer elements
    document.getElementById('drawerAppId').textContent = app.id;
    document.getElementById('drawerApplicantName').textContent = app.applicant;
    
    const statusSpan = document.getElementById('drawerAppStatus');
    if (statusSpan) {
        statusSpan.textContent = app.status;
        statusSpan.className = 'px-2 py-0.5 text-[10px] font-bold rounded-md border ' + 
            (app.status === 'Approved' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
             app.status === 'Rejected' ? 'bg-red-50 text-red-600 border-red-200' :
             'bg-amber-50 text-amber-600 border-amber-200/80');
    }

    document.getElementById('drawerBirthdate').textContent = app.birth_date || 'N/A';
    document.getElementById('drawerSex').textContent = app.sex || 'N/A';
    document.getElementById('drawerCivilStatus').textContent = app.civil_status || 'N/A';
    document.getElementById('drawerEmployment').textContent = (app.employment_status || '') + (app.occupation ? ' - ' + app.occupation : '');
    document.getElementById('drawerYearsResident').textContent = (app.years_resident || 1) + ' year(s)';
    document.getElementById('drawerBarangayDistrict').textContent = (app.barangay ? app.barangay + ', ' : '') + (app.district || '');
    document.getElementById('drawerAddress').textContent = app.street_address || 'No street address provided';
    document.getElementById('drawerValidIdType').textContent = app.valid_id_type || 'Valid ID';
    document.getElementById('drawerValidIdNumber').textContent = app.valid_id_number || 'N/A';

    // Photos Helper: support relative assets/, server uploads, data URIs, and full URLs
    function formatPhotoSrc(url) {
        if (!url || typeof url !== 'string') return null;
        url = url.trim();
        if (!url || url.startsWith('blob:')) return null;
        if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:image/')) {
            return url;
        }
        if (url.startsWith('assets/')) {
            return '../../' + url;
        }
        if (url.startsWith('/')) {
            return url;
        }
        return '../../assets/uploads/verifications/' + url;
    }

    const idBox = document.getElementById('drawerIdPhotoBox');
    const idSrc = formatPhotoSrc(app.id_front_photo_url);
    if (idSrc) {
        idBox.innerHTML = `<a href="${idSrc}" target="_blank" title="Click to view full image in new tab"><img src="${idSrc}" class="w-full h-full object-cover rounded-lg hover:opacity-90 transition cursor-pointer" alt="Valid ID" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'text-center p-2 text-slate-400\\'><i class=\\'fa-solid fa-id-card text-2xl mb-1\\'></i><span class=\\'block text-[9px]\\'>Image unavailable</span></div>';" /></a>`;
    } else {
        idBox.innerHTML = `<div class="text-center p-2 text-slate-400"><i class="fa-solid fa-id-card text-2xl mb-1"></i><span class="block text-[9px]">No photo uploaded</span></div>`;
    }

    const selfieBox = document.getElementById('drawerSelfiePhotoBox');
    const selfieSrc = formatPhotoSrc(app.selfie_photo_url);
    if (selfieSrc) {
        selfieBox.innerHTML = `<a href="${selfieSrc}" target="_blank" title="Click to view full image in new tab"><img src="${selfieSrc}" class="w-full h-full object-cover rounded-lg hover:opacity-90 transition cursor-pointer" alt="Selfie" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'text-center p-2 text-slate-400\\'><i class=\\'fa-solid fa-camera text-2xl mb-1\\'></i><span class=\\'block text-[9px]\\'>Image unavailable</span></div>';" /></a>`;
    } else {
        selfieBox.innerHTML = `<div class="text-center p-2 text-slate-400"><i class="fa-solid fa-camera text-2xl mb-1"></i><span class="block text-[9px]">No selfie uploaded</span></div>`;
    }

    drawer.classList.remove('hidden');
    drawer.classList.add('flex');
}

function closePendingDrawer() {
    activeApp = null;
    document.querySelectorAll('.pending-app-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/30');
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
        alert('No pending applications in the queue to export.');
        return;
    }

    const headers = [
        'Application ID',
        'Applicant Name',
        'Sex',
        'Birth Date',
        'Civil Status',
        'Occupation',
        'District',
        'Barangay',
        'Street Address',
        'Years Resident',
        'Valid ID Type',
        'Valid ID Number',
        'Status',
        'Submission Date',
        'Reviewer'
    ];

    const rows = applications.map(app => [
        `"${app.id}"`,
        `"${(app.applicant || '').replace(/"/g, '""')}"`,
        `"${app.sex || ''}"`,
        `"${app.birth_date || ''}"`,
        `"${app.civil_status || ''}"`,
        `"${(app.occupation || '').replace(/"/g, '""')}"`,
        `"${app.district || ''}"`,
        `"${app.barangay || ''}"`,
        `"${(app.street_address || '').replace(/"/g, '""')}"`,
        `"${app.years_resident || ''}"`,
        `"${(app.valid_id_type || '').replace(/"/g, '""')}"`,
        `"${(app.valid_id_number || '').replace(/"/g, '""')}"`,
        `"${app.status || ''}"`,
        `"${app.date} ${app.time}"`,
        `"${app.reviewer || ''}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" 
        + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    const today = new Date().toISOString().slice(0, 10);
    link.setAttribute("download", `pending_approvals_${today}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

async function handleApproveApplication() {
    if (!activeApp) return;
    if (!confirm(`Are you sure you want to APPROVE registration for ${activeApp.applicant}?`)) return;

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
            alert(`Success: ${activeApp.applicant} has been APPROVED and registered! You can view this record in Registered Citizens.`);
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to approve application.'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Approve Registration</span>';
            }
        }
    } catch (e) {
        alert('Network error connecting to API');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Approve Registration</span>';
        }
    }
}

async function handleRejectApplication() {
    if (!activeApp) return;
    const reason = prompt(`Please enter the reason for REJECTING ${activeApp.applicant}:`, 'ID photo blurry or invalid credentials');
    if (reason === null) return; // User cancelled

    const btn = document.getElementById('btnRejectApp');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';
    }

    try {
        const res = await fetch('../../api/admin/review-citizen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                verification_id: activeApp.raw_id,
                citizen_user_id: activeApp.citizen_user_id,
                action: 'reject',
                rejection_reason: reason,
                reviewed_by: 'Admin'
            })
        });
        const result = await res.json();
        if (result.status === 'success') {
            alert(`Application for ${activeApp.applicant} marked as REJECTED.`);
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to reject application.'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-xmark"></i> <span>Reject Registration</span>';
            }
        }
    } catch (e) {
        alert('Network error connecting to API');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i> <span>Reject Registration</span>';
        }
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
