<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Category metadata helper
function getCategoryMeta($category) {
    $c = strtolower(trim($category));
    if (strpos($c, 'emergency') !== false) {
        return [
            'label' => 'Emergency',
            'badgeClass' => 'bg-rose-50 text-rose-600 border border-rose-200',
            'iconBg' => 'bg-rose-50 text-rose-500',
            'icon' => 'fa-triangle-exclamation'
        ];
    } elseif (strpos($c, 'health') !== false) {
        return [
            'label' => 'Health Advisory',
            'badgeClass' => 'bg-amber-50 text-amber-600 border border-amber-200',
            'iconBg' => 'bg-amber-50 text-amber-500',
            'icon' => 'fa-heart-pulse'
        ];
    } elseif (strpos($c, 'event') !== false) {
        return [
            'label' => 'Event',
            'badgeClass' => 'bg-purple-50 text-purple-600 border border-purple-200',
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => 'fa-calendar-days'
        ];
    } elseif (strpos($c, 'curfew') !== false || strpos($c, 'ordinance') !== false) {
        return [
            'label' => 'Curfew / Ordinance',
            'badgeClass' => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
            'iconBg' => 'bg-emerald-50 text-emerald-600',
            'icon' => 'fa-shield-halved'
        ];
    } else {
        return [
            'label' => 'General Announcement',
            'badgeClass' => 'bg-blue-50 text-[#0f53d1] border border-blue-200',
            'iconBg' => 'bg-blue-50 text-[#0f53d1]',
            'icon' => 'fa-bullhorn'
        ];
    }
}

function getStatusMeta($status) {
    $s = strtolower(trim($status));
    if ($s === 'delivered' || $s === 'sent') {
        return [
            'label' => 'Delivered',
            'badgeClass' => 'bg-emerald-50 text-emerald-600 border border-emerald-200'
        ];
    } elseif ($s === 'scheduled') {
        return [
            'label' => 'Scheduled',
            'badgeClass' => 'bg-amber-50 text-amber-600 border border-amber-200'
        ];
    } elseif ($s === 'draft') {
        return [
            'label' => 'Draft',
            'badgeClass' => 'bg-slate-100 text-slate-600 border border-slate-200'
        ];
    } else {
        return [
            'label' => ucfirst($status),
            'badgeClass' => 'bg-blue-50 text-[#0f53d1] border border-blue-200'
        ];
    }
}

// Fetch real metrics & records from broadcast_alerts table
$totalBroadcasts = 0;
$totalRecipients = 0;
$deliveryRate = "0.0%";
$scheduledCount = 0;
$broadcasts = [];
$broadcastsJson = [];

