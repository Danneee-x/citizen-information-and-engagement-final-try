<?php
/**
 * CIVentral Municipal Concern Incident Clustering & Auto-Escalation Engine
 * 
 * Automatically detects multiple reports converging on the same incident:
 * - Same Barangay
 * - Same Place / Street / Landmark / Location
 * - Same Concern Category / Topic
 * 
 * When multiple citizen reports converge (>= 2 reports), dynamically links
 * the reports into an Incident Cluster and escalates priority to 'Urgent'.
 */

if (!function_exists('normalizeBarangay')) {
    function normalizeBarangay(?string $brgy): string {
        if (empty($brgy)) return '';
        $s = mb_strtolower(trim($brgy), 'UTF-8');
        // Remove prefixes
        $s = preg_replace('/^(barangay|brgy\.?|bgy\.?)\s*/i', '', $s);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        return $s;
    }
}

if (!function_exists('normalizeLocation')) {
    function normalizeLocation(?string $loc): array {
        if (empty($loc)) return ['raw' => '', 'phrase' => '', 'tokens' => []];
        $s = mb_strtolower(trim($loc), 'UTF-8');
        
        // Remove common Filipino / English prepositions and directional fillers
        $replacePatterns = [
            '/\b(kanto\s+ng|corner)\b/i' => 'cor',
            '/\b(tapat\s+ng|in\s+front\s+of|harap\s+ng)\b/i' => 'front',
            '/\b(banda\s+dito\s+sa|malapit\s+sa|near|around)\b/i' => '',
            '/\b(street|st\.)\b/i' => 'street',
            '/\b(avenue|ave\.)\b/i' => 'avenue',
            '/\b(road|rd\.)\b/i' => 'road',
            '/\b(caloocan|city|metro\s+manila)\b/i' => '',
            '/\b(sa|ng|ang|mga|dito|may|na)\b/i' => ''
        ];
        foreach ($replacePatterns as $pat => $rep) {
            $s = preg_replace($pat, $rep, $s);
        }

        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        $clean = preg_replace('/\s+/', ' ', trim($clean));
        
        $rawWords = explode(' ', $clean);
        $stopwords = ['cor', 'front', 'st', 'rd', 'ave', 'street', 'road', 'avenue', 'phase', 'block', 'lot', 'ext'];
        $tokens = [];
        foreach ($rawWords as $w) {
            if (strlen($w) >= 3 && !in_array($w, $stopwords)) {
                $tokens[] = $w;
            }
        }
        $tokens = array_values(array_unique($tokens));

        return [
            'raw' => $loc,
            'phrase' => $clean,
            'tokens' => $tokens
        ];
    }
}

if (!function_exists('detectIncidentTopic')) {
    function detectIncidentTopic(?string $cat, ?string $title = '', ?string $desc = ''): string {
        $text = mb_strtolower(trim(($cat ?? '') . ' ' . ($title ?? '') . ' ' . ($desc ?? '')), 'UTF-8');

        if (preg_match('/(streetlight|ilaw|lamp\s*post|dilim|poste|wire|sparking|bulb|electrical)/i', $text)) {
            return 'streetlight';
        }
        if (preg_match('/(pothole|lubak|kalsada|asphalt|crater|road\s*damage|crack|sidewalk|pavement|infrastructure)/i', $text)) {
            return 'road_infrastructure';
        }
        if (preg_match('/(flood|baha|drain|canal|kanal|barado|clogged|waterlog|culvert|gutter)/i', $text)) {
            return 'flood_drainage';
        }
        if (preg_match('/(garbage|basura|trash|dump|waste|odor|amoy|tapon|uncollected|sanitation)/i', $text)) {
            return 'garbage_sanitation';
        }
        if (preg_match('/(traffic|congestion|tricycle|parking|harang|obstruction|mobility)/i', $text)) {
            return 'traffic_mobility';
        }
        if (preg_match('/(noise|ingay|away|brawl|crime|hazard|peace|safety|curfew|videoke)/i', $text)) {
            return 'public_safety';
        }
        if (preg_match('/(pipe|tubig|leak|maynilad|water\s*supply)/i', $text)) {
            return 'water_utilities';
        }

        // Fallback to normalized category if available
        $c = mb_strtolower(trim($cat ?? ''), 'UTF-8');
        $c = preg_replace('/[^a-z0-9]/', '', $c);
        return !empty($c) ? $c : 'general';
    }
}

