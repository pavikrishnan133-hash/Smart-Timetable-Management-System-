<?php
/**
 * Smart Academic Timetable Management System
 * Dynamic Subject Management API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'list':
        $yearId = isset($_GET['academic_year_id']) && $_GET['academic_year_id'] !== '' ? (int)$_GET['academic_year_id'] : null;
        $category = $_GET['category'] ?? null;

        $sql = "
            SELECT 
                s.*,
                y.year_name, y.code as year_code, y.semester,
                f.full_name as faculty_name, f.staff_code, f.role as faculty_role, f.color_code as faculty_color,
                COUNT(e.id) as scheduled_hours
            FROM subjects s
            INNER JOIN academic_years y ON s.academic_year_id = y.id
            INNER JOIN staff f ON s.assigned_faculty_id = f.id
            LEFT JOIN timetable_entries e ON s.id = e.subject_id
            WHERE 1=1
        ";

        $params = [];
        if ($yearId !== null) {
            $sql .= " AND s.academic_year_id = :yearId";
            $params[':yearId'] = $yearId;
        }
        if (!empty($category)) {
            $sql .= " AND s.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " GROUP BY s.id ORDER BY s.academic_year_id ASC, s.category ASC, s.subject_name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();

        sendJsonResponse(true, $subjects);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $sub = $stmt->fetch();

        if (!$sub) {
            sendJsonResponse(false, 'Subject not found.', [], 404);
        }
        sendJsonResponse(true, $sub);
        break;

    case 'create':
        $subjectCode = strtoupper(trim($_POST['subject_code'] ?? ''));
        $subjectName = trim($_POST['subject_name'] ?? '');
        $academicYearId = (int)($_POST['academic_year_id'] ?? 0);
        $category = trim($_POST['category'] ?? 'Major Theory');
        $weeklyHours = (int)($_POST['weekly_hours'] ?? 4);
        $assignedFacultyId = (int)($_POST['assigned_faculty_id'] ?? 0);
        $secondaryFacultyId = !empty($_POST['secondary_faculty_id']) ? (int)$_POST['secondary_faculty_id'] : null;
        $preferredRoomType = trim($_POST['preferred_room_type'] ?? 'Theory Classroom');
        $isConsecutive = isset($_POST['is_consecutive_required']) ? (int)$_POST['is_consecutive_required'] : (str_contains($category, 'Lab') ? 1 : 0);
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($subjectCode) || empty($subjectName) || !$academicYearId || !$assignedFacultyId) {
            sendJsonResponse(false, 'Subject Code, Name, Academic Year, and Assigned Faculty are required.', [], 400);
        }

        // Check duplicate code in same year
        $chk = $db->prepare("SELECT id FROM subjects WHERE academic_year_id = :yId AND subject_code = :code");
        $chk->execute([':yId' => $academicYearId, ':code' => $subjectCode]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Subject Code '{$subjectCode}' already exists for this Academic Year.", [], 409);
        }

        $stmt = $db->prepare("
            INSERT INTO subjects 
            (`subject_code`, `subject_name`, `academic_year_id`, `category`, `weekly_hours`, `assigned_faculty_id`, `secondary_faculty_id`, `preferred_room_type`, `is_consecutive_required`, `is_active`)
            VALUES (:code, :name, :yId, :cat, :hrs, :facId, :secFacId, :roomType, :consec, :active)
        ");
        $stmt->execute([
            ':code' => $subjectCode,
            ':name' => $subjectName,
            ':yId' => $academicYearId,
            ':cat' => $category,
            ':hrs' => $weeklyHours,
            ':facId' => $assignedFacultyId,
            ':secFacId' => $secondaryFacultyId,
            ':roomType' => $preferredRoomType,
            ':consec' => $isConsecutive,
            ':active' => $isActive
        ]);

        sendJsonResponse(true, 'Subject created successfully.', ['id' => (int)$db->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $subjectCode = strtoupper(trim($_POST['subject_code'] ?? ''));
        $subjectName = trim($_POST['subject_name'] ?? '');
        $academicYearId = (int)($_POST['academic_year_id'] ?? 0);
        $category = trim($_POST['category'] ?? 'Major Theory');
        $weeklyHours = (int)($_POST['weekly_hours'] ?? 4);
        $assignedFacultyId = (int)($_POST['assigned_faculty_id'] ?? 0);
        $secondaryFacultyId = !empty($_POST['secondary_faculty_id']) ? (int)$_POST['secondary_faculty_id'] : null;
        $preferredRoomType = trim($_POST['preferred_room_type'] ?? 'Theory Classroom');
        $isConsecutive = isset($_POST['is_consecutive_required']) ? (int)$_POST['is_consecutive_required'] : (str_contains($category, 'Lab') ? 1 : 0);
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$id || empty($subjectCode) || empty($subjectName) || !$academicYearId || !$assignedFacultyId) {
            sendJsonResponse(false, 'Subject ID, Code, Name, Academic Year, and Faculty are required.', [], 400);
        }

        // Check duplicate code
        $chk = $db->prepare("SELECT id FROM subjects WHERE academic_year_id = :yId AND subject_code = :code AND id != :id");
        $chk->execute([':yId' => $academicYearId, ':code' => $subjectCode, ':id' => $id]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Subject Code '{$subjectCode}' already exists for this Academic Year.", [], 409);
        }

        $stmt = $db->prepare("
            UPDATE subjects 
            SET `subject_code` = :code, `subject_name` = :name, `academic_year_id` = :yId, 
                `category` = :cat, `weekly_hours` = :hrs, `assigned_faculty_id` = :facId, 
                `secondary_faculty_id` = :secFacId, `preferred_room_type` = :roomType, 
                `is_consecutive_required` = :consec, `is_active` = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':code' => $subjectCode,
            ':name' => $subjectName,
            ':yId' => $academicYearId,
            ':cat' => $category,
            ':hrs' => $weeklyHours,
            ':facId' => $assignedFacultyId,
            ':secFacId' => $secondaryFacultyId,
            ':roomType' => $preferredRoomType,
            ':consec' => $isConsecutive,
            ':active' => $isActive,
            ':id' => $id
        ]);

        sendJsonResponse(true, 'Subject updated successfully.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(false, 'Subject ID is required.', [], 400);
        }

        // Delete associated timetable entries first
        $delEntries = $db->prepare("DELETE FROM timetable_entries WHERE subject_id = :id");
        $delEntries->execute([':id' => $id]);

        $stmt = $db->prepare("DELETE FROM subjects WHERE id = :id");
        $stmt->execute([':id' => $id]);

        sendJsonResponse(true, 'Subject deleted successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
