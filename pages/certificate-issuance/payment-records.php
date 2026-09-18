<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Connect to dedicated certificate database
try {
    $pdo = getCertificateDbConnection();
} catch (Exception $e) {
    die("<div class='p-6 text-red-600 font-bold'>Database Connection Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Configurable Fee Schedule per Certificate Type
$feeSchedule = [
    'Barangay Clearance' => 50.00,
    'Certificate of Residency' => 30.00,
    'Certificate of Indigency' => 0.00, // Waived
    'Business Permit Clearance' => 200.00,
    'Certificate of Good Moral Character' => 50.00,
    'First-Time Jobseeker Certificate (RA 11261)' => 0.00 // Waived under RA 11261
];

// Fetch live payment records from certificate_payments
$payments = [];
try {
    $stmt = $pdo->query("SELECT * FROM `certificate_payments` ORDER BY `payment_date` DESC, `payment_id` DESC");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $payments = [];
}

// Also check certificate_requests for any pending payments awaiting OR collection
try {
    $pendingReqs = $pdo->query("SELECT * FROM `certificate_requests` WHERE `payment_status` = 'Pending' AND `status` NOT IN ('Rejected') ORDER BY `created_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pendingReqs as $pReq) {
        // Check if already in payments
        $alreadyIn = false;
        foreach ($payments as $existingP) {
            if ($existingP['reference_no'] === $pReq['reference_no']) {
                $alreadyIn = true;
                break;
            }
        }
        if (!$alreadyIn && (float)$pReq['fee_amount'] > 0) {
            $payments[] = [
                'payment_id' => 'REQ-' . $pReq['request_id'],
                'or_number' => !empty($pReq['or_number']) ? $pReq['or_number'] : 'PENDING-OR',
                'reference_no' => $pReq['reference_no'],
                'citizen_name' => $pReq['citizen_name'],
                'certificate_type' => $pReq['certificate_type'],
                'amount_due' => (float)$pReq['fee_amount'],
                'amount_paid' => 0.00,
                'payment_status' => 'Pending',
                'cashier_name' => 'Awaiting Cashier Collection',
                'payment_date' => $pReq['created_at']
            ];
        }
    }
} catch (Exception $e) {
    // Ignore if query fails
}

// Compute live KPIs
$todayRevenue = 0.00;
$monthlyRevenue = 0.00;
$waivedTotal = 0.00;
$waivedCount = 0;
$pendingTotal = 0.00;
$pendingCount = 0;

try {
    // Today's revenue
    $stmtToday = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM `certificate_payments` WHERE `payment_status` = 'Paid' AND DATE(`payment_date`) = CURDATE()");
    $todayRevenue = (float)$stmtToday->fetchColumn();

    // Monthly revenue
    $stmtMonth = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM `certificate_payments` WHERE `payment_status` = 'Paid' AND MONTH(`payment_date`) = MONTH(CURDATE()) AND YEAR(`payment_date`) = YEAR(CURDATE())");
    $monthlyRevenue = (float)$stmtMonth->fetchColumn();

    // Total fees waived
    $stmtWaived = $pdo->query("SELECT COALESCE(SUM(amount_due), 0) as s, COUNT(*) as c FROM `certificate_payments` WHERE `payment_status` = 'Waived'");
    $waivedRow = $stmtWaived->fetch(PDO::FETCH_ASSOC);
    $waivedTotal = (float)($waivedRow['s'] ?? 0);
    $waivedCount = (int)($waivedRow['c'] ?? 0);

    // Pending collections
    $stmtPending = $pdo->query("SELECT COALESCE(SUM(fee_amount), 0) as s, COUNT(*) as c FROM `certificate_requests` WHERE `payment_status` = 'Pending' AND `status` NOT IN ('Rejected')");
    $pendingRow = $stmtPending->fetch(PDO::FETCH_ASSOC);
    $pendingTotal = (float)($pendingRow['s'] ?? 0);
    $pendingCount = (int)($pendingRow['c'] ?? 0);
} catch (Exception $e) {
    // defaults
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
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100 shadow-xs">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Payment Records</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Payment Records & Revenue Summary</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="openFeeScheduleModal()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-sliders text-slate-400"></i>
                <span>Configure Fee Schedule</span>
            </button>

            <button onclick="exportTreasurerReport()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-csv text-xs"></i>
                <span>Export Financial Report</span>
            </button>
        </div>
    </div>

    <!-- Daily & Monthly Revenue Summary KPI Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Today's Revenue -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱<?php echo number_format($todayRevenue, 2); ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>Collected today by Cashier</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Monthly Total Revenue -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Monthly Collections</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱<?php echo number_format($monthlyRevenue, 2); ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>For Treasurer Reconciliation</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Waived Indigent / RA 11261 -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Fees Waived</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱<?php echo number_format($waivedTotal, 2); ?></h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield-heart"></i>
                    <span><?php echo $waivedCount; ?> Indigent & RA 11261 Exemptions</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Pending OR Payments -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Payments</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱<?php echo number_format($pendingTotal, 2); ?></h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-receipt"></i>
                    <span><?php echo $pendingCount; ?> Awaiting OR issuing</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Transactions Log Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
        
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Payment Transactions Directory</h3>

            <div class="flex items-center gap-3">
                <select id="paymentStatusFilter" onchange="filterPaymentTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-3 text-xs outline-none cursor-pointer">
                    <option value="">All Payment Statuses</option>
                    <option value="Paid">Paid</option>
                    <option value="Waived">Waived</option>
                    <option value="Pending">Pending</option>
                </select>

                <div class="relative w-full md:w-64">
                    <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="paymentSearchInput" oninput="filterPaymentTable()" placeholder="Search OR No., Requester..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Official Receipt (OR) No.</th>
                        <th class="py-3.5 px-3">Control Ref & Requester</th>
                        <th class="py-3.5 px-3">Certificate Type</th>
                        <th class="py-3.5 px-3">Amount Paid / Fee</th>
                        <th class="py-3.5 px-3 text-center">Payment Status</th>
                        <th class="py-3.5 px-3">Date & Cashier</th>
                        <th class="py-3.5 px-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-2 text-slate-300">
                                <i class="fa-solid fa-receipt text-xl"></i>
                            </div>
                            <p class="font-bold text-slate-600">No payment transactions recorded yet.</p>
                            <p class="text-[11px] text-slate-400 mt-1">Live payments will appear here as certificate requests are approved and issued.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $pay): 
                        $status = $pay['payment_status'];
                        $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                        if (stripos($status, 'Waived') !== false) {
                            $statusClass = 'bg-purple-50 text-purple-600 border-purple-200';
                        } else if ($status === 'Pending') {
                            $statusClass = 'bg-amber-50 text-amber-600 border-amber-200';
                        }
                        $dateFormatted = date('M j, Y • h:i A', strtotime($pay['payment_date']));
                    ?>
                    <tr class="payment-row hover:bg-slate-50 transition cursor-pointer select-none" data-status="<?php echo htmlspecialchars($pay['payment_status']); ?>">
                        <td class="py-3.5 px-4 font-black text-slate-900 whitespace-nowrap">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-receipt text-xs <?php echo stripos($status, 'Waived') !== false ? 'text-purple-600' : ($status === 'Pending' ? 'text-amber-500' : 'text-emerald-600'); ?>"></i> 
                                <?php echo htmlspecialchars($pay['or_number']); ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            <p class="font-bold text-slate-900"><?php echo htmlspecialchars($pay['citizen_name']); ?></p>
                            <p class="text-[10px] text-[#0f53d1] font-semibold"><?php echo htmlspecialchars($pay['reference_no']); ?></p>
                        </td>
                        <td class="py-3.5 px-3 font-bold text-slate-900">
                            <?php echo htmlspecialchars($pay['certificate_type']); ?>
                        </td>
                        <td class="py-3.5 px-3 font-black <?php echo (float)$pay['amount_paid'] > 0 ? 'text-emerald-600' : 'text-slate-500'; ?> whitespace-nowrap">
                            ₱<?php echo number_format((float)$pay['amount_paid'], 2); ?>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($pay['payment_status']); ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <p class="font-bold text-slate-800 text-[11px]"><?php echo $dateFormatted; ?></p>
                            <p class="text-[10px] text-slate-400 font-semibold"><?php echo htmlspecialchars($pay['cashier_name']); ?></p>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <button onclick="printReceipt('<?php echo htmlspecialchars($pay['or_number']); ?>', '<?php echo htmlspecialchars($pay['reference_no']); ?>', '<?php echo htmlspecialchars(addslashes($pay['citizen_name'])); ?>', '<?php echo number_format((float)$pay['amount_paid'], 2); ?>')" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-emerald-600 flex items-center justify-center transition mx-auto cursor-pointer" title="Print OR Receipt">
                                <i class="fa-solid fa-print text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</main>

<!-- CONFIGURE FEE SCHEDULE MODAL -->
<div id="feeScheduleModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="text-sm font-black text-slate-900">Certificate Fee Schedule (Settings)</h3>
            </div>
            <button onclick="closeFeeScheduleModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <p class="text-slate-500 font-medium">Configure standard fees per certificate type. Fee exemption applies automatically for Indigents and RA 11261.</p>

            <div class="space-y-2.5">
                <?php foreach ($feeSchedule as $type => $fee): ?>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                    <span class="font-bold text-slate-800 text-xs"><?php echo htmlspecialchars($type); ?></span>
                    <div class="flex items-center gap-1">
                        <span class="font-bold text-slate-400">₱</span>
                        <input type="number" step="10" value="<?php echo number_format($fee, 2, '.', ''); ?>" class="w-24 bg-white border border-slate-200 text-slate-800 font-bold rounded-lg p-1.5 text-xs text-right outline-none">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button onclick="closeFeeScheduleModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="saveFeeSchedule()" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-xs cursor-pointer">Save Fee Schedule</button>
        </div>
    </div>
</div>

<script>
function openFeeScheduleModal() {
    document.getElementById('feeScheduleModal').classList.remove('hidden');
}

function closeFeeScheduleModal() {
    document.getElementById('feeScheduleModal').classList.add('hidden');
}

function saveFeeSchedule() {
    alert('Certificate fee schedule updated successfully!');
    closeFeeScheduleModal();
}

function filterPaymentTable() {
    const searchVal = document.getElementById('paymentSearchInput').value.toLowerCase();
    const statusVal = document.getElementById('paymentStatusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.payment-row');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const status = (r.getAttribute('data-status') || '').toLowerCase();
        r.style.display = (!searchVal || text.includes(searchVal)) && (!statusVal || status.includes(statusVal)) ? '' : 'none';
    });
}

