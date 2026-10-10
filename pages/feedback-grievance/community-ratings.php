<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Fetch Community Ratings & Aggregate Analytics
$avgOverall = 0.0;
$totalReviews = 0;
$avgQuality = 0.0;
$avgStaff = 0.0;
$positiveSentimentRate = "100.0%";
$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$ratingsList = [];
$serviceRatings = [];

try {
    $pdo = getDbConnection();

    // Auto-heal schema if missing on Dokploy
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `community_ratings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `feedback_ref` VARCHAR(50) UNIQUE NOT NULL,
                `citizen_name` VARCHAR(150) NOT NULL,
                `citizen_email` VARCHAR(150) NULL,
                `citizen_barangay` VARCHAR(100) NULL,
                `service_name` VARCHAR(150) NOT NULL,
                `transaction_ref` VARCHAR(100) NULL,
                `overall_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `quality_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `staff_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `comments` TEXT NULL,
                `sentiment` VARCHAR(30) NOT NULL DEFAULT 'Positive',
                `status` VARCHAR(30) NOT NULL DEFAULT 'Published',
                `attachment_url` VARCHAR(500) NULL,
                `admin_notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("ALTER TABLE `community_ratings` ADD COLUMN `attachment_url` VARCHAR(500) NULL;");
    } catch (Exception $e) {}

    // 1. Overall Aggregates
    $aggStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_reviews,
            COALESCE(AVG(overall_rating), 0) as avg_overall,
            COALESCE(AVG(quality_rating), 0) as avg_quality,
            COALESCE(AVG(staff_rating), 0) as avg_staff,
            SUM(CASE WHEN sentiment = 'Positive' THEN 1 ELSE 0 END) as positive_count
        FROM `community_ratings`
    ");
    $agg = $aggStmt->fetch(PDO::FETCH_ASSOC);
    if ($agg && $agg['total_reviews'] > 0) {
        $totalReviews = (int)$agg['total_reviews'];
        $avgOverall = round((float)$agg['avg_overall'], 1);
        $avgQuality = round((float)$agg['avg_quality'], 1);
        $avgStaff = round((float)$agg['avg_staff'], 1);
        $posCount = (int)$agg['positive_count'];
        $positiveSentimentRate = number_format(($posCount / $totalReviews) * 100, 1) . '%';
    }

    // 2. Star Distribution
    $starStmt = $pdo->query("
        SELECT overall_rating, COUNT(*) as count 
        FROM `community_ratings` 
        GROUP BY overall_rating
    ");
    while ($row = $starStmt->fetch(PDO::FETCH_ASSOC)) {
        $star = (int)$row['overall_rating'];
        if (isset($starCounts[$star])) {
            $starCounts[$star] = (int)$row['count'];
        }
    }

    // 3. Service Average Breakdown
    $svcStmt = $pdo->query("
        SELECT service_name, COUNT(*) as count, ROUND(AVG(overall_rating), 1) as avg_rating 
        FROM `community_ratings` 
        GROUP BY service_name 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $serviceRatings = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Detailed Reviews List
    $listStmt = $pdo->query("
        SELECT * FROM `community_ratings` 
        ORDER BY `created_at` DESC
    ");
    $ratingsList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Database error in community-ratings.php: " . $e->getMessage());
}

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
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Breadcrumb Header -->
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
        <span>Feedback & Grievance</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Community Ratings & Satisfaction</span>
    </div>

    <!-- Title & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-star text-amber-400"></i>
                <span>Citizen Community Ratings & Feedback</span>
            </h1>
            <p class="text-xs text-slate-500 font-medium">Real-time citizen service satisfaction ratings, staff courtesy scores, and qualitative feedback.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button onclick="window.print()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-400"></i>
                <span>Export Report</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards (5 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        
        <!-- Card 1: Overall Rating -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Overall Rating</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($avgOverall, 1); ?></h3>
                <span class="text-xs font-bold text-slate-400">/ 5.0</span>
            </div>
            <div class="flex items-center gap-1 text-amber-400 text-xs">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-solid fa-star <?php echo $i <= round($avgOverall) ? 'text-amber-400' : 'text-slate-200'; ?>"></i>
                <?php endfor; ?>
                <span class="text-[10px] text-slate-400 font-semibold ml-1">(<?php echo $totalReviews; ?> ratings)</span>
            </div>
        </div>

        <!-- Card 2: Total Reviews -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Reviews</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($totalReviews); ?></h3>
            <p class="text-[10px] text-slate-400 font-medium">Citizen feedback submissions</p>
        </div>

        <!-- Card 3: Staff Courtesy -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Staff Courtesy</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($avgStaff, 1); ?></h3>
                <span class="text-xs font-bold text-slate-400">/ 5.0</span>
            </div>
            <p class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>Personnel helpfulness score</span>
            </p>
        </div>

        <!-- Card 4: Service Quality -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Service Quality</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo number_format($avgQuality, 1); ?></h3>
                <span class="text-xs font-bold text-slate-400">/ 5.0</span>
            </div>
            <p class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                <i class="fa-solid fa-circle-check"></i>
                <span>Process efficiency score</span>
            </p>
        </div>

        <!-- Card 5: Positive Sentiment -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Positive Sentiment</span>
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-face-smile"></i>
                </div>
            </div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $positiveSentimentRate; ?></h3>
            <p class="text-[10px] text-slate-400 font-medium">Citizen satisfaction index</p>
        </div>

    </div>

    <!-- Analytics Breakdown Row (Star Distribution & Top Services) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Star Distribution (5 Cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Star Rating Breakdown</h3>
            <div class="space-y-2 pt-1">
                <?php for ($s = 5; $s >= 1; $s--): 
                    $cnt = $starCounts[$s] ?? 0;
                    $pct = $totalReviews > 0 ? ($cnt / $totalReviews) * 100 : 0;
                ?>
                <div class="flex items-center gap-3 text-xs">
                    <span class="w-12 font-bold text-slate-700 flex items-center gap-1">
                        <?php echo $s; ?> <i class="fa-solid fa-star text-amber-400 text-[10px]"></i>
                    </span>
                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-400 rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                    <span class="w-16 text-right text-[11px] font-bold text-slate-500"><?php echo $cnt; ?> (<?php echo round($pct); ?>%)</span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Top Services Rated (7 Cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Top Rated Municipal Services</h3>
            <div class="space-y-2.5 pt-1">
                <?php if (empty($serviceRatings)): ?>
                    <p class="text-xs text-slate-400 italic py-4 text-center">No service ratings recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($serviceRatings as $sr): ?>
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-xs font-bold">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($sr['service_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-[10px] text-slate-400 font-semibold"><?php echo $sr['count']; ?> reviews</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-black text-xs border border-amber-200 flex items-center gap-1">
                                <i class="fa-solid fa-star text-[9px] text-amber-500"></i>
                                <?php echo $sr['avg_rating']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Filters & Table Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Search Input (5 Cols) -->
            <div class="lg:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchInput" oninput="filterReviews()" placeholder="Search feedback by citizen, service, or comment..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
            </div>

            <!-- Star Rating Filter (3 Cols) -->
            <div class="lg:col-span-3">
                <select id="ratingFilter" onchange="filterReviews()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                    <option value="">All Ratings (1 - 5 Stars)</option>
                    <option value="5">5 Stars ★★★★★</option>
                    <option value="4">4 Stars ★★★★☆</option>
                    <option value="3">3 Stars ★★★☆☆</option>
                    <option value="2">2 Stars ★★☆☆☆</option>
                    <option value="1">1 Star ★☆☆☆☆</option>
                </select>
            </div>

            <!-- Sentiment Filter (2 Cols) -->
            <div class="lg:col-span-2">
                <select id="sentimentFilter" onchange="filterReviews()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                    <option value="">All Sentiments</option>
                    <option value="Positive">Positive</option>
                    <option value="Neutral">Neutral</option>
                    <option value="Negative">Negative</option>
                </select>
            </div>

            <!-- Reset Filter (2 Cols) -->
            <div class="lg:col-span-2">
                <button type="button" onclick="resetFilters()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate text-slate-400"></i>
                    <span>Reset</span>
                </button>
            </div>

        </div>
    </div>

    <!-- Ratings Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[750px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Citizen / Reference</th>
                        <th class="py-3.5 px-3">Service Rated</th>
                        <th class="py-3.5 px-3 text-center">Overall</th>
                        <th class="py-3.5 px-3 text-center">Quality / Staff</th>
                        <th class="py-3.5 px-3">Citizen Comments</th>
                        <th class="py-3.5 px-3 text-center">Sentiment</th>
                        <th class="py-3.5 px-3">Date Submitted</th>
                    </tr>
                </thead>
                <tbody id="reviewsTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($ratingsList)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"></i>
                            <span class="font-bold text-slate-600 block text-sm">No community feedback yet</span>
                            <span class="text-xs">Citizen reviews submitted via the mobile app will be displayed here in real time.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ratingsList as $r): 
                        $overall = (int)$r['overall_rating'];
                        $quality = (int)$r['quality_rating'];
                        $staff = (int)$r['staff_rating'];
                        $sentiment = $r['sentiment'] ?: 'Positive';
                        
                        $sentimentClass = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                        if ($sentiment === 'Neutral') $sentimentClass = 'bg-blue-50 text-[#0f53d1] border-blue-200';
                        if ($sentiment === 'Negative') $sentimentClass = 'bg-rose-50 text-rose-600 border-rose-200';
                    ?>
                    <tr class="review-row hover:bg-slate-50/80 transition" data-rating="<?php echo $overall; ?>" data-sentiment="<?php echo htmlspecialchars($sentiment); ?>">
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            <div class="truncate text-xs font-bold text-slate-900"><?php echo htmlspecialchars($r['citizen_name']); ?></div>
                            <div class="text-[10px] text-slate-400 font-semibold truncate mt-0.5">Ref: <?php echo htmlspecialchars($r['feedback_ref']); ?></div>
                        </td>
                        <td class="py-3.5 px-3 font-semibold text-slate-700 text-xs">
                            <span class="truncate block max-w-xs"><?php echo htmlspecialchars($r['service_name']); ?></span>
                            <?php if (!empty($r['transaction_ref'])): ?>
                            <span class="text-[10px] text-slate-400 font-medium">Tx: <?php echo htmlspecialchars($r['transaction_ref']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-black text-xs border border-amber-200 inline-flex items-center gap-1">
                                <i class="fa-solid fa-star text-[9px] text-amber-500"></i>
                                <?php echo $overall; ?>.0
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <div class="inline-flex items-center gap-1.5 text-[11px] font-bold">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" title="Quality Score">Q: <?php echo $quality; ?>★</span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" title="Staff Courtesy Score">S: <?php echo $staff; ?>★</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-3 max-w-xs">
                            <p class="text-xs text-slate-600 font-medium line-clamp-2 leading-relaxed">
                                <?php echo !empty($r['comments']) ? htmlspecialchars($r['comments']) : '<span class="text-slate-400 italic">No written comment</span>'; ?>
                            </p>
                            <?php if (!empty($r['attachment_url'])): 
                                $attSrc = strpos($r['attachment_url'], 'http') === 0 ? $r['attachment_url'] : ('../../' . ltrim($r['attachment_url'], '/'));
                                if (strpos($attSrc, 'citizenship.civentral.tech') !== false) {
                                    $attSrc = str_replace('citizenship.civentral.tech', 'api-citizen.civentral.tech', $attSrc);
                                }
                            ?>
                            <div class="mt-1">
                                <a href="<?php echo htmlspecialchars($attSrc); ?>" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-blue-600 hover:text-blue-800 font-bold bg-blue-50 border border-blue-200/60 px-2 py-0.5 rounded-md">
                                    <i class="fa-solid fa-paperclip text-[9px]"></i> View Attachment
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo $sentimentClass; ?>">
                                <?php echo htmlspecialchars($sentiment); ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-slate-500 text-[11px] whitespace-nowrap">
                            <?php echo date('M j, Y • g:i A', strtotime($r['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="px-4 py-3 bg-slate-50/50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
            <span>Showing <?php echo count($ratingsList); ?> citizen reviews</span>
            <div id="ratingsPaginationControls" class="flex items-center gap-1.5">
                <!-- Dynamic ratings pages -->
            </div>
        </div>
    </div>

</main>

<script>
let currentRatingsPage = 1;
const ratingsPageSize = 10;

function filterReviews() {
    currentRatingsPage = 1;
    renderRatingsPagination();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('ratingFilter').value = '';
    document.getElementById('sentimentFilter').value = '';
    currentRatingsPage = 1;
    renderRatingsPagination();
}

// Client-Side Filtering & Pagination for Community Ratings
function renderRatingsPagination() {
    const searchVal = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const ratingVal = document.getElementById('ratingFilter')?.value || '';
    const sentimentVal = (document.getElementById('sentimentFilter')?.value || '').toLowerCase();

    const allRows = Array.from(document.querySelectorAll('#reviewsTableBody tr.review-row'));
    
    // 1. Filter rows matching criteria
    const matchingRows = allRows.filter(r => {
        const text = r.innerText.toLowerCase();
        const rating = r.getAttribute('data-rating') || '';
        const sentiment = (r.getAttribute('data-sentiment') || '').toLowerCase();

        const matchSearch = !searchVal || text.includes(searchVal);
        const matchRating = !ratingVal || rating === ratingVal;
        const matchSentiment = !sentimentVal || sentiment === sentimentVal;

        return matchSearch && matchRating && matchSentiment;
    });

    // 2. Hide non-matching rows
    allRows.forEach(r => {
        if (!matchingRows.includes(r)) {
            r.style.display = 'none';
        }
    });

    const totalItems = matchingRows.length;
    const totalPages = Math.ceil(totalItems / ratingsPageSize) || 1;
    if (currentRatingsPage > totalPages) currentRatingsPage = totalPages;
    if (currentRatingsPage < 1) currentRatingsPage = 1;

    const startIdx = (currentRatingsPage - 1) * ratingsPageSize;
    const endIdx = startIdx + ratingsPageSize;

    matchingRows.forEach((r, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });

    const container = document.getElementById('ratingsPaginationControls');
    if (!container) return;

    if (totalItems <= ratingsPageSize) {
        container.innerHTML = `<span class="text-[11px] text-slate-400 font-semibold">Page 1 of 1 (${totalItems} total)</span>`;
        return;
    }

    let html = '';
    const prevDisabled = currentRatingsPage === 1 ? 'disabled opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-100';
    html += `<button onclick="goToRatingsPage(${currentRatingsPage - 1})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 text-xs ${prevDisabled}"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === currentRatingsPage) {
            html += `<button class="w-7 h-7 rounded-lg bg-[#0f53d1] text-white font-bold flex items-center justify-center shadow-xs text-xs">${p}</button>`;
        } else {
            html += `<button onclick="goToRatingsPage(${p})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs cursor-pointer flex items-center justify-center">${p}</button>`;
        }
    }

    const nextDisabled = currentRatingsPage === totalPages ? 'disabled opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-100';
    html += `<button onclick="goToRatingsPage(${currentRatingsPage + 1})" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 text-xs ${nextDisabled}"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>`;

    container.innerHTML = html;
}

function goToRatingsPage(page) {
    currentRatingsPage = page;
    renderRatingsPagination();
}

document.addEventListener('DOMContentLoaded', function() {
    renderRatingsPagination();
});

</script>

<?php include '../../includes/footer.php'; ?>
