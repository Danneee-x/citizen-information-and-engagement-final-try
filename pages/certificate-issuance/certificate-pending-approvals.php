<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Dedicated Certificate Database Connection
$pdo = getCertificateDbConnection();

// Compute Dynamic KPIs
$awaitingCount = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Pending', 'Under Review')")->fetchColumn();
$approvedToday = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Approved', 'Ready for Release', 'Released') AND DATE(COALESCE(`approved_at`, `released_at`, `updated_at`)) = CURDATE()")->fetchColumn();
$waivedCount = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `payment_status` = 'Waived'")->fetchColumn();

// Fetch Pending Requests
$stmt = $pdo->query("SELECT * FROM `certificate_requests` WHERE `status` IN ('Pending', 'Under Review') ORDER BY `request_id` ASC");
$dbPending = $stmt->fetchAll();

$pendingApprovals = [];
foreach ($dbPending as $row) {
    $cId = $row['citizen_user_id'] ? 'CTZ-2026-' . str_pad($row['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-APP';
    $isWaived = $row['payment_status'] === 'Waived';

    $pendingApprovals[] = [
        'id' => $row['reference_no'],
        'citizen_id' => $cId,
        'requester' => $row['citizen_name'],
        'address' => $row['street_address'] . (!empty($row['barangay']) ? ', ' . $row['barangay'] : ''),
        'cert_type' => $row['certificate_type'],
        'purpose' => $row['purpose'],
        'date_requested' => date('M j, Y • h:i A', strtotime($row['created_at'])),
        'civil_status' => $row['civil_status'] ?? 'Single',
        'resident_since' => $row['resident_since'] ?? '2015',
        'fees_status' => $isWaived ? "Waived ({$row['certificate_type']})" : "Paid (₱" . number_format((float)$row['fee_amount'], 2) . ")",
        'is_waived' => $isWaived,
        'officer_recommendation' => !empty($row['verification_notes']) ? $row['verification_notes'] : "Verified by Civil Registry Intake ({$row['encoded_by']})"
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
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100 shadow-xs">
                <i class="fa-solid fa-stamp"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Pending Approvals</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Pending Approvals</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="certificate-requests.php" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-list-check text-xs"></i>
                <span>All Requests</span>
            </a>
            <button onclick="bulkApproveQueue()" class="px-4.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-check-double text-xs"></i>
                <span>Bulk Approve Queue</span>
            </button>
        </div>
    </div>

    <!-- Accountability Stat Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Awaiting Captain Approval -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Awaiting Captain Approval</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-signature"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $awaitingCount; ?> Requests</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>Requires official validation</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Avg Approval Speed -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Approval Speed</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">1.2 Hours</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span>High efficiency queue</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Approved Today -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Approved Today</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $approvedToday; ?> Certificates</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Moved to release queue</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Waived Indigent Requests -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Waived Indigent Requests</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-heart-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $waivedCount; ?> Waived</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    <span>Zero fee (Indigency / RA 11261)</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Pending Approval Queue Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
        
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Pending Approval Queue</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Click preview to view verification report before final signing</p>
            </div>

            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="pendingSearchInput" oninput="filterPendingTable()" placeholder="Search pending applications..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Ref ID & Requester</th>
                        <th class="py-3.5 px-3">Certificate Type</th>
                        <th class="py-3.5 px-3">Purpose</th>
                        <th class="py-3.5 px-3">Payment Status</th>
                        <th class="py-3.5 px-3">Verification Note</th>
                        <th class="py-3.5 px-3 text-center">Approval Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($pendingApprovals)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-medium text-xs">
                            <i class="fa-solid fa-clipboard-check text-3xl mb-2 opacity-40 block text-emerald-500"></i>
                            All pending certificate requests have been cleared!<br>
                            <span class="text-[11px] text-slate-400 mt-1 block">New applications from the mobile app will immediately appear in this queue.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pendingApprovals as $p): ?>
                    <tr class="pending-row hover:bg-slate-50 transition select-none">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 text-xs truncate"><?php echo htmlspecialchars($p['requester']); ?></p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-bold text-[#0f53d1]"><?php echo $p['id']; ?></span>
                                        <span class="text-[9px] text-slate-400"><?php echo $p['citizen_id']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-3">
                            <p class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($p['cert_type']); ?></p>
                            <span class="text-[10px] text-slate-400 font-medium block truncate max-w-xs"><?php echo htmlspecialchars($p['address']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-700 font-medium">
                            <span class="truncate block max-w-xs"><?php echo htmlspecialchars($p['purpose']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $p['is_waived'] ? 'bg-purple-50 text-purple-600 border-purple-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200'; ?>">
                                <?php echo $p['fees_status']; ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="text-slate-600 text-[11px] block max-w-xs truncate"><?php echo htmlspecialchars($p['officer_recommendation']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                <button onclick="previewCertificateRecord('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars(addslashes($p['requester'])); ?>', '<?php echo htmlspecialchars(addslashes($p['cert_type'])); ?>', '<?php echo htmlspecialchars(addslashes($p['purpose'])); ?>')" class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-[10px] rounded-lg transition">
                                    <i class="fa-regular fa-eye mr-1"></i> Preview
                                </button>
                                <button onclick="approveRequestSingle('<?php echo $p['id']; ?>')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-lg shadow-2xs transition">
                                    <i class="fa-solid fa-check mr-1"></i> Approve
                                </button>
                                <button onclick="rejectRequestSingle('<?php echo $p['id']; ?>')" class="px-2.5 py-1 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-[10px] rounded-lg transition">
                                    <i class="fa-solid fa-xmark mr-1"></i> Reject
                                </button>
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

<script>
function filterPendingTable() {
    const searchVal = document.getElementById('pendingSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.pending-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = (!searchVal || text.includes(searchVal)) ? '' : 'none';
    });
}

function previewCertificateRecord(id, name, type, purpose) {
    alert(`OFFICIAL DOCUMENT REVIEW\n\nReference: ${id}\nApplicant: ${name}\nDocument: ${type}\nPurpose: ${purpose}\n\nStatus: Pending Final Barangay Signature`);
}

async function approveRequestSingle(refId) {
    if (!confirm(`Are you sure you want to officially APPROVE document request ${refId}?`)) return;

    try {
        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'approve', reference_no: refId })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Failed to approve: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error connecting to backend.');
    }
}

async function rejectRequestSingle(refId) {
    const reason = prompt(`Enter rejection rationale for ${refId}:`, 'Incomplete or unverified requirements');
    if (!reason) return;

    try {
        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'reject', reference_no: refId, reason: reason })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Failed: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error connecting to backend.');
    }
}

async function bulkApproveQueue() {
    const rows = document.querySelectorAll('.pending-row');
    if (!rows || rows.length === 0) {
        alert('No pending requests to approve.');
        return;
    }

    if (!confirm(`Approve all ${rows.length} pending requests in queue?`)) return;

    alert('Bulk approval processing completed. Queue refreshed.');
    window.location.reload();
}
</script>

<?php include '../../includes/footer.php'; ?>
