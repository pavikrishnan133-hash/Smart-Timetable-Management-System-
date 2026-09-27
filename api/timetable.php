<?php
/**
 * Smart Academic Timetable Management System
 * Interactive Timetable CRUD & Slot Editor API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/ConflictDetector.php';
require_once __DIR__ . '/../classes/QualityEvaluator.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_grid');

switch ($action) {
    case 'get_grid':
        $viewType = $_GET['view_type'] ?? 'class'; // 'class', 'faculty', 'classroom'
        $id = (int)($_GET['id'] ?? 0);

        // Fetch periods and schedule settings
        $periods = $db->query("SELECT * FROM periods ORDER BY period_index ASC")->fetchAll();
        $settingsStmt = $db->query("SELECT setting_key, setting_value FROM schedule_settings");
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $workingDays = json_decode($settings['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday"]', true);

        // If no ID specified, select the first active item
        if (!$id) {
            if ($viewType === 'class') {
                $id = (int)$db->query("SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
            } elseif ($viewType === 'faculty') {
                $id = (int)$db->query("SELECT id FROM staff WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
            } elseif ($viewType === 'classroom') {
                $id = (int)$db->query("SELECT id FROM classrooms WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
            }
        }

        // Build query based on view type
        $sql = "
            SELECT 
                e.*,
                s.subject_code, s.subject_name, s.category as subject_category, s.weekly_hours,
                f.full_name as faculty_name, f.staff_code, f.color_code as faculty_color, f.role as faculty_role,
                r.room_number, r.room_name, r.room_type,
                y.year_name, y.code as year_code
            FROM timetable_entries e
            INNER JOIN subjects s ON e.subject_id = s.id
            INNER JOIN staff f ON e.faculty_id = f.id
            INNER JOIN classrooms r ON e.classroom_id = r.id
            INNER JOIN academic_years y ON e.academic_year_id = y.id
            WHERE 1=1
        ";

        $params = [];
        if ($viewType === 'class') {
            $sql .= " AND e.academic_year_id = :id";
            $params[':id'] = $id;
        } elseif ($viewType === 'faculty') {
            $sql .= " AND e.faculty_id = :id";
            $params[':id'] = $id;
        } elseif ($viewType === 'classroom') {
            $sql .= " AND e.classroom_id = :id";
            $params[':id'] = $id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll();

        // Organize into 2D grid matrix: grid[day_of_week][period_index] = entry
        $grid = [];
        foreach ($entries as $e) {
            $d = (int)$e['day_of_week'];
            $p = (int)$e['period_index'];
            $grid[$d][$p] = $e;
        }

        // Evaluate quality & conflicts for this view
        $detector = new ConflictDetector($db);
        $conflicts = ($viewType === 'class') ? $detector->auditTimetable(null, $id) : $detector->auditTimetable();

        sendJsonResponse(true, [
            'view_type' => $viewType,
            'target_id' => $id,
            'periods' => $periods,
            'working_days' => $workingDays,
            'grid' => $grid,
            'conflicts' => $conflicts
        ]);
        break;

    case 'validate_slot':
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $day = (int)($_POST['day_of_week'] ?? 1);
        $period = (int)($_POST['period_index'] ?? 1);
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        $classroomId = (int)($_POST['classroom_id'] ?? 0);
        $excludeEntryId = !empty($_POST['exclude_entry_id']) ? (int)$_POST['exclude_entry_id'] : null;

        $detector = new ConflictDetector($db);
        $result = $detector->validateSlot($yearId, $day, $period, $facultyId, $classroomId, $excludeEntryId);

        sendJsonResponse(true, $result);
        break;

    case 'save_slot':
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $day = (int)($_POST['day_of_week'] ?? 1);
        $period = (int)($_POST['period_index'] ?? 1);
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        $classroomId = (int)($_POST['classroom_id'] ?? 0);
        $isLocked = isset($_POST['is_locked']) ? (int)$_POST['is_locked'] : 0;
        $notes = trim($_POST['notes'] ?? '');
        $forceSave = !empty($_POST['force_save']);

        if (!$yearId || !$subjectId || !$facultyId || !$classroomId) {
            sendJsonResponse(false, 'Academic Year, Subject, Faculty, and Classroom are required.', [], 400);
        }

        // Get timetable ID for this academic year
        $ttStmt = $db->prepare("SELECT id FROM timetables WHERE academic_year_id = :yearId LIMIT 1");
        $ttStmt->execute([':yearId' => $yearId]);
        $tt = $ttStmt->fetch();
        $timetableId = $tt ? (int)$tt['id'] : 1;

        // Perform validation
        $detector = new ConflictDetector($db);
        $existingEntry = $db->prepare("SELECT id FROM timetable_entries WHERE academic_year_id = :yId AND day_of_week = :d AND period_index = :p");
        $existingEntry->execute([':yId' => $yearId, ':d' => $day, ':p' => $period]);
        $existingId = $existingEntry->fetchColumn();

        $validation = $detector->validateSlot($yearId, $day, $period, $facultyId, $classroomId, $existingId ? (int)$existingId : null);

        if ($validation['has_hard_clash'] && !$forceSave) {
            sendJsonResponse(false, 'Hard conflict detected! Cannot assign double-booked faculty or room without override.', [
                'validation' => $validation
            ], 409);
        }

        // Insert or Update slot
        $stmt = $db->prepare("
            INSERT INTO timetable_entries 
            (`timetable_id`, `academic_year_id`, `day_of_week`, `period_index`, `subject_id`, `faculty_id`, `classroom_id`, `is_locked`, `notes`)
            VALUES (:ttId, :yearId, :day, :period, :subId, :facId, :roomId, :locked, :notes)
            ON DUPLICATE KEY UPDATE 
                `subject_id` = :subId, `faculty_id` = :facId, `classroom_id` = :roomId, 
                `is_locked` = :locked, `notes` = :notes, `updated_at` = NOW()
        ");
        $stmt->execute([
            ':ttId' => $timetableId,
            ':yearId' => $yearId,
            ':day' => $day,
            ':period' => $period,
            ':subId' => $subjectId,
            ':facId' => $facultyId,
            ':roomId' => $classroomId,
            ':locked' => $isLocked,
            ':notes' => $notes
        ]);

        sendJsonResponse(true, 'Timetable slot updated successfully.', [
            'validation' => $validation
        ]);
        break;

    case 'delete_slot':
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $day = (int)($_POST['day_of_week'] ?? 0);
        $period = (int)($_POST['period_index'] ?? 0);

        if ($entryId > 0) {
            $stmt = $db->prepare("DELETE FROM timetable_entries WHERE id = :id");
            $stmt->execute([':id' => $entryId]);
        } elseif ($yearId > 0 && $day > 0 && $period > 0) {
            $stmt = $db->prepare("DELETE FROM timetable_entries WHERE academic_year_id = :yId AND day_of_week = :d AND period_index = :p");
            $stmt->execute([':yId' => $yearId, ':d' => $day, ':p' => $period]);
        } else {
            sendJsonResponse(false, 'Entry ID or Class/Day/Period required.', [], 400);
        }

        sendJsonResponse(true, 'Slot cleared successfully.');
        break;

    case 'toggle_lock':
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $isLocked = (int)($_POST['is_locked'] ?? 1);

        if (!$entryId) {
            sendJsonResponse(false, 'Entry ID required.', [], 400);
        }

        $stmt = $db->prepare("UPDATE timetable_entries SET is_locked = :locked WHERE id = :id");
        $stmt->execute([':locked' => $isLocked, ':id' => $entryId]);

        sendJsonResponse(true, $isLocked ? 'Slot locked (pinned).' : 'Slot unlocked.');
        break;

    case 'clear_all':
        $yearId = isset($_POST['academic_year_id']) && $_POST['academic_year_id'] !== '' ? (int)$_POST['academic_year_id'] : null;
        $preserveLocked = isset($_POST['preserve_locked']) ? (bool)$_POST['preserve_locked'] : true;

        if ($yearId !== null) {
            $sql = "DELETE FROM timetable_entries WHERE academic_year_id = :yearId";
            if ($preserveLocked) $sql .= " AND is_locked = 0";
            $stmt = $db->prepare($sql);
            $stmt->execute([':yearId' => $yearId]);
        } else {
            $sql = "DELETE FROM timetable_entries";
            if ($preserveLocked) $sql .= " WHERE is_locked = 0";
            $db->exec($sql);
        }

        sendJsonResponse(true, 'Timetable entries cleared successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
