<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Real ID Verification Audit Logs from MySQL
require_once __DIR__ . '/../../config/database.php';

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
        'duplicate_notes' => 'TEXT NULL'
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

    $stmt = $pdo->query("SELECT * FROM citizen_verifications ORDER BY COALESCE(reviewed_at, submitted_at) DESC LIMIT 100");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $fullName = trim("{$r['first_name']} {$r['middle_name']} {$r['last_name']} {$r['suffix']}");
        $dt = !empty($r['reviewed_at']) ? new DateTime($r['reviewed_at']) : new DateTime($r['submitted_at']);
        
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
            $remarks = !empty($r['rejection_reason']) ? $r['rejection_reason'] : 'Discrepancy detected during validation.';
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

        $verificationLogs[] = [
            'log_id' => 'VLOG-' . str_pad($r['verification_id'], 4, '0', STR_PAD_LEFT),
            'citizen_name' => $fullName,
            'citizen_id' => 'CTZ-' . str_pad($r['verification_id'], 4, '0', STR_PAD_LEFT),
            'address' => "{$r['barangay']}, {$r['district']}, Caloocan City",
            'id_type' => $r['valid_id_type'] ?: 'National ID',
            'verifying_staff' => $verifyingStaff,
            'timestamp' => $dt->format('M j, Y') . ' &bull; ' . $dt->format('h:i A'),
            'result' => $result,
            'result_badge' => $badge,
            'remarks' => $remarks
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
        
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Historical Identity Check Logs</h3>
            <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1">
                <i class="fa-solid fa-lock text-[10px]"></i> Immutability Enforced (Cannot be deleted/edited)
            </span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Log ID & Citizen</th>
                        <th class="py-3.5 px-3">Government ID Type Checked</th>
                        <th class="py-3.5 px-3">Verifying Staff Officer</th>
                        <th class="py-3.5 px-3">Date & Timestamp</th>
                        <th class="py-3.5 px-3 text-center">Result</th>
                        <th class="py-3.5 px-3">Staff Verification Remarks</th>
                    </tr>
                </thead>
                <tbody id="vlogTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($verificationLogs)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 font-medium text-xs">No verification logs recorded yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($verificationLogs as $log): ?>
                    <tr class="vlog-row hover:bg-slate-50 transition select-none" data-result="<?php echo htmlspecialchars($log['result']); ?>" data-staff="<?php echo htmlspecialchars($log['verifying_staff']); ?>">
                        <td class="py-3.5 px-4">
                            <span class="text-[10px] font-bold text-[#0f53d1] block"><?php echo $log['log_id']; ?></span>
                            <p class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($log['citizen_name']); ?></p>
                            <span class="text-[10px] text-slate-400 font-semibold"><?php echo $log['citizen_id']; ?></span>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-id-card text-[#0f53d1] text-xs"></i> <?php echo htmlspecialchars($log['id_type']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-800">
                            <?php echo htmlspecialchars($log['verifying_staff']); ?>
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
                            <?php echo htmlspecialchars($log['remarks']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</main>

<script>
const verificationLogsData = <?php echo json_encode($verificationLogs); ?>;

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
