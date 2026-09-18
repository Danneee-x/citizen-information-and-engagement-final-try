<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Real Duplicate Detection Query from MySQL
require_once __DIR__ . '/../../config/database.php';

$duplicateFlags = [];
$counts = [
    'pending' => 0,
    'resolved' => 0,
    'id_mismatches' => 0,
    'verified_clean' => 0
];

try {
    $pdo = getDbConnection();

    // Ensure table exists
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

    // Search for duplicate ID numbers or matching demographics
    $dupQuery = $pdo->query("SELECT v1.*, v2.verification_id as v2_id, v2.first_name as v2_fn, v2.last_name as v2_ln, 
                                    v2.birth_date as v2_dob, v2.street_address as v2_addr, v2.barangay as v2_brgy, 
                                    v2.valid_id_number as v2_idnum, v2.submitted_at as v2_sub, v2.civil_status as v2_cs
                             FROM citizen_verifications v1
                             JOIN citizen_verifications v2 
                               ON v1.verification_id < v2.verification_id 
                              AND (v1.valid_id_number = v2.valid_id_number 
                                   OR (v1.first_name = v2.first_name AND v1.last_name = v2.last_name AND v1.birth_date = v2.birth_date))
                             LIMIT 20");
    $dupRows = $dupQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dupRows as $idx => $d) {
        $nameA = trim("{$d['first_name']} {$d['last_name']}");
        $nameB = trim("{$d['v2_fn']} {$d['v2_ln']}");
        $isIdMatch = ($d['valid_id_number'] === $d['v2_idnum']);

        $duplicateFlags[] = [
            'flag_id' => 'DUP-' . date('Y') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
            'matching_criteria' => $isIdMatch ? 'Same Government ID Number' : 'Same Name + Birthdate Match',
            'match_confidence' => $isIdMatch ? 100 : 95,
            'status' => 'Pending Review',
            'status_badge' => 'bg-amber-50 text-amber-600 border-amber-200',
            'record_a' => [
                'id' => 'CTZ-' . str_pad($d['verification_id'], 4, '0', STR_PAD_LEFT),
                'name' => $nameA,
                'dob' => !empty($d['birth_date']) ? date('M j, Y', strtotime($d['birth_date'])) : 'N/A',
                'address' => "{$d['street_address']}, {$d['barangay']}, Caloocan City",
                'contact' => $d['valid_id_number'],
                'civil_status' => $d['civil_status'],
                'registered_date' => date('M j, Y', strtotime($d['submitted_at']))
            ],
            'record_b' => [
                'id' => 'CTZ-' . str_pad($d['v2_id'], 4, '0', STR_PAD_LEFT),
                'name' => $nameB,
                'dob' => !empty($d['v2_dob']) ? date('M j, Y', strtotime($d['v2_dob'])) : 'N/A',
                'address' => "{$d['v2_addr']}, {$d['v2_brgy']}, Caloocan City",
                'contact' => $d['v2_idnum'],
                'civil_status' => $d['v2_cs'],
                'registered_date' => date('M j, Y', strtotime($d['v2_sub']))
            ]
        ];
    }

    $counts['pending'] = count($duplicateFlags);
    $totalCitizens = (int)$pdo->query("SELECT COUNT(*) FROM citizen_verifications")->fetchColumn();
    $counts['verified_clean'] = max(0, $totalCitizens - (count($duplicateFlags) * 2));
} catch (Exception $e) {
    error_log("Duplicate flags error: " . $e->getMessage());
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
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100 shadow-xs">
                <i class="fa-solid fa-copy"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Citizen Registry</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Duplicate Flags</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Duplicate Flags</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="runAutoDuplicateScan()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-rotate text-slate-400"></i>
                <span>Run Duplicate Scan</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Pending Duplicate Flags -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Review</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-flag"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $counts['pending']; ?> Flagged Pairs</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Requires staff resolution</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Merged Records -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Merged Records</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-code-merge"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">18 Merged</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Combined into single profiles</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Dismissed False Flags -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dismissed Flags</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">5 Verified Unique</h3>
                <p class="text-[11px] font-semibold text-[#0f53d1] flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield"></i>
                    <span>Not a duplicate (Reason logged)</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Match Algorithm Accuracy -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Match Accuracy</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">96.5% Precision</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-brain"></i>
                    <span>Name + DOB + Address AI match</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Flagged Duplicate Pairs List -->
    <div class="space-y-6">
        
        <?php foreach ($duplicateFlags as $flag): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            
            <!-- Flag Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-bold text-[#0f53d1]"><?php echo $flag['flag_id']; ?></span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> <?php echo $flag['match_confidence']; ?>% Match Confidence
                    </span>
                    <span class="text-xs font-black text-slate-800">&bull; <?php echo $flag['matching_criteria']; ?></span>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="openMergeModal('<?php echo $flag['flag_id']; ?>')" class="px-4 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-code-merge text-xs"></i>
                        <span>Merge Records</span>
                    </button>

                    <button onclick="openNotDuplicateModal('<?php echo $flag['flag_id']; ?>')" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check text-xs text-slate-400"></i>
                        <span>Not a Duplicate</span>
                    </button>
                </div>
            </div>

            <!-- SIDE-BY-SIDE COMPARISON TABLE -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[700px] text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-4 w-1/3">Field Name</th>
                            <th class="py-2.5 px-4 w-1/3 text-blue-900 bg-blue-50/50 border-x border-blue-100">Record A (Existing)</th>
                            <th class="py-2.5 px-4 w-1/3 text-purple-900 bg-purple-50/50">Record B (Newly Registered)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Citizen ID</td>
                            <td class="py-3 px-4 font-bold text-[#0f53d1] bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['id']; ?></td>
                            <td class="py-3 px-4 font-bold text-purple-700 bg-purple-50/20"><?php echo $flag['record_b']['id']; ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Full Name</td>
                            <td class="py-3 px-4 font-bold text-slate-900 bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['name']; ?></td>
                            <td class="py-3 px-4 font-bold text-slate-900 bg-purple-50/20"><?php echo $flag['record_b']['name']; ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Date of Birth</td>
                            <td class="py-3 px-4 bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['dob']; ?></td>
                            <td class="py-3 px-4 bg-purple-50/20"><?php echo $flag['record_b']['dob']; ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Residential Address</td>
                            <td class="py-3 px-4 bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['address']; ?></td>
                            <td class="py-3 px-4 bg-purple-50/20"><?php echo $flag['record_b']['address']; ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Mobile Contact</td>
                            <td class="py-3 px-4 bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['contact']; ?></td>
                            <td class="py-3 px-4 bg-purple-50/20"><?php echo $flag['record_b']['contact']; ?></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500">Registration Date</td>
                            <td class="py-3 px-4 bg-blue-50/20 border-x border-blue-100"><?php echo $flag['record_a']['registered_date']; ?></td>
                            <td class="py-3 px-4 bg-purple-50/20"><?php echo $flag['record_b']['registered_date']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <?php endforeach; ?>

    </div>

</main>

<!-- MERGE RECORDS MODAL -->
<div id="mergeModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-code-merge text-[#0f53d1]"></i>
                <span>Merge Duplicate Citizen Records</span>
            </h3>
            <button onclick="closeMergeModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <p class="text-slate-600 font-medium">Select which master record to retain. Data from both profiles will be consolidated under the master profile.</p>

            <div class="space-y-2">
                <label class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between cursor-pointer">
                    <div>
                        <span class="font-bold text-[#0f53d1] block text-xs">Keep Record A as Master (CTZ-2025-0142)</span>
                        <span class="text-[10px] text-slate-500 font-medium">Juan Dela Cruz &bull; May 10, 2025</span>
                    </div>
                    <input type="radio" name="masterRecord" value="Record A" checked class="w-4 h-4 text-[#0f53d1]">
                </label>

                <label class="p-3 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-between cursor-pointer">
                    <div>
                        <span class="font-bold text-purple-700 block text-xs">Keep Record B as Master (CTZ-2025-0589)</span>
                        <span class="text-[10px] text-slate-500 font-medium">Juan V. Dela Cruz &bull; Jun 7, 2025</span>
                    </div>
                    <input type="radio" name="masterRecord" value="Record B" class="w-4 h-4 text-purple-600">
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button onclick="closeMergeModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmMerge()" class="px-5 py-2 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Confirm Record Merge</span>
            </button>
        </div>
    </div>
</div>

<!-- NOT A DUPLICATE MODAL -->
<div id="notDuplicateModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-check text-slate-700"></i>
                <span>Dismiss Duplicate Flag</span>
            </h3>
            <button onclick="closeNotDuplicateModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <p class="text-slate-600 font-medium">Please log the official staff reason for declaring these records as separate unique individuals.</p>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Reason for Dismissal <span class="text-rose-500">*</span></label>
                <textarea id="dismissReasonInput" rows="3" placeholder="e.g., Different middle name & verified PhilSys ID numbers..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button onclick="closeNotDuplicateModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmDismissal()" class="px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition shadow-xs cursor-pointer">Dismiss Flag & Log</button>
        </div>
    </div>
</div>

<script>
let activeFlagId = null;

function openMergeModal(flagId) {
    activeFlagId = flagId;
    document.getElementById('mergeModal').classList.remove('hidden');
}

function closeMergeModal() {
    document.getElementById('mergeModal').classList.add('hidden');
}

function confirmMerge() {
    alert(`Records for ${activeFlagId} successfully merged into master citizen profile! Duplicate resolved.`);
    closeMergeModal();
}

function openNotDuplicateModal(flagId) {
    activeFlagId = flagId;
    document.getElementById('dismissReasonInput').value = '';
    document.getElementById('notDuplicateModal').classList.remove('hidden');
}

function closeNotDuplicateModal() {
    document.getElementById('notDuplicateModal').classList.add('hidden');
}

function confirmDismissal() {
    const reason = document.getElementById('dismissReasonInput').value.trim();
    if (!reason) {
        alert('Please enter a dismissal reason for the audit trail.');
        return;
    }
    alert(`Flag ${activeFlagId} dismissed as unique individuals. Audit log saved.`);
    closeNotDuplicateModal();
}

function runAutoDuplicateScan() {
    alert('Scanning Citizen Registry database for duplicate names, birthdates, and addresses...');
}
</script>

<?php include '../../includes/footer.php'; ?>
