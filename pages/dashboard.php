<?php
$basePath = '../';
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$basePathResolver = $basePath ?? '../';

$pdo = getDbConnection();

// Fetch live counts and demographics
$vStats = $pdo->query("SELECT 
    COUNT(*) as total_verifications,
    SUM(CASE WHEN verification_status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN verification_status IN ('Pending', 'Under_Review') THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN verification_status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60 THEN 1 ELSE 0 END) as senior_count,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 30 AND 59 THEN 1 ELSE 0 END) as adult_count,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 30 THEN 1 ELSE 0 END) as youth_count,
    SUM(CASE WHEN civil_status IN ('Widowed', 'Separated', 'Divorced / Annulled', 'Common-Law / Live-In') THEN 1 ELSE 0 END) as solo_parent_count
    FROM citizen_verifications")->fetch(PDO::FETCH_ASSOC);

$totalV = (int)($vStats['total_verifications'] ?? 0);
$approvedV = (int)($vStats['approved_count'] ?? 0);
$pendingV = (int)($vStats['pending_count'] ?? 0);
$rejectedV = (int)($vStats['rejected_count'] ?? 0);
$kycRate = $totalV > 0 ? round(($approvedV / $totalV) * 100, 1) : 0;

$seniorCount = (int)($vStats['senior_count'] ?? 0);
$adultCount = (int)($vStats['adult_count'] ?? 0);
$youthCount = (int)($vStats['youth_count'] ?? 0);
$soloParentCount = (int)($vStats['solo_parent_count'] ?? 0);

