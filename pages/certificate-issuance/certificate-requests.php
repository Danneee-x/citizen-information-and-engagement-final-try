<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Dedicated Certificate Database Connection
$pdo = getCertificateDbConnection();

// Ensure certificate_requests table exists with all required workflow columns
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `certificate_requests` (
        `request_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(50) NOT NULL UNIQUE,
        `citizen_user_id` INT UNSIGNED NULL,
        `citizen_name` VARCHAR(150) NOT NULL,
        `first_name` VARCHAR(100) NULL,
        `middle_name` VARCHAR(100) NULL,
        `last_name` VARCHAR(100) NULL,
        `suffix` VARCHAR(20) NULL,
        `gender` VARCHAR(20) DEFAULT 'Male',
        `birthdate` DATE NULL,
        `civil_status` VARCHAR(50) DEFAULT 'Single',
        `contact_number` VARCHAR(50) NOT NULL,
        `email` VARCHAR(150) NULL,
        `street_address` VARCHAR(255) NOT NULL,
        `barangay` VARCHAR(100) NOT NULL,
        `district` VARCHAR(50) DEFAULT 'District 1',
        `resident_since` VARCHAR(50) DEFAULT '2015',
        `certificate_type` VARCHAR(150) NOT NULL,
        `purpose` VARCHAR(200) NOT NULL,
        `purpose_details` TEXT NULL,
        `additional_notes` TEXT NULL,
        `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
        `or_number` VARCHAR(50) NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'Pending Review',
        `verification_notes` TEXT NULL,
        `review_notes` TEXT NULL,
        `rejection_reason` TEXT NULL,
        `reviewed_by` VARCHAR(100) NULL,
        `reviewed_at` DATETIME NULL,
        `approved_by` VARCHAR(100) NULL,
        `approved_at` DATETIME NULL,
        `released_by` VARCHAR(100) NULL,
        `released_at` DATETIME NULL,
        `uploaded_documents` TEXT NULL,
        `encoded_by` VARCHAR(100) DEFAULT 'Citizen Mobile App',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_type (`certificate_type`),
        INDEX idx_stat (`status`),
        INDEX idx_brgy (`barangay`),
        INDEX idx_ref (`reference_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Auto-migrate status column to VARCHAR(50) to support all status stages seamlessly
    $pdo->exec("ALTER TABLE `certificate_requests` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Pending Review'");
} catch (Exception $e) {}

// Handle POST Actions (Status Update, Edit, Delete, Walk-in Registration)
$alertMessage = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $reqId = (int)($_POST['application_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $reviewNotes = trim($_POST['review_notes'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        $reviewer = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Verification Officer';

        if ($reqId > 0 && !empty($newStatus)) {
            $isReleased = in_array($newStatus, ['Released', 'Claimed']);
            $releasedAt = $isReleased ? date('Y-m-d H:i:s') : null;

            try {
                // Fetch existing record details for control number and sync
                $stmtFetch = $pdo->prepare("SELECT * FROM `certificate_requests` WHERE `request_id` = :id LIMIT 1");
                $stmtFetch->execute([':id' => $reqId]);
                $target = $stmtFetch->fetch();

                if ($target) {
                    $refNo = $target['reference_no'];
                    $certType = $target['certificate_type'];
                    $fee = (float)$target['fee_amount'];
                    $isWaived = ($fee == 0.00) || stripos($certType, 'Indigency') !== false || stripos($certType, 'Jobseeker') !== false;

                    $orNo = $target['or_number'];
                    if ($isReleased && empty($orNo)) {
                        $prefix = 'BRG';
                        if (stripos($certType, 'Clearance') !== false) $prefix = 'CLR';
                        elseif (stripos($certType, 'Indigency') !== false) $prefix = 'IND';
                        elseif (stripos($certType, 'Residency') !== false) $prefix = 'RES';
                        elseif (stripos($certType, 'Jobseeker') !== false) $prefix = 'JOB';
                        elseif (stripos($certType, 'Business') !== false) $prefix = 'BUS';
                        $orNo = $isWaived ? "WAIVED-{$prefix}" : ("OR-" . mt_rand(984000, 984999));
                    }
                    $pStatus = $isWaived ? 'Waived' : ($isReleased ? 'Paid' : $target['payment_status']);

                    // Update certificate_requests record
                    $updateStmt = $pdo->prepare("
                        UPDATE `certificate_requests`
                        SET `status` = :status,
                            `review_notes` = :review_notes,
                            `verification_notes` = :verification_notes,
                            `rejection_reason` = :rejection_reason,
                            `reviewed_by` = :reviewed_by,
                            `reviewed_at` = NOW(),
                            `approved_by` = CASE WHEN :new_stat1 IN ('Approved', 'Ready to Print', 'Ready for Release', 'Released') THEN COALESCE(`approved_by`, :reviewer1) ELSE `approved_by` END,
                            `approved_at` = CASE WHEN :new_stat2 IN ('Approved', 'Ready to Print', 'Ready for Release', 'Released') THEN COALESCE(`approved_at`, NOW()) ELSE `approved_at` END,
                            `released_at` = COALESCE(:released_at, `released_at`),
                            `released_by` = CASE WHEN :new_stat3 IN ('Released', 'Claimed') THEN :reviewer2 ELSE `released_by` END,
                            `or_number` = COALESCE(:or_no, `or_number`),
                            `payment_status` = :payment_status
                        WHERE `request_id` = :id
                    ");
                    $updateStmt->execute([
                        ':status' => $newStatus,
                        ':review_notes' => $reviewNotes,
                        ':verification_notes' => $reviewNotes,
                        ':rejection_reason' => $rejectionReason,
                        ':reviewed_by' => $reviewer,
                        ':new_stat1' => $newStatus,
                        ':reviewer1' => $reviewer,
                        ':new_stat2' => $newStatus,
                        ':released_at' => $releasedAt,
                        ':new_stat3' => $newStatus,
                        ':reviewer2' => $reviewer,
                        ':or_no' => $orNo,
                        ':payment_status' => $pStatus,
                        ':id' => $reqId
                    ]);

                    // If marked Released, ensure logged into issued_certificates & certificate_payments
                    if ($isReleased) {
                        try {
                            $checkIssued = $pdo->prepare("SELECT id, certificate_control_no FROM `issued_certificates` WHERE `reference_no` = :ref LIMIT 1");
                            $checkIssued->execute([':ref' => $refNo]);
                            $existingIssued = $checkIssued->fetch();

                            if (!$existingIssued) {
                                $prefix = 'BRG';
                                if (stripos($certType, 'Clearance') !== false) $prefix = 'CLR';
                                elseif (stripos($certType, 'Indigency') !== false) $prefix = 'IND';
                                elseif (stripos($certType, 'Residency') !== false) $prefix = 'RES';
                                elseif (stripos($certType, 'Jobseeker') !== false) $prefix = 'JOB';
                                elseif (stripos($certType, 'Business') !== false) $prefix = 'BUS';
                                $controlNo = "{$prefix}-2026-" . str_pad((string)mt_rand(100, 9999), 4, '0', STR_PAD_LEFT);
                                $sealHash = strtoupper(substr(md5($refNo . time()), 0, 16));

                                $insCert = $pdo->prepare("
                                    INSERT INTO `issued_certificates` (
                                        `certificate_control_no`, `request_id`, `reference_no`, `citizen_id`,
                                        `citizen_name`, `certificate_type`, `purpose`, `or_number`,
                                        `fee_amount`, `released_by`, `date_released`, `security_seal_hash`
                                    ) VALUES (
                                        :control_no, :req_id, :ref_no, :citizen_id,
                                        :citizen_name, :cert_type, :purpose, :or_no,
                                        :fee_amount, :released_by, NOW(), :seal
                                    )
                                ");
                                $cId = $target['citizen_user_id'] ? 'CTZ-2026-' . str_pad($target['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-WALK-IN';
                                $insCert->execute([
                                    ':control_no' => $controlNo,
                                    ':req_id' => $target['request_id'],
                                    ':ref_no' => $refNo,
                                    ':citizen_id' => $cId,
                                    ':citizen_name' => $target['citizen_name'],
                                    ':cert_type' => $target['certificate_type'],
                                    ':purpose' => $target['purpose'],
                                    ':or_no' => $orNo ?: 'OR-NONE',
                                    ':fee_amount' => $fee,
                                    ':released_by' => $reviewer,
                                    ':seal' => $sealHash
                                ]);

                                // Insert payment entry
                                $insPay = $pdo->prepare("
                                    INSERT INTO `certificate_payments` (
                                        `or_number`, `reference_no`, `citizen_name`, `certificate_type`,
                                        `amount_due`, `amount_paid`, `payment_status`, `cashier_name`, `payment_date`
                                    ) VALUES (
                                        :or_no, :ref_no, :citizen_name, :cert_type,
                                        :amount_due, :amount_paid, :payment_status, :cashier, NOW()
                                    )
                                ");
                                $insPay->execute([
                                    ':or_no' => $orNo ?: 'OR-NONE',
                                    ':ref_no' => $refNo,
                                    ':citizen_name' => $target['citizen_name'],
                                    ':cert_type' => $target['certificate_type'],
                                    ':amount_due' => $fee,
                                    ':amount_paid' => $isWaived ? 0.00 : $fee,
                                    ':payment_status' => $isWaived ? 'Waived' : 'Paid',
                                    ':cashier' => $reviewer
                                ]);
                            }
                        } catch (Exception $issuedEx) {}
                    }

                    // Success messages matching the ID issuance workflow
                    if ($newStatus === 'Ready for Release') {
                        $alertMessage = "Certificate request status updated to 'Ready for Release'. <a href='issued-certificates.php?ref=" . urlencode($refNo) . "' class='underline font-bold ml-2 text-blue-700 hover:text-blue-900'><i class='fa-solid fa-stamp mr-1'></i>View in Issued Certificates &rarr;</a>";
                    } elseif ($newStatus === 'Ready to Print') {
                        $alertMessage = "Certificate request updated to 'Ready to Print'. The document is cleared and queued for official printing.";
                    } elseif ($newStatus === 'Approved') {
                        $alertMessage = "Certificate approved! The document is verified and ready for printing. Note: It will move to Ready for Release upon printing.";
                    } elseif ($isReleased) {
                        $alertMessage = "Certificate officially issued & released to <strong>" . htmlspecialchars($target['citizen_name']) . "</strong> (Ref: <strong>" . htmlspecialchars($refNo) . "</strong>).";
                    } elseif ($newStatus === 'Rejected') {
                        $alertMessage = "Certificate request has been marked as Rejected.";
                    } else {
                        $alertMessage = "Certificate status successfully updated to '{$newStatus}'.";
                    }
                    $alertType = 'success';
                }
            } catch (Exception $e) {
                $alertMessage = "Database error updating request: " . htmlspecialchars($e->getMessage());
                $alertType = 'error';
            }
        }
    } elseif ($action === 'edit_application') {
        $reqId = (int)($_POST['application_id'] ?? 0);
        $citizenName = trim($_POST['citizen_name'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $streetAddress = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $district = $_POST['district'] ?? 'District 1';
        $civilStatus = $_POST['civil_status'] ?? 'Single';
        $residentSince = trim($_POST['resident_since'] ?? '2015');
        $certType = trim($_POST['certificate_type'] ?? 'Barangay Clearance');
        $purpose = trim($_POST['purpose'] ?? 'Official Requirement');
        $feeAmount = (float)($_POST['fee_amount'] ?? 50.00);
        $paymentStatus = $_POST['payment_status'] ?? 'Pending';

        if ($reqId > 0 && !empty($citizenName) && !empty($contactNumber)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `certificate_requests`
                    SET `citizen_name` = :name,
                        `contact_number` = :contact,
                        `email` = :email,
                        `street_address` = :street,
                        `barangay` = :brgy,
                        `district` = :dist,
                        `civil_status` = :civil,
                        `resident_since` = :res_since,
                        `certificate_type` = :cert_type,
                        `purpose` = :purpose,
                        `fee_amount` = :fee,
                        `payment_status` = :pstatus
                    WHERE `request_id` = :id
                ");
                $stmt->execute([
                    ':name' => $citizenName,
                    ':contact' => $contactNumber,
                    ':email' => $email,
                    ':street' => $streetAddress,
                    ':brgy' => $barangay,
                    ':dist' => $district,
                    ':civil' => $civilStatus,
                    ':res_since' => $residentSince,
                    ':cert_type' => $certType,
                    ':purpose' => $purpose,
                    ':fee' => $feeAmount,
                    ':pstatus' => $paymentStatus,
                    ':id' => $reqId
                ]);
                $alertMessage = "Resident certificate details updated successfully for request #{$reqId}.";
                $alertType = 'success';
            } catch (Exception $e) {
                $alertMessage = "Error updating details: " . htmlspecialchars($e->getMessage());
                $alertType = 'error';
            }
        } else {
            $alertMessage = "Please complete all mandatory requester information.";
            $alertType = 'error';
        }
    } elseif ($action === 'delete_application') {
        $reqId = (int)($_POST['application_id'] ?? 0);
        if ($reqId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `certificate_requests` WHERE `request_id` = :id");
                $stmt->execute([':id' => $reqId]);
                $alertMessage = "Certificate request #{$reqId} has been successfully deleted.";
                $alertType = 'success';
            } catch (Exception $e) {
                $alertMessage = "Error deleting record: " . htmlspecialchars($e->getMessage());
                $alertType = 'error';
            }
        }
    } elseif ($action === 'create_walkin') {
        $citizenName = trim($_POST['citizen_name'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $streetAddress = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? 'Barangay 171');
        $district = $_POST['district'] ?? 'District 1';
        $civilStatus = $_POST['civil_status'] ?? 'Single';
        $residentSince = trim($_POST['resident_since'] ?? '2015');
        $certType = trim($_POST['certificate_type'] ?? 'Barangay Clearance');
        $purpose = trim($_POST['purpose'] ?? 'Employment');
        $primaryDoc = trim($_POST['primary_doc_name'] ?? 'Government Valid ID');
        $reviewer = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Walk-in Desk Staff';

        $prefixes = [
            'Barangay Clearance' => 'CAL-CLR',
            'Certificate of Residency' => 'CAL-RES',
            'Certificate of Indigency' => 'CAL-IND',
            'First-Time Jobseeker Certificate (RA 11261)' => 'CAL-JOB',
            'Business Permit Clearance' => 'CAL-BUS',
            'Certificate of Good Moral Character' => 'CAL-MOR',
        ];
        $prefix = $prefixes[$certType] ?? 'CAL-DOC';
        $refNo = "{$prefix}-2026-" . rand(1000, 9999);

        $feeMap = [
            'Barangay Clearance' => 75.00,
            'Certificate of Residency' => 50.00,
            'Certificate of Indigency' => 0.00,
            'First-Time Jobseeker Certificate (RA 11261)' => 0.00,
            'Business Permit Clearance' => 200.00,
            'Certificate of Good Moral Character' => 50.00
        ];
        $feeAmount = $feeMap[$certType] ?? 50.00;
        $isWaived = ($feeAmount == 0.00);
        $paymentStatus = $isWaived ? 'Waived' : 'Paid';
        $orNo = $isWaived ? ('WAIVED-' . strtoupper(substr($prefix, 4))) : ('OR-' . rand(984000, 984999));

        $docJson = json_encode([['name' => $primaryDoc, 'url' => null]]);

        if (!empty($citizenName) && !empty($contactNumber) && !empty($streetAddress)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `certificate_requests` 
                    (`reference_no`, `citizen_name`, `contact_number`, `email`, `street_address`, `barangay`, `district`, `civil_status`, `resident_since`, `certificate_type`, `purpose`, `fee_amount`, `payment_status`, `or_number`, `status`, `review_notes`, `verification_notes`, `uploaded_documents`, `encoded_by`, `reviewed_by`, `created_at`)
                    VALUES
                    (:ref_no, :name, :contact, :email, :street, :brgy, :dist, :civil, :res_since, :cert_type, :purpose, :fee, :pstatus, :or_no, 'Under Review', 'Walk-in citizen encoded at Civil Registry Intake Desk', 'Walk-in citizen encoded at Civil Registry Intake Desk', :docs, :encoded_by, :reviewer, NOW())
                ");
                $stmt->execute([
                    ':ref_no' => $refNo,
                    ':name' => $citizenName,
                    ':contact' => $contactNumber,
                    ':email' => $email,
                    ':street' => $streetAddress,
                    ':brgy' => $barangay,
                    ':dist' => $district,
                    ':civil' => $civilStatus,
                    ':res_since' => $residentSince,
                    ':cert_type' => $certType,
                    ':purpose' => $purpose,
                    ':fee' => $feeAmount,
                    ':pstatus' => $paymentStatus,
                    ':or_no' => $orNo,
                    ':docs' => $docJson,
                    ':encoded_by' => 'Civil Registry Walk-in Desk',
                    ':reviewer' => $reviewer
                ]);
                $alertMessage = "New walk-in certificate request registered successfully with Reference No: <strong>{$refNo}</strong>.";
                $alertType = 'success';
            } catch (Exception $e) {
                $alertMessage = "Error registering walk-in request: " . htmlspecialchars($e->getMessage());
                $alertType = 'error';
            }
        } else {
            $alertMessage = "Please complete all mandatory personal and address information.";
            $alertType = 'error';
        }
    }
}

// Compute Dynamic KPIs
$totalRequests = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests`")->fetchColumn();
$pendingReview = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Pending', 'Pending Review', 'Under Review')")->fetchColumn();
$approvedProduction = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Approved', 'Ready to Print')")->fetchColumn();
$readyReleased = (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `status` IN ('Ready for Release', 'Released', 'Claimed')")->fetchColumn();

// Fetch Certificate Applications with Filters
$selectedCategory = $_GET['category'] ?? 'all';
$selectedStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM `certificate_requests` WHERE 1=1";
$params = [];

if ($selectedCategory !== 'all') {
    $sql .= " AND `certificate_type` LIKE :category";
    $params[':category'] = "%{$selectedCategory}%";
}
if ($selectedStatus !== 'all') {
    if ($selectedStatus === 'Pending Review' || $selectedStatus === 'Pending') {
        $sql .= " AND `status` IN ('Pending', 'Pending Review')";
    } elseif ($selectedStatus === 'Released' || $selectedStatus === 'Claimed') {
        $sql .= " AND `status` IN ('Released', 'Claimed')";
    } else {
        $sql .= " AND `status` = :status";
        $params[':status'] = $selectedStatus;
    }
}
if (!empty($searchQuery)) {
    $sql .= " AND (`reference_no` LIKE :q1 OR `citizen_name` LIKE :q2 OR `barangay` LIKE :q3 OR `purpose` LIKE :q4)";
    $likeTerm = "%{$searchQuery}%";
    $params[':q1'] = $likeTerm;
    $params[':q2'] = $likeTerm;
    $params[':q3'] = $likeTerm;
    $params[':q4'] = $likeTerm;
}

$sql .= " ORDER BY `request_id` DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dbRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts per category for pills
$catCounts = [
    'all' => $totalRequests,
    'Clearance' => (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `certificate_type` LIKE '%Clearance%'")->fetchColumn(),
    'Residency' => (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `certificate_type` LIKE '%Residency%'")->fetchColumn(),
    'Indigency' => (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `certificate_type` LIKE '%Indigency%'")->fetchColumn(),
    'Jobseeker' => (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `certificate_type` LIKE '%Jobseeker%'")->fetchColumn(),
    'Business' => (int)$pdo->query("SELECT COUNT(*) FROM `certificate_requests` WHERE `certificate_type` LIKE '%Business%'")->fetchColumn(),
];

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

    @media print {
        body * {
            visibility: hidden;
        }
        #printableCertificateArea, #printableCertificateArea * {
            visibility: visible;
        }
        #printableCertificateArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            padding: 24px;
        }
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header Bar -->
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
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Municipal & Barangay Certificate Issuance</h1>
                <p class="text-xs text-slate-500 font-medium">Verify, approve, and issue resident barangay certifications submitted through the Citizen App & City Hall walk-in desks.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="openWalkinModal()" class="px-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-user-plus text-[#0f53d1] text-xs"></i>
                <span>Manual Walk-in Entry</span>
            </button>
            <button onclick="exportCertLogCSV()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-csv text-xs"></i>
                <span>Export Certificate Records</span>
            </button>
        </div>
    </div>

    <?php if (!empty($alertMessage)): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between <?php echo $alertType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
        <div class="flex items-center gap-2">
            <i class="<?php echo $alertType === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-exclamation text-rose-600'; ?> text-sm"></i>
            <span><?php echo $alertMessage; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- Accountability Stat Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Applications -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Certificate Requests</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $totalRequests; ?></h3>
                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-layer-group text-slate-400"></i>
                    <span>Aggregated Civic Registries</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Pending Verification -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Review</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $pendingReview; ?></h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Awaiting staff validation</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Approved & In Production -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Approved / Printing</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base border border-indigo-100">
                    <i class="fa-solid fa-stamp"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $approvedProduction; ?></h3>
                <p class="text-[11px] font-semibold text-indigo-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-print"></i>
                    <span>Ready for printing & stamping</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Ready for Release & Issued -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ready / Released</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $readyReleased; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-certificate"></i>
                    <span>Released or ready at claim desk</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar">
        <?php
        $pills = [
            'all' => 'All Certificates',
            'Clearance' => 'Barangay Clearance',
            'Residency' => 'Certificate of Residency',
            'Indigency' => 'Certificate of Indigency',
            'Jobseeker' => 'First-Time Jobseeker (RA 11261)',
            'Business' => 'Business Clearance'
        ];
        foreach ($pills as $k => $label):
            $isActive = ($selectedCategory === $k);
            $cnt = $catCounts[$k] ?? 0;
            $url = "?category=" . urlencode($k) . ($selectedStatus !== 'all' ? "&status=" . urlencode($selectedStatus) : "") . (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : "");
        ?>
        <a href="<?php echo $url; ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold tracking-tight whitespace-nowrap transition flex items-center gap-2 border <?php echo $isActive ? 'bg-[#0f53d1] text-white border-[#0f53d1] shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200'; ?>">
            <span><?php echo $label; ?></span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black <?php echo $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'; ?>">
                <?php echo $cnt; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Secondary Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <form method="GET" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">
            
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by Ref No, citizen name, barangay, or purpose..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 rounded-xl text-xs font-medium outline-none focus:bg-white focus:border-[#0f53d1] transition">
            </div>

            <div class="w-full sm:w-48">
                <select name="status" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl py-2 px-3 text-xs font-semibold outline-none focus:bg-white focus:border-[#0f53d1] transition cursor-pointer">
                    <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Pending Review" <?php echo ($selectedStatus === 'Pending Review' || $selectedStatus === 'Pending') ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="Under Review" <?php echo $selectedStatus === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                    <option value="Approved" <?php echo $selectedStatus === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Ready to Print" <?php echo $selectedStatus === 'Ready to Print' ? 'selected' : ''; ?>>Ready to Print</option>
                    <option value="Ready for Release" <?php echo $selectedStatus === 'Ready for Release' ? 'selected' : ''; ?>>Ready for Release</option>
                    <option value="Released" <?php echo ($selectedStatus === 'Released' || $selectedStatus === 'Claimed') ? 'selected' : ''; ?>>Released</option>
                    <option value="Rejected" <?php echo $selectedStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition cursor-pointer shadow-xs">
                Filter
            </button>
            <?php if (!empty($searchQuery) || $selectedStatus !== 'all' || $selectedCategory !== 'all'): ?>
            <a href="certificate-requests.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition text-center">
                Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Certificate Requests Applications Registry Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-800">Certificate Applications Registry</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-[#0f53d1] border border-blue-100">
                    <?php echo count($dbRequests); ?> Records
                </span>
            </div>
            <p class="text-[11px] text-slate-400">Click any row to open the complete application inspector & management actions</p>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]" id="certTable">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Reference No</th>
                        <th class="py-3 px-3">Applicant Profile</th>
                        <th class="py-3 px-3">Certificate Type</th>
                        <th class="py-3 px-3">Purpose & Fee</th>
                        <th class="py-3 px-3 text-center">Verification Status</th>
                        <th class="py-3 px-3">Submitted Date</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($dbRequests)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fa-solid fa-inbox text-3xl mb-2 opacity-40 block text-[#0f53d1]"></i>
                            No certificate requests match the specified filters.<br>
                            <span class="text-[11px] text-slate-400 mt-1 block">Try clearing your search or filter options.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($dbRequests as $req): 
                        $status = $req['status'];
                        $badgeClasses = 'bg-slate-100 text-slate-700 border-slate-200';
                        if ($status === 'Pending' || $status === 'Pending Review') $badgeClasses = 'bg-amber-50 text-amber-700 border-amber-200';
                        elseif ($status === 'Under Review') $badgeClasses = 'bg-blue-50 text-blue-700 border-blue-200';
                        elseif ($status === 'Approved') $badgeClasses = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                        elseif ($status === 'Ready to Print') $badgeClasses = 'bg-sky-50 text-sky-700 border-sky-200';
                        elseif ($status === 'Ready for Release') $badgeClasses = 'bg-purple-50 text-purple-700 border-purple-200';
                        elseif ($status === 'Released' || $status === 'Claimed') $badgeClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        elseif ($status === 'Rejected') $badgeClasses = 'bg-rose-50 text-rose-700 border-rose-200';

                        $nameParts = explode(' ', trim($req['citizen_name']));
                        $initials = strtoupper(substr($nameParts[0] ?? 'C', 0, 1) . substr($nameParts[count($nameParts)-1] ?? 'Z', 0, 1));
                        $cId = $req['citizen_user_id'] ? 'CTZ-2026-' . str_pad($req['citizen_user_id'], 4, '0', STR_PAD_LEFT) : 'CTZ-WALK-IN';
                    ?>
                    <tr class="hover:bg-slate-50 transition cursor-pointer select-none" onclick="openCitizenDetailsModal(<?php echo htmlspecialchars(json_encode($req), ENT_QUOTES, 'UTF-8'); ?>)">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-bold text-[#0f53d1] text-xs block"><?php echo htmlspecialchars($req['reference_no']); ?></span>
                            <span class="text-[10px] text-slate-400 font-semibold"><?php echo $cId; ?></span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-slate-200 to-blue-100 text-[#0f53d1] font-black text-xs flex items-center justify-center shrink-0 border border-blue-200">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 text-xs truncate"><?php echo htmlspecialchars($req['citizen_name']); ?></p>
                                    <span class="text-[10px] text-slate-400 block truncate"><?php echo htmlspecialchars($req['barangay'] ?: 'Caloocan City'); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-slate-900 text-xs block"><?php echo htmlspecialchars($req['certificate_type']); ?></span>
                            <span class="text-[10px] text-slate-400 font-medium truncate max-w-xs block"><?php echo htmlspecialchars($req['street_address'] ?: 'Caloocan City'); ?></span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="font-medium text-slate-800 text-xs truncate max-w-xs block"><?php echo htmlspecialchars($req['purpose']); ?></span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="font-bold text-slate-700 text-[10px]">₱<?php echo number_format((float)$req['fee_amount'], 2); ?></span>
                                <span class="text-[9px] px-1.5 py-0.2 rounded font-bold <?php echo $req['payment_status'] === 'Waived' ? 'bg-purple-50 text-purple-600 border border-purple-100' : ($req['payment_status'] === 'Paid' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500'); ?>">
                                    <?php echo htmlspecialchars($req['payment_status']); ?>
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo $badgeClasses; ?>">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-3 whitespace-nowrap">
                            <p class="font-bold text-slate-800 text-[11px]"><?php echo date('M j, Y', strtotime($req['created_at'])); ?></p>
                            <p class="text-[10px] text-slate-400 font-medium"><?php echo htmlspecialchars($req['encoded_by'] ?? 'Citizen Mobile App'); ?></p>
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                            <button type="button" onclick="openCitizenDetailsModal(<?php echo htmlspecialchars(json_encode($req), ENT_QUOTES, 'UTF-8'); ?>)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-sliders text-[10px] text-slate-500"></i>
                                <span>Manage</span>
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

<!-- ============================================================================== -->
<!-- MASTER MODAL: RESIDENT APPLICATION DETAILS & ACTION MANAGEMENT MODAL           -->
<!-- (Exactly mirrors the ID Issuance master modal layout and actions)              -->
<!-- ============================================================================== -->
<div id="citizenDetailsModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Light Header with Back Button, Title, Ref ID, Status, Close Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeCitizenDetailsModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Registry">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-file-certificate"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Resident Application Details</h3>
                        <span id="cdmCatTag" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200/80">CERTIFICATE</span>
                    </div>
                    <p class="text-xs text-slate-400 font-mono font-bold mt-0.5" id="cdmRef">CAL-DOC-2026-0000</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span id="cdmStatusBadge" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                    PENDING REVIEW
                </span>
                <button onclick="closeCitizenDetailsModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-5 max-h-[78vh] overflow-y-auto custom-scrollbar">

            <!-- Section 1: Citizen Profile Summary -->
            <div class="bg-slate-50/80 rounded-2xl p-4.5 border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-slate-200 to-blue-100 text-[#0f53d1] font-black text-sm flex items-center justify-center shrink-0 border border-blue-200 shadow-xs" id="cdmAvatar" style="overflow: hidden;">
                            DE
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 text-sm" id="cdmApplicantName">Danny Espelita Jr.</h4>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5 font-medium">
                                <span id="cdmGender">Male</span> • 
                                <span id="cdmCivilStatus">Single</span> • 
                                <span id="cdmCitizenIdBadge" class="text-[#0f53d1] font-bold font-mono">CTZ-2026-0001</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-[11px] text-slate-500 font-semibold">
                        <span class="text-slate-400 block text-[9px] uppercase tracking-wider font-bold">Resident Status</span>
                        <span class="text-emerald-700 font-bold" id="cdmResidentSince">Resident Since 2015</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1">
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Contact Mobile</span>
                        <span class="font-bold text-slate-800 flex items-center gap-1.5" id="cdmPhone">
                            <i class="fa-solid fa-phone text-[#0f53d1] text-xs"></i> 09630902025
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Email Address</span>
                        <span class="font-semibold text-slate-800 flex items-center gap-1.5 truncate" id="cdmEmail">
                            <i class="fa-solid fa-envelope text-[#0f53d1] text-xs"></i> resident@civentral.com
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Barangay & District</span>
                        <span class="font-semibold text-slate-800 flex items-center gap-1.5" id="cdmBarangayDistrict">
                            <i class="fa-solid fa-location-dot text-rose-500 text-xs"></i> Barangay 171 • District 1
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Residential Address</span>
                        <span class="font-semibold text-slate-800 truncate" id="cdmAddress">121 Sampaguita St.</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Certificate Application & Requirements Specifications -->
            <div class="bg-white rounded-2xl p-4.5 border border-slate-200/90 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Certificate Type & Authority</span>
                        <h4 class="font-bold text-xs text-slate-900" id="cdmCertType">Barangay Clearance</h4>
                        <p class="text-[10px] text-slate-500 truncate" id="cdmBureau">Office of the Punong Barangay - Respective Executive Secretariat</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" id="cdmFeeBadge">
                        ₱75.00 • Paid
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Purpose of Request</span>
                        <strong class="text-slate-800 text-xs block truncate" id="cdmPurpose">Employment Requirements</strong>
                        <span class="text-[10px] text-emerald-600 font-bold">Turnaround: <span id="cdmTurnaround">Same Day to 24 Hours</span></span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Primary Requirement / Documents</span>
                        <div id="cdmDocsContainer" class="flex flex-col gap-1 text-slate-800 font-semibold text-xs mt-0.5">
                            <span class="text-slate-400 italic text-[11px]">No attachments uploaded</span>
                        </div>
                    </div>
                </div>

                <!-- Review Notes Alert Box (If present) -->
                <div id="cdmNotesBox" class="hidden bg-amber-50/70 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-800">
                    <strong class="font-bold block text-amber-900 mb-0.5">Officer Review Remarks:</strong>
                    <span id="cdmNotesText">No remarks</span>
                </div>
            </div>

            <!-- Section 3: UNIFIED ACTION BUTTONS BAR (Exact 4 Management Actions + Delete) -->
            <div class="bg-slate-50/90 p-5 rounded-2xl border border-slate-200/90 space-y-3.5 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-blue-100 text-[#0f53d1] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <span class="text-xs font-black uppercase tracking-wider text-slate-800">Management & Processing Actions</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Select an action for this citizen</span>
                </div>

                <!-- Buttons Grid (Matching ID Issuance Exactly) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Action 1: Review & Change Status -->
                    <button type="button" onclick="openReviewFromDetails()" class="px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-sliders text-sm"></i>
                        <span>Review & Change Status</span>
                    </button>

                    <!-- Action 2: Edit Details -->
                    <button type="button" onclick="openEditFromDetails()" class="px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                        <span>Edit Resident Info</span>
                    </button>

                    <!-- Action 3: Go to Release Desk / Issue -->
                    <button type="button" id="cdmReleaseBtn" onclick="openReleaseFromDetails()" class="px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-stamp text-sm"></i>
                        <span>Certificate Release Desk</span>
                    </button>

                    <!-- Action 4: View Official Civentral Certificate Preview / Canvas -->
                    <button type="button" onclick="openPreviewFromDetails()" class="px-4 py-3 bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-800 hover:to-indigo-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-file-lines text-sm"></i>
                        <span>Civentral Official Certificate</span>
                    </button>
                </div>

                <!-- Action 5: Delete Option -->
                <div class="pt-2.5 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500">Need to cancel or discard this application?</span>
                    <button type="button" onclick="deleteFromDetails()" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 font-bold text-[11px] rounded-lg transition flex items-center gap-1.5 cursor-pointer border border-rose-200">
                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                        <span>Delete Record</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SUBMODAL 1: REVIEW & CHANGE STATUS MODAL                                       -->
<!-- ============================================================================== -->
<div id="reviewModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="backToCitizenDetailsFromReview()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Citizen Details">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm border border-indigo-100">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-tight text-slate-900" id="reviewModalTitle">Review Certificate Request</h3>
                    <p class="text-[11px] text-slate-400 font-mono font-bold" id="reviewModalRef">CAL-DOC-2026-0000</p>
                </div>
            </div>
            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="application_id" id="reviewAppId" value="0">

            <!-- Applicant Summary Card -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80 space-y-2 text-xs">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <span class="font-bold text-slate-800" id="reviewApplicantName">Applicant Legal Name</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700" id="reviewCertType">Barangay Clearance</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div><span class="text-slate-400 font-medium">Contact:</span> <strong class="text-slate-700" id="reviewContact">0917-000-0000</strong></div>
                    <div><span class="text-slate-400 font-medium">Barangay:</span> <strong class="text-slate-700" id="reviewBarangay">Barangay 171</strong></div>
                    <div class="col-span-2"><span class="text-slate-400 font-medium">Full Address:</span> <strong class="text-slate-700" id="reviewAddress">Address line</strong></div>
                    <div class="col-span-2"><span class="text-slate-400 font-medium">Purpose:</span> <strong class="text-slate-700" id="reviewPurpose">Employment</strong></div>
                </div>
            </div>

            <!-- Status Control -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Update Request Status</label>
                <select name="status" id="reviewStatusSelect" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3.5 text-xs outline-none focus:border-indigo-500 focus:bg-white transition cursor-pointer">
                    <option value="Pending Review">Pending Review</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Approved">Approved (Ready for Printing)</option>
                    <option value="Ready to Print">Ready to Print</option>
                    <option value="Ready for Release">Ready for Release at Desk</option>
                    <option value="Released">Released / Handed Over to Resident</option>
                    <option value="Rejected">Rejected / Incomplete Requirements</option>
                </select>
            </div>

            <!-- Notes -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Officer Verification Notes</label>
                <textarea name="review_notes" id="reviewNotesInput" rows="2" placeholder="e.g. Identity and residency verified on file by Barangay Secretariat..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl p-3 outline-none focus:border-indigo-500 focus:bg-white transition placeholder:text-slate-400"></textarea>
            </div>

            <!-- Rejection Reason (Conditional) -->
            <div class="space-y-1.5" id="rejectionReasonBox">
                <label class="text-xs font-bold text-rose-700">Rejection Reason (If Applicable)</label>
                <input type="text" name="rejection_reason" id="reviewRejectionInput" placeholder="Specify deficiency (e.g. Unverified residency, missing requirements)" class="w-full bg-rose-50/50 border border-rose-200 text-rose-800 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-rose-400 transition placeholder:text-rose-300">
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="backToCitizenDetailsFromReview()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Details</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeReviewModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-check"></i>
                        <span>Save & Update</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SUBMODAL 2: EDIT RESIDENT & REQUEST DETAILS MODAL                              -->
<!-- ============================================================================== -->
<div id="editModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="backToCitizenDetailsFromEdit()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Citizen Details">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm border border-amber-100">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-tight text-slate-900">Edit Certificate Request Details</h3>
                    <p class="text-[11px] text-slate-400 font-mono font-bold" id="editModalRef">CAL-DOC-2026-0000</p>
                </div>
            </div>
            <button onclick="closeEditModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit_application">
            <input type="hidden" name="application_id" id="editAppId" value="0">

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Applicant Full Legal Name *</label>
                    <input type="text" name="citizen_name" id="editCitizenName" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Contact Number *</label>
                    <input type="text" name="contact_number" id="editContact" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" id="editEmail" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Street Address *</label>
                    <input type="text" name="street_address" id="editAddress" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Barangay *</label>
                    <input type="text" name="barangay" id="editBarangay" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">District</label>
                    <select name="district" id="editDistrict" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="District 1">District 1</option>
                        <option value="District 2">District 2</option>
                        <option value="District 3">District 3</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Civil Status</label>
                    <select name="civil_status" id="editCivilStatus" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Resident Since</label>
                    <input type="text" name="resident_since" id="editResidentSince" placeholder="e.g. 2015" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Certificate Type</label>
                    <select name="certificate_type" id="editCertType" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="Barangay Clearance">Barangay Clearance</option>
                        <option value="Certificate of Residency">Certificate of Residency</option>
                        <option value="Certificate of Indigency">Certificate of Indigency</option>
                        <option value="First-Time Jobseeker Certificate (RA 11261)">First-Time Jobseeker Certificate (RA 11261)</option>
                        <option value="Business Permit Clearance">Business Permit Clearance</option>
                        <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                    </select>
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Purpose</label>
                    <input type="text" name="purpose" id="editPurpose" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Official Fee (₱)</label>
                    <input type="number" step="0.01" name="fee_amount" id="editFeeAmount" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Payment Status</label>
                    <select name="payment_status" id="editPaymentStatus" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="Paid">Paid</option>
                        <option value="Waived">Waived</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="backToCitizenDetailsFromEdit()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Details</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SUBMODAL 3: OFFICIAL CERTIFICATE CANVAS PREVIEW (PRINTABLE)                    -->
<!-- ============================================================================== -->
<div id="previewModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="backToCitizenDetailsFromPreview()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Citizen Details">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm border border-blue-100">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <h3 class="font-bold text-sm tracking-tight text-slate-900">Official Barangay Certificate Preview</h3>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-3.5 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Official Certificate</span>
                </button>
                <button id="markReadyFromPrintBtn" type="button" onclick="markReadyFromPrint()" class="hidden px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs" title="Send printed certificate to the Release Desk">
                    <i class="fa-solid fa-stamp"></i>
                    <span>Mark Printed & Ready for Release</span>
                </button>
                <button onclick="closePreviewModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="p-6 md:p-8 space-y-6" id="printableCertificateArea">
            <!-- Official Barangay Letterhead Template Canvas -->
            <div class="border-2 border-slate-300 rounded-2xl p-8 md:p-10 bg-white text-slate-900 font-serif space-y-6 relative overflow-hidden shadow-xs">
                
                <!-- Watermark Background Seal -->
                <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none select-none">
                    <i class="fa-solid fa-landmark text-[280px]"></i>
                </div>

                <!-- Letterhead Header -->
                <div class="text-center space-y-1 border-b-2 border-slate-900 pb-5">
                    <p class="text-[11px] tracking-widest uppercase font-sans text-slate-500 font-semibold">Republic of the Philippines</p>
                    <p class="text-xs tracking-wider uppercase font-sans font-bold text-slate-700">City of Caloocan • District 1</p>
                    <h2 class="text-base md:text-lg font-black tracking-wide uppercase font-sans text-slate-900">Office of the Punong Barangay</h2>
                    <p class="text-[10px] uppercase font-sans tracking-widest text-slate-400 font-semibold">Barangay 171 Executive Council & Secretariat</p>
                </div>

                <!-- Certificate Title -->
                <div class="text-center pt-2">
                    <h1 id="certCanvasTitle" class="text-xl md:text-2xl font-black uppercase tracking-wider border-b-2 border-slate-900 inline-block pb-1 font-sans text-slate-900">BARANGAY CLEARANCE</h1>
                </div>

                <!-- Legal Certification Body -->
                <div class="space-y-4 text-xs md:text-sm leading-relaxed text-justify pt-3">
                    <p class="font-sans font-bold text-xs uppercase tracking-wider text-slate-600">TO WHOM IT MAY CONCERN:</p>
                    
                    <p id="certCanvasPara1">
                        This is to officially certify that <strong id="certCanvasCitizenName" class="underline font-black font-sans uppercase">CITIZEN NAME</strong>, of legal age, <span id="certCanvasCivilStatus">Single</span>, Filipino, is a bona fide resident of this Barangay residing at <strong id="certCanvasAddress">0025 Sampaguita Street, Barangay 171</strong> with good moral standing in the civic community.
                    </p>

                    <p id="certCanvasPara2">
                        Records on file in this office show that the above-named person has <strong>NO DEROGATORY RECORD</strong> or pending administrative case filed against them as of this date.
                    </p>

                    <p id="certCanvasPara3">
                        This certification is issued upon the official request of the interested party for <strong id="certCanvasPurpose" class="font-bold">Employment</strong> purposes.
                    </p>

                    <p class="text-[11px] text-slate-500 pt-3 font-sans">
                        Given and signed at the Barangay Hall, City of Caloocan, Metro Manila, Philippines.
                    </p>
                </div>

                <!-- Signature & Seal Footer -->
                <div class="pt-8 flex items-end justify-between border-t border-slate-200">
                    <div class="text-center">
                        <div class="w-16 h-16 border border-slate-300 rounded-xl flex items-center justify-center mx-auto text-slate-400 text-xs font-sans bg-slate-50 shadow-inner">
                            <i class="fa-solid fa-qrcode text-3xl text-slate-600"></i>
                        </div>
                        <span id="certCanvasControlNo" class="text-[9px] font-mono font-bold text-slate-500 block mt-1.5">CAL-DOC-2026-0000</span>
                    </div>

                    <div class="text-center">
                        <div class="w-56 border-b-2 border-slate-900 mx-auto mb-1"></div>
                        <p class="font-sans font-black text-xs uppercase text-slate-900">HON. PUNONG BARANGAY</p>
                        <p class="font-sans text-[10px] text-slate-500 font-medium">Barangay Captain • City of Caloocan</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-100 flex items-center justify-between">
            <button type="button" onclick="backToCitizenDetailsFromPreview()" class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Details</span>
            </button>
            <button onclick="closePreviewModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl transition cursor-pointer">Close</button>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SUBMODAL 4: MANUAL WALK-IN REGISTRATION MODAL                                  -->
<!-- ============================================================================== -->
<div id="walkinModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm border border-blue-100">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="font-bold text-sm tracking-tight text-slate-900">Manual Walk-in Certificate Registration</h3>
            </div>
            <button onclick="closeWalkinModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_walkin">

            <div class="grid grid-cols-2 gap-3 text-xs">
                <!-- Certificate Type -->
                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Certificate Document Type *</label>
                    <select name="certificate_type" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                        <option value="Barangay Clearance">Barangay Clearance (₱75.00)</option>
                        <option value="Certificate of Residency">Certificate of Residency (₱50.00)</option>
                        <option value="Certificate of Indigency">Certificate of Indigency (₱0.00 - Free)</option>
                        <option value="First-Time Jobseeker Certificate (RA 11261)">First-Time Jobseeker Certificate RA 11261 (₱0.00 - Free)</option>
                        <option value="Business Permit Clearance">Business Permit Clearance (₱200.00)</option>
                        <option value="Certificate of Good Moral Character">Certificate of Good Moral Character (₱50.00)</option>
                    </select>
                </div>

                <!-- Names -->
                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Applicant Full Legal Name *</label>
                    <input type="text" name="citizen_name" required placeholder="e.g. Juan dela Cruz" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Contact Mobile *</label>
                    <input type="text" name="contact_number" required placeholder="0917-000-0000" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" placeholder="citizen@example.com" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Street Address *</label>
                    <input type="text" name="street_address" required placeholder="House No, Block & Lot, Street" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Barangay *</label>
                    <input type="text" name="barangay" required value="Barangay 171" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">District</label>
                    <select name="district" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                        <option value="District 1">District 1</option>
                        <option value="District 2">District 2</option>
                        <option value="District 3">District 3</option>
                    </select>
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Purpose of Certification *</label>
                    <input type="text" name="purpose" required placeholder="e.g. Employment / Scholarship / Government Agency" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Primary Document Presented</label>
                    <input type="text" name="primary_doc_name" value="PhilSys National ID" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-[#0f53d1]">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeWalkinModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>Register Request</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteAppForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_application">
    <input type="hidden" name="application_id" id="deleteAppId" value="0">
</form>

<script>
let selectedAppForModal = null;

function openCitizenDetailsModal(app) {
    if (!app) return;
    selectedAppForModal = app;

    // Header Info
    document.getElementById('cdmRef').innerText = app.reference_no;
    
    // Tag and Authority
    const certType = app.certificate_type || 'Barangay Certificate';
    document.getElementById('cdmCertType').innerText = certType;
    let catTag = 'CERTIFICATE';
    if (certType.toLowerCase().includes('clearance')) catTag = 'CLEARANCE';
    else if (certType.toLowerCase().includes('residency')) catTag = 'RESIDENCY';
    else if (certType.toLowerCase().includes('indigency')) catTag = 'INDIGENCY';
    else if (certType.toLowerCase().includes('jobseeker')) catTag = 'JOBSEEKER';
    else if (certType.toLowerCase().includes('business')) catTag = 'BUSINESS';
    document.getElementById('cdmCatTag').innerText = catTag;

    // Status Badge
    const badge = document.getElementById('cdmStatusBadge');
    badge.innerText = (app.status || 'PENDING REVIEW').toUpperCase();
    badge.className = 'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border ';
    const st = (app.status || '').toLowerCase();
    if (st.includes('pending')) badge.className += 'bg-amber-50 text-amber-700 border-amber-200';
    else if (st.includes('under review')) badge.className += 'bg-blue-50 text-blue-700 border-blue-200';
    else if (st.includes('approved')) badge.className += 'bg-indigo-50 text-indigo-700 border-indigo-200';
    else if (st.includes('ready to print')) badge.className += 'bg-sky-50 text-sky-700 border-sky-200';
    else if (st.includes('ready for release')) badge.className += 'bg-purple-50 text-purple-700 border-purple-200';
    else if (st.includes('released') || st.includes('claimed')) badge.className += 'bg-emerald-50 text-emerald-700 border-emerald-200';
    else if (st.includes('rejected')) badge.className += 'bg-rose-50 text-rose-700 border-rose-200';
    else badge.className += 'bg-slate-100 text-slate-700 border-slate-200';

    // Requester Profile
    const fullName = app.citizen_name || 'Resident';
    document.getElementById('cdmApplicantName').innerText = fullName;
    const parts = fullName.split(' ');
    const initials = (parts[0] ? parts[0][0] : 'C') + (parts.length > 1 ? parts[parts.length-1][0] : 'R');
    document.getElementById('cdmAvatar').innerText = initials.toUpperCase();
    
    document.getElementById('cdmGender').innerText = app.gender || 'Resident';
    document.getElementById('cdmCivilStatus').innerText = app.civil_status || 'Single';
    document.getElementById('cdmResidentSince').innerText = 'Resident Since ' + (app.resident_since || '2015');
    
    const cId = app.citizen_user_id ? 'CTZ-2026-' + String(app.citizen_user_id).padStart(4, '0') : 'CTZ-WALK-IN';
    document.getElementById('cdmCitizenIdBadge').innerText = cId;
    
    document.getElementById('cdmPhone').innerHTML = `<i class="fa-solid fa-phone text-[#0f53d1] text-xs"></i> ${app.contact_number || '0917-000-0000'}`;
    document.getElementById('cdmEmail').innerHTML = `<i class="fa-solid fa-envelope text-[#0f53d1] text-xs"></i> ${app.email || 'resident@civentral.com'}`;
    document.getElementById('cdmBarangayDistrict').innerHTML = `<i class="fa-solid fa-location-dot text-rose-500 text-xs"></i> ${(app.barangay || 'Barangay 171')} • ${(app.district || 'District 1')}`;
    document.getElementById('cdmAddress').innerText = app.street_address || 'Caloocan City';

    // Application Specifications
    document.getElementById('cdmPurpose').innerText = app.purpose || 'Civic Requirement';
    const feeVal = parseFloat(app.fee_amount || 0).toFixed(2);
    document.getElementById('cdmFeeBadge').innerText = `₱${feeVal} • ${app.payment_status || 'Pending'}`;

    // Supporting Documents
    const docsCont = document.getElementById('cdmDocsContainer');
    docsCont.innerHTML = '';
    let parsedDocs = [];
    if (app.uploaded_documents) {
        try {
            parsedDocs = typeof app.uploaded_documents === 'string' ? JSON.parse(app.uploaded_documents) : app.uploaded_documents;
        } catch(e) {}
    }
    if (Array.isArray(parsedDocs) && parsedDocs.length > 0) {
        parsedDocs.forEach(d => {
            const docName = typeof d === 'object' ? (d.name || 'Supporting Document') : d;
            const docUrl = typeof d === 'object' ? d.url : null;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-1.5';
            if (docUrl) {
                div.innerHTML = `<i class="fa-solid fa-file-circle-check text-emerald-600"></i> <a href="${docUrl}" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1">${docName} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a>`;
            } else {
                div.innerHTML = `<i class="fa-solid fa-file-circle-check text-emerald-600"></i> <span>${docName}</span>`;
            }
            docsCont.appendChild(div);
        });
    } else {
        docsCont.innerHTML = '<span class="text-slate-400 italic text-[11px]">No attachments uploaded (Intake verified)</span>';
    }

    // Remarks box
    const notesBox = document.getElementById('cdmNotesBox');
    const notesText = document.getElementById('cdmNotesText');
    const remarks = app.review_notes || app.verification_notes || app.rejection_reason;
    if (remarks) {
        notesBox.classList.remove('hidden');
        notesText.innerText = remarks;
    } else {
        notesBox.classList.add('hidden');
    }

    // Open Modal
    document.getElementById('citizenDetailsModal').classList.remove('hidden');
}

function closeCitizenDetailsModal() {
    document.getElementById('citizenDetailsModal').classList.add('hidden');
}

function openReviewFromDetails() {
    if (!selectedAppForModal) return;
    closeCitizenDetailsModal();
    openReviewModal(selectedAppForModal);
}

function openEditFromDetails() {
    if (!selectedAppForModal) return;
    closeCitizenDetailsModal();
    openEditModal(selectedAppForModal);
}

function openPreviewFromDetails() {
    if (!selectedAppForModal) return;
    closeCitizenDetailsModal();
    openPreviewModal(selectedAppForModal);
}

function openReleaseFromDetails() {
    if (!selectedAppForModal) return;
    const stat = (selectedAppForModal.status || '').toLowerCase();
    if (stat === 'approved' || stat === 'ready to print') {
        alert(`Request ${selectedAppForModal.reference_no} is currently marked as Approved/Printing. Please print the official certificate and update its status to 'Ready for Release' or 'Released'.`);
        return;
    }

    if (confirm(`Confirm certificate release & dispatch for ${selectedAppForModal.reference_no}?\n\nThis will mark the certificate as 'Released', generate the official control number, and log it in the audit registry.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="application_id" value="${selectedAppForModal.request_id}">
            <input type="hidden" name="status" value="Released">
            <input type="hidden" name="review_notes" value="Official document printed, dry-sealed, and released to citizen at the Records Desk.">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteFromDetails() {
    if (!selectedAppForModal) return;
    confirmDeleteApp(selectedAppForModal.request_id, selectedAppForModal.reference_no);
}

function confirmDeleteApp(id, ref) {
    if (confirm(`Are you sure you want to permanently delete certificate request ${ref}? This action cannot be undone.`)) {
        document.getElementById('deleteAppId').value = id;
        document.getElementById('deleteAppForm').submit();
    }
}

function backToCitizenDetailsFromReview() {
    closeReviewModal();
    if (selectedAppForModal) openCitizenDetailsModal(selectedAppForModal);
}

function backToCitizenDetailsFromEdit() {
    closeEditModal();
    if (selectedAppForModal) openCitizenDetailsModal(selectedAppForModal);
}

function backToCitizenDetailsFromPreview() {
    closePreviewModal();
    if (selectedAppForModal) openCitizenDetailsModal(selectedAppForModal);
}

function openReviewModal(app) {
    document.getElementById('reviewAppId').value = app.request_id;
    document.getElementById('reviewModalRef').innerText = app.reference_no;
    document.getElementById('reviewModalTitle').innerText = 'Review ' + (app.certificate_type || 'Certificate Request');
    document.getElementById('reviewApplicantName').innerText = app.citizen_name;
    document.getElementById('reviewCertType').innerText = app.certificate_type;
    document.getElementById('reviewContact').innerText = app.contact_number;
    document.getElementById('reviewBarangay').innerText = (app.barangay || 'Barangay 171') + ' (' + (app.district || 'District 1') + ')';
    document.getElementById('reviewAddress').innerText = app.street_address;
    document.getElementById('reviewPurpose').innerText = app.purpose;

    document.getElementById('reviewStatusSelect').value = app.status;
    document.getElementById('reviewNotesInput').value = app.review_notes || app.verification_notes || '';
    document.getElementById('reviewRejectionInput').value = app.rejection_reason || '';

    document.getElementById('reviewModal').classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

function openEditModal(app) {
    document.getElementById('editAppId').value = app.request_id;
    document.getElementById('editModalRef').innerText = app.reference_no;
    document.getElementById('editCitizenName').value = app.citizen_name || '';
    document.getElementById('editContact').value = app.contact_number || '';
    document.getElementById('editEmail').value = app.email || '';
    document.getElementById('editAddress').value = app.street_address || '';
    document.getElementById('editBarangay').value = app.barangay || '';
    document.getElementById('editDistrict').value = app.district || 'District 1';
    document.getElementById('editCivilStatus').value = app.civil_status || 'Single';
    document.getElementById('editResidentSince').value = app.resident_since || '2015';
    document.getElementById('editCertType').value = app.certificate_type || 'Barangay Clearance';
    document.getElementById('editPurpose').value = app.purpose || '';
    document.getElementById('editFeeAmount').value = app.fee_amount || '50.00';
    document.getElementById('editPaymentStatus').value = app.payment_status || 'Pending';

    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openPreviewModal(app) {
    const certType = (app.certificate_type || 'Barangay Clearance').toUpperCase();
    document.getElementById('certCanvasTitle').innerText = certType;
    document.getElementById('certCanvasCitizenName').innerText = (app.citizen_name || 'CITIZEN NAME').toUpperCase();
    document.getElementById('certCanvasCivilStatus').innerText = app.civil_status || 'Single';
    document.getElementById('certCanvasAddress').innerText = `${app.street_address || 'Caloocan City'}, ${app.barangay || 'Barangay 171'}`;
    document.getElementById('certCanvasPurpose').innerText = app.purpose || 'Official Requirement';
    document.getElementById('certCanvasControlNo').innerText = app.reference_no;

    const printReadyBtn = document.getElementById('markReadyFromPrintBtn');
    if (printReadyBtn) {
        if (app.status === 'Approved' || app.status === 'Ready to Print') {
            printReadyBtn.classList.remove('hidden');
        } else {
            printReadyBtn.classList.add('hidden');
        }
    }

    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

function markReadyFromPrint() {
    if (!selectedAppForModal) return;
    if (confirm(`Confirm certificate printing is complete for ${selectedAppForModal.reference_no}?\n\nThis will update status to 'Ready for Release' for citizen pick-up.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="application_id" value="${selectedAppForModal.request_id}">
            <input type="hidden" name="status" value="Ready for Release">
            <input type="hidden" name="review_notes" value="Official certificate printed on official security parchment and queued at the Releasing Desk.">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openWalkinModal() {
    document.getElementById('walkinModal').classList.remove('hidden');
}

function closeWalkinModal() {
    document.getElementById('walkinModal').classList.add('hidden');
}

function exportCertLogCSV() {
    let csv = "Reference No,Citizen Name,Certificate Type,Purpose,Barangay,Fee Amount,Payment Status,Status,Submitted Date\n";
    let rows = document.querySelectorAll("#certTable tbody tr");
    rows.forEach(tr => {
        let cols = tr.querySelectorAll("td");
        if (cols.length >= 6) {
            let ref = cols[0].innerText.replace(/\n/g, ' ').trim();
            let name = cols[1].innerText.replace(/\n/g, ' ').trim();
            let cert = cols[2].innerText.replace(/\n/g, ' ').trim();
            let purp = cols[3].innerText.replace(/\n/g, ' ').trim();
            let stat = cols[4].innerText.trim();
            let date = cols[5].innerText.replace(/\n/g, ' ').trim();
            csv += `"${ref}","${name}","${cert}","${purp}","${stat}","${date}"\n`;
        }
    });

    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement("a");
    let url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `Caloocan_Certificate_Issuance_Registry_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Modal dismiss listeners (Backdrop click and Escape key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCitizenDetailsModal();
        closeReviewModal();
        closeEditModal();
        closePreviewModal();
        closeWalkinModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    ['citizenDetailsModal', 'reviewModal', 'editModal', 'previewModal', 'walkinModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
