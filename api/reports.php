<?php
/**
 * Smart Academic Timetable Management System
 * Reports, Workload Analytics & Audit Export API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/ConflictDetector.php';
require_once __DIR__ . '/../classes/QualityEvaluator.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? 'workload_report';

switch ($action) {
    case 'workload_report':
        $stmt = $db->query("
            SELECT 
                f.id as faculty_id, f.staff_code, f.full_name, f.designation, f.role,
                f.max_weekly_hours, f.color_code,
                COUNT(e.id) as total_scheduled_hours,
                SUM(CASE WHEN s.category IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as lab_hours,
                SUM(CASE WHEN s.category NOT IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as theory_hours,
                COUNT(DISTINCT s.id) as total_subjects_count,
                COUNT(DISTINCT e.academic_year_id) as total_classes_count
            FROM staff f
            LEFT JOIN timetable_entries e ON f.id = e.faculty_id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE f.is_active = 1
            GROUP BY f.id
            ORDER BY f.role DESC, total_scheduled_hours DESC
        ");
        $report = $stmt->fetchAll();

        foreach ($report as &$r) {
            $max = (int)$r['max_weekly_hours'];
            $total = (int)$r['total_scheduled_hours'];
            $r['utilization_percentage'] = ($max > 0) ? round(($total / $max) * 100, 1) : 0;
            $r['is_overloaded'] = ($total > $max);
            $r['is_underutilized'] = ($total < ($max * 0.6));
        }
        unset($r);

        sendJsonResponse(true, $report);
        break;

    case 'room_utilization':
        $daysCount = (int)($db->query("SELECT setting_value FROM schedule_settings WHERE setting_key = 'working_days_count'")->fetchColumn() ?: 5);
        $periodsCount = (int)($db->query("SELECT COUNT(*) FROM periods")->fetchColumn() ?: 5);
        $totalPossibleSlots = $daysCount * $periodsCount;

        $stmt = $db->query("
            SELECT 
                r.id as classroom_id, r.room_number, r.room_name, r.room_type, r.capacity, r.block_floor,
                COUNT(e.id) as occupied_slots,
                COUNT(DISTINCT e.academic_year_id) as distinct_classes_hosted,
                SUM(CASE WHEN s.category IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as lab_slots,
                SUM(CASE WHEN s.category NOT IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as theory_slots
            FROM classrooms r
            LEFT JOIN timetable_entries e ON r.id = e.classroom_id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE r.is_active = 1
            GROUP BY r.id
            ORDER BY occupied_slots DESC
        ");
        $rooms = $stmt->fetchAll();

        foreach ($rooms as &$rm) {
            $occupied = (int)$rm['occupied_slots'];
            $rm['total_capacity_slots'] = $totalPossibleSlots;
            $rm['utilization_percentage'] = ($totalPossibleSlots > 0) ? round(($occupied / $totalPossibleSlots) * 100, 1) : 0;
        }
        unset($rm);

        sendJsonResponse(true, $rooms);
        break;

    case 'conflict_audit':
        $detector = new ConflictDetector($db);
        $conflicts = $detector->auditTimetable();
        $evaluator = new QualityEvaluator($db);
        $quality = $evaluator->evaluateQuality();

        sendJsonResponse(true, [
            'total_conflicts' => count($conflicts),
            'quality_score' => $quality['score'],
            'quality_grade' => $quality['grade'],
            'conflicts' => $conflicts,
            'quality_details' => $quality
        ]);
        break;

    case 'export_workload_csv':
        $stmt = $db->query("
            SELECT 
                f.staff_code, f.full_name, f.designation, f.role, f.max_weekly_hours,
                COUNT(e.id) as total_hours,
                SUM(CASE WHEN s.category NOT IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as theory_hours,
                SUM(CASE WHEN s.category IN ('Major Lab', 'Skill Lab') THEN 1 ELSE 0 END) as lab_hours
            FROM staff f
            LEFT JOIN timetable_entries e ON f.id = e.faculty_id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE f.is_active = 1
            GROUP BY f.id
        ");
        $data = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=staff_workload_report.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Staff Code', 'Faculty Name', 'Designation', 'Role', 'Max Limit (Hrs)', 'Theory (Hrs)', 'Lab (Hrs)', 'Total Scheduled (Hrs)', 'Status']);

        foreach ($data as $row) {
            $status = ((int)$row['total_hours'] > (int)$row['max_weekly_hours']) ? 'OVERLOADED' : 'BALANCED';
            fputcsv($output, [
                $row['staff_code'],
                $row['full_name'],
                $row['designation'],
                $row['role'],
                $row['max_weekly_hours'],
                $row['theory_hours'] ?? 0,
                $row['lab_hours'] ?? 0,
                $row['total_hours'],
                $status
            ]);
        }
        fclose($output);
        exit;

    default:
        sendJsonResponse(false, 'Invalid report action specified.', [], 400);
        break;
}
