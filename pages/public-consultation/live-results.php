<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Survey Dataset for Live Results
$surveys = [
    'SRV-2025-001' => [
        'id' => 'SRV-2025-001',
        'title' => '2026 Barangay Disaster Resilience & Flood Control Plan',
        'category' => 'Disaster Preparedness',
        'target' => 'All Residents (Caloocan City)',
        'responses' => 3420,
        'target_responses' => 4000,
        'progress_pct' => 85.5,
        'completion_rate' => '94.2%',
        'avg_time' => '2m 34s',
        'top_turnout' => 'Barangay 176 (Bagong Silang)',
        'q1' => [
            'title' => 'What is the most urgent disaster risk in your barangay?',
            'type' => 'Multiple Choice',
            'options' => [
                ['label' => 'Flooding / Canal Blockage', 'pct' => 52, 'count' => '1,778', 'color' => 'bg-[#0f53d1]', 'text_color' => 'text-[#0f53d1]'],
                ['label' => 'Typhoon / Strong Winds', 'pct' => 28, 'count' => '957', 'color' => 'bg-emerald-500', 'text_color' => 'text-emerald-600'],
                ['label' => 'Fire Hazard in Dense Alleyways', 'pct' => 14, 'count' => '478', 'color' => 'bg-amber-500', 'text_color' => 'text-amber-600'],
                ['label' => 'Earthquake Fault Line Risk', 'pct' => 6, 'count' => '207', 'color' => 'bg-purple-500', 'text_color' => 'text-purple-600']
            ]
        ],
        'q2' => [
            'title' => 'How would you rate the current barangay flood response preparedness?',
            'type' => 'Rating Scale (1-5 Stars)',
            'avg_score' => '4.2',
            'stars' => [
                5 => ['count' => 1420, 'pct' => 41.5],
                4 => ['count' => 1250, 'pct' => 36.5],
                3 => ['count' => 510, 'pct' => 14.9],
                2 => ['count' => 180, 'pct' => 5.3],
                1 => ['count' => 60, 'pct' => 1.8]
            ]
        ],
        'q3' => [
            'title' => 'Which emergency equipment and mitigation facilities should the Barangay procure first?',
            'type' => 'Checkbox (Multi-Select)',
            'top_choice' => 'High-Capacity Drainage De-clogging Water Pumps (68% Top Pick)',
            'options' => [
                ['label' => 'High-Capacity Drainage De-clogging Water Pumps', 'pct' => 68, 'count' => '2,325', 'color' => 'bg-emerald-600', 'text_color' => 'text-emerald-600'],
                ['label' => 'Solar-Powered Emergency Warning Sirens', 'pct' => 54, 'count' => '1,846', 'color' => 'bg-[#0f53d1]', 'text_color' => 'text-[#0f53d1]'],
                ['label' => 'Inflatable Rubber Rescue Boats & Life Vests', 'pct' => 42, 'count' => '1,436', 'color' => 'bg-indigo-600', 'text_color' => 'text-indigo-600'],
                ['label' => 'Emergency Food & Medical Relief Stockpiles', 'pct' => 35, 'count' => '1,197', 'color' => 'bg-amber-500', 'text_color' => 'text-amber-600'],
                ['label' => 'Portable Evacuation Center Generators & Solar Stations', 'pct' => 28, 'count' => '957', 'color' => 'bg-purple-600', 'text_color' => 'text-purple-600']
            ]
        ]
    ],
    'SRV-2025-002' => [
        'id' => 'SRV-2025-002',
        'title' => 'Solar Streetlights & Security Lighting Priority Selection',
        'category' => 'Public Safety',
        'target' => 'Purok 1-6 Residents',
        'responses' => 1840,
        'target_responses' => 2000,
        'progress_pct' => 92.0,
        'completion_rate' => '96.8%',
        'avg_time' => '1m 50s',
        'top_turnout' => 'Barangay 178 (Camarin)',
        'q1' => [
            'title' => 'Which location has the highest need for new LED streetlights?',
            'type' => 'Multiple Choice',
            'options' => [
                ['label' => 'Market Alleyways', 'pct' => 58, 'count' => '1,067', 'color' => 'bg-[#0f53d1]', 'text_color' => 'text-[#0f53d1]'],
                ['label' => 'School Perimeter Zones', 'pct' => 27, 'count' => '496', 'color' => 'bg-emerald-500', 'text_color' => 'text-emerald-600'],
                ['label' => 'Main Highway Crosswalks', 'pct' => 15, 'count' => '277', 'color' => 'bg-amber-500', 'text_color' => 'text-amber-600']
            ]
        ],
        'q2' => [
            'title' => 'How urgent is night lighting installation for public safety?',
            'type' => 'Rating Scale (1-5 Stars)',
            'avg_score' => '4.7',
            'stars' => [
                5 => ['count' => 1310, 'pct' => 71.1],
                4 => ['count' => 380, 'pct' => 20.6],
                3 => ['count' => 110, 'pct' => 5.9],
                2 => ['count' => 30, 'pct' => 1.6],
                1 => ['count' => 10, 'pct' => 0.5]
            ]
        ],
        'q3' => [
            'title' => 'Which safety features should be integrated into new streetlight poles?',
            'type' => 'Checkbox (Multi-Select)',
            'top_choice' => 'High-Definition CCTV Security Camera (74% Top Pick)',
            'options' => [
                ['label' => 'High-Definition CCTV Security Camera', 'pct' => 74, 'count' => '1,361', 'color' => 'bg-emerald-600', 'text_color' => 'text-emerald-600'],
                ['label' => 'Emergency Panic Button to Barangay Hall', 'pct' => 61, 'count' => '1,122', 'color' => 'bg-[#0f53d1]', 'text_color' => 'text-[#0f53d1]'],
                ['label' => 'Solar Battery Backup (24hr Duration)', 'pct' => 45, 'count' => '828', 'color' => 'bg-amber-500', 'text_color' => 'text-amber-600']
            ]
        ]
    ]
];

