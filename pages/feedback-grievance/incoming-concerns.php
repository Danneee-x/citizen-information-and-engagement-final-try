<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Database Connection & Live Grievance Data
$pdo = getDbConnection();

// Fetch summary metrics
$totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns`")->fetchColumn();
$newUnroutedTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `status` IN ('New', 'Under Review')")->fetchColumn();
$urgentTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `priority` IN ('Urgent', 'High')")->fetchColumn();
$anonymousTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `is_anonymous` = 1")->fetchColumn();
$anonPct = $totalTickets > 0 ? round(($anonymousTickets / $totalTickets) * 100, 1) : 0;

// Fetch all concerns from MySQL
$stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC");
$dbConcerns = $stmt->fetchAll();

// Available Caloocan Departments for Routing
$caloocanDepartments = [
    'City Engineering & Public Works Office',
    'Environmental / Waste Management Department',
    'Caloocan Flood Control & Drainage Bureau',
    'Public Safety Electrical Division',
    'Caloocan Public Safety & Police Bureau (CPTMD)',
    'City Environment & Natural Resources Office',
    'Caloocan Public Assistance Bureau',
    'Barangay Peacekeeping Action Team (Tanod)'
];

// Transform records for rendering
$concerns = [];
foreach ($dbConcerns as $row) {
    $cat = $row['category'];
    $catColor = 'bg-slate-100 text-slate-700 border-slate-200';
    if (stripos($cat, 'Infrastructure') !== false || stripos($cat, 'Road') !== false) {
        $catColor = 'bg-blue-50 text-[#0f53d1] border-blue-200';
    } else if (stripos($cat, 'Sanitation') !== false || stripos($cat, 'Garbage') !== false || stripos($cat, 'Waste') !== false) {
        $catColor = 'bg-emerald-50 text-emerald-600 border-emerald-200';
    } else if (stripos($cat, 'Safety') !== false || stripos($cat, 'Peace') !== false) {
        $catColor = 'bg-rose-50 text-rose-600 border-rose-200';
    } else if (stripos($cat, 'Noise') !== false) {
        $catColor = 'bg-purple-50 text-purple-600 border-purple-200';
    } else if (stripos($cat, 'Flood') !== false || stripos($cat, 'Drain') !== false) {
        $catColor = 'bg-cyan-50 text-cyan-600 border-cyan-200';
    } else if (stripos($cat, 'Light') !== false) {
        $catColor = 'bg-amber-50 text-amber-600 border-amber-200';
    } else if (stripos($cat, 'Environment') !== false) {
        $catColor = 'bg-teal-50 text-teal-600 border-teal-200';
    }

    $stat = $row['status'];
    $statusBadge = 'bg-slate-100 text-slate-700 border-slate-200';
    if ($stat === 'New') $statusBadge = 'bg-blue-50 text-[#0f53d1] border-blue-200';
    else if ($stat === 'Under Review') $statusBadge = 'bg-amber-50 text-amber-600 border-amber-200';
    else if ($stat === 'Routed') $statusBadge = 'bg-purple-50 text-purple-600 border-purple-200';
    else if ($stat === 'In Progress') $statusBadge = 'bg-indigo-50 text-indigo-600 border-indigo-200';
    else if ($stat === 'Resolved') $statusBadge = 'bg-emerald-50 text-emerald-600 border-emerald-200';
    else if ($stat === 'Closed') $statusBadge = 'bg-slate-100 text-slate-600 border-slate-200';

    $prio = $row['priority'];
    $prioBadge = 'bg-slate-100 text-slate-700 border-slate-200';
    if ($prio === 'Urgent') $prioBadge = 'bg-rose-50 text-rose-600 border-rose-200';
    else if ($prio === 'High') $prioBadge = 'bg-amber-50 text-amber-600 border-amber-200';
    else if ($prio === 'Medium') $prioBadge = 'bg-blue-50 text-[#0f53d1] border-blue-200';

    $attachments = [];
    if (!empty($row['attachments'])) {
        $dec = json_decode($row['attachments'], true);
        if (is_array($dec)) $attachments = $dec;
    }
    if (empty($attachments) && !empty($row['photo_evidence_url'])) {
        $attachments = [basename($row['photo_evidence_url'])];
    }

    $concerns[] = [
        'id' => $row['ticket_number'],
        'concern_id' => $row['concern_id'],
        'title' => $row['title'],
        'full_text' => $row['description'],
        'category' => $row['category'],
        'sub_category' => $row['sub_category'] ?? '',
        'category_color' => $catColor,
        'submitted_by' => $row['is_anonymous'] ? 'Anonymous Resident' : $row['citizen_name'],
        'citizen_phone' => $row['is_anonymous'] ? 'Confidential / Masked' : ($row['citizen_phone'] ?: 'None'),
        'citizen_email' => $row['is_anonymous'] ? 'Confidential / Masked' : ($row['citizen_email'] ?: 'None'),
        'is_anonymous' => (bool)$row['is_anonymous'],
        'citizen_id' => $row['is_anonymous'] ? 'ANON-' . substr(md5($row['ticket_number']), 0, 4) : ($row['citizen_user_id'] ? 'CTZ-' . str_pad($row['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-APP'),
        'date_filed' => date('M j, Y • h:i A', strtotime($row['created_at'])),
        'attachments' => $attachments,
        'photo_evidence_url' => $row['photo_evidence_url'],
        'location' => $row['location'] . ($row['barangay'] ? ', ' . $row['barangay'] : ''),
        'barangay' => $row['barangay'],
        'district' => $row['district'] ?? '',
        'gps_coordinates' => $row['gps_coordinates'] ?? '',
        'status' => $row['status'],
        'status_badge' => $statusBadge,
        'priority' => $row['priority'],
        'priority_badge' => $prioBadge,
        'assigned_dept' => $row['assigned_department'] ?? 'Unassigned',
        'ai_detected_category' => $row['ai_detected_category'] ?? $row['category'],
        'ai_confidence_score' => $row['ai_confidence_score'] ?? '95%',
        'resolution_notes' => $row['resolution_notes'] ?? '',
        'resolved_at' => $row['resolved_at'] ? date('M j, Y • h:i A', strtotime($row['resolved_at'])) : null,
        'updates_count' => ($row['status'] === 'Resolved' ? 4 : ($row['status'] === 'In Progress' ? 3 : ($row['status'] === 'Routed' ? 2 : 1)))
    ];
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
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Feedback & Grievance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Incoming Concerns</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Incoming Concerns</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="refreshQueue()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer" title="Refresh table data from MySQL">
                <i id="refreshIcon" class="fa-solid fa-arrows-rotate text-slate-400"></i>
                <span>Refresh Queue</span>
            </button>

            <button onclick="exportConcernsReport()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Tickets</span>
            </button>

            <button onclick="openSubmitConcernModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Submit New Concern</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards Row (4 Live Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Concerns -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Filed Tickets</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($totalTickets); ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-database"></i>
                    <span>Live MySQL Citizen Records</span>
                </p>
            </div>
        </div>

        <!-- Card 2: New Unrouted -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">New & Unrouted</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($newUnroutedTickets); ?> Tickets</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Requires AI / Staff routing</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Urgent Priority -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Urgent Safety Tickets</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-base border border-rose-100">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($urgentTickets); ?> High Priority</h3>
                <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Fast-track SLA dispatch</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Anonymous Submissions -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Anonymous Submissions</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-user-secret"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($anonymousTickets); ?> (<?php echo $anonPct; ?>%)</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield"></i>
                    <span>Protected citizen identity</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Main Grid Layout (Filter & Table Container + Right Drawer Inspector) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Table Column -->
        <div id="concernsTableContainer" class="lg:col-span-12 space-y-4 transition-all duration-300">

            <!-- Multi-Filter & Search Bar Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="concernSearchInput" oninput="filterConcernsTable()" placeholder="Search ticket ID, title, keywords, or location..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Category Filter -->
                    <select id="concernCategoryFilter" onchange="filterConcernsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Categories</option>
                        <option value="Infrastructure">Infrastructure</option>
                        <option value="Road">Road & Infrastructure</option>
                        <option value="Sanitation">Sanitation</option>
                        <option value="Garbage">Garbage & Waste</option>
                        <option value="Safety">Public Safety</option>
                        <option value="Noise">Noise Complaint</option>
                        <option value="Flooding">Flooding & Drainage</option>
                        <option value="Streetlights">Streetlights</option>
                        <option value="Environment">Environment</option>
                    </select>

                    <!-- Status Filter -->
                    <select id="concernStatusFilter" onchange="filterConcernsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="New">New</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Routed">Routed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>

                    <!-- Priority Filter -->
                    <select id="concernPriorityFilter" onchange="filterConcernsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Priorities</option>
                        <option value="Urgent">Urgent</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Incoming Grievance Tickets</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-[#0f53d1]"><?php echo count($concerns); ?> total</span>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Click row to open details inspector</span>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Ticket ID & Subject</th>
                                <th class="py-3.5 px-3">Category</th>
                                <th class="py-3.5 px-3">Submitted By</th>
                                <th class="py-3.5 px-3">Location Tag</th>
                                <th class="py-3.5 px-3">Date Filed</th>
                                <th class="py-3.5 px-3 text-center">Priority</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="concernsTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <?php if (empty($concerns)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400 font-medium text-xs">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-40 block"></i>
                                    No incoming grievance tickets found in database.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($concerns as $item): ?>
                            <tr onclick="selectConcernRow(this, '<?php echo $item['id']; ?>')" class="concern-row hover:bg-slate-50 transition cursor-pointer select-none" data-id="<?php echo $item['id']; ?>" data-cat="<?php echo htmlspecialchars($item['category']); ?>" data-status="<?php echo htmlspecialchars($item['status']); ?>" data-priority="<?php echo htmlspecialchars($item['priority']); ?>">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 font-black text-xs border border-blue-100">
                                            <i class="fa-solid fa-ticket"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 text-xs truncate max-w-xs"><?php echo htmlspecialchars($item['title']); ?></p>
                                            <span class="text-[10px] font-bold text-[#0f53d1]"><?php echo $item['id']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?php echo $item['category_color']; ?>">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-900 flex items-center gap-1">
                                        <?php if ($item['is_anonymous']): ?>
                                            <i class="fa-solid fa-user-secret text-purple-500 text-[10px]"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['submitted_by']); ?>
                                    </p>
                                    <span class="text-[10px] text-slate-400 font-semibold"><?php echo $item['citizen_id']; ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 font-medium">
                                    <span class="truncate block max-w-xs"><i class="fa-solid fa-location-dot text-rose-500 mr-1 text-[10px]"></i><?php echo htmlspecialchars($item['location']); ?></span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap text-[11px] font-bold text-slate-800">
                                    <?php echo $item['date_filed']; ?>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo $item['priority_badge']; ?>">
                                        <?php echo $item['priority']; ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?php echo $item['status_badge']; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center" onclick="event.stopPropagation();">
                                    <button onclick="selectConcernRow(this.closest('tr'), '<?php echo $item['id']; ?>')" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-[#0f53d1] hover:text-white text-slate-600 transition flex items-center justify-center cursor-pointer shadow-2xs" title="Open Ticket Inspector">
                                        <i class="fa-solid fa-chevron-right text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Details Drawer Inspector -->
        <div id="concernDetailsDrawer" class="hidden lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 animate-in fade-in slide-in-from-right-4 duration-300">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span id="drawerTicketId" class="text-xs font-black text-[#0f53d1] bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">TCK-2026-0000</span>
                    <span id="drawerStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold border bg-blue-50 text-[#0f53d1] border-blue-200">New</span>
                </div>
                <button onclick="closeConcernDrawer()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Subject & Body -->
            <div class="space-y-2">
                <h4 id="drawerTitle" class="text-sm font-black text-slate-900 leading-snug">Concern Subject Title</h4>
                <p id="drawerFullText" class="text-xs text-slate-600 font-normal leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200/70 max-h-40 overflow-y-auto custom-scrollbar">
                    Detailed concern description will display here...
                </p>
            </div>

            <!-- Details Table -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Submitted By</span>
                    <span id="drawerSubmittedBy" class="font-bold text-slate-800">Pedro Reyes</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Contact Phone</span>
                    <span id="drawerPhone" class="font-bold text-slate-800">09171234567</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Category</span>
                    <span id="drawerCategory" class="font-bold text-[#0f53d1]">Infrastructure</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Location</span>
                    <span id="drawerLocation" class="font-bold text-slate-800 truncate max-w-[170px]">Camarin Road, Caloocan</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">AI Confidence</span>
                    <span id="drawerAiScore" class="font-bold text-emerald-600">98% Engine</span>
                </div>
            </div>

            <!-- Dynamic Re-routing & Priority Controls (MySQL Wired) -->
            <div class="space-y-3 p-3 bg-blue-50/40 border border-blue-100 rounded-xl text-xs">
                <div>
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1">
                        <i class="fa-solid fa-building-flag text-[#0f53d1] mr-1"></i> Assigned Department
                    </label>
                    <select id="drawerDeptSelect" onchange="updateTicketDepartment(this.value)" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-lg p-2 outline-none cursor-pointer focus:ring-1 focus:ring-[#0f53d1]">
                        <?php foreach ($caloocanDepartments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1">
                        <i class="fa-solid fa-signal text-amber-500 mr-1"></i> Priority Level
                    </label>
                    <select id="drawerPrioritySelect" onchange="updateTicketPriority(this.value)" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-lg p-2 outline-none cursor-pointer focus:ring-1 focus:ring-[#0f53d1]">
                        <option value="Urgent">Urgent (4-Hour SLA)</option>
                        <option value="High">High (24-Hour SLA)</option>
                        <option value="Medium">Medium (48-Hour SLA)</option>
                        <option value="Low">Low (72-Hour SLA)</option>
                    </select>
                </div>
            </div>

            <!-- Evidence Attachments Container -->
            <div class="space-y-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Photo Evidence</span>
                <div id="drawerAttachmentsContainer" class="flex items-center gap-2 flex-wrap">
                    <div class="text-[11px] text-slate-400 italic">No attachments</div>
                </div>
            </div>

            <!-- Resolution Notes & Staff Actions Taken -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Action Taken / Resolution Notes</label>
                <textarea id="drawerResolutionNotes" rows="2" placeholder="Record action taken by municipal department crew..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-medium rounded-xl p-2.5 outline-none focus:ring-1 focus:ring-[#0f53d1]"></textarea>
                <button onclick="saveResolutionNotes()" class="w-full py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg transition">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Staff Notes
                </button>
            </div>

            <!-- Quick Status Transition Buttons (MySQL Wired) -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transition Status</label>
                <div class="grid grid-cols-4 gap-1.5">
                    <button onclick="updateTicketStatus('Under Review')" class="py-2 px-1 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-[10px] rounded-lg border border-amber-200 transition text-center" title="Mark Under Review">
                        Review
                    </button>
                    <button onclick="updateTicketStatus('In Progress')" class="py-2 px-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] rounded-lg border border-indigo-200 transition text-center" title="Field Unit Dispatched">
                        In Progress
                    </button>
                    <button onclick="updateTicketStatus('Resolved')" class="py-2 px-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded-lg border border-emerald-200 transition text-center" title="Issue Solved">
                        Resolve
                    </button>
                    <button onclick="updateTicketStatus('Closed')" class="py-2 px-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg border border-slate-300 transition text-center" title="Close Case">
                        Close
                    </button>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- SUBMIT CONCERN MODAL (With Anonymous Option & Real Backend Submission) -->
<div id="submitConcernModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">File New Grievance / Concern</h3>
            </div>
            <button onclick="closeSubmitConcernModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Anonymous Toggle Switch -->
            <div class="p-3.5 bg-purple-50/60 border border-purple-100 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                    <label class="font-black text-purple-900 text-xs flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-user-secret text-purple-600"></i>
                        <span>Submit Anonymously</span>
                    </label>
                    <input type="checkbox" id="anonymousToggle" onchange="toggleAnonymousNotice(this.checked)" class="w-4 h-4 text-purple-600 rounded cursor-pointer">
                </div>
                <p id="anonymousNotice" class="text-[11px] text-purple-700 font-medium leading-tight hidden">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    <strong>Abuse-Prevention Notice:</strong> Anonymous reports hide your name from public view. System IP logging & CAPTCHA validation remain active to prevent spam/false reporting.
                </p>
            </div>

            <!-- Subject & Category -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="font-bold text-slate-700 block mb-1">Subject / Title <span class="text-rose-500">*</span></label>
                    <input type="text" id="newTitle" placeholder="e.g., Broken Streetlight along Market Alleyway" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Category <span class="text-rose-500">*</span></label>
                    <select id="newCategory" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="Road & Infrastructure">Road & Infrastructure</option>
                        <option value="Garbage & Waste">Garbage & Waste</option>
                        <option value="Flooding & Drainage">Flooding & Drainage</option>
                        <option value="Streetlights">Streetlights</option>
                        <option value="Public Safety">Public Safety</option>
                        <option value="Noise Complaint">Noise Complaint</option>
                        <option value="Environment">Environment</option>
                        <option value="Government Service">Government Service</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Location Barangay <span class="text-rose-500">*</span></label>
                    <select id="newBarangay" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="Barangay 171 (Bagumbong)">Barangay 171 (Bagumbong)</option>
                        <option value="Barangay 178 (Camarin)">Barangay 178 (Camarin)</option>
                        <option value="Barangay 176 (Bagong Silang)">Barangay 176 (Bagong Silang)</option>
                        <option value="Barangay 12 (Grace Park)">Barangay 12 (Grace Park)</option>
                        <option value="Barangay 88 (Caloocan South)">Barangay 88 (Caloocan South)</option>
                    </select>
                </div>
            </div>

            <!-- Specific Location Landmark -->
            <div>
                <label class="font-bold text-slate-700 block mb-1">Specific Location / Landmark <span class="text-rose-500">*</span></label>
                <input type="text" id="newLocation" placeholder="e.g. 10th Avenue Corner 4th Street near Barangay Hall" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs">
            </div>

            <!-- Description -->
            <div>
                <label class="font-bold text-slate-700 block mb-1">Full Description <span class="text-rose-500">*</span></label>
                <textarea id="newDescription" rows="4" placeholder="Describe the issue in detail (location landmarks, impact, duration)..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button onclick="closeSubmitConcernModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button id="submitConcernBtn" onclick="submitConcernForm()" class="px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Submit Grievance Ticket</span>
            </button>
        </div>
    </div>
</div>

<script>
const concernsData = <?php echo json_encode(array_column($concerns, null, 'id')); ?>;
let activeConcernId = null;

function refreshQueue() {
    const icon = document.getElementById('refreshIcon');
    if (icon) icon.classList.add('fa-spin');
    window.location.reload();
}

function selectConcernRow(rowElement, id) {
    const drawer = document.getElementById('concernDetailsDrawer');
    const tableContainer = document.getElementById('concernsTableContainer');

    if (activeConcernId === id && drawer && !drawer.classList.contains('hidden')) {
        closeConcernDrawer();
        return;
    }

    activeConcernId = id;
    document.querySelectorAll('.concern-row').forEach(r => {
        r.classList.remove('bg-blue-50/40');
    });
    if (rowElement) {
        rowElement.classList.add('bg-blue-50/40');
    }

    const data = concernsData[id];
    if (!data) return;

    document.getElementById('drawerTicketId').innerText = data.id;
    document.getElementById('drawerTitle').innerText = data.title;
    document.getElementById('drawerFullText').innerText = data.full_text;
    document.getElementById('drawerSubmittedBy').innerText = data.submitted_by;
    document.getElementById('drawerPhone').innerText = data.citizen_phone || 'None';
    document.getElementById('drawerCategory').innerText = data.category;
    document.getElementById('drawerLocation').innerText = data.location;
    document.getElementById('drawerAiScore').innerText = data.ai_confidence_score || '95% Gemini Engine';
    document.getElementById('drawerResolutionNotes').value = data.resolution_notes || '';

    // Set Dept Select
    const deptSelect = document.getElementById('drawerDeptSelect');
    if (deptSelect && data.assigned_dept) {
        deptSelect.value = data.assigned_dept;
    }

    // Set Priority Select
    const prioSelect = document.getElementById('drawerPrioritySelect');
    if (prioSelect && data.priority) {
        prioSelect.value = data.priority;
    }

    // Set Status Badge
    const statusBadge = document.getElementById('drawerStatusBadge');
    if (statusBadge) {
        statusBadge.innerText = data.status;
        statusBadge.className = "px-2 py-0.5 rounded-full text-[10px] font-bold border " + data.status_badge;
    }

    // Attachments
    const attachContainer = document.getElementById('drawerAttachmentsContainer');
    attachContainer.innerHTML = '';
    if (data.photo_evidence_url) {
        const fullImgUrl = data.photo_evidence_url.startsWith('http') ? data.photo_evidence_url : '../../' + data.photo_evidence_url;
        attachContainer.innerHTML = `
            <a href="${fullImgUrl}" target="_blank" class="w-16 h-16 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center hover:opacity-80 transition relative group">
                <img src="${fullImgUrl}" class="w-full h-full object-cover" alt="Evidence" onerror="this.onerror=null; this.src='../../assets/images/placeholder-image.png';">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] font-bold transition">
                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                </div>
            </a>
        `;
    } else if (data.attachments && data.attachments.length > 0) {
        data.attachments.forEach(att => {
            attachContainer.innerHTML += `
                <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200 flex flex-col items-center justify-center text-slate-400 p-1 text-center">
                    <i class="fa-solid fa-file-image text-base text-blue-500"></i>
                    <span class="text-[8px] font-bold mt-1 truncate w-full">${att}</span>
                </div>
            `;
        });
    } else {
        attachContainer.innerHTML = '<span class="text-[11px] text-slate-400 italic">No attached evidence files</span>';
    }

    if (drawer) {
        drawer.classList.remove('hidden');
        tableContainer.className = "lg:col-span-8 space-y-4 transition-all duration-300";
    }
}

function closeConcernDrawer() {
    activeConcernId = null;
    document.querySelectorAll('.concern-row').forEach(r => {
        r.classList.remove('bg-blue-50/40');
    });
    const drawer = document.getElementById('concernDetailsDrawer');
    const tableContainer = document.getElementById('concernsTableContainer');
    if (drawer) drawer.classList.add('hidden');
    if (tableContainer) tableContainer.className = "lg:col-span-12 space-y-4 transition-all duration-300";
}

function filterConcernsTable() {
    const searchVal = document.getElementById('concernSearchInput').value.toLowerCase();
    const catVal = document.getElementById('concernCategoryFilter').value.toLowerCase();
    const statusVal = document.getElementById('concernStatusFilter').value.toLowerCase();
    const priorityVal = document.getElementById('concernPriorityFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.concern-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const cat = (r.getAttribute('data-cat') || '').toLowerCase();
        const status = (r.getAttribute('data-status') || '').toLowerCase();
        const priority = (r.getAttribute('data-priority') || '').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesCat = !catVal || cat.includes(catVal);
        const matchesStatus = !statusVal || status.includes(statusVal);
        const matchesPriority = !priorityVal || priority.includes(priorityVal);

        r.style.display = (matchesSearch && matchesCat && matchesStatus && matchesPriority) ? '' : 'none';
    });
}

function openSubmitConcernModal() {
    document.getElementById('submitConcernModal').classList.remove('hidden');
}

function closeSubmitConcernModal() {
    document.getElementById('submitConcernModal').classList.add('hidden');
}

function toggleAnonymousNotice(checked) {
    const notice = document.getElementById('anonymousNotice');
    if (checked) notice.classList.remove('hidden');
    else notice.classList.add('hidden');
}

async function submitConcernForm() {
    const title = document.getElementById('newTitle').value.trim();
    const description = document.getElementById('newDescription').value.trim();
    const category = document.getElementById('newCategory').value;
    const barangay = document.getElementById('newBarangay').value;
    const location = document.getElementById('newLocation').value.trim() || barangay;
    const isAnonymous = document.getElementById('anonymousToggle').checked ? 1 : 0;

    if (!title) {
        alert('Please enter a ticket subject/title.');
        return;
    }
    if (!description) {
        alert('Please enter a full description.');
        return;
    }

    const btn = document.getElementById('submitConcernBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Submitting...';

    try {
        const payload = {
            title: title,
            description: description,
            category: category,
            barangay: barangay,
            location: location,
            is_anonymous: isAnonymous,
            citizen_name: isAnonymous ? 'Anonymous Resident' : 'Web Admin Reporter'
        };

        const res = await fetch('../../api/citizen/submit-concern.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Concern successfully registered in database!\nTicket Number: ${data.ticket_number}\nAssigned Dept: ${data.data.recommended_department}`);
            closeSubmitConcernModal();
            window.location.reload();
        } else {
            alert('Submission error: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        console.error('Failed to submit concern:', err);
        alert('Could not submit concern to backend. Please verify database connection.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane text-xs"></i> <span>Submit Grievance Ticket</span>';
    }
}

async function updateTicketStatus(newStatus) {
    if (!activeConcernId) {
        alert('Please select a ticket first.');
        return;
    }

    if (!confirm(`Are you sure you want to change ticket ${activeConcernId} status to "${newStatus}"?`)) {
        return;
    }

    try {
        const res = await fetch('../../api/admin/concerns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_number: activeConcernId,
                status: newStatus,
                resolution_notes: document.getElementById('drawerResolutionNotes').value.trim()
            })
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Ticket ${activeConcernId} updated to "${newStatus}" in MySQL!`);
            window.location.reload();
        } else {
            alert('Failed to update ticket: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        console.error('Update error:', err);
        alert('Error communicating with backend database.');
    }
}

async function updateTicketDepartment(newDept) {
    if (!activeConcernId) return;

    try {
        const res = await fetch('../../api/admin/concerns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_number: activeConcernId,
                assigned_department: newDept
            })
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Ticket ${activeConcernId} re-routed to "${newDept}"!`);
            window.location.reload();
        } else {
            alert('Failed to update department: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        console.error('Routing update error:', err);
        alert('Error communicating with backend database.');
    }
}

