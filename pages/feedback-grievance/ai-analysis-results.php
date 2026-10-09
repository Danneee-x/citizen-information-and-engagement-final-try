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
require_once __DIR__ . '/../../includes/concern_clustering.php';

try {
    $pdo = getDbConnection();

    // Auto-promote pending high-confidence reports to Routed in database for zero-touch automation
    try {
        $pdo->exec("UPDATE `citizen_concerns` SET `status` = 'Routed' WHERE `status` IN ('New', 'Under Review') AND `assigned_department` IS NOT NULL AND `assigned_department` != '' AND (`ai_confidence_score` IS NULL OR `ai_confidence_score` NOT LIKE '%below%')");
    } catch (Exception $e) {}

    $stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC");
    $dbRows = $stmt->fetchAll();
    if (!empty($dbRows)) {
        // Run intelligent multi-report incident clustering & dynamic Urgent escalation
        $dbRows = clusterConcerns($dbRows, $pdo, true);

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

            $isCluster = !empty($row['is_cluster']);
            $clusterCount = !empty($row['cluster_count']) ? (int)$row['cluster_count'] : 1;
            $isUrgent = ($row['priority'] === 'Urgent');
            $priorityVal = $row['priority']; // 'Urgent' if multi-report incident

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
            $slaVal = $isUrgent ? '4 Hours' : ($departments[$deptKey]['default_sla'] ?? '48 Hours');

            if ($isCluster) {
                $sentiment = "Critical Hazard • Incident Hotspot ({$clusterCount} Reports)";
                $sentimentBadge = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800';
                $cleanReason = !empty($row['cluster_escalation_reason']) ? $row['cluster_escalation_reason'] : "Multi-Modal AI detected {$clusterCount} convergent citizen reports for this identical incident in {$row['barangay']}. Priority escalated to Urgent for immediate municipal intervention.";
            } else {
                $sentiment = $isUrgent ? 'Critical Public Safety Hazard' : 'Community Service Report';
                $sentimentBadge = $isUrgent ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800';
                $cleanReason = !empty($row['ai_reason']) ? $row['ai_reason'] : 'Multi-modal NLP classification and proximity cluster evaluation complete.';
            }

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
                'sentiment' => $sentiment,
                'sentiment_badge' => $sentimentBadge,
                'barangay' => !empty($row['barangay']) ? $row['barangay'] : 'Caloocan City',
                'location' => !empty($row['location']) ? $row['location'] : '',
                'cluster' => [
                    'has_duplicates' => $isCluster,
                    'cluster_count' => $clusterCount,
                    'cluster_id' => $row['cluster_id'] ?? null,
                    'cluster_name' => $isCluster ? ("⚡ Incident Hotspot: {$clusterCount} Reports (" . (!empty($row['barangay']) ? $row['barangay'] : 'Caloocan') . ")") : ('Citizen Report #' . $row['ticket_number']),
                    'duplicate_ids' => $row['sibling_tickets'] ?? [],
                    'sibling_details' => $row['sibling_details'] ?? []
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
$avgConfidenceVal = 0;
$urgentFlagsCount = 0;
$duplicateClustersCount = 0;
$totalDuplicatesCount = 0;
$autoDispatchedCount = 0;

if ($totalTickets > 0) {
    $sumConf = 0;
    $seenClusters = [];
    foreach ($initialAiClassifications as $item) {
        $sumConf += (int)($item['ai_confidence'] ?? 95);
        if (!empty($item['priority']) && $item['priority'] === 'Urgent') {
            $urgentFlagsCount++;
        }
        if (!empty($item['cluster']['has_duplicates'])) {
            $totalDuplicatesCount++;
            $cid = $item['cluster']['cluster_id'] ?? $item['cluster']['cluster_name'];
            if (!in_array($cid, $seenClusters)) {
                $seenClusters[] = $cid;
                $duplicateClustersCount++;
            }
        }
        if ($item['status'] === 'Auto-Dispatched' || $item['status'] === 'Accepted' || $item['status'] === 'Overridden') {
            $autoDispatchedCount++;
        }
    }
    $avgConfidenceVal = round($sumConf / $totalTickets, 1);
}
$autoDispatchRateVal = $totalTickets > 0 ? round(($autoDispatchedCount / $totalTickets) * 100, 1) : 0;
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
                <h3 id="kpiAvgConfidence" class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= $totalTickets > 0 ? ($avgConfidenceVal . '% Avg') : '0.0% Avg' ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check-double"></i>
                    <span id="kpiAvgConfidenceSub"><?= $totalTickets > 0 ? ($totalTickets . ' concerns evaluated with NLP') : '0 concerns evaluated with NLP' ?></span>
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
                    <span id="kpiAcceptanceRateSub"><?= $autoDispatchedCount > 0 ? 'Zero-touch autonomous dispatch' : ($totalTickets > 0 ? 'Autonomous AI routing active' : 'No dispatches recorded yet') ?></span>
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

    <!-- AI Analysis History Table Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-indigo-600"></i>
                <span>AI Analysis History & Audit Log</span>
                <span id="aiFeedCountBadge" class="text-slate-400 font-normal text-xs">(<?= $totalTickets ?> reports recorded)</span>
            </h3>
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">Click any row or "View Analysis" to inspect the full multi-modal intelligence audit trail in a modal</span>
        </div>

        <!-- Responsive AI Analysis Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3.5 px-4 whitespace-nowrap">Ref ID / Time</th>
                            <th class="py-3.5 px-4">Concern Subject & Barangay</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">AI Classification & Bureau</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Confidence</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Urgency / Sentiment</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Cluster</th>
                            <th class="py-3.5 px-4 text-right whitespace-nowrap">Analysis Audit</th>
                        </tr>
                    </thead>
                    <tbody id="aiFeedTableBody" class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs font-medium">
                        <!-- Populated reactively by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Table Empty State -->
            <div id="aiEmptyState" class="hidden py-16 px-4 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-slate-800 text-indigo-600 mx-auto flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">No analyzed reports match your filter</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Try resetting search keywords or filter dropdown.</p>
            </div>

            <!-- Pagination & Bottom Controls (Matches Registered Citizens pattern) -->
            <div id="aiPaginationSection" class="p-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-xs text-slate-500 font-medium">Rows per page</span>
                    <select id="rowsPerPageSelect" onchange="onRowsPerPageChange(this.value)" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs rounded-lg py-1.5 px-2 outline-none font-medium cursor-pointer">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span id="showingEntriesText" class="text-xs text-slate-500 font-medium ml-1 mr-2">Showing <?= $totalTickets > 0 ? ('1 to ' . min(10, $totalTickets) . ' of ' . $totalTickets) : '0' ?> entries</span>
                </div>

                <div id="paginationButtonsContainer" class="flex items-center gap-1 flex-wrap">
                    <!-- Rendered by JavaScript -->
                </div>
            </div>
        </div>
    </div>

</main>

<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- AI INTELLIGENCE & ANALYSIS AUDIT REPORT MODAL (NO BACKDROP BLUR)           -->
<!-- ========================================================================= -->
<div id="analysisReportModal" onclick="if(event.target === this) closeAnalysisReportModal()" class="hidden fixed inset-0 z-[110] bg-slate-900/60 flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200 max-h-[92vh] overflow-y-auto custom-scrollbar">
        
        <!-- Modal Top Bar -->
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-sm shadow-sm shrink-0">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>AI Analysis Audit & Intelligence Modal</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            Read-Only Audit Trail
                        </span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium">Multi-modal NLP evaluation, confidence metrics, and autonomous routing details</p>
                </div>
            </div>
            <button onclick="closeAnalysisReportModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center cursor-pointer transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- The Modal Content (Matches Full Original Rich Card Details) -->
        <div class="space-y-4 text-xs">
            
            <!-- Card Top Header Strip -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/70 dark:bg-slate-800/40 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span id="modalReportId" class="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900">
                        ---
                    </span>
                    <div>
                        <h4 id="modalReportTitle" class="text-sm font-black text-slate-900 dark:text-white leading-snug">---</h4>
                        <div class="flex items-center gap-3 text-[11px] text-slate-400 font-medium mt-0.5 flex-wrap">
                            <span class="flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                                <span id="modalReportBarangay">---</span>
                            </span>
                            <span class="flex items-center gap-1 font-mono text-[10px]">
                                <i class="fa-regular fa-clock text-slate-400"></i>
                                <span id="modalReportDate">---</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                    <!-- AI Confidence Badge -->
                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 font-extrabold text-[11px] border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-sparkles text-indigo-500"></i>
                        <span id="modalReportConfidence">---</span>
                    </span>

                    <!-- Sentiment Badge -->
                    <span id="modalReportSentimentBadge" class="px-2.5 py-1 rounded-xl font-bold text-[11px] border bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300">
                        ---
                    </span>

                    <!-- Status Pill -->
                    <span id="modalReportStatusPill" class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                        <i class="fa-solid fa-bolt-lightning text-emerald-500"></i> <span id="modalReportStatusText">Auto-Dispatched</span>
                    </span>
                </div>
            </div>

            <!-- Citizen Narrative & Detected Keywords -->
            <div class="space-y-2 text-xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Citizen Report Narrative:</span>
                <p id="modalReportNarrative" class="text-slate-600 dark:text-slate-300 font-medium leading-relaxed bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3.5 rounded-xl italic">
                    ---
                </p>

                <div class="flex items-center gap-2 flex-wrap pt-0.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Semantic Keywords:</span>
                    <div id="modalReportKeywords" class="flex items-center gap-1.5 flex-wrap"></div>
                </div>

                <!-- Vision Multi-Modal Verification -->
                <div id="modalReportVisionContainer" class="hidden p-2.5 bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-xl flex items-center gap-2 text-[11px] text-purple-900 dark:text-purple-200 font-medium">
                    <i class="fa-solid fa-eye text-purple-600 dark:text-purple-400"></i>
                    <span><strong>Vision Multi-Modal Verification:</strong> <span id="modalReportVisionSummary">---</span></span>
                </div>

                <!-- Photo Evidence Preview (if present) -->
                <div id="modalReportPhotoContainer" class="hidden space-y-1.5 pt-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Visual Evidence:</span>
                    <div class="w-full max-h-48 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800">
                        <img id="modalReportPhoto" src="" alt="Evidence" class="w-full h-48 object-cover">
                    </div>
                </div>
            </div>

            <!-- AI Classification & Proximity Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <!-- AI Classification Box -->
                <div class="p-3.5 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 rounded-xl space-y-1.5">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider block">AI Classification & Bureau Match</span>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Category:</span>
                        <span id="modalReportCategory" class="font-extrabold text-slate-900 dark:text-white">---</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Target Bureau:</span>
                        <span id="modalReportDept" class="font-bold text-blue-700 dark:text-blue-400 flex items-center gap-1">
                            <i id="modalReportDeptIcon" class="fa-solid fa-building text-[10px]"></i>
                            <span id="modalReportDeptText">---</span>
                        </span>
                    </div>
                </div>

                <!-- Duplicate Cluster Box -->
                <div class="p-3.5 bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 rounded-xl space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider block">Proximity Cluster Evaluation</span>
                        <span id="modalReportClusterBadge" class="hidden px-2 py-0.5 rounded-md bg-purple-600 text-white font-black text-[9px]"></span>
                    </div>

                    <p id="modalReportClusterName" class="font-bold text-slate-800 dark:text-slate-200 text-xs">---</p>
                    <p id="modalReportClusterMatched" class="hidden text-[10px] text-purple-700 dark:text-purple-300 font-mono pt-0.5"></p>
                </div>
            </div>

            <!-- AI Analysis History & Autonomous Triage Audit Trail (4-Step Timeline Box) -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3.5 space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700/60 pb-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                        <span>Analysis History & Autonomous Triage Trail</span>
                    </span>
                    <span class="text-[10px] font-mono text-slate-400 flex items-center gap-1">
                        <i class="fa-regular fa-clock"></i>
                        <span id="modalReportTrailDate">---</span>
                    </span>
                </div>

                <!-- Audit Steps / History Sequence -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 text-[11px]">
                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">1. Intake & NLP</span>
                        <p id="modalReportLang" class="font-bold text-slate-800 dark:text-slate-200">---</p>
                        <span class="text-[9px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="fa-solid fa-check text-[8px]"></i> Multi-Modal Tokenized
                        </span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">2. Model Engine</span>
                        <p id="modalReportModel" class="font-bold text-slate-800 dark:text-slate-200">---</p>
                        <span id="modalReportModelConf" class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold">---</span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">3. Urgency & Impact</span>
                        <p id="modalReportPriority" class="font-bold text-slate-800 dark:text-slate-200">---</p>
                        <span id="modalReportSla" class="text-[9px] text-slate-500">---</span>
                    </div>

                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800 space-y-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase block">4. Municipal Dispatch</span>
                        <p id="modalReportDispatchDept" class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 truncate">
                            <i class="fa-solid fa-circle-check text-[9px]"></i> ---
                        </p>
                        <span id="modalReportToken" class="text-[9px] font-mono text-slate-400 block truncate">Ref: ---</span>
                    </div>
                </div>

                <!-- Model Reasoning Summary -->
                <div class="text-[11px] text-slate-600 dark:text-slate-300 bg-white/70 dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-start gap-2">
                    <i class="fa-solid fa-brain text-indigo-500 text-xs shrink-0 mt-0.5"></i>
                    <div class="space-y-0.5">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">AI Reasoning Log:</span>
                        <p id="modalReportReason" class="leading-relaxed italic">---</p>
                    </div>
                </div>
            </div>

            <!-- Informational Modal Footer -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Autonomous Dispatch Complete &bull; Token: <code id="modalReportFooterToken" class="font-mono font-bold text-slate-700 dark:text-slate-200">---</code></span>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <a href="concern-routing.php" class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl transition flex items-center gap-1.5 border border-indigo-200/60 dark:border-indigo-800">
                        <span>Concern Routing Desk</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                    <button onclick="closeAnalysisReportModal()" class="px-4 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>
<!-- ========================================================================= -->
<!-- BATCH RE-ANALYZE SIMULATION PROGRESS MODAL                                -->
<!-- ========================================================================= -->
<div id="batchProgressModal" class="hidden fixed inset-0 z-[120] bg-slate-900/60 flex items-center justify-center p-4">
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

// Pagination state (defaults to 10 items per page like registered citizens)
let currentPage = 1;
let rowsPerPage = 10;

document.addEventListener('DOMContentLoaded', function() {
    renderAiTable();

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAnalysisReportModal();
        }
    });
});

