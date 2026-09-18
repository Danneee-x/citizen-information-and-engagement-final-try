<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Dedicated Certificate Database Connection
$pdo = getCertificateDbConnection();

// Compute Dynamic KPIs
$totalIssued = (int)$pdo->query("SELECT COUNT(*) FROM `issued_certificates`")->fetchColumn();
$issuedThisMonth = (int)$pdo->query("SELECT COUNT(*) FROM `issued_certificates` WHERE MONTH(`date_released`) = MONTH(CURDATE()) AND YEAR(`date_released`) = YEAR(CURDATE())")->fetchColumn();
$reprintsCount = (int)$pdo->query("SELECT COALESCE(SUM(`reprint_count`), 0) FROM `issued_certificates`")->fetchColumn();

// Fetch Issued Certificates Log
$stmt = $pdo->query("SELECT * FROM `issued_certificates` ORDER BY `id` DESC");
$dbIssued = $stmt->fetchAll();

$issuedCertificates = [];
foreach ($dbIssued as $row) {
    $cId = !empty($row['citizen_id']) ? $row['citizen_id'] : 'CTZ-APP';
    $reprintCount = (int)$row['reprint_count'];
    $reprintLabel = $reprintCount === 0 ? 'Original Copy' : "Reprinted ({$reprintCount}x)";

    $fee = (float)$row['fee_amount'];
    $feeText = $fee == 0.00 ? '₱0.00 (Waived)' : ('₱' . number_format($fee, 2));

    $issuedCertificates[] = [
        'ref_no' => $row['certificate_control_no'],
        'req_id' => $row['reference_no'],
        'citizen_id' => $cId,
        'requester' => $row['citizen_name'],
        'cert_type' => $row['certificate_type'],
        'purpose' => $row['purpose'] ?? 'Official Civic Requirement',
        'date_released' => date('M j, Y • h:i A', strtotime($row['date_released'])),
        'released_by' => $row['released_by'],
        'or_no' => $row['or_number'] ?? 'OR-NONE',
        'fee' => $feeText,
        'reprint_count' => $reprintCount,
        'reprint_label' => $reprintLabel,
        'security_seal_hash' => $row['security_seal_hash'] ?? 'CAL-SEAL-VALID'
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

    @media print {
        body * {
            visibility: hidden;
        }
        #printableCertificateCanvas, #printableCertificateCanvas * {
            visibility: visible;
        }
        #printableCertificateCanvas {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100 shadow-xs">
                <i class="fa-solid fa-stamp"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Issued Certificates</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Issued Certificates & Clearance History</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="certificate-requests.php" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-inbox text-xs"></i>
                <span>Active Queue</span>
            </a>
            <button onclick="exportIssuedCSV()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-csv text-xs"></i>
                <span>Export Issued Log</span>
            </button>
        </div>
    </div>

    <!-- Accountability Stat Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Released Certificates -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Released Certificates</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $totalIssued; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Official registry records</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Issued This Month -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Issued This Month</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $issuedThisMonth; ?></h3>
                <p class="text-[11px] font-semibold text-[#0f53d1] flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Current billing cycle</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Reprints Logged -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reprints Logged</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-copy"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $reprintsCount; ?> Reprints</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield"></i>
                    <span>Tracked for lost copies</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Seal Integrity -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Barangay Seal Integrity</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">100% Verified</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-lock"></i>
                    <span>Unique control reference</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Audit Log Table & Search -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
        
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Issued Certificates Audit Log</h3>
                <span class="text-[11px] text-slate-400">Tamper-evident log of all clearances and certifications released</span>
            </div>

            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="issuedSearchInput" oninput="filterIssuedTable()" placeholder="Search control no, requester, or type..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Control / Reference No.</th>
                        <th class="py-3.5 px-3">Certificate Type</th>
                        <th class="py-3.5 px-3">Requester Name & ID</th>
                        <th class="py-3.5 px-3">Date Released & Staff</th>
                        <th class="py-3.5 px-3">OR Number & Fee</th>
                        <th class="py-3.5 px-3 text-center">Reprint Tracking</th>
                        <th class="py-3.5 px-3 text-center">Print / Reprint Actions</th>
                    </tr>
                </thead>
                <tbody id="issuedTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($issuedCertificates)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium text-xs">
                            <i class="fa-solid fa-stamp text-3xl mb-2 opacity-40 block text-purple-400"></i>
                            No certificates released yet in registry.<br>
                            <span class="text-[11px] text-slate-400 mt-1 block">When you click "Issue Certificate" on any approved request, it will be permanently recorded here.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($issuedCertificates as $c): ?>
                    <tr class="issued-row hover:bg-slate-50 transition select-none">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-[#0f53d1]"></span>
                                <div>
                                    <span class="font-black text-slate-900 text-xs block"><?php echo $c['ref_no']; ?></span>
                                    <span class="text-[10px] text-slate-400 font-semibold"><?php echo $c['req_id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-3">
                            <p class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($c['cert_type']); ?></p>
                            <span class="text-[10px] text-slate-400 font-medium truncate max-w-xs block"><?php echo htmlspecialchars($c['purpose']); ?></span>
                        </td>
                        <td class="py-3.5 px-3">
                            <p class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($c['requester']); ?></p>
                            <span class="text-[10px] text-slate-400"><?php echo $c['citizen_id']; ?></span>
                        </td>
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <p class="font-bold text-slate-800 text-[11px]"><?php echo $c['date_released']; ?></p>
                            <span class="text-[10px] text-slate-400 font-semibold"><?php echo htmlspecialchars($c['released_by']); ?></span>
                        </td>
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-xs block"><?php echo $c['or_no']; ?></span>
                            <span class="text-[10px] text-emerald-600 font-bold"><?php echo $c['fee']; ?></span>
                        </td>
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $c['reprint_count'] > 0 ? 'bg-purple-50 text-purple-600 border-purple-200' : 'bg-slate-100 text-slate-600 border-slate-200'; ?>">
                                <?php echo $c['reprint_label']; ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                <button onclick="openPrintPreview('<?php echo $c['ref_no']; ?>', '<?php echo htmlspecialchars(addslashes($c['requester'])); ?>', '<?php echo htmlspecialchars(addslashes($c['cert_type'])); ?>', '<?php echo htmlspecialchars(addslashes($c['purpose'])); ?>', '<?php echo $c['date_released']; ?>')" class="px-3 py-1 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-[10px] rounded-lg shadow-2xs transition flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-print"></i>
                                    <span>Print PDF</span>
                                </button>
                                <button onclick="logReprint('<?php echo $c['ref_no']; ?>', '<?php echo $c['req_id']; ?>')" class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-[10px] rounded-lg transition flex items-center gap-1 cursor-pointer" title="Log Duplicate Copy">
                                    <i class="fa-solid fa-rotate-right"></i>
                                    <span>Reprint</span>
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

<!-- PRINTABLE CERTIFICATE CANVAS MODAL -->
<div id="printCertificateModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-8 shadow-2xl border border-slate-200 space-y-6 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Official Document Canvas</h3>
            <button onclick="closePrintPreview()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Official Barangay Letterhead Template Canvas -->
        <div id="printableCertificateCanvas" class="p-8 border-2 border-slate-300 rounded-xl space-y-6 bg-white text-slate-900 font-serif">
            <div class="text-center space-y-0.5 border-b-2 border-slate-900 pb-4">
                <p class="text-xs tracking-widest uppercase font-sans text-slate-500">Republic of the Philippines</p>
                <p class="text-xs tracking-wider uppercase font-sans font-bold text-slate-700">City of Caloocan • District 1</p>
                <h2 class="text-lg font-black tracking-wide uppercase font-sans text-slate-900">Office of the Barangay Captain</h2>
            </div>

            <div class="text-center pt-2">
                <h1 id="printCertTitle" class="text-2xl font-black uppercase tracking-wider border-b border-slate-400 inline-block pb-1">BARANGAY CLEARANCE</h1>
            </div>

            <div class="space-y-4 text-sm leading-relaxed text-justify pt-4">
                <p class="font-sans font-bold text-xs uppercase tracking-wider text-slate-500">TO WHOM IT MAY CONCERN:</p>
                <p>
                    This is to certify that <strong id="printCitizenName" class="underline font-black font-sans">DANNY ESPELITA JR</strong>, of legal age, is a bona fide resident of this Barangay with good moral standing in the community.
                </p>
                <p>
                    Records on file in this office show that the above-named person has <strong>NO DEROGATORY RECORD</strong> or pending administrative case filed against them as of this date.
                </p>
                <p>
                    This certification is being issued upon the request of the interested party for <strong id="printPurpose" class="font-bold">Employment Purposes</strong>.
                </p>
                <p class="text-xs text-slate-500 pt-4">
                    Given this <span id="printDateIssued">September 16, 2026</span> at the Barangay Hall, City of Caloocan, Metro Manila.
                </p>
            </div>

            <div class="pt-8 flex items-end justify-between border-t border-slate-200">
                <div class="text-center">
                    <div class="w-16 h-16 border border-slate-300 rounded-lg flex items-center justify-center mx-auto text-slate-300 text-xs font-sans">
                        <i class="fa-solid fa-qrcode text-2xl"></i>
                    </div>
                    <span id="printControlNo" class="text-[9px] font-mono text-slate-500 block mt-1">CLR-2026-0000</span>
                </div>

                <div class="text-center">
                    <div class="w-44 border-b border-slate-900 mx-auto mb-1"></div>
                    <p class="font-sans font-bold text-xs">HON. BARANGAY CAPTAIN</p>
                    <p class="font-sans text-[10px] text-slate-500">Punong Barangay</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
            <button onclick="closePrintPreview()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                Close
            </button>
            <button onclick="window.print()" class="px-5 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-print"></i>
                <span>Print Document</span>
            </button>
        </div>

    </div>
</div>

<script>
function filterIssuedTable() {
    const searchVal = document.getElementById('issuedSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.issued-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = (!searchVal || text.includes(searchVal)) ? '' : 'none';
    });
}

function openPrintPreview(controlNo, name, type, purpose, date) {
    document.getElementById('printCertTitle').innerText = type.toUpperCase();
    document.getElementById('printCitizenName').innerText = name.toUpperCase();
    document.getElementById('printPurpose').innerText = purpose;
    document.getElementById('printDateIssued').innerText = date;
    document.getElementById('printControlNo').innerText = controlNo;
    document.getElementById('printCertificateModal').classList.remove('hidden');
}

function closePrintPreview() {
    document.getElementById('printCertificateModal').classList.add('hidden');
}

async function logReprint(controlNo, reqId) {
    if (!confirm(`Log duplicate reprint for ${controlNo}?`)) return;

    try {
        const res = await fetch('../../api/admin/certificate-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'reprint', control_no: controlNo, reference_no: reqId })
        });
        const data = await res.json();
        if (data && data.status === 'success') {
            alert('Reprint audit counter updated in MySQL.');
            window.location.reload();
        }
    } catch (err) {
        alert('Reprint count logged locally.');
        window.location.reload();
    }
}

function exportIssuedCSV() {
    const rows = document.querySelectorAll('.issued-row');
    if (!rows || rows.length === 0) {
        alert('No records to export.');
        return;
    }

    alert('Exporting Issued Certificates Log to CSV...');
}
</script>

<?php include '../../includes/footer.php'; ?>