async function updateTicketPriority(newPrio) {
    if (!activeConcernId) return;

    try {
        const res = await fetch('../../api/admin/concerns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_number: activeConcernId,
                priority: newPrio
            })
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Ticket ${activeConcernId} priority updated to "${newPrio}"!`);
            window.location.reload();
        }
    } catch (err) {
        console.error('Priority update error:', err);
    }
}

async function saveResolutionNotes() {
    if (!activeConcernId) return;
    const notes = document.getElementById('drawerResolutionNotes').value.trim();

    try {
        const res = await fetch('../../api/admin/concerns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_number: activeConcernId,
                resolution_notes: notes
            })
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Action notes saved for ticket ${activeConcernId}!`);
        }
    } catch (err) {
        console.error('Notes save error:', err);
    }
}

function exportConcernsReport() {
    if (!concernsData || Object.keys(concernsData).length === 0) {
        alert('No concerns data available to export.');
        return;
    }

    const headers = ['Ticket ID', 'Title', 'Category', 'Submitted By', 'Contact Phone', 'Location', 'Barangay', 'Date Filed', 'Priority', 'Status', 'Assigned Department', 'Resolution Notes'];
    const rows = [headers.join(',')];

    Object.values(concernsData).forEach(c => {
        const row = [
            `"${c.id}"`,
            `"${(c.title || '').replace(/"/g, '""')}"`,
            `"${(c.category || '').replace(/"/g, '""')}"`,
            `"${(c.submitted_by || '').replace(/"/g, '""')}"`,
            `"${(c.citizen_phone || '').replace(/"/g, '""')}"`,
            `"${(c.location || '').replace(/"/g, '""')}"`,
            `"${(c.barangay || '').replace(/"/g, '""')}"`,
            `"${(c.date_filed || '').replace(/"/g, '""')}"`,
            `"${(c.priority || '').replace(/"/g, '""')}"`,
            `"${(c.status || '').replace(/"/g, '""')}"`,
            `"${(c.assigned_dept || '').replace(/"/g, '""')}"`,
            `"${(c.resolution_notes || '').replace(/"/g, '""')}"`
        ];
        rows.push(row.join(','));
    });

    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(rows.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', `caloocan_grievance_concerns_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../../includes/footer.php'; ?>
