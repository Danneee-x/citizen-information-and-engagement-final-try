<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Dedicated Certificate Database Connection
$pdo = getCertificateDbConnection();

// Compute Dynamic KPIs
$awaitingCount = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Pending', 'Pending Review', 'Under Review')")->fetchColumn();
$approvedToday = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Approved', 'Ready to Print', 'Ready for Release', 'Released') AND DATE(COALESCE(`approved_at`, `released_at`, `updated_at`)) = CURDATE()")->fetchColumn();
$waivedCount = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `payment_status` = 'Waived'")->fetchColumn();

// Fetch Pending Requests
$stmt = $pdo->query("SELECT * FROM `certificate_requests` WHERE `status` IN ('Pending', 'Pending Review', 'Under Review') ORDER BY `request_id` ASC");
$dbPending = $stmt->fetchAll();

$pendingApprovals = [];
foreach ($dbPending as $row) {
    $cId = $row['citizen_user_id'] ? 'CTZ-2026-' . str_pad($row['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-APP';
    $isWaived = $row['payment_status'] === 'Waived';

    $docList = [];
    if (!empty($row['uploaded_documents'])) {
        $decoded = json_decode($row['uploaded_documents'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $d) {
                $docList[] = is_array($d) ? ($d['name'] ?? 'Supporting Document') : (string)$d;
            }
        }
    }

    $pendingApprovals[] = [
        'id' => $row['reference_no'],
        'citizen_id' => $cId,
        'requester' => $row['citizen_name'],
        'address' => $row['street_address'] . (!empty($row['barangay']) ? ', ' . $row['barangay'] : ''),
        'contact' => $row['contact_number'] ?? '0917-000-0000',
        'cert_type' => $row['certificate_type'],
        'purpose' => $row['purpose'],
        'purpose_details' => $row['purpose_details'] ?? '',
        'date_requested' => date('M j, Y • h:i A', strtotime($row['created_at'])),
        'civil_status' => $row['civil_status'] ?? 'Single',
        'resident_since' => $row['resident_since'] ?? '2015',
        'fee_amount' => number_format((float)$row['fee_amount'], 2),
        'fees_status' => $isWaived ? "Waived ({$row['certificate_type']})" : "Paid (₱" . number_format((float)$row['fee_amount'], 2) . ")",
        'is_waived' => $isWaived,
        'officer_recommendation' => !empty($row['verification_notes']) ? $row['verification_notes'] : "Verified by Civil Registry Intake (" . ($row['encoded_by'] ?? 'Citizen Mobile App') . ")",
        'docs' => $docList,
        'status' => $row['status']
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
                    <tr class="pending-row hover:bg-blue-50/40 transition select-none cursor-pointer" onclick="openCertificateReviewModal('<?php echo $p['id']; ?>')">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 text-xs truncate hover:text-[#0f53d1] transition"><?php echo htmlspecialchars($p['requester']); ?></p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-bold text-[#0f53d1]"><?php echo $p['id']; ?></span>
                                        <span class="text-[9px] text-slate-400"><?php echo $p['citizen_id']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-3">
                            <p class="font-bold text-slate-900 text-xs hover:text-[#0f53d1] transition"><?php echo htmlspecialchars($p['cert_type']); ?></p>
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
                        <td class="py-3.5 px-3 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" onclick="event.stopPropagation(); openCertificateReviewModal('<?php echo $p['id']; ?>')" class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-[10px] rounded-lg transition cursor-pointer" title="View Document Review Modal">
                                    <i class="fa-regular fa-eye mr-1"></i> Preview
                                </button>
                                <button type="button" onclick="event.stopPropagation(); approveRequestSingle('<?php echo $p['id']; ?>')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-lg shadow-2xs transition cursor-pointer">
                                    <i class="fa-solid fa-check mr-1"></i> Approve
                                </button>
                                <button type="button" onclick="event.stopPropagation(); rejectRequestSingle('<?php echo $p['id']; ?>')" class="px-2.5 py-1 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-[10px] rounded-lg transition cursor-pointer">
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


<!-- PRINTABLE / PREVIEW CERTIFICATE CANVAS MODAL -->
<div id="previewCertificateModal" class="hidden fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Top Bar -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white shrink-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeCertificatePreview()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer text-xs" title="Back to Pending Queue">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Certificate Review & Verification</h3>
                        <span id="previewRefBadge" class="text-xs font-mono font-bold text-[#0f53d1] bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">CAL-DOC-2026-0000</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium">Verify citizen records and document preview prior to official authorization</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-amber-50 text-amber-600 border border-amber-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Pending Final Approval</span>
                </span>
                <button type="button" onclick="closeCertificatePreview()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Modal Content -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5 bg-slate-50/50">
            
            <!-- Applicant & Verification Dossier Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Applicant & Request Dossier</h4>
                    </div>
                    <span id="previewCitizenIdBadge" class="text-xs text-[#0f53d1] font-mono font-bold bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-100">CTZ-2026-0000</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Applicant Legal Name</span>
                        <p id="previewApplicantName" class="text-sm font-black text-slate-900">Juan Dela Cruz</p>
                        <p id="previewApplicantAddress" class="text-xs text-slate-500 font-medium">Barangay Commonwealth, Quezon City</p>
                    </div>

                    <div class="space-y-1 md:text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Application Date & Contact</span>
                        <p id="previewDateRequested" class="text-xs font-bold text-slate-800">Oct 06, 2026 • 10:30 AM</p>
                        <p id="previewApplicantContact" class="text-xs text-slate-500 font-medium font-mono">0917-000-0000</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Certificate</span>
                        <span id="previewCertTypeInfo" class="font-bold text-slate-800 text-xs truncate block">Barangay Clearance</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Purpose</span>
                        <span id="previewPurposeInfo" class="font-bold text-slate-800 text-xs truncate block">Employment</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Civil Status</span>
                        <span id="previewCivilStatusInfo" class="font-bold text-slate-800 text-xs truncate block">Single</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Resident Since</span>
                        <span id="previewResidentSinceInfo" class="font-bold text-slate-800 text-xs truncate block">2015</span>
                    </div>
                </div>

                <!-- Payment Status & Officer Recommendation Note -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Payment / Fee:</span>
                        <span id="previewFeeStatusBadge" class="px-2.5 py-0.5 rounded-full font-bold text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-200">
                            Paid (₱75.00)
                        </span>
                    </div>
                    <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200/60 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-amber-600 text-xs shrink-0"></i>
                        <div class="min-w-0 text-xs">
                            <span class="text-[10px] font-bold text-amber-700 uppercase block">Intake Verification Note:</span>
                            <span id="previewOfficerNote" class="text-slate-700 font-medium truncate block text-[11px]">Verified by Civil Registry Intake</span>
                        </div>
                    </div>
                </div>

                <!-- Attached Supporting Documents (if any) -->
                <div id="previewDocsContainer" class="pt-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1.5">Attached Supporting Documents</span>
                    <div id="previewDocsList" class="flex flex-wrap gap-2">
                        <span class="text-xs text-slate-400 italic">No attachments required</span>
                    </div>
                </div>
            </div>

            <!-- Official Barangay Letterhead Template Canvas -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-amber-600"></i>
                        <span>Official Document Authorization Preview</span>
                    </span>
                    <span class="text-[10px] font-semibold text-slate-400">Live preview prior to release</span>
                </div>

                <div class="p-6 md:p-8 border-2 border-slate-300 rounded-xl space-y-5 bg-white text-slate-900 font-serif shadow-xs">
                    <div class="text-center space-y-0.5 border-b-2 border-slate-900 pb-4">
                        <p class="text-[11px] tracking-widest uppercase font-sans text-slate-500">Republic of the Philippines</p>
                        <p class="text-xs tracking-wider uppercase font-sans font-bold text-slate-700">City of Caloocan • District 1</p>
                        <h2 class="text-base md:text-lg font-black tracking-wide uppercase font-sans text-slate-900">Office of the Punong Barangay</h2>
                    </div>

                    <div class="text-center pt-2">
                        <h1 id="previewCertTitle" class="text-xl md:text-2xl font-black uppercase tracking-wider border-b-2 border-slate-900 inline-block pb-1 font-sans">BARANGAY CLEARANCE</h1>
                    </div>

                    <div class="space-y-4 text-xs md:text-sm leading-relaxed text-justify pt-3">
                        <p class="font-sans font-bold text-xs uppercase tracking-wider text-slate-600">TO WHOM IT MAY CONCERN:</p>
                        <p id="previewCertParagraph1">
                            This is to officially certify that <strong id="previewCitizenName" class="underline font-black font-sans uppercase">CITIZEN NAME</strong>, of legal age, <span id="previewCivilStatusText">Single</span>, is a bona fide resident of this Barangay residing at <strong id="previewAddressText">0025 Kasunduan Street</strong> with good moral standing in the community.
                        </p>
                        <p id="previewCertParagraph2">
                            Records on file in this office show that the above-named person has <strong>NO DEROGATORY RECORD</strong> or pending administrative case filed against them as of this date.
                        </p>
                        <p id="previewCertParagraph3">
                            This certification is being processed upon the official request of the interested party for <strong id="previewPurpose" class="font-bold">Scholarship & Educational Assistance</strong> purposes.
                        </p>
                        <p class="text-[11px] text-slate-500 pt-2 font-sans">
                            Given and signed at the Barangay Hall, City of Caloocan, Metro Manila, Philippines.
                        </p>
                    </div>

                    <div class="pt-6 flex items-end justify-between border-t border-slate-200">
                        <div class="text-center">
                            <div class="w-14 h-14 border border-slate-300 rounded-lg flex items-center justify-center mx-auto text-slate-400 text-xs font-sans bg-slate-50">
                                <i class="fa-solid fa-qrcode text-2xl"></i>
                            </div>
                            <span id="previewControlNo" class="text-[9px] font-mono font-bold text-slate-500 block mt-1">REQ-2026-0000</span>
                        </div>

                        <div class="text-center">
                            <div class="w-48 border-b-2 border-slate-900 mx-auto mb-1"></div>
                            <p class="font-sans font-black text-xs uppercase text-slate-900">HON. BARANGAY CAPTAIN</p>
                            <p class="font-sans text-[10px] text-slate-500 font-medium">Punong Barangay • Caloocan City</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision Rationale / Actions Card -->
            <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xs shrink-0 shadow-xs">
                        <i class="fa-solid fa-gavel"></i>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-amber-950">Administrative Authorization Decision</h5>
                        <p class="text-[11px] text-amber-800">Approving will move this request to the Ready for Release queue for official stamp and dispatch.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="rejectFromPreview()" class="px-3.5 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Reject</span>
                    </button>
                    <button type="button" id="previewApproveBtn" onclick="approveFromPreview()" class="px-4.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Approve Document</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Modal Bottom Navigation Footer -->
        <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end shrink-0">
            <button type="button" onclick="closeCertificatePreview()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>

    </div>
</div>

</main>

<script>
const pendingApprovalsData = <?php echo json_encode(array_column($pendingApprovals, null, 'id')); ?>;
let activePendingId = null;

function filterPendingTable() {
    const searchVal = document.getElementById('pendingSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.pending-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = (!searchVal || text.includes(searchVal)) ? '' : 'none';
    });
}

function openCertificateReviewModal(refId) {
    const item = pendingApprovalsData[refId];
    if (!item) {
        console.error('Pending request record not found:', refId);
        return;
    }

    activePendingId = refId;

    // Header info
    const refBadge = document.getElementById('previewRefBadge');
    if (refBadge) refBadge.textContent = item.id;
    const controlNo = document.getElementById('previewControlNo');
    if (controlNo) controlNo.textContent = item.id;

    // Dossier fields
    const appName = document.getElementById('previewApplicantName');
    if (appName) appName.textContent = item.requester;
    const cIdBadge = document.getElementById('previewCitizenIdBadge');
    if (cIdBadge) cIdBadge.textContent = item.citizen_id;
    const appAddr = document.getElementById('previewApplicantAddress');
    if (appAddr) appAddr.textContent = item.address;
    const appContact = document.getElementById('previewApplicantContact');
    if (appContact) appContact.textContent = item.contact || 'Not provided';
    const dateReq = document.getElementById('previewDateRequested');
    if (dateReq) dateReq.textContent = item.date_requested;
    const cTypeInfo = document.getElementById('previewCertTypeInfo');
    if (cTypeInfo) cTypeInfo.textContent = item.cert_type;
    const purpInfo = document.getElementById('previewPurposeInfo');
    if (purpInfo) purpInfo.textContent = item.purpose;
    const civStatusInfo = document.getElementById('previewCivilStatusInfo');
    if (civStatusInfo) civStatusInfo.textContent = item.civil_status || 'Single';
    const resSinceInfo = document.getElementById('previewResidentSinceInfo');
    if (resSinceInfo) resSinceInfo.textContent = item.resident_since || '2015';
    const offNote = document.getElementById('previewOfficerNote');
    if (offNote) offNote.textContent = item.officer_recommendation;

    // Fee badge
    const feeBadge = document.getElementById('previewFeeStatusBadge');
    if (feeBadge) {
        feeBadge.textContent = item.fees_status;
        if (item.is_waived) {
            feeBadge.className = "px-2.5 py-0.5 rounded-full font-bold text-[10px] bg-purple-50 text-purple-600 border border-purple-200";
        } else {
            feeBadge.className = "px-2.5 py-0.5 rounded-full font-bold text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-200";
        }
    }

    // Docs
    const docsContainer = document.getElementById('previewDocsList');
    if (docsContainer) {
        if (item.docs && item.docs.length > 0) {
            docsContainer.innerHTML = item.docs.map(d => `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                    <i class="fa-solid fa-file-circle-check text-[10px]"></i>
                    <span>${escapeHtml(d)}</span>
                </span>
            `).join('');
        } else {
            docsContainer.innerHTML = `<span class="text-xs text-slate-400 italic">No attachments required</span>`;
        }
    }

    // Document Canvas Preview
    const certTitle = document.getElementById('previewCertTitle');
    if (certTitle) certTitle.textContent = item.cert_type.toUpperCase();
    const citName = document.getElementById('previewCitizenName');
    if (citName) citName.textContent = item.requester.toUpperCase();
    const civStatusText = document.getElementById('previewCivilStatusText');
    if (civStatusText) civStatusText.textContent = item.civil_status || 'Single';
    const addrText = document.getElementById('previewAddressText');
    if (addrText) addrText.textContent = item.address;
    const purpText = document.getElementById('previewPurpose');
    if (purpText) purpText.textContent = item.purpose;

    // Tailor certificate body based on certificate type
    const cType = item.cert_type.toLowerCase();
    const p1 = document.getElementById('previewCertParagraph1');
    const p2 = document.getElementById('previewCertParagraph2');
    const p3 = document.getElementById('previewCertParagraph3');

    if (p1 && p2 && p3) {
        if (cType.includes('indigency')) {
            p1.innerHTML = `This is to officially certify that <strong class="underline font-black font-sans uppercase">${escapeHtml(item.requester)}</strong>, of legal age, <span>${escapeHtml(item.civil_status || 'Single')}</span>, is a bona fide resident of this Barangay residing at <strong>${escapeHtml(item.address)}</strong>.`;
            p2.innerHTML = `Further certified that the above-named individual belongs to an <strong>INDIGENT FAMILY</strong> in this community with low income, and is hereby recommended for government, educational, or medical assistance in accordance with RA 11261 / local welfare guidelines.`;
            p3.innerHTML = `This certification is issued upon the official request of the interested party for <strong class="font-bold">${escapeHtml(item.purpose)}</strong> purposes.`;
        } else if (cType.includes('residency')) {
            p1.innerHTML = `This is to officially certify that <strong class="underline font-black font-sans uppercase">${escapeHtml(item.requester)}</strong>, of legal age, <span>${escapeHtml(item.civil_status || 'Single')}</span>, has been a verified resident of this Barangay since <strong>${escapeHtml(item.resident_since || '2015')}</strong>, presently residing at <strong>${escapeHtml(item.address)}</strong>.`;
            p2.innerHTML = `Records confirm continuous bona fide residency in this community and that the individual is known to be of good moral character.`;
            p3.innerHTML = `Issued upon request of the above-named resident for <strong class="font-bold">${escapeHtml(item.purpose)}</strong> purposes.`;
        } else {
            p1.innerHTML = `This is to officially certify that <strong class="underline font-black font-sans uppercase">${escapeHtml(item.requester)}</strong>, of legal age, <span>${escapeHtml(item.civil_status || 'Single')}</span>, is a bona fide resident of this Barangay residing at <strong>${escapeHtml(item.address)}</strong> with good moral standing in the community.`;
            p2.innerHTML = `Records on file in this office show that the above-named person has <strong>NO DEROGATORY RECORD</strong> or pending administrative case filed against them as of this date.`;
            p3.innerHTML = `This certification is being processed upon the official request of the interested party for <strong class="font-bold">${escapeHtml(item.purpose)}</strong> purposes.`;
        }
    }

    const modal = document.getElementById('previewCertificateModal');
    if (modal) modal.classList.remove('hidden');
}

function closeCertificatePreview() {
    activePendingId = null;
    const modal = document.getElementById('previewCertificateModal');
    if (modal) modal.classList.add('hidden');
}

function approveFromPreview() {
    if (!activePendingId) return;
    approveRequestSingle(activePendingId);
}

function rejectFromPreview() {
    if (!activePendingId) return;
    rejectRequestSingle(activePendingId);
}

function previewCertificateRecord(id, name, type, purpose) {
    openCertificateReviewModal(id);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>"']/g, function(m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
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
    const ids = Object.keys(pendingApprovalsData);
    if (!ids || ids.length === 0) {
        alert('No pending requests to approve.');
        return;
    }

    if (!confirm(`Are you sure you want to approve all ${ids.length} pending requests in queue?`)) return;

    let successCount = 0;
    for (const refId of ids) {
        try {
            const res = await fetch('../../api/admin/certificate-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'approve', reference_no: refId })
            });
            const data = await res.json();
            if (data && data.status === 'success') {
                successCount++;
            }
        } catch (e) {
            console.error('Error approving', refId, e);
        }
    }

    alert(`Bulk approval complete. ${successCount} of ${ids.length} requests approved.`);
    window.location.reload();
}

// Modal dismiss listeners (Backdrop click and Escape key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCertificatePreview();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('previewCertificateModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeCertificatePreview();
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
