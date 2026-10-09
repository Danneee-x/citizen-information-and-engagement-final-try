<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Dedicated Certificate & ID Issuance Database Connection
$pdo = getCertificateDbConnection();

// Auto-create id_issuance_applications table if not exists
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
        `primary_doc_name` VARCHAR(150) NULL,
        `primary_doc_url` TEXT NULL,
        `photo_2x2_url` TEXT NULL,
        `support_doc_name` VARCHAR(150) NULL,
        `support_doc_url` TEXT NULL,
        `status` ENUM('Pending Review', 'Under Review', 'Approved', 'Ready for Release', 'Claimed', 'Rejected') NOT NULL DEFAULT 'Pending Review',
        `claim_office` VARCHAR(200) NULL,
        `estimated_turnaround` VARCHAR(100) NULL,
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
        INDEX idx_brgy (`barangay`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}

// Handle POST actions (Update Status, Review, or Manual Walk-in Encoding)
$alertMessage = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $appId = (int)($_POST['application_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $reviewNotes = trim($_POST['review_notes'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        $reviewer = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Verification Officer';

        if ($appId > 0 && !empty($newStatus)) {
            $releasedAt = ($newStatus === 'Claimed') ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("
                UPDATE `id_issuance_applications` 
                SET `status` = :status, 
                    `review_notes` = :review_notes, 
                    `rejection_reason` = :rejection_reason,
                    `reviewed_by` = :reviewed_by,
                    `reviewed_at` = NOW(),
                    `released_at` = COALESCE(:released_at, `released_at`),
                    `released_by` = CASE WHEN :new_status = 'Claimed' THEN :reviewer ELSE `released_by` END
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':status' => $newStatus,
                ':review_notes' => $reviewNotes,
                ':rejection_reason' => $rejectionReason,
                ':reviewed_by' => $reviewer,
                ':released_at' => $releasedAt,
                ':new_status' => $newStatus,
                ':reviewer' => $reviewer,
                ':id' => $appId
            ]);

            // Fetch reference no for direct release link
            $refStmt = $pdo->prepare("SELECT `reference_no` FROM `id_issuance_applications` WHERE `id` = :id");
            $refStmt->execute([':id' => $appId]);
            $updatedRef = $refStmt->fetchColumn() ?: '';

            if ($newStatus === 'Ready for Release') {
                $alertMessage = "Application status updated to 'Ready for Release'. <a href='id-releases.php?ref=" . urlencode($updatedRef) . "' class='underline font-bold ml-2 text-indigo-700 hover:text-indigo-900'><i class='fa-solid fa-hand-holding-hand mr-1'></i>Open in ID Release Desk &rarr;</a>";
            } elseif ($newStatus === 'Approved') {
                $alertMessage = "Application approved! The card is now ready for printing in production. Note: It will not appear on the Release & Pick-up Desk until it has been printed and marked as 'Ready for Release'.";
            } else {
                $alertMessage = "Application status successfully updated to '{$newStatus}'.";
            }
            $alertType = 'success';
        }
    } elseif ($action === 'edit_application') {
        $appId = (int)($_POST['application_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $gender = $_POST['gender'] ?? 'Male';
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        $civilStatus = $_POST['civil_status'] ?? 'Single';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $street = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $district = $_POST['district'] ?? 'District 1';
        $residentSince = trim($_POST['resident_since'] ?? '2015');

        if ($appId > 0 && !empty($firstName) && !empty($lastName) && !empty($contactNumber)) {
            $stmt = $pdo->prepare("
                UPDATE `id_issuance_applications`
                SET `first_name` = :first,
                    `middle_name` = :middle,
                    `last_name` = :last,
                    `suffix` = :suffix,
                    `gender` = :gender,
                    `birthdate` = :bday,
                    `civil_status` = :civil,
                    `contact_number` = :contact,
                    `email` = :email,
                    `street_address` = :street,
                    `barangay` = :brgy,
                    `district` = :dist,
                    `resident_since` = :res_since
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':first' => $firstName,
                ':middle' => $middleName,
                ':last' => $lastName,
                ':suffix' => $suffix,
                ':gender' => $gender,
                ':bday' => $birthdate,
                ':civil' => $civilStatus,
                ':contact' => $contactNumber,
                ':email' => $email,
                ':street' => $street,
                ':brgy' => $barangay,
                ':dist' => $district,
                ':res_since' => $residentSince,
                ':id' => $appId
            ]);
            $alertMessage = "Resident information updated successfully for application #{$appId}.";
            $alertType = 'success';
        } else {
            $alertMessage = "Please provide required name and contact information.";
            $alertType = 'error';
        }
    } elseif ($action === 'delete_application') {
        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId > 0) {
            $stmt = $pdo->prepare("DELETE FROM `id_issuance_applications` WHERE `id` = :id");
            $stmt->execute([':id' => $appId]);
            $alertMessage = "Application record #{$appId} has been successfully deleted.";
            $alertType = 'success';
        }
    } elseif ($action === 'create_walkin') {
        $category = $_POST['id_category'] ?? 'citizen_id';
        $appType = $_POST['application_type'] ?? 'New Application';
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $gender = $_POST['gender'] ?? 'Male';
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        $civilStatus = $_POST['civil_status'] ?? 'Single';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $street = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? 'Barangay 171');
        $district = $_POST['district'] ?? 'District 1';
        $residentSince = trim($_POST['resident_since'] ?? '2015');
        $primaryDoc = trim($_POST['primary_doc_name'] ?? 'Valid Government ID');
        $reviewer = !empty($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Walk-in Desk Staff';

        $idTitles = [
            'citizen_id' => 'Caloocan Citizen Unified ID Card',
            'barangay_id' => 'Barangay Resident Identification Card',
            'solo_parent_id' => 'Solo Parent Welfare Identification Card (RA 11861)',
            'pwd_id' => 'Persons with Disability Card (Republic Act 10754)',
            'senior_citizen_id' => 'OSCA Senior Citizen Card (Republic Act 9994)'
        ];

        $prefixes = [
            'citizen_id' => 'CAL-CIT',
            'barangay_id' => 'CAL-BRGY',
            'solo_parent_id' => 'CAL-SP',
            'pwd_id' => 'CAL-PWD',
            'senior_citizen_id' => 'CAL-SR'
        ];

        $bureaus = [
            'citizen_id' => 'Caloocan Civil Registry & Identity Management Bureau',
            'barangay_id' => 'Respective Barangay Executive Office & Secretariat',
            'solo_parent_id' => 'City Social Welfare & Development Office (CSWDO)',
            'pwd_id' => 'Persons with Disability Affairs Office (PDAO)',
            'senior_citizen_id' => 'Office of Senior Citizens Affairs (OSCA)'
        ];

        $offices = [
            'citizen_id' => 'Caloocan Main City Hall - Window 6',
            'barangay_id' => 'Local Barangay Hall - Records Desk',
            'solo_parent_id' => 'Caloocan CSWDO Office - Solo Parent Desk',
            'pwd_id' => 'PDAO Counter - City Hall Ground Floor',
            'senior_citizen_id' => 'OSCA Main Building - Caloocan City Complex'
        ];

        $turnarounds = [
            'citizen_id' => '3 to 5 Business Days',
            'barangay_id' => '1 to 2 Business Days',
            'solo_parent_id' => '5 to 7 Business Days',
            'pwd_id' => '3 to 5 Business Days',
            'senior_citizen_id' => '2 to 4 Business Days'
        ];

        $refNo = ($prefixes[$category] ?? 'CAL-ID') . '-2026-' . rand(1000, 9999);
        $title = $idTitles[$category] ?? 'Caloocan ID Card';
        $bureau = $bureaus[$category] ?? 'Caloocan City Hall';
        $claimOffice = $offices[$category] ?? 'Caloocan City Hall Desk';
        $turnaround = $turnarounds[$category] ?? '3 to 5 Business Days';

        if (!empty($firstName) && !empty($lastName) && !empty($contactNumber)) {
            $stmt = $pdo->prepare("
                INSERT INTO `id_issuance_applications` 
                (`reference_no`, `id_category`, `id_title`, `application_type`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `contact_number`, `email`, `street_address`, `barangay`, `district`, `resident_since`, `issuing_bureau`, `primary_doc_name`, `status`, `claim_office`, `estimated_turnaround`, `reviewed_by`, `created_at`)
                VALUES
                (:ref_no, :cat, :title, :app_type, :first, :middle, :last, :suffix, :gender, :bday, :civil, :contact, :email, :street, :brgy, :dist, :res_since, :bureau, :doc_name, 'Under Review', :claim_office, :turnaround, :reviewer, NOW())
            ");
            $stmt->execute([
                ':ref_no' => $refNo,
                ':cat' => $category,
                ':title' => $title,
                ':app_type' => $appType,
                ':first' => $firstName,
                ':middle' => $middleName,
                ':last' => $lastName,
                ':suffix' => $suffix,
                ':gender' => $gender,
                ':bday' => $birthdate,
                ':civil' => $civilStatus,
                ':contact' => $contactNumber,
                ':email' => $email,
                ':street' => $street,
                ':brgy' => $barangay,
                ':dist' => $district,
                ':res_since' => $residentSince,
                ':bureau' => $bureau,
                ':doc_name' => $primaryDoc,
                ':claim_office' => $claimOffice,
                ':turnaround' => $turnaround,
                ':reviewer' => $reviewer
            ]);
            $alertMessage = "New walk-in application registered successfully with Reference No: <strong>{$refNo}</strong>.";
            $alertType = 'success';
        } else {
            $alertMessage = "Please complete all mandatory personal information.";
            $alertType = 'error';
        }
    }
}

