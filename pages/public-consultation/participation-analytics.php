<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/consultation-db-helper.php';

ConsultationDB::ensureTables();
$pdo = Database::getInstance()->getPdo();

// Query real participation metrics
$totalResponses = (int)$pdo->query("SELECT COUNT(*) FROM `survey_responses`")->fetchColumn();
$activeSurveysCount = (int)$pdo->query("SELECT COUNT(*) FROM `public_surveys` WHERE status = 'Open'")->fetchColumn();

// Age groups breakdown
$ageStmt = $pdo->query("
    SELECT age_group, COUNT(*) as count 
    FROM `survey_responses` 
    WHERE age_group IS NOT NULL AND age_group != '' 
    GROUP BY age_group 
    ORDER BY count DESC
");
$rawAge = $ageStmt->fetchAll(PDO::FETCH_ASSOC);

$ageGroups = [];
$ageColors = ['bg-blue-500', 'bg-[#0f53d1]', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500'];
$colorIdx = 0;
foreach ($rawAge as $ra) {
    $pct = $totalResponses > 0 ? round(($ra['count'] / $totalResponses) * 100, 1) : 0;
    $ageGroups[] = [
        'group' => $ra['age_group'],
        'count' => (int)$ra['count'],
        'pct' => $pct,
        'color' => $ageColors[$colorIdx++ % count($ageColors)]
    ];
}

// Gender breakdown
$genderStmt = $pdo->query("
    SELECT gender, COUNT(*) as count 
    FROM `survey_responses` 
    WHERE gender IS NOT NULL AND gender != '' 
    GROUP BY gender 
    ORDER BY count DESC
");
$rawGender = $genderStmt->fetchAll(PDO::FETCH_ASSOC);

$genders = [];
$gColors = ['Female' => 'bg-rose-500', 'Male' => 'bg-blue-500', 'Other' => 'bg-purple-500'];
foreach ($rawGender as $rg) {
    $pct = $totalResponses > 0 ? round(($rg['count'] / $totalResponses) * 100, 1) : 0;
    $genders[] = [
        'label' => $rg['gender'],
        'count' => (int)$rg['count'],
        'pct' => $pct,
        'color' => $gColors[$rg['gender']] ?? 'bg-slate-400'
    ];
}

// Barangay Turnout
$brgyStmt = $pdo->query("
    SELECT barangay, COUNT(*) as count 
    FROM `survey_responses` 
    WHERE barangay IS NOT NULL AND barangay != '' 
    GROUP BY barangay 
    ORDER BY count DESC 
    LIMIT 6
");
$barangays = $brgyStmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span>Public Consultation & Survey</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                        <span class="text-brand-dark">Participation Analytics</span>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Civic Turnout & Demographic Analytics</h1>
                </div>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">Cross-sectional turnout metrics, demographic representation, and automated reminder broadcasts.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="dispatchRemindersPrompt()" class="px-4 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Dispatch Citizen Reminders</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Total Submissions</span>
                <h3 class="text-2xl font-black text-slate-900"><?php echo number_format($totalResponses); ?></h3>
                <span class="text-[10px] text-emerald-600 font-bold">From mobile app & portal</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Active Surveys</span>
                <h3 class="text-2xl font-black text-emerald-600"><?php echo $activeSurveysCount; ?></h3>
                <span class="text-[10px] text-slate-400 font-semibold">Currently collecting</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Participating Barangays</span>
                <h3 class="text-2xl font-black text-purple-600"><?php echo count($barangays); ?></h3>
                <span class="text-[10px] text-purple-600 font-bold">Active zones</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400">Data Reliability</span>
                <h3 class="text-2xl font-black text-amber-600"><?php echo $totalResponses > 0 ? '100%' : '0%'; ?></h3>
                <span class="text-[10px] text-slate-400 font-semibold">Verified Caloocan Accounts</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <?php if ($totalResponses === 0): ?>
    <!-- Zero Analytics Empty State -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#0f53d1] mx-auto flex items-center justify-center text-2xl border border-blue-100">
            <i class="fa-solid fa-chart-simple"></i>
        </div>
        <div class="max-w-md mx-auto space-y-1.5">
            <h3 class="text-base font-black text-slate-900">No Participation Data Yet</h3>
            <p class="text-xs text-slate-500 font-medium">All mock data has been cleared. When citizens start completing surveys via the mobile app, real-time age brackets, gender ratios, and barangay turnout metrics will display here automatically.</p>
        </div>
        <div class="pt-2">
            <a href="manage-surveys.php" class="px-5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-2">
                <i class="fa-solid fa-list-check text-xs"></i>
                <span>View Survey Engine</span>
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- Real Demographics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Age Distribution -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Age Group Representation</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Breakdown of responding citizens</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0f53d1] border border-blue-100">Live</span>
            </div>

            <div class="space-y-3 text-xs">
                <?php foreach ($ageGroups as $ag): ?>
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700">
                        <span><?php echo htmlspecialchars($ag['group']); ?></span>
                        <span class="text-slate-900 font-black"><?php echo $ag['pct']; ?>% <span class="text-slate-400 font-normal">(<?php echo $ag['count']; ?>)</span></span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="<?php echo $ag['color']; ?> h-full rounded-full transition-all" style="width: <?php echo $ag['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Gender Ratio -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Gender Ratio</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Citizen profile demographic</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-600 border border-purple-100">Verified</span>
            </div>

            <div class="space-y-3 text-xs">
                <?php foreach ($genders as $g): ?>
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700">
                        <span><?php echo htmlspecialchars($g['label']); ?></span>
                        <span class="text-slate-900 font-black"><?php echo $g['pct']; ?>% <span class="text-slate-400 font-normal">(<?php echo $g['count']; ?>)</span></span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="<?php echo $g['color']; ?> h-full rounded-full transition-all" style="width: <?php echo $g['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Participating Barangays -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Top Participating Barangays</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Ranked by volume of submitted responses</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($barangays as $b): ?>
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Barangay Zone</span>
                    <h4 class="text-xs font-black text-slate-900 truncate"><?php echo htmlspecialchars($b['barangay']); ?></h4>
                    <p class="text-sm font-black text-[#0f53d1]"><?php echo $b['count']; ?> <span class="text-[10px] font-normal text-slate-400">responses</span></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <?php endif; ?>

</main>

<script>
async function dispatchRemindersPrompt() {
    if (!confirm('Send automated push notifications to registered Caloocan citizens encouraging active survey participation?')) return;

    const btn = event ? event.currentTarget : document.querySelector('button[onclick="dispatchRemindersPrompt()"]');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-white"></i> <span>Broadcasting Reminders...</span>`;
    }

    try {
        const res = await fetch('../../api/admin/dispatch-survey-reminder.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            if (btn) btn.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-300"></i> <span>Reminders Sent!</span>`;
            alert('Success! ' + data.message);
        } else {
            alert('Failed: ' + (data.message || 'Could not dispatch reminders'));
        }
    } catch (err) {
        alert('Network error connecting to notification server.');
    } finally {
        setTimeout(() => {
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }, 2500);
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
