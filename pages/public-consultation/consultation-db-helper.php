<?php
/**
 * Civentral Public Consultation & Survey Database Helper
 * Handles CRUD, Real-Time Question Aggregation, Seeding from Mobile App Dataset, and Outcome Publishing.
 */

require_once __DIR__ . '/../../config/database.php';

class ConsultationDB {
    private static function getPdo(): PDO {
        return Database::getInstance()->getPdo();
    }

    /**
     * Ensure database tables exist (auto-migration fallback)
     */
    public static function ensureTables(): void {
        $pdo = self::getPdo();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `public_surveys` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `survey_code` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `short_description` TEXT NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `estimated_time` VARCHAR(50) NOT NULL DEFAULT '3 mins',
                `target_audience` VARCHAR(100) NOT NULL DEFAULT 'All Residents',
                `sub_target` VARCHAR(150) NULL,
                `open_date` DATE NOT NULL,
                `close_date` DATE NOT NULL,
                `privacy_setting` ENUM('Identified', 'Anonymous') NOT NULL DEFAULT 'Identified',
                `is_public_results` TINYINT(1) NOT NULL DEFAULT 1,
                `status` ENUM('Draft', 'Open', 'Closing Soon', 'Completed', 'Closed') NOT NULL DEFAULT 'Open',
                `created_by` VARCHAR(150) NOT NULL DEFAULT 'City Administration',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_survey_status` (`status`),
                INDEX `idx_survey_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `survey_questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `survey_id` INT NOT NULL,
                `question_order` INT NOT NULL DEFAULT 1,
                `title` TEXT NOT NULL,
                `question_type` ENUM(
                    'multiple_choice',
                    'multiple_selection',
                    'yes_no',
                    'rating_scale',
                    'likert_scale',
                    'short_answer',
                    'long_answer'
                ) NOT NULL DEFAULT 'multiple_choice',
                `options_json` JSON NULL,
                `is_required` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_survey_id` (`survey_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `survey_responses` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `survey_id` INT NOT NULL,
                `citizen_id` INT NULL,
                `citizen_name` VARCHAR(150) NOT NULL DEFAULT 'Anonymous Citizen',
                `barangay` VARCHAR(100) NULL,
                `age_group` VARCHAR(50) NULL,
                `gender` VARCHAR(30) NULL,
                `answers_json` JSON NOT NULL,
                `overall_rating` DECIMAL(3,2) NULL,
                `commentary` TEXT NULL,
                `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_response_survey` (`survey_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `civic_consultations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `consultation_code` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `background_info` TEXT NOT NULL,
                `objective` TEXT NOT NULL,
                `closing_date` DATE NOT NULL,
                `status` ENUM('Open', 'Closing Soon', 'Under Review', 'Closed / Outcome Published') NOT NULL DEFAULT 'Open',
                `created_by` VARCHAR(150) NOT NULL DEFAULT 'City Legal & Policy Board',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_consultation_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `consultation_feedbacks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `consultation_id` INT NOT NULL,
                `citizen_id` INT NULL,
                `citizen_name` VARCHAR(150) NOT NULL DEFAULT 'Caloocan Resident',
                `barangay` VARCHAR(100) NULL,
                `stance` ENUM('In Favor', 'Neutral', 'Against', 'Suggested Amendments') NOT NULL DEFAULT 'In Favor',
                `commentary` TEXT NOT NULL,
                `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_feedback_consultation` (`consultation_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `consultation_outcomes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `consultation_id` INT NOT NULL UNIQUE,
                `key_findings` TEXT NOT NULL,
                `policy_outcome` TEXT NOT NULL,
                `ordinance_number` VARCHAR(100) NULL,
                `allocated_budget` VARCHAR(100) NULL,
                `transparency_score` VARCHAR(50) NOT NULL DEFAULT '100% Published & Verified',
                `pdf_filename` VARCHAR(255) NULL,
                `published_by` VARCHAR(150) NOT NULL DEFAULT 'City Council Secretariat',
                `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Get all surveys with question count and response count
     */
    public static function getAllSurveys(): array {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->query("
            SELECT s.*, 
                   COUNT(DISTINCT q.id) AS question_count,
                   COUNT(DISTINCT r.id) AS response_count,
                   AVG(r.overall_rating) AS avg_rating
            FROM `public_surveys` s
            LEFT JOIN `survey_questions` q ON q.survey_id = s.id
            LEFT JOIN `survey_responses` r ON r.survey_id = s.id
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ");
        $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch questions for each survey
        foreach ($surveys as &$srv) {
            $qStmt = $pdo->prepare("SELECT * FROM `survey_questions` WHERE survey_id = ? ORDER BY question_order ASC");
            $qStmt->execute([$srv['id']]);
            $srv['questions'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($srv['questions'] as &$q) {
                if (!empty($q['options_json'])) {
                    $q['options'] = json_decode($q['options_json'], true) ?: [];
                } else {
                    $q['options'] = [];
                }
            }
        }
        return $surveys;
    }

    /**
     * Get single survey by ID or Code
     */
    public static function getSurveyById($idOrCode): ?array {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM `public_surveys` WHERE id = ? OR survey_code = ? LIMIT 1");
        $stmt->execute([$idOrCode, $idOrCode]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$survey) return null;

        $qStmt = $pdo->prepare("SELECT * FROM `survey_questions` WHERE survey_id = ? ORDER BY question_order ASC");
        $qStmt->execute([$survey['id']]);
        $survey['questions'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($survey['questions'] as &$q) {
            $q['options'] = !empty($q['options_json']) ? (json_decode($q['options_json'], true) ?: []) : [];
        }

        $rStmt = $pdo->prepare("SELECT COUNT(*) AS total_responses, AVG(overall_rating) AS avg_rating FROM `survey_responses` WHERE survey_id = ?");
        $rStmt->execute([$survey['id']]);
        $rMeta = $rStmt->fetch(PDO::FETCH_ASSOC);
        $survey['response_count'] = (int)($rMeta['total_responses'] ?? 0);
        $survey['avg_rating'] = $rMeta['avg_rating'] ? round((float)$rMeta['avg_rating'], 1) : null;

        return $survey;
    }

    /**
     * Create a new survey with questions
     */
    public static function createSurvey(array $data, array $questions): int {
        self::ensureTables();
        $pdo = self::getPdo();
        $pdo->beginTransaction();
        try {
            $surveyCode = $data['survey_code'] ?? ('SRV-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT));
            
            $stmt = $pdo->prepare("
                INSERT INTO `public_surveys` 
                (`survey_code`, `title`, `short_description`, `category`, `estimated_time`, `target_audience`, `sub_target`, `open_date`, `close_date`, `privacy_setting`, `is_public_results`, `status`, `created_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $surveyCode,
                $data['title'],
                $data['short_description'] ?? '',
                $data['category'] ?? 'General Consultation',
                $data['estimated_time'] ?? '3 mins',
                $data['target_audience'] ?? 'All Residents',
                $data['sub_target'] ?? null,
                $data['open_date'] ?? date('Y-m-d'),
                $data['close_date'] ?? date('Y-m-d', strtotime('+30 days')),
                $data['privacy_setting'] ?? 'Identified',
                isset($data['is_public_results']) ? (int)$data['is_public_results'] : 1,
                $data['status'] ?? 'Open',
                $data['created_by'] ?? 'City Administration'
            ]);
            $surveyId = (int)$pdo->lastInsertId();

            // Insert questions
            $qStmt = $pdo->prepare("
                INSERT INTO `survey_questions` (`survey_id`, `question_order`, `title`, `question_type`, `options_json`, `is_required`)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $order = 1;
            foreach ($questions as $q) {
                $optionsJson = !empty($q['options']) ? json_encode(array_values($q['options'])) : null;
                $qStmt->execute([
                    $surveyId,
                    $order++,
                    $q['title'],
                    $q['question_type'],
                    $optionsJson,
                    isset($q['is_required']) ? (int)$q['is_required'] : 1
                ]);
            }

            $pdo->commit();
            return $surveyId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete survey
     */
    public static function deleteSurvey(int $id): bool {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("DELETE FROM `public_surveys` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Update survey status
     */
    public static function updateSurveyStatus(int $id, string $status): bool {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("UPDATE `public_surveys` SET `status` = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Clear all surveys and responses
     */
    public static function clearAllSurveys(): void {
        self::ensureTables();
        $pdo = self::getPdo();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `survey_responses`;");
        $pdo->exec("TRUNCATE TABLE `survey_questions`;");
        $pdo->exec("TRUNCATE TABLE `public_surveys`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * Seed official Mobile App Surveys into database (matching app/public-surveys/index.tsx exactly)
     */
    public static function seedAppDefaultSurveys(): void {
        self::ensureTables();
        $pdo = self::getPdo();

        // 1. Caloocan Urban Mobility & Bike Lane Expansion 2026
        $check = $pdo->query("SELECT id FROM `public_surveys` WHERE survey_code = 'SRV-2026-001'")->fetch();
        if (!$check) {
            $srv1Id = self::createSurvey([
                'survey_code' => 'SRV-2026-001',
                'title' => 'Caloocan Urban Mobility & Bike Lane Expansion 2026',
                'short_description' => 'Share your feedback on dedicated bike corridors, pedestrian walkability, and UV express terminal relocation along Samson Road & Monumento.',
                'category' => 'Urban Mobility & Transport',
                'estimated_time' => '4 mins',
                'target_audience' => 'All Residents',
                'sub_target' => 'Districts 1, 2, and 3',
                'open_date' => date('Y-m-d', strtotime('-10 days')),
                'close_date' => '2026-08-31',
                'privacy_setting' => 'Identified',
                'is_public_results' => 1,
                'status' => 'Open',
                'created_by' => 'Caloocan Transport & Traffic Management Board'
            ], [
                [
                    'title' => 'How often do you commute or travel within Caloocan City using active transport (walking, cycling, e-scooter)?',
                    'question_type' => 'multiple_choice',
                    'options' => ['Daily', '3-4 times a week', 'Once a week', 'Rarely / Never'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Which arterial routes do you think need immediate protected bike lane barriers?',
                    'question_type' => 'multiple_selection',
                    'options' => ['Samson Road (Monumento to Malabon boundary)', 'Camarin Road (North Caloocan)', 'Bagumbong - Bignay Access Road', 'Rizal Avenue Extension'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Do you support relocating street-side tricycle queues into designated off-street multi-modal bays?',
                    'question_type' => 'yes_no',
                    'options' => ['Yes', 'No'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Rate the overall pedestrian safety and streetlighting of your primary commute route:',
                    'question_type' => 'rating_scale',
                    'options' => ['1 Star - Very Poor', '2 Stars - Poor', '3 Stars - Fair', '4 Stars - Good', '5 Stars - Excellent'],
                    'is_required' => 1
                ],
                [
                    'title' => '“The current pedestrian overpasses in Caloocan are well-maintained, accessible for PWDs, and safe at night.”',
                    'question_type' => 'likert_scale',
                    'options' => ['Strongly Disagree', 'Disagree', 'Neutral / Undecided', 'Agree', 'Strongly Agree'],
                    'is_required' => 1
                ],
                [
                    'title' => 'What is your primary Barangay or terminal checkpoint during rush hour?',
                    'question_type' => 'short_answer',
                    'options' => [],
                    'is_required' => 0
                ],
                [
                    'title' => 'What specific mobility improvement would make the biggest positive difference for your daily travel in Caloocan?',
                    'question_type' => 'long_answer',
                    'options' => [],
                    'is_required' => 0
                ]
            ]);

            // Add sample realistic responses
            self::seedSampleResponses($srv1Id);
        }

        // 2. Community Waste Segregation & Green Energy Initiative
        $check2 = $pdo->query("SELECT id FROM `public_surveys` WHERE survey_code = 'SRV-2026-002'")->fetch();
        if (!$check2) {
            $srv2Id = self::createSurvey([
                'survey_code' => 'SRV-2026-002',
                'title' => 'Community Waste Segregation & Green Energy Initiative',
                'short_description' => 'Evaluation of household garbage collection schedules, barangay composting hubs, and solar streetlighting rollout across Districts 1, 2, and 3.',
                'category' => 'Environment & Sanitation',
                'estimated_time' => '3 mins',
                'target_audience' => 'All Residents',
                'sub_target' => 'Barangay 1 to 188',
                'open_date' => date('Y-m-d', strtotime('-5 days')),
                'close_date' => '2026-09-15',
                'privacy_setting' => 'Identified',
                'is_public_results' => 1,
                'status' => 'Closing Soon',
                'created_by' => 'Environmental Management Dept (EMD)'
            ], [
                [
                    'title' => 'Does your household actively practice biodegradable vs non-biodegradable waste separation?',
                    'question_type' => 'yes_no',
                    'options' => ['Yes', 'No'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Which environmental programs would you like Caloocan to prioritize?',
                    'question_type' => 'multiple_selection',
                    'options' => ['Barangay Materials Recovery Facilities (MRF)', 'River Cleanups & Trash Traps', 'Community Solar Streetlights', 'Plastic-for-Rice Incentive Programs'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Rate the promptness of garbage collection trucks in your neighborhood:',
                    'question_type' => 'rating_scale',
                    'options' => ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                    'is_required' => 1
                ],
                [
                    'title' => 'Provide any suggestions to eliminate illegal dumping spots in your area:',
                    'question_type' => 'long_answer',
                    'options' => [],
                    'is_required' => 0
                ]
            ]);
        }
    }

    /**
     * Seed sample realistic responses for a survey
     */
    private static function seedSampleResponses(int $surveyId): void {
        $pdo = self::getPdo();
        $samples = [
            [
                'citizen_name' => 'Maria Santos',
                'barangay' => 'Barangay 178 (Camarin)',
                'age_group' => 'Adults (30 - 49 yrs)',
                'gender' => 'Female',
                'overall_rating' => 4.0,
                'commentary' => 'Relocating tricycles to an off-street terminal near Samson Road will immediately ease bottlenecks during morning rush hour.',
                'answers' => [
                    'q1' => 'Daily',
                    'q2' => ['Samson Road (Monumento to Malabon boundary)', 'Rizal Avenue Extension'],
                    'q3' => 'Yes',
                    'q4' => 4,
                    'q5' => 'Agree',
                    'q6' => 'Monumento Circle',
                    'q7' => 'Relocating tricycles to an off-street terminal near Samson Road will immediately ease bottlenecks during morning rush hour.'
                ]
            ],
            [
                'citizen_name' => 'Karlo Mendoza',
                'barangay' => 'Barangay 176 (Bagong Silang)',
                'age_group' => 'Youth (18 - 29 yrs)',
                'gender' => 'Male',
                'overall_rating' => 4.5,
                'commentary' => 'Protected physical bollards for bikes along Samson road are urgently needed. White paint alone does not stop buses.',
                'answers' => [
                    'q1' => '3-4 times a week',
                    'q2' => ['Samson Road (Monumento to Malabon boundary)', 'Camarin Road (North Caloocan)'],
                    'q3' => 'Yes',
                    'q4' => 5,
                    'q5' => 'Strongly Agree',
                    'q6' => 'Bagong Silang Phase 1 Terminal',
                    'q7' => 'Protected physical bollards for bikes along Samson road are urgently needed. White paint alone does not stop buses.'
                ]
            ],
            [
                'citizen_name' => 'Elena Bautista',
                'barangay' => 'Barangay 12 (District 2)',
                'age_group' => 'Senior Citizens (60+ yrs)',
                'gender' => 'Female',
                'overall_rating' => 3.5,
                'commentary' => 'Overpass ramps should have elevators or escalators for elderly and wheelchair users.',
                'answers' => [
                    'q1' => 'Once a week',
                    'q2' => ['Rizal Avenue Extension'],
                    'q3' => 'Yes',
                    'q4' => 3,
                    'q5' => 'Disagree',
                    'q6' => 'Caloocan City Hall South',
                    'q7' => 'Overpass ramps should have elevators or escalators for elderly and wheelchair users.'
                ]
            ]
        ];

        $ins = $pdo->prepare("
            INSERT INTO `survey_responses` 
            (`survey_id`, `citizen_name`, `barangay`, `age_group`, `gender`, `answers_json`, `overall_rating`, `commentary`, `submitted_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        foreach ($samples as $s) {
            $ins->execute([
                $surveyId,
                $s['citizen_name'],
                $s['barangay'],
                $s['age_group'],
                $s['gender'],
                json_encode($s['answers']),
                $s['overall_rating'],
                $s['commentary']
            ]);
        }
    }

    // ==========================================
    // CIVIC CONSULTATIONS & POLICY OUTCOMES
    // ==========================================

    /**
     * Get all civic consultations with feedback counts and breakdown
     */
    public static function getAllConsultations(): array {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->query("
            SELECT c.*,
                   COUNT(f.id) AS feedback_count,
                   SUM(CASE WHEN f.stance = 'In Favor' THEN 1 ELSE 0 END) AS count_in_favor,
                   SUM(CASE WHEN f.stance = 'Neutral' THEN 1 ELSE 0 END) AS count_neutral,
                   SUM(CASE WHEN f.stance = 'Against' THEN 1 ELSE 0 END) AS count_against,
                   SUM(CASE WHEN f.stance = 'Suggested Amendments' THEN 1 ELSE 0 END) AS count_amendments,
                   o.id AS outcome_id,
                   o.key_findings,
                   o.policy_outcome,
                   o.ordinance_number,
                   o.allocated_budget,
                   o.pdf_filename,
                   o.published_at AS outcome_published_at
            FROM `civic_consultations` c
            LEFT JOIN `consultation_feedbacks` f ON f.consultation_id = c.id
            LEFT JOIN `consultation_outcomes` o ON o.consultation_id = c.id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create civic consultation
     */
    public static function createConsultation(array $data): int {
        self::ensureTables();
        $pdo = self::getPdo();
        $code = $data['consultation_code'] ?? ('CONS-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT));
        $stmt = $pdo->prepare("
            INSERT INTO `civic_consultations` 
            (`consultation_code`, `title`, `category`, `background_info`, `objective`, `closing_date`, `status`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $data['title'],
            $data['category'] ?? 'Policy Consultation',
            $data['background_info'] ?? '',
            $data['objective'] ?? '',
            $data['closing_date'] ?? date('Y-m-d', strtotime('+45 days')),
            $data['status'] ?? 'Open',
            $data['created_by'] ?? 'City Legal & Policy Board'
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Seed official Mobile App Consultations
     */
    public static function seedAppDefaultConsultations(): void {
        self::ensureTables();
        $pdo = self::getPdo();

        // 1. Draft Ordinance No. 2026-042
        $check = $pdo->query("SELECT id FROM `civic_consultations` WHERE consultation_code = 'CONS-2026-042'")->fetch();
        if (!$check) {
            $c1Id = self::createConsultation([
                'consultation_code' => 'CONS-2026-042',
                'title' => 'Draft Ordinance No. 2026-042: Night Market & Micro-Vendor Formalization Program',
                'category' => 'Local Economic Development',
                'background_info' => 'Caloocan City Council is drafting Ordinance 2026-042 to establish regulated, clean night market economic zones with subsidized power, sanitary water connections, and digital POS payment systems for micro-entrepreneurs and food vendors along designated pedestrian streets.',
                'objective' => 'Balancing informal vendor livelihood opportunities with pedestrian walkability, food safety compliance, and zero-extortion security monitoring.',
                'closing_date' => '2026-09-10',
                'status' => 'Open',
                'created_by' => 'Caloocan City Council Committee on Trade & Market Admin'
            ]);

            // Add sample feedbacks
            $fStmt = $pdo->prepare("
                INSERT INTO `consultation_feedbacks` (`consultation_id`, `citizen_name`, `barangay`, `stance`, `commentary`)
                VALUES (?, ?, ?, ?, ?)
            ");
            $fStmt->execute([
                $c1Id,
                'Renato Valerio',
                'Barangay 12',
                'In Favor',
                'Very supportive of this ordinance. Clean stall layouts with trash cans will stop vendors from leaving food grease on the sidewalk.'
            ]);
            $fStmt->execute([
                $c1Id,
                'Lourdes Panganiban',
                'Barangay 77',
                'Suggested Amendments',
                'Please mandate that stall rental fees be capped at ₱150/day so small kwek-kwek and balut sellers are not priced out.'
            ]);
            $fStmt->execute([
                $c1Id,
                'Geraldine Cruz',
                'Barangay 176',
                'In Favor',
                'Digital payment QR codes (GCash/Maya) will help protect vendors from street extortion and petty robbery.'
            ]);
        }

        // 2. Comprehensive Youth Sports & After-School Tech Hub Expansion
        $check2 = $pdo->query("SELECT id FROM `civic_consultations` WHERE consultation_code = 'CONS-2026-043'")->fetch();
        if (!$check2) {
            self::createConsultation([
                'consultation_code' => 'CONS-2026-043',
                'title' => 'Comprehensive Youth Sports & After-School Tech Hub Expansion',
                'category' => 'Youth & Education Policy',
                'background_info' => 'The City Government is allocating ₱85M capital development funding to upgrade 15 barangay multi-purpose courts into covered climate-resilient community centers equipped with free public WiFi and AI Robotics coding learning pods for public school students.',
                'objective' => 'Gathering citizen input on proposed locations, facility schedules, and security measures for youth hubs.',
                'closing_date' => '2026-09-25',
                'status' => 'Open',
                'created_by' => 'Youth Development Office (YDO)'
            ]);
        }
    }

    /**
     * Clear all consultations
     */
    public static function clearAllConsultations(): void {
        self::ensureTables();
        $pdo = self::getPdo();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `consultation_outcomes`;");
        $pdo->exec("TRUNCATE TABLE `consultation_feedbacks`;");
        $pdo->exec("TRUNCATE TABLE `civic_consultations`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * Save policy outcome for a consultation
     */
    public static function saveConsultationOutcome(array $data): bool {
        self::ensureTables();
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO `consultation_outcomes` 
            (`consultation_id`, `key_findings`, `policy_outcome`, `ordinance_number`, `allocated_budget`, `transparency_score`, `pdf_filename`, `published_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            `key_findings` = VALUES(`key_findings`),
            `policy_outcome` = VALUES(`policy_outcome`),
            `ordinance_number` = VALUES(`ordinance_number`),
            `allocated_budget` = VALUES(`allocated_budget`),
            `transparency_score` = VALUES(`transparency_score`),
            `pdf_filename` = VALUES(`pdf_filename`),
            `published_by` = VALUES(`published_by`),
            `published_at` = NOW()
        ");
        $res = $stmt->execute([
            (int)$data['consultation_id'],
            $data['key_findings'],
            $data['policy_outcome'],
            $data['ordinance_number'] ?? null,
            $data['allocated_budget'] ?? null,
            $data['transparency_score'] ?? '100% Published & Verified',
            $data['pdf_filename'] ?? null,
            $data['published_by'] ?? 'City Council Secretariat'
        ]);

        if ($res) {
            $pdo->prepare("UPDATE `civic_consultations` SET `status` = 'Closed / Outcome Published' WHERE id = ?")
                ->execute([(int)$data['consultation_id']]);
        }
        return $res;
    }
}
?>
