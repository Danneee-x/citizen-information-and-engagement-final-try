<?php
require_once __DIR__ . '/../../src/bootstrap.php';
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';

require_once __DIR__ . '/../../config/database.php';

// ── Bootstrap DB: create table if missing ──────────────────────────────────
$counts       = ['pending' => 0, 'merged' => 0, 'dismissed' => 0];
$duplicateFlags = [];
$totalCitizens  = 0;
$dbError        = null;

try {
    $pdo = getDbConnection();

    // Auto-create duplicate_flags table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `duplicate_flags` (
        `flag_id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `flag_code`         VARCHAR(30) NOT NULL UNIQUE,
        `verification_id_a` INT UNSIGNED NOT NULL,
        `verification_id_b` INT UNSIGNED NOT NULL,
        `matching_criteria` VARCHAR(150) NOT NULL,
        `match_confidence`  TINYINT UNSIGNED NOT NULL DEFAULT 95,
        `status`            ENUM('Pending','Merged','Dismissed') NOT NULL DEFAULT 'Pending',
        `resolution_notes`  TEXT NULL DEFAULT NULL,
        `master_record_id`  INT UNSIGNED NULL DEFAULT NULL,
        `resolved_by`       VARCHAR(150) NULL DEFAULT NULL,
        `resolved_at`       DATETIME NULL DEFAULT NULL,
        `detected_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_df_status`  (`status`),
        INDEX `idx_df_vid_a`   (`verification_id_a`),
        INDEX `idx_df_vid_b`   (`verification_id_b`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auto-run a quick scan if no flags exist yet
    $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags")->fetchColumn();
    if ($existingCount === 0) {
        $dupQuery = $pdo->query("
            SELECT v1.verification_id as vid_a, v2.verification_id as vid_b,
                   v1.valid_id_number as id_a, v2.valid_id_number as id_b
            FROM citizen_verifications v1
            JOIN citizen_verifications v2
              ON v1.verification_id < v2.verification_id
             AND (v1.valid_id_number = v2.valid_id_number
                  OR (v1.first_name = v2.first_name AND v1.last_name = v2.last_name AND v1.birth_date = v2.birth_date))
            LIMIT 50
        ");
        $dupRows = $dupQuery->fetchAll(PDO::FETCH_ASSOC);
        $seq = 1;
        foreach ($dupRows as $row) {
            $isId    = ($row['id_a'] === $row['id_b']);
            $code    = 'DUP-' . date('Y') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $insert  = $pdo->prepare("INSERT IGNORE INTO duplicate_flags
                (flag_code, verification_id_a, verification_id_b, matching_criteria, match_confidence)
                VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$code, $row['vid_a'], $row['vid_b'],
                              $isId ? 'Same Government ID Number' : 'Same Name + Birthdate Match',
                              $isId ? 100 : 95]);
            $seq++;
        }
    }

    // KPI counts
    $counts['pending']   = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Pending'")->fetchColumn();
    $counts['merged']    = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Merged'")->fetchColumn();
    $counts['dismissed'] = (int)$pdo->query("SELECT COUNT(*) FROM duplicate_flags WHERE status='Dismissed'")->fetchColumn();
    $totalCitizens       = (int)$pdo->query("SELECT COUNT(*) FROM citizen_verifications")->fetchColumn();

    // Load ALL pending flags with joined verification data
    $stmt = $pdo->query("
        SELECT f.*,
               v1.first_name as fn_a, v1.last_name as ln_a, v1.birth_date as dob_a,
               v1.street_address as addr_a, v1.barangay as brgy_a,
               v1.valid_id_number as idnum_a, v1.civil_status as cs_a, v1.submitted_at as sub_a,
               v2.first_name as fn_b, v2.last_name as ln_b, v2.birth_date as dob_b,
               v2.street_address as addr_b, v2.barangay as brgy_b,
               v2.valid_id_number as idnum_b, v2.civil_status as cs_b, v2.submitted_at as sub_b
        FROM duplicate_flags f
        LEFT JOIN citizen_verifications v1 ON f.verification_id_a = v1.verification_id
        LEFT JOIN citizen_verifications v2 ON f.verification_id_b = v2.verification_id
        WHERE f.status = 'Pending'
        ORDER BY f.detected_at DESC
        LIMIT 50
    ");
    $dbFlags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbFlags as $d) {
        $nameA = trim("{$d['fn_a']} {$d['ln_a']}");
        $nameB = trim("{$d['fn_b']} {$d['ln_b']}");
        $duplicateFlags[] = [
            'flag_code'         => $d['flag_code'],
            'matching_criteria' => $d['matching_criteria'],
            'match_confidence'  => $d['match_confidence'],
            'vid_a'             => $d['verification_id_a'],
            'vid_b'             => $d['verification_id_b'],
            'record_a' => [
                'id'              => 'CTZ-' . str_pad($d['verification_id_a'], 4, '0', STR_PAD_LEFT),
                'name'            => $nameA ?: 'N/A',
                'dob'             => !empty($d['dob_a'])  ? date('M j, Y', strtotime($d['dob_a']))  : 'N/A',
                'address'         => trim("{$d['addr_a']}, {$d['brgy_a']}, Caloocan City", ', ') ?: 'N/A',
                'id_number'       => $d['idnum_a'] ?? 'N/A',
                'civil_status'    => $d['cs_a'] ?? 'N/A',
                'registered_date' => !empty($d['sub_a'])  ? date('M j, Y', strtotime($d['sub_a']))  : 'N/A',
            ],
            'record_b' => [
                'id'              => 'CTZ-' . str_pad($d['verification_id_b'], 4, '0', STR_PAD_LEFT),
                'name'            => $nameB ?: 'N/A',
                'dob'             => !empty($d['dob_b'])  ? date('M j, Y', strtotime($d['dob_b']))  : 'N/A',
                'address'         => trim("{$d['addr_b']}, {$d['brgy_b']}, Caloocan City", ', ') ?: 'N/A',
                'id_number'       => $d['idnum_b'] ?? 'N/A',
                'civil_status'    => $d['cs_b'] ?? 'N/A',
                'registered_date' => !empty($d['sub_b'])  ? date('M j, Y', strtotime($d['sub_b']))  : 'N/A',
            ],
        ];
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
    error_log("Duplicate flags DB error: " . $e->getMessage());
}
?>
<style>
    .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header -->
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
            <button id="scanBtn" onclick="runAutoDuplicateScan()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-rotate text-slate-400"></i>
                <span>Run Duplicate Scan</span>
            </button>
        </div>
    </div>

    <?php if ($dbError): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-xs font-semibold text-red-700 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Database error: <?php echo htmlspecialchars($dbError); ?>
    </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Pending Flags -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Review</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-flag"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight" id="kpiPending"><?php echo $counts['pending']; ?> Flagged Pairs</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Requires staff resolution</span>
                </p>
            </div>
        </div>

        <!-- Merged Records (live from DB) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Merged Records</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-code-merge"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight" id="kpiMerged"><?php echo $counts['merged']; ?> Merged</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Combined into single profiles</span>
                </p>
            </div>
        </div>

        <!-- Dismissed Flags (live from DB) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dismissed Flags</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight" id="kpiDismissed"><?php echo $counts['dismissed']; ?> Verified Unique</h3>
                <p class="text-[11px] font-semibold text-[#0f53d1] flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield"></i>
                    <span>Not a duplicate (Reason logged)</span>
                </p>
            </div>
        </div>

        <!-- Match Accuracy -->
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
                    <span>Name + DOB + ID Number match</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Flagged Pairs List -->
    <div class="space-y-6" id="flagsList">

        <?php if (empty($duplicateFlags)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center space-y-3">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-2xl border border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3 class="text-base font-black text-slate-800">No Pending Duplicate Flags</h3>
            <p class="text-xs text-slate-500 font-medium max-w-xs mx-auto">The citizen registry is clean. Click <strong>Run Duplicate Scan</strong> to re-check for new potential duplicates.</p>
        </div>

        <?php else: ?>
        <?php foreach ($duplicateFlags as $flag): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 flag-card" data-flag="<?php echo htmlspecialchars($flag['flag_code']); ?>">

            <!-- Flag Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-xs font-bold text-[#0f53d1]"><?php echo htmlspecialchars($flag['flag_code']); ?></span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                        <?php echo $flag['match_confidence']; ?>% Match Confidence
                    </span>
                    <span class="text-xs font-black text-slate-800">&bull; <?php echo htmlspecialchars($flag['matching_criteria']); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openMergeModal('<?php echo htmlspecialchars($flag['flag_code']); ?>', <?php echo $flag['vid_a']; ?>, <?php echo $flag['vid_b']; ?>, '<?php echo htmlspecialchars($flag['record_a']['name']); ?>', '<?php echo htmlspecialchars($flag['record_b']['name']); ?>')"
                        class="px-4 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-code-merge text-xs"></i>
                        <span>Merge Records</span>
                    </button>
                    <button onclick="openNotDuplicateModal('<?php echo htmlspecialchars($flag['flag_code']); ?>')"
                        class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check text-xs text-slate-400"></i>
                        <span>Not a Duplicate</span>
                    </button>
                </div>
            </div>

            <!-- Side-by-Side Comparison Table -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[700px] text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-4 w-1/3">Field Name</th>
                            <th class="py-2.5 px-4 w-1/3 text-blue-900 bg-blue-50/50 border-x border-blue-100">
                                Record A — <?php echo htmlspecialchars($flag['record_a']['id']); ?> (Existing)
                            </th>
                            <th class="py-2.5 px-4 w-1/3 text-purple-900 bg-purple-50/50">
                                Record B — <?php echo htmlspecialchars($flag['record_b']['id']); ?> (Newer)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php
                        $fields = [
                            'Full Name'            => ['name',            'name'],
                            'Date of Birth'        => ['dob',             'dob'],
                            'Residential Address'  => ['address',         'address'],
                            'Valid ID Number'       => ['id_number',       'id_number'],
                            'Civil Status'         => ['civil_status',    'civil_status'],
                            'Registration Date'    => ['registered_date', 'registered_date'],
                        ];
                        foreach ($fields as $label => [$keyA, $keyB]):
                            $valA = htmlspecialchars($flag['record_a'][$keyA] ?? 'N/A');
                            $valB = htmlspecialchars($flag['record_b'][$keyB] ?? 'N/A');
                            $match = ($valA === $valB && $valA !== 'N/A');
                        ?>
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-500"><?php echo $label; ?></td>
                            <td class="py-3 px-4 bg-blue-50/20 border-x border-blue-100 <?php echo $match ? 'font-bold text-amber-700' : ''; ?>">
                                <?php echo $valA; ?>
                                <?php if ($match): ?><span class="ml-1 text-[9px] font-black text-amber-500 uppercase tracking-wide">MATCH</span><?php endif; ?>
                            </td>
                            <td class="py-3 px-4 bg-purple-50/20 <?php echo $match ? 'font-bold text-amber-700' : ''; ?>">
                                <?php echo $valB; ?>
                                <?php if ($match): ?><span class="ml-1 text-[9px] font-black text-amber-500 uppercase tracking-wide">MATCH</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>

<!-- ── MERGE RECORDS MODAL ── -->
<div id="mergeModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
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
            <div class="space-y-2" id="mergeOptions"></div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button onclick="closeMergeModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmMerge()" id="confirmMergeBtn" class="px-5 py-2 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Confirm Record Merge</span>
            </button>
        </div>
    </div>
</div>

<!-- ── DISMISS FLAG MODAL ── -->
<div id="notDuplicateModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
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
                <textarea id="dismissReasonInput" rows="3"
                    placeholder="e.g., Different middle name & verified PhilSys ID numbers confirmed distinct individuals..."
                    class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs resize-none focus:border-[#0f53d1] focus:ring-1 focus:ring-[#0f53d1]/20 transition"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button onclick="closeNotDuplicateModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmDismissal()" id="confirmDismissBtn" class="px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Dismiss Flag & Log</span>
            </button>
        </div>
    </div>
</div>

<script>
const API_URL = '/civentral-citizen-information-and-engagement/api/admin/duplicate-flags.php';
let activeFlagCode = null;
let activeMasterVidA = null;
let activeMasterVidB = null;
let activeNameA = '';
let activeNameB = '';

// ── Merge Modal ─────────────────────────────────────────────────────────────
function openMergeModal(flagCode, vidA, vidB, nameA, nameB) {
    activeFlagCode   = flagCode;
    activeMasterVidA = vidA;
    activeMasterVidB = vidB;
    activeNameA      = nameA;
    activeNameB      = nameB;

    document.getElementById('mergeOptions').innerHTML = `
        <label class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between cursor-pointer">
            <div>
                <span class="font-bold text-[#0f53d1] block text-xs">Keep Record A as Master (CTZ-${String(vidA).padStart(4,'0')})</span>
                <span class="text-[10px] text-slate-500 font-medium">${nameA}</span>
            </div>
            <input type="radio" name="masterRecord" value="${vidA}" checked class="w-4 h-4 text-[#0f53d1]">
        </label>
        <label class="p-3 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-between cursor-pointer">
            <div>
                <span class="font-bold text-purple-700 block text-xs">Keep Record B as Master (CTZ-${String(vidB).padStart(4,'0')})</span>
                <span class="text-[10px] text-slate-500 font-medium">${nameB}</span>
            </div>
            <input type="radio" name="masterRecord" value="${vidB}" class="w-4 h-4 text-purple-600">
        </label>
    `;
    document.getElementById('mergeModal').classList.remove('hidden');
}

function closeMergeModal() {
    document.getElementById('mergeModal').classList.add('hidden');
}

async function confirmMerge() {
    const selected = document.querySelector('input[name="masterRecord"]:checked');
    if (!selected) return;

    const btn = document.getElementById('confirmMergeBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Merging...';

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'merge',
                flag_code: activeFlagCode,
                master_record_id: parseInt(selected.value),
                resolved_by: 'Citizenship Administrator'
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeMergeModal();
            removeFlagCard(activeFlagCode);
            showToast('success', `<i class="fa-solid fa-code-merge"></i> ${data.message}`);
            updateKpi('merged', 1);
            updateKpi('pending', -1);
        } else {
            showToast('error', data.message || 'Merge failed.');
        }
    } catch (e) {
        showToast('error', 'Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> Confirm Record Merge';
    }
}

// ── Dismiss Modal ───────────────────────────────────────────────────────────
function openNotDuplicateModal(flagCode) {
    activeFlagCode = flagCode;
    document.getElementById('dismissReasonInput').value = '';
    document.getElementById('notDuplicateModal').classList.remove('hidden');
}

function closeNotDuplicateModal() {
    document.getElementById('notDuplicateModal').classList.add('hidden');
}

async function confirmDismissal() {
    const reason = document.getElementById('dismissReasonInput').value.trim();
    if (!reason) {
        document.getElementById('dismissReasonInput').classList.add('border-red-400');
        document.getElementById('dismissReasonInput').focus();
        return;
    }
    document.getElementById('dismissReasonInput').classList.remove('border-red-400');

    const btn = document.getElementById('confirmDismissBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Logging...';

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'dismiss',
                flag_code: activeFlagCode,
                reason: reason,
                resolved_by: 'Citizenship Administrator'
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeNotDuplicateModal();
            removeFlagCard(activeFlagCode);
            showToast('info', `<i class="fa-solid fa-user-check"></i> ${data.message}`);
            updateKpi('dismissed', 1);
            updateKpi('pending', -1);
        } else {
            showToast('error', data.message || 'Dismissal failed.');
        }
    } catch (e) {
        showToast('error', 'Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> Dismiss Flag & Log';
    }
}

// ── Auto Scan ───────────────────────────────────────────────────────────────
async function runAutoDuplicateScan() {
    const btn = document.getElementById('scanBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[#0f53d1]"></i> <span>Scanning Registry...</span>';

    try {
        const res  = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'scan' })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('success', `<i class="fa-solid fa-rotate"></i> ${data.message}`);
            if (data.new_found > 0) {
                setTimeout(() => location.reload(), 1500);
            } else {
                btn.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-500"></i> <span class="text-emerald-700">Registry Clean</span>';
                setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2500);
                return;
            }
        } else {
            showToast('error', data.message || 'Scan failed.');
        }
    } catch (e) {
        showToast('error', 'Network error during scan.');
    }
    btn.innerHTML = orig;
    btn.disabled  = false;
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function removeFlagCard(flagCode) {
    const card = document.querySelector(`.flag-card[data-flag="${flagCode}"]`);
    if (!card) return;
    card.style.transition = 'opacity 0.3s, transform 0.3s';
    card.style.opacity    = '0';
    card.style.transform  = 'scale(0.97)';
    setTimeout(() => {
        card.remove();
        if (!document.querySelector('.flag-card')) {
            document.getElementById('flagsList').innerHTML = `
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-2xl border border-emerald-100">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-800">All Flags Resolved!</h3>
                    <p class="text-xs text-slate-500 font-medium max-w-xs mx-auto">Citizen registry is clean. Run another scan to detect new duplicates.</p>
                </div>`;
        }
    }, 350);
}

function updateKpi(key, delta) {
    const map = { pending: 'kpiPending', merged: 'kpiMerged', dismissed: 'kpiDismissed' };
    const el  = document.getElementById(map[key]);
    if (!el) return;
    const num   = (parseInt(el.textContent) || 0) + delta;
    const labels = { pending: 'Flagged Pairs', merged: 'Merged', dismissed: 'Verified Unique' };
    el.textContent = `${Math.max(0, num)} ${labels[key]}`;
}

let toastTimer;
function showToast(type, html) {
    clearTimeout(toastTimer);
    const colors = { success: 'bg-emerald-600', error: 'bg-red-600', info: 'bg-[#0f53d1]' };
    let t = document.getElementById('globalToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'globalToast';
        t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] px-5 py-3 rounded-2xl text-white text-xs font-bold shadow-xl flex items-center gap-2 transition-all duration-300';
        document.body.appendChild(t);
    }
    t.className = `fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] px-5 py-3 rounded-2xl text-white text-xs font-bold shadow-xl flex items-center gap-2 transition-all duration-300 ${colors[type] || colors.info}`;
    t.innerHTML = html;
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    toastTimer = setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(-50%) translateY(20px)'; }, 4000);
}
</script>

<?php include '../../includes/footer.php'; ?>
