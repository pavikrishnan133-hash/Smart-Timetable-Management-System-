<?php
/**
 * Smart Academic Timetable Management System
 * Real-time Conflict Detector & Audit Engine
 */

require_once __DIR__ . '/../config/database.php';

class ConflictDetector {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDB();
    }

    /**
     * Audit full timetable and return list of all detected conflicts
     * 
     * @param int|null $timetableId Optional specific timetable ID or all active
     * @param int|null $academicYearId Optional specific year
     * @return array Array of conflict objects
     */
    public function auditTimetable(?int $timetableId = null, ?int $academicYearId = null): array {
        $conflicts = [];

        // 1. Audit Faculty Clashes (Same faculty in 2 classes at same day & period)
        $facultyClashes = $this->detectFacultyClashes($academicYearId);
        $conflicts = array_merge($conflicts, $facultyClashes);

        // 2. Audit Room Clashes (Same room in 2 classes at same day & period)
        $roomClashes = $this->detectRoomClashes($academicYearId);
        $conflicts = array_merge($conflicts, $roomClashes);

        // 3. Audit Faculty Workload Overruns
        $workloadOverruns = $this->detectWorkloadOverruns();
        $conflicts = array_merge($conflicts, $workloadOverruns);

        // 4. Audit Unmet Subject Hours (Syllabus quota missing or over-allocated)
        $unmetHours = $this->detectSubjectHourDiscrepancies($academicYearId);
        $conflicts = array_merge($conflicts, $unmetHours);

        // 5. Audit Staff Availability Violations
        $availViolations = $this->detectAvailabilityViolations($academicYearId);
        $conflicts = array_merge($conflicts, $availViolations);

        // 6. Audit Lab Splitting Violations
        $labViolations = $this->detectLabSplittingViolations($academicYearId);
        $conflicts = array_merge($conflicts, $labViolations);

        return $conflicts;
    }

    /**
     * Detect faculty double bookings on the same day and period across all classes
     */
    public function detectFacultyClashes(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e1.id as entry1_id, e2.id as entry2_id,
                e1.academic_year_id as year1_id, y1.year_name as year1_name,
                e2.academic_year_id as year2_id, y2.year_name as year2_name,
                e1.day_of_week, e1.period_index,
                e1.faculty_id, f.full_name as faculty_name,
                e1.subject_id as sub1_id, s1.subject_name as sub1_name,
                e2.subject_id as sub2_id, s2.subject_name as sub2_name
            FROM timetable_entries e1
            INNER JOIN timetable_entries e2 
                ON e1.faculty_id = e2.faculty_id 
                AND e1.day_of_week = e2.day_of_week 
                AND e1.period_index = e2.period_index 
                AND e1.id < e2.id
            INNER JOIN staff f ON e1.faculty_id = f.id
            INNER JOIN academic_years y1 ON e1.academic_year_id = y1.id
            INNER JOIN academic_years y2 ON e2.academic_year_id = y2.id
            INNER JOIN subjects s1 ON e1.subject_id = s1.id
            INNER JOIN subjects s2 ON e2.subject_id = s2.id
        ";

        if ($academicYearId !== null) {
            $sql .= " WHERE (e1.academic_year_id = :yearId OR e2.academic_year_id = :yearId)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        $clashes = [];
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        while ($row = $stmt->fetch()) {
            $dayStr = $dayNames[$row['day_of_week']] ?? ('Day ' . $row['day_of_week']);
            $clashes[] = [
                'type' => 'faculty_clash',
                'severity' => 'high',
                'day_of_week' => (int)$row['day_of_week'],
                'period_index' => (int)$row['period_index'],
                'faculty_id' => (int)$row['faculty_id'],
                'faculty_name' => $row['faculty_name'],
                'message' => "Faculty Clash: {$row['faculty_name']} is double-booked on {$dayStr}, Period {$row['period_index']} between '{$row['year1_name']}' ({$row['sub1_name']}) and '{$row['year2_name']}' ({$row['sub2_name']}).",
                'details' => $row
            ];
        }

        return $clashes;
    }

    /**
     * Detect classroom double bookings on the same day and period
     */
    public function detectRoomClashes(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e1.id as entry1_id, e2.id as entry2_id,
                e1.academic_year_id as year1_id, y1.year_name as year1_name,
                e2.academic_year_id as year2_id, y2.year_name as year2_name,
                e1.day_of_week, e1.period_index,
                e1.classroom_id, r.room_number, r.room_name,
                e1.subject_id as sub1_id, s1.subject_name as sub1_name,
                e2.subject_id as sub2_id, s2.subject_name as sub2_name
            FROM timetable_entries e1
            INNER JOIN timetable_entries e2 
                ON e1.classroom_id = e2.classroom_id 
                AND e1.day_of_week = e2.day_of_week 
                AND e1.period_index = e2.period_index 
                AND e1.id < e2.id
            INNER JOIN classrooms r ON e1.classroom_id = r.id
            INNER JOIN academic_years y1 ON e1.academic_year_id = y1.id
            INNER JOIN academic_years y2 ON e2.academic_year_id = y2.id
            INNER JOIN subjects s1 ON e1.subject_id = s1.id
            INNER JOIN subjects s2 ON e2.subject_id = s2.id
        ";

        if ($academicYearId !== null) {
            $sql .= " WHERE (e1.academic_year_id = :yearId OR e2.academic_year_id = :yearId)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        $clashes = [];
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        while ($row = $stmt->fetch()) {
            $dayStr = $dayNames[$row['day_of_week']] ?? ('Day ' . $row['day_of_week']);
            $clashes[] = [
                'type' => 'room_clash',
                'severity' => 'high',
                'day_of_week' => (int)$row['day_of_week'],
                'period_index' => (int)$row['period_index'],
                'classroom_id' => (int)$row['classroom_id'],
                'room_number' => $row['room_number'],
                'message' => "Classroom Clash: Room {$row['room_number']} ({$row['room_name']}) is double-booked on {$dayStr}, Period {$row['period_index']} for '{$row['year1_name']}' and '{$row['year2_name']}'.",
                'details' => $row
            ];
        }

        return $clashes;
    }

    /**
     * Detect faculty workload exceeding max hours
     */
    public function detectWorkloadOverruns(): array {
        $sql = "
            SELECT 
                f.id as faculty_id, f.full_name, f.role, f.max_weekly_hours,
                COUNT(e.id) as assigned_hours
            FROM staff f
            LEFT JOIN timetable_entries e ON f.id = e.faculty_id
            WHERE f.is_active = 1
            GROUP BY f.id
            HAVING assigned_hours > f.max_weekly_hours
        ";

        $stmt = $this->db->query($sql);
        $overruns = [];

        while ($row = $stmt->fetch()) {
            $excess = $row['assigned_hours'] - $row['max_weekly_hours'];
            $overruns[] = [
                'type' => 'workload_exceeded',
                'severity' => 'medium',
                'faculty_id' => (int)$row['faculty_id'],
                'faculty_name' => $row['full_name'],
                'message' => "Workload Overload: {$row['full_name']} ({$row['role']}) is assigned {$row['assigned_hours']} hours/week, exceeding the maximum limit of {$row['max_weekly_hours']} hours by +{$excess} hours.",
                'details' => $row
            ];
        }

        return $overruns;
    }

    /**
     * Detect subject required hours vs allocated hours in current timetable
     */
    public function detectSubjectHourDiscrepancies(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                s.id as subject_id, s.subject_code, s.subject_name, s.weekly_hours,
                s.academic_year_id, y.year_name,
                COUNT(e.id) as allocated_hours
            FROM subjects s
            INNER JOIN academic_years y ON s.academic_year_id = y.id
            LEFT JOIN timetable_entries e ON s.id = e.subject_id
            WHERE s.is_active = 1 AND y.is_active = 1
        ";

        if ($academicYearId !== null) {
            $sql .= " AND s.academic_year_id = :yearId";
        }

        $sql .= " GROUP BY s.id HAVING allocated_hours != s.weekly_hours";

        $stmt = $this->db->prepare($sql);
        if ($academicYearId !== null) {
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt->execute();
        }

        $discrepancies = [];
        while ($row = $stmt->fetch()) {
            $diff = $row['allocated_hours'] - $row['weekly_hours'];
            $statusStr = ($diff < 0) 
                ? "Deficit of " . abs($diff) . " hour(s) (Allocated {$row['allocated_hours']}/{$row['weekly_hours']})"
                : "Over-allocated by " . $diff . " hour(s) (Allocated {$row['allocated_hours']}/{$row['weekly_hours']})";

            $discrepancies[] = [
                'type' => 'unmet_hours',
                'severity' => ($diff < 0) ? 'high' : 'medium',
                'subject_id' => (int)$row['subject_id'],
                'academic_year_id' => (int)$row['academic_year_id'],
                'message' => "Subject Hour Discrepancy: '{$row['subject_name']}' ({$row['year_name']}) has a {$statusStr}.",
                'details' => $row
            ];
        }

        return $discrepancies;
    }

    /**
     * Detect faculty scheduled when marked unavailable
     */
    public function detectAvailabilityViolations(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e.id as entry_id, e.academic_year_id, y.year_name,
                e.day_of_week, e.period_index,
                e.faculty_id, f.full_name as faculty_name,
                e.subject_id, s.subject_name
            FROM timetable_entries e
            INNER JOIN staff f ON e.faculty_id = f.id
            INNER JOIN academic_years y ON e.academic_year_id = y.id
            INNER JOIN subjects s ON e.subject_id = s.id
            INNER JOIN staff_availability sa 
                ON e.faculty_id = sa.staff_id 
                AND e.day_of_week = sa.day_of_week 
                AND e.period_index = sa.period_index
            WHERE sa.is_available = 0
        ";

        if ($academicYearId !== null) {
            $sql .= " AND e.academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        $violations = [];
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        while ($row = $stmt->fetch()) {
            $dayStr = $dayNames[$row['day_of_week']] ?? ('Day ' . $row['day_of_week']);
            $violations[] = [
                'type' => 'unavailable_slot',
                'severity' => 'medium',
                'day_of_week' => (int)$row['day_of_week'],
                'period_index' => (int)$row['period_index'],
                'faculty_id' => (int)$row['faculty_id'],
                'message' => "Availability Warning: {$row['faculty_name']} is scheduled on {$dayStr}, Period {$row['period_index']} ({$row['subject_name']}) despite being marked unavailable.",
                'details' => $row
            ];
        }

        return $violations;
    }

    /**
     * Detect lab subjects scheduled in isolated single periods instead of consecutive blocks
     */
    public function detectLabSplittingViolations(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e.id, e.academic_year_id, y.year_name, e.day_of_week, e.period_index,
                e.subject_id, s.subject_name, s.category, s.is_consecutive_required
            FROM timetable_entries e
            INNER JOIN subjects s ON e.subject_id = s.id
            INNER JOIN academic_years y ON e.academic_year_id = y.id
            WHERE s.is_consecutive_required = 1
        ";

        if ($academicYearId !== null) {
            $sql .= " AND e.academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        $entries = $stmt->fetchAll();
        $byDayAndSub = [];

        foreach ($entries as $e) {
            $key = $e['academic_year_id'] . '_' . $e['subject_id'] . '_' . $e['day_of_week'];
            $byDayAndSub[$key][] = (int)$e['period_index'];
        }

        $violations = [];
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        foreach ($entries as $e) {
            $key = $e['academic_year_id'] . '_' . $e['subject_id'] . '_' . $e['day_of_week'];
            $periods = $byDayAndSub[$key] ?? [];
            sort($periods);

            // If subject has only 1 period on this day, it's isolated
            if (count($periods) === 1) {
                $dayStr = $dayNames[$e['day_of_week']] ?? ('Day ' . $e['day_of_week']);
                $violations[] = [
                    'type' => 'lab_split',
                    'severity' => 'low',
                    'day_of_week' => (int)$e['day_of_week'],
                    'period_index' => (int)$e['period_index'],
                    'subject_id' => (int)$e['subject_id'],
                    'message' => "Lab Scheduling Alert: '{$e['subject_name']}' is scheduled as an isolated single period on {$dayStr}, Period {$e['period_index']}. Labs should be in consecutive blocks.",
                    'details' => $e
                ];
            }
        }

        return $violations;
    }

    /**
     * Real-time slot validation before manual saving/editing
     * 
     * @param int $academicYearId
     * @param int $dayOfWeek
     * @param int $periodIndex
     * @param int $facultyId
     * @param int $classroomId
     * @param int|null $excludeEntryId
     * @return array ['has_conflict' => bool, 'warnings' => array]
     */
    public function validateSlot(int $academicYearId, int $dayOfWeek, int $periodIndex, int $facultyId, int $classroomId, ?int $excludeEntryId = null): array {
        $warnings = [];

        // Check if faculty is occupied in another class at this day/period
        $sql = "
            SELECT e.id, y.year_name, s.subject_name
            FROM timetable_entries e
            INNER JOIN academic_years y ON e.academic_year_id = y.id
            INNER JOIN subjects s ON e.subject_id = s.id
            WHERE e.faculty_id = :facultyId 
              AND e.day_of_week = :day 
              AND e.period_index = :period
        ";
        if ($excludeEntryId) {
            $sql .= " AND e.id != :excludeId";
        }

        $stmt = $this->db->prepare($sql);
        $params = [':facultyId' => $facultyId, ':day' => $dayOfWeek, ':period' => $periodIndex];
        if ($excludeEntryId) $params[':excludeId'] = $excludeEntryId;
        $stmt->execute($params);

        if ($clash = $stmt->fetch()) {
            $warnings[] = [
                'type' => 'faculty_clash',
                'message' => "Faculty is already teaching '{$clash['subject_name']}' for {$clash['year_name']} during this slot."
            ];
        }

        // Check if classroom is occupied by another class at this day/period
        $sql2 = "
            SELECT e.id, y.year_name, s.subject_name, r.room_number
            FROM timetable_entries e
            INNER JOIN academic_years y ON e.academic_year_id = y.id
            INNER JOIN subjects s ON e.subject_id = s.id
            INNER JOIN classrooms r ON e.classroom_id = r.id
            WHERE e.classroom_id = :classroomId 
              AND e.day_of_week = :day 
              AND e.period_index = :period
        ";
        if ($excludeEntryId) {
            $sql2 .= " AND e.id != :excludeId";
        }

        $stmt2 = $this->db->prepare($sql2);
        $params2 = [':classroomId' => $classroomId, ':day' => $dayOfWeek, ':period' => $periodIndex];
        if ($excludeEntryId) $params2[':excludeId'] = $excludeEntryId;
        $stmt2->execute($params2);

        if ($roomClash = $stmt2->fetch()) {
            $warnings[] = [
                'type' => 'room_clash',
                'message' => "Classroom {$roomClash['room_number']} is already occupied by {$roomClash['year_name']} ({$roomClash['subject_name']}) during this slot."
            ];
        }

        // Check staff availability preference
        $stmt3 = $this->db->prepare("
            SELECT is_available, preference 
            FROM staff_availability 
            WHERE staff_id = :facultyId AND day_of_week = :day AND period_index = :period
        ");
        $stmt3->execute([':facultyId' => $facultyId, ':day' => $dayOfWeek, ':period' => $periodIndex]);
        $avail = $stmt3->fetch();
        if ($avail && (int)$avail['is_available'] === 0) {
            $warnings[] = [
                'type' => 'unavailable_slot',
                'message' => "Warning: Faculty has marked themselves as unavailable for this day/period."
            ];
        }

        return [
            'has_conflict' => (count($warnings) > 0),
            'has_hard_clash' => array_reduce($warnings, fn($carry, $w) => $carry || in_array($w['type'], ['faculty_clash', 'room_clash']), false),
            'warnings' => $warnings
        ];
    }
}
