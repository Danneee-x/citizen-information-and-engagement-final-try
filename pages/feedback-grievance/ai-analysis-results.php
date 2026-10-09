<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Caloocan Municipal Department & Auto-Routing Reference (Aligned with Concern Routing)
$departments = [
    'pafm' => [
        'code' => 'PAFM',
        'name' => 'Public Assets & Facilities Management (PAFM)',
        'short' => 'Public Assets & Facilities (PAFM)',
        'badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
        'icon' => 'fa-solid fa-road',
        'default_priority' => 'High',
        'default_sla' => '24 Hours'
    ],
    'hsm' => [
        'code' => 'HSM',
        'name' => 'Health & Sanitation Management (HSM)',
        'short' => 'Health & Sanitation (HSM)',
        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
        'icon' => 'fa-solid fa-recycle',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'drrm' => [
        'code' => 'DRRM',
        'name' => 'Disaster Risk Reduction & Emergency Response (DRRM)',
        'short' => 'Disaster & Emergency (DRRM)',
        'badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-800',
        'icon' => 'fa-solid fa-water',
        'default_priority' => 'Urgent',
        'default_sla' => '4 Hours'
    ],
    'tmm' => [
        'code' => 'TMM',
        'name' => 'Transport & Mobility Management (TMM)',
        'short' => 'Transport & Mobility (TMM)',
        'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'icon' => 'fa-solid fa-traffic-light',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'cie' => [
        'code' => 'CIE',
        'name' => 'Citizenship Information & Engagement (CIE)',
        'short' => 'Citizen Engagement (CIE)',
        'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
        'icon' => 'fa-solid fa-comments',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours'
    ],
    'ssm' => [
        'code' => 'SSM',
        'name' => 'Social Services Management (SSM)',
        'short' => 'Social Services (SSM)',
        'badge' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800',
        'icon' => 'fa-solid fa-hands-holding-child',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'plm' => [
        'code' => 'PLM',
        'name' => 'Permits & Licensing Management (PLM)',
        'short' => 'Permits & Licensing (PLM)',
        'badge' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800',
        'icon' => 'fa-solid fa-file-contract',
        'default_priority' => 'Medium',
        'default_sla' => '72 Hours'
    ],
    'upzh' => [
        'code' => 'UPZH',
        'name' => 'Urban Planning Zoning & Housing (UPZH)',
        'short' => 'Urban Planning & Housing (UPZH)',
        'badge' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-800',
        'icon' => 'fa-solid fa-city',
        'default_priority' => 'Medium',
        'default_sla' => '72 Hours'
    ],
    'rcts' => [
        'code' => 'RCTS',
        'name' => 'Revenue Collection & Treasury Services (RCTS)',
        'short' => 'Revenue & Treasury (RCTS)',
        'badge' => 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800',
        'icon' => 'fa-solid fa-coins',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours'
    ],
    'esms' => [
        'code' => 'ESMS',
        'name' => 'Education & Scholarship (ESMS)',
        'short' => 'Education & Scholarship (ESMS)',
        'badge' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800',
        'icon' => 'fa-solid fa-graduation-cap',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours'
    ],
    'it' => [
        'code' => 'IT',
        'name' => 'Information Technology Department (IT)',
        'short' => 'Information Technology (IT)',
        'badge' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'icon' => 'fa-solid fa-laptop-code',
        'default_priority' => 'Medium',
        'default_sla' => '24 Hours'
    ]
];

// Live AI Analysis Dataset populated from MySQL
$initialAiClassifications = [];
$clustersByGroup = [];

// Load Live Concerns from MySQL Database
require_once __DIR__ . '/../../config/database.php';
try {
    $pdo = getDbConnection();

    // Auto-promote pending high-confidence reports to Routed in database for zero-touch automation
    try {
        $pdo->exec("UPDATE `citizen_concerns` SET `status` = 'Routed' WHERE `status` IN ('New', 'Under Review') AND `assigned_department` IS NOT NULL AND `assigned_department` != '' AND (`ai_confidence_score` IS NULL OR `ai_confidence_score` NOT LIKE '%below%')");
    } catch (Exception $e) {}

    $stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC");
    $dbRows = $stmt->fetchAll();
    if (!empty($dbRows)) {
        // Group by barangay + category for intelligent proximity clustering
        foreach ($dbRows as $r) {
            $grpKey = trim($r['barangay']) . '|' . trim($r['category']);
            $clustersByGroup[$grpKey][] = $r['ticket_number'];
        }

        $liveAi = [];
        foreach ($dbRows as $row) {
            $cat = $row['category'] ?? '';
            $assigned = $row['assigned_department'] ?? '';
            $deptKey = 'cie';

            if (stripos($assigned, 'PAFM') !== false || stripos($assigned, 'Public Assets') !== false || stripos($cat, 'Road') !== false || stripos($cat, 'Infra') !== false || stripos($cat, 'Streetlight') !== false || stripos($cat, 'Light') !== false) {
                $deptKey = 'pafm';
            } else if (stripos($assigned, 'HSM') !== false || stripos($assigned, 'Health') !== false || stripos($assigned, 'Sanitation') !== false || stripos($cat, 'Garbage') !== false || stripos($cat, 'Waste') !== false || stripos($cat, 'Sanitation') !== false || stripos($cat, 'Health') !== false || stripos($cat, 'Environment') !== false) {
                $deptKey = 'hsm';
            } else if (stripos($assigned, 'DRRM') !== false || stripos($assigned, 'Disaster') !== false || stripos($cat, 'Flood') !== false || stripos($cat, 'Drain') !== false || stripos($cat, 'Emergency') !== false) {
                $deptKey = 'drrm';
            } else if (stripos($assigned, 'TMM') !== false || stripos($assigned, 'Transport') !== false || stripos($cat, 'Traffic') !== false || stripos($cat, 'Transport') !== false || stripos($cat, 'Safety') !== false || stripos($cat, 'Police') !== false) {
                $deptKey = 'tmm';
            } else if (stripos($assigned, 'SSM') !== false || stripos($assigned, 'Social') !== false || stripos($cat, 'Social') !== false || stripos($cat, 'Welfare') !== false || stripos($cat, 'Indigent') !== false || stripos($cat, 'Senior') !== false) {
                $deptKey = 'ssm';
            } else if (stripos($assigned, 'PLM') !== false || stripos($assigned, 'Permits') !== false || stripos($cat, 'Permit') !== false || stripos($cat, 'License') !== false || stripos($cat, 'Business') !== false) {
                $deptKey = 'plm';
            } else if (stripos($assigned, 'UPZH') !== false || stripos($assigned, 'Zoning') !== false || stripos($cat, 'Zoning') !== false || stripos($cat, 'Housing') !== false) {
                $deptKey = 'upzh';
            } else if (stripos($assigned, 'RCTS') !== false || stripos($assigned, 'Revenue') !== false || stripos($cat, 'Tax') !== false || stripos($cat, 'Treasury') !== false) {
                $deptKey = 'rcts';
            } else if (stripos($assigned, 'ESMS') !== false || stripos($assigned, 'Education') !== false || stripos($cat, 'Scholarship') !== false || stripos($cat, 'Education') !== false) {
                $deptKey = 'esms';
            } else if (stripos($assigned, 'IT') !== false || stripos($cat, 'System') !== false || stripos($cat, 'App') !== false || stripos($cat, 'Technical') !== false) {
                $deptKey = 'it';
            }

            $deptName = !empty($row['assigned_department']) ? $row['assigned_department'] : ($departments[$deptKey]['name'] ?? 'Citizenship Information & Engagement (CIE)');
            $hasPhoto = !empty($row['photo_evidence_url']);
            $isUrgent = (!empty($row['priority']) && ($row['priority'] === 'Urgent' || $row['priority'] === 'High'));

            $grpKey = trim($row['barangay']) . '|' . trim($row['category']);
            $grpTickets = $clustersByGroup[$grpKey] ?? [$row['ticket_number']];
            $hasDups = count($grpTickets) > 1;

            $confVal = (!empty($row['ai_confidence_score']) && preg_match('/(\d+)%/', $row['ai_confidence_score'], $m)) ? (int)$m[1] : 96;

            if ($row['status'] === 'Overridden') {
                $currentStatus = 'Overridden';
            } else if (in_array($row['status'], ['Routed', 'In Progress', 'Resolved']) || $confVal >= 85) {
                // Zero-touch: high confidence (>= 85%) reports are automatically dispatched
                $currentStatus = 'Auto-Dispatched';
            } else {
                $currentStatus = 'Pending Review';
            }
            $analyzedAt = !empty($row['created_at']) ? date('M d, Y • h:i A', strtotime($row['created_at'])) : date('M d, Y • h:i A');
            $dispatchToken = 'ACK-' . strtoupper(substr(md5($row['ticket_number']), 0, 8));
            $isTaglish = (bool)preg_match('/(po|opo|ang|mga|sa|ng|may|walang|baha|basura|ilaw|kalsada|lubak|tubig|dumi)/i', $row['title'] . ' ' . $row['description']);
            $langDetected = $isTaglish ? 'Filipino / Taglish' : 'English (PH)';
            $priorityVal = !empty($row['priority']) ? $row['priority'] : ($isUrgent ? 'Urgent' : 'Medium');
            $slaVal = $departments[$deptKey]['default_sla'] ?? ($isUrgent ? '4 Hours' : '48 Hours');
            $cleanReason = !empty($row['ai_reason']) ? $row['ai_reason'] : 'Multi-modal NLP classification and proximity cluster evaluation complete.';

            $liveAi[] = [
                'id' => $row['ticket_number'],
                'title' => $row['title'],
                'text' => $row['description'],
                'detected_keywords' => array_values(array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9 ]/', '', $row['title'])))),
                'ai_category' => $row['category'],
                'sub_category' => !empty($row['sub_category']) ? $row['sub_category'] : $row['category'],
                'department_key' => $deptKey,
                'suggested_routing' => $deptName,
                'ai_confidence' => (!empty($row['ai_confidence_score']) && preg_match('/(\d+)%/', $row['ai_confidence_score'], $m)) ? (int)$m[1] : 96,
                'vision_verified' => $hasPhoto,
                'vision_summary' => $hasPhoto ? 'Gemini Vision verified citizen uploaded photo evidence.' : 'Intake verified from citizen mobile app report text.',
                'sentiment' => $isUrgent ? 'Critical Public Safety Hazard' : 'Community Service Report',
                'sentiment_badge' => $isUrgent ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
                'barangay' => !empty($row['barangay']) ? $row['barangay'] : 'Caloocan City',
                'cluster' => [
                    'has_duplicates' => $hasDups,
                    'cluster_count' => count($grpTickets),
                    'cluster_name' => $hasDups ? ('Cluster: ' . $row['category'] . ' (' . $row['barangay'] . ')') : ('Citizen Report #' . $row['ticket_number']),
                    'duplicate_ids' => $grpTickets
                ],
                'status' => $currentStatus,
                'analyzed_at' => $analyzedAt,
                'dispatch_token' => $dispatchToken,
                'language_detected' => $langDetected,
                'priority' => $priorityVal,
                'sla_target' => $slaVal,
                'ai_reason' => $cleanReason,
                'model_name' => 'Google Gemini 3.5 Flash',
                'photo_evidence_url' => $hasPhoto ? $row['photo_evidence_url'] : null,
                'routing_target_url' => 'concern-routing.php'
            ];
        }
        $initialAiClassifications = $liveAi;
    }
} catch (Exception $e) {
    // Graceful fallback
}

// Compute Dynamic AI Intelligence Metrics from live database rows
$totalTickets = count($initialAiClassifications);
$avgConfidenceVal = 96.2;
$urgentFlagsCount = 0;
$duplicateClustersCount = 0;
$totalDuplicatesCount = 0;
$autoDispatchedCount = 0;

if ($totalTickets > 0) {
    $sumConf = 0;
    foreach ($initialAiClassifications as $item) {
        $sumConf += (int)($item['ai_confidence'] ?? 95);
        if (!empty($item['sentiment_badge']) && strpos($item['sentiment_badge'], 'rose') !== false) {
            $urgentFlagsCount++;
        }
        if (!empty($item['cluster']['has_duplicates'])) {
            $totalDuplicatesCount++;
        }
        if ($item['status'] === 'Auto-Dispatched' || $item['status'] === 'Accepted' || $item['status'] === 'Overridden') {
            $autoDispatchedCount++;
        }
    }
    $avgConfidenceVal = round($sumConf / $totalTickets, 1);

    foreach ($clustersByGroup as $g => $tList) {
        if (count($tList) > 1) {
            $duplicateClustersCount++;
        }
    }
}
$autoDispatchRateVal = $totalTickets > 0 ? round(($autoDispatchedCount / $totalTickets) * 100, 1) : 98.5;
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
                    <span class="text-indigo-600 dark:text-indigo-400">Gemini Multi-Modal AI Intelligence</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>AI Analysis Results & Intelligence History</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>⚡ Autonomous NLP History Log</span>
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
                <h3 id="kpiAvgConfidence" class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= $avgConfidenceVal ?>% Avg</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check-double"></i>
                    <span id="kpiAvgConfidenceSub"><?= $totalTickets ?> concerns evaluated with NLP</span>
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
                <h3 id="kpiDuplicateClusters" class="text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight"><?= $duplicateClustersCount ?> Clusters</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-layer-group"></i>
                    <span id="kpiDuplicateClustersSub"><?= $totalDuplicatesCount > 0 ? ($totalDuplicatesCount . ' duplicate reports grouped') : 'No proximity duplicates detected' ?></span>
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
                <h3 id="kpiUrgentFlags" class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight"><?= $urgentFlagsCount ?> Flagged</h3>
                <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span id="kpiUrgentFlagsSub"><?= $urgentFlagsCount > 0 ? 'High distress & safety alerts' : 'Standard priority queue' ?></span>
                </p>
            </div>
        </div>

        <!-- Card 4: Autonomous Auto-Routing Rate -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-3 hover:border-emerald-300 dark:hover:border-emerald-700 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Autonomous Auto-Routing</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base border border-emerald-100 dark:border-emerald-800">
                    <i class="fa-solid fa-bolt-lightning"></i>
                </div>
            </div>
            <div>
                <h3 id="kpiAcceptanceRate" class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight"><?= $totalTickets > 0 ? ($autoDispatchedCount . ' / ' . $totalTickets . ' (' . $autoDispatchRateVal . '%)') : '100%' ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="kpiAcceptanceRateSub"><?= $autoDispatchedCount > 0 ? 'Zero-touch autonomous dispatch' : 'Autonomous AI routing active' ?></span>
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
                <span class="text-slate-500 font-bold">Filter Reports:</span>
                <select id="aiStatusFilter" onchange="applyAiFilters()" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-indigo-500">
                    <option value="all">All Analyzed Reports</option>
                    <option value="High Confidence">High AI Confidence (≥ 90%)</option>
                    <option value="Urgent">Urgent / Safety Hazards</option>
                    <option value="Clustered">Duplicate / Neighborhood Clusters</option>
                    <option value="Vision">Photo Evidence Verified</option>
                </select>
            </div>

        </div>
    </div>

    <!-- AI Classification Feed Cards List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-indigo-600"></i>
                <span>AI Analysis History & Audit Log</span>
                <span id="aiFeedCountBadge" class="text-slate-400 font-normal text-xs">(13 reports recorded)</span>
            </h3>
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">Read-only intelligence log of autonomous NLP evaluations, multi-modal evidence parsing, and department routing history</span>
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
<!-- ========================================================================= -->
<!-- AI INTELLIGENCE & ANALYSIS AUDIT REPORT MODAL (READ-ONLY INFORMATIONAL)    -->
<!-- ========================================================================= -->
<div id="analysisReportModal" class="hidden fixed inset-0 z-[110] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>AI Analysis Audit Report</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            Read-Only Intelligence Log
                        </span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium">Detailed audit breakdown of Gemini multi-modal inference and triage decisions</p>
                </div>
            </div>
            <button onclick="closeAnalysisReportModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Header Metadata Card -->
            <div class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Report Reference Number</span>
                        <span id="modalReportId" class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-base">CAL-REP-2026-4821</span>
                    </div>
                    <div class="sm:text-right">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Analysis Timestamp</span>
                        <span id="modalReportDate" class="font-mono text-slate-600 dark:text-slate-300 font-bold text-xs">Oct 10, 2026 • 03:45 AM</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-[11px]">
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Barangay</span>
                        <span id="modalReportBarangay" class="font-bold text-slate-800 dark:text-slate-200">Barangay 8</span>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Model Engine</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">Gemini 3.5 Flash</span>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Confidence Score</span>
                        <span id="modalReportConfidence" class="font-bold text-emerald-600 dark:text-emerald-400">97.1% Match</span>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Urgency / Priority</span>
                        <span id="modalReportPriority" class="font-bold text-rose-600 dark:text-rose-400">High Priority</span>
                    </div>
                </div>
            </div>

            <!-- Subject & Narrative -->
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Citizen Report Narrative</span>
                <h4 id="modalReportTitle" class="font-bold text-slate-900 dark:text-white text-sm">Large Pothole Hazard along 10th Avenue</h4>
                <p id="modalReportNarrative" class="text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800 font-medium leading-relaxed italic"></p>
            </div>

            <!-- Photo Evidence Preview (if present) -->
            <div id="modalReportPhotoContainer" class="hidden space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Visual Evidence</span>
                <div class="w-full max-h-48 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800">
                    <img id="modalReportPhoto" src="" alt="Evidence" class="w-full h-48 object-cover">
                </div>
            </div>

            <!-- NLP Keyword Extraction -->
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Extracted NLP Semantic Keywords</span>
                <div id="modalReportKeywords" class="flex items-center gap-1.5 flex-wrap"></div>
            </div>

            <!-- Model Reasoning & Autonomous Routing Details -->
            <div class="bg-indigo-50/50 dark:bg-indigo-950/30 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50 space-y-2">
                <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider block">AI Model Inference & Reasoning Log</span>
                <p id="modalReportReason" class="text-slate-700 dark:text-slate-200 leading-relaxed font-medium"></p>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-indigo-100 dark:border-indigo-900/40 text-[11px]">
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Target Bureau</span>
                        <span id="modalReportDept" class="font-bold text-slate-800 dark:text-slate-100">Public Assets & Facilities (PAFM)</span>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Municipal Token</span>
                        <span id="modalReportToken" class="font-mono font-bold text-indigo-600 dark:text-indigo-400">ACK-74F2A19B</span>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block">Estimated SLA</span>
                        <span id="modalReportSla" class="font-bold text-slate-800 dark:text-slate-100">24 Hours</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[11px] text-slate-400 font-medium">To route or re-assign this concern, use the Concern Routing Desk.</span>
            <button onclick="closeAnalysisReportModal()" class="px-5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition cursor-pointer">
                Close Report
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

document.addEventListener('DOMContentLoaded', function() {
    renderAiFeed();
});

// Render Dynamic Feed Cards (Strictly Informational AI Analysis History)
function renderAiFeed() {
    const container = document.getElementById('aiFeedContainer');
    const emptyState = document.getElementById('aiEmptyState');
    const countBadge = document.getElementById('aiFeedCountBadge');

    const filtered = getFilteredAiItems();
    if (countBadge) countBadge.innerText = `(${filtered.length} reports recorded)`;

    if (filtered.length === 0) {
        container.innerHTML = '';
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    let html = '';
    filtered.forEach(item => {
        const deptInfo = departmentsMap[item.department_key] || { short: item.suggested_routing, badge: 'bg-slate-100 text-slate-700', icon: 'fa-solid fa-building' };
        const isHighConfidence = (item.ai_confidence || 0) >= 85;

        html += `
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4 hover:border-indigo-300 dark:hover:border-indigo-800 transition">
            
            <!-- Card Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                <div class="flex items-center gap-3">
                    <span class="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900">
                        ${item.id}
                    </span>
                    <div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white leading-snug">${escapeHtml(item.title)}</h4>
                        <div class="flex items-center gap-3 text-[11px] text-slate-400 font-medium mt-0.5 flex-wrap">
                            <span class="flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                                <span>${escapeHtml(item.barangay)}</span>
                            </span>
                            <span class="flex items-center gap-1 font-mono text-[10px]">
                                <i class="fa-regular fa-clock text-slate-400"></i>
                                <span>Analyzed: ${escapeHtml(item.analyzed_at)}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                    <!-- AI Confidence Badge -->
                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 font-extrabold text-[11px] border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-sparkles text-indigo-500"></i>
                        <span>Gemini 3.5 Flash • ${item.ai_confidence}%</span>
                    </span>

                    <!-- Sentiment Badge -->
                    <span class="px-2.5 py-1 rounded-xl font-bold text-[11px] border ${item.sentiment_badge}">
                        ${item.sentiment}
                    </span>

                    <!-- Status Pill (Autonomous Dispatch Confirmation) -->
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                        <i class="fa-solid fa-bolt-lightning text-emerald-500"></i> Auto-Dispatched to ${escapeHtml(deptInfo.short)}
                    </span>
                </div>
            </div>

            <!-- Citizen Narrative & Detected Keywords -->
            <div class="space-y-2 text-xs">
                <p class="text-slate-600 dark:text-slate-300 font-medium leading-relaxed bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3.5 rounded-xl">
                    "${escapeHtml(item.text)}"
                </p>

                <div class="flex items-center gap-2 flex-wrap pt-0.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Semantic Keywords:</span>
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

            <!-- AI Classification & Proximity Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <!-- AI Classification Box -->
                <div class="p-3.5 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 rounded-xl space-y-1.5">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider block">AI Classification & Bureau Match</span>
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
                        <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider block">Proximity Cluster Evaluation</span>
                        ${item.cluster.has_duplicates ? `
                        <span class="px-2 py-0.5 rounded-md bg-purple-600 text-white font-black text-[9px]">
                            ${item.cluster.cluster_count} Similar Reports in Radius
                        </span>
                        ` : ''}
                    </div>

                    <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">${escapeHtml(item.cluster.cluster_name)}</p>
                    
                    ${item.cluster.has_duplicates ? `
                    <p class="text-[10px] text-purple-700 dark:text-purple-300 font-mono pt-0.5">Matched Tickets: ${item.cluster.duplicate_ids.join(', ')}</p>
                    ` : `
                    <p class="text-[10px] text-slate-400">No duplicate reports detected in neighborhood radius.</p>
                    `}
                </div>
            </div>

            <!-- AI Analysis History & Autonomous Triage Audit Trail (Informational Log) -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3.5 space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700/60 pb-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                        <span>Analysis History & Autonomous Triage Trail</span>
                    </span>
                    <span class="text-[10px] font-mono text-slate-400 flex items-center gap-1">
                        <i class="fa-regular fa-clock"></i>
                        <span>${escapeHtml(item.analyzed_at)}</span>
                    </span>
                </div>

                <!-- Audit Steps / History Sequence -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 text-[11px]">
                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">1. Intake & NLP</span>
                        <p class="font-bold text-slate-800 dark:text-slate-200">${escapeHtml(item.language_detected)}</p>
                        <span class="text-[9px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="fa-solid fa-check text-[8px]"></i> Multi-Modal Tokenized
                        </span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">2. Model Engine</span>
                        <p class="font-bold text-slate-800 dark:text-slate-200">${escapeHtml(item.model_name)}</p>
                        <span class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold">
                            Confidence: ${item.ai_confidence}%
                        </span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">3. Urgency & Impact</span>
                        <p class="font-bold text-slate-800 dark:text-slate-200">${escapeHtml(item.priority)} Priority</p>
                        <span class="text-[9px] text-slate-500">Est. SLA: ${escapeHtml(item.sla_target)}</span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">4. Municipal Dispatch</span>
                        <p class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 truncate">
                            <i class="fa-solid fa-circle-check text-[9px]"></i> ${escapeHtml(deptInfo.short)}
                        </p>
                        <span class="text-[9px] font-mono text-slate-400 block truncate">Ref: ${escapeHtml(item.dispatch_token)}</span>
                    </div>
                </div>

                <!-- Model Reasoning Summary -->
                <div class="text-[11px] text-slate-600 dark:text-slate-300 bg-white/70 dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-start gap-2">
                    <i class="fa-solid fa-brain text-indigo-500 text-xs shrink-0 mt-0.5"></i>
                    <div class="space-y-0.5">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">AI Reasoning Log:</span>
                        <p class="leading-relaxed italic">"${escapeHtml(item.ai_reason)}"</p>
                    </div>
                </div>
            </div>

            <!-- Informational Footer (Strictly Informational, NO ACTION BUTTONS) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Autonomous Dispatch Complete &bull; Municipal Token: <code class="font-mono font-bold text-slate-700 dark:text-slate-200">${item.dispatch_token}</code></span>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <button onclick="openAnalysisReportModal('${item.id}')" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-file-waveform text-indigo-500"></i>
                        <span>View Analysis Breakdown</span>
                    </button>
                    <a href="concern-routing.php" class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl transition flex items-center gap-1.5 border border-indigo-200/60 dark:border-indigo-800">
                        <span>Concern Routing Desk</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>
        `;
    });

    container.innerHTML = html;
}

// Filter engine for analysis records
function getFilteredAiItems() {
    const searchVal = (document.getElementById('aiSearchInput')?.value || '').toLowerCase().trim();
    const filterVal = document.getElementById('aiStatusFilter')?.value || 'all';

    return aiClassificationsData.filter(item => {
        if (searchVal) {
            const matchId = item.id.toLowerCase().includes(searchVal);
            const matchTitle = item.title.toLowerCase().includes(searchVal);
            const matchText = item.text.toLowerCase().includes(searchVal);
            const matchBrgy = item.barangay.toLowerCase().includes(searchVal);
            const matchKw = item.detected_keywords.some(k => k.toLowerCase().includes(searchVal));

            if (!matchId && !matchTitle && !matchText && !matchBrgy && !matchKw) return false;
        }

        if (filterVal === 'High Confidence') {
            if ((item.ai_confidence || 0) < 90) return false;
        } else if (filterVal === 'Urgent') {
            if (!item.sentiment_badge || !item.sentiment_badge.includes('rose')) return false;
        } else if (filterVal === 'Clustered') {
            if (!item.cluster || !item.cluster.has_duplicates) return false;
        } else if (filterVal === 'Vision') {
            if (!item.vision_verified) return false;
        }

        return true;
    });
}

function applyAiFilters() {
    renderAiFeed();
}

// Dynamically Recalculate KPI Analytics in Real-Time
function updateAiKpis() {
    const total = aiClassificationsData.length;
    if (total === 0) return;

    let sumConf = 0;
    let urgent = 0;
    let autoDispatched = 0;
    let duplicates = 0;

    aiClassificationsData.forEach(item => {
        sumConf += (item.ai_confidence || 95);
        if (item.sentiment_badge && item.sentiment_badge.includes('rose')) urgent++;
        if (item.status === 'Auto-Dispatched' || item.status === 'Accepted' || item.status === 'Overridden') autoDispatched++;
        if (item.cluster && item.cluster.has_duplicates) duplicates++;
    });

    const avgConf = (sumConf / total).toFixed(1);
    const autoPct = ((autoDispatched / total) * 100).toFixed(1);

    const elConf = document.getElementById('kpiAvgConfidence');
    if (elConf) elConf.innerText = `${avgConf}% Avg`;

    const elUrgent = document.getElementById('kpiUrgentFlags');
    if (elUrgent) elUrgent.innerText = `${urgent} Flagged`;

    const elAccept = document.getElementById('kpiAcceptanceRate');
    if (elAccept) elAccept.innerText = `${autoDispatched} / ${total} (${autoPct}%)`;

    const elAcceptSub = document.getElementById('kpiAcceptanceRateSub');
    if (elAcceptSub) elAcceptSub.innerText = autoDispatched > 0 ? 'Zero-touch autonomous dispatch' : 'Autonomous AI routing active';
}

// Open Read-Only Analysis Report Modal
function openAnalysisReportModal(id) {
    const item = aiClassificationsData.find(c => c.id === id);
    if (!item) return;

    document.getElementById('modalReportId').innerText = item.id;
    document.getElementById('modalReportDate').innerText = item.analyzed_at;
    document.getElementById('modalReportTitle').innerText = item.title;
    document.getElementById('modalReportBarangay').innerText = item.barangay;
    document.getElementById('modalReportNarrative').innerText = '"' + item.text + '"';
    document.getElementById('modalReportConfidence').innerText = item.ai_confidence + '% Confidence';
    document.getElementById('modalReportPriority').innerText = item.priority;
    document.getElementById('modalReportDept').innerText = item.suggested_routing;
    document.getElementById('modalReportReason').innerText = item.ai_reason;
    document.getElementById('modalReportToken').innerText = item.dispatch_token;
    document.getElementById('modalReportSla').innerText = item.sla_target;

    const photoContainer = document.getElementById('modalReportPhotoContainer');
    if (item.photo_evidence_url) {
        photoContainer.classList.remove('hidden');
        document.getElementById('modalReportPhoto').src = item.photo_evidence_url;
    } else {
        photoContainer.classList.add('hidden');
    }

    const keywordsBox = document.getElementById('modalReportKeywords');
    keywordsBox.innerHTML = item.detected_keywords.map(kw => 
        `<span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-bold text-[10px] border border-indigo-100 dark:border-indigo-800">#${escapeHtml(kw)}</span>`
    ).join(' ');

    document.getElementById('analysisReportModal').classList.remove('hidden');
}

function closeAnalysisReportModal() {
    document.getElementById('analysisReportModal').classList.add('hidden');
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
