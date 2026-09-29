<?php
/**
 * GET Active Public Surveys & Questions for Citizen App
 * Path: /citizen-backend/api/citizen/get-surveys.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();
    
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'public_surveys'")->fetch();
    if (!$tableCheck) {
        echo json_encode(['success' => true, 'count' => 0, 'data' => []]);
        exit;
    }

    $stmt = $pdo->query("
        SELECT id, survey_code, title, short_description, category, estimated_time, 
               close_date, status, is_public_results, open_date, target_audience
        FROM `public_surveys`
        WHERE status IN ('Open', 'Closing Soon')
        ORDER BY created_at DESC
    ");
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($surveys as $s) {
        $qStmt = $pdo->prepare("
            SELECT id, title, question_type, options_json, is_required
            FROM `survey_questions`
            WHERE survey_id = ?
            ORDER BY question_order ASC
        ");
        $qStmt->execute([$s['id']]);
        $rawQuestions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = [];
        foreach ($rawQuestions as $rq) {
            $questions[] = [
                'id' => 'q_' . $rq['id'],
                'title' => $rq['title'],
                'type' => $rq['question_type'],
                'options' => !empty($rq['options_json']) ? (json_decode($rq['options_json'], true) ?: []) : [],
                'required' => (bool)$rq['is_required']
            ];
        }

        $formatted[] = [
            'id' => (string)$s['id'],
            'surveyCode' => $s['survey_code'],
            'title' => $s['title'],
            'shortDescription' => $s['short_description'],
            'category' => $s['category'],
            'estimatedTime' => $s['estimated_time'],
            'closingDate' => date('F d, Y', strtotime($s['close_date'])),
            'status' => $s['status'],
            'isPublicResults' => (bool)$s['is_public_results'],
            'questions' => $questions
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($formatted),
        'data' => $formatted
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
