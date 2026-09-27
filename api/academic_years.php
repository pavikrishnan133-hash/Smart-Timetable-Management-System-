<?php
/**
 * Smart Academic Timetable Management System
 * Academic Years & Batches Management API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'list':
        $stmt = $db->query("
            SELECT 
                y.*,
                COUNT(DISTINCT s.id) as subjects_count,
                COALESCE(SUM(s.weekly_hours), 0) as total_required_hours,
                COUNT(DISTINCT e.id) as total_scheduled_hours,
                t.quality_score, t.version as timetable_version
            FROM academic_years y
            LEFT JOIN subjects s ON y.id = s.academic_year_id AND s.is_active = 1
            LEFT JOIN timetable_entries e ON y.id = e.academic_year_id
            LEFT JOIN timetables t ON y.id = t.academic_year_id
            GROUP BY y.id
            ORDER BY y.id ASC
        ");
        $years = $stmt->fetchAll();
        sendJsonResponse(true, $years);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $year = $stmt->fetch();
        if (!$year) {
            sendJsonResponse(false, 'Academic Year not found.', [], 404);
        }
        sendJsonResponse(true, $year);
        break;

    case 'create':
        $yearName = trim($_POST['year_name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $semester = trim($_POST['semester'] ?? 'Semester 1');
        $batchStrength = (int)($_POST['batch_strength'] ?? 30);
        $academicSession = trim($_POST['academic_session'] ?? '2026-2027');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($yearName) || empty($code)) {
            sendJsonResponse(false, 'Year Name and Code are required.', [], 400);
        }

        $chk = $db->prepare("SELECT id FROM academic_years WHERE code = :code");
        $chk->execute([':code' => $code]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Code '{$code}' already exists.", [], 409);
        }

        $stmt = $db->prepare("
            INSERT INTO academic_years (`year_name`, `code`, `semester`, `batch_strength`, `academic_session`, `is_active`)
            VALUES (:name, :code, :sem, :strength, :session, :active)
        ");
        $stmt->execute([
            ':name' => $yearName,
            ':code' => $code,
            ':sem' => $semester,
            ':strength' => $batchStrength,
            ':session' => $academicSession,
            ':active' => $isActive
        ]);

        sendJsonResponse(true, 'Academic Year created successfully.', ['id' => (int)$db->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $yearName = trim($_POST['year_name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $semester = trim($_POST['semester'] ?? 'Semester 1');
        $batchStrength = (int)($_POST['batch_strength'] ?? 30);
        $academicSession = trim($_POST['academic_session'] ?? '2026-2027');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$id || empty($yearName) || empty($code)) {
            sendJsonResponse(false, 'ID, Year Name, and Code are required.', [], 400);
        }

        $chk = $db->prepare("SELECT id FROM academic_years WHERE code = :code AND id != :id");
        $chk->execute([':code' => $code, ':id' => $id]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Code '{$code}' already exists.", [], 409);
        }

        $stmt = $db->prepare("
            UPDATE academic_years 
            SET `year_name` = :name, `code` = :code, `semester` = :sem, 
                `batch_strength` = :strength, `academic_session` = :session, `is_active` = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $yearName,
            ':code' => $code,
            ':sem' => $semester,
            ':strength' => $batchStrength,
            ':session' => $academicSession,
            ':active' => $isActive,
            ':id' => $id
        ]);

        sendJsonResponse(true, 'Academic Year updated successfully.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(false, 'Academic Year ID required.', [], 400);
        }

        $stmt = $db->prepare("DELETE FROM academic_years WHERE id = :id");
        $stmt->execute([':id' => $id]);

        sendJsonResponse(true, 'Academic Year deleted successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
