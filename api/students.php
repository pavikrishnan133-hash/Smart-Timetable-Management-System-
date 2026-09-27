<?php
/**
 * Smart Academic Timetable Management System
 * Student Management API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'list':
        $yearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
        
        $sql = "
            SELECT 
                st.*,
                ay.year_name as academic_year_name,
                ay.code as academic_year_code,
                ay.semester as academic_year_semester
            FROM students st
            JOIN academic_years ay ON st.academic_year_id = ay.id
        ";
        
        if ($yearId > 0) {
            $sql .= " WHERE st.academic_year_id = :year_id";
        }
        
        $sql .= " ORDER BY st.academic_year_id ASC, st.register_number ASC";
        
        $stmt = $db->prepare($sql);
        if ($yearId > 0) {
            $stmt->execute([':year_id' => $yearId]);
        } else {
            $stmt->execute();
        }
        
        $students = $stmt->fetchAll();
        sendJsonResponse(true, $students);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("
            SELECT st.*, ay.year_name as academic_year_name, ay.code as academic_year_code
            FROM students st
            JOIN academic_years ay ON st.academic_year_id = ay.id
            WHERE st.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            sendJsonResponse(false, 'Student not found.', [], 404);
        }
        sendJsonResponse(true, $student);
        break;

    case 'create':
        $fullName = trim($_POST['full_name'] ?? '');
        $regNumber = trim($_POST['register_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $academicYearId = (int)($_POST['academic_year_id'] ?? 0);
        $section = trim($_POST['section'] ?? 'Section A');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        // Validation
        if (empty($fullName)) {
            sendJsonResponse(false, 'Student Name is required.', [], 400);
        }
        if (empty($regNumber)) {
            sendJsonResponse(false, 'Register Number is required.', [], 400);
        }
        if (empty($email)) {
            sendJsonResponse(false, 'Email address is required.', [], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(false, 'Please provide a valid email address.', [], 400);
        }
        if ($academicYearId <= 0) {
            sendJsonResponse(false, 'Please select a valid Academic Year.', [], 400);
        }

        // Check if Academic Year exists
        $chkYear = $db->prepare("SELECT id FROM academic_years WHERE id = :id");
        $chkYear->execute([':id' => $academicYearId]);
        if (!$chkYear->fetch()) {
            sendJsonResponse(false, 'Selected Academic Year does not exist.', [], 400);
        }

        // Check for duplicates
        $chk = $db->prepare("SELECT id, register_number, email FROM students WHERE register_number = :reg OR email = :email");
        $chk->execute([':reg' => $regNumber, ':email' => $email]);
        $duplicate = $chk->fetch();
        if ($duplicate) {
            if (strcasecmp($duplicate['register_number'], $regNumber) === 0) {
                sendJsonResponse(false, "A student with Register Number '{$regNumber}' already exists.", [], 409);
            } else {
                sendJsonResponse(false, "A student with Email '{$email}' already exists.", [], 409);
            }
        }

        if (empty($section)) {
            $section = 'Section A';
        }

        $stmt = $db->prepare("
            INSERT INTO students (`register_number`, `full_name`, `email`, `phone`, `academic_year_id`, `section`, `is_active`)
            VALUES (:reg, :name, :email, :phone, :year_id, :section, :active)
        ");
        $stmt->execute([
            ':reg' => $regNumber,
            ':name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':year_id' => $academicYearId,
            ':section' => $section,
            ':active' => $isActive
        ]);

        $newId = (int)$db->lastInsertId();
        sendJsonResponse(true, 'Student added successfully.', ['id' => $newId]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $regNumber = trim($_POST['register_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $academicYearId = (int)($_POST['academic_year_id'] ?? 0);
        $section = trim($_POST['section'] ?? 'Section A');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$id || empty($fullName) || empty($regNumber) || empty($email) || $academicYearId <= 0) {
            sendJsonResponse(false, 'Student ID, Name, Register Number, Email, and Academic Year are required.', [], 400);
        }

        // Check duplicate excluding self
        $chk = $db->prepare("SELECT id FROM students WHERE (register_number = :reg OR email = :email) AND id != :id");
        $chk->execute([':reg' => $regNumber, ':email' => $email, ':id' => $id]);
        if ($chk->fetch()) {
            sendJsonResponse(false, 'Another student already uses this Register Number or Email.', [], 409);
        }

        $stmt = $db->prepare("
            UPDATE students 
            SET `register_number` = :reg, `full_name` = :name, `email` = :email, 
                `phone` = :phone, `academic_year_id` = :year_id, `section` = :section, `is_active` = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':reg' => $regNumber,
            ':name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':year_id' => $academicYearId,
            ':section' => $section,
            ':active' => $isActive,
            ':id' => $id
        ]);

        sendJsonResponse(true, 'Student updated successfully.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(false, 'Student ID is required.', [], 400);
        }

        $stmt = $db->prepare("DELETE FROM students WHERE id = :id");
        $stmt->execute([':id' => $id]);
        sendJsonResponse(true, 'Student deleted successfully.');
        break;

    case 'stats':
        $total = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $active = (int)$db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
        
        $yearCounts = $db->query("
            SELECT ay.id, ay.year_name, ay.code, COUNT(st.id) as student_count
            FROM academic_years ay
            LEFT JOIN students st ON ay.id = st.academic_year_id AND st.is_active = 1
            GROUP BY ay.id
            ORDER BY ay.id ASC
        ")->fetchAll();

        sendJsonResponse(true, [
            'total' => $total,
            'active' => $active,
            'by_year' => $yearCounts
        ]);
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
