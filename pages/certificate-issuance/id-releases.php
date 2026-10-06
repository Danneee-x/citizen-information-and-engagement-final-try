<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Dedicated Certificate & ID Database Connection
$pdo = getCertificateDbConnection();

// Ensure table exists with all required columns
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `id_issuance_applications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(50) NOT NULL UNIQUE,
        `citizen_user_id` INT UNSIGNED NULL,
        `id_category` VARCHAR(50) NOT NULL,
        `id_title` VARCHAR(150) NOT NULL,
        `application_type` ENUM('New Application', 'Renewal', 'Replacement') NOT NULL DEFAULT 'New Application',
        `first_name` VARCHAR(100) NOT NULL,
        `middle_name` VARCHAR(100) NULL DEFAULT '',
        `last_name` VARCHAR(100) NOT NULL,
        `suffix` VARCHAR(20) NULL DEFAULT '',
        `gender` VARCHAR(20) DEFAULT 'Male',
        `birthdate` DATE NULL,
        `civil_status` VARCHAR(50) DEFAULT 'Single',
        `contact_number` VARCHAR(50) NOT NULL,
        `email` VARCHAR(150) NULL,
        `street_address` VARCHAR(255) NOT NULL,
        `barangay` VARCHAR(100) NOT NULL,
        `district` VARCHAR(50) DEFAULT 'District 1',
        `resident_since` VARCHAR(50) DEFAULT '2015',
        `issuing_bureau` VARCHAR(200) NOT NULL,
        `claim_office` VARCHAR(200) NULL,
        `estimated_turnaround` VARCHAR(100) NULL,
        `card_serial_number` VARCHAR(100) NULL,
        `claimed_by_name` VARCHAR(150) NULL,
        `claim_notes` TEXT NULL,
        `verified_photo_id` TINYINT(1) DEFAULT 0,
        `verified_residency_proof` TINYINT(1) DEFAULT 0,
        `verified_claim_voucher` TINYINT(1) DEFAULT 0,
        `primary_doc_name` VARCHAR(150) NULL,
        `primary_doc_url` TEXT NULL,
        `photo_2x2_url` TEXT NULL,
        `support_doc_name` VARCHAR(150) NULL,
        `support_doc_url` TEXT NULL,
        `status` ENUM('Pending Review', 'Under Review', 'Processing & Verification', 'Approved', 'Ready for Release', 'Claimed', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending Review',
        `review_notes` TEXT NULL,
        `rejection_reason` TEXT NULL,
        `reviewed_by` VARCHAR(100) NULL,
        `reviewed_at` DATETIME NULL,
        `released_by` VARCHAR(100) NULL,
        `released_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cat (`id_category`),
        INDEX idx_stat (`status`),
        INDEX idx_brgy (`barangay`),
        INDEX idx_ref (`reference_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}

$alertMessage = '';
$alertType = '';
$justReleasedApp = null;

// Handle Form POST for ID Release & Handover
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm_release') {
        $appId = (int)($_POST['application_id'] ?? 0);
        $cardSerial = trim($_POST['card_serial_number'] ?? '');
        $claimedByName = trim($_POST['claimed_by_name'] ?? '');
        $claimNotes = trim($_POST['claim_notes'] ?? '');
        $verifPhotoId = !empty($_POST['verified_photo_id']) ? 1 : 0;
        $verifResidency = !empty($_POST['verified_residency_proof']) ? 1 : 0;
        $verifVoucher = !empty($_POST['verified_claim_voucher']) ? 1 : 0;
        $releasingOfficer = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Releasing Desk Officer';

        if ($appId > 0) {
            $stmt = $pdo->prepare("
                UPDATE `id_issuance_applications` 
                SET `status` = 'Claimed',
                    `card_serial_number` = :card_serial,
                    `claimed_by_name` = :claimed_by,
                    `claim_notes` = :claim_notes,
                    `verified_photo_id` = :v_photo,
                    `verified_residency_proof` = :v_res,
                    `verified_claim_voucher` = :v_voucher,
                    `released_by` = :released_by,
                    `released_at` = NOW()
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':card_serial' => $cardSerial,
                ':claimed_by' => $claimedByName,
                ':claim_notes' => $claimNotes,
                ':v_photo' => $verifPhotoId,
                ':v_res' => $verifResidency,
                ':v_voucher' => $verifVoucher,
                ':released_by' => $releasingOfficer,
                ':id' => $appId
            ]);

            // Fetch the updated app record for instant printing/confirmation
            $fetchStmt = $pdo->prepare("SELECT * FROM `id_issuance_applications` WHERE `id` = :id");
            $fetchStmt->execute([':id' => $appId]);
            $justReleasedApp = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            $ref = $justReleasedApp['reference_no'] ?? '';
            $applicant = trim("{$justReleasedApp['first_name']} {$justReleasedApp['last_name']}");
            $alertMessage = "Resident ID successfully handed over & activated for <strong>{$applicant}</strong> (Ref: <strong>{$ref}</strong>). Status is now <strong>Completed / Claimed</strong>.";
            $alertType = 'success';
        }
    } elseif ($action === 'mark_ready_for_release') {
        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId > 0) {
            $stmt = $pdo->prepare("UPDATE `id_issuance_applications` SET `status` = 'Ready for Release' WHERE `id` = :id");
            $stmt->execute([':id' => $appId]);
            $alertMessage = "Application marked as 'Ready for Release' at designated claim desk.";
            $alertType = 'success';
        }
    }
}

// Compute Dynamic Counters
$readyCount = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` = 'Ready for Release'")->fetchColumn();
$claimedToday = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` = 'Claimed' AND DATE(`released_at`) = CURDATE()")->fetchColumn();
$totalClaimed = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` = 'Claimed'")->fetchColumn();
$totalInPipeline = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` IN ('Ready for Release', 'Claimed')")->fetchColumn();

// Query Filters
$activeTab = $_GET['tab'] ?? 'ready'; // ready | claimed | all
$searchQuery = trim($_GET['search'] ?? '');
$targetRef = trim($_GET['ref'] ?? '');

if (!empty($targetRef) && empty($searchQuery)) {
    $searchQuery = $targetRef;
    $activeTab = 'all';
}

$sql = "SELECT * FROM `id_issuance_applications` WHERE 1=1";
$params = [];

if ($activeTab === 'ready') {
    // Only cards physically ready for citizen handover
    $sql .= " AND `status` = 'Ready for Release'";
} elseif ($activeTab === 'claimed') {
    $sql .= " AND `status` = 'Claimed'";
} else {
    // Desk records: only applications that have arrived at the release counter
    $sql .= " AND `status` IN ('Ready for Release', 'Claimed')";
}

if (!empty($searchQuery)) {
    $sql .= " AND (`reference_no` LIKE :q1 OR `first_name` LIKE :q2 OR `last_name` LIKE :q3 OR `barangay` LIKE :q4 OR `card_serial_number` LIKE :q5)";
    $likeTerm = "%{$searchQuery}%";
    $params[':q1'] = $likeTerm;
    $params[':q2'] = $likeTerm;
    $params[':q3'] = $likeTerm;
    $params[':q4'] = $likeTerm;
    $params[':q5'] = $likeTerm;
}

$sql .= " ORDER BY CASE WHEN `status` = 'Ready for Release' THEN 0 ELSE 1 END, `id` DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$releaseList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if search matched an application that is still in review or printing queue (Approved / Pending)
$searchedPipelineApp = null;
if (!empty($searchQuery) && empty($releaseList)) {
    $checkStmt = $pdo->prepare("SELECT * FROM `id_issuance_applications` WHERE (`reference_no` = :ref OR `reference_no` LIKE :q1) LIMIT 1");
    $checkStmt->execute([':ref' => $searchQuery, ':q1' => "%{$searchQuery}%"]);
    $searchedPipelineApp = $checkStmt->fetch(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .barcode-font {
        font-family: 'Libre Barcode 39', 'Courier New', monospace;
        letter-spacing: 2px;
    }
    @media print {
        body * {
            visibility: hidden !important;
        }
        #printableReleaseReceipt, #printableReleaseReceipt * {
            visibility: visible !important;
        }
        #printableReleaseReceipt {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: white !important;
            padding: 24px;
            z-index: 999999;
        }
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Header Breadcrumbs & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100 shadow-xs">
                <i class="fa-solid fa-hand-holding-hand"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">ID Release & Pick-up Desk</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Official ID Release Counter & Claim Desk</h1>
                <p class="text-xs text-slate-500 font-medium">Verify citizen claim vouchers, inspect required residency proofs, and record physical card handovers.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="id-issuance.php" class="px-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left text-slate-400"></i>
                <span>Applications Queue</span>
            </a>
            <button onclick="document.getElementById('voucherSearchInput').focus()" class="px-4.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-barcode text-xs"></i>
                <span>Scan Voucher</span>
            </button>
        </div>
    </div>

    <?php if (!empty($alertMessage)): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between <?php echo $alertType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
        <div class="flex items-center gap-2">
            <i class="<?php echo $alertType === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-exclamation text-rose-600'; ?> text-sm"></i>
            <span><?php echo $alertMessage; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- KPI Summary Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ready for Pick-up -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ready for Pick-up</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $readyCount; ?></h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i> Waiting for Resident Claim
                </p>
            </div>
        </div>

        <!-- Released & Claimed Today -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Handed Over Today</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $claimedToday; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-check"></i> Activated at Release Counter
                </p>
            </div>
        </div>

        <!-- Total Historical Claimed -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Released IDs</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base border border-indigo-100">
                    <i class="fa-solid fa-stamp"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $totalClaimed; ?></h3>
                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-database"></i> Officially Completed Cards
                </p>
            </div>
        </div>

        <!-- Release Desk SLA / Turnaround -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Target Turnaround</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">1 - 2 <span class="text-sm font-normal text-slate-500">Days</span></h3>
                <p class="text-[11px] font-semibold text-blue-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bolt"></i> Average Handover: &lt; 3 mins
                </p>
            </div>
        </div>
    </div>

    <!-- Live Barcode Scanner / Reference Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
            <div class="relative flex-1 w-full">
                <i class="fa-solid fa-barcode text-slate-400 text-lg absolute left-4 top-1/2 -translate-y-1/2"></i>
                <input type="text" 
                       name="search" 
                       id="voucherSearchInput"
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Scan Digital Claim Barcode or Type Ref Code (e.g. CAL-BRGY-2026-1879, Citizen Name, or Barangay)..." 
                       class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 text-xs text-slate-800 font-semibold rounded-xl pl-12 pr-4 py-3 outline-none transition placeholder:text-slate-400">
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Find Voucher</span>
            </button>
            <?php if (!empty($searchQuery)): ?>
            <a href="id-releases.php?tab=<?php echo urlencode($activeTab); ?>" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition flex items-center justify-center">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table Container with Queue Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 pt-4 bg-slate-50/50 flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <a href="id-releases.php?tab=ready<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 <?php echo $activeTab === 'ready' ? 'border-emerald-600 text-emerald-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'; ?>">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Ready for Release</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800"><?php echo $readyCount; ?></span>
                </a>

                <a href="id-releases.php?tab=claimed<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 <?php echo $activeTab === 'claimed' ? 'border-emerald-600 text-emerald-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'; ?>">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Claimed & Handed Over</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700"><?php echo $totalClaimed; ?></span>
                </a>

                <a href="id-releases.php?tab=all<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 <?php echo $activeTab === 'all' ? 'border-emerald-600 text-emerald-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'; ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>All Applications</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700"><?php echo $totalInPipeline; ?></span>
                </a>
            </div>

            <div class="text-[11px] font-semibold text-slate-500 pb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Claim Desk: <strong>Local Barangay Hall Records Desk</strong></span>
            </div>
        </div>

        <!-- Release Table -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Claim Voucher Ref</th>
                        <th class="py-3 px-4">Applicant & Barangay</th>
                        <th class="py-3 px-4">ID Category & Title</th>
                        <th class="py-3 px-4">Designated Desk</th>
                        <th class="py-3 px-4">Current Status</th>
                        <th class="py-3 px-4">Card Serial / Handover Info</th>
                        <th class="py-3 px-4 text-right">Counter Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($releaseList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400 font-medium">
                            <?php if (!empty($searchedPipelineApp)): ?>
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-lg mb-2 border border-amber-200">
                                <i class="fa-solid fa-print"></i>
                            </div>
                            <div class="space-y-1.5">
                                <p class="font-bold text-slate-800 text-sm">Application Ref: <strong><?php echo htmlspecialchars($searchedPipelineApp['reference_no']); ?></strong></p>
                                <p class="text-xs text-slate-600">Current Status: <span class="px-2 py-0.5 rounded-full font-black text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200"><?php echo htmlspecialchars($searchedPipelineApp['status']); ?></span></p>
                                <p class="text-xs text-slate-500 max-w-md mx-auto">This ID card is still in the card printing / evaluation queue. It will only appear on the Release Desk once printed and marked as <strong>Ready for Release</strong>.</p>
                                <div class="pt-2">
                                    <a href="id-issuance.php?search=<?php echo urlencode($searchedPipelineApp['reference_no']); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition">
                                        <i class="fa-solid fa-id-card"></i>
                                        <span>Open in ID Issuance Queue &rarr;</span>
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-lg mb-2">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <span>No identification cards found in this queue.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($releaseList as $app): 
                        $fullName = trim("{$app['first_name']} {$app['middle_name']} {$app['last_name']} {$app['suffix']}");
                        $isReady = ($app['status'] === 'Ready for Release');
                        $isClaimed = ($app['status'] === 'Claimed');
                    ?>
                    <tr class="hover:bg-slate-50/70 transition <?php echo ($targetRef && $app['reference_no'] === $targetRef) ? 'bg-amber-50/60' : ''; ?>">
                        <!-- Ref No with Barcode indicator -->
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-barcode text-slate-400 text-sm"></i>
                                <span class="text-xs"><?php echo htmlspecialchars($app['reference_no']); ?></span>
                            </div>
                            <span class="text-[9px] font-black text-indigo-600 uppercase tracking-wider">
                                <?php echo htmlspecialchars($app['application_type']); ?>
                            </span>
                        </td>

                        <!-- Applicant Profile -->
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 text-xs">
                                <?php echo htmlspecialchars($fullName); ?>
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium">
                                <i class="fa-solid fa-location-dot text-[9px] text-slate-400 mr-1"></i><?php echo htmlspecialchars($app['barangay']); ?>
                                <span class="mx-1">•</span>
                                <i class="fa-solid fa-phone text-[9px] text-slate-400 mr-1"></i><?php echo htmlspecialchars($app['contact_number']); ?>
                            </div>
                        </td>

                        <!-- ID Title -->
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-800 text-xs">
                                <?php echo htmlspecialchars($app['id_title']); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium truncate max-w-xs">
                                <?php echo htmlspecialchars($app['issuing_bureau']); ?>
                            </div>
                        </td>

                        <!-- Designated Desk -->
                        <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">
                            <div><?php echo htmlspecialchars($app['claim_office'] ?: 'Local Barangay Hall Desk'); ?></div>
                            <span class="text-[10px] text-emerald-600 font-bold">Turnaround: <?php echo htmlspecialchars($app['estimated_turnaround'] ?: '1 to 2 Days'); ?></span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <?php if ($isReady): ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1.5 w-fit">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>Ready for Release</span>
                            </span>
                            <?php elseif ($isClaimed): ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1 w-fit">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                <span>Claimed & Released</span>
                            </span>
                            <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200 w-fit">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Card Serial / Handover Info -->
                        <td class="py-3.5 px-4">
                            <?php if ($isClaimed): ?>
                            <div class="text-xs font-mono font-bold text-slate-900">
                                <i class="fa-solid fa-credit-card text-emerald-600 mr-1 text-[10px]"></i><?php echo htmlspecialchars($app['card_serial_number'] ?: 'STANDARD-ISSUE'); ?>
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Released by <?php echo htmlspecialchars($app['released_by'] ?: 'Staff'); ?> on <?php echo date('M d, Y • h:i A', strtotime($app['released_at'] ?: $app['updated_at'])); ?>
                            </div>
                            <?php else: ?>
                            <span class="text-[11px] text-slate-400 italic">Awaiting citizen claim</span>
                            <?php endif; ?>
                        </td>

                        <!-- Counter Actions -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <?php if ($isReady): ?>
                                <button onclick='openClaimHandoverModal(<?php echo json_encode($app, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-hand-holding-hand text-[11px]"></i>
                                    <span>Release ID</span>
                                </button>
                                <?php endif; ?>

                                <button onclick='openVoucherModal(<?php echo json_encode($app, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1 cursor-pointer"
                                        title="View Digital Voucher & Claim Requirements">
                                    <i class="fa-solid fa-barcode text-[10px]"></i>
                                    <span>Voucher</span>
                                </button>

                                <?php if ($isClaimed): ?>
                                <button onclick='printClaimReceipt(<?php echo json_encode($app, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                        class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl transition flex items-center gap-1 cursor-pointer"
                                        title="Print Official Handover Receipt">
                                    <i class="fa-solid fa-receipt text-[10px]"></i>
                                    <span>Receipt</span>
                                </button>
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
<!-- MODAL: DIGITAL CLAIM VOUCHER & MOBILE APP MIRROR VIEW                          -->
<!-- (Mirrors Citizen Mobile App Screenshots 2 & 3 Exactly)                         -->
<!-- ============================================================================== -->
<div id="voucherModal" class="fixed inset-0 z-[9999] hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        
        <!-- Mobile App Mirrored Header -->
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-black border border-blue-100">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900 leading-tight">Civentral</h3>
                    <p class="text-[10px] text-slate-400 font-semibold">Official ID Issuance Services</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs relative">
                    <i class="fa-regular fa-bell"></i>
                    <span class="w-2 h-2 rounded-full bg-rose-500 absolute top-1 right-1 border border-white"></span>
                </span>
                <button onclick="closeVoucherModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto custom-scrollbar">

            <!-- Designated Release Counter Box (Mirroring Screenshot 2) -->
            <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100/80 space-y-2.5">
                <div class="flex items-center gap-2 text-emerald-800 font-extrabold text-xs">
                    <i class="fa-solid fa-id-badge text-emerald-600"></i>
                    <span>Designated Release Counter & Pick-up</span>
                </div>
                
                <div class="space-y-1.5 text-[11px] pt-1 border-t border-emerald-200/50">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-medium">Designated Claim Center:</span>
                        <strong class="text-slate-800 text-right" id="vModalClaimCenter">Local Barangay Hall - Administrative Records Desk</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-medium">Estimated Turnaround:</span>
                        <strong class="text-blue-600" id="vModalTurnaround">1 to 2 Business Days</strong>
                    </div>
                    <div class="pt-1 text-slate-600 text-[10px] leading-relaxed">
                        <strong class="text-slate-700">Claim Requirements:</strong> Present proof of barangay residency (lease/utility bill), any valid ID, and claim voucher at your Barangay Hall.
                    </div>
                </div>
            </div>

            <!-- Application Lifecycle Tracking (5 Stages Stepper Mirroring Screenshot 2) -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 space-y-3 shadow-xs">
                <div class="border-b border-slate-100 pb-2">
                    <h4 class="font-extrabold text-xs text-slate-900">Application Lifecycle Tracking</h4>
                    <p class="text-[9px] text-slate-400 font-medium">Submitted &rarr; Document Review &rarr; Verification &rarr; Ready for Release &rarr; Completed</p>
                </div>

                <div class="space-y-3 pt-1">
                    <!-- Step 1: Submitted -->
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-800">Submitted &check;</div>
                            <div class="text-[10px] text-slate-400">Application filed online</div>
                        </div>
                    </div>

                    <!-- Step 2: Document Review -->
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5" id="vStepDocIcon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-800">Document Review</div>
                            <div class="text-[10px] text-slate-400">Issuing bureau evaluating documents</div>
                        </div>
                    </div>

                    <!-- Step 3: Processing & Verification -->
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5" id="vStepProcIcon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-800">Processing & Verification</div>
                            <div class="text-[10px] text-slate-400">Registry clearance & credential coding</div>
                        </div>
                    </div>

                    <!-- Step 4: Ready for Release -->
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5" id="vStepReleaseIcon">
                            <i class="fa-solid fa-circle-dot"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-blue-700" id="vStepReleaseTitle">Ready for Release (Active)</div>
                            <div class="text-[10px] text-slate-400">Available at designated release desk</div>
                        </div>
                    </div>

                    <!-- Step 5: Completed -->
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5" id="vStepClaimIcon">
                            <i class="fa-solid fa-circle"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-500" id="vStepClaimTitle">Completed</div>
                            <div class="text-[10px] text-slate-400">ID Card claimed & activated</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Digital Claim Voucher Card (Mirroring Screenshot 3) -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm space-y-4">
                <div class="text-center space-y-1">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">DIGITAL CLAIM VOUCHER</span>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight font-mono" id="vModalRef">CAL-BRGY-2026-1879</h2>
                    
                    <!-- SVG Barcode Representation -->
                    <div class="flex items-center justify-center py-2">
                        <svg class="h-12 w-64 max-w-full" viewBox="0 0 200 40" preserveAspectRatio="none">
                            <rect x="0" y="0" width="3" height="40" fill="#0f172a"/>
                            <rect x="5" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="10" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="16" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="20" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="28" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="33" y="0" width="3" height="40" fill="#0f172a"/>
                            <rect x="39" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="46" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="52" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="58" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="64" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="71" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="75" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="83" y="0" width="3" height="40" fill="#0f172a"/>
                            <rect x="88" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="92" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="99" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="104" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="112" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="117" y="0" width="3" height="40" fill="#0f172a"/>
                            <rect x="123" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="131" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="136" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="142" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="148" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="155" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="160" y="0" width="5" height="40" fill="#0f172a"/>
                            <rect x="168" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="172" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="178" y="0" width="2" height="40" fill="#0f172a"/>
                            <rect x="184" y="0" width="4" height="40" fill="#0f172a"/>
                            <rect x="190" y="0" width="3" height="40" fill="#0f172a"/>
                            <rect x="196" y="0" width="2" height="40" fill="#0f172a"/>
                        </svg>
                    </div>
                    <p class="text-[10px] text-slate-400">Present this reference code or digital voucher at the release desk.</p>
                </div>

                <!-- Documents to Bring Upon Claiming (Checklist from Screenshot 3) -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <span class="text-xs font-extrabold text-slate-900 block">Documents to Bring Upon Claiming:</span>
                    <ul class="space-y-1.5 text-[11px] text-slate-700">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                            <span>1 Valid Government-issued Photo ID (original)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                            <span>Original supporting proof (Proof of Barangay Residency (Min 6 Months))</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                            <span>This digital claim voucher or printed receipt</span>
                        </li>
                    </ul>
                </div>

                <!-- Pick-up Desk Box -->
                <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3 flex items-start gap-2.5">
                    <i class="fa-solid fa-building text-blue-600 text-xs mt-0.5"></i>
                    <div>
                        <span class="text-[10px] font-bold text-blue-900 block">Designated Pick-up Desk:</span>
                        <span class="text-[11px] font-semibold text-blue-800" id="vModalDeskName">Local Barangay Hall - Administrative Records Desk</span>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="pt-2 flex items-center justify-between gap-3">
                <button type="button" onclick="closeVoucherModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Close</button>
                <div class="flex items-center gap-2">
                    <button type="button" id="vModalHandoverBtn" class="px-4.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-hand-holding-hand"></i>
                        <span>Process Handover</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- MODAL: HANDOVER & PHYSICAL CARD RELEASE CONFIRMATION                           -->
<!-- ============================================================================== -->
<div id="handoverModal" class="fixed inset-0 z-[9999] hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        
        <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm border border-emerald-400/30">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-tight">Confirm ID Card Release & Handover</h3>
                    <p class="text-[11px] text-slate-400 font-mono" id="hModalRef">CAL-BRGY-2026-0000</p>
                </div>
            </div>
            <button onclick="closeHandoverModal()" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="confirm_release">
            <input type="hidden" name="application_id" id="hModalAppId" value="0">

            <!-- Applicant Preview -->
            <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200/80 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <strong class="text-slate-900 text-sm" id="hModalApplicantName">Applicant Legal Name</strong>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700" id="hModalCatTitle">Barangay ID</span>
                </div>
                <div class="text-[11px] text-slate-500">
                    Barangay: <strong class="text-slate-700" id="hModalBarangay">Brgy 171</strong> • Contact: <strong class="text-slate-700" id="hModalContact">0917-000-0000</strong>
                </div>
            </div>

            <!-- Verification Checklist before releasing -->
            <div class="space-y-2 bg-emerald-50/50 p-3.5 rounded-xl border border-emerald-100">
                <span class="text-xs font-bold text-emerald-900 block">In-person Counter Verification Checklist:</span>
                
                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="verified_photo_id" value="1" required checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Verified Citizen's Original Valid Photo ID</span>
                </label>

                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="verified_residency_proof" value="1" required checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Verified Original Proof of Barangay Residency</span>
                </label>

                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="verified_claim_voucher" value="1" required checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Validated Digital Claim Voucher & Barcode Match</span>
                </label>
            </div>

            <!-- Card Serial & Release Inputs -->
            <div class="space-y-3">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Physical ID Card Serial / Control Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="card_serial_number" id="hModalCardSerial" required placeholder="e.g. CAL-BRGY-2026-0891" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs font-mono font-bold rounded-xl px-3.5 py-2.5 outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Claimed By (Resident / Authorized Rep) <span class="text-rose-500">*</span></label>
                    <input type="text" name="claimed_by_name" id="hModalClaimedBy" required placeholder="Full name of person claiming" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-xl px-3.5 py-2.5 outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Release Notes / Remarks (Optional)</label>
                    <textarea name="claim_notes" rows="2" placeholder="e.g. Claimed in person at records counter with updated voter certification..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl p-3 outline-none focus:border-emerald-500 focus:bg-white transition"></textarea>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeHandoverModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Confirm Handover & Complete</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- PRINTABLE OFFICIAL CLAIM HANDOVER RECEIPT / ACKNOWLEDGMENT SLIP                -->
<!-- ============================================================================== -->
<div id="printableReleaseReceipt" class="hidden">
    <div class="max-w-xl mx-auto p-6 bg-white border border-slate-300 rounded-2xl space-y-5 text-slate-900 font-sans">
        <!-- Receipt Top Header -->
        <div class="text-center border-b border-slate-200 pb-4 space-y-1">
            <h2 class="text-sm font-black uppercase tracking-widest text-slate-800">REPUBLIC OF THE PHILIPPINES</h2>
            <h3 class="text-base font-black text-slate-900">CITY GOVERNMENT OF CALOOCAN</h3>
            <p class="text-xs font-bold text-emerald-800">BARANGAY CERTIFICATE & ID ISSUANCE DESK</p>
            <p class="text-[10px] text-slate-500 font-semibold tracking-wide">OFFICIAL CITIZEN ID HANDOVER & RELEASE ACKNOWLEDGMENT</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] font-bold">VOUCHER REFERENCE NO:</span>
                <strong class="font-mono text-sm" id="pReceiptRef">CAL-BRGY-2026-1879</strong>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block text-[10px] font-bold">DATE & TIME RELEASED:</span>
                <strong id="pReceiptDate"><?php echo date('M d, Y • h:i A'); ?></strong>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold">RESIDENT NAME:</span>
                <strong id="pReceiptApplicant">Danny Espelita Jr.</strong>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block text-[10px] font-bold">CARD SERIAL NUMBER:</span>
                <strong class="font-mono text-emerald-700" id="pReceiptSerial">CAL-BRGY-2026-0891</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold">BARANGAY:</span>
                <strong id="pReceiptBarangay">Barangay 171</strong>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block text-[10px] font-bold">CLAIMED BY:</span>
                <strong id="pReceiptClaimedBy">Danny Espelita Jr.</strong>
            </div>
        </div>

        <!-- Verification Acknowledgment -->
        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-[10px] leading-relaxed text-slate-700">
            <strong>CERTIFICATION OF HANDOVER:</strong> This confirms that the official identification card specified above has been verified, validated against resident registration credentials, and handed over to the authorized recipient.
        </div>

        <!-- Signatures Area -->
        <div class="grid grid-cols-2 gap-8 pt-6 border-t border-slate-200 text-center">
            <div class="space-y-8">
                <div class="h-8"></div>
                <div class="border-t border-slate-400 pt-1 text-xs font-bold text-slate-800">
                    <span id="pReceiptSignApplicant">Danny Espelita Jr.</span>
                    <div class="text-[9px] font-normal text-slate-500">Resident / Recipient Signature</div>
                </div>
            </div>
            <div class="space-y-8">
                <div class="h-8"></div>
                <div class="border-t border-slate-400 pt-1 text-xs font-bold text-slate-800">
                    <span><?php echo htmlspecialchars(!empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Releasing Officer'); ?></span>
                    <div class="text-[9px] font-normal text-slate-500">Releasing Desk Officer Signature</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentActiveApp = null;

    function openVoucherModal(app) {
        currentActiveApp = app;
        const fullName = `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''} ${app.suffix || ''}`.replace(/\s+/g, ' ').trim();
        
        document.getElementById('vModalRef').textContent = app.reference_no;
        document.getElementById('vModalClaimCenter').textContent = app.claim_office || 'Local Barangay Hall - Administrative Records Desk';
        document.getElementById('vModalTurnaround').textContent = app.estimated_turnaround || '1 to 2 Business Days';
        document.getElementById('vModalDeskName').textContent = app.claim_office || 'Local Barangay Hall - Administrative Records Desk';

        // Stepper state
        const isClaimed = app.status === 'Claimed';
        const claimIcon = document.getElementById('vStepClaimIcon');
        const claimTitle = document.getElementById('vStepClaimTitle');

        if (isClaimed) {
            claimIcon.className = 'w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5';
            claimIcon.innerHTML = '<i class="fa-solid fa-check"></i>';
            claimTitle.className = 'font-bold text-xs text-emerald-700';
            claimTitle.textContent = 'Completed (Claimed & Activated)';
        } else {
            claimIcon.className = 'w-5 h-5 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5';
            claimIcon.innerHTML = '<i class="fa-solid fa-circle"></i>';
            claimTitle.className = 'font-bold text-xs text-slate-500';
            claimTitle.textContent = 'Completed';
        }

        const handoverBtn = document.getElementById('vModalHandoverBtn');
        if (isClaimed) {
            handoverBtn.innerHTML = '<i class="fa-solid fa-receipt"></i><span>Print Handover Slip</span>';
            handoverBtn.onclick = () => { closeVoucherModal(); printClaimReceipt(app); };
        } else {
            handoverBtn.innerHTML = '<i class="fa-solid fa-hand-holding-hand"></i><span>Process Handover</span>';
            handoverBtn.onclick = () => { closeVoucherModal(); openClaimHandoverModal(app); };
        }

        document.getElementById('voucherModal').classList.remove('hidden');
    }

    function closeVoucherModal() {
        document.getElementById('voucherModal').classList.add('hidden');
    }

    function openClaimHandoverModal(app) {
        currentActiveApp = app;
        const fullName = `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''} ${app.suffix || ''}`.replace(/\s+/g, ' ').trim();

        document.getElementById('hModalAppId').value = app.id;
        document.getElementById('hModalRef').textContent = app.reference_no;
        document.getElementById('hModalApplicantName').textContent = fullName;
        document.getElementById('hModalCatTitle').textContent = app.id_title || 'ID Card';
        document.getElementById('hModalBarangay').textContent = app.barangay || 'Barangay';
        document.getElementById('hModalContact').textContent = app.contact_number || 'N/A';
        document.getElementById('hModalCardSerial').value = app.card_serial_number || `${app.reference_no}-CRD`;
        document.getElementById('hModalClaimedBy').value = fullName;

        document.getElementById('handoverModal').classList.remove('hidden');
    }

    function closeHandoverModal() {
        document.getElementById('handoverModal').classList.add('hidden');
    }

    function printClaimReceipt(app) {
        const fullName = `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''} ${app.suffix || ''}`.replace(/\s+/g, ' ').trim();

        document.getElementById('pReceiptRef').textContent = app.reference_no;
        document.getElementById('pReceiptApplicant').textContent = fullName;
        document.getElementById('pReceiptSignApplicant').textContent = fullName;
        document.getElementById('pReceiptSerial').textContent = app.card_serial_number || 'OFFICIAL-CARD-ISSUED';
        document.getElementById('pReceiptBarangay').textContent = app.barangay || 'Barangay';
        document.getElementById('pReceiptClaimedBy').textContent = app.claimed_by_name || fullName;

        const receiptEl = document.getElementById('printableReleaseReceipt');
        receiptEl.classList.remove('hidden');
        window.print();
        setTimeout(() => {
            receiptEl.classList.add('hidden');
        }, 1000);
    }

    // Auto-focus and open voucher modal if targeted in URL
    <?php if (!empty($targetRef) && !empty($releaseList)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        const matched = <?php echo json_encode($releaseList[0], JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        if (matched && matched.reference_no === '<?php echo addslashes($targetRef); ?>') {
            openVoucherModal(matched);
        }
    });
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>
