<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Dedicated Certificate Database Connection
$pdo = getCertificateDbConnection();

// Auto-fill Citizen Registry from citizen_verifications table
$registeredCitizens = [];
try {
    $verPdo = getDbConnection();
    $citStmt = $verPdo->query("SELECT id, first_name, last_name, middle_name, current_address, barangay, mobile_number FROM citizen_verifications WHERE status = 'Approved' ORDER BY id DESC LIMIT 50");
    while ($c = $citStmt->fetch()) {
        $cId = 'CTZ-2026-' . str_pad($c['id'], 4, '0', STR_PAD_LEFT);
        $name = trim("{$c['first_name']} {$c['middle_name']} {$c['last_name']}");
        $registeredCitizens[$cId] = [
            'id' => $cId,
            'name' => $name,
            'address' => (!empty($c['current_address']) ? $c['current_address'] . ', ' : '') . ($c['barangay'] ?? 'Caloocan City'),
            'contact' => $c['mobile_number'] ?? '09170000000',
            'civil_status' => 'Single',
            'resident_since' => '2018'
        ];
    }
} catch (Exception $e) {
    // Fallback if verification DB offline
}

// Compute Dynamic KPIs from civentral_certificates.certificate_requests
$totalRequests = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests`")->fetchColumn();
$pendingProcessing = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Pending', 'Under Review')")->fetchColumn();
$readyForRelease = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` = 'Ready for Release'")->fetchColumn();
$releasedToday = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` = 'Released' AND DATE(`released_at`) = CURDATE()")->fetchColumn();

// Fetch Live Certificate Requests
$stmt = $pdo->query("SELECT * FROM `certificate_requests` ORDER BY `request_id` DESC");
$dbRequests = $stmt->fetchAll();

$requests = [];
foreach ($dbRequests as $row) {
    $stat = $row['status'];
    $statusClass = 'bg-slate-100 text-slate-700 border-slate-200';
    if ($stat === 'Pending') $statusClass = 'bg-amber-50 text-amber-600 border-amber-200';
    else if ($stat === 'Under Review') $statusClass = 'bg-blue-50 text-blue-600 border-blue-200';
    else if ($stat === 'Approved') $statusClass = 'bg-indigo-50 text-indigo-600 border-indigo-200';
    else if ($stat === 'Ready for Release') $statusClass = 'bg-purple-50 text-purple-600 border-purple-200';
    else if ($stat === 'Released') $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-200';
    else if ($stat === 'Rejected') $statusClass = 'bg-rose-50 text-rose-600 border-rose-200';

    $docList = [];
    if (!empty($row['uploaded_documents'])) {
        $decoded = json_decode($row['uploaded_documents'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $d) {
                if (is_array($d)) {
                    $u = $d['url'] ?? ($d['data'] ?? ($d['uri'] ?? null));
                    if ($u && strpos($u, 'http') !== 0 && strpos($u, 'data:') !== 0) {
                        $clean = ltrim($u, '/');
                        if (strpos($clean, 'uploads/') === 0) $clean = 'assets/' . $clean;
                        $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'citizenship.civentral.tech';
                        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $u = $proto . $host . '/' . $clean;
                    }
                    $docList[] = [
                        'name' => $d['name'] ?? 'Supporting Document',
                        'url'  => $u
                    ];
                } else {
                    $docList[] = [
                        'name' => (string)$d,
                        'url'  => null
                    ];
                }
            }
        }
    }

    $requests[] = [
        'id' => $row['reference_no'],
        'request_id' => $row['request_id'],
        'citizen_id' => $row['citizen_user_id'] ? 'CTZ-2026-' . str_pad($row['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-WALK-IN',
        'requester' => $row['citizen_name'],
        'address' => $row['street_address'] . (!empty($row['barangay']) ? ', ' . $row['barangay'] : ''),
        'cert_type' => $row['certificate_type'],
        'purpose' => $row['purpose'],
        'purpose_details' => $row['purpose_details'] ?? '',
        'additional_notes' => $row['additional_notes'] ?? '',
        'date_requested' => date('M j, Y • h:i A', strtotime($row['created_at'])),
        'encoded_by' => $row['encoded_by'],
        'contact' => $row['contact_number'] ?? 'Not provided',
        'docs' => $docList,
        'status' => $row['status'],
        'status_class' => $statusClass,
        'fee_amount' => number_format((float)$row['fee_amount'], 2),
        'payment_status' => $row['payment_status'],
        'or_number' => $row['or_number'] ?? 'None'
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

    <!-- Top Title & Action Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Certificate Requests</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Certificate Requests</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="refreshRequestsQueue()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer" title="Refresh Live Queue">
                <i class="fa-solid fa-rotate text-xs" id="certRefreshIcon"></i>
                <span>Refresh</span>
            </button>
            <button onclick="exportRequestsCSV()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export List</span>
            </button>

            <button onclick="openNewRequestModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Encode Request</span>
            </button>
        </div>
    </div>

    <!-- Stat Summary Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Requests -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Requests</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $totalRequests; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-database"></i>
                    <span>Live Database Records</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Pending Processing -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Processing</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $pendingProcessing; ?></h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <span>Awaiting clerk / captain approval</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Ready for Release -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ready for Release</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-stamp"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $readyForRelease; ?></h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-building-flag"></i>
                    <span>For pickup at Barangay Hall</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Released Today -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Released Today</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $releasedToday; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Cleared today</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Main Table Container -->
    <div id="requestTableContainer" class="w-full space-y-4">

            <!-- Search & Multi-Filter Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="requestSearchInput" oninput="filterRequestsTable()" placeholder="Search by requester name, reference ID, purpose, or certificate type..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Certificate Type Filter -->
                    <select id="certTypeFilter" onchange="filterRequestsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Certificate Types</option>
                        <option value="Barangay Certificate">Barangay Certificate</option>
                        <option value="Barangay Clearance">Barangay Clearance</option>
                        <option value="Certificate of Residency">Certificate of Residency</option>
                        <option value="Certificate of Indigency">Certificate of Indigency</option>
                        <option value="Business Permit Clearance">Business Permit Clearance</option>
                        <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                        <option value="First-Time Jobseeker Certificate (RA 11261)">First-Time Jobseeker (RA 11261)</option>
                    </select>

                    <!-- Status Filter -->
                    <select id="requestStatusFilter" onchange="filterRequestsTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Ready for Release">Ready for Release</option>
                        <option value="Released">Released</option>
                        <option value="Rejected">Rejected</option>
                    </select>

                    <!-- Reset Button -->
                    <button onclick="resetRequestFilters()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                        <i class="fa-solid fa-rotate-left text-slate-400"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Certificate Requests Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Requests Directory <span id="requestsCountBadge" class="text-slate-400 font-normal ml-1">(<?php echo count($requests); ?> requests)</span></h3>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Request Ref ID & Requester</th>
                                <th class="py-3.5 px-3">Certificate Type</th>
                                <th class="py-3.5 px-3">Purpose</th>
                                <th class="py-3.5 px-3">Date Requested & Source</th>
                                <th class="py-3.5 px-3">Fee / OR</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-medium text-xs">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-40 block"></i>
                                    No certificate requests found in database.<br>
                                    <span class="text-[11px] text-slate-400 mt-1 block">Requests submitted from the mobile app or encoded here will appear live.</span>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr onclick="selectRequestRow(this, '<?php echo $req['id']; ?>')" class="request-row hover:bg-slate-50 transition cursor-pointer select-none" data-id="<?php echo $req['id']; ?>" data-cert="<?php echo htmlspecialchars($req['cert_type']); ?>" data-status="<?php echo htmlspecialchars($req['status']); ?>">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 font-black text-xs border border-blue-100">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 text-xs truncate"><?php echo htmlspecialchars($req['requester']); ?></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold text-[#0f53d1]"><?php echo $req['id']; ?></span>
                                                <span class="text-[9px] text-slate-400 font-semibold"><?php echo $req['citizen_id']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-slate-900 text-xs block"><?php echo htmlspecialchars($req['cert_type']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-medium truncate max-w-xs block"><?php echo htmlspecialchars($req['address']); ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium">
                                    <span class="truncate block max-w-xs"><?php echo htmlspecialchars($req['purpose']); ?></span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800 text-[11px]"><?php echo $req['date_requested']; ?></p>
                                    <p class="text-[10px] text-slate-400 font-semibold"><?php echo $req['encoded_by']; ?></p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 text-xs block">₱<?php echo $req['fee_amount']; ?></span>
                                    <span class="text-[10px] font-semibold text-slate-400"><?php echo $req['payment_status']; ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $req['status_class']; ?>"><?php echo $req['status']; ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-center" onclick="event.stopPropagation();">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="selectRequestRow(this.closest('tr'), '<?php echo $req['id']; ?>')" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-[#0f53d1] flex items-center justify-center transition cursor-pointer" title="View Request Details"><i class="fa-regular fa-eye text-xs"></i></button>
                                        <?php if ($req['status'] !== 'Released' && $req['status'] !== 'Claimed' && $req['status'] !== 'Rejected'): ?>
                                        <?php if ($req['status'] !== 'Ready for Release'): ?>
                                        <button onclick="processQuickAction('approve', '<?php echo $req['id']; ?>')" class="w-7 h-7 rounded-lg hover:bg-blue-50 text-slate-400 hover:text-blue-600 flex items-center justify-center transition cursor-pointer" title="Mark Ready for Release"><i class="fa-solid fa-file-circle-check text-xs"></i></button>
                                        <?php endif; ?>
                                        <button onclick="processQuickAction('release', '<?php echo $req['id']; ?>')" class="w-7 h-7 rounded-lg hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 flex items-center justify-center transition cursor-pointer" title="Issue & Release Certificate"><i class="fa-solid fa-stamp text-xs"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
    </div>

</main>

<!-- ============================================================================== -->
<!-- CERTIFICATE REQUEST DETAILS & INSPECTOR MODAL                                  -->
<!-- ============================================================================== -->
<div id="requestDetailsDrawer" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
        
        <!-- Modal Header with Back Button, Ref ID, Status, Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeRequestDrawer()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Certificate Requests Queue">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-certificate"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Certificate Request</h3>
                        <span id="drawerReqId" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">CAL-DOC-2026-0000</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Municipal Document Application Inspector</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="drawerReqStatus" class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-amber-50 text-amber-600 border border-amber-200">Pending</span>
                <button type="button" onclick="closeRequestDrawer()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
            
            <!-- Requester Details Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-user text-[#0f53d1]"></i>
                    <span>Requester Profile</span>
                </span>
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 id="drawerRequesterName" class="text-base font-black text-slate-900 leading-snug">Danny Espelita Jr</h3>
                        <p id="drawerRequesterAddress" class="text-xs text-slate-500 font-medium mt-0.5">Barangay 171, Caloocan City</p>
                    </div>
                    <span id="drawerCitizenId" class="text-xs text-[#0f53d1] font-mono font-bold bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">CTZ-2026-0001</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Requested Certificate</span>
                        <span id="drawerCertType" class="font-bold text-slate-800 text-xs">Barangay Clearance</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Purpose of Request</span>
                        <span id="drawerPurpose" class="font-bold text-slate-800 text-xs truncate block">Employment</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Official Fee</span>
                        <span id="drawerFee" class="font-bold text-emerald-600 text-xs">₱50.00</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Payment Status</span>
                        <span id="drawerPaymentStatus" class="font-bold text-slate-800 text-xs">Pending</span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Contact Phone</span>
                    <span id="drawerContact" class="font-bold text-slate-800">09171234567</span>
                </div>
            </div>

            <!-- Uploaded Supporting Documents Section -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4.5 space-y-3 shadow-xs">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-paperclip text-slate-500"></i>
                    <span>Attached Supporting Documents</span>
                </span>
                <div id="drawerDocsList" class="space-y-2">
                    <span class="text-xs text-slate-400 italic">No attachments provided</span>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div id="drawerActionButtonsBox" class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Staff Processing Actions</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <button type="button" id="drawerBtnReadyForRelease" onclick="actionFromDrawer('approve')" class="py-2.5 px-3 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-file-circle-check text-xs"></i>
                        <span>Ready for Release</span>
                    </button>

                    <button type="button" id="drawerBtnIssueCert" onclick="actionFromDrawer('release')" class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-stamp text-xs"></i>
                        <span>Issue Certificate</span>
                    </button>

                    <button type="button" id="drawerBtnReject" onclick="actionFromDrawer('reject')" class="py-2.5 px-3 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Reject</span>
                    </button>
                </div>
            </div>

        </div>



    </div>
</div>

<!-- NEW REQUEST ENCODING MODAL (Connected to Backend Database) -->
<div id="newRequestModal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                    <i class="fa-solid fa-keyboard"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Encode New Certificate Request</h3>
                    <p class="text-xs text-slate-500 font-medium">Submit directly to the central certificate registry</p>
                </div>
            </div>
            <button onclick="closeNewRequestModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Auto-Fill Citizen Selection Dropdown -->
            <div class="p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                    <label class="font-black text-slate-900 text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-id-card text-[#0f53d1]"></i>
                        <span>Select Registered Citizen (Auto-Fill)</span>
                    </label>
                    <span class="text-[10px] font-bold text-[#0f53d1]">Verified registry</span>
                </div>
                <select id="citizenRegistrySelect" onchange="autoFillCitizenDetails(this.value)" class="w-full bg-white border border-slate-200 text-slate-800 font-bold rounded-xl p-2.5 outline-none text-xs cursor-pointer">
                    <option value="">-- Choose Citizen to Auto-Fill --</option>
                    <?php foreach ($registeredCitizens as $cid => $cit): ?>
                    <option value="<?php echo $cid; ?>"><?php echo "{$cid} - {$cit['name']} ({$cit['address']})"; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Form Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Applicant Full Name *</label>
                    <input type="text" id="encodeName" placeholder="e.g. Danny Espelita Jr" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Contact Mobile Number *</label>
                    <input type="text" id="encodeContact" placeholder="09171234567" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]">
                </div>

                <div class="md:col-span-2">
                    <label class="font-bold text-slate-700 block mb-1">Residential Street Address *</label>
                    <input type="text" id="encodeAddress" placeholder="House/Lot No., Street" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Barangay Location *</label>
                    <select id="encodeBarangay" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none font-bold">
                        <option value="Barangay 171 (Bagumbong)">Barangay 171 (Bagumbong)</option>
                        <option value="Barangay 176 (Bagong Silang)">Barangay 176 (Bagong Silang)</option>
                        <option value="Barangay 178 (Camarin)">Barangay 178 (Camarin)</option>
                        <option value="Barangay 12 (Grace Park)">Barangay 12 (Grace Park)</option>
                        <option value="Barangay 88 (Caloocan South)">Barangay 88 (Caloocan South)</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Certificate Type *</label>
                    <select id="encodeCertType" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none font-bold">
                        <option value="Barangay Certificate">Barangay Certificate (₱50.00)</option>
                        <option value="Barangay Clearance">Barangay Clearance (₱75.00)</option>
                        <option value="Certificate of Residency">Certificate of Residency (₱50.00)</option>
                        <option value="Certificate of Indigency">Certificate of Indigency (FREE)</option>
                        <option value="First-Time Jobseeker Certificate (RA 11261)">First-Time Jobseeker (FREE)</option>
                        <option value="Business Permit Clearance">Business Permit Clearance (₱200.00)</option>
                        <option value="Certificate of Good Moral Character">Good Moral Character (₱50.00)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="font-bold text-slate-700 block mb-1">Application Purpose *</label>
                    <input type="text" id="encodePurpose" placeholder="e.g. Local Employment / Scholarship / Hospital Requirement" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4">
            <button onclick="closeNewRequestModal()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl transition cursor-pointer">
                Cancel
            </button>
            <button id="encodeSubmitBtn" onclick="submitEncodedRequest()" class="px-5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save to Database</span>
            </button>
        </div>

    </div>
</div>

<!-- ISSUE & RELEASE CONFIRMATION MODAL -->
<div id="issueConfirmModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div id="issueModalIconBox" class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100 ring-2 ring-emerald-50 shadow-xs">
                    <i id="issueModalIcon" class="fa-solid fa-stamp"></i>
                </div>
                <div>
                    <h3 id="issueModalTitle" class="text-sm font-black text-slate-900 tracking-tight">Issue & Release Certificate</h3>
                    <p id="issueModalSubtitle" class="text-xs text-slate-400 mt-0.5 font-medium">Official Issuance Authorization</p>
                </div>
            </div>
            <button type="button" onclick="closeIssueConfirmModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4">
            <!-- Request Summary Card -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Document Type</span>
                    <span id="issueModalCertType" class="font-extrabold text-slate-800 text-xs">Barangay Clearance</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Applicant</span>
                    <span id="issueModalApplicantName" class="font-bold text-slate-800">Citizen Applicant</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Reference No</span>
                    <span id="issueModalApplicantRef" class="font-mono font-bold text-[#0f53d1]">CAL-DOC-2026-0000</span>
                </div>
            </div>

            <!-- Confirmation Prompt -->
            <div class="space-y-2.5">
                <p id="issueModalPromptParagraph" class="text-xs font-semibold text-slate-700 leading-snug">
                    Are you sure you want to officially <span class="text-emerald-600 font-black uppercase">issue & release</span> certificate for request <span id="issueModalRefText" class="font-mono font-bold text-slate-900"></span>?
                </p>
                <div id="issueModalCalloutBox" class="p-3 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-start gap-2.5">
                    <i id="issueModalCalloutIcon" class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-xs shrink-0"></i>
                    <p id="issueModalExplainerText" class="text-[11px] text-emerald-900 leading-relaxed font-medium">
                        This will officially mark the document as released, generate an official municipal control number, and record the transaction in the issued certificates registry.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button type="button" onclick="closeIssueConfirmModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                Cancel
            </button>
            <button type="button" id="issueModalConfirmBtn" onclick="executeIssueRequest()" class="px-4.5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                <i id="issueModalConfirmBtnIcon" class="fa-solid fa-stamp text-[11px]"></i>
                <span id="issueModalConfirmBtnText">Issue & Release Certificate</span>
            </button>
        </div>
    </div>
</div>

<!-- REJECT CONFIRMATION MODAL -->
<div id="rejectConfirmModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg border border-rose-100 ring-2 ring-rose-50 shadow-xs">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Reject Certificate Request</h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-medium">Administrative Rejection</p>
                </div>
            </div>
            <button type="button" onclick="closeRejectConfirmModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4">
            <!-- Request Summary Card -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Document Type</span>
                    <span id="rejectModalCertType" class="font-extrabold text-slate-800 text-xs">Barangay Clearance</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Applicant</span>
                    <span id="rejectModalApplicantName" class="font-bold text-slate-800">Citizen Applicant</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Reference No</span>
                    <span id="rejectModalApplicantRef" class="font-mono font-bold text-rose-600">CAL-DOC-2026-0000</span>
                </div>
            </div>

            <!-- Reason Input -->
            <div class="space-y-1.5">
                <label for="rejectReasonInput" class="block text-xs font-bold text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                <textarea id="rejectReasonInput" rows="3" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition" placeholder="State reason for rejecting this document request (e.g., Incomplete or unverified requirements)..."></textarea>
                <p class="text-[10px] text-slate-400">This rationale will be recorded in the system audit trail.</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button type="button" onclick="closeRejectConfirmModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                Cancel
            </button>
            <button type="button" id="rejectModalConfirmBtn" onclick="executeRejectRequest()" class="px-4.5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-xmark text-[11px]"></i>
                <span id="rejectModalConfirmBtnText">Confirm Rejection</span>
            </button>
        </div>
    </div>
</div>

<script>
const requestsDataset = <?php echo json_encode(array_column($requests, null, 'id')); ?>;
const citizenRegistry = <?php echo json_encode($registeredCitizens); ?>;
let activeRequestId = null;

function refreshRequestsQueue() {
    const icon = document.getElementById('certRefreshIcon');
    if (icon) icon.classList.add('fa-spin');
    window.location.reload();
}

function selectRequestRow(rowElement, refId) {
    const drawer = document.getElementById('requestDetailsDrawer');
    const tableContainer = document.getElementById('requestTableContainer');
    const data = requestsDataset[refId];

    if (!data) return;

    activeRequestId = refId;

    document.querySelectorAll('.request-row').forEach(r => r.classList.remove('bg-blue-50/40'));
    if (rowElement) rowElement.classList.add('bg-blue-50/40');

    document.getElementById('drawerReqId').innerText = data.id;
    document.getElementById('drawerReqStatus').innerText = data.status;
    document.getElementById('drawerRequesterName').innerText = data.requester;
    document.getElementById('drawerCitizenId').innerText = data.citizen_id;
    document.getElementById('drawerRequesterAddress').innerText = data.address;
    document.getElementById('drawerCertType').innerText = data.cert_type;
    document.getElementById('drawerPurpose').innerText = data.purpose;
    document.getElementById('drawerFee').innerText = '₱' + data.fee_amount;
    document.getElementById('drawerPaymentStatus').innerText = data.payment_status;
    document.getElementById('drawerContact').innerText = data.contact;

    const docsList = document.getElementById('drawerDocsList');
    if (data.docs && data.docs.length > 0) {
        docsList.innerHTML = '';
        data.docs.forEach(doc => {
            const docName = typeof doc === 'object' ? (doc.name || 'Supporting Document') : doc;
            const docUrl = typeof doc === 'object' ? doc.url : null;
            if (docUrl) {
                docsList.innerHTML += `
                    <div class="p-2.5 bg-blue-50/50 border border-blue-200 rounded-xl flex items-center justify-between text-xs hover:bg-blue-50 transition">
                        <a href="${docUrl}" target="_blank" class="flex items-center gap-2 truncate text-blue-700 hover:text-blue-900 font-bold" title="Click to view file">
                            <i class="fa-solid fa-file-lines text-blue-600 text-sm shrink-0"></i>
                            <span class="text-[11px] truncate">${docName}</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] shrink-0 ml-1"></i>
                        </a>
                        <a href="${docUrl}" target="_blank" class="text-[10px] font-bold text-blue-600 underline shrink-0">View</a>
                    </div>
                `;
            } else {
                docsList.innerHTML += `
                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <i class="fa-solid fa-file-lines text-blue-500 text-sm shrink-0"></i>
                            <span class="font-bold text-slate-800 text-[11px] truncate">${docName}</span>
                        </div>
                    </div>
                `;
            }
        });
    } else {
        docsList.innerHTML = '<span class="text-xs text-slate-400 italic">No attachments provided</span>';
    }

    // Adapt drawer buttons according to current request status
    const readyBtn = document.getElementById('drawerBtnReadyForRelease');
    const issueBtn = document.getElementById('drawerBtnIssueCert');
    const rejectBtn = document.getElementById('drawerBtnReject');

    if (data.status === 'Released' || data.status === 'Claimed') {
        if (readyBtn) readyBtn.classList.add('hidden');
        if (issueBtn) {
            issueBtn.classList.remove('hidden');
            issueBtn.disabled = true;
            issueBtn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Released</span>';
            issueBtn.className = 'py-2.5 px-3 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 cursor-default';
        }
        if (rejectBtn) rejectBtn.classList.add('hidden');
    } else if (data.status === 'Rejected') {
        if (readyBtn) readyBtn.classList.add('hidden');
        if (issueBtn) issueBtn.classList.add('hidden');
        if (rejectBtn) {
            rejectBtn.classList.remove('hidden');
            rejectBtn.disabled = true;
            rejectBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> <span>Rejected</span>';
            rejectBtn.className = 'py-2.5 px-3 bg-rose-50 text-rose-500 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 cursor-default';
        }
    } else if (data.status === 'Ready for Release') {
        if (readyBtn) {
            readyBtn.classList.remove('hidden');
            readyBtn.disabled = true;
            readyBtn.innerHTML = '<i class="fa-solid fa-circle-check text-xs"></i> <span>Ready for Release</span>';
            readyBtn.className = 'py-2.5 px-3 bg-purple-50 text-purple-700 border border-purple-200 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 cursor-default';
        }
        if (issueBtn) {
            issueBtn.classList.remove('hidden');
            issueBtn.disabled = false;
            issueBtn.innerHTML = '<i class="fa-solid fa-stamp text-xs"></i> <span>Issue Certificate</span>';
            issueBtn.className = 'py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
        }
        if (rejectBtn) {
            rejectBtn.classList.remove('hidden');
            rejectBtn.disabled = false;
            rejectBtn.innerHTML = '<i class="fa-solid fa-xmark text-xs"></i> <span>Reject</span>';
            rejectBtn.className = 'py-2.5 px-3 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
        }
    } else {
        // Pending / Under Review
        if (readyBtn) {
            readyBtn.classList.remove('hidden');
            readyBtn.disabled = false;
            readyBtn.innerHTML = '<i class="fa-solid fa-file-circle-check text-xs"></i> <span>Ready for Release</span>';
            readyBtn.className = 'py-2.5 px-3 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
        }
        if (issueBtn) {
            issueBtn.classList.remove('hidden');
            issueBtn.disabled = false;
            issueBtn.innerHTML = '<i class="fa-solid fa-stamp text-xs"></i> <span>Issue Certificate</span>';
            issueBtn.className = 'py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
        }
        if (rejectBtn) {
            rejectBtn.classList.remove('hidden');
            rejectBtn.disabled = false;
            rejectBtn.innerHTML = '<i class="fa-solid fa-xmark text-xs"></i> <span>Reject</span>';
            rejectBtn.className = 'py-2.5 px-3 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
        }
    }

    if (drawer) {
        drawer.classList.remove('hidden');
    }
}

function closeRequestDrawer() {
    activeRequestId = null;
    document.querySelectorAll('.request-row').forEach(r => r.classList.remove('bg-blue-50/40'));
    const drawer = document.getElementById('requestDetailsDrawer');
    if (drawer) drawer.classList.add('hidden');
}

function filterRequestsTable() {
    const searchVal = document.getElementById('requestSearchInput').value.toLowerCase();
    const certVal = document.getElementById('certTypeFilter').value.toLowerCase();
    const statusVal = document.getElementById('requestStatusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.request-row');
    let visibleCount = 0;

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const cert = (r.getAttribute('data-cert') || '').toLowerCase();
        const status = (r.getAttribute('data-status') || '').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesCert = !certVal || cert.includes(certVal);
        const matchesStatus = !statusVal || status.includes(statusVal);

        if (matchesSearch && matchesCert && matchesStatus) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });

    const countBadge = document.getElementById('requestsCountBadge');
    if (countBadge) countBadge.innerText = `(${visibleCount} requests)`;
}

function resetRequestFilters() {
    document.getElementById('requestSearchInput').value = '';
    document.getElementById('certTypeFilter').value = '';
    document.getElementById('requestStatusFilter').value = '';
    filterRequestsTable();
}

function openNewRequestModal() {
    document.getElementById('newRequestModal').classList.remove('hidden');
}

function closeNewRequestModal() {
    document.getElementById('newRequestModal').classList.add('hidden');
}

function autoFillCitizenDetails(cid) {
    if (!cid || !citizenRegistry[cid]) return;
    const c = citizenRegistry[cid];
    document.getElementById('encodeName').value = c.name;
    document.getElementById('encodeContact').value = c.contact;
    document.getElementById('encodeAddress').value = c.address;
}

async function submitEncodedRequest() {
    const name = document.getElementById('encodeName').value.trim();
    const contact = document.getElementById('encodeContact').value.trim();
    const address = document.getElementById('encodeAddress').value.trim();
    const barangay = document.getElementById('encodeBarangay').value;
    const certType = document.getElementById('encodeCertType').value;
    const purpose = document.getElementById('encodePurpose').value.trim();

    if (!name || !address || !purpose) {
        showToast('error', '<i class="fa-solid fa-circle-exclamation"></i> Please fill out all required fields marked with *');
        return;
    }

    const btn = document.getElementById('encodeSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';

    try {
        const res = await fetch('../../api/citizen/request-certificate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                applicant_name: name,
                contact_number: contact,
                street_address: address,
                barangay: barangay,
                certificate_type: certType,
                purpose: purpose,
                encoded_by: 'Staff Walk-in Desk'
            })
        });

        const data = await res.json();
        if (data && data.status === 'success') {
            alert(`Certificate request created! Reference No: ${data.data.reference_no}`);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Could not save request'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save to Database';
        }
    } catch (err) {
        console.error('Submit error:', err);
        alert('Network error connecting to database.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save to Database';
    }
}

function showToast(type, html) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        document.body.appendChild(toast);
    }
    toast.className = `fixed bottom-5 right-5 z-[100000] px-4 py-3 rounded-xl shadow-lg text-xs font-bold transition-all transform flex items-center gap-2 ${
        type === 'success' ? 'bg-emerald-600 text-white shadow-emerald-500/20' : 
        type === 'error' ? 'bg-rose-600 text-white shadow-rose-500/20' : 
        'bg-slate-900 text-white'
    }`;
    toast.innerHTML = html;
    toast.classList.remove('translate-y-10', 'opacity-0');
    setTimeout(() => {
        toast.classList.add('translate-y-10', 'opacity-0');
    }, 3500);
}

let pendingActionRefId = null;
let pendingActionType = 'release'; // 'release' or 'approve'

function openIssueConfirmModal(refId, action = 'release') {
    pendingActionRefId = refId;
    pendingActionType = action;

    const item = requestsDataset[refId] || {};
    const refBadge = document.getElementById('issueModalApplicantRef');
    if (refBadge) refBadge.textContent = refId;

    const refText = document.getElementById('issueModalRefText');
    if (refText) refText.textContent = refId;

    const nameText = document.getElementById('issueModalApplicantName');
    if (nameText) nameText.textContent = item.requester || 'Citizen Applicant';

    const certType = document.getElementById('issueModalCertType');
    if (certType) certType.textContent = item.cert_type || 'Barangay Document';

    const iconBox = document.getElementById('issueModalIconBox');
    const icon = document.getElementById('issueModalIcon');
    const title = document.getElementById('issueModalTitle');
    const subtitle = document.getElementById('issueModalSubtitle');
    const promptPara = document.getElementById('issueModalPromptParagraph');
    const calloutBox = document.getElementById('issueModalCalloutBox');
    const calloutIcon = document.getElementById('issueModalCalloutIcon');
    const explainer = document.getElementById('issueModalExplainerText');
    const btn = document.getElementById('issueModalConfirmBtn');
    const btnIcon = document.getElementById('issueModalConfirmBtnIcon');
    const btnText = document.getElementById('issueModalConfirmBtnText');

    if (action === 'approve') {
        if (iconBox) iconBox.className = 'w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 ring-2 ring-blue-50 shadow-xs';
        if (icon) icon.className = 'fa-solid fa-file-circle-check';
        if (title) title.textContent = 'Mark Ready for Release';
        if (subtitle) subtitle.textContent = 'Document Verification & Preparation';
        if (promptPara) promptPara.innerHTML = `Are you sure you want to mark request <span class="font-mono font-bold text-slate-900">${refId}</span> as <span class="text-[#0f53d1] font-black uppercase">Ready for Release</span>?`;
        if (calloutBox) calloutBox.className = 'p-3 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-start gap-2.5';
        if (calloutIcon) calloutIcon.className = 'fa-solid fa-circle-check text-blue-600 mt-0.5 text-xs shrink-0';
        if (explainer) explainer.textContent = 'This will update the request status to "Ready for Release", notifying the citizen that their certificate has been prepared and is ready for pickup at the Barangay Hall.';
        if (btn) {
            btn.className = 'px-4.5 py-2 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5';
            btn.disabled = false;
        }
        if (btnIcon) btnIcon.className = 'fa-solid fa-file-circle-check text-[11px]';
        if (btnText) btnText.textContent = 'Confirm Ready for Release';
    } else {
        // action === 'release'
        if (iconBox) iconBox.className = 'w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100 ring-2 ring-emerald-50 shadow-xs';
        if (icon) icon.className = 'fa-solid fa-stamp';
        if (title) title.textContent = 'Issue & Release Certificate';
        if (subtitle) subtitle.textContent = 'Official Issuance Authorization';
        if (promptPara) promptPara.innerHTML = `Are you sure you want to officially <span class="text-emerald-600 font-black uppercase">issue & release</span> certificate for request <span class="font-mono font-bold text-slate-900">${refId}</span>?`;
        if (calloutBox) calloutBox.className = 'p-3 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-start gap-2.5';
        if (calloutIcon) calloutIcon.className = 'fa-solid fa-circle-check text-emerald-600 mt-0.5 text-xs shrink-0';
        if (explainer) explainer.textContent = 'This will officially mark the document as released, generate an official municipal control number, and record the transaction in the issued certificates registry.';
        if (btn) {
            btn.className = 'px-4.5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5';
            btn.disabled = false;
        }
        if (btnIcon) btnIcon.className = 'fa-solid fa-stamp text-[11px]';
        if (btnText) btnText.textContent = 'Issue & Release Certificate';
    }

    const modal = document.getElementById('issueConfirmModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeIssueConfirmModal() {
    const modal = document.getElementById('issueConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    pendingActionRefId = null;
}

async function executeIssueRequest() {
    if (!pendingActionRefId) return;
    const refId = pendingActionRefId;
    const action = pendingActionType || 'release';

    const btn = document.getElementById('issueModalConfirmBtn');
    const btnText = document.getElementById('issueModalConfirmBtnText');
    const originalText = btnText ? btnText.textContent : 'Confirm';

    try {
        if (btn) btn.disabled = true;
        if (btnText) btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i> Processing...';

        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: action, reference_no: refId })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            closeIssueConfirmModal();
            closeRequestDrawer();
            showToast('success', `<i class="fa-solid fa-circle-check"></i> ${data.message || 'Action completed successfully!'}`);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', `<i class="fa-solid fa-circle-exclamation"></i> ${data.message || 'Operation failed'}`);
            if (btn) btn.disabled = false;
            if (btnText) btnText.textContent = originalText;
        }
    } catch (err) {
        showToast('error', `<i class="fa-solid fa-triangle-exclamation"></i> Network error connecting to backend.`);
        if (btn) btn.disabled = false;
        if (btnText) btnText.textContent = originalText;
    }
}

let pendingRejectRefId = null;

function openRejectConfirmModal(refId) {
    pendingRejectRefId = refId;
    const item = requestsDataset[refId] || {};

    const refBadge = document.getElementById('rejectModalApplicantRef');
    if (refBadge) refBadge.textContent = refId;

    const nameText = document.getElementById('rejectModalApplicantName');
    if (nameText) nameText.textContent = item.requester || 'Citizen Applicant';

    const typeText = document.getElementById('rejectModalCertType');
    if (typeText) typeText.textContent = item.cert_type || 'Barangay Document';

    const reasonInput = document.getElementById('rejectReasonInput');
    if (reasonInput) reasonInput.value = 'Incomplete or unverified requirements';

    const btn = document.getElementById('rejectModalConfirmBtn');
    const btnText = document.getElementById('rejectModalConfirmBtnText');
    if (btn) btn.disabled = false;
    if (btnText) btnText.textContent = 'Confirm Rejection';

    const modal = document.getElementById('rejectConfirmModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeRejectConfirmModal() {
    const modal = document.getElementById('rejectConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    pendingRejectRefId = null;
}

async function executeRejectRequest() {
    if (!pendingRejectRefId) return;
    const refId = pendingRejectRefId;
    const reasonInput = document.getElementById('rejectReasonInput');
    const reason = reasonInput ? reasonInput.value.trim() : '';

    const btn = document.getElementById('rejectModalConfirmBtn');
    const btnText = document.getElementById('rejectModalConfirmBtnText');
    const originalText = btnText ? btnText.textContent : 'Confirm Rejection';

    try {
        if (btn) btn.disabled = true;
        if (btnText) btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i> Processing...';

        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'reject', reference_no: refId, reason: reason })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            closeRejectConfirmModal();
            closeRequestDrawer();
            showToast('success', `<i class="fa-solid fa-circle-check"></i> ${data.message || 'Request successfully rejected.'}`);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', `<i class="fa-solid fa-circle-exclamation"></i> ${data.message || 'Failed to reject request'}`);
            if (btn) btn.disabled = false;
            if (btnText) btnText.textContent = originalText;
        }
    } catch (err) {
        showToast('error', `<i class="fa-solid fa-triangle-exclamation"></i> Network error connecting to backend.`);
        if (btn) btn.disabled = false;
        if (btnText) btnText.textContent = originalText;
    }
}

function processQuickAction(action, refId) {
    if (action === 'reject') {
        openRejectConfirmModal(refId);
    } else {
        openIssueConfirmModal(refId, action);
    }
}

function actionFromDrawer(action) {
    if (!activeRequestId) return;
    processQuickAction(action, activeRequestId);
}

function exportRequestsCSV() {
    if (!requestsDataset || Object.keys(requestsDataset).length === 0) {
        showToast('error', '<i class="fa-solid fa-circle-exclamation"></i> No requests available to export.');
        return;
    }

    const headers = ['Reference No', 'Requester', 'Citizen ID', 'Certificate Type', 'Purpose', 'Barangay Address', 'Contact', 'Fee Amount', 'Payment Status', 'Status', 'Date Requested'];
    const rows = [headers.join(',')];

    Object.values(requestsDataset).forEach(r => {
        const row = [
            `"${(r.id || '').replace(/"/g, '""')}"`,
            `"${(r.requester || '').replace(/"/g, '""')}"`,
            `"${(r.citizen_id || '').replace(/"/g, '""')}"`,
            `"${(r.cert_type || '').replace(/"/g, '""')}"`,
            `"${(r.purpose || '').replace(/"/g, '""')}"`,
            `"${(r.address || '').replace(/"/g, '""')}"`,
            `"${(r.contact || '').replace(/"/g, '""')}"`,
            `"${(r.fee_amount || '').replace(/"/g, '""')}"`,
            `"${(r.payment_status || '').replace(/"/g, '""')}"`,
            `"${(r.status || '').replace(/"/g, '""')}"`,
            `"${(r.date_requested || '').replace(/"/g, '""')}"`
        ];
        rows.push(row.join(','));
    });

    const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Caloocan_Certificate_Requests_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
}

// Modal dismiss listeners (Backdrop click and Escape key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const issueModal = document.getElementById('issueConfirmModal');
        if (issueModal && !issueModal.classList.contains('hidden')) {
            closeIssueConfirmModal();
            return;
        }
        const rejectModal = document.getElementById('rejectConfirmModal');
        if (rejectModal && !rejectModal.classList.contains('hidden')) {
            closeRejectConfirmModal();
            return;
        }
        closeRequestDrawer();
        closeNewRequestModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    ['requestDetailsDrawer', 'newRequestModal', 'issueConfirmModal', 'rejectConfirmModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    if (id === 'issueConfirmModal') closeIssueConfirmModal();
                    else if (id === 'rejectConfirmModal') closeRejectConfirmModal();
                    else if (id === 'newRequestModal') closeNewRequestModal();
                    else closeRequestDrawer();
                }
            });
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
