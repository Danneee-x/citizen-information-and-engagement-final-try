<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/consultation-db-helper.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_survey') {
        try {
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                throw new Exception('Survey Title is required.');
            }

            $questionsRaw = $_POST['questions'] ?? [];
            $questions = [];
            foreach ($questionsRaw as $q) {
                if (empty($q['title'])) continue;
                $options = [];
                if (!empty($q['options'])) {
                    if (is_array($q['options'])) {
                        $options = array_filter(array_map('trim', $q['options']));
                    } else {
                        $options = array_filter(array_map('trim', explode("\n", $q['options'])));
                    }
                }
                $questions[] = [
                    'title' => trim($q['title']),
                    'question_type' => $q['type'] ?? 'multiple_choice',
                    'options' => $options,
                    'is_required' => !empty($q['required']) ? 1 : 0
                ];
            }

            ConsultationDB::createSurvey([
                'title' => $title,
                'short_description' => trim($_POST['short_description'] ?? ''),
                'category' => $_POST['category'] ?? 'Urban Mobility & Transport',
                'estimated_time' => $_POST['estimated_time'] ?? '3 mins',
                'target_audience' => $_POST['target_audience'] ?? 'All Residents',
                'sub_target' => trim($_POST['sub_target'] ?? ''),
                'open_date' => $_POST['open_date'] ?? date('Y-m-d'),
                'close_date' => $_POST['close_date'] ?? date('Y-m-d', strtotime('+30 days')),
                'privacy_setting' => $_POST['privacy_setting'] ?? 'Identified',
                'is_public_results' => isset($_POST['is_public_results']) ? 1 : 0,
                'status' => $_POST['status'] ?? 'Open',
                'created_by' => 'Caloocan City Admin'
            ], $questions);

            $_SESSION['flash_message'] = 'Survey created and published successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error creating survey: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'seed_defaults') {
        try {
            ConsultationDB::seedAppDefaultSurveys();
            $_SESSION['flash_message'] = 'Official Caloocan App surveys (Mobility & Waste Segregation) imported successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error importing defaults: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'clear_all') {
        try {
            ConsultationDB::clearAllSurveys();
            $_SESSION['flash_message'] = 'All surveys and responses have been cleared.';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error clearing surveys: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'update_status') {
        $surveyId = (int)($_POST['survey_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'Open';
        if ($surveyId) {
            ConsultationDB::updateSurveyStatus($surveyId, $newStatus);
            $_SESSION['flash_message'] = "Survey status updated to {$newStatus}.";
            $_SESSION['flash_type'] = 'success';
        }
    } elseif ($action === 'delete_survey') {
        $surveyId = (int)($_POST['survey_id'] ?? 0);
        if ($surveyId) {
            ConsultationDB::deleteSurvey($surveyId);
            $_SESSION['flash_message'] = 'Survey removed from system.';
            $_SESSION['flash_type'] = 'success';
        }
    }

    // Post/Redirect/Get: redirect to clean GET url so refreshing (F5) never resubmits form
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve flash message from session
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Fetch all surveys from Database
$surveys = ConsultationDB::getAllSurveys();

// Compute real KPI counts
$totalSurveys = count($surveys);
$activeSurveysCount = 0;
$totalResponsesCount = 0;
$closingSoonCount = 0;

foreach ($surveys as $s) {
    if ($s['status'] === 'Open') $activeSurveysCount++;
    if ($s['status'] === 'Closing Soon') $closingSoonCount++;
    $totalResponsesCount += (int)($s['response_count'] ?? 0);
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
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span>Public Consultation & Survey</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                        <span class="text-brand-dark">Manage Surveys</span>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Citizen Survey Directory & Builder</h1>
                </div>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">Configure community surveys with Rating Scales, Likert tests, multiple choices, and open citizen commentary.</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php if ($totalSurveys > 0): ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to clear all surveys and responses? This cannot be undone.');">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="px-3.5 py-2.5 bg-white hover:bg-rose-50 border border-slate-200 text-rose-600 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-trash-can text-rose-500"></i>
                    <span>Clear Data</span>
                </button>
            </form>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="seed_defaults">
                <button type="submit" title="Sync standard Caloocan citizen app surveys" class="px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                    <span>Sync App Surveys</span>
                </button>
            </form>

            <button onclick="openCreateSurveyModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Create New Survey</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Total Surveys</span>
                <h3 class="text-2xl font-black text-slate-900"><?php echo $totalSurveys; ?></h3>
                <span class="text-[10px] text-slate-400 font-semibold">Registered in system</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Active / Open</span>
                <h3 class="text-2xl font-black text-emerald-600"><?php echo $activeSurveysCount; ?></h3>
                <span class="text-[10px] text-emerald-600 font-bold">Accepting responses</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Total Responses</span>
                <h3 class="text-2xl font-black text-purple-600"><?php echo number_format($totalResponsesCount); ?></h3>
                <span class="text-[10px] text-purple-600 font-bold">Citizen submissions</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Closing Soon</span>
                <h3 class="text-2xl font-black text-amber-600"><?php echo $closingSoonCount; ?></h3>
                <span class="text-[10px] text-amber-600 font-bold">Near deadline</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>
    </div>

    <!-- Filter and Search Row -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="filterStatus('all')" id="btnFilterAll" class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-[#0f53d1] text-white">All Surveys</button>
            <button onclick="filterStatus('Open')" id="btnFilterOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200">Open</button>
            <button onclick="filterStatus('Closing Soon')" id="btnFilterClosing" class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200">Closing Soon</button>
            <button onclick="filterStatus('Completed')" id="btnFilterCompleted" class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200">Completed</button>
            <button onclick="filterStatus('Draft')" id="btnFilterDraft" class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200">Drafts</button>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="surveySearchInput" onkeyup="filterSurveysTable()" placeholder="Search title or code..." class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:ring-1 focus:ring-[#0f53d1]">
            </div>
            <select id="categoryFilter" onchange="filterSurveysTable()" class="bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold px-2.5 py-1.5 text-slate-600 outline-none cursor-pointer">
                <option value="all">All Categories</option>
                <option value="Urban Mobility & Transport">Urban Mobility & Transport</option>
                <option value="Environment & Sanitation">Environment & Sanitation</option>
                <option value="Infrastructure & Works">Infrastructure & Works</option>
                <option value="Public Safety">Public Safety</option>
                <option value="Youth & Education Policy">Youth & Education Policy</option>
                <option value="Local Economic Development">Local Economic Development</option>
            </select>
        </div>
    </div>

    <!-- Main Content Layout (Full Width Table) -->
    <div id="surveyTableWrapper" class="w-full">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">

                <?php if (empty($surveys)): ?>
                <!-- Clean Empty State (When mock data is cleared) -->
                <div class="p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#0f53d1] mx-auto flex items-center justify-center text-2xl border border-blue-100">
                        <i class="fa-solid fa-square-poll-vertical"></i>
                    </div>
                    <div class="max-w-md mx-auto space-y-1.5">
                        <h3 class="text-base font-black text-slate-900">No Surveys in Database</h3>
                        <p class="text-xs text-slate-500 font-medium">All previous mock data has been cleared. The civic survey database is ready for real citizen participation.</p>
                    </div>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <form method="POST">
                            <input type="hidden" name="action" value="seed_defaults">
                            <button type="submit" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                                <span>Load App Default Surveys (2)</span>
                            </button>
                        </form>
                        <button onclick="openCreateSurveyModal()" class="px-4 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Create First Survey</span>
                        </button>
                    </div>
                </div>

                <?php else: ?>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 text-[11px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Survey Details</th>
                                <th class="py-3.5 px-3">Target Audience</th>
                                <th class="py-3.5 px-3">Schedule</th>
                                <th class="py-3.5 px-3">Questions & Type</th>
                                <th class="py-3.5 px-3">Responses</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="surveysTableBody" class="divide-y divide-slate-100">
                            <?php foreach ($surveys as $s): ?>
                            <tr class="survey-row hover:bg-slate-50 transition cursor-pointer select-none" 
                                onclick="selectSurveyRow(this, '<?php echo $s['id']; ?>')"
                                data-id="<?php echo $s['id']; ?>"
                                data-code="<?php echo htmlspecialchars($s['survey_code']); ?>"
                                data-status="<?php echo $s['status']; ?>"
                                data-category="<?php echo htmlspecialchars($s['category']); ?>">
                                
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm border border-slate-200/50">
                                            <i class="fa-solid fa-square-poll-vertical"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 text-xs truncate max-w-xs md:max-w-md"><?php echo htmlspecialchars($s['title']); ?></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold text-slate-400"><?php echo htmlspecialchars($s['survey_code']); ?></span>
                                                <span class="px-2 py-0.2 rounded-md font-bold text-[9px] border bg-blue-50 text-[#0f53d1] border-blue-100"><?php echo htmlspecialchars($s['category']); ?></span>
                                                <span class="text-[10px] text-slate-400">&bull; Est. <?php echo htmlspecialchars($s['estimated_time']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3 font-semibold text-slate-700">
                                    <span class="flex items-center gap-1"><i class="fa-solid fa-users text-[10px] text-slate-400"></i> <?php echo htmlspecialchars($s['target_audience']); ?></span>
                                    <?php if (!empty($s['sub_target'])): ?>
                                    <span class="text-[9px] text-slate-400 block font-normal mt-0.5"><?php echo htmlspecialchars($s['sub_target']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800 text-[11px]"><?php echo date('M d, Y', strtotime($s['open_date'])); ?> &ndash; <?php echo date('M d, Y', strtotime($s['close_date'])); ?></p>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-clock text-[8px]"></i> Closes <?php echo date('M d', strtotime($s['close_date'])); ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                                            <?php echo count($s['questions']); ?> Questions
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-slate-800"><?php echo number_format($s['response_count']); ?></span>
                                        <?php if ($s['avg_rating']): ?>
                                        <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 border border-amber-200 rounded text-[9px] font-bold flex items-center gap-0.5">
                                            <i class="fa-solid fa-star text-[8px]"></i> <?php echo $s['avg_rating']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    <?php
                                    $badge = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                                    if ($s['status'] === 'Closing Soon') $badge = 'bg-amber-50 text-amber-600 border-amber-200';
                                    if ($s['status'] === 'Completed' || $s['status'] === 'Closed') $badge = 'bg-slate-100 text-slate-600 border-slate-200';
                                    if ($s['status'] === 'Draft') $badge = 'bg-indigo-50 text-indigo-600 border-indigo-200';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black border <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars($s['status']); ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5" onclick="event.stopPropagation()">
                                        <a href="live-results.php?survey_id=<?php echo $s['id']; ?>" title="View Live Results" class="w-7 h-7 rounded-lg bg-blue-50 text-[#0f53d1] hover:bg-blue-100 flex items-center justify-center transition text-xs">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </a>

                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this survey and its recorded responses?');">
                                            <input type="hidden" name="action" value="delete_survey">
                                            <input type="hidden" name="survey_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" title="Delete Survey" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition text-xs cursor-pointer">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>
        </div>
</main>

<!-- ============================================================================== -->
<!-- SURVEY DETAILS & QUESTION BREAKDOWN MODAL                                      -->
<!-- ============================================================================== -->
<div id="surveyDetailsDrawer" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
        
        <!-- Modal Light Header with Back button, Code, Status, Close button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeSurveyDrawer()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Surveys Queue">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Survey Overview</h3>
                        <span id="drawerSurveyCode" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">SRV-2026-001</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Public Consultation & Citizen Opinion Poll</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="drawerSurveyStatusBadge" class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200">Open</span>
                <button type="button" onclick="closeSurveyDrawer()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
            
            <!-- Survey Narrative Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-2.5 shadow-xs">
                <h3 id="drawerSurveyTitle" class="text-base font-black text-slate-900 leading-snug">Survey Title</h3>
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-xs text-slate-700 font-medium leading-relaxed">
                    <p id="drawerSurveyDescription">Description...</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 pt-1">
                    <span id="drawerSurveyCategory" class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">Category</span>
                    <span class="text-slate-300">&bull;</span>
                    <span id="drawerSurveyPrivacy" class="px-2.5 py-1 rounded-full font-bold text-[10px] bg-slate-100 text-slate-700">Identified</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Responses</span>
                    <h4 id="drawerTotalResponses" class="text-2xl font-black text-slate-900">0</h4>
                    <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                        <span>Live Sync with Mobile App</span>
                    </span>
                </div>
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Target Audience</span>
                    <h4 id="drawerSurveyTarget" class="text-sm font-black text-slate-900 truncate">All Residents</h4>
                    <span class="text-[10px] text-slate-400 font-medium">Caloocan Citizens</span>
                </div>
            </div>

            <!-- Question Architecture Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-list-check text-indigo-600"></i>
                    <span>Question Architecture</span>
                </span>
                <div id="drawerQuestionsList" class="space-y-2 text-xs max-h-60 overflow-y-auto custom-scrollbar">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Action Link -->
            <div class="pt-1">
                <a id="drawerLiveResultsLink" href="live-results.php" class="w-full py-3 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-chart-pie text-xs"></i>
                    <span>View Live Analytics & Results Breakdown</span>
                </a>
            </div>

        </div>

        <!-- Modal Bottom Navigation (Back & Close Buttons) -->
        <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
            <button type="button" onclick="closeSurveyDrawer()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Surveys Queue</span>
            </button>
            <button type="button" onclick="closeSurveyDrawer()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>

    </div>
</div>

<!-- SURVEY BUILDER MODAL (z-[9999] Frosted Backdrop covers sticky header) -->
<div id="createSurveyModal" onclick="if(event.target === this) closeCreateSurveyModal()" class="hidden fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar my-auto">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                    <i class="fa-solid fa-hammer"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Survey Builder</h3>
                    <p class="text-xs text-slate-500 font-medium">Configure survey details, question types, target audience & auto-close</p>
                </div>
            </div>
            <button onclick="closeCreateSurveyModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form method="POST" id="createSurveyForm" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="create_survey">

            <!-- Title & Description -->
            <div>
                <label class="font-bold text-slate-700 block mb-1">Survey Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g., 2026 Barangay Road Repair & Infrastructure Priorities" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-3 outline-none font-medium text-xs focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Survey Description / Objective <span class="text-rose-500">*</span></label>
                <textarea name="short_description" rows="2" required placeholder="Briefly describe the purpose of this public consultation for citizens..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-3 outline-none font-medium text-xs focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]"></textarea>
            </div>

            <!-- Category & Target Audience Filter -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Category <span class="text-rose-500">*</span></label>
                    <select name="category" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="Urban Mobility & Transport">Urban Mobility & Transport</option>
                        <option value="Environment & Sanitation">Environment & Sanitation</option>
                        <option value="Infrastructure & Works">Infrastructure & Works</option>
                        <option value="Public Safety">Public Safety & Disaster</option>
                        <option value="Youth & Education Policy">Youth & Education Policy</option>
                        <option value="Health & Social Services">Health & Social Services</option>
                        <option value="Local Economic Development">Local Economic Development</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Estimated Completion Time</label>
                    <input type="text" name="estimated_time" value="3 mins" placeholder="e.g. 3 mins, 5 mins" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Target Audience Filter <span class="text-rose-500">*</span></label>
                    <select name="target_audience" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="All Residents">All Residents (Entire Caloocan)</option>
                        <option value="Specific Barangay">Specific Barangay</option>
                        <option value="Specific Demographic">Specific Demographic (Youth, Senior, PWD)</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Specific Sub-Target (Optional)</label>
                    <input type="text" name="sub_target" placeholder="e.g. Districts 1-3, or Barangay 178" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs">
                </div>
            </div>

            <!-- Open Date / Close Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Open Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="open_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Close Date (Auto-closes after deadline) <span class="text-rose-500">*</span></label>
                    <input type="date" name="close_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                </div>
            </div>

            <!-- Privacy Setting -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between select-none">
                <div>
                    <span class="font-bold text-slate-900 block text-xs">Response Privacy Setting</span>
                    <span class="text-[11px] text-slate-500 font-medium">Require Citizen Verification (Identified) vs Anonymous Responses</span>
                </div>
                <select name="privacy_setting" class="bg-white border border-slate-200 text-xs font-bold rounded-lg px-2.5 py-1.5 outline-none cursor-pointer">
                    <option value="Identified">Identified Responses</option>
                    <option value="Anonymous">Anonymous Responses</option>
                </select>
            </div>

            <!-- QUESTION TYPES & BUILDER (Matching mobile app question types) -->
            <div class="border-t border-slate-100 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider">Question Architecture & Types</h4>
                        <span class="text-[10px] text-slate-400 font-medium">Supports Rating Scales (1-5★), Likert, Choices, Yes/No, and Commentary</span>
                    </div>
                    <button type="button" onclick="addQuestionField()" class="text-[11px] font-bold text-[#0f53d1] hover:underline flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Add Question
                    </button>
                </div>

                <div id="questionsBuilderContainer" class="space-y-3">
                    <!-- Default Question 1 -->
                    <div class="question-block p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5" data-index="0">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700 text-xs">Question 1</span>
                            <div class="flex items-center gap-2">
                                <select name="questions[0][type]" onchange="onQuestionTypeChange(this)" class="bg-white border border-slate-200 text-slate-700 text-[11px] font-semibold rounded-lg px-2 py-1 outline-none cursor-pointer">
                                    <option value="rating_scale">Rating Scale (1-5 Stars)</option>
                                    <option value="likert_scale">Likert Scale (Strongly Disagree to Strongly Agree)</option>
                                    <option value="multiple_choice">Multiple Choice (Single Select)</option>
                                    <option value="multiple_selection">Multiple Selection (Checkbox)</option>
                                    <option value="yes_no">Yes / No</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="long_answer">Commentary / Long Answer</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" name="questions[0][title]" required placeholder="e.g., How would you rate the overall pedestrian safety and streetlighting?" class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium">
                        <div class="options-container hidden">
                            <label class="text-[10px] text-slate-400 font-bold block mb-1">Options (One per line or comma-separated)</label>
                            <textarea name="questions[0][options]" rows="2" placeholder="Option 1&#10;Option 2&#10;Option 3" class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeCreateSurveyModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Publish Survey to App</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const surveysData = <?php echo json_encode(array_column($surveys, null, 'id')); ?>;
let questionCount = 1;

function selectSurveyRow(rowElement, id) {
    document.querySelectorAll('.survey-row').forEach(r => r.classList.remove('bg-blue-50/50', 'border-l-4', 'border-l-[#0f53d1]'));
    rowElement.classList.add('bg-blue-50/50', 'border-l-4', 'border-l-[#0f53d1]');

    const data = surveysData[id];
    if (!data) return;

    document.getElementById('drawerSurveyCode').textContent = data.survey_code || 'SRV';
    document.getElementById('drawerSurveyTitle').textContent = data.title;
    document.getElementById('drawerSurveyDescription').textContent = data.short_description || 'No description provided.';
    document.getElementById('drawerSurveyCategory').textContent = data.category;
    document.getElementById('drawerSurveyPrivacy').textContent = data.privacy_setting;
    document.getElementById('drawerTotalResponses').textContent = Number(data.response_count || 0).toLocaleString();
    document.getElementById('drawerSurveyTarget').textContent = data.target_audience;

    const statusBadge = document.getElementById('drawerSurveyStatusBadge');
    statusBadge.textContent = data.status;
    statusBadge.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full ' + (data.status === 'Open' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200');

    // Populate questions list
    const qList = document.getElementById('drawerQuestionsList');
    qList.innerHTML = '';
    if (data.questions && data.questions.length > 0) {
        data.questions.forEach((q, idx) => {
            const item = document.createElement('div');
            item.className = 'p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1';
            let typeBadge = q.question_type.replace('_', ' ').toUpperCase();
            item.innerHTML = `
                <div class="flex items-center justify-between text-[10px]">
                    <span class="font-bold text-slate-700">Q${idx + 1} &bull; ${typeBadge}</span>
                </div>
                <p class="font-semibold text-slate-800 text-xs">${q.title}</p>
            `;
            qList.appendChild(item);
        });
    } else {
        qList.innerHTML = '<p class="text-slate-400 italic text-[11px]">No questions configured.</p>';
    }

    document.getElementById('drawerLiveResultsLink').href = 'live-results.php?survey_id=' + data.id;

    // Show Modal
    const drawer = document.getElementById('surveyDetailsDrawer');
    if (drawer) {
        drawer.classList.remove('hidden');
    }
}

function closeSurveyDrawer() {
    const drawer = document.getElementById('surveyDetailsDrawer');
    if (drawer) {
        drawer.classList.add('hidden');
    }
    document.querySelectorAll('.survey-row').forEach(r => r.classList.remove('bg-blue-50/50', 'border-l-4', 'border-l-[#0f53d1]'));
}

function filterStatus(status) {
    ['all', 'Open', 'Closing Soon', 'Completed', 'Draft'].forEach(s => {
        const btn = document.getElementById('btnFilter' + (s === 'all' ? 'All' : s.replace(' ', '')));
        if (btn) {
            if (s === status) {
                btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition bg-[#0f53d1] text-white';
            } else {
                btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-100 text-slate-600 hover:bg-slate-200';
            }
        }
    });

    document.querySelectorAll('.survey-row').forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterSurveysTable() {
    const query = document.getElementById('surveySearchInput').value.toLowerCase().trim();
    const cat = document.getElementById('categoryFilter').value;

    document.querySelectorAll('.survey-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        const rowCat = row.getAttribute('data-category');
        const matchesQuery = !query || text.includes(query);
        const matchesCat = (cat === 'all') || (rowCat === cat);

        if (matchesQuery && matchesCat) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openCreateSurveyModal() {
    document.getElementById('createSurveyModal').classList.remove('hidden');
}

function closeCreateSurveyModal() {
    document.getElementById('createSurveyModal').classList.add('hidden');
}

function onQuestionTypeChange(selectElem) {
    const block = selectElem.closest('.question-block');
    const optionsContainer = block.querySelector('.options-container');
    const val = selectElem.value;
    if (val === 'multiple_choice' || val === 'multiple_selection') {
        optionsContainer.classList.remove('hidden');
    } else {
        optionsContainer.classList.add('hidden');
    }
}

function addQuestionField() {
    const idx = questionCount++;
    const container = document.getElementById('questionsBuilderContainer');
    const div = document.createElement('div');
    div.className = 'question-block p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5';
    div.setAttribute('data-index', idx);
    div.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="font-bold text-slate-700 text-xs">Question ${idx + 1}</span>
            <div class="flex items-center gap-2">
                <select name="questions[${idx}][type]" onchange="onQuestionTypeChange(this)" class="bg-white border border-slate-200 text-slate-700 text-[11px] font-semibold rounded-lg px-2 py-1 outline-none cursor-pointer">
                    <option value="rating_scale">Rating Scale (1-5 Stars)</option>
                    <option value="likert_scale">Likert Scale (Strongly Disagree to Strongly Agree)</option>
                    <option value="multiple_choice">Multiple Choice (Single Select)</option>
                    <option value="multiple_selection">Multiple Selection (Checkbox)</option>
                    <option value="yes_no">Yes / No</option>
                    <option value="short_answer">Short Answer</option>
                    <option value="long_answer">Commentary / Long Answer</option>
                </select>
                <button type="button" onclick="this.closest('.question-block').remove()" class="text-rose-500 hover:text-rose-700 text-xs">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>
        <input type="text" name="questions[${idx}][title]" required placeholder="Enter question prompt..." class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium">
        <div class="options-container hidden">
            <label class="text-[10px] text-slate-400 font-bold block mb-1">Options (One per line or comma-separated)</label>
            <textarea name="questions[${idx}][options]" rows="2" placeholder="Option 1&#10;Option 2&#10;Option 3" class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium"></textarea>
        </div>
    `;
    container.appendChild(div);
}

// Dismiss modal when clicking backdrop
document.getElementById('surveyDetailsDrawer')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSurveyDrawer();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const drawer = document.getElementById('surveyDetailsDrawer');
        if (drawer && !drawer.classList.contains('hidden')) {
            closeSurveyDrawer();
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
