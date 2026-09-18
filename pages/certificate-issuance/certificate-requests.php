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
                $docList[] = is_array($d) ? ($d['name'] ?? 'Supporting Document') : (string)$d;
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

    <!-- Main Grid Layout (Table Container + Right Side Drawer) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Table Column -->
        <div id="requestTableContainer" class="lg:col-span-12 space-y-4 transition-all duration-300">

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
                                        <?php if ($req['status'] === 'Pending' || $req['status'] === 'Under Review'): ?>
                                        <button onclick="processQuickAction('approve', '<?php echo $req['id']; ?>')" class="w-7 h-7 rounded-lg hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 flex items-center justify-center transition cursor-pointer" title="Approve Request"><i class="fa-solid fa-check text-xs"></i></button>
                                        <?php elseif ($req['status'] === 'Ready for Release' || $req['status'] === 'Approved'): ?>
                                        <button onclick="processQuickAction('release', '<?php echo $req['id']; ?>')" class="w-7 h-7 rounded-lg hover:bg-purple-50 text-slate-400 hover:text-purple-600 flex items-center justify-center transition cursor-pointer" title="Issue & Release Certificate"><i class="fa-solid fa-stamp text-xs"></i></button>
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

        </div>

        <!-- Right Side Inspector Drawer: Request Details & Attached Documents -->
        <div id="requestDetailsDrawer" class="hidden lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-5 sticky top-6">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span id="drawerReqId" class="text-xs font-bold text-[#0f53d1]">CAL-DOC-2026-0000</span>
                    <span id="drawerReqStatus" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 text-amber-600 border border-amber-200">Pending</span>
                </div>
                <button onclick="closeRequestDrawer()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Requester Details -->
            <div class="space-y-3">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Requester Profile</span>
                    <h3 id="drawerRequesterName" class="text-sm font-black text-slate-900 leading-snug">Danny Espelita Jr</h3>
                    <p id="drawerCitizenId" class="text-xs text-[#0f53d1] font-bold">CTZ-2026-0001</p>
                    <p id="drawerRequesterAddress" class="text-xs text-slate-500 font-medium mt-1">Barangay 171, Caloocan City</p>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 text-xs">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-medium">Requested Certificate</span>
                        <span id="drawerCertType" class="font-bold text-slate-800">Barangay Clearance</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-medium">Purpose</span>
                        <span id="drawerPurpose" class="font-bold text-slate-800 truncate max-w-[170px]">Employment</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-medium">Fee / Amount</span>
                        <span id="drawerFee" class="font-bold text-emerald-600">₱50.00</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-medium">Payment Status</span>
                        <span id="drawerPaymentStatus" class="font-bold text-slate-800">Pending</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-medium">Contact Phone</span>
                        <span id="drawerContact" class="font-bold text-slate-800">09171234567</span>
                    </div>
                </div>
            </div>

            <!-- Uploaded Supporting Documents Section -->
            <div class="space-y-3 border-t border-slate-100 pt-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Documents</span>
                <div id="drawerDocsList" class="space-y-2">
                    <span class="text-xs text-slate-400 italic">No attachments provided</span>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="space-y-2 border-t border-slate-100 pt-4">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="actionFromDrawer('approve')" class="flex-1 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Approve</span>
                    </button>

                    <button type="button" onclick="actionFromDrawer('release')" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-stamp text-xs"></i>
                        <span>Issue Certificate</span>
                    </button>
                </div>

                <button type="button" onclick="actionFromDrawer('reject')" class="w-full py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-xmark text-xs"></i>
                    <span>Reject Request</span>
                </button>
            </div>

        </div>

    </div>

</main>

<!-- NEW REQUEST ENCODING MODAL (Connected to Backend Database) -->
<div id="newRequestModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
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
            docsList.innerHTML += `
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2 truncate">
                        <i class="fa-solid fa-file-lines text-blue-500 text-sm shrink-0"></i>
                        <span class="font-bold text-slate-800 text-[11px] truncate">${doc}</span>
                    </div>
                </div>
            `;
        });
    } else {
        docsList.innerHTML = '<span class="text-xs text-slate-400 italic">No attachments provided</span>';
    }

    if (drawer) {
        drawer.classList.remove('hidden');
        tableContainer.className = "lg:col-span-8 space-y-4 transition-all duration-300";
    }
}

function closeRequestDrawer() {
    activeRequestId = null;
    document.querySelectorAll('.request-row').forEach(r => r.classList.remove('bg-blue-50/40'));
    const drawer = document.getElementById('requestDetailsDrawer');
    const tableContainer = document.getElementById('requestTableContainer');
    if (drawer) drawer.classList.add('hidden');
    if (tableContainer) tableContainer.className = "lg:col-span-12 space-y-4 transition-all duration-300";
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
        alert('Please fill out all required fields marked with *');
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

async function processQuickAction(action, refId) {
    const label = action === 'approve' ? 'Approve & Mark Ready for Release' : (action === 'release' ? 'Issue & Release Certificate' : action);
    if (!confirm(`Are you sure you want to ${label} for request ${refId}?`)) return;

    try {
        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: action, reference_no: refId })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Failed: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Error connecting to backend.');
    }
}

function actionFromDrawer(action) {
    if (!activeRequestId) return;
    processQuickAction(action, activeRequestId);
}

function exportRequestsCSV() {
    if (!requestsDataset || Object.keys(requestsDataset).length === 0) {
        alert('No requests available to export.');
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
</script>

<?php include '../../includes/footer.php'; ?>
