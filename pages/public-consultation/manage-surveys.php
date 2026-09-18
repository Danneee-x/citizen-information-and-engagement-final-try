<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Mock Surveys Dataset following Section 5.1 Specifications
$surveys = [
    [
        'id' => 'SRV-2025-001',
        'title' => '2026 Barangay Disaster Resilience Plan Consultation',
        'description' => 'Gathering community feedback on disaster preparedness, flood prevention priorities, and evacuation center readiness for FY 2026.',
        'category' => 'Disaster Preparedness',
        'category_class' => 'bg-rose-50 text-rose-600 border-rose-100',
        'category_icon' => 'fa-triangle-exclamation',
        'target_type' => 'All Residents',
        'target' => 'All Residents (Districts 1-3)',
        'open_date' => 'Jun 1, 2025',
        'close_date' => 'Jun 30, 2025',
        'auto_close' => true,
        'privacy' => 'Identified', // Identified vs Anonymous
        'privacy_class' => 'bg-blue-50 text-[#0f53d1] border-blue-200',
        'responses' => 3420,
        'target_responses' => 4000,
        'progress_pct' => 85.5,
        'status' => 'Published',
        'status_class' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
        'created_by' => 'Juan Dela Cruz (Barangay Admin)',
        'q1_title' => 'What is the most urgent disaster risk in your barangay?',
        'q1_type' => 'Multiple Choice',
        'q1_options' => [
            ['label' => 'Flooding / Canal Blockage', 'pct' => 52, 'count' => '1,778', 'color' => 'bg-blue-500'],
            ['label' => 'Typhoon / Strong Winds', 'pct' => 28, 'count' => '957', 'color' => 'bg-emerald-500'],
            ['label' => 'Fire Hazard', 'pct' => 14, 'count' => '478', 'color' => 'bg-amber-500'],
            ['label' => 'Landslide / Soil Erosion', 'pct' => 6, 'count' => '207', 'color' => 'bg-purple-500'],
        ]
    ],
    [
        'id' => 'SRV-2025-002',
        'title' => 'Solar Streetlight Installation Site Selection',
        'description' => 'Identifying unlit pathways and dark street corners in Barangay 1 and Barangay 2 for priority solar streetlight mounting.',
        'category' => 'Infrastructure & Works',
        'category_class' => 'bg-blue-50 text-[#0f53d1] border-blue-100',
        'category_icon' => 'fa-lightbulb',
        'target_type' => 'Specific Barangay',
        'target' => 'Barangay 1 & Barangay 2 (District 1)',
        'open_date' => 'May 15, 2025',
        'close_date' => 'Jun 15, 2025',
        'auto_close' => true,
        'privacy' => 'Identified',
        'privacy_class' => 'bg-blue-50 text-[#0f53d1] border-blue-200',
        'responses' => 2150,
        'target_responses' => 2500,
        'progress_pct' => 86.0,
        'status' => 'Published',
        'status_class' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
        'created_by' => 'Engr. Pedro Ramos (Engineering Dept)',
        'q1_title' => 'Do you support replacing traditional streetlights with solar units?',
        'q1_type' => 'Rating Scale (1-5)',
        'q1_options' => [
            ['label' => '5 Stars - Strongly Support', 'pct' => 76, 'count' => '1,634', 'color' => 'bg-emerald-500'],
            ['label' => '4 Stars - Somewhat Support', 'pct' => 18, 'count' => '387', 'color' => 'bg-blue-500'],
            ['label' => '3 Stars - Neutral', 'pct' => 4, 'count' => '86', 'color' => 'bg-amber-500'],
            ['label' => '1-2 Stars - Oppose', 'pct' => 2, 'count' => '43', 'color' => 'bg-rose-500'],
        ]
    ],
    [
        'id' => 'SRV-2025-003',
        'title' => 'Senior Citizen Health & Maintenance Medicine Needs Assessment',
        'description' => 'Anonymous feedback survey to assess monthly maintenance medication requirements and door-to-door checkup frequency.',
        'category' => 'Health & Sanitation',
        'category_class' => 'bg-amber-50 text-amber-600 border-amber-100',
        'category_icon' => 'fa-heart-pulse',
        'target_type' => 'Specific Demographic',
        'target' => 'Senior Citizens (60+ yrs)',
        'open_date' => 'Jun 5, 2025',
        'close_date' => 'Jul 5, 2025',
        'auto_close' => true,
        'privacy' => 'Anonymous',
        'privacy_class' => 'bg-purple-50 text-purple-600 border-purple-200',
        'responses' => 1890,
        'target_responses' => 2000,
        'progress_pct' => 94.5,
        'status' => 'Published',
        'status_class' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
        'created_by' => 'Dr. Maria Santos (Health Officer)',
        'q1_title' => 'Which medical services do you require during health center visits? (Checkbox)',
        'q1_type' => 'Checkbox (Multiple Select)',
        'q1_options' => [
            ['label' => 'Free Maintenance Medicines', 'pct' => 61, 'count' => '1,152', 'color' => 'bg-emerald-500'],
            ['label' => 'Blood Pressure & Diabetes Monitoring', 'pct' => 48, 'count' => '907', 'color' => 'bg-blue-500'],
            ['label' => 'Physical Therapy & Rehabilitation', 'pct' => 22, 'count' => '415', 'color' => 'bg-purple-500'],
            ['label' => 'Dental Check-up & Extraction', 'pct' => 15, 'count' => '283', 'color' => 'bg-amber-500'],
        ]
    ],
    [
        'id' => 'SRV-2025-004',
        'title' => 'Youth Sports Complex & Recreational Facility Feedback',
        'description' => 'Consultation with barangay youth regarding sports tournament schedules and facility upgrades.',
        'category' => 'Youth & Sports',
        'category_class' => 'bg-purple-50 text-purple-600 border-purple-100',
        'category_icon' => 'fa-volleyball',
        'target_type' => 'Specific Demographic',
        'target' => 'Youth Residents (15-24 yrs)',
        'open_date' => 'May 1, 2025',
        'close_date' => 'May 31, 2025',
        'auto_close' => true,
        'privacy' => 'Identified',
        'privacy_class' => 'bg-blue-50 text-[#0f53d1] border-blue-200',
        'responses' => 2800,
        'target_responses' => 2800,
        'progress_pct' => 100.0,
        'status' => 'Closed',
        'status_class' => 'bg-slate-100 text-slate-600 border-slate-200',
        'created_by' => 'SK Chairman Mark Lim',
        'q1_title' => 'What sports court should be prioritized for renovation?',
        'q1_type' => 'Multiple Choice',
        'q1_options' => [
            ['label' => 'Basketball Covered Court', 'pct' => 45, 'count' => '1,260', 'color' => 'bg-blue-500'],
            ['label' => 'Volleyball Court', 'pct' => 30, 'count' => '840', 'color' => 'bg-purple-500'],
            ['label' => 'Badminton & Indoor Games', 'pct' => 15, 'count' => '420', 'color' => 'bg-emerald-500'],
            ['label' => 'Skate Park & Fitness Zone', 'pct' => 10, 'count' => '280', 'color' => 'bg-amber-500'],
        ]
    ],
    [
        'id' => 'SRV-2025-005',
        'title' => 'Proposed Barangay Ordinance on Waste Segregation & Penalties',
        'description' => 'Public ordinance feedback regarding mandatory household waste separation prior to garbage truck pickup.',
        'category' => 'Ordinance & Policy',
        'category_class' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'category_icon' => 'fa-scale-balanced',
        'target_type' => 'All Residents',
        'target' => 'All Household Heads',
        'open_date' => 'Jul 1, 2025',
        'close_date' => 'Jul 31, 2025',
        'auto_close' => true,
        'privacy' => 'Identified',
        'privacy_class' => 'bg-blue-50 text-[#0f53d1] border-blue-200',
        'responses' => 0,
        'target_responses' => 3500,
        'progress_pct' => 0.0,
        'status' => 'Draft',
        'status_class' => 'bg-amber-50 text-amber-600 border-amber-200',
        'created_by' => 'Kagawad Liza Gonzales',
        'q1_title' => 'Please provide open suggestions on how to improve garbage pickup schedules:',
        'q1_type' => 'Open Text Response',
        'q1_options' => []
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
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100 shadow-xs">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span>Public Consultation & Survey</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                        <span class="text-brand-dark">Manage Surveys</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Manage Surveys</h1>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="exportSurveysReport()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Reports</span>
            </button>

            <button onclick="openCreateSurveyModal()" class="px-4.5 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Survey Builder</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Surveys -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Surveys</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base border border-blue-100">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">28</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>+12.4% vs last month</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Published / Active -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Published / Active</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base border border-emerald-100">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">8 Published</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle text-[8px] animate-pulse"></i>
                    <span>Auto-close active</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Response Volume -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Responses</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base border border-purple-100">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">18,420</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-shield-check"></i>
                    <span>Identified & Anonymous</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Drafts & Closed -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Drafts & Closed</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base border border-amber-100">
                    <i class="fa-solid fa-folder-closed"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">12 Drafts / 8 Closed</h3>
                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Archived consultations</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Main Grid Layout (Table Container + Right Side Inspector Drawer) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Table Column (Full 12 cols when drawer closed, 8 cols when open) -->
        <div id="surveyTableContainer" class="lg:col-span-12 space-y-4 transition-all duration-300">

            <!-- Search & Multi-Filter Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="surveySearchInput" oninput="filterSurveysTable()" placeholder="Search by title, description, target Barangay, or demographic..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Target Audience Filter -->
                    <select id="surveyTargetFilter" onchange="filterSurveysTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Target Audiences</option>
                        <option value="All Residents">All Residents</option>
                        <option value="Specific Barangay">Specific Barangay (Caloocan)</option>
                        <option value="Specific Demographic">Specific Demographic</option>
                    </select>

                    <!-- Status Filter (Draft / Published / Closed) -->
                    <select id="surveyStatusFilter" onchange="filterSurveysTable()" class="bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="Published">Published / Active</option>
                        <option value="Draft">Draft</option>
                        <option value="Closed">Closed</option>
                    </select>

                    <!-- Reset Button -->
                    <button onclick="resetSurveyFilters()" class="px-3.5 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                        <i class="fa-solid fa-rotate-left text-slate-400"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Surveys Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Managed Surveys Directory <span id="surveysCountBadge" class="text-slate-400 font-normal ml-1">(5 surveys)</span></h3>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Survey Title & Category</th>
                                <th class="py-3.5 px-3">Target Audience</th>
                                <th class="py-3.5 px-3">Open / Close Dates</th>
                                <th class="py-3.5 px-3">Response Privacy</th>
                                <th class="py-3.5 px-3">Responses & Target</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="surveysTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <?php foreach ($surveys as $index => $srv): ?>
                            <tr onclick="selectSurveyRow(this, '<?php echo $srv['id']; ?>')" class="survey-row hover:bg-slate-50 transition cursor-pointer select-none" data-id="<?php echo $srv['id']; ?>" data-target-type="<?php echo htmlspecialchars($srv['target_type']); ?>" data-status="<?php echo htmlspecialchars($srv['status']); ?>">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl <?php echo explode(' ', $srv['category_class'])[0]; ?> <?php echo explode(' ', $srv['category_class'])[1]; ?> flex items-center justify-center shrink-0 text-sm border border-slate-200/50">
                                            <i class="fa-solid <?php echo $srv['category_icon']; ?>"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 text-xs truncate max-w-md"><?php echo htmlspecialchars($srv['title']); ?></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold text-slate-400"><?php echo $srv['id']; ?></span>
                                                <span class="px-2 py-0.2 rounded-md font-bold text-[9px] border <?php echo $srv['category_class']; ?>"><?php echo $srv['category']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-700">
                                    <span class="flex items-center gap-1"><i class="fa-solid fa-users text-[10px] text-slate-400"></i> <?php echo htmlspecialchars($srv['target']); ?></span>
                                    <span class="text-[9px] text-slate-400 block font-normal mt-0.5"><?php echo $srv['target_type']; ?></span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800 text-[11px]"><?php echo $srv['open_date']; ?> &bull; <?php echo $srv['close_date']; ?></p>
                                    <span class="text-[9px] font-bold text-amber-600 flex items-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-clock text-[8px]"></i> Auto-closes at deadline
                                    </span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $srv['privacy_class']; ?>">
                                        <i class="fa-solid <?php echo $srv['privacy'] === 'Identified' ? 'fa-id-card' : 'fa-user-secret'; ?> text-[9px] mr-1"></i>
                                        <?php echo $srv['privacy']; ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 min-w-[130px]">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-800 mb-1">
                                        <span><?php echo number_format($srv['responses']); ?></span>
                                        <span class="text-[10px] text-slate-400 font-normal">of <?php echo number_format($srv['target_responses']); ?></span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-[#0f53d1] h-1.5 rounded-full transition-all" style="width: <?php echo $srv['progress_pct']; ?>%;"></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?php echo $srv['status_class']; ?>"><?php echo $srv['status']; ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="event.stopPropagation(); selectSurveyRow(this.closest('tr'), '<?php echo $srv['id']; ?>');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-[#0f53d1] flex items-center justify-center transition cursor-pointer" title="View Survey Details"><i class="fa-regular fa-eye text-xs"></i></button>
                                        <button onclick="event.stopPropagation(); editSurvey('<?php echo $srv['id']; ?>');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-[#0f53d1] flex items-center justify-center transition cursor-pointer" title="Edit Survey"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                        <button onclick="event.stopPropagation(); deleteSurvey(this, '<?php echo $srv['id']; ?>');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-rose-600 flex items-center justify-center transition cursor-pointer" title="Delete Survey"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noSurveysRow" class="hidden">
                                <td colspan="7" class="p-8 text-center text-slate-400 font-medium text-xs">
                                    <i class="fa-solid fa-folder-open text-2xl mb-2 block text-slate-300"></i>
                                    No surveys match the selected filter parameters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div class="px-4 py-3 bg-slate-50/50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
                    <div>
                        <span>Showing 1 to 5 of 5 surveys</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer text-xs"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <button class="w-7 h-7 rounded-lg bg-[#0f53d1] text-white font-bold flex items-center justify-center shadow-xs text-xs">1</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer text-xs"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px]">Rows per page</span>
                        <select class="bg-white border border-slate-200 rounded-lg text-xs font-bold px-2 py-1 outline-none cursor-pointer">
                            <option>10</option>
                            <option>25</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side Inspector Drawer: Survey Details & Live Analytics (Hidden by Default) -->
        <div id="surveyDetailsDrawer" class="hidden lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-5 sticky top-6">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span id="drawerSurveyCode" class="text-xs font-bold text-slate-400">SRV-2025-001</span>
                    <span id="drawerSurveyStatusBadge" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200">Published</span>
                </div>
                <button onclick="closeSurveyDrawer()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Survey Title & Target Header -->
            <div class="space-y-2">
                <h3 id="drawerSurveyTitle" class="text-sm font-black text-slate-900 leading-snug">2026 Barangay Disaster Resilience Plan Consultation</h3>
                <p id="drawerSurveyDescription" class="text-xs text-slate-600 font-medium leading-relaxed">Gathering community feedback on disaster preparedness, flood prevention priorities, and evacuation center readiness for FY 2026.</p>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 pt-1">
                    <span id="drawerSurveyCategory" class="px-2.5 py-0.5 rounded-md bg-rose-50 text-rose-600 font-bold text-[10px] border border-rose-100">Disaster Preparedness</span>
                    <span class="text-slate-300">&bull;</span>
                    <span id="drawerSurveyPrivacy" class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-blue-50 text-[#0f53d1] border border-blue-200">Identified</span>
                </div>
            </div>

            <!-- Real-Time Response Stats Grid -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Total Responses</span>
                    <h4 id="drawerTotalResponses" class="text-lg font-black text-slate-900">3,420</h4>
                    <span class="text-[9px] text-emerald-600 font-bold">85.5% Target Reached</span>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Target Audience</span>
                    <h4 id="drawerSurveyTarget" class="text-xs font-black text-slate-900 truncate">All Residents</h4>
                    <span class="text-[9px] text-slate-400 font-medium">Filtered Group</span>
                </div>
            </div>

            <!-- Question Breakdown Preview -->
            <div class="space-y-3 border-t border-slate-100 pt-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Question Results Preview</h4>
                    <span id="drawerQ1TypeBadge" class="text-[10px] font-bold text-[#0f53d1]">Multiple Choice</span>
                </div>

                <p id="drawerQ1Title" class="text-xs font-bold text-slate-800 leading-tight">What is the most urgent disaster risk in your barangay?</p>

                <!-- Options Breakdown Container -->
                <div id="drawerQ1OptionsContainer" class="space-y-2.5 text-xs">
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                            <span>Flooding / Canal Blockage</span>
                            <span class="text-slate-900 font-black">52% (1,778)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 52%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Survey Metadata & Auto-Close Info -->
            <div class="border-t border-slate-100 pt-3 space-y-2 text-xs">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Created By</span>
                    <span id="drawerCreatedBy" class="font-bold text-slate-800">Juan Dela Cruz (Barangay Admin)</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Open / Close Dates</span>
                    <span id="drawerDates" class="font-bold text-slate-800">Jun 1, 2025 – Jun 30, 2025</span>
                </div>
                <div class="p-2.5 bg-amber-50 border border-amber-200/80 rounded-xl text-[11px] text-amber-800 font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-clock text-amber-600 text-xs"></i>
                    <span>Auto-closes automatically after deadline date.</span>
                </div>
            </div>

            <!-- Drawer Action Buttons -->
            <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="viewLiveResultsPage()" class="flex-1 py-2.5 bg-[#0f53d1] hover:bg-[#0d46b0] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-chart-line text-xs"></i>
                    <span>Live Results</span>
                </button>

                <button type="button" onclick="exportSurveyResponses()" class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-download text-slate-400 text-xs"></i>
                    <span>Export CSV</span>
                </button>
            </div>

        </div>

    </div>

</main>

<!-- SECTION 5.1 SURVEY BUILDER MODAL -->
<div id="createSurveyModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-lg border border-blue-100">
                    <i class="fa-solid fa-hammer"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Survey Builder</h3>
                    <p class="text-xs text-slate-500 font-medium">Configure survey details, question types, target audience & auto-close</p>
                </div>
            </div>
            <button onclick="closeCreateSurveyModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Title & Description -->
            <div>
                <label class="font-bold text-slate-700 block mb-1">Survey Title <span class="text-rose-500">*</span></label>
                <input type="text" id="newSurveyTitle" placeholder="e.g., 2026 Barangay Road Repair & Infrastructure Priorities" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-3 outline-none font-medium text-xs focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>

            <div>
                <label class="font-bold text-slate-700 block mb-1">Survey Description / Objective <span class="text-rose-500">*</span></label>
                <textarea id="newSurveyDescription" rows="2" placeholder="Briefly describe the purpose of this public consultation for citizens..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-3 outline-none font-medium text-xs focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]"></textarea>
            </div>

            <!-- Category & Target Audience Filter -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Category <span class="text-rose-500">*</span></label>
                    <select id="newSurveyCategory" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="Infrastructure & Works">Infrastructure & Works</option>
                        <option value="Health & Sanitation">Health & Sanitation</option>
                        <option value="Disaster Preparedness">Disaster Preparedness</option>
                        <option value="Youth & Sports">Youth & Sports</option>
                        <option value="Ordinance & Policy">Ordinance & Policy</option>
                        <option value="Commerce & Markets">Commerce & Markets</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Target Audience Filter <span class="text-rose-500">*</span></label>
                    <select id="newSurveyTargetType" onchange="onTargetTypeChange(this.value)" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                        <option value="All Residents">All Residents (Entire Caloocan)</option>
                        <option value="Specific Barangay">Specific Barangay (Caloocan City)</option>
                        <option value="Specific Demographic">Specific Demographic (Seniors, Youth, PWD)</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Specific Target Dropdown Container -->
            <div id="specificTargetSubContainer" class="hidden p-3 bg-blue-50/50 border border-blue-100 rounded-xl">
                <label id="subTargetLabel" class="font-bold text-slate-700 block mb-1">Select Specific Purok</label>
                <select id="newSurveySubTarget" class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none font-medium text-xs cursor-pointer">
                    <!-- Options injected via JS -->
                </select>
            </div>

            <!-- Open Date / Close Date (Auto-close after deadline) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Open Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="newSurveyStartDate" value="2025-06-15" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Close Date (Auto-closes after deadline) <span class="text-rose-500">*</span></label>
                    <input type="date" id="newSurveyEndDate" value="2025-07-15" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer">
                </div>
            </div>

            <!-- Anonymous vs. Identified Responses Toggle -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between select-none">
                <div>
                    <span class="font-bold text-slate-900 block text-xs">Response Privacy Setting</span>
                    <span class="text-[11px] text-slate-500 font-medium">Require Citizen Verification (Identified) vs. Anonymous Responses</span>
                </div>
                <label class="flex items-center gap-2 cursor-pointer shrink-0">
                    <input type="checkbox" id="toggleAnonymous" onchange="togglePrivacyLabel(this)" class="w-4 h-4 text-[#0f53d1] border-slate-300 rounded focus:ring-[#0f53d1]/40 cursor-pointer">
                    <span id="privacyToggleText" class="font-bold text-[#0f53d1] text-xs">Identified Responses</span>
                </label>
            </div>

            <!-- Question Types Builder (Multiple choice, Checkbox, Rating scale, Open text) -->
            <div class="border-t border-slate-100 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-black text-slate-900 text-xs uppercase tracking-wider">Question Types & Builder</h4>
                        <span class="text-[10px] text-slate-400 font-medium">Supported: Multiple Choice, Checkbox, Rating Scale, Open Text</span>
                    </div>
                    <button type="button" onclick="addQuestionField()" class="text-[11px] font-bold text-[#0f53d1] hover:underline flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Add Question
                    </button>
                </div>

                <div id="questionsBuilderContainer" class="space-y-3">
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700 text-xs">Question 1</span>
                            <select class="bg-white border border-slate-200 text-slate-700 text-[11px] font-semibold rounded-lg px-2 py-1 outline-none cursor-pointer">
                                <option value="Multiple Choice">Multiple Choice (Single Select)</option>
                                <option value="Checkbox">Checkbox (Multiple Select)</option>
                                <option value="Rating Scale">Rating Scale (1-5 Stars)</option>
                                <option value="Open Text">Open Text Response</option>
                            </select>
                        </div>
                        <input type="text" placeholder="e.g., Which community project should be prioritized first?" class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium">
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <input type="text" placeholder="Option 1 (e.g. Drainage Repair)" class="bg-white border border-slate-200 rounded-lg p-1.5 outline-none font-medium">
                            <input type="text" placeholder="Option 2 (e.g. Solar Streetlights)" class="bg-white border border-slate-200 rounded-lg p-1.5 outline-none font-medium">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button onclick="closeCreateSurveyModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="saveNewSurvey('Draft')" class="px-4 py-2.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-floppy-disk text-amber-600"></i>
                <span>Save as Draft</span>
            </button>
            <button onclick="saveNewSurvey('Published')" class="px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Publish Survey Now</span>
            </button>
        </div>
    </div>
</div>

<script>
const surveysData = <?php echo json_encode(array_column($surveys, null, 'id')); ?>;

let activeSurveyId = null;

function selectSurveyRow(rowElement, id) {
    const drawer = document.getElementById('surveyDetailsDrawer');
    const tableContainer = document.getElementById('surveyTableContainer');

    if (activeSurveyId === id && drawer && !drawer.classList.contains('hidden')) {
        closeSurveyDrawer();
        return;
    }

    activeSurveyId = id;
    document.querySelectorAll('.survey-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    rowElement.classList.add('bg-blue-50/40');

    const data = surveysData[id];
    if (!data) return;

    // Update Drawer UI
    document.getElementById('drawerSurveyCode').innerText = data.id;
    document.getElementById('drawerSurveyTitle').innerText = data.title;
    document.getElementById('drawerSurveyDescription').innerText = data.description || 'No description provided.';
    document.getElementById('drawerSurveyCategory').innerText = data.category;
    document.getElementById('drawerSurveyCategory').className = `px-2.5 py-0.5 rounded-md font-bold text-[10px] border ${data.category_class}`;
    document.getElementById('drawerSurveyTarget').innerText = data.target;

    const privacyBadge = document.getElementById('drawerSurveyPrivacy');
    privacyBadge.innerText = data.privacy || 'Identified';
    privacyBadge.className = `px-2 py-0.5 rounded-full font-bold text-[10px] border ${data.privacy_class || 'bg-blue-50 text-[#0f53d1] border-blue-200'}`;

    document.getElementById('drawerTotalResponses').innerText = Number(data.responses).toLocaleString();
    document.getElementById('drawerCreatedBy').innerText = data.created_by;
    document.getElementById('drawerDates').innerText = `${data.open_date} – ${data.close_date}`;

    const statusBadge = document.getElementById('drawerSurveyStatusBadge');
    statusBadge.innerText = data.status;
    statusBadge.className = `px-2 py-0.5 text-[10px] font-bold rounded-full border ${data.status_class}`;

    // Question 1 Preview
    document.getElementById('drawerQ1Title').innerText = data.q1_title;
    document.getElementById('drawerQ1TypeBadge').innerText = data.q1_type || 'Multiple Choice';

    const optionsContainer = document.getElementById('drawerQ1OptionsContainer');
    optionsContainer.innerHTML = '';

    if (data.q1_options && data.q1_options.length > 0) {
        data.q1_options.forEach(opt => {
            optionsContainer.innerHTML += `
                <div>
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                        <span>${opt.label}</span>
                        <span class="text-slate-900 font-black">${opt.pct}% (${opt.count})</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="${opt.color || 'bg-blue-500'} h-2 rounded-full transition-all" style="width: ${opt.pct}%;"></div>
                    </div>
                </div>
            `;
        });
    } else {
        optionsContainer.innerHTML = `<p class="text-xs text-slate-400 italic">Open text answers collected anonymously.</p>`;
    }

    if (drawer) {
        drawer.classList.remove('hidden');
        tableContainer.className = "lg:col-span-8 space-y-4 transition-all duration-300";
    }
}

function closeSurveyDrawer() {
    activeSurveyId = null;
    document.querySelectorAll('.survey-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    const drawer = document.getElementById('surveyDetailsDrawer');
    const tableContainer = document.getElementById('surveyTableContainer');
    if (drawer) drawer.classList.add('hidden');
    if (tableContainer) tableContainer.className = "lg:col-span-12 space-y-4 transition-all duration-300";
}

function filterSurveysTable() {
    const searchVal = document.getElementById('surveySearchInput').value.toLowerCase();
    const targetVal = document.getElementById('surveyTargetFilter').value.toLowerCase();
    const statusVal = document.getElementById('surveyStatusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.survey-row');
    let visibleCount = 0;

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const targetType = (r.getAttribute('data-target-type') || '').toLowerCase();
        const status = (r.getAttribute('data-status') || '').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesTarget = !targetVal || targetType.includes(targetVal);
        const matchesStatus = !statusVal || status.includes(statusVal);

        if (matchesSearch && matchesTarget && matchesStatus) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noSurveysRow');
    if (noRow) {
        if (visibleCount === 0) {
            noRow.classList.remove('hidden');
            noRow.style.display = '';
        } else {
            noRow.classList.add('hidden');
            noRow.style.display = 'none';
        }
    }
}

function resetSurveyFilters() {
    document.getElementById('surveySearchInput').value = '';
    document.getElementById('surveyTargetFilter').value = '';
    document.getElementById('surveyStatusFilter').value = '';
    filterSurveysTable();
}

function openCreateSurveyModal() {
    document.getElementById('createSurveyModal').classList.remove('hidden');
}

function closeCreateSurveyModal() {
    document.getElementById('createSurveyModal').classList.add('hidden');
}

function onTargetTypeChange(val) {
    const container = document.getElementById('specificTargetSubContainer');
    const label = document.getElementById('subTargetLabel');
    const select = document.getElementById('newSurveySubTarget');

    if (val === 'Specific Barangay') {
        container.classList.remove('hidden');
        label.textContent = 'Select Caloocan Barangay';
        select.innerHTML = `
            <option value="Barangay 1 (District 1)">Barangay 1 (District 1)</option>
            <option value="Barangay 12 (District 2)">Barangay 12 (District 2)</option>
            <option value="Barangay 77 (District 1)">Barangay 77 (District 1)</option>
            <option value="Barangay 176 (Bagong Silang - District 1)">Barangay 176 (Bagong Silang - District 1)</option>
            <option value="Barangay 178 (Camarin - District 3)">Barangay 178 (Camarin - District 3)</option>
            <option value="Barangay 188 (Tala - District 3)">Barangay 188 (Tala - District 3)</option>
        `;
    } else if (val === 'Specific Demographic') {
        container.classList.remove('hidden');
        label.textContent = 'Select Target Demographic Group';
        select.innerHTML = `
            <option value="Senior Citizens (60+ yrs)">Senior Citizens (60+ yrs)</option>
            <option value="Youth Residents (15-24 yrs)">Youth Residents (15-24 yrs)</option>
            <option value="Persons with Disabilities (PWDs)">Persons with Disabilities (PWDs)</option>
            <option value="Solo Parents">Solo Parents</option>
            <option value="Registered Voters">Registered Voters</option>
        `;
    } else {
        container.classList.add('hidden');
    }
}

function togglePrivacyLabel(cb) {
    const text = document.getElementById('privacyToggleText');
    if (cb.checked) {
        text.textContent = 'Anonymous Responses';
        text.className = 'font-bold text-purple-600 text-xs';
    } else {
        text.textContent = 'Identified Responses';
        text.className = 'font-bold text-[#0f53d1] text-xs';
    }
}

let questionCount = 1;
function addQuestionField() {
    questionCount++;
    const container = document.getElementById('questionsBuilderContainer');
    const div = document.createElement('div');
    div.className = 'p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5';
    div.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="font-bold text-slate-700 text-xs">Question ${questionCount}</span>
            <select class="bg-white border border-slate-200 text-slate-700 text-[11px] font-semibold rounded-lg px-2 py-1 outline-none cursor-pointer">
                <option value="Multiple Choice">Multiple Choice (Single Select)</option>
                <option value="Checkbox">Checkbox (Multiple Select)</option>
                <option value="Rating Scale">Rating Scale (1-5 Stars)</option>
                <option value="Open Text">Open Text Response</option>
            </select>
        </div>
        <input type="text" placeholder="Enter question prompt..." class="w-full bg-white border border-slate-200 text-slate-800 rounded-lg p-2 outline-none text-xs font-medium">
    `;
    container.appendChild(div);
}

function saveNewSurvey(statusState) {
    const title = document.getElementById('newSurveyTitle').value.trim();
    const description = document.getElementById('newSurveyDescription').value.trim();
    const category = document.getElementById('newSurveyCategory').value;
    const targetType = document.getElementById('newSurveyTargetType').value;
    const isAnonymous = document.getElementById('toggleAnonymous').checked;

    let targetLabel = targetType;
    if (targetType === 'Specific Barangay' || targetType === 'Specific Demographic') {
        const subVal = document.getElementById('newSurveySubTarget').value;
        if (subVal) targetLabel = subVal;
    }

    if (!title) {
        alert('Please enter a Survey Title before proceeding.');
        return;
    }

    const newId = `SRV-2025-00${Math.floor(Math.random() * 90) + 10}`;
    const privacy = isAnonymous ? 'Anonymous' : 'Identified';
    const privacyClass = isAnonymous ? 'bg-purple-50 text-purple-600 border-purple-200' : 'bg-blue-50 text-[#0f53d1] border-blue-200';

    let statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-200';
    if (statusState === 'Draft') statusClass = 'bg-amber-50 text-amber-600 border-amber-200';
    if (statusState === 'Closed') statusClass = 'bg-slate-100 text-slate-600 border-slate-200';

    const tbody = document.getElementById('surveysTableBody');
    const tr = document.createElement('tr');
    tr.className = 'survey-row hover:bg-slate-50 transition cursor-pointer select-none';
    tr.setAttribute('onclick', `selectSurveyRow(this, '${newId}')`);
    tr.setAttribute('data-id', newId);
    tr.setAttribute('data-target-type', targetType);
    tr.setAttribute('data-status', statusState);

    tr.innerHTML = `
        <td class="py-3.5 px-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm border border-slate-200/50">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-bold text-slate-900 text-xs truncate max-w-md">${title}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] font-bold text-slate-400">${newId}</span>
                        <span class="px-2 py-0.2 rounded-md font-bold text-[9px] border bg-blue-50 text-[#0f53d1] border-blue-100">${category}</span>
                    </div>
                </div>
            </div>
        </td>
        <td class="py-3.5 px-3 font-semibold text-slate-700">
            <span class="flex items-center gap-1"><i class="fa-solid fa-users text-[10px] text-slate-400"></i> ${targetLabel}</span>
            <span class="text-[9px] text-slate-400 block font-normal mt-0.5">${targetType}</span>
        </td>
        <td class="py-3.5 px-3 whitespace-nowrap">
            <p class="font-bold text-slate-800 text-[11px]">Jun 15, 2025 &bull; Jul 15, 2025</p>
            <span class="text-[9px] font-bold text-amber-600 flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-clock text-[8px]"></i> Auto-closes at deadline
            </span>
        </td>
        <td class="py-3.5 px-3">
            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border ${privacyClass}">
                <i class="fa-solid ${isAnonymous ? 'fa-user-secret' : 'fa-id-card'} text-[9px] mr-1"></i>
                ${privacy}
            </span>
        </td>
        <td class="py-3.5 px-3 min-w-[130px]">
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-800 mb-1">
                <span>0</span>
                <span class="text-[10px] text-slate-400 font-normal">of 2,500</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-[#0f53d1] h-1.5 rounded-full transition-all" style="width: 0%;"></div>
            </div>
        </td>
        <td class="py-3.5 px-3 text-center">
            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border ${statusClass}">${statusState}</span>
        </td>
        <td class="py-3.5 px-3 text-center">
            <div class="flex items-center justify-center gap-1">
                <button onclick="event.stopPropagation(); selectSurveyRow(this.closest('tr'), '${newId}');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-[#0f53d1] flex items-center justify-center transition cursor-pointer"><i class="fa-regular fa-eye text-xs"></i></button>
                <button onclick="event.stopPropagation(); editSurvey('${newId}');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-[#0f53d1] flex items-center justify-center transition cursor-pointer"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                <button onclick="event.stopPropagation(); deleteSurvey(this, '${newId}');" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-rose-600 flex items-center justify-center transition cursor-pointer"><i class="fa-solid fa-trash-can text-xs"></i></button>
            </div>
        </td>
    `;

    surveysData[newId] = {
        id: newId,
        title: title,
        description: description,
        category: category,
        category_class: 'bg-blue-50 text-[#0f53d1] border-blue-100',
        target_type: targetType,
        target: targetLabel,
        open_date: 'Jun 15, 2025',
        close_date: 'Jul 15, 2025',
        privacy: privacy,
        privacy_class: privacyClass,
        responses: 0,
        target_responses: 2500,
        progress_pct: 0,
        status: statusState,
        status_class: statusClass,
        created_by: 'Current Admin',
        q1_title: 'Sample Question',
        q1_type: 'Multiple Choice',
        q1_options: [
            { label: 'Option 1', pct: 0, count: '0', color: 'bg-blue-500' }
        ]
    };

    tbody.prepend(tr);
    closeCreateSurveyModal();
    document.getElementById('newSurveyTitle').value = '';
    document.getElementById('newSurveyDescription').value = '';
    alert(`Survey "${title}" has been successfully saved with status "${statusState}"!`);
}

function editSurvey(id) {
    const data = surveysData[id];
    alert(`Editing Survey ID #${id}: ${data ? data.title : ''}`);
}

function deleteSurvey(btn, id) {
    if (confirm(`Are you sure you want to delete Survey ID #${id}?`)) {
        btn.closest('tr').remove();
        if (activeSurveyId === id) closeSurveyDrawer();
    }
}

function viewLiveResultsPage() {
    const sId = activeSurveyId || 'SRV-2025-001';
    window.location.href = '<?php echo $basePath; ?>pages/public-consultation/live-results.php?id=' + sId;
}

function exportSurveysReport() {
    alert('Exporting Public Surveys Overview & Metrics (CSV/PDF)...');
}

function exportSurveyResponses() {
    alert('Exporting survey responses dataset (CSV)...');
}
</script>

<?php include '../../includes/footer.php'; ?>