// Compute Dynamic KPIs
$totalApplications = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications`")->fetchColumn();
$pendingReview = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` IN ('Pending Review', 'Under Review')")->fetchColumn();
$approvedProduction = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` IN ('Approved', 'Ready to Print')")->fetchColumn();
$readyClaimed = (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `status` IN ('Ready for Release', 'Claimed')")->fetchColumn();

// Fetch Applications
$selectedCategory = $_GET['category'] ?? 'all';
$selectedStatus = $_GET['status'] ?? 'all';
$selectedType = $_GET['type'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM `id_issuance_applications` WHERE 1=1";
$params = [];

if ($selectedCategory === 'all') {
    // Archived per panelist review preference; hidden from main view
    $sql .= " AND `id_category` != 'solo_parent_id'";
} else {
    $sql .= " AND `id_category` = :category";
    $params[':category'] = $selectedCategory;
}
if ($selectedStatus !== 'all') {
    $sql .= " AND `status` = :status";
    $params[':status'] = $selectedStatus;
}
if ($selectedType !== 'all') {
    $sql .= " AND `application_type` = :app_type";
    $params[':app_type'] = $selectedType;
}
if (!empty($searchQuery)) {
    $sql .= " AND (`reference_no` LIKE :q1 OR `first_name` LIKE :q2 OR `last_name` LIKE :q3 OR `barangay` LIKE :q4)";
    $likeTerm = "%{$searchQuery}%";
    $params[':q1'] = $likeTerm;
    $params[':q2'] = $likeTerm;
    $params[':q3'] = $likeTerm;
    $params[':q4'] = $likeTerm;
}

$sql .= " ORDER BY `id` DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts per category for pills
$catCounts = [
    'all' => $totalApplications,
    'citizen_id' => (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `id_category` = 'citizen_id'")->fetchColumn(),
    'barangay_id' => (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `id_category` = 'barangay_id'")->fetchColumn(),
    'solo_parent_id' => (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `id_category` = 'solo_parent_id'")->fetchColumn(),
    'pwd_id' => (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `id_category` = 'pwd_id'")->fetchColumn(),
    'senior_citizen_id' => (int)$pdo->query("SELECT COUNT(*) FROM `id_issuance_applications` WHERE `id_category` = 'senior_citizen_id'")->fetchColumn(),
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
        #printableVoucherArea, #printableVoucherArea * {
            visibility: visible;
        }
        #printableVoucherArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            padding: 20px;
        }
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg border border-indigo-100 shadow-xs">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Barangay Certificate & ID Issuance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">ID Issuance Applications</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Municipal & Barangay ID Issuance</h1>
                <p class="text-xs text-slate-500 font-medium">Verify, approve, and track resident identification cards submitted through the Citizen App & City Hall walk-in desks.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="openWalkinModal()" class="px-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-user-plus text-indigo-600 text-xs"></i>
                <span>Manual Walk-in Entry</span>
            </button>
            <button onclick="exportIDLogCSV()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-csv text-xs"></i>
                <span>Export ID Records</span>
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

    <!-- Accountability Stat Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Applications -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total ID Applications</span>
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-base border border-slate-200/60">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $totalApplications; ?></h3>
                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-server text-slate-400"></i> Synced with Mobile App & MySQL
                </p>
            </div>
        </div>

        <!-- Card 2: Pending Document Review -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Evaluation</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-amber-600 tracking-tight"><?php echo $pendingReview; ?></h3>
                <p class="text-[11px] font-semibold text-amber-700 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-exclamation text-amber-500"></i> Requires Document Verification
                </p>
            </div>
        </div>

        <!-- Card 3: Approved / In Production -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Card Production</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base border border-indigo-100">
                    <i class="fa-solid fa-print"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-indigo-600 tracking-tight"><?php echo $approvedProduction; ?></h3>
                <p class="text-[11px] font-semibold text-indigo-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check text-indigo-500"></i> Validated credentials
                </p>
            </div>
        </div>

        <!-- Card 4: Ready & Claimed -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ready / Claimed</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-emerald-600 tracking-tight"><?php echo $readyClaimed; ?></h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-handshake text-emerald-500"></i> Issued at Bureau Counters
                </p>
            </div>
        </div>
    </div>

    <!-- Category Pills Tabs (5 ID Types from Citizen App) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-3">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar text-xs font-bold">
            <a href="?category=all&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'all' ? 'bg-[#0f53d1] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <span>All Identification Types</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['all']; ?></span>
            </a>

            <a href="?category=citizen_id&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'citizen_id' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <i class="fa-solid fa-credit-card text-xs"></i>
                <span>Citizen ID (CAL-CIT)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'citizen_id' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['citizen_id']; ?></span>
            </a>

            <a href="?category=barangay_id&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'barangay_id' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <i class="fa-solid fa-house text-xs"></i>
                <span>Barangay ID (CAL-BRGY)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'barangay_id' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['barangay_id']; ?></span>
            </a>

            <?php /* Archived per panelist review preference: Solo Parent ID */ if (false): ?><a href="?category=solo_parent_id&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'solo_parent_id' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <i class="fa-solid fa-people-roof text-xs"></i>
                <span>Solo Parent ID (CAL-SP)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'solo_parent_id' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['solo_parent_id']; ?></span>
            </a><?php endif; ?>

            <a href="?category=pwd_id&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'pwd_id' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <i class="fa-solid fa-wheelchair text-xs"></i>
                <span>PWD ID (CAL-PWD)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'pwd_id' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['pwd_id']; ?></span>
            </a>

            <a href="?category=senior_citizen_id&status=<?php echo urlencode($selectedStatus); ?>&type=<?php echo urlencode($selectedType); ?>&search=<?php echo urlencode($searchQuery); ?>" 
               class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 whitespace-nowrap <?php echo $selectedCategory === 'senior_citizen_id' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                <i class="fa-solid fa-award text-xs"></i>
                <span>Senior Citizen (OSCA)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] <?php echo $selectedCategory === 'senior_citizen_id' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $catCounts['senior_citizen_id']; ?></span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
        <form method="GET" class="flex flex-col md:flex-row items-center justify-between gap-3">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">

            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Search Ref No, name, barangay..." 
                       class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-xl pl-9 pr-3.5 py-2.5 outline-none focus:border-indigo-500 focus:bg-white transition placeholder:text-slate-400">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto flex-wrap">
                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer focus:border-indigo-500">
                    <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Pending Review" <?php echo $selectedStatus === 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="Under Review" <?php echo $selectedStatus === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                    <option value="Approved" <?php echo $selectedStatus === 'Approved' ? 'selected' : ''; ?>>Approved / Production</option>
                    <option value="Ready for Release" <?php echo $selectedStatus === 'Ready for Release' ? 'selected' : ''; ?>>Ready for Release</option>
                    <option value="Claimed" <?php echo $selectedStatus === 'Claimed' ? 'selected' : ''; ?>>Claimed / Issued</option>
                    <option value="Rejected" <?php echo $selectedStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <!-- Application Type Filter -->
                <select name="type" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer focus:border-indigo-500">
                    <option value="all" <?php echo $selectedType === 'all' ? 'selected' : ''; ?>>All Application Types</option>
                    <option value="New Application" <?php echo $selectedType === 'New Application' ? 'selected' : ''; ?>>New Application</option>
                    <option value="Renewal" <?php echo $selectedType === 'Renewal' ? 'selected' : ''; ?>>Renewal</option>
                    <option value="Replacement" <?php echo $selectedType === 'Replacement' ? 'selected' : ''; ?>>Replacement</option>
                </select>

                <button type="submit" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>Apply</span>
                </button>

                <?php if ($selectedCategory !== 'all' || $selectedStatus !== 'all' || $selectedType !== 'all' || !empty($searchQuery)): ?>
                <a href="id-issuance.php" class="px-3 py-2.5 text-xs text-rose-600 hover:text-rose-700 font-bold transition flex items-center gap-1" title="Clear all filters">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Main Data Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">ID Applications Registry</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/80"><?php echo count($applications); ?> Records</span>
            </div>
            <span class="text-[11px] text-slate-400 font-medium">Click any citizen row to open full application details & management actions</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs" id="idTable">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <th class="py-3 px-4">Reference No</th>
                        <th class="py-3 px-4">Applicant Profile</th>
                        <th class="py-3 px-4">ID Category & Bureau</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Document Requirement</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Submitted At</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400 font-medium">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-lg mb-2">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>
                            <span>No identification applications found matching your criteria.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($applications as $app): 
                        $fullName = trim("{$app['first_name']} {$app['middle_name']} {$app['last_name']} {$app['suffix']}");
                        $stat = $app['status'];

                        // Status badge colors
                        $statBadge = 'bg-slate-100 text-slate-700 border-slate-200';
                        if ($stat === 'Pending Review') $statBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                        elseif ($stat === 'Under Review') $statBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                        elseif ($stat === 'Approved') $statBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                        elseif ($stat === 'Ready for Release') $statBadge = 'bg-purple-50 text-purple-700 border-purple-200';
                        elseif ($stat === 'Claimed') $statBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        elseif ($stat === 'Rejected') $statBadge = 'bg-rose-50 text-rose-700 border-rose-200';

                        // Category icon & styling
                        $catMeta = [
                            'citizen_id' => ['icon' => 'fa-credit-card', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'tag' => 'CITY RESIDENT'],
                            'barangay_id' => ['icon' => 'fa-house', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'tag' => 'BARANGAY'],
                            'solo_parent_id' => ['icon' => 'fa-people-roof', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'tag' => 'SOLO PARENT'],
                            'pwd_id' => ['icon' => 'fa-wheelchair', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'tag' => 'PWD'],
                            'senior_citizen_id' => ['icon' => 'fa-award', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200', 'tag' => 'OSCA SENIOR']
                        ][$app['id_category']] ?? ['icon' => 'fa-id-card', 'badge' => 'bg-slate-100 text-slate-700 border-slate-200', 'tag' => 'ID CARD'];

                        $avatarInitials = strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1));
                    ?>
                    <tr onclick='openCitizenDetailsModal(<?php echo json_encode($app, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                        class="hover:bg-indigo-50/60 cursor-pointer transition group" title="Click to view details and actions">
                        <!-- Reference No -->
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                            <span class="text-xs group-hover:text-indigo-600 transition"><?php echo htmlspecialchars($app['reference_no']); ?></span>
                            <div class="mt-0.5">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border <?php echo $catMeta['badge']; ?>">
                                    <?php echo $catMeta['tag']; ?>
                                </span>
                            </div>
                        </td>

                        <!-- Applicant Profile -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-slate-200 to-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 border border-indigo-200/50">
                                    <?php echo $avatarInitials; ?>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                        <span class="group-hover:text-indigo-600 transition"><?php echo htmlspecialchars($fullName); ?></span>
                                        <span class="text-[10px] text-slate-400 font-normal">(<?php echo htmlspecialchars($app['gender']); ?>)</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        <i class="fa-solid fa-phone text-[9px] text-slate-400 mr-1"></i><?php echo htmlspecialchars($app['contact_number']); ?>
                                        <span class="mx-1">•</span>
                                        <i class="fa-solid fa-location-dot text-[9px] text-slate-400 mr-1"></i><?php echo htmlspecialchars($app['barangay']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- ID Category & Bureau -->
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-800 text-xs">
                                <?php echo htmlspecialchars($app['id_title']); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium truncate max-w-xs" title="<?php echo htmlspecialchars($app['issuing_bureau']); ?>">
                                <?php echo htmlspecialchars($app['issuing_bureau']); ?>
                            </div>
                        </td>

                        <!-- Application Type -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $app['application_type'] === 'New Application' ? 'bg-sky-50 text-sky-700 border border-sky-200' : ($app['application_type'] === 'Renewal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'); ?>">
                                <?php echo htmlspecialchars($app['application_type']); ?>
                            </span>
                        </td>

                        <!-- Primary Document Requirement -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-700">
                                <i class="fa-solid fa-file-lines text-indigo-500 text-xs"></i>
                                <span class="truncate max-w-[150px]" title="<?php echo htmlspecialchars($app['primary_doc_name']); ?>">
                                    <?php echo htmlspecialchars($app['primary_doc_name'] ?: 'Government Valid ID'); ?>
                                </span>
                            </div>
                            <span class="text-[9px] text-emerald-600 font-bold"><i class="fa-solid fa-check text-[8px]"></i> Attached & Verified</span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $statBadge; ?>">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                        </td>

                        <!-- Submitted Date -->
                        <td class="py-3.5 px-4 text-slate-500 font-medium text-[11px] whitespace-nowrap">
                            <?php echo date('M j, Y • h:i A', strtotime($app['created_at'])); ?>
                        </td>

                        <!-- Actions Indicator -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <span class="px-3 py-1.5 rounded-xl bg-slate-100 group-hover:bg-indigo-600 group-hover:text-white text-slate-700 font-bold text-xs transition inline-flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-sliders text-[10px]"></i>
                                <span>Manage</span>
                                <i class="fa-solid fa-chevron-right text-[9px] opacity-70"></i>
                            </span>
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
<!-- MASTER MODAL: CITIZEN APPLICATION DETAILS & ACTION MANAGEMENT MODAL           -->
<!-- (Opens when any citizen row is clicked and contains all action buttons)        -->
<!-- ============================================================================== -->
<div id="citizenDetailsModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Light Header with Back Button -->
        <div class="bg-white px-6 py-4.5 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeCitizenDetailsModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Registry">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg border border-indigo-100 shadow-xs">
                    <i class="fa-solid fa-address-card"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-base tracking-tight text-slate-900">Resident Application Details</h3>
                        <span id="cdmCatTag" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200/80">BARANGAY</span>
                    </div>
                    <p class="text-xs text-slate-400 font-mono font-bold mt-0.5" id="cdmRef">CAL-BRGY-2026-0000</p>
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
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-slate-200 to-indigo-100 text-indigo-700 font-black text-sm flex items-center justify-center shrink-0 border border-indigo-200 shadow-xs" id="cdmAvatar">
                            DE
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 text-sm" id="cdmApplicantName">Danny Espelita Jr.</h4>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5 font-medium">
                                <span id="cdmGender">Male</span> • 
                                <span id="cdmCivilStatus">Single</span> • 
                                <span>Born: <strong class="text-slate-700" id="cdmBirthdate">2003-12-07</strong></span>
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
                            <i class="fa-solid fa-phone text-indigo-500 text-xs"></i> 09630902025
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Email Address</span>
                        <span class="font-semibold text-slate-800 flex items-center gap-1.5 truncate" id="cdmEmail">
                            <i class="fa-solid fa-envelope text-indigo-500 text-xs"></i> espelitadanny@gmail.com
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Barangay & District</span>
                        <span class="font-semibold text-slate-800 flex items-center gap-1.5" id="cdmBarangayDistrict">
                            <i class="fa-solid fa-location-dot text-rose-500 text-xs"></i> Barangay 171 • District 2
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Residential Address</span>
                        <span class="font-semibold text-slate-800 truncate" id="cdmAddress">121 Sampaguita St.</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: ID Application & Requirements Specifications -->
            <div class="bg-white rounded-2xl p-4.5 border border-slate-200/90 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Card Type & Authority</span>
                        <h4 class="font-bold text-xs text-slate-900" id="cdmIdTitle">Barangay Resident Identification Card</h4>
                        <p class="text-[10px] text-slate-500 truncate" id="cdmBureau">Respective Barangay Executive Office & Secretariat</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200" id="cdmAppType">
                        New Application
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Designated Pick-up Desk</span>
                        <strong class="text-slate-800 text-xs block" id="cdmClaimOffice">Local Barangay Hall - Administrative Records Desk</strong>
                        <span class="text-[10px] text-emerald-600 font-bold">Turnaround: <span id="cdmTurnaround">1 to 2 Business Days</span></span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Primary Requirement</span>
                        <div class="flex items-center gap-1.5 text-slate-800 font-semibold text-xs">
                            <i class="fa-solid fa-file-circle-check text-emerald-600"></i>
                            <span id="cdmDocName">Proof of Residency (Min 6 Months)</span>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold block">&check; Attached & Verified on file</span>
                    </div>
                </div>

                <!-- Review Notes Alert Box (If present) -->
                <div id="cdmNotesBox" class="hidden bg-amber-50/70 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-800">
                    <strong class="font-bold block text-amber-900 mb-0.5">Officer Review Remarks:</strong>
                    <span id="cdmNotesText">No remarks</span>
                </div>
            </div>

            <!-- Section 3: UNIFIED ACTION BUTTONS BAR (Clean Light UI) -->
            <div class="bg-slate-50/90 p-5 rounded-2xl border border-slate-200/90 space-y-3.5 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <span class="text-xs font-black uppercase tracking-wider text-slate-800">Management & Processing Actions</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Select an action for this citizen</span>
                </div>

                <!-- Buttons Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Action 1: Review & Progress Status -->
                    <button type="button" onclick="openReviewFromDetails()" class="px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-sliders text-sm"></i>
                        <span>Review & Change Status</span>
                    </button>

                    <!-- Action 2: Edit Details -->
                    <button type="button" onclick="openEditFromDetails()" class="px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                        <span>Edit Resident Info</span>
                    </button>

                    <!-- Action 3: Go to Release Desk -->
                    <button type="button" id="cdmReleaseBtn" onclick="goToReleaseFromDetails()" class="px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-hand-holding-hand text-sm"></i>
                        <span>ID Release Desk</span>
                    </button>

                    <!-- Action: View Official Civentral Citizen Card Modal -->
                    <button type="button" onclick="openCiventralCitizenCardFromDetails()" class="px-4 py-3 bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-800 hover:to-indigo-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-id-card text-sm"></i>
                        <span>Civentral Citizen Card</span>
                    </button>

                    <!-- Action 4: View ID Card & Voucher -->
                    <button type="button" onclick="openCardFromDetails()" class="px-4 py-3 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-eye text-sm text-indigo-600"></i>
                        <span>Card & Claim Voucher</span>
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

            <!-- Modal Bottom Navigation (Back Button) -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="closeCitizenDetailsModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Registry</span>
                </button>
                <button type="button" onclick="closeCitizenDetailsModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
<div id="reviewModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="backToCitizenDetailsFromReview()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Citizen Details">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm border border-indigo-100">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-tight text-slate-900" id="reviewModalTitle">Review ID Application</h3>
                    <p class="text-[11px] text-slate-400 font-mono font-bold" id="reviewModalRef">CAL-CIT-2026-0000</p>
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
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700" id="reviewAppType">New Application</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div><span class="text-slate-400 font-medium">Contact:</span> <strong class="text-slate-700" id="reviewContact">0917-000-0000</strong></div>
                    <div><span class="text-slate-400 font-medium">Barangay:</span> <strong class="text-slate-700" id="reviewBarangay">Barangay 171</strong></div>
                    <div class="col-span-2"><span class="text-slate-400 font-medium">Full Address:</span> <strong class="text-slate-700" id="reviewAddress">Address line</strong></div>
                    <div class="col-span-2"><span class="text-slate-400 font-medium">Issuing Bureau:</span> <strong class="text-slate-700" id="reviewBureau">Bureau Name</strong></div>
                    <div class="col-span-2"><span class="text-slate-400 font-medium">Primary Requirement:</span> <strong class="text-emerald-700 font-bold" id="reviewDocName">Valid ID</strong></div>
                </div>
            </div>

            <!-- Status Control -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Update Application Status</label>
                <select name="status" id="reviewStatusSelect" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3.5 text-xs outline-none focus:border-indigo-500 focus:bg-white transition cursor-pointer">
                    <option value="Pending Review">Pending Review</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Ready to Print">Ready to Print</option>
                    <option value="Approved">Approved (Ready for Card Printing)</option>
                    <option value="Ready for Release">Ready for Release at Desk</option>
                    <option value="Claimed">Claimed / Released to Resident</option>
                    <option value="Rejected">Rejected / Incomplete Requirements</option>
                </select>
            </div>

            <!-- Notes -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Officer Verification Notes</label>
                <textarea name="review_notes" id="reviewNotesInput" rows="2" placeholder="e.g. Identity and residency verified with Barangay Secretariat..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl p-3 outline-none focus:border-indigo-500 focus:bg-white transition placeholder:text-slate-400"></textarea>
            </div>

            <!-- Rejection Reason (Conditional) -->
            <div class="space-y-1.5" id="rejectionReasonBox">
                <label class="text-xs font-bold text-rose-700">Rejection Reason (If Applicable)</label>
                <input type="text" name="rejection_reason" id="reviewRejectionInput" placeholder="Specify deficiency (e.g. Unreadable ID photo, missing certification)" class="w-full bg-rose-50/50 border border-rose-200 text-rose-800 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-rose-400 transition placeholder:text-rose-300">
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

<!-- MODAL 2: ID CARD & CLAIM VOUCHER PREVIEW (PRINTABLE) -->
<div id="previewModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" onclick="backToCitizenDetailsFromPreview()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer" title="Back to Citizen Details">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm border border-indigo-100">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <h3 class="font-bold text-sm tracking-tight text-slate-900">Official ID Card & Claim Voucher Preview</h3>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Card / Voucher</span>
                </button>
                <button id="markReadyFromPrintBtn" type="button" onclick="markReadyFromPrint()" class="hidden px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-xs" title="Send printed card to the ID Release & Pick-up Desk">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Mark Printed & Send to Release Desk</span>
                </button>
                <button onclick="closePreviewModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6" id="printableVoucherArea">
            <!-- Caloocan ID Card Digital Layout Card (Simulation) -->
            <div class="bg-gradient-to-r from-indigo-900 via-blue-900 to-slate-900 rounded-2xl p-5 text-white shadow-lg border border-indigo-400/30 relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-44 h-44 rounded-full bg-white/5 pointer-events-none"></div>

                <!-- Card Top Header -->
                <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-sm font-black border border-white/20">
                            🏛️
                        </div>
                        <div>
                            <div class="text-[9px] uppercase tracking-widest text-indigo-200 font-extrabold">Republic of the Philippines</div>
                            <div class="text-xs font-black tracking-wider text-white">CITY GOVERNMENT OF CALOOCAN</div>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-white/10 text-white border border-white/20" id="cardBadgeCategory">
                        CITIZEN UNIFIED ID
                    </span>
                </div>

                <!-- Card Body -->
                <div class="flex items-center gap-4">
                    <div class="w-24 h-28 rounded-xl bg-slate-800 border-2 border-white/30 flex flex-col items-center justify-center text-white shrink-0 overflow-hidden shadow-inner">
                        <i class="fa-solid fa-user text-3xl opacity-40"></i>
                        <span class="text-[9px] font-bold mt-1 text-slate-300">2x2 PHOTO</span>
                    </div>

                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="text-[9px] uppercase tracking-wider text-indigo-300 font-bold">Full Legal Name</div>
                        <div class="text-base font-black tracking-tight text-white truncate" id="cardHolderName">JUAN DELA CRUZ</div>

                        <div class="grid grid-cols-2 gap-2 text-[10px] pt-1">
                            <div>
                                <span class="text-indigo-300 block text-[9px]">ID Reference No</span>
                                <strong class="font-mono text-white" id="cardRefNumber">CAL-CIT-2026-0000</strong>
                            </div>
                            <div>
                                <span class="text-indigo-300 block text-[9px]">Barangay & District</span>
                                <strong class="text-white" id="cardBarangay">Barangay 171 • Dist 1</strong>
                            </div>
                            <div>
                                <span class="text-indigo-300 block text-[9px]">Civil Status / Gender</span>
                                <strong class="text-white" id="cardCivilGender">Single • Male</strong>
                            </div>
                            <div>
                                <span class="text-indigo-300 block text-[9px]">Issuance Bureau</span>
                                <strong class="text-white truncate block" id="cardBureau">Civil Registry Bureau</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Barcode Sim -->
                <div class="mt-4 pt-2.5 border-t border-white/10 flex items-center justify-between text-[9px] font-mono text-indigo-200">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-qrcode text-base text-white"></i>
                        <span>SECURE CALOOCAN DIGITAL CREDENTIAL</span>
                    </div>
                    <span>VALID CITY-WIDE • RA 11861 / RA 10754</span>
                </div>
            </div>

            <!-- Official Claim Voucher Details -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h4 class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-receipt text-indigo-600"></i>
                        <span>Resident Claim Voucher & Turnaround Information</span>
                    </h4>
                    <span class="text-[10px] font-mono text-slate-400">CALOOCAN-VOUCHER-2026</span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 text-[11px]">
                    <div>
                        <span class="text-slate-400 block font-medium">Claiming Office:</span>
                        <strong class="text-slate-800" id="voucherClaimOffice">Caloocan Main City Hall - Window 6</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Estimated Turnaround:</span>
                        <strong class="text-indigo-700 font-bold" id="voucherTurnaround">3 to 5 Business Days</strong>
                    </div>
                    <div class="col-span-2">
                        <span class="text-slate-400 block font-medium">Claim Requirements:</span>
                        <p class="text-slate-600 text-[11px] mt-0.5">Please present 1 original valid government ID, this physical or digital QR claim stub, and surrender old card (for renewals/replacements) at the designated desk.</p>
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

<!-- MODAL 3: MANUAL WALK-IN APPLICATION MODAL -->
<div id="walkinModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm border border-indigo-100">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="font-bold text-sm tracking-tight text-slate-900">Manual Walk-in ID Registration</h3>
            </div>
            <button onclick="closeWalkinModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_walkin">

            <div class="grid grid-cols-2 gap-3 text-xs">
                <!-- ID Category -->
                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Identification Category</label>
                    <select name="id_category" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                        <option value="citizen_id">Citizen ID (Caloocan Citizen Unified ID Card)</option>
                        <option value="barangay_id">Barangay ID (Barangay Resident ID Card)</option>
                        <?php /* Archived per panelist review preference */ if (false): ?><option value="solo_parent_id">Solo Parent ID (RA 11861 Welfare Card)</option><?php endif; ?>
                        <option value="pwd_id">PWD ID (RA 10754 Disability Privilege Card)</option>
                        <option value="senior_citizen_id">Senior Citizen ID (RA 9994 OSCA Card)</option>
                    </select>
                </div>

                <!-- Application Type -->
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Application Type</label>
                    <select name="application_type" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                        <option value="New Application">New Application</option>
                        <option value="Renewal">Renewal</option>
                        <option value="Replacement">Replacement</option>
                    </select>
                </div>

                <!-- Gender -->
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Gender</label>
                    <select name="gender" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Names -->
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">First Name *</label>
                    <input type="text" name="first_name" required placeholder="e.g. Juan" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Last Name *</label>
                    <input type="text" name="last_name" required placeholder="e.g. dela Cruz" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Contact Mobile *</label>
                    <input type="text" name="contact_number" required placeholder="0917-000-0000" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" placeholder="citizen@example.com" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="col-span-2 space-y-1">
                    <label class="font-bold text-slate-700">Street Address *</label>
                    <input type="text" name="street_address" required placeholder="House No, Block & Lot, Street" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Barangay *</label>
                    <input type="text" name="barangay" required value="Barangay 171" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Primary Document Presented</label>
                    <input type="text" name="primary_doc_name" value="PhilSys National ID" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 text-xs outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeWalkinModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>Register Application</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT RESIDENT APPLICATION DETAILS -->
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
                    <h3 class="font-bold text-sm tracking-tight text-slate-900">Edit Resident ID Application</h3>
                    <p class="text-[11px] text-slate-400 font-mono font-bold" id="editModalRef">CAL-BRGY-2026-0000</p>
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
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">First Name *</label>
                    <input type="text" name="first_name" id="editFirstName" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Last Name *</label>
                    <input type="text" name="last_name" id="editLastName" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Middle Name</label>
                    <input type="text" name="middle_name" id="editMiddleName" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Suffix</label>
                    <input type="text" name="suffix" id="editSuffix" placeholder="Jr, Sr, III" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Gender</label>
                    <select name="gender" id="editGender" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
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
                    <label class="font-bold text-slate-700">Contact Number *</label>
                    <input type="text" name="contact_number" id="editContact" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
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
                    <input type="text" name="barangay" id="editBarangay" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700">District</label>
                    <select name="district" id="editDistrict" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-xl p-2.5 outline-none focus:border-amber-500">
                        <option value="District 1">District 1</option>
                        <option value="District 2">District 2</option>
                        <option value="District 3">District 3</option>
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
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
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
    selectedAppForModal = app;
    const fullName = `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''} ${app.suffix || ''}`.replace(/\s+/g, ' ').trim();
    const avatarInitials = ((app.first_name ? app.first_name[0] : '') + (app.last_name ? app.last_name[0] : '')).toUpperCase() || 'ID';

    document.getElementById('cdmRef').innerText = app.reference_no;
    document.getElementById('cdmCatTag').innerText = (app.id_category || 'ID').toUpperCase().replace('_', ' ');
    document.getElementById('cdmApplicantName').innerText = fullName;
    document.getElementById('cdmAvatar').innerText = avatarInitials;
    document.getElementById('cdmGender').innerText = app.gender || 'Not specified';
    document.getElementById('cdmCivilStatus').innerText = app.civil_status || 'Single';
    document.getElementById('cdmBirthdate').innerText = app.birthdate || 'Not specified';
    document.getElementById('cdmResidentSince').innerText = 'Resident Since ' + (app.resident_since || '2015');
    
    document.getElementById('cdmPhone').innerHTML = `<i class="fa-solid fa-phone text-indigo-500 text-xs"></i> ${app.contact_number || 'No contact'}`;
    document.getElementById('cdmEmail').innerHTML = `<i class="fa-solid fa-envelope text-indigo-500 text-xs"></i> ${app.email || 'No email'}`;
    document.getElementById('cdmBarangayDistrict').innerHTML = `<i class="fa-solid fa-location-dot text-rose-500 text-xs"></i> ${app.barangay || 'Barangay'} • ${app.district || 'District 1'}`;
    document.getElementById('cdmAddress').innerText = app.street_address || 'Address on file';

    document.getElementById('cdmIdTitle').innerText = app.id_title || 'Resident ID';
    document.getElementById('cdmBureau').innerText = app.issuing_bureau || 'City Registry';
    document.getElementById('cdmAppType').innerText = app.application_type || 'New Application';
    document.getElementById('cdmClaimOffice').innerText = app.claim_office || 'Local Barangay Hall - Administrative Records Desk';
    document.getElementById('cdmTurnaround').innerText = app.estimated_turnaround || '1 to 2 Business Days';
    document.getElementById('cdmDocName').innerText = app.primary_doc_name || 'Proof of Residency (Min 6 Months)';

    const statBadgeEl = document.getElementById('cdmStatusBadge');
    statBadgeEl.innerText = app.status;
    let badgeClass = 'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border ';
    if (app.status === 'Pending Review') badgeClass += 'bg-amber-50 text-amber-700 border-amber-200';
    else if (app.status === 'Under Review') badgeClass += 'bg-blue-50 text-blue-700 border-blue-200';
    else if (app.status === 'Approved') badgeClass += 'bg-indigo-50 text-indigo-700 border-indigo-200';
    else if (app.status === 'Ready for Release') badgeClass += 'bg-purple-50 text-purple-700 border-purple-200';
    else if (app.status === 'Claimed') badgeClass += 'bg-emerald-50 text-emerald-700 border-emerald-200';
    else if (app.status === 'Rejected') badgeClass += 'bg-rose-50 text-rose-700 border-rose-200';
    else badgeClass += 'bg-slate-100 text-slate-700 border-slate-200';
    statBadgeEl.className = badgeClass;

    const notesBox = document.getElementById('cdmNotesBox');
    if (app.review_notes || app.rejection_reason) {
        notesBox.classList.remove('hidden');
        document.getElementById('cdmNotesText').innerText = app.review_notes || app.rejection_reason;
    } else {
        notesBox.classList.add('hidden');
    }

    const releaseBtn = document.getElementById('cdmReleaseBtn');
    if (['Ready for Release', 'Claimed'].includes(app.status)) {
        releaseBtn.classList.remove('opacity-40', 'pointer-events-none');
        releaseBtn.title = "Open in ID Release Desk";
    } else {
        releaseBtn.classList.add('opacity-40', 'pointer-events-none');
        releaseBtn.title = (app.status === 'Approved')
            ? "Card is approved & ready to print. Print the card first, then set status to 'Ready for Release' to send to Release Desk."
            : "Only available when application is Ready for Release or Claimed.";
    }

    document.getElementById('citizenDetailsModal').classList.remove('hidden');
}

function closeCitizenDetailsModal() {
    document.getElementById('citizenDetailsModal').classList.add('hidden');
}

function openReviewFromDetails() {
    closeCitizenDetailsModal();
    if (selectedAppForModal) openReviewModal(selectedAppForModal);
}

function openEditFromDetails() {
    closeCitizenDetailsModal();
    if (selectedAppForModal) openEditModal(selectedAppForModal);
}


function openCiventralCitizenCardFromDetails() {
    if (!selectedAppForModal) return;
    if (typeof openCitizenCardModal === "function") {
        const formatted = {
            first_name: selectedAppForModal.first_name || "",
            last_name: selectedAppForModal.last_name || "",
            middle_name: selectedAppForModal.middle_name || "",
            suffix: selectedAppForModal.suffix || "",
            sex: selectedAppForModal.gender || "Female",
            gender: selectedAppForModal.gender || "Female",
            birth_date: selectedAppForModal.birthdate || "1995-10-20",
            birthdate: selectedAppForModal.birthdate || "1995-10-20",
            civil_status: selectedAppForModal.civil_status || "Single",
            street_address: selectedAppForModal.street_address || "",
            barangay: selectedAppForModal.barangay || "",
            district: selectedAppForModal.district || "District 1",
            citizen_id_number: selectedAppForModal.reference_no,
            reference_no: selectedAppForModal.reference_no,
            reviewed_at: selectedAppForModal.reviewed_at || selectedAppForModal.created_at || "2026-10-06",
            emergency_contact: selectedAppForModal.emergency_contact_phone || "(02) 8366-3101",
            photo_1x1_url: selectedAppForModal.photo_2x2_url || null,
            signature_photo_url: selectedAppForModal.signature_url || null,
            e_signature_name: selectedAppForModal.e_signature_name || null,
        };
        openCitizenCardModal(formatted);
    } else {
        alert("Citizen Card Modal is initializing, please try again in a moment.");
    }
}

function openCardFromDetails() {
    closeCitizenDetailsModal();
    if (selectedAppForModal) openPreviewModal(selectedAppForModal);
}

function goToReleaseFromDetails() {
    if (selectedAppForModal) {
        if (selectedAppForModal.status === 'Approved' || selectedAppForModal.status === 'Ready to Print') {
            alert(`Application ${selectedAppForModal.reference_no} is currently Approved (Ready for Printing). Please print the card first and change its status to 'Ready for Release' before sending to the Release Desk.`);
            return;
        }
        window.location.href = `id-releases.php?ref=${encodeURIComponent(selectedAppForModal.reference_no)}`;
    }
}

function deleteFromDetails() {
    if (selectedAppForModal) {
        confirmDeleteApp(selectedAppForModal.id, selectedAppForModal.reference_no);
    }
}

function backToCitizenDetailsFromReview() {
    closeReviewModal();
    if (selectedAppForModal) {
        openCitizenDetailsModal(selectedAppForModal);
    }
}

function backToCitizenDetailsFromEdit() {
    closeEditModal();
    if (selectedAppForModal) {
        openCitizenDetailsModal(selectedAppForModal);
    }
}

function backToCitizenDetailsFromPreview() {
    closePreviewModal();
    if (selectedAppForModal) {
        openCitizenDetailsModal(selectedAppForModal);
    }
}

function openEditModal(app) {
    document.getElementById('editAppId').value = app.id;
    document.getElementById('editModalRef').innerText = app.reference_no;
    document.getElementById('editFirstName').value = app.first_name || '';
    document.getElementById('editMiddleName').value = app.middle_name || '';
    document.getElementById('editLastName').value = app.last_name || '';
    document.getElementById('editSuffix').value = app.suffix || '';
    document.getElementById('editGender').value = app.gender || 'Male';
    document.getElementById('editCivilStatus').value = app.civil_status || 'Single';
    document.getElementById('editContact').value = app.contact_number || '';
    document.getElementById('editEmail').value = app.email || '';
    document.getElementById('editAddress').value = app.street_address || '';
    document.getElementById('editBarangay').value = app.barangay || '';
    document.getElementById('editDistrict').value = app.district || 'District 1';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDeleteApp(id, ref) {
    if (confirm(`Are you sure you want to permanently delete application ${ref}? This action cannot be undone.`)) {
        document.getElementById('deleteAppId').value = id;
        document.getElementById('deleteAppForm').submit();
    }
}
function openReviewModal(app) {
    document.getElementById('reviewAppId').value = app.id;
    document.getElementById('reviewModalRef').innerText = app.reference_no;
    document.getElementById('reviewModalTitle').innerText = 'Review ' + app.id_title;
    document.getElementById('reviewApplicantName').innerText = (app.first_name + ' ' + (app.middle_name || '') + ' ' + app.last_name + ' ' + (app.suffix || '')).trim();
    document.getElementById('reviewAppType').innerText = app.application_type;
    document.getElementById('reviewContact').innerText = app.contact_number;
    document.getElementById('reviewBarangay').innerText = app.barangay + ' (' + (app.district || 'District 1') + ')';
    document.getElementById('reviewAddress').innerText = app.street_address;
    document.getElementById('reviewBureau').innerText = app.issuing_bureau;
    document.getElementById('reviewDocName').innerText = app.primary_doc_name || 'Government Valid ID';

    document.getElementById('reviewStatusSelect').value = app.status;
    document.getElementById('reviewNotesInput').value = app.review_notes || '';
    document.getElementById('reviewRejectionInput').value = app.rejection_reason || '';

    document.getElementById('reviewModal').classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

function openPreviewModal(app) {
    document.getElementById('cardBadgeCategory').innerText = app.id_title.toUpperCase();
    document.getElementById('cardHolderName').innerText = (app.first_name + ' ' + (app.middle_name || '') + ' ' + app.last_name + ' ' + (app.suffix || '')).toUpperCase();
    document.getElementById('cardRefNumber').innerText = app.reference_no;
    document.getElementById('cardBarangay').innerText = app.barangay + ' • ' + (app.district || 'District 1');
    document.getElementById('cardCivilGender').innerText = (app.civil_status || 'Single') + ' • ' + (app.gender || 'Resident');
    document.getElementById('cardBureau').innerText = app.issuing_bureau;

    document.getElementById('voucherClaimOffice').innerText = app.claim_office || 'Caloocan City Hall Complex Desk';
    document.getElementById('voucherTurnaround').innerText = app.estimated_turnaround || '3 to 5 Business Days';

    const printReadyBtn = document.getElementById('markReadyFromPrintBtn');
    if (printReadyBtn) {
        if (app.status === 'Approved') {
            printReadyBtn.classList.remove('hidden');
        } else {
            printReadyBtn.classList.add('hidden');
        }
    }

    document.getElementById('previewModal').classList.remove('hidden');
}

function markReadyFromPrint() {
    if (!selectedAppForModal) return;
    if (confirm(`Confirm card printing is complete for ${selectedAppForModal.reference_no}?\n\nThis will update status to 'Ready for Release' and make the card available at the ID Release & Pick-up Desk.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="application_id" value="${selectedAppForModal.id}">
            <input type="hidden" name="status" value="Ready for Release">
            <input type="hidden" name="review_notes" value="Physical card printed in production and sent to the ID Release & Pick-up Desk.">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

function openWalkinModal() {
    document.getElementById('walkinModal').classList.remove('hidden');
}

function closeWalkinModal() {
    document.getElementById('walkinModal').classList.add('hidden');
}

function exportIDLogCSV() {
    let csv = "Reference No,ID Category,Applicant Name,Barangay,Application Type,Status,Submitted At\n";
    let rows = document.querySelectorAll("#idTable tbody tr");
    rows.forEach(tr => {
        let cols = tr.querySelectorAll("td");
        if (cols.length >= 7) {
            let ref = cols[0].innerText.replace(/\n/g, ' ').trim();
            let name = cols[1].innerText.replace(/\n/g, ' ').trim();
            let cat = cols[2].innerText.replace(/\n/g, ' ').trim();
            let type = cols[3].innerText.trim();
            let stat = cols[5].innerText.trim();
            let date = cols[6].innerText.trim();
            csv += `"${ref}","${cat}","${name}","${type}","${stat}","${date}"\n`;
        }
    });

    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement("a");
    let url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `Caloocan_ID_Issuance_Registry_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../../includes/footer.php'; ?>

<?php include_once __DIR__ . '/../../includes/citizen-card-modal.php'; ?>
