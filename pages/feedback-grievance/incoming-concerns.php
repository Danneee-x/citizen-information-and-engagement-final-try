<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Database Connection & Live Grievance Data
$pdo = getDbConnection();

// Ensure required columns exist in citizen_concerns table (thorough auto-migration)
$requiredConcernCols = [
    'citizen_user_id' => 'INT UNSIGNED NULL',
    'citizen_name' => "VARCHAR(150) NOT NULL DEFAULT 'Anonymous Resident'",
    'citizen_phone' => 'VARCHAR(50) NULL',
    'citizen_email' => 'VARCHAR(150) NULL',
    'is_anonymous' => 'TINYINT(1) NOT NULL DEFAULT 0',
    'category' => 'VARCHAR(100) NOT NULL DEFAULT "General"',
    'sub_category' => 'VARCHAR(100) NULL',
    'location' => 'VARCHAR(255) NOT NULL DEFAULT ""',
    'barangay' => 'VARCHAR(100) NOT NULL DEFAULT ""',
    'district' => 'VARCHAR(50) NULL DEFAULT "District 1"',
    'gps_coordinates' => 'VARCHAR(100) NULL',
    'status' => "ENUM('New', 'Under Review', 'Routed', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'New'",
    'priority' => "ENUM('Urgent', 'High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium'",
    'assigned_department' => 'VARCHAR(150) NULL',
    'ai_detected_category' => 'VARCHAR(100) NULL',
    'ai_confidence_score' => 'VARCHAR(100) NULL',
    'photo_evidence_url' => 'MEDIUMTEXT NULL',
    'attachments' => 'TEXT NULL',
    'resolution_notes' => 'TEXT NULL',
    'resolved_at' => 'DATETIME NULL'
];
foreach ($requiredConcernCols as $col => $def) {
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM `citizen_concerns` LIKE '{$col}'")->fetch();
        if (!$chk) {
            $pdo->exec("ALTER TABLE `citizen_concerns` ADD COLUMN `{$col}` {$def}");
        }
    } catch (Throwable $e) {}
}

// Fetch summary metrics
$totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns`")->fetchColumn();
$newUnroutedTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `status` IN ('New', 'Under Review')")->fetchColumn();
$urgentTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `priority` IN ('Urgent', 'High')")->fetchColumn();
$anonymousTickets = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns` WHERE `is_anonymous` = 1")->fetchColumn();
$anonPct = $totalTickets > 0 ? round(($anonymousTickets / $totalTickets) * 100, 1) : 0;

// Dynamic primary key detection (supports either concern_id or id)
$pkCol = 'id';
try {
    $concernCols = $pdo->query("SHOW COLUMNS FROM `citizen_concerns`")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('concern_id', $concernCols)) {
        $pkCol = 'concern_id';
    } elseif (in_array('id', $concernCols)) {
        $pkCol = 'id';
    }
} catch (Throwable $e) {}

// Fetch all concerns from MySQL
$stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `{$pkCol}` DESC");
$dbConcerns = $stmt->fetchAll();

