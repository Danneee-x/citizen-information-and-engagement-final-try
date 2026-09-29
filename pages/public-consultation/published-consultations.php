<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/consultation-db-helper.php';

ConsultationDB::ensureTables();

$flashMessage = null;
$flashType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_consultation') {
        try {
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) throw new Exception('Consultation Title is required.');

            ConsultationDB::createConsultation([
                'title' => $title,
                'category' => $_POST['category'] ?? 'Policy Consultation',
                'background_info' => trim($_POST['background_info'] ?? ''),
                'objective' => trim($_POST['objective'] ?? ''),
                'closing_date' => $_POST['closing_date'] ?? date('Y-m-d', strtotime('+45 days')),
                'status' => 'Open',
                'created_by' => 'Caloocan City Legislative Committee'
            ]);
            $_SESSION['flash_message'] = 'Civic consultation published successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'seed_defaults') {
        try {
            ConsultationDB::seedAppDefaultConsultations();
            $_SESSION['flash_message'] = 'Official Caloocan App consultations (Night Market & Youth Tech Hub) loaded successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error loading defaults: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'clear_all') {
        try {
            ConsultationDB::clearAllConsultations();
            $_SESSION['flash_message'] = 'All consultations, stances, and outcomes have been cleared.';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'save_outcome') {
        try {
            $consultationId = (int)($_POST['consultation_id'] ?? 0);
            if (!$consultationId) throw new Exception('Invalid consultation ID.');

            ConsultationDB::saveConsultationOutcome([
                'consultation_id' => $consultationId,
                'key_findings' => trim($_POST['key_findings'] ?? ''),
                'policy_outcome' => trim($_POST['policy_outcome'] ?? ''),
                'ordinance_number' => trim($_POST['ordinance_number'] ?? ''),
                'allocated_budget' => trim($_POST['allocated_budget'] ?? ''),
                'pdf_filename' => trim($_POST['pdf_filename'] ?? 'Consultation_Outcomes_Summary.pdf'),
                'published_by' => 'Caloocan City Council Secretariat'
            ]);
            $_SESSION['flash_message'] = 'Official policy outcome and ordinance resolution published!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error publishing outcome: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }

    // Post/Redirect/Get: redirect to clean GET url
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve flash message from session
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Fetch all consultations from Database
$consultations = ConsultationDB::getAllConsultations();

// Compute stats
$totalConsultations = count($consultations);
$activeConsultations = 0;
$totalFeedbackCount = 0;
$outcomesCount = 0;

foreach ($consultations as $c) {
    if ($c['status'] === 'Open' || $c['status'] === 'Closing Soon') $activeConsultations++;
    $totalFeedbackCount += (int)($c['feedback_count'] ?? 0);
    if (!empty($c['outcome_id'])) $outcomesCount++;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <?php if ($flashMessage): ?>
    <div class="p-4 rounded-xl text-xs font-bold flex items-center justify-between shadow-xs border <?php echo $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'; ?>">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid <?php echo $flashType === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600'; ?> text-base"></i>
            <span><?php echo htmlspecialchars($flashMessage); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span>Public Consultation & Survey</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                        <span class="text-brand-dark">Civic Consultations & Policy Outcomes</span>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Draft Ordinances & Citizen Policy Stances</h1>
                </div>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">Review citizen positions (In Favor, Neutral, Against, Suggested Amendments) and publish enacted ordinances.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <?php if ($totalConsultations > 0): ?>
            <form method="POST" onsubmit="return confirm('Clear all consultation records?');">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="px-3.5 py-2.5 bg-white hover:bg-rose-50 border border-slate-200 text-rose-600 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-trash-can text-rose-500"></i>
                    <span>Clear Data</span>
                </button>
            </form>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="seed_defaults">
                <button type="submit" class="px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                    <span>Sync App Consultations</span>
                </button>
            </form>

            <button onclick="openCreateConsultationModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Publish New Consultation</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Total Consultations</span>
                <h3 class="text-2xl font-black text-slate-900"><?php echo $totalConsultations; ?></h3>
                <span class="text-[10px] text-slate-400 font-semibold">Registered ordinances</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Active Hearings</span>
                <h3 class="text-2xl font-black text-emerald-600"><?php echo $activeConsultations; ?></h3>
                <span class="text-[10px] text-emerald-600 font-bold">Open for citizen feedback</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100">
                <i class="fa-solid fa-comments"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Citizen Stances Logged</span>
                <h3 class="text-2xl font-black text-purple-600"><?php echo number_format($totalFeedbackCount); ?></h3>
                <span class="text-[10px] text-purple-600 font-bold">In Favor / Amendments</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100">
                <i class="fa-solid fa-user-pen"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Enacted Policy Outcomes</span>
                <h3 class="text-2xl font-black text-amber-600"><?php echo $outcomesCount; ?></h3>
                <span class="text-[10px] text-amber-600 font-bold">Ordinances Published</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100">
                <i class="fa-solid fa-file-shield"></i>
            </div>
        </div>
    </div>

    <?php if (empty($consultations)): ?>
    <!-- Zero State -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#0f53d1] mx-auto flex items-center justify-center text-2xl border border-blue-100">
            <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <div class="max-w-md mx-auto space-y-1.5">
            <h3 class="text-base font-black text-slate-900">No Civic Consultations in Database</h3>
            <p class="text-xs text-slate-500 font-medium">All mock data has been cleared. Publish an ordinance or community consultation to solicit citizen stances (In Favor, Neutral, Against, Suggested Amendments) and written commentary.</p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-3">
            <form method="POST">
                <input type="hidden" name="action" value="seed_defaults">
                <button type="submit" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                    <span>Load App Default Consultations (2)</span>
                </button>
            </form>
            <button onclick="openCreateConsultationModal()" class="px-4 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Publish First Consultation</span>
            </button>
        </div>
    </div>

    <?php else: ?>
    <!-- Consultations Cards List -->
    <div class="space-y-6">
        <?php foreach ($consultations as $c): 
            $totalF = (int)($c['feedback_count'] ?? 0);
            $inFavor = (int)($c['count_in_favor'] ?? 0);
            $neutral = (int)($c['count_neutral'] ?? 0);
            $against = (int)($c['count_against'] ?? 0);
            $amendments = (int)($c['count_amendments'] ?? 0);

            $favorPct = $totalF > 0 ? round(($inFavor / $totalF) * 100, 1) : 0;
            $neutralPct = $totalF > 0 ? round(($neutral / $totalF) * 100, 1) : 0;
            $againstPct = $totalF > 0 ? round(($against / $totalF) * 100, 1) : 0;
            $amendPct = $totalF > 0 ? round(($amendments / $totalF) * 100, 1) : 0;
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="space-y-1.5 max-w-3xl">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-md font-bold text-[10px] bg-purple-50 text-purple-700 border border-purple-200"><?php echo htmlspecialchars($c['category']); ?></span>
                        <span class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars($c['consultation_code']); ?></span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-xs text-slate-500 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-calendar-day text-slate-400"></i> Closes <?php echo date('M d, Y', strtotime($c['closing_date'])); ?>
                        </span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 leading-snug"><?php echo htmlspecialchars($c['title']); ?></h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium"><?php echo htmlspecialchars($c['background_info']); ?></p>
                    <div class="p-2.5 bg-blue-50/60 border border-blue-100 rounded-xl text-xs text-blue-900 font-medium">
                        <span class="font-bold text-[#0f53d1]">Legislative Objective:</span> <?php echo htmlspecialchars($c['objective']); ?>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row md:flex-col items-end gap-2 shrink-0">
                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo ($c['status'] === 'Open' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'); ?>">
                        <?php echo htmlspecialchars($c['status']); ?>
                    </span>

                    <?php if (empty($c['outcome_id'])): ?>
                    <button onclick="openOutcomeModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['title'])); ?>')" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-stamp text-amber-600"></i>
                        <span>Publish Outcome</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Citizen Stances Breakdown Bar -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                    <span>Citizen Stances Recorded (<?php echo $totalF; ?> Participants)</span>
                    <span class="text-slate-400 font-medium text-[11px]">Synced via Civentral App</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center justify-between font-bold text-emerald-800 text-[11px]">
                            <span>In Favor</span>
                            <span class="font-black text-sm"><?php echo $favorPct; ?>%</span>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-medium mt-0.5 block"><?php echo $inFavor; ?> citizens</span>
                    </div>

                    <div class="p-3 bg-slate-100 border border-slate-200 rounded-xl">
                        <div class="flex items-center justify-between font-bold text-slate-700 text-[11px]">
                            <span>Neutral</span>
                            <span class="font-black text-sm"><?php echo $neutralPct; ?>%</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-medium mt-0.5 block"><?php echo $neutral; ?> citizens</span>
                    </div>

                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl">
                        <div class="flex items-center justify-between font-bold text-rose-800 text-[11px]">
                            <span>Against</span>
                            <span class="font-black text-sm"><?php echo $againstPct; ?>%</span>
                        </div>
                        <span class="text-[10px] text-rose-600 font-medium mt-0.5 block"><?php echo $against; ?> citizens</span>
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex items-center justify-between font-bold text-amber-800 text-[11px]">
                            <span>Amendments</span>
                            <span class="font-black text-sm"><?php echo $amendPct; ?>%</span>
                        </div>
                        <span class="text-[10px] text-amber-700 font-medium mt-0.5 block"><?php echo $amendments; ?> proposals</span>
                    </div>
                </div>
            </div>

            <!-- Published Policy Outcome Banner (If Closed/Outcome Published) -->
            <?php if (!empty($c['outcome_id'])): ?>
            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-black text-emerald-800 text-xs flex items-center gap-1.5 uppercase tracking-wide">
                        <i class="fa-solid fa-certificate text-emerald-600"></i> Enacted Ordinance & Policy Outcome
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/60 px-2 py-0.5 rounded-full border border-emerald-300">
                        100% Published & Verified
                    </span>
                </div>
                <p class="font-bold text-slate-800"><span class="text-slate-500 font-medium">Ordinance No:</span> <?php echo htmlspecialchars($c['ordinance_number'] ?? 'N/A'); ?> &bull; <span class="text-slate-500 font-medium">Budget:</span> <?php echo htmlspecialchars($c['allocated_budget'] ?? 'N/A'); ?></p>
                <p class="text-slate-700 font-medium"><?php echo htmlspecialchars($c['policy_outcome']); ?></p>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<!-- CREATE CONSULTATION MODAL (z-[9999] Frosted Backdrop) -->
<div id="createConsultationModal" onclick="if(event.target === this) closeCreateConsultationModal()" class="hidden fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Publish Civic Consultation</h3>
                    <p class="text-xs text-slate-500 font-medium">Create a draft ordinance or city program consultation</p>
                </div>
            </div>
            <button onclick="closeCreateConsultationModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="create_consultation">

            <div>
                <label class="font-bold text-slate-700 block mb-1">Title / Ordinance Header <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g. Draft Ordinance No. 2026-050: Clean Water Expansion" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none text-xs font-medium focus:ring-1 focus:ring-purple-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Policy Category <span class="text-rose-500">*</span></label>
                    <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none text-xs font-medium cursor-pointer">
                        <option value="Local Economic Development">Local Economic Development</option>
                        <option value="Youth & Education Policy">Youth & Education Policy</option>
                        <option value="Urban Mobility & Transport">Urban Mobility & Transport</option>
                        <option value="Environment & Sanitation">Environment & Sanitation</option>
                        <option value="Public Safety & Security">Public Safety & Security</option>
                        <option value="Health & Social Welfare">Health & Social Welfare</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Closing Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="closing_date" value="<?php echo date('Y-m-d', strtotime('+45 days')); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none text-xs font-medium cursor-pointer">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Background Information <span class="text-rose-500">*</span></label>
                <textarea name="background_info" rows="3" required placeholder="Explain the legal or infrastructure context..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none text-xs font-medium focus:ring-1 focus:ring-purple-500"></textarea>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Legislative Objective <span class="text-rose-500">*</span></label>
                <textarea name="objective" rows="2" required placeholder="State key goals and what citizen feedback will resolve..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none text-xs font-medium focus:ring-1 focus:ring-purple-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeCreateConsultationModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Publish Consultation to App</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PUBLISH OUTCOME MODAL (z-[9999] Frosted Backdrop) -->
<div id="outcomeModal" onclick="if(event.target === this) closeOutcomeModal()" class="hidden fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100">
                    <i class="fa-solid fa-stamp"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Publish Policy Outcome</h3>
                    <p id="modalConsultationTitle" class="text-xs text-slate-500 font-medium truncate max-w-xs">Consultation Title</p>
                </div>
            </div>
            <button onclick="closeOutcomeModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="save_outcome">
            <input type="hidden" name="consultation_id" id="modalConsultationId">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Enacted Ordinance No.</label>
                    <input type="text" name="ordinance_number" placeholder="e.g. Ordinance No. 2026-042" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none text-xs font-medium">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Allocated Budget</label>
                    <input type="text" name="allocated_budget" placeholder="e.g. ₱450,000" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 outline-none text-xs font-medium">
                </div>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Key Citizen Findings <span class="text-rose-500">*</span></label>
                <textarea name="key_findings" rows="2" required placeholder="e.g. 78% of small micro-vendors voted in favor of digital POS payment terminals..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none text-xs font-medium"></textarea>
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Enacted Policy Outcome & Actions <span class="text-rose-500">*</span></label>
                <textarea name="policy_outcome" rows="3" required placeholder="Detail the official government action, regulations enacted, and implementation schedule..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none text-xs font-medium"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeOutcomeModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Publish Resolution</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateConsultationModal() {
    document.getElementById('createConsultationModal').classList.remove('hidden');
}
function closeCreateConsultationModal() {
    document.getElementById('createConsultationModal').classList.add('hidden');
}

function openOutcomeModal(id, title) {
    document.getElementById('modalConsultationId').value = id;
    document.getElementById('modalConsultationTitle').textContent = title;
    document.getElementById('outcomeModal').classList.remove('hidden');
}
function closeOutcomeModal() {
    document.getElementById('outcomeModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>
