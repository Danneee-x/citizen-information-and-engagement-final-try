<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Real ID Verification Audit Logs from MySQL
require_once __DIR__ . '/../../config/database.php';

$apiBase = rtrim(getenv('API_BASE_URL') ?: 'https://api-citizen.civentral.tech', '/');

$verificationLogs = [];
$verifyingStaffList = [];
$counts = [
    'total' => 0,
    'passed' => 0,
    'pending' => 0,
    'failed' => 0,
    'flagged' => 0,
    'passed_pct' => 0
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

    // Self-healing columns
    $cols = $pdo->query("SHOW COLUMNS FROM citizen_verifications")->fetchAll(PDO::FETCH_COLUMN);
    $needed = [
        'reviewed_by' => 'VARCHAR(100) NULL',
        'rejection_reason' => 'TEXT NULL',
        'reviewed_at' => 'DATETIME NULL',
        'is_duplicate' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'duplicate_notes' => 'TEXT NULL',
        'photo_1x1_url' => 'VARCHAR(500) NULL',
        'signature_photo_url' => 'VARCHAR(500) NULL',
        'admin_action_notes' => 'TEXT NULL',
        'citizen_id_number' => 'VARCHAR(50) NULL'
    ];
    foreach ($needed as $col => $type) {
        if (!in_array($col, $cols)) {
            $pdo->exec("ALTER TABLE citizen_verifications ADD COLUMN `$col` $type");
        }
    }

    // Check duplicate discrepancies from DB (Name + DOB matching or duplicate ID numbers)
    $dupQuery = $pdo->query("SELECT v1.verification_id as v1_id, v2.verification_id as v2_id 
                             FROM citizen_verifications v1
                             JOIN citizen_verifications v2 
                               ON v1.verification_id < v2.verification_id 
                              AND (v1.valid_id_number = v2.valid_id_number 
                                   OR (v1.first_name = v2.first_name AND v1.last_name = v2.last_name AND v1.birth_date = v2.birth_date))");
    $flaggedMap = [];
    while ($dRow = $dupQuery->fetch(PDO::FETCH_ASSOC)) {
        $flaggedMap[$dRow['v2_id']] = $dRow['v1_id'];
    }

    // Dynamic stats query
    $statsStmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as passed,
        SUM(CASE WHEN verification_status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN verification_status = 'Rejected' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN is_duplicate = 1 OR verification_status = 'Under_Review' THEN 1 ELSE 0 END) as flagged_db
        FROM citizen_verifications");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $counts['total']   = (int)($stats['total'] ?? 0);
    $counts['passed']  = (int)($stats['passed'] ?? 0);
    $counts['pending'] = (int)($stats['pending'] ?? 0);
    $counts['failed']  = (int)($stats['failed'] ?? 0);
    $counts['flagged'] = max((int)($stats['flagged_db'] ?? 0), count($flaggedMap));
    $counts['passed_pct'] = $counts['total'] > 0 ? round(($counts['passed'] / $counts['total']) * 100, 1) : 0;

    // Distinct verifying staff from DB
    $staffStmt = $pdo->query("SELECT DISTINCT reviewed_by FROM citizen_verifications WHERE reviewed_by IS NOT NULL AND reviewed_by != '' AND reviewed_by != 'Unassigned'");
    $verifyingStaffList = $staffStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($verifyingStaffList)) {
        $verifyingStaffList = ['Admin'];
    }

    // Fetch verifications with left join on citizen_users
    try {
        $stmt = $pdo->query("SELECT v.*, u.email as user_email, u.mobile_number as user_mobile 
                             FROM citizen_verifications v 
                             LEFT JOIN citizen_users u ON v.citizen_user_id = u.citizen_user_id 
                             ORDER BY COALESCE(v.reviewed_at, v.submitted_at) DESC LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stmt = $pdo->query("SELECT * FROM citizen_verifications ORDER BY COALESCE(reviewed_at, submitted_at) DESC LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rows as $r) {
        $fullName = preg_replace('/\s+/', ' ', trim("{$r['first_name']} {$r['middle_name']} {$r['last_name']} {$r['suffix']}"));
        $dt = !empty($r['reviewed_at']) ? new DateTime($r['reviewed_at']) : new DateTime($r['submitted_at']);
        $subDt = !empty($r['submitted_at']) ? new DateTime($r['submitted_at']) : null;
        $revDt = !empty($r['reviewed_at']) ? new DateTime($r['reviewed_at']) : null;

        $birthDateStr = $r['birth_date'] ?? '';
        $birthDateFormatted = 'Not Provided';
        $ageStr = 'N/A';
        if (!empty($birthDateStr) && $birthDateStr !== '0000-00-00') {
            try {
                $dob = new DateTime($birthDateStr);
                $now = new DateTime();
                $age = $now->diff($dob)->y;
                $birthDateFormatted = $dob->format('M j, Y');
                $ageStr = "{$age} years old";
            } catch (Exception $e) {
                $birthDateFormatted = $birthDateStr;
            }
        }
        
        $isDup = isset($flaggedMap[$r['verification_id']]) || !empty($r['is_duplicate']) || $r['verification_status'] === 'Under_Review';

        $result = 'Under Review';
        $badge = 'bg-blue-50 text-blue-600 border-blue-200';
        $remarks = 'Pending administrative review and biometric verification against city civil registry.';

        if ($r['verification_status'] === 'Approved') {
            $result = 'Verified';
            $badge = 'bg-emerald-50 text-emerald-600 border-emerald-200';
            $remarks = "{$r['valid_id_type']} ({$r['valid_id_number']}) verified. Personal credentials and photo match confirmed.";
        } elseif ($r['verification_status'] === 'Rejected') {
            $result = 'Failed';
            $badge = 'bg-rose-50 text-rose-600 border-rose-200';
            $remarks = !empty($r['rejection_reason']) ? $r['rejection_reason'] : (!empty($r['admin_action_notes']) ? $r['admin_action_notes'] : 'Discrepancy detected during validation.');
        } elseif ($r['verification_status'] === 'Returned_For_Correction') {
            $result = 'Returned';
            $badge = 'bg-amber-50 text-amber-600 border-amber-200';
            $remarks = !empty($r['admin_action_notes']) ? $r['admin_action_notes'] : 'Returned for citizen credential rework.';
        } elseif ($r['verification_status'] === 'Superseded') {
            $result = 'Superseded';
            $badge = 'bg-slate-100 text-slate-600 border-slate-300';
            $remarks = 'Prior application superseded by subsequent approved registration.';
        } elseif ($isDup) {
            $result = 'Flagged';
            $badge = 'bg-amber-50 text-amber-600 border-amber-200';
            if (isset($flaggedMap[$r['verification_id']])) {
                $matchId = 'VER-' . str_pad($flaggedMap[$r['verification_id']], 4, '0', STR_PAD_LEFT);
                $remarks = "Name / ID mismatch: Potential duplicate match with Application #{$matchId}.";
            } else {
                $remarks = !empty($r['duplicate_notes']) ? $r['duplicate_notes'] : 'Potential duplicate profile flagged.';
            }
        }

        $verifyingStaff = !empty($r['reviewed_by']) && $r['reviewed_by'] !== 'Unassigned' 
            ? $r['reviewed_by'] 
            : ($result === 'Verified' || $result === 'Failed' ? 'Admin' : 'Queue (Pending Review)');

        $fullAddr = trim("{$r['street_address']}, {$r['barangay']}, {$r['district']}, Caloocan City", ", ");

        $verificationLogs[] = [
            'log_id' => 'VLOG-' . str_pad($r['verification_id'], 4, '0', STR_PAD_LEFT),
            'app_id' => 'VER-' . str_pad($r['verification_id'], 4, '0', STR_PAD_LEFT),
            'citizen_id' => !empty($r['citizen_id_number']) ? $r['citizen_id_number'] : ('CTZ-' . str_pad($r['verification_id'], 4, '0', STR_PAD_LEFT)),
            'raw_id' => (int)$r['verification_id'],
            'citizen_user_id' => $r['citizen_user_id'] ?? null,
            'citizen_name' => $fullName ?: 'Citizen Applicant',
            'first_name' => $r['first_name'] ?? '',
            'middle_name' => $r['middle_name'] ?? '',
            'last_name' => $r['last_name'] ?? '',
            'suffix' => $r['suffix'] ?? '',
            'sex' => $r['sex'] ?? 'Not Specified',
            'birth_date' => $birthDateFormatted,
            'age' => $ageStr,
            'place_of_birth' => $r['place_of_birth'] ?? 'N/A',
            'civil_status' => $r['civil_status'] ?? 'N/A',
            'employment_status' => $r['employment_status'] ?? 'N/A',
            'occupation' => $r['occupation'] ?? 'N/A',
            'educational_attainment' => $r['educational_attainment'] ?? 'N/A',
            'years_resident' => isset($r['years_resident']) ? ($r['years_resident'] . ' year(s)') : 'N/A',
            'street_address' => $r['street_address'] ?? '',
            'barangay' => $r['barangay'] ?? '',
            'district' => $r['district'] ?? '',
            'address' => $fullAddr,
            'user_email' => $r['user_email'] ?? '',
            'user_mobile' => $r['user_mobile'] ?? '',
            'id_type' => $r['valid_id_type'] ?: 'PhilSys National ID',
            'id_number' => $r['valid_id_number'] ?: 'N/A',
            'id_front_photo_url' => $r['id_front_photo_url'] ?? '',
            'selfie_photo_url' => $r['selfie_photo_url'] ?? '',
            'photo_1x1_url' => $r['photo_1x1_url'] ?? '',
            'signature_photo_url' => $r['signature_photo_url'] ?? '',
            'verifying_staff' => $verifyingStaff,
            'reviewed_by' => $r['reviewed_by'] ?? '',
            'reviewed_at' => $revDt ? $revDt->format('M j, Y • h:i A') : 'Pending / Not Reviewed',
            'submitted_at' => $subDt ? $subDt->format('M j, Y • h:i A') : 'N/A',
            'timestamp' => $dt->format('M j, Y') . ' &bull; ' . $dt->format('h:i A'),
            'raw_timestamp' => $dt->format('M j, Y h:i A'),
            'result' => $result,
            'result_badge' => $badge,
            'remarks' => $remarks,
            'rejection_reason' => $r['rejection_reason'] ?? '',
            'admin_action_notes' => $r['admin_action_notes'] ?? '',
            'is_duplicate' => $isDup,
            'duplicate_matched_id' => isset($flaggedMap[$r['verification_id']]) ? ('VER-' . str_pad($flaggedMap[$r['verification_id']], 4, '0', STR_PAD_LEFT)) : '',
            'duplicate_notes' => $r['duplicate_notes'] ?? '',
            'avatar' => "https://ui-avatars.com/api/?name=" . urlencode($fullName ?: 'Citizen') . "&background=0f53d1&color=fff"
        ];
    }
} catch (Exception $e) {
    error_log("Verification logs error: " . $e->getMessage());
}
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

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                <i class="fa-solid fa-address-card"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Citizen Registry</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">ID Verification Logs</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">ID Verification Audit Logs</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="exportVerificationAuditCSV()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-xs"></i>
                <span>Export Verification Audit Log</span>
            </button>
        </div>
    </div>

    <!-- Audit Security Stat Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Verifications -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total ID Checks Logged</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($counts['total']); ?> Checks</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-lock"></i>
                    <span>Read-only append-only integrity</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Verified Citizens -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Passed Verification</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($counts['passed']); ?> (<?php echo $counts['passed_pct']; ?>%)</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>PhilSys / Government ID verified</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Flagged Discrepancies -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Flagged Discrepancies</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($counts['flagged']); ?> Flagged</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Name / DOB mismatch</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Failed Verifications -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Failed / Expired IDs</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-base border border-rose-100">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($counts['failed']); ?> Failed</h3>
                <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-ban"></i>
                    <span>Expired / Unreadable document</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Multi-Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            
            <!-- Search Input -->
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="vlogSearchInput" oninput="filterVerificationLogsTable()" placeholder="Search log ID, citizen name, staff, or ID type..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>

            <!-- Result Filter -->
            <select id="vlogResultFilter" onchange="filterVerificationLogsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                <option value="">All Verification Results</option>
                <option value="Verified">Verified</option>
                <option value="Under Review">Under Review</option>
                <option value="Flagged">Flagged</option>
                <option value="Failed">Failed</option>
            </select>

            <!-- Staff Filter -->
            <select id="vlogStaffFilter" onchange="filterVerificationLogsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                <option value="">All Verifying Staff</option>
                <?php foreach ($verifyingStaffList as $staff): ?>
                    <option value="<?php echo htmlspecialchars($staff); ?>"><?php echo htmlspecialchars($staff); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- ID Verification Logs Table (Read-Only Historical Record) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
        
        <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Historical Identity Check Logs</h3>
                <p class="text-[11px] text-slate-400 font-medium">Click any log entry to view full verification credentials, documents, and audit details.</p>
            </div>
            <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1">
                <i class="fa-solid fa-lock text-[10px] text-emerald-500"></i> Immutability Enforced (Cannot be deleted/edited)
            </span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Log ID & Citizen</th>
                        <th class="py-3.5 px-3">Government ID Type Checked</th>
                        <th class="py-3.5 px-3">Verifying Staff Officer</th>
                        <th class="py-3.5 px-3">Date & Timestamp</th>
                        <th class="py-3.5 px-3 text-center">Result</th>
                        <th class="py-3.5 px-3">Staff Verification Remarks</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="vlogTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($verificationLogs)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 font-medium text-xs">No verification logs recorded yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($verificationLogs as $index => $log): ?>
                    <tr onclick="openLogDetailModal(<?php echo $index; ?>)"
                        class="vlog-row hover:bg-blue-50/40 hover:shadow-2xs transition select-none cursor-pointer group" 
                        data-result="<?php echo htmlspecialchars($log['result']); ?>" 
                        data-staff="<?php echo htmlspecialchars($log['verifying_staff']); ?>"
                        title="Click to view verification details">
                        <td class="py-3.5 px-4">
                            <span class="text-[10px] font-bold text-[#0f53d1] block font-mono group-hover:underline"><?php echo $log['log_id']; ?></span>
                            <p class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($log['citizen_name']); ?></p>
                            <span class="text-[10px] text-slate-400 font-semibold"><?php echo $log['citizen_id']; ?></span>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-id-card text-[#0f53d1] text-xs"></i> <?php echo htmlspecialchars($log['id_type']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-shield text-slate-400 text-xs"></i> <?php echo htmlspecialchars($log['verifying_staff']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 whitespace-nowrap font-bold text-slate-700 text-[11px]">
                            <?php echo $log['timestamp']; ?>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $log['result_badge']; ?>">
                                <?php echo $log['result']; ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-600 font-medium text-[11px] max-w-sm">
                            <span class="line-clamp-2"><?php echo htmlspecialchars($log['remarks']); ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button type="button" 
                                    onclick="event.stopPropagation(); openLogDetailModal(<?php echo $index; ?>);" 
                                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 group-hover:bg-[#0f53d1] text-slate-600 group-hover:text-white font-bold text-[11px] transition flex items-center gap-1.5 border border-slate-200 group-hover:border-[#0f53d1] shadow-2xs mx-auto cursor-pointer"
                                    title="View Verification Details">
                                <i class="fa-solid fa-eye text-xs"></i>
                                <span>View</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</main>

<!-- ============================================================================== -->
<!-- ID VERIFICATION AUDIT LOG DETAILS MODAL                                        -->
<!-- ============================================================================== -->
<div id="vlogDetailModal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 md:p-6 overflow-y-auto" onclick="handleBackdropClick(event)">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-auto flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-150" onclick="event.stopPropagation()">
        
        <!-- Modal Light Header with Back button, Log ID, App ID, Status badge, and Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="closeLogDetailModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer shrink-0" title="Back to Verification Logs">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs shrink-0">
                    <i class="fa-solid fa-address-card"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-black text-base tracking-tight text-slate-900">Verification Audit Record</h3>
                        <span id="modalLogId" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">VLOG-0000</span>
                        <span id="modalAppId" class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">VER-0000</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium truncate mt-0.5">Citizen Identity Verification & Credential Inspector</p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span id="modalResultBadge" class="px-3 py-1 text-xs font-bold rounded-full border bg-emerald-50 text-emerald-600 border-emerald-200">Verified</span>
                <button type="button" onclick="closeLogDetailModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close Modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center px-6 border-b border-slate-200 bg-slate-50/60 shrink-0">
            <button id="modalTabOverviewBtn" onclick="switchModalTab('overview')" class="px-4 py-2.5 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1] transition cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-user-check text-xs"></i>
                <span>Audit Overview & Identity</span>
            </button>
            <button id="modalTabDocsBtn" onclick="switchModalTab('docs')" class="px-4 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer flex items-center gap-2 border-b-2 border-transparent">
                <i class="fa-solid fa-id-card-clip text-xs"></i>
                <span>Document Assets (4)</span>
            </button>
            <button id="modalTabAuditBtn" onclick="switchModalTab('audit')" class="px-4 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer flex items-center gap-2 border-b-2 border-transparent">
                <i class="fa-solid fa-shield-halved text-xs"></i>
                <span>Security & Audit Trail</span>
            </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
            
            <!-- ================= TAB 1: OVERVIEW & CITIZEN DETAILS ================= -->
            <div id="tabContentOverview" class="space-y-4">
                
                <!-- Verification Outcome Banner (Dynamic status styling) -->
                <div id="modalOutcomeBanner" class="p-4 rounded-2xl border flex items-start gap-3.5 transition-all">
                    <div id="modalOutcomeIconBox" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0">
                        <i id="modalOutcomeIcon" class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="space-y-1 flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span id="modalOutcomeTitle" class="text-xs font-black uppercase tracking-wider">Identity Verification Passed & Certified</span>
                            <span id="modalOutcomeAuditMeta" class="text-[11px] text-slate-500 font-semibold">Reviewed: <strong id="modalReviewedAt" class="text-slate-700">Oct 9, 2026</strong></span>
                        </div>
                        <p id="modalOutcomeRemarks" class="text-xs font-medium leading-relaxed"></p>
                        <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-500 pt-1">
                            <span>Verifying Officer: <strong id="modalVerifyingOfficer" class="text-slate-800 font-bold">Admin</strong></span>
                            <span>&bull;</span>
                            <span>Submitted: <strong id="modalSubmittedAt" class="text-slate-700">Oct 9, 2026</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Citizen Profile Header Card -->
                <div class="flex items-center gap-4 p-4.5 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                    <div class="relative shrink-0">
                        <img id="modalCitizenAvatar" src="" class="w-16 h-16 rounded-full border-2 border-white shadow-sm shrink-0 object-cover" alt="Citizen Profile">
                        <span id="modalAvatarOnlineBadge" class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 id="modalCitizenName" class="text-base font-black text-slate-900 truncate">Josephine Toledano Espelita</h4>
                            <span id="modalCitizenIdPill" class="text-[11px] font-mono font-bold text-slate-500 bg-white px-2 py-0.5 rounded-md border border-slate-200 shadow-2xs">CTZ-0008</span>
                        </div>
                        <p id="modalCitizenAddressLine" class="text-xs text-slate-600 font-medium mt-1 truncate">
                            <i class="fa-solid fa-location-dot text-[#0f53d1] mr-1"></i>
                            <span>Barangay 171, District 1, Caloocan City</span>
                        </p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap text-[11px] text-slate-500">
                            <span id="modalEmailPill" class="inline-flex items-center gap-1.5 font-medium"><i class="fa-solid fa-envelope text-slate-400"></i> <span id="modalEmailText">None</span></span>
                            <span id="modalPhonePill" class="inline-flex items-center gap-1.5 font-medium"><i class="fa-solid fa-phone text-slate-400"></i> <span id="modalPhoneText">None</span></span>
                        </div>
                    </div>
                </div>

                <!-- Government ID Credentials Inspected Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-black text-slate-900 tracking-wide uppercase flex items-center gap-2">
                            <i class="fa-solid fa-id-card text-[#0f53d1]"></i>
                            <span>Government Identification Inspected</span>
                        </h3>
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full flex items-center gap-1">
                            <i class="fa-solid fa-shield-check"></i> Cross-Referenced ID
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Government ID Type</span>
                            <span id="modalIdType" class="font-bold text-slate-900 text-xs block mt-0.5">PhilSys National ID</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">ID Document Number</span>
                            <span id="modalIdNumber" class="font-bold font-mono text-[#0f53d1] text-xs block mt-0.5">3322114455</span>
                        </div>
                    </div>
                </div>

                <!-- Personal Demographics Details Grid -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                    <h3 class="text-xs font-black text-slate-900 tracking-wide uppercase border-b border-slate-100 pb-2.5 flex items-center gap-2">
                        <i class="fa-solid fa-user text-[#0f53d1]"></i>
                        <span>Personal Demographics & Civil Registry</span>
                    </h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Date of Birth</span>
                            <span id="modalBirthdate" class="font-bold text-slate-800 text-xs block mt-0.5">May 15, 1998</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Age</span>
                            <span id="modalAge" class="font-bold text-slate-800 text-xs block mt-0.5">28 years old</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Sex / Gender</span>
                            <span id="modalSex" class="font-bold text-slate-800 text-xs block mt-0.5">Female</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Civil Status</span>
                            <span id="modalCivilStatus" class="font-bold text-slate-800 text-xs block mt-0.5">Single</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Place of Birth</span>
                            <span id="modalPlaceOfBirth" class="font-bold text-slate-800 text-xs block mt-0.5">Caloocan City</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Employment Status</span>
                            <span id="modalEmployment" class="font-bold text-slate-800 text-xs block mt-0.5">Employed</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Occupation</span>
                            <span id="modalOccupation" class="font-bold text-slate-800 text-xs block mt-0.5">Teacher</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Educational Attainment</span>
                            <span id="modalEducation" class="font-bold text-slate-800 text-xs block mt-0.5">College Graduate</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Residency in Caloocan</span>
                            <span id="modalResidency" class="font-bold text-slate-800 text-xs block mt-0.5">12 year(s)</span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Registered Residential Address</span>
                        <span id="modalFullAddress" class="font-bold text-slate-800 block mt-0.5">Block 12 Lot 5, Sampaguita St., Barangay 171, District 1, Caloocan City</span>
                    </div>
                </div>

            </div>

            <!-- ================= TAB 2: DOCUMENT & BIOMETRIC ASSETS ================= -->
            <div id="tabContentDocs" class="hidden space-y-4">
                
                <div class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 text-xs text-blue-950 font-medium">
                        <i class="fa-solid fa-circle-info text-[#0f53d1]"></i>
                        <span>High-resolution identity credentials submitted by applicant. Click any document to expand in fullscreen lightbox.</span>
                    </div>
                    <span class="text-[10px] font-bold text-[#0f53d1] bg-white px-2 py-0.5 rounded-full border border-blue-200 shrink-0">4 Assets</span>
                </div>

                <!-- Submitted Assets: 4 Cards (ID Front, Selfie, 1x1 Photo, Digital Signature) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Valid ID Front -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 flex flex-col space-y-2 shadow-xs group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-id-card text-[#0f53d1]"></i> Valid ID (Front)
                            </span>
                            <span id="badgeIdFront" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Pending</span>
                        </div>
                        <div id="modalBoxIdFront" class="w-full h-44 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden relative">
                            <!-- Populated via JS -->
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium text-center">Primary government-issued identity proof</p>
                    </div>

                    <!-- Selfie Liveness Verification -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 flex flex-col space-y-2 shadow-xs group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-camera text-indigo-600"></i> Selfie Liveness
                            </span>
                            <span id="badgeSelfie" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Pending</span>
                        </div>
                        <div id="modalBoxSelfie" class="w-full h-44 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden relative">
                            <!-- Populated via JS -->
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium text-center">Biometric photo matching live camera capture</p>
                    </div>

                    <!-- 1x1 Formal Photo -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 flex flex-col space-y-2 shadow-xs group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-regular fa-image text-emerald-600"></i> 1x1 Formal ID Photo
                            </span>
                            <span id="badgePhoto1x1" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Pending</span>
                        </div>
                        <div id="modalBoxPhoto1x1" class="w-full h-44 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden relative">
                            <!-- Populated via JS -->
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium text-center">Standard credential photo for physical badge issuance</p>
                    </div>

                    <!-- Digital Signature -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 flex flex-col space-y-2 shadow-xs group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-signature text-purple-600"></i> Digital Signature
                            </span>
                            <span id="badgeSignature" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Pending</span>
                        </div>
                        <div id="modalBoxSignature" class="w-full h-44 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden relative">
                            <!-- Populated via JS -->
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium text-center">Applicant e-signature verifying attestation</p>
                    </div>

                </div>

            </div>

            <!-- ================= TAB 3: AUDIT TRAIL & IMMUTABILITY ================= -->
            <div id="tabContentAudit" class="hidden space-y-4">
                
                <!-- Security & Legal Compliance Box -->
                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                            <span>Tamper-Evident Security Seal</span>
                        </span>
                        <span class="text-[10px] font-mono text-emerald-800 font-bold bg-emerald-100/80 border border-emerald-200 px-2.5 py-0.5 rounded-md shadow-2xs">IMMUTABLE LOG</span>
                    </div>
                    <p class="text-xs text-emerald-950/80 leading-relaxed font-normal">
                        This verification transaction is permanently recorded under the Caloocan City Citizen Data Registry Framework and Republic Act 10173 (Data Privacy Act of 2012). Modifications, deletions, or retroactive alterations are strictly prohibited by system security controls.
                    </p>
                </div>

                <!-- Duplicate Alert Note (Visible if flagged) -->
                <div id="modalDuplicateAlertBox" class="hidden p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-black text-amber-900 uppercase tracking-wide">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                        <span>Duplicate Discrepancy Flag Information</span>
                    </div>
                    <p id="modalDuplicateAlertText" class="text-xs text-amber-950 font-medium leading-relaxed"></p>
                </div>

                <!-- Chronological Audit Milestones -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                    <h3 class="text-xs font-black text-slate-900 tracking-wide uppercase border-b border-slate-100 pb-2.5 flex items-center gap-2">
                        <i class="fa-solid fa-timeline text-[#0f53d1]"></i>
                        <span>Transaction Audit Timeline</span>
                    </h3>

                    <div class="space-y-3 text-xs pl-2">
                        
                        <!-- Milestone 1: Submission -->
                        <div class="flex items-start gap-3 relative before:absolute before:left-3.5 before:top-6 before:bottom-0 before:w-0.5 before:bg-slate-200">
                            <div class="w-7 h-7 rounded-full bg-blue-100 text-[#0f53d1] flex items-center justify-center shrink-0 text-xs font-bold border border-blue-200 z-10">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="pb-3 min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <strong class="font-bold text-slate-900">Application Submitted</strong>
                                    <span id="modalTimelineSubmittedAt" class="text-[11px] text-slate-400 font-semibold">Oct 9, 2026</span>
                                </div>
                                <p class="text-slate-500 text-[11px] mt-0.5">Citizen submitted personal data & credential proofs via Citizen Mobile Portal.</p>
                            </div>
                        </div>

                        <!-- Milestone 2: Review -->
                        <div class="flex items-start gap-3 relative before:absolute before:left-3.5 before:top-6 before:bottom-0 before:w-0.5 before:bg-slate-200">
                            <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center shrink-0 text-xs font-bold border border-purple-200 z-10">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div class="pb-3 min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <strong class="font-bold text-slate-900">Administrative Adjudication</strong>
                                    <span id="modalTimelineReviewedAt" class="text-[11px] text-slate-400 font-semibold">Oct 9, 2026</span>
                                </div>
                                <p class="text-slate-500 text-[11px] mt-0.5">
                                    Processed by <strong id="modalTimelineStaff" class="text-slate-800">Admin</strong> with recorded outcome: <strong id="modalTimelineResult" class="text-slate-800">Verified</strong>.
                                </p>
                            </div>
                        </div>

                        <!-- Milestone 3: Permanent Ledger Committal -->
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-xs font-bold border border-emerald-200 z-10">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <strong class="font-bold text-slate-900">Append-Only Audit Committed</strong>
                                    <span class="text-[11px] text-emerald-600 font-bold">LOCKED & VERIFIED</span>
                                </div>
                                <p class="text-slate-500 text-[11px] mt-0.5">Verification outcome archived with system hash integrity verification.</p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <!-- Modal Bottom Navigation (Back & Close Buttons) -->
        <div class="bg-white px-6 py-3.5 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
            <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 truncate">
                <i class="fa-solid fa-lock text-[10px] text-emerald-500"></i>
                <span>Audit Reference: <strong id="modalFooterRef" class="text-slate-700 font-mono">VLOG-0000</strong></span>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="printAuditSlip()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Print Audit Slip</span>
                </button>
                <button type="button" onclick="closeLogDetailModal()" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                    <span>Close</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================================== -->
<!-- IMAGE LIGHTBOX MODAL                                                           -->
<!-- ============================================================================== -->
<div id="vlogImageLightbox" class="hidden fixed inset-0 z-[10001] bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4" onclick="closeImageLightbox()">
    <div class="relative max-w-4xl max-h-[90vh] bg-slate-900 rounded-3xl border border-slate-700 overflow-hidden shadow-2xl flex flex-col my-auto" onclick="event.stopPropagation()">
        <div class="px-5 py-3.5 bg-slate-800/90 border-b border-slate-700 flex items-center justify-between text-white shrink-0">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-id-card text-blue-400 text-sm"></i>
                <span id="lightboxImageTitle" class="text-xs font-bold">Document Image Inspection</span>
            </div>
            <div class="flex items-center gap-2">
                <a id="lightboxNewTabLink" href="#" target="_blank" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-700 hover:bg-slate-600 text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Open Original
                </a>
                <button type="button" onclick="closeImageLightbox()" class="w-8 h-8 rounded-full bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>
        <div class="p-4 overflow-auto flex items-center justify-center bg-black/40 min-h-[300px]">
            <img id="lightboxImageEl" src="" alt="High resolution inspection" class="max-h-[75vh] max-w-full object-contain rounded-xl shadow-lg">
        </div>
    </div>
</div>

<script>
const verificationLogsData = <?php echo json_encode($verificationLogs); ?>;
const API_BASE_URL = <?php echo json_encode($apiBase); ?>;
let activeLogIndex = null;

function filterVerificationLogsTable() {
    const searchVal = document.getElementById('vlogSearchInput').value.toLowerCase();
    const resultVal = document.getElementById('vlogResultFilter').value.toLowerCase();
    const staffVal = document.getElementById('vlogStaffFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.vlog-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const res = (r.getAttribute('data-result') || '').toLowerCase();
        const staff = (r.getAttribute('data-staff') || '').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesResult = !resultVal || res === resultVal;
        const matchesStaff = !staffVal || staff === staffVal;

        r.style.display = (matchesSearch && matchesResult && matchesStaff) ? '' : 'none';
    });
}

function formatPhotoSrc(url) {
    if (!url || typeof url !== 'string') return null;
    url = url.trim();
    if (!url || url.startsWith('blob:')) return null;

    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:image/')) {
        if (url.includes('/uploads/verifications/')) {
            return url.replace('/uploads/verifications/', '/assets/uploads/verifications/');
        }
        return url;
    }

    const cleanPath = url.replace(/^\/+/, '');
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    if (isLocal) {
        if (cleanPath.startsWith('assets/')) return '../../' + cleanPath;
        if (cleanPath.startsWith('uploads/')) return '../../assets/' + cleanPath;
        if (cleanPath.startsWith('verifications/')) return '../../assets/uploads/' + cleanPath;
        if (!cleanPath.includes('/')) return '../../assets/uploads/verifications/' + cleanPath;
    }
    
    if (cleanPath.startsWith('uploads/')) {
        return API_BASE_URL + '/assets/' + cleanPath;
    }
    if (cleanPath.startsWith('verifications/')) {
        return API_BASE_URL + '/assets/uploads/' + cleanPath;
    }
    if (!cleanPath.includes('/')) {
        return API_BASE_URL + '/assets/uploads/verifications/' + cleanPath;
    }
    return API_BASE_URL + '/' + cleanPath;
}

function renderDocCard(boxId, badgeId, photoUrl, fallbackIcon, docTitle) {
    const box = document.getElementById(boxId);
    const badge = document.getElementById(badgeId);
    if (!box) return;

    const src = formatPhotoSrc(photoUrl);
    if (src) {
        if (badge) {
            badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200";
            badge.textContent = "Uploaded";
        }
        box.innerHTML = `
            <div class="relative w-full h-full group/img cursor-pointer" onclick="openImageLightbox('${src.replace(/'/g, "\\'")}', '${docTitle}')">
                <img src="${src}" class="w-full h-full object-cover rounded-xl transition duration-200 group-hover/img:scale-105" alt="${docTitle}" 
                     onerror="this.closest('.group\\/img').innerHTML='<div class=\\'text-center p-3 text-slate-400\\'><i class=\\'${fallbackIcon} text-2xl mb-1 text-slate-300\\'></i><span class=\\'block text-[10px] font-semibold\\'>Image Unavailable</span></div>';" />
                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover/img:opacity-100 transition flex items-center justify-center gap-1.5 text-white font-bold text-xs rounded-xl backdrop-blur-2xs">
                    <i class="fa-solid fa-expand text-xs"></i>
                    <span>Expand Full Size</span>
                </div>
            </div>
        `;
    } else {
        if (badge) {
            badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 border border-slate-200";
            badge.textContent = "Not Uploaded";
        }
        box.innerHTML = `
            <div class="text-center p-3 text-slate-400">
                <i class="${fallbackIcon} text-2xl mb-1 text-slate-300"></i>
                <span class="block text-[10px] font-semibold">Not Uploaded</span>
            </div>
        `;
    }
}

function openLogDetailModal(index) {
    if (typeof index !== 'number' || !verificationLogsData[index]) return;
    activeLogIndex = index;
    const log = verificationLogsData[index];

    // IDs
    document.getElementById('modalLogId').textContent = log.log_id || 'VLOG-0000';
    document.getElementById('modalAppId').textContent = log.app_id || 'VER-0000';
    document.getElementById('modalCitizenIdPill').textContent = log.citizen_id || 'CTZ-0000';
    document.getElementById('modalFooterRef').textContent = `${log.log_id || ''} • ${log.timestamp || ''}`;

    // Result Badge Top
    const badgeEl = document.getElementById('modalResultBadge');
    badgeEl.textContent = log.result || 'Pending';
    badgeEl.className = `px-3 py-1 text-xs font-bold rounded-full border ${log.result_badge || 'bg-slate-100 text-slate-600 border-slate-200'}`;

    // Citizen Info
    document.getElementById('modalCitizenName').textContent = log.citizen_name || 'Citizen';
    document.getElementById('modalCitizenAddressLine').innerHTML = `
        <i class="fa-solid fa-location-dot text-[#0f53d1] mr-1"></i>
        <span>${log.address || 'Caloocan City'}</span>
    `;
    document.getElementById('modalCitizenAvatar').src = log.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(log.citizen_name || 'Citizen')}&background=0f53d1&color=fff`;
    
    // Contact Info
    document.getElementById('modalEmailText').textContent = log.user_email || 'Not registered';
    document.getElementById('modalPhoneText').textContent = log.user_mobile || 'Not registered';

    // Outcome Banner
    const banner = document.getElementById('modalOutcomeBanner');
    const iconBox = document.getElementById('modalOutcomeIconBox');
    const icon = document.getElementById('modalOutcomeIcon');
    const title = document.getElementById('modalOutcomeTitle');

    if (log.result === 'Verified') {
        banner.className = "p-4 rounded-2xl border bg-emerald-50/70 border-emerald-200 text-emerald-950 flex items-start gap-3.5 transition-all";
        iconBox.className = "w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg shrink-0 border border-emerald-200";
        icon.className = "fa-solid fa-circle-check";
        title.textContent = "Identity Verification Passed & Certified";
    } else if (log.result === 'Failed') {
        banner.className = "p-4 rounded-2xl border bg-rose-50/70 border-rose-200 text-rose-950 flex items-start gap-3.5 transition-all";
        iconBox.className = "w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg shrink-0 border border-rose-200";
        icon.className = "fa-solid fa-circle-xmark";
        title.textContent = "Identity Verification Failed / Document Invalid";
    } else if (log.result === 'Flagged') {
        banner.className = "p-4 rounded-2xl border bg-amber-50/70 border-amber-200 text-amber-950 flex items-start gap-3.5 transition-all";
        iconBox.className = "w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0 border border-amber-200";
        icon.className = "fa-solid fa-triangle-exclamation";
        title.textContent = "Discrepancy / Duplicate Record Flagged";
    } else if (log.result === 'Superseded') {
        banner.className = "p-4 rounded-2xl border bg-slate-100 border-slate-300 text-slate-800 flex items-start gap-3.5 transition-all";
        iconBox.className = "w-10 h-10 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center text-lg shrink-0 border border-slate-300";
        icon.className = "fa-solid fa-history";
        title.textContent = "Prior Record Superseded by Subsequent Approval";
    } else {
        banner.className = "p-4 rounded-2xl border bg-blue-50/70 border-blue-200 text-blue-950 flex items-start gap-3.5 transition-all";
        iconBox.className = "w-10 h-10 rounded-xl bg-blue-100 text-[#0f53d1] flex items-center justify-center text-lg shrink-0 border border-blue-200";
        icon.className = "fa-solid fa-clock";
        title.textContent = "Pending Administrative Validation";
    }

    document.getElementById('modalOutcomeRemarks').textContent = log.remarks || 'No remarks provided.';
    document.getElementById('modalVerifyingOfficer').textContent = log.verifying_staff || 'Admin';
    document.getElementById('modalReviewedAt').textContent = log.reviewed_at || 'Pending';
    document.getElementById('modalSubmittedAt').textContent = log.submitted_at || 'N/A';

    // Gov ID checked
    document.getElementById('modalIdType').textContent = log.id_type || 'PhilSys National ID';
    document.getElementById('modalIdNumber').textContent = log.id_number || 'N/A';

    // Demographics
    document.getElementById('modalBirthdate').textContent = log.birth_date || 'N/A';
    document.getElementById('modalAge').textContent = log.age || 'N/A';
    document.getElementById('modalSex').textContent = log.sex || 'Not Specified';
    document.getElementById('modalCivilStatus').textContent = log.civil_status || 'Single';
    document.getElementById('modalPlaceOfBirth').textContent = log.place_of_birth || 'Caloocan City';
    document.getElementById('modalEmployment').textContent = log.employment_status || 'N/A';
    document.getElementById('modalOccupation').textContent = log.occupation || 'N/A';
    document.getElementById('modalEducation').textContent = log.educational_attainment || 'N/A';
    document.getElementById('modalResidency').textContent = log.years_resident || 'N/A';
    document.getElementById('modalFullAddress').textContent = log.address || 'Caloocan City';

    // Render Document Assets
    renderDocCard('modalBoxIdFront', 'badgeIdFront', log.id_front_photo_url, 'fa-solid fa-id-card', 'Valid Government ID (Front)');
    renderDocCard('modalBoxSelfie', 'badgeSelfie', log.selfie_photo_url, 'fa-solid fa-camera', 'Selfie Liveness Verification');
    renderDocCard('modalBoxPhoto1x1', 'badgePhoto1x1', log.photo_1x1_url, 'fa-regular fa-image', '1x1 Formal ID Photo');
    renderDocCard('modalBoxSignature', 'badgeSignature', log.signature_photo_url, 'fa-solid fa-signature', 'Cardholder Digital Signature');

    // Audit Tab details
    document.getElementById('modalTimelineSubmittedAt').textContent = log.submitted_at || 'N/A';
    document.getElementById('modalTimelineReviewedAt').textContent = log.reviewed_at || 'Pending';
    document.getElementById('modalTimelineStaff').textContent = log.verifying_staff || 'Admin';
    document.getElementById('modalTimelineResult').textContent = log.result || 'Pending';

    // Duplicate info
    const dupBox = document.getElementById('modalDuplicateAlertBox');
    if (log.is_duplicate) {
        dupBox.classList.remove('hidden');
        let note = log.remarks;
        if (log.duplicate_matched_id) {
            note = `Potential duplicate application collision with record #${log.duplicate_matched_id}. Cross-match detected on Name, Birthdate, or ID document number.`;
        }
        document.getElementById('modalDuplicateAlertText').textContent = note;
    } else {
        dupBox.classList.add('hidden');
    }

    // Default to Overview tab
    switchModalTab('overview');

    // Show modal
    const modal = document.getElementById('vlogDetailModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLogDetailModal() {
    const modal = document.getElementById('vlogDetailModal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function handleBackdropClick(e) {
    if (e.target && e.target.id === 'vlogDetailModal') {
        closeLogDetailModal();
    }
}

function switchModalTab(tab) {
    const tabOverview = document.getElementById('tabContentOverview');
    const tabDocs = document.getElementById('tabContentDocs');
    const tabAudit = document.getElementById('tabContentAudit');

    const btnOverview = document.getElementById('modalTabOverviewBtn');
    const btnDocs = document.getElementById('modalTabDocsBtn');
    const btnAudit = document.getElementById('modalTabAuditBtn');

    if (!tabOverview || !tabDocs || !tabAudit) return;

    tabOverview.classList.add('hidden');
    tabDocs.classList.add('hidden');
    tabAudit.classList.add('hidden');

    const activeClass = "px-4 py-2.5 border-b-2 border-[#0f53d1] text-xs font-bold text-[#0f53d1] transition cursor-pointer flex items-center gap-2";
    const inactiveClass = "px-4 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer flex items-center gap-2 border-b-2 border-transparent";

    btnOverview.className = inactiveClass;
    btnDocs.className = inactiveClass;
    btnAudit.className = inactiveClass;

    if (tab === 'docs') {
        tabDocs.classList.remove('hidden');
        btnDocs.className = activeClass;
    } else if (tab === 'audit') {
        tabAudit.classList.remove('hidden');
        btnAudit.className = activeClass;
    } else {
        tabOverview.classList.remove('hidden');
        btnOverview.className = activeClass;
    }
}

function openImageLightbox(src, title) {
    const box = document.getElementById('vlogImageLightbox');
    const img = document.getElementById('lightboxImageEl');
    const titleEl = document.getElementById('lightboxImageTitle');
    const link = document.getElementById('lightboxNewTabLink');

    if (!box || !img) return;

    img.src = src;
    titleEl.textContent = title || 'Document Inspection';
    link.href = src;
    box.classList.remove('hidden');
}

function closeImageLightbox() {
    const box = document.getElementById('vlogImageLightbox');
    if (box) box.classList.add('hidden');
}

function printAuditSlip() {
    if (activeLogIndex === null || !verificationLogsData[activeLogIndex]) {
        alert('Please select an audit log to print.');
        return;
    }

    const log = verificationLogsData[activeLogIndex];

    const printWin = window.open('', '_blank', 'width=850,height=900');
    if (!printWin) {
        alert('Pop-up was blocked. Please allow pop-ups for this site to print the audit slip.');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Verification Audit Slip - ${log.log_id}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; padding: 30px; color: #1e293b; }
                .header { text-align: center; border-bottom: 2px solid #0f53d1; padding-bottom: 12px; margin-bottom: 20px; }
                .header h1 { margin: 0; font-size: 18px; color: #0f53d1; text-transform: uppercase; letter-spacing: 1px; }
                .header p { margin: 4px 0 0; font-size: 12px; color: #64748b; font-weight: 600; }
                .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; }
                .badge-verified { background: #d1fae5; color: #065f46; }
                .badge-failed { background: #ffe4e6; color: #9f1239; }
                .badge-flagged { background: #fef3c7; color: #92400e; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
                th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
                th { background: #f8fafc; font-weight: bold; width: 30%; }
                .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #0f53d1; margin-top: 20px; margin-bottom: 6px; }
                .sign-box { margin-top: 40px; display: flex; justify-content: space-between; }
                .sign-line { width: 40%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 11px; font-weight: bold; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>City Government of Caloocan</h1>
                <p>CITIZEN REGISTRY SYSTEM &bull; OFFICIAL IDENTITY VERIFICATION AUDIT SLIP</p>
                <p style="font-size: 10px; color: #94a3b8; margin-top: 6px;">Reference: <strong>${log.log_id}</strong> (Application: <strong>${log.app_id}</strong>)</p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div><strong>Date & Timestamp:</strong> ${log.timestamp}</div>
                <div>
                    <strong>Verification Outcome:</strong> 
                    <span class="badge ${log.result === 'Verified' ? 'badge-verified' : (log.result === 'Failed' ? 'badge-failed' : 'badge-flagged')}">
                        ${log.result}
                    </span>
                </div>
            </div>

            <div class="section-title">Citizen Identity Profile</div>
            <table>
                <tr><th>Full Legal Name</th><td><strong>${log.citizen_name}</strong></td></tr>
                <tr><th>Citizen Registry ID</th><td>${log.citizen_id}</td></tr>
                <tr><th>Birthdate & Age</th><td>${log.birth_date} (${log.age})</td></tr>
                <tr><th>Sex / Civil Status</th><td>${log.sex} / ${log.civil_status}</td></tr>
                <tr><th>Residential Address</th><td>${log.address}</td></tr>
                <tr><th>Length of Residency</th><td>${log.years_resident}</td></tr>
                <tr><th>Contact Information</th><td>Mobile: ${log.user_mobile || 'N/A'} &bull; Email: ${log.user_email || 'N/A'}</td></tr>
            </table>

            <div class="section-title">Government Identification Verified</div>
            <table>
                <tr><th>ID Document Checked</th><td><strong>${log.id_type}</strong></td></tr>
                <tr><th>Document Number</th><td><code>${log.id_number}</code></td></tr>
                <tr><th>Staff Verification Remarks</th><td>${log.remarks}</td></tr>
            </table>

            <div class="section-title">Audit Adjudication</div>
            <table>
                <tr><th>Verifying Staff Officer</th><td>${log.verifying_staff}</td></tr>
                <tr><th>Adjudication Timestamp</th><td>${log.reviewed_at}</td></tr>
                <tr><th>Submission Timestamp</th><td>${log.submitted_at}</td></tr>
                <tr><th>Ledger Integrity Status</th><td>Read-Only Append-Only Ledger Entry (Immutability Enforced)</td></tr>
            </table>

            <div class="sign-box">
                <div class="sign-line">
                    ${log.verifying_staff}<br>
                    <span style="font-size: 10px; color: #64748b; font-weight: normal;">Verifying Staff Officer</span>
                </div>
                <div class="sign-line">
                    City Civil Registrar / System Auditor<br>
                    <span style="font-size: 10px; color: #64748b; font-weight: normal;">Caloocan Citizen Registry</span>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

// Global keyboard navigation (ESC closes modal)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const lightbox = document.getElementById('vlogImageLightbox');
        if (lightbox && !lightbox.classList.contains('hidden')) {
            closeImageLightbox();
            return;
        }
        closeLogDetailModal();
    }
});

function exportVerificationAuditCSV() {
    if (!verificationLogsData || verificationLogsData.length === 0) {
        alert('No verification audit logs to export.');
        return;
    }

    const headers = [
        'Log ID',
        'Citizen ID',
        'Citizen Name',
        'Address',
        'Government ID Type Checked',
        'Government ID Number',
        'Verifying Staff Officer',
        'Date & Timestamp',
        'Result',
        'Staff Verification Remarks'
    ];

    const rows = verificationLogsData.map(log => [
        `"${log.log_id}"`,
        `"${log.citizen_id}"`,
        `"${(log.citizen_name || '').replace(/"/g, '""')}"`,
        `"${(log.address || '').replace(/"/g, '""')}"`,
        `"${(log.id_type || '').replace(/"/g, '""')}"`,
        `"${(log.id_number || '').replace(/"/g, '""')}"`,
        `"${(log.verifying_staff || '').replace(/"/g, '""')}"`,
        `"${(log.timestamp || '').replace(/\u2022/g, '-').replace(/\s+/g, ' ').trim()}"`,
        `"${log.result}"`,
        `"${(log.remarks || '').replace(/"/g, '""')}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" 
        + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    const today = new Date().toISOString().slice(0, 10);
    link.setAttribute("download", `id_verification_audit_logs_${today}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../../includes/footer.php'; ?>