if (!function_exists('areConcernsSameIncident')) {
    function areConcernsSameIncident(array $a, array $b): bool {
        // 1. Check Barangay
        $brgyA = normalizeBarangay($a['barangay'] ?? '');
        $brgyB = normalizeBarangay($b['barangay'] ?? '');

        $brgyMatches = false;
        if (empty($brgyA) || empty($brgyB)) {
            // If one is unspecified, accept if locations match strongly
            $brgyMatches = true;
        } else if ($brgyA === $brgyB) {
            $brgyMatches = true;
        } else {
            // Check if one contains the other (e.g. "171" and "171 bagumbong")
            if (strpos($brgyA, $brgyB) !== false || strpos($brgyB, $brgyA) !== false) {
                $brgyMatches = true;
            } else {
                // Check if numeric barangay IDs match (e.g. "171")
                preg_match('/\d+/', $brgyA, $mA);
                preg_match('/\d+/', $brgyB, $mB);
                if (!empty($mA[0]) && !empty($mB[0]) && $mA[0] === $mB[0]) {
                    $brgyMatches = true;
                }
            }
        }

        if (!$brgyMatches) {
            return false;
        }

        // 2. Check Incident Topic
        $topicA = detectIncidentTopic($a['category'] ?? '', $a['title'] ?? '', $a['description'] ?? '');
        $topicB = detectIncidentTopic($b['category'] ?? '', $b['title'] ?? '', $b['description'] ?? '');

        if ($topicA !== $topicB) {
            return false;
        }

        // 3. Check Location / Place
        $locA = normalizeLocation($a['location'] ?? '');
        $locB = normalizeLocation($b['location'] ?? '');

        // Also check if description or title mentions specific street or landmark
        $descA = mb_strtolower(($a['description'] ?? '') . ' ' . ($a['title'] ?? ''), 'UTF-8');
        $descB = mb_strtolower(($b['description'] ?? '') . ' ' . ($b['title'] ?? ''), 'UTF-8');

        // Direct normalized phrase match
        if (!empty($locA['phrase']) && !empty($locB['phrase']) && $locA['phrase'] === $locB['phrase']) {
            return true;
        }

        // Token intersection check (e.g. "sampaguita", "kamuning", "camarin", "kasunduan", "zamora", "monumento")
        if (!empty($locA['tokens']) && !empty($locB['tokens'])) {
            $intersect = array_intersect($locA['tokens'], $locB['tokens']);
            if (count($intersect) >= 1) {
                return true;
            }
        }

        // Check if token from locA is mentioned in descB, or token from locB mentioned in descA
        foreach ($locA['tokens'] as $tok) {
            if (strlen($tok) >= 4 && strpos($descB, $tok) !== false) {
                return true;
            }
        }
        foreach ($locB['tokens'] as $tok) {
            if (strlen($tok) >= 4 && strpos($descA, $tok) !== false) {
                return true;
            }
        }

        // String similarity ratio
        if (!empty($locA['phrase']) && !empty($locB['phrase'])) {
            similar_text($locA['phrase'], $locB['phrase'], $pct);
            if ($pct >= 65.0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('clusterConcerns')) {
    /**
     * Process an array of concerns, identify duplicate/multi-report clusters,
     * and escalate clusters with >= 2 reports to Urgent priority.
     * 
     * @param array $concerns List of concern associative arrays
     * @param PDO|null $pdo Database connection (optional, for auto-persisting priority escalation)
     * @param bool $persistDb Whether to persist escalated Urgent priority to MySQL
     * @return array Enriched concerns array
     */
    function clusterConcerns(array $concerns, ?PDO $pdo = null, bool $persistDb = true): array {
        $n = count($concerns);
        if ($n === 0) return [];

        // Disjoint-set data structure for incident clustering
        $parent = range(0, $n - 1);
        $find = function($i) use (&$parent, &$find) {
            if ($parent[$i] === $i) return $i;
            $parent[$i] = $find($parent[$i]);
            return $parent[$i];
        };
        $union = function($i, $j) use (&$parent, &$find) {
            $rootI = $find($i);
            $rootJ = $find($j);
            if ($rootI !== $rootJ) {
                $parent[$rootI] = $rootJ;
            }
        };

        // Compare all pairs
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if (areConcernsSameIncident($concerns[$i], $concerns[$j])) {
                    $union($i, $j);
                }
            }
        }

        // Group indices by root cluster
        $clusters = [];
        for ($i = 0; $i < $n; $i++) {
            $root = $find($i);
            $clusters[$root][] = $i;
        }

        $ticketsToEscalate = [];

        // Annotate each concern with cluster intelligence
        foreach ($clusters as $rootIndex => $indices) {
            $clusterSize = count($indices);
            $isMultiReport = $clusterSize >= 2;

            // Generate representative incident metadata from cluster
            $repItem = $concerns[$rootIndex];
            $brgyLabel = !empty($repItem['barangay']) ? $repItem['barangay'] : 'Caloocan';
            $locLabel = !empty($repItem['location']) ? $repItem['location'] : 'Local Jurisdiction';
            $topic = detectIncidentTopic($repItem['category'] ?? '', $repItem['title'] ?? '', $repItem['description'] ?? '');
            
            $clusterSlug = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', "INC-{$brgyLabel}-{$locLabel}-{$topic}"));
            $clusterSlug = trim($clusterSlug, '-');

            $allTicketIds = [];
            foreach ($indices as $idx) {
                $tId = $concerns[$idx]['ticket_number'] ?? ($concerns[$idx]['id'] ?? ('TCK-' . $idx));
                $allTicketIds[] = $tId;
            }

            foreach ($indices as $idx) {
                $item = &$concerns[$idx];
                $thisTicketId = $item['ticket_number'] ?? ($item['id'] ?? ('TCK-' . $idx));
                
                // Siblings are other tickets in the same cluster
                $siblings = array_values(array_filter($allTicketIds, function($tid) use ($thisTicketId) {
                    return $tid !== $thisTicketId;
                }));

                $siblingDetails = [];
                foreach ($indices as $sIdx) {
                    if ($sIdx === $idx) continue;
                    $sItem = $concerns[$sIdx];
                    $siblingDetails[] = [
                        'ticket_number' => $sItem['ticket_number'] ?? ($sItem['id'] ?? ''),
                        'title' => $sItem['title'] ?? '',
                        'citizen_name' => (!empty($sItem['is_anonymous']) ? 'Anonymous Resident' : ($sItem['citizen_name'] ?? 'Citizen Resident')),
                        'date_filed' => !empty($sItem['created_at']) ? date('M d, Y • h:i A', strtotime($sItem['created_at'])) : ($sItem['date_filed'] ?? 'Recently'),
                        'status' => $sItem['status'] ?? 'New'
                    ];
                }

                $originalPriority = $item['priority'] ?? 'Medium';

                if ($isMultiReport) {
                    $item['is_cluster'] = true;
                    $item['cluster_count'] = $clusterSize;
                    $item['cluster_id'] = $clusterSlug;
                    $item['cluster_title'] = "Incident Hotspot: {$locLabel}, {$brgyLabel}";
                    $item['sibling_tickets'] = $siblings;
                    $item['sibling_details'] = $siblingDetails;
                    $item['has_duplicate'] = true;
                    $item['duplicate_text'] = "Linked to {$clusterSize} reports for identical incident";
                    
                    // AUTO-ESCALATE TO URGENT
                    $item['effective_priority'] = 'Urgent';
                    $item['priority'] = 'Urgent'; // Dynamically override for all visual badge and SLA checks
                    $item['is_escalated_urgent'] = ($originalPriority !== 'Urgent');
                    $item['cluster_escalation_reason'] = "⚡ Multi-Report Hotspot Detected: {$clusterSize} citizens filed identical reports for this incident in {$brgyLabel} ({$locLabel}). Priority automatically elevated to Urgent for immediate municipal response.";
                    
                    // Track for database update if not already Urgent
                    if ($originalPriority !== 'Urgent' && !empty($item['ticket_number'])) {
                        $ticketsToEscalate[] = $item['ticket_number'];
                    }
                } else {
                    $item['is_cluster'] = false;
                    $item['cluster_count'] = 1;
                    $item['cluster_id'] = null;
                    $item['sibling_tickets'] = [];
                    $item['sibling_details'] = [];
                    $item['has_duplicate'] = false;
                    $item['duplicate_text'] = '';
                    $item['effective_priority'] = $originalPriority;
                    $item['is_escalated_urgent'] = false;
                    $item['cluster_escalation_reason'] = '';
                }
            }
            unset($item);
        }

        // Persist priority escalations in MySQL if PDO is provided
        if ($persistDb && $pdo !== null && !empty($ticketsToEscalate)) {
            try {
                $ticketsToEscalate = array_unique($ticketsToEscalate);
                $placeholders = implode(',', array_fill(0, count($ticketsToEscalate), '?'));
                $stmt = $pdo->prepare("UPDATE `citizen_concerns` SET `priority` = 'Urgent' WHERE `ticket_number` IN ($placeholders) AND `priority` != 'Urgent'");
                $stmt->execute(array_values($ticketsToEscalate));
            } catch (Throwable $e) {
                // Silently handle if table locked or read-only
            }
        }

        return $concerns;
    }
}