// Recent activity events from DB
$recentEvents = $pdo->query("SELECT 
    verification_id, first_name, last_name, verification_status, reviewed_by, reviewed_at, submitted_at, district, barangay, valid_id_type,
    CASE 
        WHEN reviewed_at IS NOT NULL AND verification_status = 'Approved' THEN reviewed_at
        WHEN reviewed_at IS NOT NULL AND verification_status = 'Rejected' THEN reviewed_at
        ELSE submitted_at
    END AS activity_time,
    CASE 
        WHEN reviewed_at IS NOT NULL AND verification_status = 'Approved' THEN 'approved'
        WHEN reviewed_at IS NOT NULL AND verification_status = 'Rejected' THEN 'rejected'
        ELSE 'submitted'
    END AS activity_type
    FROM citizen_verifications
    ORDER BY activity_time DESC
    LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

function getRelativeTimeStr($datetime) {
    if (empty($datetime)) return 'Recently';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hr' . (round($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 172800) return 'Yesterday';
    return date('M d, Y', $time);
}
?>

<!-- Load Custom Dashboard Stylesheet -->
<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/dashboard-analytics.css">

    <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto space-y-6 overflow-y-auto">
      
      <!-- Breadcrumb Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
            <span>Citizen Information & Engagement</span>
            <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
            <span class="text-brand-dark font-extrabold">Dashboard Overview</span>
          </div>
          <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">Citizen Engagement Command Center</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button onclick="location.reload()" class="px-3.5 py-2 text-xs font-bold text-[#0f53d1] bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2 shadow-xs">
            <i class="fa-solid fa-rotate text-[11px]"></i>
            <span>Refresh</span>
          </button>
          <a href="citizen-registry/pending-approvals.php" class="px-3.5 py-2 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs flex items-center gap-2">
            <i class="fa-solid fa-user-check text-[11px]"></i>
            <span>Pending Review (<?php echo $pendingV; ?>)</span>
          </a>
        </div>
      </div>

      <!-- Top Metric Cards Row (4 Live Cards) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Widget 1: Registered Citizens -->
        <a href="citizen-registry/registered-citizens.php" class="glass-panel glow-card-navy rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80 hover:shadow-md transition-all">
          <div class="space-y-1">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Registered Citizens</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tight"><?php echo number_format($approvedV); ?></span>
              <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                <i class="fa-solid fa-check text-[9px]"></i> Verified
              </span>
            </div>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">Caloocan biometric resident profiles</p>
          </div>
          <div class="h-11 w-11 rounded-xl bg-brand-light dark:bg-slate-800 text-brand-dark dark:text-brand-medium border border-brand-border/40 dark:border-slate-700/60 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-brand-dark group-hover:text-white dark:group-hover:bg-brand-medium">
            <i class="fa-solid fa-users text-sm"></i>
          </div>
        </a>

        <!-- Widget 2: Verification Rate -->
        <a href="citizen-registry/id-verification-logs.php" class="glass-panel glow-card-teal rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80 hover:shadow-md transition-all">
          <div class="space-y-1">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">KYC Verification Rate</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tight"><?php echo $kycRate; ?>%</span>
              <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                <i class="fa-solid fa-shield-halved text-[9px]"></i> LGU Clean
              </span>
            </div>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium"><?php echo $approvedV; ?> of <?php echo $totalV; ?> submitted IDs passed audit</p>
          </div>
          <div class="h-11 w-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-100/60 dark:border-emerald-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-emerald-600 group-hover:text-white">
            <i class="fa-solid fa-circle-check text-sm"></i>
          </div>
        </a>

        <!-- Widget 3: Pending Review Queue -->
        <a href="citizen-registry/pending-approvals.php" class="glass-panel glow-card-amber rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80 hover:shadow-md transition-all">
          <div class="space-y-1">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Pending Review Queue</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tight"><?php echo number_format($pendingV); ?></span>
              <span class="text-[10px] font-extrabold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                <i class="fa-solid fa-clock text-[9px]"></i> Action Req.
              </span>
            </div>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">Awaiting administrator verification</p>
          </div>
          <div class="h-11 w-11 rounded-xl bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 border border-amber-100/60 dark:border-amber-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-amber-500 group-hover:text-white">
            <i class="fa-solid fa-hourglass-half text-sm"></i>
          </div>
        </a>

        <!-- Widget 4: Active Citizen Engagements -->
        <a href="feedback-grievance/incoming-concerns.php" class="glass-panel glow-card-purple rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80 hover:shadow-md transition-all">
          <div class="space-y-1">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Community Concerns & 311</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">14 Active</span>
              <span class="text-[10px] font-extrabold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                <i class="fa-solid fa-comments text-[9px]"></i> 311 Feed
              </span>
            </div>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">Citizen complaints, feedback & inquiries</p>
          </div>
          <div class="h-11 w-11 rounded-xl bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 border border-purple-100/60 dark:border-purple-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-purple-600 group-hover:text-white">
            <i class="fa-solid fa-bullhorn text-sm"></i>
          </div>
        </a>

      </div>

      <!-- Advanced Analytics Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Area Chart Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Glowing Area Chart Panel -->
          <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
              <div>
                <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-chart-area text-brand-dark dark:text-brand-medium"></i> Citizen Registration & Engagement Trends
                </h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Monthly verification and citizen engagement activity logs across Caloocan City</p>
              </div>
              <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700 px-3 py-1.5 rounded-lg text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide shrink-0">
                <div class="flex items-center gap-1">
                  <span class="inline-block h-2 w-2 rounded-full bg-brand-dark dark:bg-brand-medium"></span> 
                  <span>Citizens Verified</span>
                </div>
                <div class="flex items-center gap-1">
                  <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span> 
                  <span>Engagement Actions</span>
                </div>
              </div>
            </div>
            <div class="relative h-72 w-full">
              <canvas id="trendsChart"></canvas>
            </div>
          </div>

          <!-- Live Citizen Engagement Activity Feed -->
          <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
              <div>
                <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-bolt text-amber-500"></i> Live Citizen Activity & Verification Feed
                </h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Real-time incoming submissions, verification updates & LGU records</p>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Stream
              </span>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
              <?php if (empty($recentEvents)): ?>
                <div class="py-6 text-center text-xs text-slate-400">No recent activity logged</div>
              <?php else: ?>
                <?php foreach ($recentEvents as $ev): 
                  $evName = htmlspecialchars(trim("{$ev['first_name']} {$ev['last_name']}"));
                  $evTime = htmlspecialchars(getRelativeTimeStr($ev['activity_time']));
                  $evStaff = htmlspecialchars(!empty($ev['reviewed_by']) ? $ev['reviewed_by'] : 'Admin');
                  $evLocation = htmlspecialchars("{$ev['barangay']}, {$ev['district']}");
                ?>
                  <div class="py-3.5 flex items-center justify-between gap-4 text-xs transition hover:bg-slate-50/50 dark:hover:bg-slate-850/50 px-2 rounded-lg -mx-2">
                    <div class="flex items-center gap-3 min-w-0">
                      <?php if ($ev['activity_type'] === 'approved'): ?>
                        <div class="h-8.5 w-8.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/30">
                          <i class="fa-solid fa-user-check text-xs"></i>
                        </div>
                        <div class="min-w-0 space-y-0.5">
                          <p class="font-bold text-slate-700 dark:text-slate-200 truncate text-[11px]">Identity Verification Approved</p>
                          <p class="text-[9px] text-slate-400 dark:text-slate-400 font-medium">Citizen: <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $evName; ?></span> &bull; Verified by: <?php echo $evStaff; ?></p>
                        </div>
                      <?php elseif ($ev['activity_type'] === 'rejected'): ?>
                        <div class="h-8.5 w-8.5 rounded-lg bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100 dark:border-rose-900/30">
                          <i class="fa-solid fa-user-xmark text-xs"></i>
                        </div>
                        <div class="min-w-0 space-y-0.5">
                          <p class="font-bold text-slate-700 dark:text-slate-200 truncate text-[11px]">Verification Needs Correction</p>
                          <p class="text-[9px] text-slate-400 dark:text-slate-400 font-medium">Citizen: <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $evName; ?></span> &bull; <?php echo $evLocation; ?></p>
                        </div>
                      <?php else: ?>
                        <div class="h-8.5 w-8.5 rounded-lg bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/30">
                          <i class="fa-solid fa-mobile-screen text-xs"></i>
                        </div>
                        <div class="min-w-0 space-y-0.5">
                          <p class="font-bold text-slate-700 dark:text-slate-200 truncate text-[11px]">New Verification via Citizen Mobile App</p>
                          <p class="text-[9px] text-slate-400 dark:text-slate-400 font-medium">Applicant: <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $evName; ?></span> &bull; <?php echo $evLocation; ?></p>
                        </div>
                      <?php endif; ?>
                    </div>
                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 shrink-0"><?php echo $evTime; ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Right Column (1/3 width) -->
        <div class="space-y-6">
          
          <!-- Doughnut Chart: Demographics -->
          <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
            <div>
              <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-brand-dark dark:text-brand-medium"></i> Citizen Demographics
              </h3>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Live demographic breakdown of registered residents</p>
            </div>
            <div class="relative h-48 w-full flex items-center justify-center">
              <canvas id="demographicsChart"></canvas>
            </div>
          </div>

          <!-- Radar Chart: Engagement by City Module -->
          <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
            <div>
              <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-brand-dark dark:text-brand-medium"></i> Engagement by Module
              </h3>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Activity workload ratios across the 5 Civentral modules</p>
            </div>
            <div class="relative h-56 w-full flex items-center justify-center">
              <canvas id="workloadRadarChart"></canvas>
            </div>
          </div>

          <!-- Citizen Service & Governance KPIs -->
          <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
            <div>
              <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight">Citizen Service & Governance KPIs</h3>
              <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Performance targets across citizen services</p>
            </div>
            <div class="space-y-4 pt-1">
              
              <!-- KPI 1 -->
              <div class="space-y-1.5">
                <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                  <span>KYC Review Turnaround (&lt; 48 hrs)</span>
                  <span class="text-brand-dark dark:text-brand-medium">92%</span>
                </div>
                <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-brand-medium to-brand-dark rounded-full" style="width: 92%"></div>
                </div>
              </div>
              
              <!-- KPI 2 -->
              <div class="space-y-1.5">
                <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                  <span>Grievance Resolution Compliance</span>
                  <span class="text-amber-600 dark:text-amber-400">88%</span>
                </div>
                <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full" style="width: 88%"></div>
                </div>
              </div>
              
              <!-- KPI 3 -->
              <div class="space-y-1.5">
                <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                  <span>Duplicate Record Prevention</span>
                  <span class="text-emerald-600 dark:text-emerald-400">99.4%</span>
                </div>
                <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full" style="width: 99.4%"></div>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </main>

    <script>
      window.dashboardAnalyticsData = {
        demographics: [
          <?php echo max(1, $youthCount); ?>,
          <?php echo max(1, $adultCount); ?>,
          <?php echo max(1, $seniorCount); ?>,
          <?php echo max(1, $soloParentCount); ?>
        ],
        demographicsLabels: ['Youth (<30)', 'Working Class (30-59)', 'Senior Citizens (60+)', 'Solo Parents'],
        radarLabels: ['Civil Registry & KYC', 'Public Grievance (311)', 'Barangay Certificates', 'Public Consultations', 'Community Broadcasts'],
        radarData: [88, 76, 92, 68, 75]
      };
    </script>

    <!-- Load ChartJS Library & External Analytics Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo $basePathResolver; ?>assets/js/dashboard-analytics.js"></script>
    
<?php include '../includes/footer.php'; ?>