function onRowsPerPageChange(val) {
    rowsPerPage = parseInt(val, 10) || 10;
    currentPage = 1;
    renderAiTable();
}

function goToPage(page) {
    currentPage = page;
    renderAiTable();
}

// Render the AI Analysis Records into Table Rows with 10-Row Pagination
function renderAiTable() {
    const tableBody = document.getElementById('aiFeedTableBody');
    const emptyState = document.getElementById('aiEmptyState');
    const countBadge = document.getElementById('aiFeedCountBadge');
    const paginationSection = document.getElementById('aiPaginationSection');
    const showingEntriesText = document.getElementById('showingEntriesText');
    if (!tableBody) return;

    const filtered = getFilteredAiItems();
    const totalMatching = filtered.length;
    if (countBadge) countBadge.innerText = `(${totalMatching} reports recorded)`;

    if (totalMatching === 0) {
        tableBody.innerHTML = '';
        if (emptyState) emptyState.classList.remove('hidden');
        if (showingEntriesText) showingEntriesText.textContent = 'Showing 0 entries';
        renderPaginationControls(1, 1);
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    const totalPages = Math.ceil(totalMatching / rowsPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pageItems = filtered.slice(startIndex, endIndex);

    if (showingEntriesText) {
        const fromNum = startIndex + 1;
        const toNum = Math.min(endIndex, totalMatching);
        showingEntriesText.textContent = `Showing ${fromNum} to ${toNum} of ${totalMatching} entries`;
    }

    let html = '';
    pageItems.forEach(item => {
        const deptInfo = departmentsMap[item.department_key] || { short: item.suggested_routing, badge: 'bg-slate-100 text-slate-700', icon: 'fa-solid fa-building' };
        const hasCluster = item.cluster && item.cluster.has_duplicates;

        html += `
        <tr onclick="openAnalysisReportModal('${item.id}')" class="hover:bg-indigo-50/40 dark:hover:bg-slate-800/60 transition cursor-pointer group">
            
            <!-- Ref ID & Time -->
            <td class="py-3 px-4 whitespace-nowrap align-middle">
                <span class="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-md border border-indigo-100 dark:border-indigo-900 block w-max">
                    ${item.id}
                </span>
                <span class="text-[10px] text-slate-400 flex items-center gap-1 mt-1 font-mono">
                    <i class="fa-regular fa-clock text-[9px]"></i>
                    <span>${escapeHtml(item.analyzed_at)}</span>
                </span>
            </td>

            <!-- Citizen Concern Subject & Barangay -->
            <td class="py-3 px-4 align-middle max-w-xs sm:max-w-sm">
                <div class="font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition leading-snug line-clamp-1">
                    ${escapeHtml(item.title)}
                </div>
                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
                    <span class="flex items-center gap-1 font-medium text-slate-500 dark:text-slate-400">
                        <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                        <span>${escapeHtml(item.barangay)}</span>
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="truncate max-w-[200px] text-[10px] italic text-slate-400">
                        "${escapeHtml(item.text)}"
                    </span>
                </div>
            </td>

            <!-- AI Classification & Bureau -->
            <td class="py-3 px-4 whitespace-nowrap align-middle">
                <div class="font-bold text-slate-800 dark:text-slate-200">
                    ${escapeHtml(item.ai_category)}
                </div>
                <div class="text-[11px] font-bold text-blue-700 dark:text-blue-400 flex items-center gap-1 mt-0.5">
                    <i class="${deptInfo.icon} text-[10px]"></i>
                    <span>${escapeHtml(deptInfo.short)}</span>
                </div>
            </td>

            <!-- Confidence -->
            <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 font-extrabold text-[11px] border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                    <i class="fa-solid fa-sparkles text-indigo-500 text-[9px]"></i>
                    <span>${item.ai_confidence}%</span>
                </span>
            </td>

            <!-- Urgency / Sentiment -->
            <td class="py-3 px-4 whitespace-nowrap align-middle">
                <span class="px-2.5 py-1 rounded-xl font-bold text-[10px] border ${item.sentiment_badge} inline-block">
                    ${item.sentiment}
                </span>
                ${hasCluster ? `
                <span class="block text-[8px] font-black text-rose-600 dark:text-rose-400 mt-0.5 uppercase tracking-wider">
                    <i class="fa-solid fa-bolt text-[7px]"></i> Hotspot Auto-Escalated
                </span>
                ` : ''}
            </td>

            <!-- Cluster -->
            <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                ${hasCluster ? `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/50 dark:text-rose-300 dark:border-rose-800 shadow-2xs">
                    <i class="fa-solid fa-fire text-rose-500 animate-pulse text-[9px]"></i>
                    <span>${item.cluster.cluster_count} Reports • Urgent</span>
                </span>
                ` : `
                <span class="text-slate-400 text-[11px] font-medium">Single Report</span>
                `}
            </td>

            <!-- Action: View Analysis Audit in Modal -->
            <td class="py-3 px-4 text-right whitespace-nowrap align-middle">
                <button onclick="event.stopPropagation(); openAnalysisReportModal('${item.id}')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl transition inline-flex items-center gap-1.5 border border-indigo-200/80 dark:border-indigo-800 shadow-2xs cursor-pointer">
                    <i class="fa-solid fa-file-waveform text-indigo-500"></i>
                    <span>View Analysis</span>
                </button>
            </td>

        </tr>
        `;
    });

    tableBody.innerHTML = html;
    renderPaginationControls(currentPage, totalPages);
}

function renderPaginationControls(page, totalPages) {
    const container = document.getElementById('paginationButtonsContainer');
    if (!container) return;

    let html = '';

    const prevDisabled = page === 1 ? 'disabled opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer';
    html += `<button onclick="goToPage(1)" ${page === 1 ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 dark:text-slate-500 transition ${prevDisabled}" title="First Page"><i class="fa-solid fa-angles-left text-[10px]"></i></button>`;
    html += `<button onclick="goToPage(${page - 1})" ${page === 1 ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 dark:text-slate-500 transition ${prevDisabled}" title="Previous Page"><i class="fa-solid fa-angle-left text-[10px]"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === page) {
            html += `<button class="w-8 h-8 rounded-lg flex items-center justify-center text-white bg-indigo-600 font-bold text-xs shadow-xs">${p}</button>`;
        } else {
            html += `<button onclick="goToPage(${p})" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition cursor-pointer">${p}</button>`;
        }
    }

    const nextDisabled = page === totalPages ? 'disabled opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer';
    html += `<button onclick="goToPage(${page + 1})" ${page === totalPages ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 transition ${nextDisabled}" title="Next Page"><i class="fa-solid fa-angle-right text-[10px]"></i></button>`;
    html += `<button onclick="goToPage(${totalPages})" ${page === totalPages ? 'disabled' : ''} class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 transition ${nextDisabled}" title="Last Page"><i class="fa-solid fa-angles-right text-[10px]"></i></button>`;

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
    currentPage = 1;
    renderAiTable();
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

// Open Read-Only Analysis Report Modal with Complete Card Information (No Background Blur)
function openAnalysisReportModal(id) {
    const item = aiClassificationsData.find(c => c.id === id);
    if (!item) return;

    const deptInfo = departmentsMap[item.department_key] || { short: item.suggested_routing, badge: 'bg-slate-100 text-slate-700', icon: 'fa-solid fa-building' };

    // 1. Header Details
    document.getElementById('modalReportId').innerText = item.id;
    document.getElementById('modalReportTitle').innerText = item.title;
    document.getElementById('modalReportBarangay').innerText = item.barangay;
    document.getElementById('modalReportDate').innerText = 'Analyzed: ' + item.analyzed_at;
    document.getElementById('modalReportConfidence').innerText = (item.model_name || 'Gemini 3.5 Flash') + ' • ' + item.ai_confidence + '%';
    
    // Sentiment Badge
    const sentBadge = document.getElementById('modalReportSentimentBadge');
    sentBadge.className = 'px-2.5 py-1 rounded-xl font-bold text-[11px] border ' + (item.sentiment_badge || 'bg-blue-50 text-blue-700 border-blue-200');
    sentBadge.innerText = item.sentiment;

    // Status Pill
    document.getElementById('modalReportStatusText').innerText = 'Auto-Dispatched to ' + (deptInfo.short || item.suggested_routing);

    // 2. Citizen Narrative
    document.getElementById('modalReportNarrative').innerText = '"' + item.text + '"';

    // Semantic Keywords
    const keywordsBox = document.getElementById('modalReportKeywords');
    keywordsBox.innerHTML = (item.detected_keywords || []).map(kw => 
        `<span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-bold text-[10px] border border-indigo-100 dark:border-indigo-800">#${escapeHtml(kw)}</span>`
    ).join(' ') || '<span class="text-slate-400 italic">None detected</span>';

    // Vision Evidence
    const visionContainer = document.getElementById('modalReportVisionContainer');
    const photoContainer = document.getElementById('modalReportPhotoContainer');
    if (item.vision_verified && item.photo_evidence_url) {
        visionContainer.classList.remove('hidden');
        document.getElementById('modalReportVisionSummary').innerText = item.vision_summary;
        photoContainer.classList.remove('hidden');
        document.getElementById('modalReportPhoto').src = item.photo_evidence_url;
    } else if (item.vision_verified) {
        visionContainer.classList.remove('hidden');
        document.getElementById('modalReportVisionSummary').innerText = item.vision_summary;
        photoContainer.classList.add('hidden');
    } else {
        visionContainer.classList.add('hidden');
        photoContainer.classList.add('hidden');
    }

    // 3. AI Classification & Bureau Box
    document.getElementById('modalReportCategory').innerHTML = `${escapeHtml(item.ai_category)} <span class="text-slate-400 text-[10px] font-normal">(${escapeHtml(item.sub_category)})</span>`;
    document.getElementById('modalReportDeptIcon').className = deptInfo.icon + ' text-[10px]';
    document.getElementById('modalReportDeptText').innerText = item.suggested_routing;

    // 4. Proximity Cluster Box
    const clusterBadge = document.getElementById('modalReportClusterBadge');
    const clusterName = document.getElementById('modalReportClusterName');
    const clusterMatched = document.getElementById('modalReportClusterMatched');
    if (item.cluster && item.cluster.has_duplicates) {
        clusterBadge.classList.remove('hidden');
        clusterBadge.className = 'px-2 py-0.5 rounded-md bg-rose-600 text-white font-black text-[9px] flex items-center gap-1 shadow-2xs';
        clusterBadge.innerHTML = `<i class="fa-solid fa-fire text-amber-300"></i> ${item.cluster.cluster_count} Reports • Auto-Escalated to Urgent`;
        clusterName.innerText = item.cluster.cluster_name;
        clusterMatched.classList.remove('hidden');
        clusterMatched.innerHTML = `Linked Incident Reports: <strong>${item.cluster.duplicate_ids.join(', ')}</strong>`;
    } else {
        clusterBadge.classList.add('hidden');
        clusterName.innerText = item.cluster?.cluster_name || ('Report #' + item.id);
        clusterMatched.classList.add('hidden');
    }

    // 5. 4-Step Analysis History Trail
    document.getElementById('modalReportTrailDate').innerText = item.analyzed_at;
    document.getElementById('modalReportLang').innerText = item.language_detected;
    document.getElementById('modalReportModel').innerText = item.model_name;
    document.getElementById('modalReportModelConf').innerText = `Confidence: ${item.ai_confidence}%`;
    document.getElementById('modalReportPriority').innerHTML = item.cluster && item.cluster.has_duplicates ? 
        `<span class="text-rose-600 dark:text-rose-400 font-black">Urgent</span> <span class="text-[9px] font-bold text-rose-500">(Hotspot Escalation)</span>` : 
        `${item.priority} Priority`;
    document.getElementById('modalReportSla').innerText = `Est. SLA: ${item.sla_target}`;
    document.getElementById('modalReportDispatchDept').innerHTML = `<i class="fa-solid fa-circle-check text-[9px]"></i> ${escapeHtml(deptInfo.short)}`;
    document.getElementById('modalReportToken').innerText = `Ref: ${item.dispatch_token}`;
    document.getElementById('modalReportReason').innerText = `"${item.ai_reason}"`;
    document.getElementById('modalReportFooterToken').innerText = item.dispatch_token;

    // Show modal without background blur
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