try {
    $pdo = getDbConnection();

    // Handle direct POST delete fallback
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_alert') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $delStmt = $pdo->prepare("DELETE FROM \`broadcast_alerts\` WHERE id = :id");
            $delStmt->execute([':id' => $delId]);
            header('Location: broadcast-history.php');
            exit;
        }
    }

    // Stats
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_broadcasts,
            COALESCE(SUM(recipients_count), 0) AS total_recipients,
            COALESCE(SUM(delivered_count), 0) AS total_delivered,
            COALESCE(SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END), 0) AS total_scheduled
        FROM `broadcast_alerts`
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    if ($stats) {
        $totalBroadcasts = (int)$stats['total_broadcasts'];
        $totalRecipients = (int)$stats['total_recipients'];
        $totalDelivered = (int)$stats['total_delivered'];
        $scheduledCount = (int)$stats['total_scheduled'];
        if ($totalRecipients > 0) {
            $deliveryRate = number_format(($totalDelivered / $totalRecipients) * 100, 1) . '%';
        }
    }

    // Rows
    $stmt = $pdo->query("
        SELECT * FROM `broadcast_alerts` 
        ORDER BY `created_at` DESC
    ");
    $broadcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build client json map for quick drawer display
    foreach ($broadcasts as $b) {
        $catMeta = getCategoryMeta($b['category']);
        $statusMeta = getStatusMeta($b['status']);
        
        $recip = (int)$b['recipients_count'];
        $deliv = (int)$b['delivered_count'];
        $failed = (int)$b['failed_count'];
        $pending = (int)$b['pending_count'];

        $delivPct = $recip > 0 ? number_format(($deliv / $recip) * 100, 1) : '0.0';
        $failPct = $recip > 0 ? number_format(($failed / $recip) * 100, 1) : '0.0';
        $pendPct = $recip > 0 ? number_format(($pending / $recip) * 100, 1) : '0.0';

        $broadcastsJson[$b['id']] = [
            'id' => (int)$b['id'],
            'alertId' => $b['alert_id'] ?: 'ALERT-' . $b['id'],
            'title' => $b['title'],
            'category' => $catMeta['label'],
            'categoryBadgeClass' => $catMeta['badgeClass'],
            'iconClass' => $catMeta['iconBg'] . ' ' . $catMeta['icon'],
            'sender' => ($b['sender_name'] ?: 'Barangay Information Office') . ' (' . ($b['sender_role'] ?: 'Public Information Officer') . ')',
            'timestamp' => date('M j, Y • g:i A', strtotime($b['created_at'])),
            'status' => $statusMeta['label'],
            'statusClass' => $statusMeta['badgeClass'],
            'targetRecipients' => $b['target_audience'] ?: 'All Residents',
            'recipientsCount' => number_format($recip),
            'deliveredCount' => number_format($deliv) . " ({$delivPct}%)",
            'failedCount' => number_format($failed) . " ({$failPct}%)",
            'pendingCount' => number_format($pending) . " ({$pendPct}%)",
            'bodyHTML' => !empty($b['body']) ? $b['body'] : '<p class="text-slate-400 italic">No content provided.</p>',
            'channels' => $b['channels'] ?: 'In-App / Push',
            'attachmentUrl' => $b['attachment_url'],
            'rawDeliveredPct' => (float)$delivPct,
            'rawFailedPct' => (float)$failPct,
        ];
    }
} catch (Exception $e) {
    // Graceful fallback if database error
    error_log("Database error in broadcast-history: " . $e->getMessage());
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

    <!-- Breadcrumb Header -->
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
        <span>Notifications & Alerts</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Broadcast History</span>
    </div>

    <!-- Top Action Row -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Broadcast History & Delivery Logs</h1>
            <p class="text-xs text-slate-500 font-medium">Real-time repository of all citizen alerts, public warnings, and municipal advisories.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="compose-alert.php" class="px-4 py-2.5 bg-[#0f53d1] hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-pen"></i>
                <span>Compose New Alert</span>
            </a>
            <button type="button" onclick="exportReport()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Report</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Broadcasts -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Broadcasts</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($totalBroadcasts); ?></h3>
                <p class="text-[11px] font-semibold text-slate-400 flex items-center gap-1 mt-1">
                    <span><?php echo $totalBroadcasts > 0 ? 'Live database records' : 'No broadcasts yet'; ?></span>
                </p>
            </div>
        </div>

        <!-- Card 2: Total Recipients Reached -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Recipients Reached</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($totalRecipients); ?></h3>
                <p class="text-[11px] font-semibold text-slate-400 flex items-center gap-1 mt-1">
                    <span><?php echo $totalRecipients > 0 ? 'Delivered via App, SMS & Email' : 'No recipients yet'; ?></span>
                </p>
            </div>
        </div>

        <!-- Card 3: Successful Deliveries -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Successful Deliveries</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $deliveryRate; ?></h3>
                <p class="text-[11px] font-semibold text-slate-400 flex items-center gap-1 mt-1">
                    <span>Average transmission rate</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Scheduled Broadcasts -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Scheduled Broadcasts</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($scheduledCount); ?></h3>
                <span class="text-[11px] font-medium text-slate-400 inline-block mt-1"><?php echo $scheduledCount > 0 ? 'Pending queue delivery' : 'No scheduled alerts'; ?></span>
            </div>
        </div>

    </div>

    <!-- Main Content Layout (Full Width Table) -->
    <div id="tableContainer" class="w-full space-y-4">
            
            <!-- Filters & Search Bar Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                    
                    <!-- Search Input (4 Cols) -->
                    <div class="lg:col-span-4 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchInput" oninput="filterTable()" placeholder="Search alerts by title or content..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Category Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="categoryFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Categories</option>
                            <option value="General Announcement">General Announcement</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Health Advisory">Health Advisory</option>
                            <option value="Event">Event</option>
                            <option value="Curfew">Curfew / Ordinance</option>
                        </select>
                    </div>

                    <!-- Channel Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="channelFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Channels</option>
                            <option value="SMS">SMS</option>
                            <option value="In-App">In-App / Push</option>
                            <option value="Email">Email</option>
                        </select>
                    </div>

                    <!-- Status Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="statusFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Partial">Partial</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Draft">Draft</option>
                        </select>
                    </div>

                    <!-- Filter Button (2 Cols) -->
                    <div class="lg:col-span-2">
                        <button type="button" onclick="filterTable()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-sliders text-slate-400"></i>
                            <span>Filter</span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Broadcast History Data Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Alert Title</th>
                                <th class="py-3.5 px-3">Category</th>
                                <th class="py-3.5 px-3">Sender</th>
                                <th class="py-3.5 px-3">Date / Time Sent</th>
                                <th class="py-3.5 px-3 text-center">Recipients</th>
                                <th class="py-3.5 px-3 text-center">Channels</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="broadcastTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <?php if (empty($broadcasts)): ?>
                            <tr>
                                <td colspan="8" class="py-14 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 text-lg">
                                            <i class="fa-solid fa-inbox"></i>
                                        </div>
                                        <p class="font-bold text-slate-700 text-sm">No broadcast alerts found</p>
                                        <p class="text-xs text-slate-400">There are no broadcast notifications or emergency alerts recorded yet.</p>
                                        <a href="compose-alert.php" class="mt-2 px-4 py-2 bg-[#0f53d1] hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Compose New Alert</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($broadcasts as $b): 
                                $cat = getCategoryMeta($b['category']);
                                $st = getStatusMeta($b['status']);
                                $channels = explode(',', $b['channels'] ?: 'In-App / Push');
                            ?>
                            <tr class="broadcast-row hover:bg-slate-50/80 transition cursor-pointer" onclick="selectBroadcastRow(this, <?php echo $b['id']; ?>)" data-category="<?php echo htmlspecialchars($cat['label']); ?>" data-status="<?php echo htmlspecialchars($st['label']); ?>" data-channels="<?php echo htmlspecialchars($b['channels'] ?: ''); ?>">
                                <td class="py-3.5 px-4 font-bold text-slate-900 max-w-xs">
                                    <div class="truncate text-xs font-bold text-slate-900 flex items-center gap-1.5"><?php echo htmlspecialchars($b['title']); ?><?php if (!empty($b['attachment_url'])): ?><span class="px-1.5 py-0.5 rounded-md bg-blue-50 text-[#0f53d1] font-bold text-[9px] border border-blue-200 shrink-0 inline-flex items-center gap-1"><i class="fa-solid fa-paperclip text-[8px]"></i> File</span><?php endif; ?></div>
                                    <div class="text-[10px] text-slate-400 font-semibold truncate mt-0.5">ID: <?php echo htmlspecialchars($b['alert_id'] ?: 'ALERT-' . $b['id']); ?></div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5 <?php echo $cat['badgeClass']; ?>">
                                        <i class="fa-solid <?php echo $cat['icon']; ?> text-[9px]"></i>
                                        <?php echo htmlspecialchars($cat['label']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 font-semibold text-xs">
                                    <?php echo htmlspecialchars($b['sender_name'] ?: 'Barangay Admin'); ?>
                                </td>
                                <td class="py-3.5 px-3 text-slate-500 text-[11px] whitespace-nowrap">
                                    <?php echo date('M j, Y • g:i A', strtotime($b['created_at'])); ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800">
                                    <?php echo number_format((int)$b['recipients_count']); ?>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs text-slate-500">
                                        <?php 
                                        $chStr = strtolower($b['channels'] ?: '');
                                        if (strpos($chStr, 'in-app') !== false || strpos($chStr, 'push') !== false): ?>
                                            <span title="In-App / Push" class="w-6 h-6 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-[10px]"><i class="fa-solid fa-bell"></i></span>
                                        <?php endif; ?>
                                        <?php if (strpos($chStr, 'sms') !== false): ?>
                                            <span title="SMS" class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px]"><i class="fa-solid fa-comment-dots"></i></span>
                                        <?php endif; ?>
                                        <?php if (strpos($chStr, 'email') !== false): ?>
                                            <span title="Email" class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-[10px]"><i class="fa-solid fa-envelope"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $st['badgeClass']; ?>">
                                        <?php echo htmlspecialchars($st['label']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center" onclick="event.stopPropagation()">
                                    <button type="button" onclick="selectBroadcastRow(this.closest('tr'), <?php echo $b['id']; ?>)" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-[#0f53d1] text-slate-600 hover:text-white font-bold text-[11px] transition cursor-pointer">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                <div class="px-4 py-3 bg-slate-50/50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
                    <div>
                        <span id="broadcastPaginationInfo">Showing <?php echo count($broadcasts); ?> of <?php echo count($broadcasts); ?> results</span>
                    </div>

                    <div id="broadcastPaginationControls" class="flex items-center gap-1.5">
                        <!-- Dynamic page buttons -->
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px]">Rows per page</span>
                        <select id="broadcastRowsPerPage" onchange="changeBroadcastPageSize(this.value)" class="bg-white border border-slate-200 rounded-lg text-xs font-bold px-2 py-1 outline-none cursor-pointer">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                    </div>
                </div>
            </div>

    </div>

</main>

<!-- ============================================================================== -->
<!-- ALERT & BROADCAST DETAILS MODAL                                                -->
<!-- ============================================================================== -->
<div id="alertDetailsDrawer" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
        
        <!-- Modal Light Header with Back button, Alert Title, Status badge, and Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeDetailsDrawer()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Alerts Queue">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div id="drawerCategoryIcon" class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 id="drawerTitle" class="font-extrabold text-base tracking-tight text-slate-900 leading-snug">Alert Details</h3>
                    <p id="drawerCategoryText" class="text-xs text-[#0f53d1] font-bold mt-0.5">General Announcement</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="drawerStatusBadge" class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200 shrink-0">Delivered</span>
                <button type="button" onclick="closeDetailsDrawer()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
            <!-- Timestamp banner -->
            <div class="flex items-center justify-between px-3 py-2 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                <span class="text-slate-400 font-bold uppercase text-[10px]">Broadcast Timestamp</span>
                <p id="drawerTimestamp" class="text-slate-700 font-bold text-xs flex items-center gap-1.5">
                    <i class="fa-regular fa-calendar text-[#0f53d1]"></i>
                    <span>Date & Time</span>
                </p>
            </div>

            <!-- Message Content Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-2 shadow-xs">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">MESSAGE CONTENT</h4>
                <div id="drawerMessageContent" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 font-medium leading-relaxed space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                </div>
            </div>

            <!-- Attached Media & Document Card -->
            <div id="drawerAttachmentSection" class="hidden bg-white rounded-2xl border border-slate-200 p-4.5 space-y-2 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ATTACHED MEDIA / DOCUMENT</h4>
                    <span id="drawerAttachmentBadge" class="px-2 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[9px] border border-blue-200">Attachment</span>
                </div>
                <div id="drawerAttachmentContainer" class="p-2 bg-slate-50 border border-slate-200 rounded-xl">
                </div>
            </div>

            <!-- Recipients & Delivery Channels Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3.5 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">RECIPIENTS SUMMARY</h4>
                    <span id="drawerRecipientsBadge" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">0 recipients</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-xs">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span id="drawerRecipientsTarget" class="text-xs font-bold text-slate-900 block">All Residents</span>
                        <span class="text-[10px] text-slate-400">Target Audience</span>
                    </div>
                </div>

                <div class="space-y-1.5 pt-1">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DELIVERY CHANNELS</h4>
                    <div id="drawerChannelsList" class="grid grid-cols-3 gap-2">
                        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                            <div class="text-emerald-500 text-sm"><i class="fa-solid fa-comment-dots"></i></div>
                            <p class="text-[10px] font-bold text-slate-800">SMS</p>
                            <p class="text-[9px] text-emerald-600 font-bold">Enabled</p>
                        </div>
                        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                            <div class="text-[#0f53d1] text-sm"><i class="fa-solid fa-bell"></i></div>
                            <p class="text-[10px] font-bold text-slate-800">In-App / Push</p>
                            <p class="text-[9px] text-emerald-600 font-bold">Delivered</p>
                        </div>
                        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                            <div class="text-purple-600 text-sm"><i class="fa-solid fa-envelope"></i></div>
                            <p class="text-[10px] font-bold text-slate-800">Email</p>
                            <p class="text-[9px] text-emerald-600 font-bold">Enabled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Summary Donut Chart Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block border-b border-slate-100 pb-2">DELIVERY STATUS BREAKDOWN</h4>
                <div class="flex items-center gap-5">
                    <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-slate-300" stroke-dasharray="100, 100" stroke-dashoffset="0" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path id="donutFailedPath" class="text-amber-400" stroke-dasharray="0, 100" stroke-dashoffset="0" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path id="donutDeliveredPath" class="text-emerald-500" stroke-dasharray="98.5, 100" stroke-dashoffset="0" stroke-width="4.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span id="donutTotalRecipients" class="text-xs font-black text-slate-900 leading-none">0</span>
                            <span class="text-[8px] font-bold text-slate-400 leading-none mt-0.5">Total</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs min-w-0 flex-1">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-600 font-medium text-[11px]">Delivered</span>
                            </div>
                            <span id="donutDeliveredCount" class="font-bold text-slate-900 text-[11px]">0 (0%)</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span class="text-slate-600 font-medium text-[11px]">Failed</span>
                            </div>
                            <span id="donutFailedCount" class="font-bold text-slate-900 text-[11px]">0 (0%)</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span class="text-slate-600 font-medium text-[11px]">Pending</span>
                            </div>
                            <span id="donutPendingCount" class="font-bold text-slate-900 text-[11px]">0 (0%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata Details -->
            <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Created By</span>
                    <p id="drawerCreatedBy" class="font-bold text-slate-800 text-[11px] truncate">Admin</p>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Alert ID</span>
                    <p id="drawerAlertId" class="font-bold text-slate-800 text-[11px] flex items-center gap-1 truncate">
                        <span>ALERT-ID</span>
                        <i class="fa-regular fa-copy text-slate-400 hover:text-slate-700 cursor-pointer" onclick="copyAlertId()"></i>
                    </p>
                </div>
            </div>

            <!-- Staff Action Buttons -->
            <div class="flex items-center gap-2 pt-1">
                <button type="button" onclick="resendAlert()" class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-slate-400"></i>
                    <span>Resend Alert</span>
                </button>
                <button type="button" onclick="downloadAlertReport()" class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-download text-slate-400"></i>
                    <span>Download Report</span>
                </button>
                <button type="button" onclick="deleteBroadcastAlert()" class="py-2.5 px-3.5 bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Delete Broadcast Alert">
                    <i class="fa-solid fa-trash-can text-rose-600"></i>
                    <span>Delete</span>
                </button>
            </div>
        </div>

        <!-- Modal Bottom Navigation (Delete & Close Buttons) -->
        <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
            <button type="button" onclick="deleteBroadcastAlert()" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-2xs" title="Permanently delete this broadcast announcement">
                <i class="fa-solid fa-trash-can text-xs text-rose-600"></i>
                <span>Delete Announcement</span>
            </button>
            <button type="button" onclick="closeDetailsDrawer()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>

    </div>
</div>

<script>
const broadcastData = <?php echo json_encode($broadcastsJson); ?>;
let activeBroadcastId = null;

function selectBroadcastRow(rowElement, id) {
    const drawer = document.getElementById('alertDetailsDrawer');
    const tableContainer = document.getElementById('tableContainer');

    if (activeBroadcastId === id && !drawer.classList.contains('hidden')) {
        closeDetailsDrawer();
        return;
    }

    activeBroadcastId = id;
    document.querySelectorAll('.broadcast-row').forEach(r => {
        r.classList.remove('bg-blue-50/60');
    });
    if (rowElement) {
        rowElement.classList.add('bg-blue-50/60');
    }

    const data = broadcastData[id];
    if (!data) return;

    // Update Drawer UI
    document.getElementById('drawerTitle').innerText = data.title;
    document.getElementById('drawerCategoryText').innerText = data.category;
    document.getElementById('drawerTimestamp').innerHTML = `<i class="fa-regular fa-calendar"></i> <span>${data.timestamp}</span>`;
    
    // Status Badge
    const statusBadge = document.getElementById('drawerStatusBadge');
    statusBadge.innerText = data.status;
    statusBadge.className = `px-2.5 py-0.5 rounded-full font-bold text-[10px] border shrink-0 ${data.statusClass}`;

    // Icon
    const iconContainer = document.getElementById('drawerCategoryIcon');
    const iconParts = (data.iconClass || '').split(' ');
    iconContainer.className = `w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm ${iconParts[0] || 'bg-blue-50'} ${iconParts[1] || 'text-[#0f53d1]'}`;
    iconContainer.innerHTML = `<i class="fa-solid ${iconParts[2] || 'fa-bullhorn'}"></i>`;

    // Message Content & Summary
    document.getElementById('drawerMessageContent').innerHTML = data.bodyHTML;
    document.getElementById('drawerRecipientsTarget').innerText = data.targetRecipients;
    document.getElementById('drawerRecipientsBadge').innerText = `${data.recipientsCount} recipients`;
    document.getElementById('donutTotalRecipients').innerText = data.recipientsCount;
    document.getElementById('donutDeliveredCount').innerText = data.deliveredCount;
    document.getElementById('donutFailedCount').innerText = data.failedCount;
    document.getElementById('donutPendingCount').innerText = data.pendingCount;

    // Update SVG Donut stroke
    const delivPath = document.getElementById('donutDeliveredPath');
    if (delivPath) {
        const pct = Math.min(100, Math.max(0, data.rawDeliveredPct || 0));
        delivPath.setAttribute('stroke-dasharray', `${pct}, 100`);
    }

    // Metadata
    document.getElementById('drawerCreatedBy').innerHTML = `${data.sender}`;
    document.getElementById('drawerAlertId').innerHTML = `<span>${data.alertId}</span> <i class="fa-regular fa-copy text-slate-400 hover:text-slate-700 cursor-pointer" onclick="copyAlertId('${data.alertId}')"></i>`;

    // Attachment Display in Modal
    const attachSection = document.getElementById('drawerAttachmentSection');
    const attachContainer = document.getElementById('drawerAttachmentContainer');
    if (attachSection && attachContainer) {
        if (data.attachmentUrl && data.attachmentUrl.trim()) {
            let fullUrl = data.attachmentUrl.trim();
            if (!fullUrl.startsWith('http://') && !fullUrl.startsWith('https://') && !fullUrl.startsWith('data:image/')) {
                const clean = fullUrl.replace(/^\/+/, '');
                fullUrl = '../../' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
            }
            const isPdf = fullUrl.toLowerCase().endsWith('.pdf');
            if (isPdf) {
                attachContainer.innerHTML = `
                    <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-file-pdf text-rose-500 text-xl"></i>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Official Attached Document (PDF)</p>
                                <a href="${fullUrl}" target="_blank" class="text-[10px] text-blue-600 font-semibold hover:underline">Click to view / download document</a>
                            </div>
                        </div>
                        <a href="${fullUrl}" target="_blank" class="px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-xs font-bold flex items-center gap-1 transition">
                            <span>Open</span> <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                `;
            } else {
                attachContainer.innerHTML = `
                    <div class="space-y-2">
                        <div class="w-full max-h-60 rounded-xl overflow-hidden border border-slate-200 bg-slate-900/5 flex items-center justify-center">
                            <img src="${fullUrl}" alt="Alert Graphic" class="w-full h-auto max-h-60 object-cover" onerror="this.onerror=null; this.src='../../assets/images/placeholder-image.png';">
                        </div>
                        <div class="flex items-center justify-between px-1">
                            <span class="text-[10px] text-slate-400 font-medium truncate">${fullUrl.split('/').pop()}</span>
                            <a href="${fullUrl}" target="_blank" class="text-[10px] font-bold text-blue-600 hover:underline flex items-center gap-1">
                                <span>View Full Size</span> <i class="fa-solid fa-magnifying-glass-plus text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                `;
            }
            attachSection.classList.remove('hidden');
        } else {
            attachSection.classList.add('hidden');
            attachContainer.innerHTML = '';
        }
    }

    // Unhide modal
    drawer.classList.remove('hidden');
}

function closeDetailsDrawer() {
    activeBroadcastId = null;
    document.querySelectorAll('.broadcast-row').forEach(r => {
        r.classList.remove('bg-blue-50/60');
    });
    const drawer = document.getElementById('alertDetailsDrawer');
    if (drawer) drawer.classList.add('hidden');
}

function filterTable() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const catVal = document.getElementById('categoryFilter').value.toLowerCase();
    const chanVal = document.getElementById('channelFilter').value.toLowerCase();
    const statusVal = document.getElementById('statusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.broadcast-row');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const cat = (r.getAttribute('data-category') || '').toLowerCase();
        const chan = (r.getAttribute('data-channels') || '').toLowerCase();
        const status = (r.getAttribute('data-status') || '').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesCat = !catVal || cat.includes(catVal);
        const matchesChan = !chanVal || chan.includes(chanVal);
        const matchesStatus = !statusVal || status.includes(statusVal);

        if (matchesSearch && matchesCat && matchesChan && matchesStatus) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function exportReport() {
    window.print();
}

function resendAlert() {
    const title = document.getElementById('drawerTitle').innerText;
    alert(`Initiating re-broadcast transmission for: "${title}".`);
}

function downloadAlertReport() {
    const alertId = document.getElementById('drawerAlertId').innerText.trim();
    alert(`Downloading delivery metrics report for ID: ${alertId}...`);
}

function copyAlertId(alertId) {
    const idText = alertId || document.getElementById('drawerAlertId').innerText.trim();
    navigator.clipboard.writeText(idText);
    alert(`Copied Alert ID: ${idText}`);
}

async function deleteBroadcastAlert() {
    if (!activeBroadcastId) {
        alert('Please select an announcement or alert first.');
        return;
    }
    const data = broadcastData[activeBroadcastId];
    const title = data ? data.title : 'this announcement';
    if (!confirm(`Are you sure you want to permanently delete the broadcast announcement "${title}"?\n\nThis action cannot be undone.`)) {
        return;
    }

    try {
        const res = await fetch('../../api/admin/delete-alert.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: activeBroadcastId, alert_id: data ? data.alertId : '' })
        });
        const result = await res.json();
        if (result.status === 'success') {
            alert('Broadcast announcement deleted successfully.');
            window.location.reload();
        } else {
            alert(result.message || 'Failed to delete alert.');
        }
    } catch (err) {
        // Fallback to Form POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'broadcast-history.php';
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'delete_alert';
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = activeBroadcastId;
        form.appendChild(inputAction);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
}

// Client-Side Pagination for Broadcast History
let currentBroadcastPage = 1;
let broadcastPageSize = 10;

function renderBroadcastPagination() {
    const rows = Array.from(document.querySelectorAll('tbody tr.broadcast-row')).filter(r => r.style.display !== 'none');
    const totalItems = rows.length;
    const totalPages = Math.ceil(totalItems / broadcastPageSize) || 1;
    if (currentBroadcastPage > totalPages) currentBroadcastPage = totalPages;

    const startIdx = (currentBroadcastPage - 1) * broadcastPageSize;
    const endIdx = startIdx + broadcastPageSize;

    rows.forEach((r, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });

    const info = document.getElementById('broadcastPaginationInfo');
    if (info) {
        const start = totalItems === 0 ? 0 : startIdx + 1;
        const end = Math.min(endIdx, totalItems);
        info.textContent = `Showing ${start} to ${end} of ${totalItems} results`;
    }

    const container = document.getElementById('broadcastPaginationControls');
    if (!container) return;

    let html = '';
    const prevDisabled = currentBroadcastPage === 1 ? 'disabled opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-50';
    html += `<button onclick="goToBroadcastPage(${currentBroadcastPage - 1})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 text-xs ${prevDisabled}"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === currentBroadcastPage) {
            html += `<button class="w-7 h-7 rounded-lg bg-[#0f53d1] text-white font-bold flex items-center justify-center shadow-xs text-xs">${p}</button>`;
        } else {
            html += `<button onclick="goToBroadcastPage(${p})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs cursor-pointer flex items-center justify-center">${p}</button>`;
        }
    }

    const nextDisabled = currentBroadcastPage === totalPages ? 'disabled opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-50';
    html += `<button onclick="goToBroadcastPage(${currentBroadcastPage + 1})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 text-xs ${nextDisabled}"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>`;

    container.innerHTML = html;
}

function goToBroadcastPage(page) {
    const rows = Array.from(document.querySelectorAll('tbody tr.broadcast-row')).filter(r => r.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / broadcastPageSize) || 1;
    if (page < 1 || page > totalPages) return;
    currentBroadcastPage = page;
    renderBroadcastPagination();
}

function changeBroadcastPageSize(newSize) {
    broadcastPageSize = parseInt(newSize, 10) || 10;
    currentBroadcastPage = 1;
    renderBroadcastPagination();
}

document.addEventListener('DOMContentLoaded', function() {
    renderBroadcastPagination();
    const modal = document.getElementById('alertDetailsDrawer');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDetailsDrawer();
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailsDrawer();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
