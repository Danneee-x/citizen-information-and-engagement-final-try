<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

// Real Registered Citizens from MySQL
require_once __DIR__ . '/../../config/database.php';

$citizens = [];
$counts = [
    'total' => 0,
    'active' => 0,
    'senior' => 0,
    'pwd' => 0,
    'solo_parent' => 0,
    'four_ps' => 0,
    'new_regs' => 0,
    'pending' => 0,
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

    // Real Metrics Query: ONLY Approved citizens are registered citizens
    $metricStmt = $pdo->query("SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN verification_status = 'Approved' AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60 THEN 1 ELSE 0 END) as senior_count,
        SUM(CASE WHEN verification_status = 'Approved' AND civil_status IN ('Widowed', 'Separated', 'Divorced / Annulled', 'Common-Law / Live-In') THEN 1 ELSE 0 END) as solo_parent_count,
        SUM(CASE WHEN verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN verification_status = 'Approved' AND DATE(COALESCE(reviewed_at, submitted_at)) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_regs_count
        FROM citizen_verifications");
    $stats = $metricStmt->fetch(PDO::FETCH_ASSOC);

    $counts['total']       = (int)($stats['approved_count'] ?? 0);
    $counts['active']      = (int)($stats['approved_count'] ?? 0);
    $counts['senior']      = (int)($stats['senior_count'] ?? 0);
    $counts['solo_parent'] = (int)($stats['solo_parent_count'] ?? 0);
    $counts['pending']     = (int)($stats['pending_count'] ?? 0);
    $counts['new_regs']    = (int)($stats['new_regs_count'] ?? 0);
    $counts['pwd']         = 0;
    $counts['four_ps']     = 0;

    // Fetch ONLY Approved citizens for the Registered Citizens table
    $stmt = $pdo->query("SELECT * FROM citizen_verifications WHERE verification_status = 'Approved' ORDER BY COALESCE(reviewed_at, submitted_at) DESC LIMIT 100");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $fullName = trim("{$r['last_name']}, {$r['first_name']} {$r['middle_name']} {$r['suffix']}");
        $displayApplicant = trim("{$r['first_name']} {$r['last_name']}");
        
        $birthDate = !empty($r['birth_date']) ? new DateTime($r['birth_date']) : null;
        $age = $birthDate ? (new DateTime())->diff($birthDate)->y : 0;
        
        $subDate = !empty($r['submitted_at']) ? new DateTime($r['submitted_at']) : new DateTime();
        $updDate = !empty($r['reviewed_at']) ? new DateTime($r['reviewed_at']) : $subDate;

        $tags = [];
        if ($age >= 60) $tags[] = 'Senior Citizen';
        if (in_array($r['civil_status'], ['Widowed', 'Separated', 'Divorced / Annulled'])) $tags[] = 'Solo Parent';

        $status = ($age >= 60) ? 'Senior Citizen' : 'Active';

        $citizens[] = [
            'id' => 'CIZ-' . str_pad($r['verification_id'], 5, '0', STR_PAD_LEFT),
            'raw_id' => $r['verification_id'],
            'name' => $fullName,
            'age' => $age,
            'sex' => $r['sex'] ?? 'Not Specified',
            'district' => $r['district'] ?? 'District 1',
            'barangay' => $r['barangay'] ?? '',
            'civil_status' => $r['civil_status'] ?? '',
            'household' => 'HH-' . str_pad($r['citizen_user_id'] ?: $r['verification_id'], 5, '0', STR_PAD_LEFT),
            'occupation' => $r['occupation'] ?? 'Resident',
            'mobile' => '09' . substr(preg_replace('/[^0-9]/', '', $r['valid_id_number'] ?? '123456789'), 0, 9),
            'status' => $status,
            'tags' => $tags,
            'date' => $subDate->format('M d, Y'),
            'updated' => $updDate->format('M d, Y'),
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($displayApplicant) . '&background=random'
        ];
    }
} catch (Exception $e) {
    error_log("Registered citizens error: " . $e->getMessage());
}

    // Quick Statistics from live DB
    $quickStatsStmt = $pdo->query("SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN verification_status = 'Approved' AND sex = 'Male' THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN verification_status = 'Approved' AND sex = 'Female' THEN 1 ELSE 0 END) as female_count,
        AVG(CASE WHEN verification_status = 'Approved' AND birth_date IS NOT NULL AND birth_date != '0000-00-00' THEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) ELSE NULL END) as avg_age,
        COUNT(DISTINCT CASE WHEN verification_status = 'Approved' AND street_address IS NOT NULL AND street_address != '' THEN street_address ELSE NULL END) as households,
        SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicates
        FROM citizen_verifications");
    $dbQuickStats = $quickStatsStmt->fetch(PDO::FETCH_ASSOC);

    $avgAge = !empty($dbQuickStats['avg_age']) ? round((float)$dbQuickStats['avg_age'], 1) : 0;
    $avgAgeDisplay = $avgAge > 0 ? "{$avgAge} years" : "N/A";

    $approvedTotal = (int)($dbQuickStats['approved_count'] ?? 0);
    $males = (int)($dbQuickStats['male_count'] ?? 0);
    $females = (int)($dbQuickStats['female_count'] ?? 0);
    if ($approvedTotal > 0 && ($males + $females) > 0) {
        $genderTotal = $males + $females;
        $mPct = round(($males / $genderTotal) * 100);
        $fPct = 100 - $mPct;
        $genderRatioDisplay = "{$mPct}% : {$fPct}%";
    } else {
        $genderRatioDisplay = "50% : 50%";
    }

    $totalHouseholds = (int)($dbQuickStats['households'] ?? 0);
    if ($totalHouseholds === 0 && $approvedTotal > 0) {
        $totalHouseholds = $approvedTotal;
    }
    $householdsDisplay = number_format($totalHouseholds > 0 ? $totalHouseholds : 1);

    $totalAll = (int)($dbQuickStats['total_records'] ?? 0);
    $duplicates = (int)($dbQuickStats['duplicates'] ?? 0);
    if ($totalAll > 0) {
        $accuracy = round((($totalAll - $duplicates) / $totalAll) * 100, 1);
        $accuracyDisplay = "{$accuracy}%";
    } else {
        $accuracyDisplay = "100%";
    }

    // Recently Registered Citizens (Approved)
    $recentRegStmt = $pdo->query("SELECT verification_id, first_name, last_name, district, barangay, reviewed_at, submitted_at, selfie_photo_url 
        FROM citizen_verifications 
        WHERE verification_status = 'Approved' 
        ORDER BY COALESCE(reviewed_at, submitted_at) DESC 
        LIMIT 4");
    $recentlyRegisteredList = $recentRegStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Activities (Latest events from verification lifecycle)
    $actStmt = $pdo->query("SELECT 
        verification_id, first_name, last_name, verification_status, reviewed_by, reviewed_at, submitted_at,
        CASE 
            WHEN reviewed_at IS NOT NULL AND verification_status = 'Approved' THEN reviewed_at
            WHEN reviewed_at IS NOT NULL AND verification_status = 'Rejected' THEN reviewed_at
            ELSE submitted_at
        END AS activity_time,
        CASE 
            WHEN reviewed_at IS NOT NULL AND verification_status = 'Approved' THEN 'approved'
            WHEN reviewed_at IS NOT NULL AND verification_status = 'Rejected' THEN 'rejected'
            ELSE 'submitted'
        END AS activity_type
        FROM citizen_verifications
        ORDER BY activity_time DESC
        LIMIT 4");
    $recentActivitiesList = $actStmt->fetchAll(PDO::FETCH_ASSOC);

function getRelativeTimeStr($datetime) {
    if (empty($datetime)) return 'Recently';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hour' . (round($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 172800) return 'Yesterday';
    return date('M d, Y', $time);
}

function getStatusBadge($status) {
    switch ($status) {
        case 'Active': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'Senior Citizen': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Pending Validation': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Inactive': return 'bg-slate-100 text-slate-700 border-slate-200';
        case 'Deceased': return 'bg-red-100 text-red-700 border-red-200';
        case 'Transferred Out': return 'bg-orange-100 text-orange-700 border-orange-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* Custom Scrollbar for the main area and table if needed */
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
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-6">
        <span>Citizen Registry</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Registered Citizens</span>
    </div>
    <!-- KPI Cards Row (2 lines x 4 boxes on desktop, responsive on smaller screens) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1 -->
        <div onclick="filterByCard('all')" data-card-type="all" title="Click to view all citizens" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-blue-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50/80 flex items-center justify-center shrink-0 border border-blue-100">
                    <i class="fa-solid fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">Total Registered Citizens</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['total']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 3.45%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div onclick="filterByCard('Active')" data-card-type="Active" title="Click to view active citizens" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-emerald-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50/80 flex items-center justify-center shrink-0 border border-emerald-100">
                    <i class="fa-solid fa-user-check text-emerald-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">Active Citizens</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['active']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 2.91%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div onclick="filterByCard('Senior Citizen')" data-card-type="Senior Citizen" title="Click to view senior citizens" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-orange-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-orange-50/80 flex items-center justify-center shrink-0 border border-orange-100">
                    <i class="fa-solid fa-person-cane text-orange-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">Senior Citizens</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['senior']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 1.88%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div onclick="filterByCard('PWD')" data-card-type="PWD" title="Click to view PWD citizens" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-purple-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-50/80 flex items-center justify-center shrink-0 border border-purple-100">
                    <i class="fa-brands fa-accessible-icon text-purple-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">PWD Citizens</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['pwd']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 2.14%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 5 -->
        <div onclick="filterByCard('Solo Parent')" data-card-type="Solo Parent" title="Click to view solo parents" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-pink-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-pink-50/80 flex items-center justify-center shrink-0 border border-pink-100">
                    <i class="fa-solid fa-person-breastfeeding text-pink-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">Solo Parents</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['solo_parent']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 1.35%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 6 -->
        <div onclick="filterByCard('4Ps Beneficiary')" data-card-type="4Ps Beneficiary" title="Click to view 4Ps beneficiaries" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-cyan-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-cyan-50/80 flex items-center justify-center shrink-0 border border-cyan-100">
                    <i class="fa-solid fa-people-group text-cyan-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">4Ps Beneficiaries</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['four_ps']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 2.02%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 7 -->
        <div onclick="filterByCard('New Registrations')" data-card-type="New Registrations" title="Click to view new registrations" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-indigo-50/80 flex items-center justify-center shrink-0 border border-indigo-100">
                    <i class="fa-regular fa-calendar-check text-indigo-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">New Registrations</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['new_regs']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 12.40%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>

        <!-- Card 8 -->
        <div onclick="filterByCard('Pending Validation')" data-card-type="Pending Validation" title="Click to view pending validation citizens" class="kpi-stat-card bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-amber-300 transition-all cursor-pointer group select-none">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-amber-50/80 flex items-center justify-center shrink-0 border border-amber-100">
                    <i class="fa-solid fa-shield-halved text-amber-600 text-xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide truncate">Pending Validation</p>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-0.5"><?php echo number_format($counts['pending']); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100 text-[11px] font-semibold">
                <span class="text-red-500 flex items-center gap-1"><i class="fa-solid fa-arrow-up text-[10px]"></i> 4.21%</span>
                <span class="text-slate-400">vs last month</span>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="flex flex-col gap-6 w-full">
        
        <!-- Main Table Section -->
        <div class="w-full flex flex-col gap-6 min-w-0">
            
            <!-- Filters Section -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <!-- Search Bar -->
                <div class="flex flex-col sm:flex-row gap-3 justify-between items-stretch sm:items-center">
                    <div class="relative w-full flex-1">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchInput" oninput="filterCitizensByDistrict()" placeholder="Search by Name, Household ID, National ID, Voter ID..." class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block pl-11 p-3 transition outline-none placeholder-slate-400 font-medium">
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <button id="searchBtn" onclick="filterCitizensByDistrict()" class="px-5 py-3 text-xs font-bold text-white bg-[#0f53d1] border border-[#0f53d1] rounded-xl hover:bg-[#0d46b0] shadow-sm transition cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-search text-[10px]"></i>
                            <span>Search</span>
                        </button>
                        <button id="toggleFiltersBtn" onclick="toggleAdvancedFilters()" class="px-4 py-3 text-xs font-bold text-[#0f53d1] bg-blue-50/50 rounded-xl border border-[#0f53d1]/20 hover:bg-blue-50 transition cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-xs"></i>
                            <span id="toggleFiltersText">Show Filters</span>
                            <i id="toggleFiltersIcon" class="fa-solid fa-chevron-down text-[10px] ml-1 transition-transform"></i>
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters (Hidden by Default) -->
                <div id="advancedFiltersContainer" class="hidden grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-x-4 gap-y-5 mt-5 border-t border-slate-100 pt-5">
                    
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">District</label>
                        <select id="districtFilter" onchange="onDistrictChange()" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer">
                            <option value="">All Districts</option>
                            <option value="District 1">District 1</option>
                            <option value="District 2">District 2</option>
                            <option value="District 3">District 3</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">Barangay</label>
                        <select id="barangayFilter" onchange="filterCitizensByDistrict()" disabled class="w-full bg-slate-50 border border-slate-200 text-slate-500 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-not-allowed">
                            <option value="">Select District First...</option>
                        </select>
                    </div>
                    
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">Status</label>
                        <select id="statusFilter" onchange="filterCitizensByDistrict()" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Senior Citizen">Senior Citizen</option>
                            <option value="Pending Validation">Pending Validation</option>
                            <option value="Deceased">Deceased</option>
                            <option value="Transferred Out">Transferred Out</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">Sex</label>
                        <select id="sexFilter" onchange="filterCitizensByDistrict()" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer">
                            <option value="">All Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">Civil Status</label>
                        <select id="civilStatusFilter" onchange="filterCitizensByDistrict()" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer">
                            <option value="">All Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced/Annulled">Divorced / Annulled</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500">Age Range</label>
                        <select id="ageRangeFilter" onchange="filterCitizensByDistrict()" class="w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer">
                            <option value="">All Ages</option>
                            <option value="0-4">0–4 years</option>
                            <option value="5-14">5–14 years</option>
                            <option value="15-29">15–29 years</option>
                            <option value="30-59">30–59 years</option>
                            <option value="60+">60+ years</option>
                        </select>
                    </div>
                    
                    <div class="xl:col-span-6 flex items-center justify-end pt-3 border-t border-slate-100 mt-1">
                        <button id="clearFiltersBtn" onclick="resetDistrictFilters()" class="px-5 py-2.5 text-xs font-bold text-[#0f53d1] bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                            Clear Filters
                        </button>
                    </div>

                </div>
            </div>

            <!-- Toolbar & Data Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                
                <!-- Bulk Actions Toolbar -->
                <div class="flex items-center justify-between p-4 border-b border-slate-100 flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="selectAllCheckboxToolbar" onchange="toggleSelectAllCitizens(this)" class="w-4 h-4 text-[#0f53d1] bg-slate-100 border-slate-300 rounded focus:ring-[#0f53d1]/50 cursor-pointer">
                        </label>
                        <span id="selectedCountSpan" class="text-xs font-bold text-slate-800">0 selected</span>
                        <button id="selectAllTextBtn" onclick="toggleSelectAllBtnClick()" class="text-xs font-bold text-[#0f53d1] hover:underline cursor-pointer">Select all citizens</button>
                    </div>
                    
                    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 -mb-1">
                        <button onclick="markSelectedForValidation()" class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-amber-500"></i> Mark for Validation
                        </button>
                        <button class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-download text-slate-400"></i> Export Selected
                        </button>
                        <button class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-print text-slate-400"></i> Print Selected
                        </button>
                        <div class="relative inline-block text-left">
                            <button onclick="toggleChangeStatusDropdown(event)" class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                                <i class="fa-solid fa-repeat text-slate-400"></i> Change Status <i class="fa-solid fa-chevron-down text-[8px] ml-1 opacity-60"></i>
                            </button>
                            <div id="changeStatusMenu" class="hidden fixed w-44 bg-white border border-slate-200 rounded-xl shadow-xl py-1.5 z-[9999] text-xs font-medium text-slate-600">
                                <button onclick="changeSelectedCitizensStatus('Active')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                                </button>
                                <button onclick="changeSelectedCitizensStatus('Inactive')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span> Inactive
                                </button>
                                <button onclick="changeSelectedCitizensStatus('Senior Citizen')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> Senior Citizen
                                </button>
                                <button onclick="changeSelectedCitizensStatus('Pending Validation')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending Validation
                                </button>
                                <button onclick="changeSelectedCitizensStatus('Deceased')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Deceased
                                </button>
                                <button onclick="changeSelectedCitizensStatus('Transferred Out')" class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
                                    <span class="w-2 h-2 rounded-full bg-orange-500"></span> Transferred Out
                                </button>
                            </div>
                        </div>
                        <button class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-house-user text-slate-400"></i> Assign to Household
                        </button>
                        <button class="whitespace-nowrap px-3 py-2 text-[11px] font-bold text-red-600 bg-white border border-red-100 rounded-lg hover:bg-red-50 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-box-archive opacity-80"></i> Archive Selected
                        </button>
                    </div>
                </div>

                <!-- Table Wrapper -->
                <div class="overflow-x-auto w-full custom-scrollbar">
                    <table class="w-full text-left border-collapse whitespace-nowrap min-w-[1200px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="p-4 w-12 text-center"></th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Citizen ID</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Full Name</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sex</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">District</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Barangay</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Household ID</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mobile Number</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Registered</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Last Updated</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($citizens)): ?>
                            <tr>
                                <td colspan="13" class="p-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-14 h-14 rounded-2xl bg-blue-50/60 border border-blue-100 flex items-center justify-center text-brand-dark mb-1">
                                            <i class="fa-solid fa-users text-2xl text-blue-500"></i>
                                        </div>
                                        <p class="font-bold text-slate-700 text-sm">No Approved Citizens in Registry</p>
                                        <p class="text-xs text-slate-400 max-w-md">New submissions from the citizen mobile app appear in <a href="pending-approvals.php" class="text-[#0f53d1] font-bold hover:underline">Pending Approvals</a>. Once verified and approved by staff, they will automatically be listed here as registered citizens.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($citizens as $index => $c): ?>
                            <tr onclick="toggleCitizenRow(event, this)" class="hover:bg-slate-50 transition cursor-pointer select-none" data-household="<?php echo htmlspecialchars($c['household']); ?>" data-district="<?php echo htmlspecialchars($c['district']); ?>" data-barangay="<?php echo htmlspecialchars($c['barangay']); ?>" data-sex="<?php echo htmlspecialchars($c['sex']); ?>" data-civil-status="<?php echo htmlspecialchars($c['civil_status']); ?>" data-age="<?php echo htmlspecialchars($c['age']); ?>" data-status="<?php echo htmlspecialchars($c['status']); ?>" data-tags="<?php echo htmlspecialchars(implode(',', $c['tags'])); ?>" data-date="<?php echo htmlspecialchars($c['date']); ?>">
                                <td class="p-4 text-center">
                                    <input type="checkbox" onchange="updateSelectAllState()" class="citizen-row-checkbox w-4 h-4 text-[#0f53d1] bg-slate-100 border-slate-300 rounded focus:ring-[#0f53d1]/50 cursor-pointer">
                                </td>
                                <td class="p-4 text-xs font-semibold text-slate-600"><?php echo $c['id']; ?></td>
                                <td class="p-4 flex items-center gap-3">
                                    <img src="<?php echo $c['avatar']; ?>" class="w-8 h-8 rounded-full border border-slate-200 shadow-sm" alt="Avatar">
                                    <span class="text-xs font-bold text-slate-800"><?php echo $c['name']; ?></span>
                                </td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['age']; ?></td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['sex']; ?></td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['district']; ?></td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['barangay']; ?></td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['household']; ?></td>
                                <td class="p-4 text-xs text-slate-600 font-medium"><?php echo $c['mobile']; ?></td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-md border <?php echo getStatusBadge($c['status']); ?>">
                                        <?php echo $c['status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-[11px] text-slate-500 font-medium"><?php echo $c['date']; ?></td>
                                <td class="p-4 text-[11px] text-slate-500 font-medium"><?php echo $c['updated']; ?></td>
                                <td class="p-4 text-center">
                                    <button onclick="toggleRowActionsMenu(event, this, '<?php echo $c['id']; ?>')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer mx-auto">
                                        <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="noCitizensRow" class="hidden">
                                <td colspan="13" class="p-8 text-center text-slate-400 font-medium text-xs">
                                    <i class="fa-solid fa-users-slash text-2xl mb-2 block text-slate-300"></i>
                                    No registered citizens found for the selected filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Bottom Controls -->
                <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs text-slate-500 font-medium">Rows per page</span>
                        <select id="rowsPerPageSelect" onchange="onRowsPerPageChange(this.value)" class="bg-white border border-slate-200 text-slate-700 text-xs rounded-lg py-1.5 px-2 outline-none font-medium cursor-pointer">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span id="showingEntriesText" class="text-xs text-slate-500 font-medium ml-1 mr-2">Showing 1 to 10 of 15 entries</span>

                        <!-- Vertical Divider -->
                        <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>

                        <!-- View Toggles Beside Showing Entries -->
                        <div class="flex items-center gap-2">
                            <button id="tableViewBtn" onclick="switchViewMode('table')" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-[#0f53d1]/30 bg-blue-50/50 shadow-xs cursor-pointer hover:bg-blue-50 transition group">
                                <div class="w-6 h-6 rounded-md bg-white flex items-center justify-center border border-[#0f53d1]/20 text-[#0f53d1] text-xs transition-transform group-hover:scale-105">
                                    <i class="fa-solid fa-table-cells"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xs font-bold text-[#0f53d1] leading-tight">Table View</h4>
                                    <p class="text-[9px] text-slate-500 font-medium hidden sm:block">Detailed table format</p>
                                </div>
                            </button>

                            <button id="householdViewBtn" onclick="switchViewMode('household')" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-transparent bg-slate-50 shadow-xs cursor-pointer hover:border-slate-200 transition group">
                                <div class="w-6 h-6 rounded-md bg-white flex items-center justify-center border border-slate-200 text-slate-400 text-xs transition-transform group-hover:scale-105 group-hover:text-slate-600">
                                    <i class="fa-solid fa-house-chimney"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xs font-bold text-slate-700 leading-tight">Household View</h4>
                                    <p class="text-[9px] text-slate-500 font-medium hidden sm:block">Grouped by household</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div id="paginationButtonsContainer" class="flex items-center gap-1 flex-wrap">
                        <!-- Rendered by JS -->
                    </div>
                </div>

            </div>

        </div>

        <!-- Bottom Widgets Section (3 cards at bottom of table) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Recent Activities -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Recent Activities</h3>
                        <a href="id-verification-logs.php" class="text-[10px] font-bold text-[#0f53d1] hover:underline">View All</a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($recentActivitiesList)): ?>
                            <div class="py-6 text-center text-xs text-slate-400">No recent activities recorded</div>
                        <?php else: ?>
                            <?php foreach ($recentActivitiesList as $act): 
                                $actApplicant = trim("{$act['first_name']} {$act['last_name']}");
                                $actReviewer = !empty($act['reviewed_by']) && $act['reviewed_by'] !== 'Unassigned' ? $act['reviewed_by'] : 'Admin';
                                $actTimeStr = getRelativeTimeStr($act['activity_time']);
                            ?>
                                <div class="flex gap-3">
                                    <?php if ($act['activity_type'] === 'approved'): ?>
                                        <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-check text-[10px] text-emerald-500"></i></div>
                                        <div>
                                            <p class="text-[11px] text-slate-700 font-medium"><span class="font-bold"><?php echo htmlspecialchars($actReviewer); ?></span> approved <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($actApplicant); ?></span></p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 font-semibold"><?php echo htmlspecialchars($actTimeStr); ?></p>
                                        </div>
                                    <?php elseif ($act['activity_type'] === 'rejected'): ?>
                                        <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-xmark text-[10px] text-red-500"></i></div>
                                        <div>
                                            <p class="text-[11px] text-slate-700 font-medium"><span class="font-bold"><?php echo htmlspecialchars($actReviewer); ?></span> rejected <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($actApplicant); ?></span></p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 font-semibold"><?php echo htmlspecialchars($actTimeStr); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-6 h-6 rounded-full bg-blue-50 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-user-plus text-[10px] text-blue-500"></i></div>
                                        <div>
                                            <p class="text-[11px] text-slate-700 font-medium"><span class="font-bold"><?php echo htmlspecialchars($actApplicant); ?></span> submitted verification</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 font-semibold"><?php echo htmlspecialchars($actTimeStr); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recently Registered -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Recently Registered</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($recentlyRegisteredList)): ?>
                            <div class="py-6 text-center text-xs text-slate-400">No approved citizens yet</div>
                        <?php else: ?>
                            <?php foreach ($recentlyRegisteredList as $reg): 
                                $regName = trim("{$reg['first_name']} {$reg['last_name']}");
                                $regDate = !empty($reg['reviewed_at']) ? date('M d, Y', strtotime($reg['reviewed_at'])) : date('M d, Y', strtotime($reg['submitted_at']));
                                $regLoc = !empty($reg['district']) ? $reg['district'] : (!empty($reg['barangay']) ? "Brgy. {$reg['barangay']}" : 'District 1');
                                $regAvatar = !empty($reg['selfie_photo_url']) ? $reg['selfie_photo_url'] : ('https://ui-avatars.com/api/?name=' . urlencode($regName) . '&background=random');
                            ?>
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($regAvatar); ?>" class="w-8 h-8 rounded-full object-cover shadow-sm border border-slate-100" alt="<?php echo htmlspecialchars($regName); ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($regName); ?>&background=random'">
                                        <div>
                                            <p class="text-[11px] font-bold text-slate-800 group-hover:text-[#0f53d1] transition cursor-pointer"><?php echo htmlspecialchars($regName); ?></p>
                                            <p class="text-[9px] text-slate-500 font-medium mt-0.5"><?php echo htmlspecialchars($regDate); ?> &bull; <?php echo htmlspecialchars($regLoc); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="window.scrollTo({top: 400, behavior: 'smooth'})" class="w-full mt-4 py-2 text-[10px] font-bold text-[#0f53d1] bg-blue-50/50 rounded-lg border border-[#0f53d1]/20 hover:bg-blue-50 hover:text-[#0d46b0] transition cursor-pointer">
                    View All
                </button>
            </div>

            <!-- Quick Statistics -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Quick Statistics</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fa-solid fa-clock-rotate-left text-[10px] w-4 text-center"></i>
                                <span class="text-[11px] font-semibold">Average Age</span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-800"><?php echo $avgAgeDisplay; ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fa-solid fa-venus-mars text-[10px] w-4 text-center"></i>
                                <span class="text-[11px] font-semibold">Male to Female Ratio</span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-800"><?php echo $genderRatioDisplay; ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fa-solid fa-house-chimney text-[10px] w-4 text-center"></i>
                                <span class="text-[11px] font-semibold">Total Households</span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-800"><?php echo $householdsDisplay; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fa-solid fa-bullseye text-[10px] w-4 text-center"></i>
                                <span class="text-[11px] font-semibold">Data Accuracy Score</span>
                            </div>
                            <span class="text-[11px] font-bold text-emerald-600"><?php echo $accuracyDisplay; ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
const districtBarangaysMap = {
    'District 1': [1, 2, 3, 4, 77, 78, 79, 80, 81, 82, 83, 84, 85, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177],
    'District 2': [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131],
    'District 3': [178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188]
};

function updateBarangayDropdown(selectedDistrict) {
    const barangayFilter = document.getElementById('barangayFilter');
    if (!barangayFilter) return;

    const previousSelected = barangayFilter.value;
    barangayFilter.innerHTML = '';

    if (!selectedDistrict || !districtBarangaysMap[selectedDistrict]) {
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Select District First...';
        barangayFilter.appendChild(defaultOpt);
        barangayFilter.disabled = true;
        barangayFilter.className = 'w-full bg-slate-50 border border-slate-200 text-slate-500 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-not-allowed';
        return;
    }

    barangayFilter.disabled = false;
    barangayFilter.className = 'w-full bg-white border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-2 focus:ring-[#0f53d1]/50 focus:border-[#0f53d1] block p-2.5 outline-none font-medium cursor-pointer';

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = `All Barangays in ${selectedDistrict}`;
    barangayFilter.appendChild(defaultOpt);

    const bList = districtBarangaysMap[selectedDistrict];
    bList.forEach(bNum => {
        const opt = document.createElement('option');
        const val = `Barangay ${bNum}`;
        opt.value = val;
        opt.textContent = val;
        if (val === previousSelected) opt.selected = true;
        barangayFilter.appendChild(opt);
    });
}

function toggleAdvancedFilters() {
    const container = document.getElementById('advancedFiltersContainer');
    const textSpan = document.getElementById('toggleFiltersText');
    const icon = document.getElementById('toggleFiltersIcon');

    if (!container) return;

    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        if (textSpan) textSpan.textContent = 'Hide Filters';
        if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    } else {
        container.classList.add('hidden');
        if (textSpan) textSpan.textContent = 'Show Filters';
        if (icon) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
}

function onDistrictChange() {
    const districtFilter = document.getElementById('districtFilter');
    const selectedDistrict = districtFilter ? districtFilter.value.trim() : '';
    updateBarangayDropdown(selectedDistrict);
    filterCitizensByDistrict();
}

let activeCardFilters = new Set();
let currentPage = 1;
let rowsPerPage = 10;

function onRowsPerPageChange(val) {
    rowsPerPage = parseInt(val, 10) || 10;
    currentPage = 1;
    filterCitizensByDistrict();
}

function goToPage(page) {
    currentPage = page;
    filterCitizensByDistrict();
}

function filterByCard(cardType) {
    if (activeCardFilters.has(cardType)) {
        activeCardFilters.delete(cardType);
    } else {
        activeCardFilters.add(cardType);
    }
    currentPage = 1;

    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        const activeStatuses = Array.from(activeCardFilters).filter(c => 
            ['Active', 'Senior Citizen', 'Pending Validation', 'Inactive', 'Deceased', 'Transferred Out'].includes(c)
        );
        if (activeStatuses.length === 1) {
            statusFilter.value = activeStatuses[0];
        } else {
            statusFilter.value = '';
        }
    }

    updateStatCardsHighlight();
    filterCitizensByDistrict();

    const tableContainer = document.querySelector('table');
    if (tableContainer) {
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function updateStatCardsHighlight() {
    const cards = document.querySelectorAll('.kpi-stat-card');
    cards.forEach(card => {
        const type = card.getAttribute('data-card-type');
        if (type && activeCardFilters.has(type)) {
            card.classList.add('ring-2', 'ring-[#0f53d1]', 'border-[#0f53d1]', 'shadow-md', 'bg-blue-50/20');
            card.classList.remove('border-slate-200/80');
        } else {
            card.classList.remove('ring-2', 'ring-[#0f53d1]', 'border-[#0f53d1]', 'shadow-md', 'bg-blue-50/20');
            card.classList.add('border-slate-200/80');
        }
    });
}

function filterCitizensByDistrict() {
    const districtFilter = document.getElementById('districtFilter');
    const barangayFilter = document.getElementById('barangayFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sexFilter = document.getElementById('sexFilter');
    const civilStatusFilter = document.getElementById('civilStatusFilter');
    const ageRangeFilter = document.getElementById('ageRangeFilter');
    const searchInput = document.getElementById('searchInput');

    const selectedDistrict = districtFilter ? districtFilter.value.trim() : '';
    const selectedBarangay = barangayFilter ? barangayFilter.value.trim() : '';
    const selectedStatus = statusFilter ? statusFilter.value.trim() : '';
    const selectedSex = sexFilter ? sexFilter.value.trim() : '';
    const selectedCivilStatus = civilStatusFilter ? civilStatusFilter.value.trim() : '';
    const selectedAgeRange = ageRangeFilter ? ageRangeFilter.value.trim() : '';
    const searchQuery = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const rows = document.querySelectorAll('tbody tr[data-district]');
    const noRow = document.getElementById('noCitizensRow');
    const showingEntriesText = document.getElementById('showingEntriesText');

    const matchingRows = [];
    const totalRows = rows.length;

    rows.forEach(row => {
        const rowDistrict = row.getAttribute('data-district') || '';
        const rowBarangay = row.getAttribute('data-barangay') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        const rowSex = row.getAttribute('data-sex') || '';
        const rowCivilStatus = row.getAttribute('data-civil-status') || '';
        const rowAge = parseInt(row.getAttribute('data-age') || '0', 10);
        const rowTagsStr = row.getAttribute('data-tags') || '';
        const rowTags = rowTagsStr.split(',').map(t => t.trim());
        const rowDate = row.getAttribute('data-date') || '';
        const rowText = row.textContent.toLowerCase();

        let matchesAge = true;
        if (selectedAgeRange === '0-4') matchesAge = rowAge >= 0 && rowAge <= 4;
        else if (selectedAgeRange === '5-14') matchesAge = rowAge >= 5 && rowAge <= 14;
        else if (selectedAgeRange === '15-29') matchesAge = rowAge >= 15 && rowAge <= 29;
        else if (selectedAgeRange === '30-59') matchesAge = rowAge >= 30 && rowAge <= 59;
        else if (selectedAgeRange === '60+') matchesAge = rowAge >= 60;

        let matchesCard = true;
        if (activeCardFilters.size > 0 && !activeCardFilters.has('all')) {
            matchesCard = Array.from(activeCardFilters).every(filter => {
                if (filter === 'PWD') return rowTags.includes('PWD');
                if (filter === 'Solo Parent') return rowTags.includes('Solo Parent');
                if (filter === '4Ps Beneficiary') return rowTags.includes('4Ps Beneficiary');
                if (filter === 'New Registrations') return rowDate.includes('May 2025');
                if (filter === 'Pending Validation') return rowStatus === 'Pending Validation' || rowTags.includes('Pending Validation');
                if (filter === 'Active') return rowStatus === 'Active';
                if (filter === 'Senior Citizen') return rowStatus === 'Senior Citizen' || rowAge >= 60 || rowTags.includes('Senior Citizen');
                return rowStatus === filter;
            });
        }

        const matchesDistrict = !selectedDistrict || rowDistrict === selectedDistrict;
        const matchesBarangay = !selectedBarangay || rowBarangay === selectedBarangay;
        const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
        const matchesSex = !selectedSex || rowSex === selectedSex;
        const matchesCivilStatus = !selectedCivilStatus || rowCivilStatus === selectedCivilStatus;
        const matchesSearch = !searchQuery || rowText.includes(searchQuery);

        if (matchesDistrict && matchesBarangay && matchesStatus && matchesSex && matchesCivilStatus && matchesAge && matchesCard && matchesSearch) {
            matchingRows.push(row);
        } else {
            row.style.display = 'none';
        }
    });

    const totalMatching = matchingRows.length;
    const totalPages = Math.ceil(totalMatching / rowsPerPage) || 1;

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    matchingRows.forEach((row, idx) => {
        if (idx >= startIndex && idx < endIndex) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    if (noRow) {
        if (totalMatching === 0) {
            noRow.classList.remove('hidden');
            noRow.style.display = '';
        } else {
            noRow.classList.add('hidden');
            noRow.style.display = 'none';
        }
    }

    if (showingEntriesText) {
        if (totalMatching === 0) {
            showingEntriesText.textContent = 'Showing 0 entries';
        } else {
            const fromNum = startIndex + 1;
            const toNum = Math.min(endIndex, totalMatching);
            showingEntriesText.textContent = `Showing ${fromNum} to ${toNum} of ${totalMatching} entries`;
        }
    }

    renderPaginationControls(currentPage, totalPages);
}

function renderPaginationControls(page, totalPages) {
    const container = document.getElementById('paginationButtonsContainer');
    if (!container) return;

    let html = '';

    const prevDisabled = page === 1 ? 'disabled opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:text-slate-700 cursor-pointer';
    html += `<button onclick="goToPage(1)" ${page === 1 ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 transition ${prevDisabled}"><i class="fa-solid fa-angles-left text-[10px]"></i></button>`;
    html += `<button onclick="goToPage(${page - 1})" ${page === 1 ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 transition ${prevDisabled}"><i class="fa-solid fa-angle-left text-[10px]"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === page) {
            html += `<button class="w-8 h-8 rounded-lg flex items-center justify-center text-white bg-[#0f53d1] font-bold text-xs shadow-sm">${p}</button>`;
        } else {
            html += `<button onclick="goToPage(${p})" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-xs transition cursor-pointer">${p}</button>`;
        }
    }

    const nextDisabled = page === totalPages ? 'disabled opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 hover:text-slate-700 cursor-pointer';
    html += `<button onclick="goToPage(${page + 1})" ${page === totalPages ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 transition ${nextDisabled}"><i class="fa-solid fa-angle-right text-[10px]"></i></button>`;
    html += `<button onclick="goToPage(${totalPages})" ${page === totalPages ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 transition ${nextDisabled}"><i class="fa-solid fa-angles-right text-[10px]"></i></button>`;

    container.innerHTML = html;
}

function resetDistrictFilters() {
    activeCardFilters.clear();
    updateStatCardsHighlight();

    const districtFilter = document.getElementById('districtFilter');
    const barangayFilter = document.getElementById('barangayFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sexFilter = document.getElementById('sexFilter');
    const civilStatusFilter = document.getElementById('civilStatusFilter');
    const ageRangeFilter = document.getElementById('ageRangeFilter');
    const searchInput = document.getElementById('searchInput');

    if (districtFilter) districtFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (sexFilter) sexFilter.value = '';
    if (civilStatusFilter) civilStatusFilter.value = '';
    if (ageRangeFilter) ageRangeFilter.value = '';
    if (searchInput) searchInput.value = '';

    updateBarangayDropdown('');
    filterCitizensByDistrict();
}

document.addEventListener('DOMContentLoaded', function () {
    const districtFilter = document.getElementById('districtFilter');
    const selectedDistrict = districtFilter ? districtFilter.value.trim() : '';
    updateBarangayDropdown(selectedDistrict);
    filterCitizensByDistrict();
    updateSelectAllState();
});

function toggleSelectAllCitizens(masterCheckbox) {
    const isChecked = masterCheckbox ? masterCheckbox.checked : false;
    const master1 = document.getElementById('selectAllCheckboxToolbar');
    const master2 = document.getElementById('selectAllCheckboxHeader');
    if (master1) { master1.checked = isChecked; master1.indeterminate = false; }
    if (master2) { master2.checked = isChecked; master2.indeterminate = false; }

    const rowCheckboxes = document.querySelectorAll('.citizen-row-checkbox');
    rowCheckboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = isChecked;
            if (isChecked) {
                row.classList.add('bg-blue-50/30');
            } else {
                row.classList.remove('bg-blue-50/30');
            }
        }
    });
    updateSelectAllState();
}

function toggleSelectAllBtnClick() {
    const visibleRowCheckboxes = Array.from(document.querySelectorAll('.citizen-row-checkbox')).filter(cb => {
        const row = cb.closest('tr');
        return row && row.style.display !== 'none';
    });

    const allChecked = visibleRowCheckboxes.length > 0 && visibleRowCheckboxes.every(cb => cb.checked);
    const targetState = !allChecked;

    const master1 = document.getElementById('selectAllCheckboxToolbar');
    if (master1) master1.checked = targetState;
    toggleSelectAllCitizens(master1 || { checked: targetState });
}

function updateSelectAllState() {
    const master1 = document.getElementById('selectAllCheckboxToolbar');
    const master2 = document.getElementById('selectAllCheckboxHeader');
    const selectAllTextBtn = document.getElementById('selectAllTextBtn');
    
    const visibleRowCheckboxes = Array.from(document.querySelectorAll('.citizen-row-checkbox')).filter(cb => {
        const row = cb.closest('tr');
        return row && row.style.display !== 'none';
    });

    const allChecked = visibleRowCheckboxes.length > 0 && visibleRowCheckboxes.every(cb => cb.checked);
    const someChecked = visibleRowCheckboxes.some(cb => cb.checked);

    [master1, master2].forEach(m => {
        if (m) {
            m.checked = allChecked;
            m.indeterminate = !allChecked && someChecked;
        }
    });

    if (selectAllTextBtn) {
        selectAllTextBtn.textContent = allChecked ? 'Unselect all citizens' : 'Select all citizens';
    }

    document.querySelectorAll('.citizen-row-checkbox').forEach(cb => {
        const row = cb.closest('tr');
        if (row) {
            if (cb.checked) {
                row.classList.add('bg-blue-50/30');
            } else {
                row.classList.remove('bg-blue-50/30');
            }
        }
    });

    updateSelectedCounter();
}

function updateSelectedCounter() {
    const selectedCountSpan = document.getElementById('selectedCountSpan');
    if (!selectedCountSpan) return;
    const checkedCount = document.querySelectorAll('.citizen-row-checkbox:checked').length;
    selectedCountSpan.textContent = `${checkedCount} selected`;
}

function toggleCitizenRow(event, rowElement) {
    const target = event.target;
    if (target.closest('button') || target.closest('a') || (target.closest('.group') && target.closest('div.relative'))) {
        return;
    }

    const checkbox = rowElement.querySelector('.citizen-row-checkbox');
    if (!checkbox) return;

    if (target !== checkbox) {
        checkbox.checked = !checkbox.checked;
    }

    updateSelectAllState();
}

function markSelectedForValidation() {
    changeSelectedCitizensStatus('Pending Validation');
}

function toggleChangeStatusDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('changeStatusMenu');
    const button = event ? event.currentTarget : null;
    
    if (menu) {
        const isHidden = menu.classList.contains('hidden');
        if (isHidden && button) {
            const rect = button.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + 6) + 'px';
            menu.style.left = rect.left + 'px';
            menu.style.zIndex = '9999';
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    }
}

document.addEventListener('click', function (e) {
    const changeStatusMenu = document.getElementById('changeStatusMenu');
    if (changeStatusMenu && !changeStatusMenu.contains(e.target) && !e.target.closest('button[onclick*="toggleChangeStatusDropdown"]')) {
        changeStatusMenu.classList.add('hidden');
    }

    const rowMenu = document.getElementById('globalRowActionsMenu');
    if (rowMenu && !rowMenu.contains(e.target) && !e.target.closest('button[onclick*="toggleRowActionsMenu"]')) {
        rowMenu.classList.add('hidden');
    }
});

window.addEventListener('scroll', function () {
    const changeStatusMenu = document.getElementById('changeStatusMenu');
    if (changeStatusMenu && !changeStatusMenu.classList.contains('hidden')) {
        changeStatusMenu.classList.add('hidden');
    }

    const rowMenu = document.getElementById('globalRowActionsMenu');
    if (rowMenu && !rowMenu.classList.contains('hidden')) {
        rowMenu.classList.add('hidden');
    }
}, true);

function toggleRowActionsMenu(event, buttonElement, citizenId) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('globalRowActionsMenu');
    if (!menu) return;

    if (!menu.classList.contains('hidden') && menu.dataset.activeId === citizenId) {
        menu.classList.add('hidden');
        return;
    }

    menu.dataset.activeId = citizenId;

    const rect = buttonElement.getBoundingClientRect();
    const menuWidth = 208;
    const menuHeight = 265;

    let topPos = rect.bottom + 4;
    if (topPos + menuHeight > window.innerHeight) {
        topPos = rect.top - menuHeight - 4;
    }

    let leftPos = rect.right - menuWidth;
    if (leftPos < 10) leftPos = 10;

    menu.style.position = 'fixed';
    menu.style.top = topPos + 'px';
    menu.style.left = leftPos + 'px';
    menu.style.zIndex = '9999';
    menu.classList.remove('hidden');
}

function handleRowAction(event, action) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const menu = document.getElementById('globalRowActionsMenu');
    if (!menu) return;
    const citizenId = menu.dataset.activeId;
    menu.classList.add('hidden');

    if (action === 'mark-validation') {
        const rows = document.querySelectorAll('tbody tr[data-district]');
        rows.forEach(row => {
            if (row.children[1] && row.children[1].textContent.trim() === citizenId) {
                row.setAttribute('data-status', 'Pending Validation');
                const statusCell = row.children[9];
                if (statusCell) {
                    statusCell.innerHTML = getStatusBadgeHtml('Pending Validation');
                }
            }
        });
        filterCitizensByDistrict();
    } else if (action === 'archive-record') {
        const rows = document.querySelectorAll('tbody tr[data-district]');
        rows.forEach(row => {
            if (row.children[1] && row.children[1].textContent.trim() === citizenId) {
                row.style.display = 'none';
            }
        });
    }
}

function getStatusBadgeHtml(status) {
    switch (status) {
        case 'Active':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-emerald-100 text-emerald-700 border-emerald-200">Active</span>';
        case 'Senior Citizen':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-blue-100 text-blue-700 border-blue-200">Senior Citizen</span>';
        case 'Pending Validation':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-amber-100 text-amber-700 border-amber-200">Pending Validation</span>';
        case 'Inactive':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-slate-100 text-slate-700 border-slate-200">Inactive</span>';
        case 'Deceased':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-red-100 text-red-700 border-red-200">Deceased</span>';
        case 'Transferred Out':
            return '<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-orange-100 text-orange-700 border-orange-200">Transferred Out</span>';
        default:
            return `<span class="px-2.5 py-1 text-[10px] font-bold rounded-md border bg-slate-100 text-slate-700 border-slate-200">${status}</span>`;
    }
}

function changeSelectedCitizensStatus(newStatus) {
    const checkedRowCheckboxes = document.querySelectorAll('.citizen-row-checkbox:checked');
    if (checkedRowCheckboxes.length === 0) {
        alert('Please select at least one citizen to change status.');
        const menu = document.getElementById('changeStatusMenu');
        if (menu) menu.classList.add('hidden');
        return;
    }

    checkedRowCheckboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row) {
            row.setAttribute('data-status', newStatus);
            const statusCell = row.children[9];
            if (statusCell) {
                statusCell.innerHTML = getStatusBadgeHtml(newStatus);
            }
        }
    });

    const menu = document.getElementById('changeStatusMenu');
    if (menu) menu.classList.add('hidden');

    filterCitizensByDistrict();
}

let currentViewMode = 'table';

function switchViewMode(mode) {
    currentViewMode = mode;
    const tableBtn = document.getElementById('tableViewBtn');
    const householdBtn = document.getElementById('householdViewBtn');
    const tbody = document.querySelector('tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr[data-district]'));
    const noRow = document.getElementById('noCitizensRow');

    if (mode === 'household') {
        if (tableBtn) {
            tableBtn.className = "flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-transparent bg-slate-50 shadow-xs cursor-pointer hover:border-slate-200 transition group";
            const iconDiv = tableBtn.querySelector('div');
            const h4 = tableBtn.querySelector('h4');
            if (iconDiv) iconDiv.className = "w-6 h-6 rounded-md bg-white flex items-center justify-center border border-slate-200 text-slate-400 text-xs transition-transform group-hover:scale-105 group-hover:text-slate-600";
            if (h4) h4.className = "text-xs font-bold text-slate-700 leading-tight";
        }
        if (householdBtn) {
            householdBtn.className = "flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-[#0f53d1]/30 bg-blue-50/50 shadow-xs cursor-pointer hover:bg-blue-50 transition group";
            const iconDiv = householdBtn.querySelector('div');
            const h4 = householdBtn.querySelector('h4');
            if (iconDiv) iconDiv.className = "w-6 h-6 rounded-md bg-white flex items-center justify-center border border-[#0f53d1]/20 text-[#0f53d1] text-xs transition-transform group-hover:scale-105";
            if (h4) h4.className = "text-xs font-bold text-[#0f53d1] leading-tight";
        }

        // Sort rows by Household ID
        rows.sort((a, b) => {
            const hhA = a.getAttribute('data-household') || '';
            const hhB = b.getAttribute('data-household') || '';
            return hhA.localeCompare(hhB);
        });

    } else {
        if (householdBtn) {
            householdBtn.className = "flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-transparent bg-slate-50 shadow-xs cursor-pointer hover:border-slate-200 transition group";
            const iconDiv = householdBtn.querySelector('div');
            const h4 = householdBtn.querySelector('h4');
            if (iconDiv) iconDiv.className = "w-6 h-6 rounded-md bg-white flex items-center justify-center border border-slate-200 text-slate-400 text-xs transition-transform group-hover:scale-105 group-hover:text-slate-600";
            if (h4) h4.className = "text-xs font-bold text-slate-700 leading-tight";
        }
        if (tableBtn) {
            tableBtn.className = "flex items-center gap-2.5 px-3.5 py-2 rounded-xl border-2 border-[#0f53d1]/30 bg-blue-50/50 shadow-xs cursor-pointer hover:bg-blue-50 transition group";
            const iconDiv = tableBtn.querySelector('div');
            const h4 = tableBtn.querySelector('h4');
            if (iconDiv) iconDiv.className = "w-6 h-6 rounded-md bg-white flex items-center justify-center border border-[#0f53d1]/20 text-[#0f53d1] text-xs transition-transform group-hover:scale-105";
            if (h4) h4.className = "text-xs font-bold text-[#0f53d1] leading-tight";
        }

        // Sort rows by Citizen ID
        rows.sort((a, b) => {
            const idA = a.children[1] ? a.children[1].textContent.trim() : '';
            const idB = b.children[1] ? b.children[1].textContent.trim() : '';
            return idA.localeCompare(idB);
        });
    }

    rows.forEach(r => tbody.appendChild(r));
    if (noRow) tbody.appendChild(noRow);

    filterCitizensByDistrict();
}
</script>

