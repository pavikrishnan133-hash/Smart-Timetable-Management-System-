<?php
/**
 * Smart Academic Timetable Management System
 * Staff & Faculty Management API Endpoint
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
                s.*,
                COUNT(DISTINCT sub.id) as subjects_count,
                COUNT(DISTINCT e.id) as current_assigned_hours
            FROM staff s
            LEFT JOIN subjects sub ON s.id = sub.assigned_faculty_id AND sub.is_active = 1
            LEFT JOIN timetable_entries e ON s.id = e.faculty_id
            GROUP BY s.id
            ORDER BY s.role DESC, s.full_name ASC
        ");
        $staff = $stmt->fetchAll();
        sendJsonResponse(true, $staff);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM staff WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $staffMember = $stmt->fetch();
        
        if (!$staffMember) {
            sendJsonResponse(false, 'Staff member not found.', [], 404);
        }
        sendJsonResponse(true, $staffMember);
        break;

    case 'create':
        $staffCode = trim($_POST['staff_code'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? 'B.Voc SD & SA');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $role = trim($_POST['role'] ?? 'Faculty');
        $maxWeeklyHours = (int)($_POST['max_weekly_hours'] ?? ($role === 'HOD' ? 14 : 18));
        $colorCode = trim($_POST['color_code'] ?? '#3B82F6');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($staffCode) || empty($fullName) || empty($email)) {
            sendJsonResponse(false, 'Staff Code, Full Name, and Email are required.', [], 400);
        }

        // Check duplicates
        $chk = $db->prepare("SELECT id FROM staff WHERE staff_code = :code OR email = :email");
        $chk->execute([':code' => $staffCode, ':email' => $email]);
        if ($chk->fetch()) {
            sendJsonResponse(false, 'A staff member with this Staff Code or Email already exists.', [], 409);
        }

        $stmt = $db->prepare("
            INSERT INTO staff (`staff_code`, `full_name`, `email`, `phone`, `designation`, `department`, `role`, `max_weekly_hours`, `color_code`, `is_active`)
            VALUES (:code, :name, :email, :phone, :desig, :dept, :role, :maxHours, :color, :active)
        ");
        $stmt->execute([
            ':code' => $staffCode,
            ':name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':desig' => $designation,
            ':dept' => $department,
            ':role' => $role,
            ':maxHours' => $maxWeeklyHours,
            ':color' => $colorCode,
            ':active' => $isActive
        ]);
        $newId = (int)$db->lastInsertId();

        // Initialize availability matrix (5 days * 5 periods = all available)
        $availStmt = $db->prepare("
            INSERT INTO staff_availability (`staff_id`, `day_of_week`, `period_index`, `is_available`, `preference`)
            VALUES (:sid, :day, :period, 1, 'neutral')
        ");
        for ($d = 1; $d <= 5; $d++) {
            for ($p = 1; $p <= 5; $p++) {
                $availStmt->execute([':sid' => $newId, ':day' => $d, ':period' => $p]);
            }
        }

        sendJsonResponse(true, 'Faculty member added successfully.', ['id' => $newId]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $staffCode = trim($_POST['staff_code'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? 'B.Voc SD & SA');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $role = trim($_POST['role'] ?? 'Faculty');
        $maxWeeklyHours = (int)($_POST['max_weekly_hours'] ?? ($role === 'HOD' ? 14 : 18));
        $colorCode = trim($_POST['color_code'] ?? '#3B82F6');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$id || empty($staffCode) || empty($fullName) || empty($email)) {
            sendJsonResponse(false, 'Staff ID, Code, Name, and Email are required.', [], 400);
        }

        // Check duplicate code/email excluding self
        $chk = $db->prepare("SELECT id FROM staff WHERE (staff_code = :code OR email = :email) AND id != :id");
        $chk->execute([':code' => $staffCode, ':email' => $email, ':id' => $id]);
        if ($chk->fetch()) {
            sendJsonResponse(false, 'Another staff member already uses this Staff Code or Email.', [], 409);
        }

        $stmt = $db->prepare("
            UPDATE staff 
            SET `staff_code` = :code, `full_name` = :name, `email` = :email, `phone` = :phone, 
                `designation` = :desig, `department` = :dept, `role` = :role, `max_weekly_hours` = :maxHours, 
                `color_code` = :color, `is_active` = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':code' => $staffCode,
            ':name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':desig' => $designation,
            ':dept' => $department,
            ':role' => $role,
            ':maxHours' => $maxWeeklyHours,
            ':color' => $colorCode,
            ':active' => $isActive,
            ':id' => $id
        ]);

        sendJsonResponse(true, 'Faculty member updated successfully.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(false, 'Staff ID required.', [], 400);
        }

        // Check if assigned to active subjects
        $chk = $db->prepare("SELECT COUNT(*) FROM subjects WHERE assigned_faculty_id = :id");
        $chk->execute([':id' => $id]);
        $subCount = (int)$chk->fetchColumn();

        if ($subCount > 0) {
            sendJsonResponse(false, "Cannot delete staff member who is assigned to {$subCount} active subject(s). Please reassign those subjects first.", [], 400);
        }

        $stmt = $db->prepare("DELETE FROM staff WHERE id = :id");
        $stmt->execute([':id' => $id]);
        sendJsonResponse(true, 'Staff member deleted successfully.');
        break;

    case 'get_availability':
        $staffId = (int)($_GET['staff_id'] ?? 0);
        if (!$staffId) {
            sendJsonResponse(false, 'Staff ID is required.', [], 400);
        }

        $stmt = $db->prepare("
            SELECT day_of_week, period_index, is_available, preference 
            FROM staff_availability 
            WHERE staff_id = :sid
            ORDER BY day_of_week ASC, period_index ASC
        ");
        $stmt->execute([':sid' => $staffId]);
        $matrix = $stmt->fetchAll();

        sendJsonResponse(true, $matrix);
        break;

    case 'save_availability':
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $slots = $_POST['slots'] ?? []; // JSON string or array

        if (is_string($slots)) {
            $slots = json_decode($slots, true) ?: [];
        }

        if (!$staffId || empty($slots)) {
            sendJsonResponse(false, 'Staff ID and slots data are required.', [], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO staff_availability (`staff_id`, `day_of_week`, `period_index`, `is_available`, `preference`)
            VALUES (:sid, :day, :period, :avail, :pref)
            ON DUPLICATE KEY UPDATE `is_available` = :avail, `preference` = :pref
        ");

        $db->beginTransaction();
        foreach ($slots as $slot) {
            $day = (int)($slot['day_of_week'] ?? 1);
            $period = (int)($slot['period_index'] ?? 1);
            $avail = isset($slot['is_available']) ? (int)$slot['is_available'] : 1;
            $pref = $slot['preference'] ?? 'neutral';

            $stmt->execute([
                ':sid' => $staffId,
                ':day' => $day,
                ':period' => $period,
                ':avail' => $avail,
                ':pref' => $pref
            ]);
        }
        $db->commit();

        sendJsonResponse(true, 'Staff availability matrix saved successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
