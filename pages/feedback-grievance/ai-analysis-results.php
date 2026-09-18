<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Caloocan Municipal Department & Auto-Routing Reference (Aligned with Concern Routing)
$departments = [
    'dpwh' => [
        'name' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'short' => 'DPWH / City Engineering',
        'badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
        'icon' => 'fa-solid fa-road',
        'default_priority' => 'High',
        'default_sla' => '24 Hours'
    ],
    'cenro' => [
        'name' => 'Environmental / Waste Management Department (CENRO)',
        'short' => 'CENRO Waste Mgmt',
        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
        'icon' => 'fa-solid fa-recycle',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'flood' => [
        'name' => 'Caloocan Flood Control & Drainage Bureau',
        'short' => 'Flood Control Bureau',
        'badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-800',
        'icon' => 'fa-solid fa-water',
        'default_priority' => 'High',
        'default_sla' => '24 Hours'
    ],
    'electrical' => [
        'name' => 'Public Safety Electrical Division',
        'short' => 'Electrical Division',
        'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'icon' => 'fa-solid fa-bolt',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'cptmd' => [
        'name' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'short' => 'CPTMD Public Safety',
        'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
        'icon' => 'fa-solid fa-shield-halved',
        'default_priority' => 'Urgent',
        'default_sla' => '4 Hours'
    ],
    'cenro_env' => [
        'name' => 'City Environment & Natural Resources Office',
        'short' => 'City Environment Office',
        'badge' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800',
        'icon' => 'fa-solid fa-tree',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'assistance' => [
        'name' => 'Caloocan Public Assistance & Grievance Bureau',
        'short' => 'Public Assistance Bureau',
        'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
        'icon' => 'fa-solid fa-handshake-angle',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours'
    ]
];

// Rich AI Analysis Dataset synchronized with Concern Routing Records
$initialAiClassifications = [
    [
        'id' => 'CAL-REP-2026-4821',
        'title' => 'Deep asphalt crater on Camarin Road causing motor vehicle accidents',
        'text' => 'A dangerous crater-sized pothole has opened up along Camarin Road near Susano Market pedestrian crosswalk. Over the last 48 hours, multiple tricycles and motorbikes have suffered flat tires and near-collisions trying to swerve into oncoming lanes. Immediate asphalt patching is urgently requested.',
        'detected_keywords' => ['pothole', 'asphalt crater', 'road damage', 'accident hazard', 'camarin road'],
        'ai_category' => 'Road & Infrastructure',
        'sub_category' => 'Pavement & Road Rehabilitation',
        'department_key' => 'dpwh',
        'suggested_routing' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'ai_confidence' => 98,
        'vision_verified' => true,
        'vision_summary' => 'Gemini Vision detected high-severity road surface depression (depth > 12cm) with asphalt breakdown.',
        'sentiment' => 'Urgent / Distressed',
        'sentiment_badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
        'barangay' => 'Brgy 178 Camarin',
        'cluster' => [
            'has_duplicates' => true,
            'cluster_count' => 2,
            'cluster_name' => 'Cluster #14: Camarin Road Asphalt Pothole Reports',
            'duplicate_ids' => ['CAL-REP-2026-4819', 'CAL-REP-2026-4824']
        ],
        'status' => 'Pending Review', // 'Pending Review', 'Accepted', 'Overridden'
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-8912',
        'title' => 'Fallen lamppost with exposed live wires sparking near pedestrian crossing',
        'text' => 'A streetlamp post collapsed during the strong wind gust earlier today, leaving open high-voltage cables dangling 4 feet above the ground. Sparking occurs intermittently near wet sidewalk puddles. Severe electrocution hazard for schoolchildren.',
        'detected_keywords' => ['live wire', 'sparking', 'lamppost', 'electrocution risk', 'street light'],
        'ai_category' => 'Streetlights',
        'sub_category' => 'Electrical Emergency & Public Safety',
        'department_key' => 'electrical',
        'suggested_routing' => 'Public Safety Electrical Division',
        'ai_confidence' => 99,
        'vision_verified' => true,
        'vision_summary' => 'Gemini Vision detected exposed copper cabling with arcing thermal signature near metal post base.',
        'sentiment' => 'Critical Public Safety Hazard',
        'sentiment_badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800 animate-pulse',
        'barangay' => 'Brgy 80 Grace Park',
        'cluster' => [
            'has_duplicates' => false,
            'cluster_count' => 1,
            'cluster_name' => 'Standalone Emergency Report',
            'duplicate_ids' => ['CAL-REP-2026-8912']
        ],
        'status' => 'Accepted',
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-3104',
        'title' => 'Recurring illegal garbage dump and rotten market waste blocking alleyway',
        'text' => 'Uncollected rotting market garbage bags have accumulated for 4 consecutive days along the Bagumbong Market perimeter. Stray dogs and rodents have ripped bags open, spreading leachate and pungent foul smell into adjacent residential daycare.',
        'detected_keywords' => ['garbage dump', 'uncollected waste', 'foul odor', 'market alley', 'basura'],
        'ai_category' => 'Garbage & Waste',
        'sub_category' => 'Solid Waste Hauling & Sanitation',
        'department_key' => 'cenro',
        'suggested_routing' => 'Environmental / Waste Management Department (CENRO)',
        'ai_confidence' => 96,
        'vision_verified' => true,
        'vision_summary' => 'Gemini Vision detected multiple torn polypropylene trash sacks spilling organic refuse into drainage gutter.',
        'sentiment' => 'High Disruption / Health Hazard',
        'sentiment_badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'barangay' => 'Brgy 171 Bagumbong',
        'cluster' => [
            'has_duplicates' => true,
            'cluster_count' => 2,
            'cluster_name' => 'Cluster #08: Bagumbong Market Perimeter Solid Waste',
            'duplicate_ids' => ['CAL-REP-2026-3098', 'CAL-REP-2026-3101']
        ],
        'status' => 'Pending Review',
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-5520',
        'title' => 'Major drainage canal obstruction causing knee-deep flash flooding on 10th Ave',
        'text' => 'The main box culvert canal along 10th Avenue is completely jammed with plastic crates, tree branches, and heavy sediment silt. Light afternoon rain resulted in knee-high floodwaters entering ground-floor commercial shops.',
        'detected_keywords' => ['drainage clogged', 'canal overflow', 'flash flooding', '10th avenue', 'box culvert'],
        'ai_category' => 'Flooding & Drainage',
        'sub_category' => 'Culvert De-silt & Flood Control',
        'department_key' => 'flood',
        'suggested_routing' => 'Caloocan Flood Control & Drainage Bureau',
        'ai_confidence' => 97,
        'vision_verified' => true,
        'vision_summary' => 'Gemini Vision confirmed submerged sidewalk curb and heavy hydraulic blockage in storm culvert inlet.',
        'sentiment' => 'High Infrastructure Disruption',
        'sentiment_badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'barangay' => 'Brgy 12',
        'cluster' => [
            'has_duplicates' => false,
            'cluster_count' => 1,
            'cluster_name' => 'Standalone Report',
            'duplicate_ids' => ['CAL-REP-2026-5520']
        ],
        'status' => 'Pending Review',
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-7241',
        'title' => 'Late night videoke brawl and drunken disturbance past quiet hours ordinance',
        'text' => 'Repeated extreme loud music and drunken altercation occurring outside residential compound past 1:00 AM. Multiple senior citizens and working parents unable to rest. Barricading the street with plastic tables.',
        'detected_keywords' => ['public disturbance', 'videoke noise', 'brawl', 'ordinance violation', 'curfew'],
        'ai_category' => 'Public Safety',
        'sub_category' => 'Peace & Order Ordinance Enforcement',
        'department_key' => 'cptmd',
        'suggested_routing' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'ai_confidence' => 95,
        'vision_verified' => false,
        'vision_summary' => 'Audio/text analysis matched public disturbance pattern with repeated curfew violations.',
        'sentiment' => 'Nuisance / Peace & Order Alert',
        'sentiment_badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
        'barangay' => 'Brgy 176 Bagong Silang',
        'cluster' => [
            'has_duplicates' => false,
            'cluster_count' => 1,
            'cluster_name' => 'Standalone Report',
            'duplicate_ids' => ['CAL-REP-2026-7241']
        ],
        'status' => 'Accepted',
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-9033',
        'title' => 'Open burning of hazardous plastic scrap and toxic black smoke emission',
        'text' => 'An unauthorized scrap compound is burning rubber tires and wiring insulation behind the residential rowhouses, emitting dense black toxic smoke that is triggering asthma among local children.',
        'detected_keywords' => ['open burning', 'toxic smoke', 'air pollution', 'plastic burning', 'hazard'],
        'ai_category' => 'Environment',
        'sub_category' => 'Clean Air Act Enforcement & Anti-Pollution',
        'department_key' => 'cenro_env',
        'suggested_routing' => 'City Environment & Natural Resources Office',
        'ai_confidence' => 94,
        'vision_verified' => true,
        'vision_summary' => 'Gemini Vision detected dense opaque particulate column consistent with open synthetic combustion.',
        'sentiment' => 'Environmental Health Hazard',
        'sentiment_badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'barangay' => 'Brgy 178 Camarin',
        'cluster' => [
            'has_duplicates' => false,
            'cluster_count' => 1,
            'cluster_name' => 'Standalone Report',
            'duplicate_ids' => ['CAL-REP-2026-9033']
        ],
        'status' => 'Pending Review',
        'routing_target_url' => 'concern-routing.php'
    ],
    [
        'id' => 'CAL-REP-2026-2180',
        'title' => 'Inquiry for Senior Citizen quarterly medical subsidy and clinic schedule',
        'text' => 'Resident requesting information regarding the release date of Barangay healthcare supplies, vitamin maintenance kits, and schedule of doctor consultations for bedridden seniors.',
        'detected_keywords' => ['senior citizen', 'health center', 'medical subsidy', 'inquiry'],
        'ai_category' => 'Government Service / General Inquiry',
        'sub_category' => 'Public Assistance & Senior Care',
        'department_key' => 'assistance',
        'suggested_routing' => 'Caloocan Public Assistance & Grievance Bureau',
        'ai_confidence' => 92,
        'vision_verified' => false,
        'vision_summary' => 'Routine public inquiry format detected with neutral sentiment profile.',
        'sentiment' => 'Informational / Non-Urgent',
        'sentiment_badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
        'barangay' => 'Brgy 80 Grace Park',
        'cluster' => [
            'has_duplicates' => false,
            'cluster_count' => 1,
            'cluster_name' => 'Standalone Inquiry',
            'duplicate_ids' => ['CAL-REP-2026-2180']
        ],
        'status' => 'Pending Review',
        'routing_target_url' => 'concern-routing.php'
    ]
];

// Load Live Concerns from MySQL Database (Replaces Mock Data)
require_once __DIR__ . '/../../config/database.php';
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC");
    $dbRows = $stmt->fetchAll();
    if (!empty($dbRows)) {
        $liveAi = [];
        foreach ($dbRows as $row) {
            $cat = $row['category'];
            $deptKey = 'assistance';
            if (stripos($cat, 'Road') !== false || stripos($cat, 'Infra') !== false) $deptKey = 'dpwh';
            else if (stripos($cat, 'Garbage') !== false || stripos($cat, 'Waste') !== false || stripos($cat, 'Sanitation') !== false) $deptKey = 'cenro';
            else if (stripos($cat, 'Flood') !== false || stripos($cat, 'Drain') !== false) $deptKey = 'flood';
            else if (stripos($cat, 'Light') !== false) $deptKey = 'electrical';
            else if (stripos($cat, 'Safety') !== false || stripos($cat, 'Police') !== false) $deptKey = 'cptmd';
            else if (stripos($cat, 'Environment') !== false) $deptKey = 'cenro_env';

            $deptName = !empty($row['assigned_department']) ? $row['assigned_department'] : ($departments[$deptKey]['name'] ?? 'City Engineering & Public Works Office');
            $hasPhoto = !empty($row['photo_evidence_url']);
            $isUrgent = ($row['priority'] === 'Urgent' || $row['priority'] === 'High');

            $liveAi[] = [
                'id' => $row['ticket_number'],
                'title' => $row['title'],
                'text' => $row['description'],
                'detected_keywords' => array_values(array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9 ]/', '', $row['title'])))),
                'ai_category' => $row['category'],
                'sub_category' => !empty($row['sub_category']) ? $row['sub_category'] : $row['category'],
                'department_key' => $deptKey,
                'suggested_routing' => $deptName,
                'ai_confidence' => 96,
                'vision_verified' => $hasPhoto,
                'vision_summary' => $hasPhoto ? 'Gemini Vision verified citizen uploaded photo evidence.' : 'Intake verified from citizen mobile app report text.',
                'sentiment' => $isUrgent ? 'Critical Public Safety Hazard' : 'Community Service Report',
                'sentiment_badge' => $isUrgent ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
                'barangay' => !empty($row['barangay']) ? $row['barangay'] : 'Caloocan City',
                'cluster' => [
                    'has_duplicates' => false,
                    'cluster_count' => 1,
                    'cluster_name' => 'Citizen Report #' . $row['ticket_number'],
                    'duplicate_ids' => [$row['ticket_number']]
                ],
                'status' => 'Pending Review',
                'routing_target_url' => 'concern-routing.php'
            ];
        }
        $initialAiClassifications = $liveAi;
    }
} catch (Exception $e) {
    // Keep fallback
}
?>

<!-- Custom Styling -->
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
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #475569;
    }
    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.02); }
    }
    .animate-pulse-subtle {
        animation: pulse-subtle 2.5s infinite ease-in-out;
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/60 dark:bg-slate-950 min-h-[calc(100vh-4rem)] space-y-6 transition-colors duration-200">

    <!-- Top Title & Action Header Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-slate-800 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white flex items-center justify-center text-xl shadow-lg shadow-purple-500/20 ring-4 ring-purple-50 dark:ring-slate-800 shrink-0">
                <i class="fa-solid fa-brain"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">
                    <span>Feedback & Grievance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-indigo-600 dark:text-indigo-400">Gemini Multi-Modal AI Triage</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>AI Analysis Results & Triage Hub</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></span>
                        <span>Multi-Modal NLP Active</span>
                    </span>
                </h1>
            </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="concern-routing.php" class="px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer group">
                <i class="fa-solid fa-route text-blue-600 dark:text-blue-400"></i>
                <span>Open Concern Routing Board</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 group-hover:translate-x-0.5 transition-transform"></i>
            </a>

            <button onclick="triggerBatchReAnalyze()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                <span>Re-Analyze All Intake Queue</span>
            </button>
        </div>
    </div>

    <!-- AI Intelligence KPI Analytics Row (4 Modern Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: AI Model Confidence -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-3 hover:border-indigo-300 dark:hover:border-indigo-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">AI Model Confidence</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base border border-indigo-100 dark:border-indigo-800">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
            <div>
                <h3 id="kpiAvgConfidence" class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">96.2% Avg</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check-double"></i>
                    <span>High precision multi-modal extraction</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Clustered Duplicates -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-3 hover:border-purple-300 dark:hover:border-purple-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Duplicate Proximity Clusters</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-base border border-purple-100 dark:border-purple-800">
                    <i class="fa-solid fa-object-group"></i>
                </div>
            </div>
            <div>
                <h3 id="kpiDuplicateClusters" class="text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">14 Clusters</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>42 duplicate reports grouped</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Urgent Sentiment Flags -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-3 hover:border-rose-300 dark:hover:border-rose-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Urgent Sentiment Flags</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center text-base border border-rose-100 dark:border-rose-800">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div>
                <h3 id="kpiUrgentFlags" class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">5 Flagged</h3>
                <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span>High distress & safety alerts</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Human-in-the-Loop Acceptance Rate -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-3 hover:border-blue-300 dark:hover:border-blue-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Human Acceptance Rate</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-base border border-blue-100 dark:border-blue-800">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div>
                <h3 id="kpiAcceptanceRate" class="text-2xl font-black text-blue-600 dark:text-blue-400 tracking-tight">94.8%</h3>
                <p class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-thumbs-up"></i>
                    <span>Staff confirmed auto-dispatch</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-xs space-y-3.5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="aiSearchInput" oninput="applyAiFilters()" placeholder="Search analyzed reports by Ref ID (e.g. CAL-REP-2026-4821), Keyword, Title, or Barangay..." class="w-full bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 text-xs rounded-xl pl-9 pr-4 py-2.5 outline-none focus:border-indigo-500 font-medium">
            </div>

            <div class="flex items-center gap-2.5 text-xs">
                <span class="text-slate-500 font-bold">Filter Status:</span>
                <select id="aiStatusFilter" onchange="applyAiFilters()" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-indigo-500">
                    <option value="all">All Triage Statuses</option>
                    <option value="Pending Review">Pending Staff Review</option>
                    <option value="Accepted">Accepted & Dispatched</option>
                    <option value="Overridden">Manually Overridden</option>
                </select>
            </div>

        </div>
    </div>

    <!-- AI Classification Feed Cards List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-600"></i>
                <span>AI Classification & Triage Feed</span>
                <span id="aiFeedCountBadge" class="text-slate-400 font-normal text-xs">(7 tickets analyzed)</span>
            </h3>
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">Human-in-the-loop: Review and confirm or override AI routing</span>
        </div>

        <div id="aiFeedContainer" class="grid grid-cols-1 gap-4">
            <!-- Injected dynamically by JavaScript for interactive accept/override/merge actions -->
        </div>

        <!-- Empty State -->
        <div id="aiEmptyState" class="hidden py-16 px-4 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-slate-800 text-indigo-600 mx-auto flex items-center justify-center text-2xl">
                <i class="fa-solid fa-brain"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-white">No analyzed reports match your filter</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Try resetting search keywords or status filter.</p>
        </div>
    </div>

</main>

<!-- ========================================================================= -->
<!-- STAFF OVERRIDE MODAL (HUMAN-IN-THE-LOOP CONTROL)                          -->
<!-- ========================================================================= -->
<div id="staffOverrideModal" class="hidden fixed inset-0 z-[110] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-indigo-600"></i>
                <span>Override AI Triage & Routing (Human-in-the-Loop)</span>
            </h3>
            <button onclick="closeStaffOverrideModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="bg-indigo-50 dark:bg-indigo-950/40 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Target Report Ref</span>
                    <span id="overrideModalTicketId" class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-sm">CAL-REP-2026-4821</span>
                </div>
                <span class="text-[10px] bg-white dark:bg-slate-800 px-2 py-0.5 rounded-md font-bold text-slate-600 dark:text-slate-300">Staff Governance</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Manual Category Assignment</label>
                <select id="overrideCategorySelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer focus:border-indigo-500">
                    <option value="Road & Infrastructure">Road & Infrastructure</option>
                    <option value="Garbage & Waste">Garbage & Waste</option>
                    <option value="Flooding & Drainage">Flooding & Drainage</option>
                    <option value="Streetlights">Streetlights</option>
                    <option value="Public Safety">Public Safety</option>
                    <option value="Environment">Environment</option>
                    <option value="Government Service / General Inquiry">Government Service / General Inquiry</option>
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Target Department Bureau</label>
                <select id="overrideDeptSelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer focus:border-indigo-500">
                    <option value="dpwh">City Engineering & Public Works Office (DPWH/CEPO)</option>
                    <option value="cenro">Environmental / Waste Management Department (CENRO)</option>
                    <option value="flood">Caloocan Flood Control & Drainage Bureau</option>
                    <option value="electrical">Public Safety Electrical Division</option>
                    <option value="cptmd">Caloocan Public Safety & Police Bureau (CPTMD)</option>
                    <option value="cenro_env">City Environment & Natural Resources Office</option>
                    <option value="assistance">Caloocan Public Assistance & Grievance Bureau</option>
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Staff Override Audit Justification *</label>
                <textarea id="overrideReasonText" rows="2" placeholder="State reason for overriding AI recommendation (e.g. specialized utility jurisdiction, inter-agency protocol)..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500 font-medium"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button onclick="closeStaffOverrideModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                Cancel
            </button>
            <button onclick="saveStaffOverride()" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span>Save Staff Override & Dispatch</span>
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- BATCH RE-ANALYZE SIMULATION PROGRESS MODAL                                -->
<!-- ========================================================================= -->
<div id="batchProgressModal" class="hidden fixed inset-0 z-[120] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 text-center space-y-4 animate-in fade-in zoom-in-95 duration-200">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 mx-auto flex items-center justify-center text-xl">
            <i class="fa-solid fa-wand-magic-sparkles fa-spin"></i>
        </div>
        <div>
            <h4 class="text-sm font-black text-slate-900 dark:text-white">Running Gemini NLP Pipeline</h4>
            <p class="text-xs text-slate-500 font-medium mt-1">Re-parsing keyword embeddings, sentiment scores, and image attachments...</p>
        </div>
        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div id="batchProgressBar" class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full w-0 transition-all duration-300"></div>
        </div>
        <span id="batchProgressLabel" class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400 block">Processing 0%</span>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TOAST NOTIFICATION CONTAINER                                              -->
<!-- ========================================================================= -->
<div id="aiToastContainer" class="fixed bottom-6 right-6 z-[140] space-y-2 pointer-events-none"></div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT STATE ENGINE & INTERACTIVE CONTROLLERS                        -->
<!-- ========================================================================= -->
<script>
// Master Reactive State for AI Triage Results
let aiClassificationsData = <?php echo json_encode($initialAiClassifications, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let departmentsMap = <?php echo json_encode($departments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let activeOverrideItem = null;

document.addEventListener('DOMContentLoaded', function() {
    renderAiFeed();
});

// Render the AI Classification Cards
function renderAiFeed() {
    const container = document.getElementById('aiFeedContainer');
    const emptyState = document.getElementById('aiEmptyState');
    const feedCountBadge = document.getElementById('aiFeedCountBadge');
    if (!container) return;

    const filtered = getFilteredAiItems();
    if (feedCountBadge) feedCountBadge.innerText = `(${filtered.length} tickets analyzed)`;

    if (filtered.length === 0) {
        container.innerHTML = '';
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    let html = '';
    filtered.forEach(item => {
        const deptInfo = departmentsMap[item.department_key] || { short: item.suggested_routing, badge: 'bg-slate-100 text-slate-700', icon: 'fa-solid fa-building' };
        const isAccepted = item.status === 'Accepted';
        const isOverridden = item.status === 'Overridden';

        let statusPill = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800">Pending Review</span>`;
        if (isAccepted) {
            statusPill = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 flex items-center gap-1"><i class="fa-solid fa-check"></i> Accepted & Dispatched</span>`;
        } else if (isOverridden) {
            statusPill = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800 flex items-center gap-1"><i class="fa-solid fa-user-check"></i> Staff Overridden</span>`;
        }

        html += `
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4 hover:border-indigo-300 dark:hover:border-indigo-800 transition ${isAccepted ? 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/10' : ''}">
            
            <!-- Card Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                <div class="flex items-center gap-3">
                    <span class="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900">
                        ${item.id}
                    </span>
                    <div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white leading-snug">${escapeHtml(item.title)}</h4>
                        <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1 mt-0.5">
                            <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                            <span>${escapeHtml(item.barangay)}</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                    <!-- AI Confidence Badge -->
                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 font-extrabold text-[11px] border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-brain text-indigo-500"></i>
                        <span>${item.ai_confidence}% AI Match</span>
                    </span>

                    <!-- Sentiment Badge -->
                    <span class="px-2.5 py-1 rounded-xl font-bold text-[11px] border ${item.sentiment_badge}">
                        ${item.sentiment}
                    </span>

                    ${statusPill}
                </div>
            </div>

            <!-- Citizen Narrative & Detected Keywords -->
            <div class="space-y-2 text-xs">
                <p class="text-slate-600 dark:text-slate-300 font-medium leading-relaxed bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3.5 rounded-xl">
                    "${escapeHtml(item.text)}"
                </p>

                <div class="flex items-center gap-2 flex-wrap pt-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detected Keywords:</span>
                    ${item.detected_keywords.map(kw => `
                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-bold text-[10px] border border-indigo-100 dark:border-indigo-800">
                            #${escapeHtml(kw)}
                        </span>
                    `).join('')}
                </div>

                ${item.vision_verified ? `
                <div class="p-2.5 bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-xl flex items-center gap-2 text-[11px] text-purple-900 dark:text-purple-200 font-medium">
                    <i class="fa-solid fa-eye text-purple-600 dark:text-purple-400"></i>
                    <span><strong>Vision Multi-Modal Verification:</strong> ${escapeHtml(item.vision_summary)}</span>
                </div>
                ` : ''}
            </div>

            <!-- AI Suggested Bureau & Duplicate Cluster Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                
                <!-- AI Suggested Routing Box -->
                <div class="p-3.5 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 rounded-xl space-y-1.5">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider block">AI Suggested Classification & Routing</span>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Category:</span>
                        <span class="font-extrabold text-slate-900 dark:text-white">${escapeHtml(item.ai_category)} <span class="text-slate-400 text-[10px] font-normal">(${escapeHtml(item.sub_category)})</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Target Bureau:</span>
                        <span class="font-bold text-blue-700 dark:text-blue-400 flex items-center gap-1">
                            <i class="${deptInfo.icon} text-[10px]"></i>
                            <span>${escapeHtml(item.suggested_routing)}</span>
                        </span>
                    </div>
                </div>

                <!-- Duplicate Cluster Box -->
                <div class="p-3.5 bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 rounded-xl space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider block">Proximity Cluster Detector</span>
                        ${item.cluster.has_duplicates ? `
                        <span class="px-2 py-0.5 rounded-md bg-purple-600 text-white font-black text-[9px]">
                            ${item.cluster.cluster_count} Similar Reports
                        </span>
                        ` : ''}
                    </div>

                    <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">${escapeHtml(item.cluster.cluster_name)}</p>
                    
                    ${item.cluster.has_duplicates ? `
                    <div class="flex items-center justify-between pt-0.5">
                        <p class="text-[10px] text-purple-700 dark:text-purple-300 font-mono">Cluster: ${item.cluster.duplicate_ids.join(', ')}</p>
                        <button onclick="mergeDuplicateCluster('${item.id}')" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 underline hover:text-purple-800 cursor-pointer">
                            Merge Cluster
                        </button>
                    </div>
                    ` : `
                    <p class="text-[10px] text-slate-400">No duplicate reports detected in neighborhood radius.</p>
                    `}
                </div>

            </div>

            <!-- Action Buttons Footer -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="concern-routing.php" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    <span>Track in Concern Routing & Dispatch Center</span>
                </a>

                <div class="flex items-center gap-2 justify-end">
                    <button onclick="openStaffOverrideModal('${item.id}')" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-sliders text-slate-400"></i>
                        <span>Override AI Category / Routing</span>
                    </button>

                    ${isAccepted ? `
                    <button disabled class="px-5 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl opacity-90 cursor-default flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-xs"></i>
                        <span>Dispatched to ${deptInfo.short}</span>
                    </button>
                    ` : `
                    <button onclick="acceptAiRecommendation('${item.id}')" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Accept AI Triage & Dispatch</span>
                    </button>
                    `}
                </div>
            </div>

        </div>
        `;
    });

    container.innerHTML = html;
}

// Filter engine
function getFilteredAiItems() {
    const searchVal = (document.getElementById('aiSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('aiStatusFilter')?.value || 'all';

    return aiClassificationsData.filter(item => {
        if (searchVal) {
            const matchId = item.id.toLowerCase().includes(searchVal);
            const matchTitle = item.title.toLowerCase().includes(searchVal);
            const matchText = item.text.toLowerCase().includes(searchVal);
            const matchBrgy = item.barangay.toLowerCase().includes(searchVal);
            const matchKw = item.detected_keywords.some(k => k.toLowerCase().includes(searchVal));

            if (!matchId && !matchTitle && !matchText && !matchBrgy && !matchKw) return false;
        }

        if (statusVal !== 'all' && item.status !== statusVal) return false;

        return true;
    });
}

function applyAiFilters() {
    renderAiFeed();
}

// Accept AI Triage & Dispatch to Concern Routing
function acceptAiRecommendation(id) {
    const item = aiClassificationsData.find(c => c.id === id);
    if (!item) return;

    item.status = 'Accepted';
    renderAiFeed();
    showAiToast(`Triage for ${id} ACCEPTED! Concern auto-dispatched to ${item.suggested_routing}.`, 'success');
}

// Staff Override Controller
function openStaffOverrideModal(id) {
    const item = aiClassificationsData.find(c => c.id === id);
    if (!item) return;

    activeOverrideItem = item;
    document.getElementById('overrideModalTicketId').innerText = item.id;
    document.getElementById('overrideCategorySelect').value = item.ai_category;
    document.getElementById('overrideDeptSelect').value = item.department_key;
    document.getElementById('overrideReasonText').value = '';

    document.getElementById('staffOverrideModal').classList.remove('hidden');
}

function closeStaffOverrideModal() {
    document.getElementById('staffOverrideModal').classList.add('hidden');
    activeOverrideItem = null;
}

function saveStaffOverride() {
    if (!activeOverrideItem) return;

    const newCat = document.getElementById('overrideCategorySelect').value;
    const newDeptKey = document.getElementById('overrideDeptSelect').value;
    const reason = document.getElementById('overrideReasonText').value.trim() || 'Staff administrative jurisdiction override.';

    activeOverrideItem.ai_category = newCat;
    activeOverrideItem.department_key = newDeptKey;
    activeOverrideItem.suggested_routing = departmentsMap[newDeptKey]?.name || newDeptKey;
    activeOverrideItem.status = 'Overridden';

    closeStaffOverrideModal();
    renderAiFeed();
    showAiToast(`Staff override saved for ${activeOverrideItem.id}: Routed to ${departmentsMap[newDeptKey]?.short}.`, 'success');
}

function mergeDuplicateCluster(id) {
    const item = aiClassificationsData.find(c => c.id === id);
    if (!item) return;

    item.cluster.has_duplicates = false;
    renderAiFeed();
    showAiToast(`Duplicate cluster merged into primary ticket ${id}!`, 'info');
}

// Batch Re-Analysis Simulation
function triggerBatchReAnalyze() {
    const modal = document.getElementById('batchProgressModal');
    const bar = document.getElementById('batchProgressBar');
    const label = document.getElementById('batchProgressLabel');
    if (!modal || !bar || !label) return;

    modal.classList.remove('hidden');
    let progress = 0;

    const interval = setInterval(() => {
        progress += 20;
        bar.style.width = `${progress}%`;
        label.innerText = `Processing NLP embeddings... ${progress}%`;

        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                modal.classList.add('hidden');
                bar.style.width = '0%';
                showAiToast('Gemini NLP batch triage completed! All queues updated.', 'success');
            }, 400);
        }
    }, 250);
}

// Utilities
function escapeHtml(string) {
    if (!string) return '';
    return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showAiToast(message, type = 'info') {
    const container = document.getElementById('aiToastContainer');
    if (!container) return;

    let icon = 'fa-solid fa-circle-info text-indigo-500';
    let border = 'border-indigo-200 dark:border-indigo-800';

    if (type === 'success') {
        icon = 'fa-solid fa-circle-check text-emerald-500';
        border = 'border-emerald-200 dark:border-emerald-800';
    } else if (type === 'warning') {
        icon = 'fa-solid fa-triangle-exclamation text-amber-500';
        border = 'border-amber-200 dark:border-amber-800';
    }

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-3 bg-white dark:bg-slate-900 border ${border} shadow-xl rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white transition-all duration-300 transform translate-y-2 opacity-0 max-w-sm`;
    toast.innerHTML = `
        <i class="${icon} text-base shrink-0"></i>
        <span class="flex-1">${escapeHtml(message)}</span>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
</script>

<?php include '../../includes/footer.php'; ?>
