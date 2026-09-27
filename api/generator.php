<?php
/**
 * Smart Academic Timetable Management System
 * Timetable Generator & Quality Score API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/TimetableEngine.php';
require_once __DIR__ . '/../classes/ConflictDetector.php';
require_once __DIR__ . '/../classes/QualityEvaluator.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'quality_score');

switch ($action) {
    case 'generate':
        $targetYearId = isset($_POST['academic_year_id']) && $_POST['academic_year_id'] !== '' ? (int)$_POST['academic_year_id'] : null;
        $preserveLocked = isset($_POST['preserve_locked']) ? (bool)$_POST['preserve_locked'] : true;

        try {
            $engine = new TimetableEngine($db);
            $result = $engine->generate($targetYearId, [
                'preserve_locked' => $preserveLocked
            ]);

            sendJsonResponse(true, $result['message'], [
                'details' => $result
            ]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Timetable generation error: ' . $e->getMessage(), [], 500);
        }
        break;

    case 'quality_score':
        $targetYearId = isset($_GET['academic_year_id']) && $_GET['academic_year_id'] !== '' ? (int)$_GET['academic_year_id'] : null;
        try {
            $evaluator = new QualityEvaluator($db);
            $quality = $evaluator->evaluateQuality($targetYearId);
            sendJsonResponse(true, $quality);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Quality evaluation error: ' . $e->getMessage(), [], 500);
        }
        break;

    case 'audit_conflicts':
        $targetYearId = isset($_GET['academic_year_id']) && $_GET['academic_year_id'] !== '' ? (int)$_GET['academic_year_id'] : null;
        try {
            $detector = new ConflictDetector($db);
            $conflicts = $detector->auditTimetable(null, $targetYearId);
            sendJsonResponse(true, $conflicts);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Conflict audit error: ' . $e->getMessage(), [], 500);
        }
        break;

    default:
        sendJsonResponse(false, 'Invalid generator action specified.', [], 400);
        break;
}