<!-- Floating Global Row Actions Dropdown Overlay -->
<div id="globalRowActionsMenu" class="hidden fixed w-52 bg-white border border-slate-200 rounded-xl shadow-xl py-1.5 z-[9999] text-xs font-medium text-slate-600 text-left">
    <a href="#" onclick="handleRowAction(event, 'view-profile')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-regular fa-user text-slate-400 w-4 text-center"></i> View Profile</a>
    <a href="#" onclick="handleRowAction(event, 'edit-citizen')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-solid fa-pen text-slate-400 w-4 text-center"></i> Edit Citizen</a>
    <a href="#" onclick="handleRowAction(event, 'view-household')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-solid fa-house-user text-slate-400 w-4 text-center"></i> View Household</a>
    <a href="#" onclick="handleRowAction(event, 'edit-history')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-solid fa-clock-rotate-left text-slate-400 w-4 text-center"></i> View Edit History</a>
    <a href="#" onclick="handleRowAction(event, 'generate-pdf')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-solid fa-file-pdf text-slate-400 w-4 text-center"></i> Generate PDF</a>
    <a href="#" onclick="handleRowAction(event, 'mark-validation')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition"><i class="fa-solid fa-shield-halved text-slate-400 w-4 text-center"></i> Mark for Validation</a>
    <div class="border-t border-slate-100 my-1"></div>
    <a href="#" onclick="handleRowAction(event, 'archive-record')" class="flex items-center gap-2.5 px-4 py-2 hover:bg-red-50 text-red-600 transition"><i class="fa-solid fa-trash-can opacity-80 w-4 text-center"></i> Archive Record</a>
</div>

<?php include '../../includes/footer.php'; ?>