function printReceipt(orNo, refNo, requester, amount) {
    const printWindow = window.open('', '_blank', 'width=600,height=700');
    printWindow.document.write(`
        <html>
        <head>
            <title>Official Receipt - ${orNo}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1e293b; }
                .receipt-box { border: 2px dashed #94a3b8; padding: 25px; border-radius: 12px; }
                .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
                .header h2 { margin: 0; color: #0f172a; font-size: 18px; text-transform: uppercase; }
                .header p { margin: 4px 0 0; color: #64748b; font-size: 12px; }
                .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #e2e8f0; font-size: 13px; }
                .total-row { display: flex; justify-content: space-between; padding: 15px 0 0; font-size: 16px; font-weight: bold; color: #0f53d1; }
                .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class="receipt-box">
                <div class="header">
                    <h2>Republic of the Philippines</h2>
                    <p>Barangay Civil Registry & Treasury Office</p>
                    <p><strong>OFFICIAL RECEIPT / PAYMENT CERTIFICATION</strong></p>
                </div>
                <div class="item-row"><span>Official Receipt (OR) No.:</span><strong>${orNo}</strong></div>
                <div class="item-row"><span>Reference Control No.:</span><strong>${refNo}</strong></div>
                <div class="item-row"><span>Payor / Citizen:</span><strong>${requester}</strong></div>
                <div class="item-row"><span>Date Issued:</span><span>${new Date().toLocaleString()}</span></div>
                <div class="total-row"><span>Total Amount Paid:</span><span>₱${amount}</span></div>
                <div class="footer">
                    <p>Barangay Official Treasury Electronic Record</p>
                    <p>This serves as an authentic payment confirmation for certificate issuance.</p>
                </div>
            </div>
            <script>
                window.onload = function() { window.print(); };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function exportTreasurerReport() {
    const rows = document.querySelectorAll('.payment-row');
    if (!rows.length) {
        alert('No payment records to export.');
        return;
    }
    let csv = 'OR Number,Reference No,Requester,Certificate Type,Amount Paid,Status,Date\n';
    rows.forEach(r => {
        const cols = r.querySelectorAll('td');
        if (cols.length >= 6) {
            const orNo = cols[0].innerText.replace(/\s+/g, ' ').trim();
            const req = cols[1].querySelector('p:first-child')?.innerText.trim() || '';
            const ref = cols[1].querySelector('p:last-child')?.innerText.trim() || '';
            const type = cols[2].innerText.trim();
            const amount = cols[3].innerText.replace('₱', '').trim();
            const status = cols[4].innerText.trim();
            const date = cols[5].querySelector('p:first-child')?.innerText.trim() || '';
            csv += `"${orNo}","${ref}","${req}","${type}","${amount}","${status}","${date}"\n`;
        }
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Treasurer_Payment_Records_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../../includes/footer.php'; ?>