// Available Caloocan Official Municipal Departments (Reference from Department Management)
$caloocanDepartments = [];
try {
    $deptStmt = $pdo->query("SELECT `department_name` FROM `departments` WHERE `status` = 'Active' ORDER BY `department_id` ASC");
    $caloocanDepartments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

if (empty($caloocanDepartments)) {
    $caloocanDepartments = [
        'Public Assets & Facilities Management (PAFM)',
        'Health & Sanitation Management (HSM)',
        'Disaster Risk Reduction & Emergency Response (DRRM)',
        'Transport & Mobility Management (TMM)',
        'Citizenship Information & Engagement (CIE)',
        'Social Services Management (SSM)',
        'Permits & Licensing Management (PLM)',
        'Urban Planning Zoning & Housing (UPZH)',
        'Revenue Collection & Treasury Services (RCTS)',
        'Education & Scholarship (ESMS)',
        'Information Technology Department (IT)'
    ];
}

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
        'id' => $row['ticket_number'] ?? $row['id'] ?? 1,
        'concern_id' => $row['concern_id'] ?? $row['id'] ?? null,
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

    <!-- Main Full-Width Table Layout -->
    <div id="concernsTableContainer" class="w-full space-y-4">

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
                    <span class="text-xs text-slate-400 font-medium">Click row to open details modal</span>
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
                                    <?php if ($item['status'] === 'Routed'): ?>
                                    <span class="block text-[8px] font-extrabold text-purple-600 mt-0.5 whitespace-nowrap"><i class="fa-solid fa-bolt text-[7px]"></i> AI Auto-Routed</span>
                                    <?php elseif ($item['status'] === 'Under Review'): ?>
                                    <span class="block text-[8px] font-bold text-amber-600 mt-0.5 whitespace-nowrap"><i class="fa-solid fa-user-clock text-[7px]"></i> Triage Needed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-3 text-center" onclick="event.stopPropagation();">
                                    <button onclick="selectConcernRow(this.closest('tr'), '<?php echo $item['id']; ?>')" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-[#0f53d1] hover:text-white text-slate-600 transition flex items-center justify-center cursor-pointer shadow-2xs" title="Open Concern Details Modal">
                                        <i class="fa-solid fa-expand text-xs"></i>
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

</main>

<!-- ============================================================================== -->
<!-- CONCERN DETAILS & RESOLUTION MANAGEMENT MODAL                                  -->
<!-- ============================================================================== -->
<div id="concernDetailsModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Light Header with Back/Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeConcernModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Concerns Queue">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Grievance Ticket Details</h3>
                        <span id="modalTicketId" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100">TCK-2026-0000</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Municipal Citizen Feedback & Concern Inspector</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="modalStatusBadge" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 text-[#0f53d1] border border-blue-200">
                    NEW
                </span>
                <button type="button" onclick="closeConcernModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content -->
        <div class="p-6 space-y-5 max-h-[78vh] overflow-y-auto custom-scrollbar">

            <!-- Subject & Description Card -->
            <div class="bg-slate-50/80 rounded-2xl p-4.5 border border-slate-200/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Concern Title & Narrative</span>
                    <span id="modalCategoryBadge" class="px-2 py-0.5 rounded text-[10px] font-bold border bg-blue-50 text-[#0f53d1] border-blue-200">Garbage & Waste</span>
                </div>
                <h4 id="modalTitle" class="text-base font-black text-slate-900 leading-snug">Concern Subject Title</h4>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-xs text-slate-700 font-normal leading-relaxed">
                    <p id="modalFullText" class="whitespace-pre-line">Detailed concern description will display here...</p>
                </div>
            </div>

            <!-- Citizen & Incident Details Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-[#0f53d1]"></i>
                        <span>Incident & Citizen Profile</span>
                    </span>
                    <span id="modalAiScore" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">98% AI Match</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block mb-0.5">Submitted By</span>
                        <div class="flex items-center gap-1.5 font-bold text-slate-900">
                            <i id="modalAnonIcon" class="fa-solid fa-user-secret text-purple-600 hidden text-xs"></i>
                            <span id="modalSubmittedBy">Anonymous Resident</span>
                            <span id="modalCitizenId" class="text-[10px] text-slate-400 font-normal ml-1">(CTZ-APP)</span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block mb-0.5">Contact Number</span>
                        <span id="modalPhone" class="font-bold text-slate-800">Confidential / Masked</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block mb-0.5">Location Tag</span>
                        <span class="font-bold text-slate-800 flex items-center gap-1">
                            <i class="fa-solid fa-location-dot text-rose-500 text-xs"></i>
                            <span id="modalLocation">Camarin Road, Caloocan</span>
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block mb-0.5">Date & Time Filed</span>
                        <span id="modalDateFiled" class="font-bold text-slate-800">Oct 06, 2026 • 02:40 PM</span>
                    </div>
                </div>
            </div>

            <!-- Attached Photo Evidence -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-2.5 shadow-xs">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-camera text-slate-500"></i>
                    <span>Attached Photo Evidence</span>
                </span>
                <div id="modalAttachmentsContainer" class="flex items-center gap-3 flex-wrap pt-1">
                    <span class="text-xs text-slate-400 italic">No attachments</span>
                </div>
            </div>

            <!-- Municipal Routing & Priority SLA Card -->
            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4.5 space-y-3.5 shadow-xs">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-[#0f53d1]"></i>
                    <span>Department Assignment & Priority SLA</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1">
                            <i class="fa-solid fa-building-flag text-[#0f53d1] mr-1"></i> Assigned Municipal Department
                        </label>
                        <select id="modalDeptSelect" onchange="updateTicketDepartment(this.value)" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl p-2.5 outline-none cursor-pointer focus:ring-2 focus:ring-[#0f53d1]/30">
                            <?php foreach ($caloocanDepartments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1">
                            <i class="fa-solid fa-signal text-amber-500 mr-1"></i> Priority Level (SLA)
                        </label>
                        <select id="modalPrioritySelect" onchange="updateTicketPriority(this.value)" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl p-2.5 outline-none cursor-pointer focus:ring-2 focus:ring-[#0f53d1]/30">
                            <option value="Urgent">Urgent (4-Hour SLA Dispatch)</option>
                            <option value="High">High (24-Hour SLA Dispatch)</option>
                            <option value="Medium">Medium (48-Hour SLA Dispatch)</option>
                            <option value="Low">Low (72-Hour SLA Dispatch)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Resolution Notes & Staff Actions Taken -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-2.5 shadow-xs">
                <label class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-indigo-600"></i>
                    <span>Action Taken / Resolution Notes</span>
                </label>
                <textarea id="modalResolutionNotes" rows="3" placeholder="Record official action taken by municipal department, dispatch report, or resolution summary..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-medium rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#0f53d1]/30 focus:bg-white transition"></textarea>
                <button type="button" onclick="saveResolutionNotes()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-slate-500"></i>
                    <span>Save Staff Notes</span>
                </button>
            </div>

            <!-- Quick Status Workflow Transitions -->
            <div class="bg-slate-50/80 p-4.5 rounded-2xl border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Transition Status</span>
                    <span class="text-[10px] text-slate-400">Click to update ticket status in live database</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button type="button" onclick="updateTicketStatus('Under Review')" class="py-2.5 px-2 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs rounded-xl border border-amber-200 transition text-center shadow-xs cursor-pointer flex items-center justify-center gap-1.5" title="Mark Under Review">
                        <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
                        <span>Under Review</span>
                    </button>
                    <button type="button" onclick="updateTicketStatus('In Progress')" class="py-2.5 px-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 transition text-center shadow-xs cursor-pointer flex items-center justify-center gap-1.5" title="Field Unit Dispatched">
                        <i class="fa-solid fa-person-digging text-[11px]"></i>
                        <span>In Progress</span>
                    </button>
                    <button type="button" onclick="updateTicketStatus('Resolved')" class="py-2.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200 transition text-center shadow-xs cursor-pointer flex items-center justify-center gap-1.5" title="Issue Solved">
                        <i class="fa-solid fa-circle-check text-[11px]"></i>
                        <span>Resolved</span>
                    </button>
                    <button type="button" onclick="updateTicketStatus('Closed')" class="py-2.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition text-center shadow-xs cursor-pointer flex items-center justify-center gap-1.5" title="Close Case">
                        <i class="fa-solid fa-lock text-[11px]"></i>
                        <span>Closed</span>
                    </button>
                </div>
            </div>

            <!-- Modal Bottom Navigation (Close Button) -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="closeConcernModal()" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>

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
    const modal = document.getElementById('concernDetailsModal');

    activeConcernId = id;
    document.querySelectorAll('.concern-row').forEach(r => {
        r.classList.remove('bg-blue-50/40');
    });
    if (rowElement) {
        rowElement.classList.add('bg-blue-50/40');
    }

    const data = concernsData[id];
    if (!data) return;

    const ticketIdEl = document.getElementById('modalTicketId');
    if (ticketIdEl) ticketIdEl.innerText = data.id;

    const titleEl = document.getElementById('modalTitle');
    if (titleEl) titleEl.innerText = data.title;

    const fullTextEl = document.getElementById('modalFullText');
    if (fullTextEl) fullTextEl.innerText = data.full_text;

    const submittedByEl = document.getElementById('modalSubmittedBy');
    if (submittedByEl) submittedByEl.innerText = data.submitted_by;

    const citizenIdEl = document.getElementById('modalCitizenId');
    if (citizenIdEl) citizenIdEl.innerText = `(${data.citizen_id || 'CTZ-APP'})`;

    const anonIcon = document.getElementById('modalAnonIcon');
    if (anonIcon) {
        if (data.is_anonymous) anonIcon.classList.remove('hidden');
        else anonIcon.classList.add('hidden');
    }

    const phoneEl = document.getElementById('modalPhone');
    if (phoneEl) phoneEl.innerText = data.citizen_phone || 'None';

    const catBadge = document.getElementById('modalCategoryBadge');
    if (catBadge) {
        catBadge.innerText = data.category;
        catBadge.className = `px-2 py-0.5 rounded text-[10px] font-bold border ${data.category_color || 'bg-blue-50 text-[#0f53d1] border-blue-200'}`;
    }

    const locEl = document.getElementById('modalLocation');
    if (locEl) locEl.innerText = data.location || 'Caloocan City';

    const dateEl = document.getElementById('modalDateFiled');
    if (dateEl) dateEl.innerText = data.date_filed || 'Recently';

    const aiScoreEl = document.getElementById('modalAiScore');
    if (aiScoreEl) aiScoreEl.innerText = data.ai_confidence_score ? `${data.ai_confidence_score} AI Match` : '95% AI Match';

    const notesEl = document.getElementById('modalResolutionNotes');
    if (notesEl) notesEl.value = data.resolution_notes || '';

    // Set Dept Select
    const deptSelect = document.getElementById('modalDeptSelect');
    if (deptSelect && data.assigned_dept) {
        deptSelect.value = data.assigned_dept;
    }

    // Set Priority Select
    const prioSelect = document.getElementById('modalPrioritySelect');
    if (prioSelect && data.priority) {
        prioSelect.value = data.priority;
    }

    // Set Status Badge
    const statusBadge = document.getElementById('modalStatusBadge');
    if (statusBadge) {
        statusBadge.innerText = (data.status || 'NEW').toUpperCase();
        statusBadge.className = "px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border " + data.status_badge;
    }

    // Attachments
    const attachContainer = document.getElementById('modalAttachmentsContainer');
    if (attachContainer) {
        attachContainer.innerHTML = '';
        if (data.photo_evidence_url) {
            let fullImgUrl = data.photo_evidence_url;
            if (fullImgUrl.includes('api-citizen.civentral.tech')) {
                fullImgUrl = fullImgUrl.replace('api-citizen.civentral.tech', window.location.host);
            } else if (!fullImgUrl.startsWith('http://') && !fullImgUrl.startsWith('https://') && !fullImgUrl.startsWith('data:image/')) {
                const clean = fullImgUrl.replace(/^\/+/, '');
                const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                if (isLocal) {
                    fullImgUrl = clean.startsWith('assets/') ? '../../' + clean : '../../assets/' + clean;
                } else {
                    fullImgUrl = clean.startsWith('uploads/') ? '/assets/' + clean : '/' + clean;
                }
            }
            attachContainer.innerHTML = `
                <a href="${fullImgUrl}" target="_blank" class="w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center hover:opacity-85 transition relative group shadow-2xs">
                    <img src="${fullImgUrl}" class="w-full h-full object-cover" alt="Evidence" onerror="this.onerror=null; this.src='../../assets/images/placeholder-image.png';">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </div>
                </a>
            `;
        } else if (data.attachments && data.attachments.length > 0) {
            data.attachments.forEach(att => {
                attachContainer.innerHTML += `
                    <div class="w-20 h-20 rounded-xl bg-slate-50 border border-slate-200 flex flex-col items-center justify-center text-slate-400 p-2 text-center shadow-2xs">
                        <i class="fa-solid fa-file-image text-xl text-blue-500"></i>
                        <span class="text-[9px] font-bold mt-1.5 truncate w-full text-slate-600">${att}</span>
                    </div>
                `;
            });
        } else {
            attachContainer.innerHTML = '<span class="text-xs text-slate-400 italic">No attached evidence files</span>';
        }
    }

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeConcernModal() {
    const modal = document.getElementById('concernDetailsModal');
    if (modal) modal.classList.add('hidden');
    document.querySelectorAll('.concern-row').forEach(r => {
        r.classList.remove('bg-blue-50/40');
    });
}

function closeConcernDrawer() {
    closeConcernModal();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeConcernModal();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('concernDetailsModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeConcernModal();
            }
        });
    }
});

function filterConcernsTable() {
    const searchVal = (document.getElementById('concernSearchInput') ? document.getElementById('concernSearchInput').value : '').toLowerCase();
    const catVal = (document.getElementById('concernCategoryFilter') ? document.getElementById('concernCategoryFilter').value : '').toLowerCase();
    const statusVal = (document.getElementById('concernStatusFilter') ? document.getElementById('concernStatusFilter').value : '').toLowerCase();
    const priorityVal = (document.getElementById('concernPriorityFilter') ? document.getElementById('concernPriorityFilter').value : '').toLowerCase();

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

async function updateTicketStatus(newStatus) {
    if (!activeConcernId) {
        alert('Please select a ticket first.');
        return;
    }

    if (!confirm(`Are you sure you want to change ticket ${activeConcernId} status to "${newStatus}"?`)) {
        return;
    }

    try {
        const notesEl = document.getElementById('modalResolutionNotes') || document.getElementById('drawerResolutionNotes');
        const notes = notesEl ? notesEl.value.trim() : '';

        const res = await fetch('../../api/admin/concerns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_number: activeConcernId,
                status: newStatus,
                resolution_notes: notes
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
    const notesEl = document.getElementById('modalResolutionNotes') || document.getElementById('drawerResolutionNotes');
    const notes = notesEl ? notesEl.value.trim() : '';

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
