<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Dataset of Published Public Consultations & Policy Outcomes
$publishedConsultations = [
    [
        'id' => 'SRV-2025-001',
        'title' => '2026 Barangay Disaster Resilience & Flood Control Plan',
        'category' => 'Disaster Preparedness',
        'closed_date' => 'May 28, 2025',
        'total_participants' => '3,420 Residents',
        'key_findings' => '52% of residents identified canal blockage in Camarin Road as the primary flood risk.',
        'barangay_policy_outcome' => 'Enacted Barangay Ordinance No. 2025-04 allocating ₱450,000 for drainage de-clogging & purchasing 2 high-capacity water pumps.',
        'transparency_score' => '100% Published & Verified',
        'pdf_filename' => '2026_Disaster_Resilience_Summary_Report.pdf'
    ],
    [
        'id' => 'SRV-2025-002',
        'title' => 'Solar Streetlights & Security Lighting Priority Selection',
        'category' => 'Public Safety',
        'closed_date' => 'Apr 15, 2025',
        'total_participants' => '1,840 Residents',
        'key_findings' => '58% prioritized dark market alleyways in Bagong Silang for immediate lighting.',
        'barangay_policy_outcome' => 'Procured 40 units of 100W Solar LED Streetlights installed along Phase 1 Market Alleyways and 5th Avenue corners.',
        'transparency_score' => '100% Published & Verified',
        'pdf_filename' => 'Solar_Streetlight_Outcomes_Report.pdf'
    ],
    [
        'id' => 'SRV-2024-088',
        'title' => 'Barangay Health Center Free Maintenance Medicine Distribution',
        'category' => 'Public Health',
        'closed_date' => 'Dec 20, 2024',
        'total_participants' => '2,150 Residents',
        'key_findings' => '61% of senior citizens requested monthly home delivery for Amlodipine & Metformin.',
        'barangay_policy_outcome' => 'Established "Senior Health Express" barangay tanod mobile delivery team for house-to-house maintenance medicine distribution.',
        'transparency_score' => '100% Published & Verified',
        'pdf_filename' => 'Senior_Health_Medicine_Outcomes.pdf'
    ]
];
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
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg border border-purple-100 shadow-xs">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Public Consultation & Survey</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Published Consultations</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Published Consultations</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="exportArchiveCSV()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Outcomes Log</span>
            </button>
        </div>
    </div>

    <!-- Accountability Stat Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Published Outcomes -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Published Archives</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">12 Reports</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Public transparency compliant</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Total Resident Voices -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Voices Informing Policy</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">14,280 Responses</h3>
                <p class="text-[11px] font-semibold text-[#0f53d1] flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-users"></i>
                    <span>Direct citizen governance</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Ordinances Enacted -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ordinances Enacted</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-gavel"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">8 Ordinances</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-check"></i>
                    <span>Informed by survey findings</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Transparency Score -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Transparency Score</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">100% Verified</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-lock-open"></i>
                    <span>Open access for citizens</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Published Consultations Cards List -->
    <div class="space-y-4">
        
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Completed Consultations & Barangay Policy Outcomes</h3>
            <span class="text-xs text-slate-400 font-medium">Public record of how survey data informed council decisions</span>
        </div>

        <div class="grid grid-cols-1 gap-5">
            <?php foreach ($publishedConsultations as $pub): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 hover:border-purple-300 transition">
                
                <!-- Card Top Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-purple-600"><?php echo $pub['id']; ?></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-600 border border-purple-200"><?php echo $pub['category']; ?></span>
                            <span class="text-[10px] text-slate-400 font-medium">&bull; Closed on <?php echo $pub['closed_date']; ?></span>
                        </div>
                        <h3 class="text-base font-black text-slate-900 mt-1"><?php echo htmlspecialchars($pub['title']); ?></h3>
                    </div>

                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-600 font-bold text-xs border border-emerald-200 shrink-0">
                        <i class="fa-solid fa-circle-check mr-1"></i> <?php echo $pub['total_participants']; ?> Participated
                    </span>
                </div>

                <!-- Findings vs Policy Outcome Grid (2 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    
                    <!-- Key Findings Box -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-chart-pie text-[#0f53d1]"></i>
                            <span>Survey Key Findings</span>
                        </span>
                        <p class="text-slate-800 font-medium text-xs leading-relaxed"><?php echo htmlspecialchars($pub['key_findings']); ?></p>
                    </div>

                    <!-- Barangay Policy Outcome Box -->
                    <div class="p-4 bg-purple-50/60 border border-purple-100 rounded-xl space-y-1.5">
                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-landmark text-purple-600"></i>
                            <span>Barangay Policy / Action Taken</span>
                        </span>
                        <p class="text-purple-900 font-bold text-xs leading-relaxed"><?php echo htmlspecialchars($pub['barangay_policy_outcome']); ?></p>
                    </div>

                </div>

                <!-- Action Footer: Download PDF & View Full Report -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                    <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                        <i class="fa-solid fa-shield-check text-emerald-500"></i>
                        <?php echo $pub['transparency_score']; ?>
                    </span>

                    <div class="flex items-center gap-2">
                        <button onclick="downloadPdfReport('<?php echo $pub['pdf_filename']; ?>')" class="px-4 py-2 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-file-pdf text-xs"></i>
                            <span>Download Results PDF</span>
                        </button>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </div>

</main>

<script>
function downloadPdfReport(filename) {
    alert(`Downloading Official Published Consultation Summary Report (${filename})...`);
}

function exportArchiveCSV() {
    alert('Exporting Published Consultations Transparency Log (CSV)...');
}
</script>

<?php include '../../includes/footer.php'; ?>
