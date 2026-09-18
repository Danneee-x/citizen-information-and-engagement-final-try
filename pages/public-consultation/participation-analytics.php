<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Turnout & Demographic Analytics Dataset
$demographics = [
    'age_groups' => [
        ['group' => 'Youth (18 - 29 yrs)', 'count' => 1240, 'pct' => 36.2, 'color' => 'bg-blue-500'],
        ['group' => 'Adults (30 - 49 yrs)', 'count' => 1380, 'pct' => 40.3, 'color' => 'bg-[#0f53d1]'],
        ['group' => 'Middle-Aged (50 - 59 yrs)', 'count' => 480, 'pct' => 14.0, 'color' => 'bg-purple-500'],
        ['group' => 'Senior Citizens (60+ yrs)', 'count' => 320, 'pct' => 9.5, 'color' => 'bg-amber-500']
    ],
    'gender' => [
        ['label' => 'Female', 'pct' => 54.2, 'count' => 1853, 'color' => 'bg-rose-500'],
        ['label' => 'Male', 'pct' => 44.1, 'count' => 1508, 'color' => 'bg-blue-500'],
        ['label' => 'Prefer not to say', 'pct' => 1.7, 'count' => 59, 'color' => 'bg-slate-400']
    ],
    'barangays' => [
        ['name' => 'Barangay 178, Camarin', 'responses' => 1420, 'eligible' => 1800, 'turnout' => 78.8, 'status' => 'High Participation'],
        ['name' => 'Barangay 176, Bagong Silang', 'responses' => 1150, 'eligible' => 1600, 'turnout' => 71.8, 'status' => 'High Participation'],
        ['name' => 'Barangay 12, Caloocan', 'responses' => 520, 'eligible' => 800, 'turnout' => 65.0, 'status' => 'Moderate Participation'],
        ['name' => 'Barangay 88, Caloocan', 'responses' => 330, 'eligible' => 600, 'turnout' => 55.0, 'status' => 'Low Participation (Needs Reminder)']
    ]
];

// Non-Response Tracking List for Follow-up Reminders
$nonResponders = [
    [
        'segment' => 'Senior Citizens (Barangay 88)',
        'eligible_count' => 270,
        'responded_count' => 85,
        'non_response_rate' => '68.5% Non-Response',
        'last_reminder' => '2 days ago',
        'priority' => 'High (Send Reminder)'
    ],
    [
        'segment' => 'Purok 4 Residents (Barangay 12)',
        'eligible_count' => 340,
        'responded_count' => 140,
        'non_response_rate' => '58.8% Non-Response',
        'last_reminder' => '5 days ago',
        'priority' => 'Medium'
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
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>Public Consultation & Survey</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-brand-dark">Participation Analytics</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Participation Analytics</h1>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="exportAnalyticsCSV()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Analytics</span>
            </button>

            <button onclick="sendBulkRemindersModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-bell text-xs"></i>
                <span>Broadcast Follow-Up Reminder</span>
            </button>
        </div>
    </div>

    <!-- Analytics Stat Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Turnout Rate -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Overall Turnout Rate</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-percent"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">68.4%</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>+8.2% vs previous survey</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Total Eligible Citizens -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Eligible Audience</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">5,000 Citizens</h3>
                <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Registered in Citizen Registry</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Senior Citizen Participation -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Senior Citizen Turnout</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-person-cane"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">72.1%</h3>
                <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-heart"></i>
                    <span>High senior Engagement</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Non-Response Segment Count -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Non-Response Gaps</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-base border border-rose-100">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">2 Segments</h3>
                <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-bell"></i>
                    <span>Requires follow-up reminders</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Main Demographic Breakdown Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Age Group Breakdown (6 Cols) -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Age Group Participation</h3>
                <span class="text-xs text-slate-400 font-medium">3,420 total responses</span>
            </div>

            <div class="space-y-3.5">
                <?php foreach ($demographics['age_groups'] as $ag): ?>
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-800"><?php echo $ag['group']; ?></span>
                        <span class="text-[#0f53d1]"><?php echo $ag['pct']; ?>% (<?php echo number_format($ag['count']); ?>)</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $ag['color']; ?> rounded-full" style="width: <?php echo $ag['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Gender Breakdown (6 Cols) -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Gender Distribution</h3>
                <span class="text-xs text-slate-400 font-medium">Verified Citizen Demographics</span>
            </div>

            <div class="space-y-4">
                <?php foreach ($demographics['gender'] as $g): ?>
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-800"><?php echo $g['label']; ?></span>
                        <span class="text-slate-900"><?php echo $g['pct']; ?>% (<?php echo number_format($g['count']); ?>)</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $g['color']; ?> rounded-full" style="width: <?php echo $g['pct']; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Caloocan Barangay Turnout Comparison Table (12 Cols) -->
        <div class="lg:col-span-12 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Caloocan Barangay Turnout Rate Breakdown</h3>
                <span class="text-xs text-slate-400 font-medium">Target vs Actual Participation per Area</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Caloocan Barangay</th>
                            <th class="py-3.5 px-3">Responses Recorded</th>
                            <th class="py-3.5 px-3">Eligible Citizens</th>
                            <th class="py-3.5 px-3">Turnout Percentage</th>
                            <th class="py-3.5 px-3 text-center">Participation Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        <?php foreach ($demographics['barangays'] as $b): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <?php echo htmlspecialchars($b['name']); ?>
                            </td>
                            <td class="py-3.5 px-3 font-bold text-[#0f53d1]">
                                <?php echo number_format($b['responses']); ?>
                            </td>
                            <td class="py-3.5 px-3 text-slate-600">
                                <?php echo number_format($b['eligible']); ?>
                            </td>
                            <td class="py-3.5 px-3 font-black text-slate-900">
                                <?php echo $b['turnout']; ?>%
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?php echo $b['turnout'] >= 70 ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : ($b['turnout'] >= 60 ? 'bg-blue-50 text-[#0f53d1] border-blue-200' : 'bg-rose-50 text-rose-600 border-rose-200'); ?>">
                                    <?php echo $b['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Non-Response Tracking & Broadcast Reminder Section (12 Cols) -->
        <div class="lg:col-span-12 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-black text-rose-600 uppercase tracking-wider block">Non-Response Tracking & Follow-up</span>
                    <h3 class="text-base font-black text-slate-900">Low-Participation Demographic Segments</h3>
                </div>
                <button onclick="sendBulkRemindersModal()" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Trigger Reminders for All Low Turnout</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <?php foreach ($nonResponders as $nr): ?>
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($nr['segment']); ?></span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200"><?php echo $nr['non_response_rate']; ?></span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Responded: <?php echo $nr['responded_count']; ?> / <?php echo $nr['eligible_count']; ?></span>
                        <span>Last Reminder: <?php echo $nr['last_reminder']; ?></span>
                    </div>

                    <button onclick="sendSegmentReminder('<?php echo $nr['segment']; ?>')" class="w-full py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs rounded-lg transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-bell text-xs"></i>
                        <span>Send Target SMS & Push Reminder</span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</main>

<script>
function sendSegmentReminder(segment) {
    alert(`Targeted SMS & Mobile App Push Reminder sent to ${segment}!`);
}

function sendBulkRemindersModal() {
    if (confirm('Send automated follow-up survey reminders to all non-responding citizens?')) {
        alert('Bulk SMS and notification reminders dispatched to non-responding audience.');
    }
}

function exportAnalyticsCSV() {
    alert('Exporting Participation Analytics & Demographic Report (CSV)...');
}
</script>

<?php include '../../includes/footer.php'; ?>
