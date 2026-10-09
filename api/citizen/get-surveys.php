<?php
/**
 * API: GET /api/citizen/get-surveys.php
 * Fetches all available citizen public surveys and their questions.
 */

error_reporting(0);
ini_set('display_errors', '0');
ob_start();

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../pages/public-consultation/consultation-db-helper.php';

try {
    ConsultationDB::ensureTables();

    // Check if surveys table has records; if not, seed defaults
    $surveys = ConsultationDB::getAllSurveys();
    if (empty($surveys)) {
        ConsultationDB::seedAppDefaultSurveys();
        $surveys = ConsultationDB::getAllSurveys();
    }

    $formatted = [];
    foreach ($surveys as $s) {
        $questions = [];
        if (!empty($s['questions']) && is_array($s['questions'])) {
            foreach ($s['questions'] as $q) {
                $opts = !empty($q['options']) && is_array($q['options']) ? $q['options'] : [];
                $questions[] = [
                    'id'            => (string)$q['id'],
                    'title'         => $q['title'] ?? '',
                    'type'          => $q['question_type'] ?? 'multiple_choice',
                    'question_type' => $q['question_type'] ?? 'multiple_choice',
                    'options'       => $opts,
                    'required'      => (bool)($q['is_required'] ?? true),
                    'is_required'   => (bool)($q['is_required'] ?? true),
                ];
            }
        }

        $formatted[] = [
            'id'               => (string)$s['id'],
            'surveyCode'       => $s['survey_code'] ?? '',
            'survey_code'      => $s['survey_code'] ?? '',
            'title'            => $s['title'] ?? '',
            'shortDescription' => $s['short_description'] ?? '',
            'short_description'=> $s['short_description'] ?? '',
            'category'         => $s['category'] ?? 'General Governance',
            'estimatedTime'    => $s['estimated_time'] ?? '3 mins',
            'estimated_time'   => $s['estimated_time'] ?? '3 mins',
            'closingDate'      => $s['close_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'close_date'       => $s['close_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'status'           => $s['status'] ?? 'Open',
            'isPublicResults'  => (bool)($s['is_public_results'] ?? true),
            'is_public_results'=> (bool)($s['is_public_results'] ?? true),
            'questions'        => $questions,
            'question_count'   => count($questions),
            'response_count'   => (int)($s['response_count'] ?? 0),
            'avg_rating'       => (float)($s['avg_rating'] ?? 0),
        ];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'status'  => 'success',
        'data'    => $formatted,
        'count'   => count($formatted)
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'error',
        'message' => 'Failed to retrieve surveys: ' . $e->getMessage()
    ]);
}
