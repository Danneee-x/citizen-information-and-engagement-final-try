<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/consultation-db-helper.php';

// Fetch all available surveys
$allSurveys = ConsultationDB::getAllSurveys();

// Determine selected survey
$selectedSurveyId = isset($_GET['survey_id']) ? (int)$_GET['survey_id'] : (!empty($allSurveys) ? (int)$allSurveys[0]['id'] : null);
$selectedSurvey = null;

if ($selectedSurveyId) {
    $selectedSurvey = ConsultationDB::getSurveyById($selectedSurveyId);
}

// Fetch responses for selected survey
$responses = [];
if ($selectedSurvey) {
    $pdo = Database::getInstance()->getPdo();
    $rStmt = $pdo->prepare("SELECT * FROM `survey_responses` WHERE survey_id = ? ORDER BY submitted_at DESC");
    $rStmt->execute([$selectedSurvey['id']]);
    $responses = $rStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalResponses = count($responses);

// Calculate question-level stats dynamically
$questionStats = [];
if ($selectedSurvey && !empty($selectedSurvey['questions'])) {
    foreach ($selectedSurvey['questions'] as $q) {
        $qId = 'q_' . $q['id'];
        $type = $q['question_type'];
        $options = $q['options'] ?? [];

        $stat = [
            'question' => $q,
            'type' => $type,
            'title' => $q['title'],
            'total_answers' => 0,
            'data' => []
        ];

        if ($type === 'rating_scale') {
            $starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            $sumRating = 0;
            $ratedCount = 0;

            foreach ($responses as $r) {
                $ans = json_decode($r['answers_json'], true) ?: [];
                $val = $ans[$qId] ?? ($r['overall_rating'] ?? null);
                if ($val !== null && is_numeric($val)) {
                    $star = (int)round((float)$val);
                    if ($star >= 1 && $star <= 5) {
                        $starCounts[$star]++;
                        $sumRating += (float)$val;
                        $ratedCount++;
                    }
                }
            }

            $stat['total_answers'] = $ratedCount;
            $stat['avg_score'] = $ratedCount > 0 ? round($sumRating / $ratedCount, 1) : 0;
            $stat['stars'] = [];
            foreach ($starCounts as $sNum => $sCount) {
                $pct = $ratedCount > 0 ? round(($sCount / $ratedCount) * 100, 1) : 0;
                $stat['stars'][$sNum] = ['count' => $sCount, 'pct' => $pct];
            }
        } elseif ($type === 'likert_scale') {
            $likertKeys = ['Strongly Agree', 'Agree', 'Neutral / Undecided', 'Disagree', 'Strongly Disagree'];
            $likertCounts = array_fill_keys($likertKeys, 0);
            $totalLikert = 0;

            foreach ($responses as $r) {
                $ans = json_decode($r['answers_json'], true) ?: [];
                $val = $ans[$qId] ?? null;
                if ($val && isset($likertCounts[$val])) {
                    $likertCounts[$val]++;
                    $totalLikert++;
                }
            }

            $stat['total_answers'] = $totalLikert;
            $stat['likert'] = [];
            foreach ($likertCounts as $lKey => $lCount) {
                $pct = $totalLikert > 0 ? round(($lCount / $totalLikert) * 100, 1) : 0;
                $stat['likert'][$lKey] = ['count' => $lCount, 'pct' => $pct];
            }
        } elseif ($type === 'yes_no') {
            $yesCount = 0;
            $noCount = 0;
            $totalYn = 0;

            foreach ($responses as $r) {
                $ans = json_decode($r['answers_json'], true) ?: [];
                $val = strtolower((string)($ans[$qId] ?? ''));
                if ($val === 'yes' || $val === '1' || $val === 'true') {
                    $yesCount++;
                    $totalYn++;
                } elseif ($val === 'no' || $val === '0' || $val === 'false') {
                    $noCount++;
                    $totalYn++;
                }
            }

            $stat['total_answers'] = $totalYn;
            $stat['yes_pct'] = $totalYn > 0 ? round(($yesCount / $totalYn) * 100, 1) : 0;
            $stat['no_pct'] = $totalYn > 0 ? round(($noCount / $totalYn) * 100, 1) : 0;
            $stat['yes_count'] = $yesCount;
            $stat['no_count'] = $noCount;
        } elseif ($type === 'multiple_choice' || $type === 'multiple_selection') {
            $optCounts = [];
            foreach ($options as $opt) {
                $optCounts[$opt] = 0;
            }
            $totalPicks = 0;

            foreach ($responses as $r) {
                $ans = json_decode($r['answers_json'], true) ?: [];
                $val = $ans[$qId] ?? null;
                if (is_array($val)) {
                    foreach ($val as $subVal) {
                        if (isset($optCounts[$subVal])) {
                            $optCounts[$subVal]++;
                            $totalPicks++;
                        }
                    }
                } elseif (is_string($val) && isset($optCounts[$val])) {
                    $optCounts[$val]++;
                    $totalPicks++;
                }
            }

            $stat['total_answers'] = $totalPicks;
            $stat['options'] = [];
            foreach ($optCounts as $oLabel => $oCount) {
                $pct = $totalPicks > 0 ? round(($oCount / $totalPicks) * 100, 1) : 0;
                $stat['options'][] = ['label' => $oLabel, 'count' => $oCount, 'pct' => $pct];
            }
        } elseif ($type === 'long_answer' || $type === 'short_answer') {
            $comments = [];
            foreach ($responses as $r) {
                $ans = json_decode($r['answers_json'], true) ?: [];
                $text = trim((string)($ans[$qId] ?? ($r['commentary'] ?? '')));
                if (!empty($text)) {
                    $comments[] = [
                        'citizen_name' => $r['citizen_name'] ?? 'Verified Citizen',
                        'barangay' => $r['barangay'] ?? 'District 1',
                        'commentary' => $text,
                        'date' => date('M d, Y', strtotime($r['submitted_at']))
                    ];
                }
            }
            $stat['total_answers'] = count($comments);
            $stat['comments'] = array_slice($comments, 0, 15);
        }

        $questionStats[] = $stat;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span>Public Consultation & Survey</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                        <span class="text-brand-dark">Live Results</span>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Real-Time Survey Analytics & Citizen Feedback</h1>
                </div>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">Live aggregated responses, star rating distributions, Likert scales, and qualitative commentary feeds.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="manage-surveys.php" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-list-check text-slate-400"></i>
                <span>Manage Surveys</span>
            </a>

            <?php if (!empty($allSurveys)): ?>
            <select onchange="window.location.href='live-results.php?survey_id=' + this.value" class="bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-3 py-2.5 shadow-xs outline-none cursor-pointer">
                <?php foreach ($allSurveys as $srv): ?>
                <option value="<?php echo $srv['id']; ?>" <?php echo ($selectedSurveyId == $srv['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($srv['survey_code'] . ' - ' . $srv['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($allSurveys)): ?>
    <!-- Zero Surveys State -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#0f53d1] mx-auto flex items-center justify-center text-2xl border border-blue-100">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div class="max-w-md mx-auto space-y-1.5">
            <h3 class="text-base font-black text-slate-900">No Live Survey Data Available</h3>
            <p class="text-xs text-slate-500 font-medium">All mock data has been cleared. To view live analytics, please publish a survey from the Manage Surveys engine.</p>
        </div>
        <div class="pt-2">
            <a href="manage-surveys.php" class="px-5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Create or Sync Survey</span>
            </a>
        </div>
    </div>

    <?php elseif ($totalResponses === 0): ?>
    <!-- Survey exists but 0 responses yet -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-10 text-center space-y-4">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-xl border border-amber-100">
            <i class="fa-solid fa-hourglass-start"></i>
        </div>
        <div class="max-w-md mx-auto space-y-1.5">
            <h3 class="text-base font-black text-slate-900"><?php echo htmlspecialchars($selectedSurvey['title']); ?></h3>
            <p class="text-xs text-slate-500 font-medium">Survey code: <span class="font-bold text-slate-700"><?php echo htmlspecialchars($selectedSurvey['survey_code']); ?></span>. No citizen responses have been submitted yet. Responses from the Civentral citizen mobile app will appear here in real time.</p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-3">
            <a href="manage-surveys.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                <span>Back to Surveys</span>
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- Active Real-Time Analytics Dashboard -->

    <!-- Header Meta & KPI Grid -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-blue-50 text-[#0f53d1] border border-blue-100"><?php echo htmlspecialchars($selectedSurvey['category']); ?></span>
                    <span class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars($selectedSurvey['survey_code']); ?></span>
                </div>
                <h2 class="text-lg font-black text-slate-900"><?php echo htmlspecialchars($selectedSurvey['title']); ?></h2>
                <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($selectedSurvey['short_description']); ?></p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Tracking</span>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-1">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Total Submissions</span>
                <h4 class="text-xl font-black text-slate-900"><?php echo number_format($totalResponses); ?></h4>
                <span class="text-[10px] text-emerald-600 font-bold">100% Verified</span>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Average Rating</span>
                <h4 class="text-xl font-black text-amber-600"><?php echo $selectedSurvey['avg_rating'] ? $selectedSurvey['avg_rating'] . ' ★' : 'N/A'; ?></h4>
                <span class="text-[10px] text-slate-400 font-medium">Out of 5.0 Stars</span>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Est. Completion</span>
                <h4 class="text-xl font-black text-slate-900"><?php echo htmlspecialchars($selectedSurvey['estimated_time']); ?></h4>
                <span class="text-[10px] text-blue-600 font-bold">Quick Mobile Form</span>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                <span class="text-[10px] text-slate-400 font-bold uppercase">Target Audience</span>
                <h4 class="text-xs font-black text-slate-900 truncate"><?php echo htmlspecialchars($selectedSurvey['target_audience']); ?></h4>
                <span class="text-[10px] text-slate-400 font-medium">Caloocan Citizens</span>
            </div>
        </div>
    </div>

    <!-- Questions Dynamic Breakdown Cards -->
    <div class="space-y-6">
        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-list-ol text-[#0f53d1]"></i>
            <span>Question Responses Breakdown (<?php echo count($questionStats); ?> Questions)</span>
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($questionStats as $idx => $qs): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4 flex flex-col justify-between">
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-blue-50 text-[#0f53d1] border border-blue-100">
                            Q<?php echo $idx + 1; ?> &bull; <?php echo strtoupper(str_replace('_', ' ', $qs['type'])); ?>
                        </span>
                        <span class="text-[11px] font-bold text-slate-400"><?php echo $qs['total_answers']; ?> Responses</span>
                    </div>
                    <h4 class="text-sm font-black text-slate-900 leading-snug"><?php echo htmlspecialchars($qs['title']); ?></h4>
                </div>

                <!-- RATING SCALE VISUALIZER -->
                <?php if ($qs['type'] === 'rating_scale'): ?>
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-4 p-3 bg-amber-50/60 border border-amber-200/60 rounded-xl">
                        <div class="text-center">
                            <span class="text-2xl font-black text-amber-700"><?php echo $qs['avg_score']; ?></span>
                            <span class="block text-[9px] font-bold text-amber-600 uppercase">Avg Score</span>
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex text-amber-500 text-sm">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fa-solid fa-star <?php echo ($s <= round($qs['avg_score'])) ? 'text-amber-500' : 'text-slate-200'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[11px] font-medium text-slate-500">Based on <?php echo $qs['total_answers']; ?> citizen ratings</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <?php for ($s = 5; $s >= 1; $s--): 
                            $info = $qs['stars'][$s] ?? ['count' => 0, 'pct' => 0];
                        ?>
                        <div class="flex items-center gap-2">
                            <span class="w-12 font-bold text-slate-600 text-[11px] flex items-center gap-1"><?php echo $s; ?> <i class="fa-solid fa-star text-[9px] text-amber-500"></i></span>
                            <div class="flex-1 bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full transition-all" style="width: <?php echo $info['pct']; ?>%;"></div>
                            </div>
                            <span class="w-16 text-right font-black text-slate-800 text-[11px]"><?php echo $info['pct']; ?>% <span class="text-slate-400 font-normal">(<?php echo $info['count']; ?>)</span></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- LIKERT SCALE VISUALIZER -->
                <?php elseif ($qs['type'] === 'likert_scale'): ?>
                <div class="space-y-2 pt-2 text-xs">
                    <?php 
                    $colors = [
                        'Strongly Agree' => 'bg-emerald-600',
                        'Agree' => 'bg-emerald-400',
                        'Neutral / Undecided' => 'bg-slate-400',
                        'Disagree' => 'bg-amber-500',
                        'Strongly Disagree' => 'bg-rose-500'
                    ];
                    foreach ($qs['likert'] as $label => $lData): 
                        $col = $colors[$label] ?? 'bg-blue-500';
                    ?>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-700">
                            <span><?php echo htmlspecialchars($label); ?></span>
                            <span class="text-slate-900"><?php echo $lData['pct']; ?>% (<?php echo $lData['count']; ?>)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="<?php echo $col; ?> h-full rounded-full" style="width: <?php echo $lData['pct']; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- YES / NO VISUALIZER -->
                <?php elseif ($qs['type'] === 'yes_no'): ?>
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-emerald-800 uppercase flex items-center gap-1"><i class="fa-solid fa-thumbs-up"></i> Yes</span>
                            <span class="text-lg font-black text-emerald-600"><?php echo $qs['yes_pct']; ?>%</span>
                        </div>
                        <span class="text-[11px] text-emerald-700 font-medium"><?php echo $qs['yes_count']; ?> Citizens In Favor</span>
                    </div>

                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-rose-800 uppercase flex items-center gap-1"><i class="fa-solid fa-thumbs-down"></i> No</span>
                            <span class="text-lg font-black text-rose-600"><?php echo $qs['no_pct']; ?>%</span>
                        </div>
                        <span class="text-[11px] text-rose-700 font-medium"><?php echo $qs['no_count']; ?> Citizens Opposed</span>
                    </div>
                </div>

                <!-- MULTIPLE CHOICE / MULTI SELECTION VISUALIZER -->
                <?php elseif ($qs['type'] === 'multiple_choice' || $qs['type'] === 'multiple_selection'): ?>
                <div class="space-y-2.5 pt-2 text-xs">
                    <?php foreach ($qs['options'] as $o): ?>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-700">
                            <span class="truncate max-w-xs"><?php echo htmlspecialchars($o['label']); ?></span>
                            <span class="text-slate-900 font-black"><?php echo $o['pct']; ?>% <span class="text-slate-400 font-normal">(<?php echo $o['count']; ?>)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-[#0f53d1] h-full rounded-full" style="width: <?php echo $o['pct']; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- COMMENTARY & LONG ANSWER VISUALIZER -->
                <?php elseif ($qs['type'] === 'long_answer' || $qs['type'] === 'short_answer'): ?>
                <div class="space-y-2 pt-1 max-h-56 overflow-y-auto custom-scrollbar">
                    <?php if (empty($qs['comments'])): ?>
                    <p class="text-xs text-slate-400 italic">No written commentaries recorded yet.</p>
                    <?php else: ?>
                    <?php foreach ($qs['comments'] as $c): ?>
                    <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl space-y-1 text-xs">
                        <p class="text-slate-800 font-medium italic">“<?php echo htmlspecialchars($c['commentary']); ?>”</p>
                        <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold pt-0.5">
                            <span><i class="fa-solid fa-user-check text-[#0f53d1]"></i> <?php echo htmlspecialchars($c['citizen_name']); ?> (<?php echo htmlspecialchars($c['barangay']); ?>)</span>
                            <span><?php echo $c['date']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php include '../../includes/footer.php'; ?>