$selectedSurveyId = $_GET['id'] ?? 'SRV-2025-001';
$currentSurvey = $surveys[$selectedSurveyId] ?? $surveys['SRV-2025-001'];
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

    <!-- Top Action & Survey Selector Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Public Consultation & Survey</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Live Results</span>
                </div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Live Results</h1>
                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center gap-1">
                        <i class="fa-solid fa-circle text-[6px] animate-pulse"></i> Live Syncing
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Active Survey Selector Dropdown -->
            <select id="surveySelector" onchange="switchSurveyDataset(this.value)" class="bg-white border border-slate-200 text-slate-800 font-bold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer shadow-xs">
                <?php foreach ($surveys as $sId => $sData): ?>
                <option value="<?php echo $sId; ?>" <?php echo $sId === $selectedSurveyId ? 'selected' : ''; ?>>
                    <?php echo $sId; ?> - <?php echo htmlspecialchars($sData['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <button onclick="triggerLiveRefresh()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i id="refreshIcon" class="fa-solid fa-arrows-rotate text-slate-400 text-xs"></i>
                <span>Refresh</span>
            </button>

            <button onclick="exportLiveReport()" class="px-4 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-xs"></i>
                <span>Export Report (PDF)</span>
            </button>
        </div>
    </div>

    <!-- Live Analytics KPI Metrics Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Response Count & Progress Bar -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Responses</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($currentSurvey['responses']); ?></h3>
                <div class="space-y-1 mt-2">
                    <div class="flex justify-between text-[10px] font-bold text-slate-500">
                        <span>Target: <?php echo number_format($currentSurvey['target_responses']); ?></span>
                        <span class="text-[#0f53d1]"><?php echo $currentSurvey['progress_pct']; ?>%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0f53d1] rounded-full transition-all duration-500" style="width: <?php echo $currentSurvey['progress_pct']; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Completion Rate -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Completion Rate</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $currentSurvey['completion_rate']; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>High survey engagement</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Avg Response Time -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Completion Time</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $currentSurvey['avg_time']; ?></h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Optimal completion speed</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Top Turnout Barangay -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Highest Turnout</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight truncate"><?php echo $currentSurvey['top_turnout']; ?></h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-fire"></i>
                    <span>Top participating area</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Caloocan Barangay & Demographic Response Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-slate-400"></i>
            <span class="font-black text-slate-900 uppercase tracking-wider text-[11px]">Filter Visual Analytics:</span>
        </div>

        <div class="flex items-center gap-3 flex-wrap w-full md:w-auto">
            <select id="barangayFilter" onchange="filterLiveCharts()" class="bg-slate-50 border border-slate-200 text-slate-800 font-bold rounded-xl py-2 px-3 text-xs outline-none cursor-pointer">
                <option value="">All Caloocan Barangays</option>
                <option value="Barangay 178, Camarin">Barangay 178, Camarin</option>
                <option value="Barangay 176, Bagong Silang">Barangay 176, Bagong Silang</option>
                <option value="Barangay 12, Caloocan">Barangay 12, Caloocan</option>
                <option value="Barangay 88, Caloocan">Barangay 88, Caloocan</option>
            </select>

            <select id="demographicFilter" onchange="filterLiveCharts()" class="bg-slate-50 border border-slate-200 text-slate-800 font-bold rounded-xl py-2 px-3 text-xs outline-none cursor-pointer">
                <option value="">All Demographic Groups</option>
                <option value="Senior Citizens">Senior Citizens (60+)</option>
                <option value="Youth">Youth / Students</option>
                <option value="Household Head">Head of Household</option>
            </select>
        </div>
    </div>

    <!-- Visual Charts Grid (Question 1, Question 2 & Question 3 Checkbox Multi-Select) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Question 1: Multiple Choice Bar Chart Card (8 Cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-black text-[#0f53d1] uppercase tracking-wider block">Question 1 &bull; Multiple Choice</span>
                    <h3 class="text-base font-black text-slate-900"><?php echo htmlspecialchars($currentSurvey['q1']['title']); ?></h3>
                </div>
                <span class="px-2.5 py-1 rounded-md bg-blue-50 text-[#0f53d1] font-bold text-xs border border-blue-100">Single Select</span>
            </div>

            <div class="space-y-4">
                <?php foreach ($currentSurvey['q1']['options'] as $opt): ?>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-800"><?php echo htmlspecialchars($opt['label']); ?></span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 font-medium text-[11px]">(<?php echo $opt['count']; ?> votes)</span>
                            <span class="<?php echo $opt['text_color']; ?> font-black"><?php echo $opt['pct']; ?>%</span>
                        </div>
                    </div>
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $opt['color']; ?> rounded-full transition-all duration-500" style="width: <?php echo $opt['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Question 2: 5-Star Rating Distribution Card (4 Cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-black text-amber-600 uppercase tracking-wider block">Question 2 &bull; Rating Scale</span>
                    <h3 class="text-sm font-black text-slate-900 leading-snug"><?php echo htmlspecialchars($currentSurvey['q2']['title']); ?></h3>
                </div>
            </div>

            <div class="p-4 bg-amber-50/60 border border-amber-100 rounded-2xl text-center space-y-1">
                <h2 class="text-3xl font-black text-amber-600 tracking-tight"><?php echo $currentSurvey['q2']['avg_score']; ?> <span class="text-sm font-bold text-amber-500">/ 5.0</span></h2>
                <div class="flex items-center justify-center gap-1 text-amber-400 text-sm">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star-half-stroke"></i>
                </div>
                <p class="text-[10px] font-bold text-amber-700">Overall Rating Score</p>
            </div>

            <div class="space-y-2 text-xs font-medium">
                <?php foreach ($currentSurvey['q2']['stars'] as $star => $sData): ?>
                <div class="flex items-center gap-2">
                    <span class="w-12 font-bold text-slate-700 text-right"><?php echo $star; ?> Stars</span>
                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-400 rounded-full" style="width: <?php echo $sData['pct']; ?>%;"></div>
                    </div>
                    <span class="w-10 text-[11px] font-bold text-slate-500 text-right"><?php echo $sData['pct']; ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- QUESTION 3: CHECKBOX MULTI-SELECT HORIZONTAL PROGRESS BARS -->
        <div class="lg:col-span-12 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-wider block">Question 3 &bull; Checkbox Multi-Select Analysis</span>
                    <h3 class="text-base font-black text-slate-900"><?php echo htmlspecialchars($currentSurvey['q3']['title']); ?></h3>
                </div>
                <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-600 font-bold text-xs border border-emerald-100 flex items-center gap-1">
                    <i class="fa-solid fa-square-check text-xs"></i> Multi-Select Allowed
                </span>
            </div>

            <!-- Top Choice Banner -->
            <div class="p-3.5 bg-emerald-50/60 border border-emerald-100 rounded-xl flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-amber-500 text-base"></i>
                    <div>
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Highest Voted Priority Option</span>
                        <span class="font-black text-slate-900 text-xs"><?php echo $currentSurvey['q3']['top_choice']; ?></span>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-white text-emerald-700 font-bold text-[11px] border border-emerald-200 shadow-xs">Top Priority</span>
            </div>

            <!-- Multi-Select Progress Bars Grid -->
            <div class="space-y-4">
                <?php foreach ($currentSurvey['q3']['options'] as $mOpt): ?>
                <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-900 text-xs flex items-center gap-2">
                            <i class="fa-regular fa-square-check text-emerald-600"></i>
                            <?php echo htmlspecialchars($mOpt['label']); ?>
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 font-medium text-[11px]">(<?php echo $mOpt['count']; ?> votes)</span>
                            <span class="<?php echo $mOpt['text_color']; ?> font-black text-sm"><?php echo $mOpt['pct']; ?>%</span>
                        </div>
                    </div>
                    <div class="w-full h-3.5 bg-slate-200/80 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $mOpt['color']; ?> rounded-full transition-all duration-500" style="width: <?php echo $mOpt['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</main>

<script>
function switchSurveyDataset(surveyId) {
    window.location.href = `live-results.php?id=${surveyId}`;
}

function triggerLiveRefresh() {
    const icon = document.getElementById('refreshIcon');
    if (icon) icon.classList.add('animate-spin');
    setTimeout(() => {
        if (icon) icon.classList.remove('animate-spin');
        alert('Live poll data refreshed with real-time incoming citizen responses!');
    }, 600);
}

function filterLiveCharts() {
    const brgy = document.getElementById('barangayFilter').value;
    const demo = document.getElementById('demographicFilter').value;
    alert(`Filtering Live Results for ${brgy || 'All Barangays'} and ${demo || 'All Demographics'}...`);
}

function exportLiveReport() {
    alert('Exporting Live Poll Analytics Report (PDF)...');
}
</script>

<?php include '../../includes/footer.php'; ?>
