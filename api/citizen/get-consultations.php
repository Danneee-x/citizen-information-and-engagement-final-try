<?php
/**
 * API: GET /api/citizen/get-consultations.php
 * Fetches all civic public consultations and hearing items.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../pages/public-consultation/consultation-db-helper.php';

try {
    ConsultationDB::ensureTables();

    // Check if consultations table has records; if not, seed defaults
    $consultations = ConsultationDB::getAllConsultations();
    if (empty($consultations)) {
        ConsultationDB::seedAppDefaultConsultations();
        $consultations = ConsultationDB::getAllConsultations();
    }

    $formatted = [];
    foreach ($consultations as $c) {
        $feedbackCount = (int)($c['feedback_count'] ?? 0);
        $formatted[] = [
            'id'                => (string)$c['id'],
            'consultationCode'  => $c['consultation_code'] ?? '',
            'consultation_code' => $c['consultation_code'] ?? '',
            'title'             => $c['title'] ?? '',
            'category'          => $c['category'] ?? 'Public Infrastructure',
            'closingDate'       => $c['closing_date'] ?? date('Y-m-d', strtotime('+45 days')),
            'closing_date'      => $c['closing_date'] ?? date('Y-m-d', strtotime('+45 days')),
            'backgroundInfo'    => $c['background_info'] ?? '',
            'background_info'   => $c['background_info'] ?? '',
            'objective'         => $c['objective'] ?? '',
            'participantsCount' => $feedbackCount,
            'feedback_count'    => $feedbackCount,
            'status'            => $c['status'] ?? 'Open',
            'count_in_favor'    => (int)($c['count_in_favor'] ?? 0),
            'count_neutral'     => (int)($c['count_neutral'] ?? 0),
            'count_against'     => (int)($c['count_against'] ?? 0),
            'count_amendments'  => (int)($c['count_amendments'] ?? 0),
            'outcome_id'        => $c['outcome_id'] ?? null,
            'key_findings'      => $c['key_findings'] ?? null,
            'policy_outcome'    => $c['policy_outcome'] ?? null,
            'ordinance_number'  => $c['ordinance_number'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'status'  => 'success',
        'count'   => count($formatted),
        'data'    => $formatted,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'error',
        'message' => 'Failed to fetch consultations: ' . $e->getMessage(),
        'data'    => []
    ]);
}
