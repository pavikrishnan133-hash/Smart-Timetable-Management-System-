<?php
/**
 * Smart Academic Timetable Management System
 * Core Automated Timetable Generation Engine
 * Constraint Satisfaction & Heuristic Placement Solver
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ConflictDetector.php';
require_once __DIR__ . '/QualityEvaluator.php';

class TimetableEngine {
    private PDO $db;
    private ConflictDetector $detector;
    private QualityEvaluator $evaluator;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDB();
        $this->detector = new ConflictDetector($this->db);
        $this->evaluator = new QualityEvaluator($this->db);
    }

    /**
     * Generate complete timetables for all active academic years or a specific year
     * 
     * @param int|null $targetYearId Target year ID or null for all active years
     * @param array $options Configuration flags (e.g. preserve_locked, force_regenerate)
     * @return array Results summary including quality score, conflicts, and generated counts
     */
    public function generate(?int $targetYearId = null, array $options = []): array {
        $preserveLocked = $options['preserve_locked'] ?? true;
        
        // 1. Fetch Schedule Settings & Periods
        $settings = $this->getScheduleSettings();
        $periods = $this->getPeriods();
        $numPeriods = count($periods);
        $daysCount = (int)($settings['working_days_count'] ?? 5);

        // 2. Fetch Academic Years to generate for
        $years = $this->getAcademicYears($targetYearId);
        if (empty($years)) {
            throw new Exception("No active academic years found to generate timetables.");
        }

        // 3. Fetch Classrooms & Labs
        $classrooms = $this->getClassrooms();
        if (empty($classrooms)) {
            throw new Exception("No classrooms or labs configured in the system.");
        }

        // 4. Fetch Staff & Availability Matrix
        $staffMap = $this->getStaffMap();
        $availabilityMap = $this->getStaffAvailabilityMap();

        // 5. Initialize Global Occupancy & Tracking Structures
        $facultyOccupied = []; // [faculty_id][day][period] => true
        $roomOccupied = [];    // [classroom_id][day][period] => true
        $classOccupied = [];   // [academic_year_id][day][period] => true
        $facultyWorkload = []; // [faculty_id] => total_periods
        $subjectPlaced = [];   // [subject_id] => total_periods
        $lockedEntries = [];

        foreach ($staffMap as $sid => $s) {
            $facultyWorkload[$sid] = 0;
        }

        // If preserving locked entries, load them into occupancy maps
        if ($preserveLocked) {
            $lockedEntries = $this->getLockedEntries($targetYearId);
            foreach ($lockedEntries as $le) {
                $yId = (int)$le['academic_year_id'];
                $d = (int)$le['day_of_week'];
                $p = (int)$le['period_index'];
                $fId = (int)$le['faculty_id'];
                $rId = (int)$le['classroom_id'];
                $sId = (int)$le['subject_id'];

                $facultyOccupied[$fId][$d][$p] = true;
                $roomOccupied[$rId][$d][$p] = true;
                $classOccupied[$yId][$d][$p] = true;
                $facultyWorkload[$fId] = ($facultyWorkload[$fId] ?? 0) + 1;
                $subjectPlaced[$sId] = ($subjectPlaced[$sId] ?? 0) + 1;
            }
        }

        // Begin Transaction
        $this->db->beginTransaction();

        try {
            // Clear unlocked existing entries for the target years
            if ($targetYearId !== null) {
                if ($preserveLocked) {
                    $stmt = $this->db->prepare("DELETE FROM timetable_entries WHERE academic_year_id = :yearId AND is_locked = 0");
                    $stmt->execute([':yearId' => $targetYearId]);
                } else {
                    $stmt = $this->db->prepare("DELETE FROM timetable_entries WHERE academic_year_id = :yearId");
                    $stmt->execute([':yearId' => $targetYearId]);
                }
            } else {
                if ($preserveLocked) {
                    $this->db->exec("DELETE FROM timetable_entries WHERE is_locked = 0");
                } else {
                    $this->db->exec("DELETE FROM timetable_entries");
                }
            }

            $generatedEntries = [];

            // 6. Loop through each Academic Year & allocate subjects
            foreach ($years as $year) {
                $yearId = (int)$year['id'];
                $subjects = $this->getSubjectsForYear($yearId);

                // Split into Lab/Practical subjects and Theory subjects
                $labSubjects = [];
                $theorySubjects = [];

                foreach ($subjects as $sub) {
                    $sId = (int)$sub['id'];
                    $alreadyPlaced = $subjectPlaced[$sId] ?? 0;
                    $remainingHours = max(0, (int)$sub['weekly_hours'] - $alreadyPlaced);
                    $sub['remaining_hours'] = $remainingHours;

                    if ($sub['remaining_hours'] <= 0) continue;

                    if (in_array($sub['category'], ['Major Lab', 'Skill Lab']) || (int)$sub['is_consecutive_required'] === 1) {
                        $labSubjects[] = $sub;
                    } else {
                        $theorySubjects[] = $sub;
                    }
                }

                // Identify dedicated classroom for this year
                $defaultClassroom = $this->findDefaultRoomForYear($classrooms, $year['year_name']);

                // === PASS 1: Allocate Lab Subjects in 2-Period Blocks ===
                foreach ($labSubjects as &$labSub) {
                    $subId = (int)$labSub['id'];
                    $facultyId = (int)$labSub['assigned_faculty_id'];
                    $preferredType = $labSub['preferred_room_type'] ?: 'Computer Lab';

                    $blockSize = 2;
                    $blocksNeeded = (int)ceil($labSub['remaining_hours'] / $blockSize);

                    for ($b = 0; $b < $blocksNeeded && $labSub['remaining_hours'] > 0; $b++) {
                        $currentBlockSize = min($blockSize, $labSub['remaining_hours']);
                        $placed = false;

                        // Try finding best day & consecutive period pair
                        for ($day = 1; $day <= $daysCount; $day++) {
                            if ($placed) break;

                            $candidatePairs = $this->getConsecutiveSlotPairs($periods, $currentBlockSize);
                            shuffle($candidatePairs);

                            foreach ($candidatePairs as $pair) {
                                $p1 = $pair[0];
                                $p2 = $pair[1] ?? $p1;

                                // Find an available lab room for both periods
                                $labRoom = $this->findFreeRoomForBlock($classrooms, $preferredType, $roomOccupied, $day, $p1, $p2);
                                if (!$labRoom) {
                                    // Fallback to any Lab
                                    $labRoom = $this->findFreeRoomForBlock($classrooms, 'Computer Lab', $roomOccupied, $day, $p1, $p2)
                                            ?: $this->findFreeRoomForBlock($classrooms, 'General Lab', $roomOccupied, $day, $p1, $p2);
                                }
                                if (!$labRoom) continue;

                                // Check class & faculty occupancy
                                $canPlace = true;
                                for ($p = $p1; $p <= $p2; $p++) {
                                    if (!empty($classOccupied[$yearId][$day][$p])) { $canPlace = false; break; }
                                    if (!empty($facultyOccupied[$facultyId][$day][$p])) { $canPlace = false; break; }
                                    // Check staff availability
                                    $isAvail = $availabilityMap[$facultyId][$day][$p] ?? 1;
                                    if ($isAvail == 0) { $canPlace = false; break; }
                                }

                                if ($canPlace) {
                                    // Commit block placement
                                    for ($p = $p1; $p <= $p2; $p++) {
                                        $classOccupied[$yearId][$day][$p] = true;
                                        $facultyOccupied[$facultyId][$day][$p] = true;
                                        $roomOccupied[$labRoom['id']][$day][$p] = true;
                                        $facultyWorkload[$facultyId] = ($facultyWorkload[$facultyId] ?? 0) + 1;
                                        $subjectPlaced[$subId] = ($subjectPlaced[$subId] ?? 0) + 1;

                                        $generatedEntries[] = [
                                            'academic_year_id' => $yearId,
                                            'day_of_week' => $day,
                                            'period_index' => $p,
                                            'subject_id' => $subId,
                                            'faculty_id' => $facultyId,
                                            'classroom_id' => $labRoom['id'],
                                            'is_locked' => 0
                                        ];
                                    }

                                    $labSub['remaining_hours'] -= $currentBlockSize;
                                    $placed = true;
                                    break;
                                }
                            }
                        }
                    }
                }
                unset($labSub);

                // === PASS 2: Allocate Theory Subjects Evenly ===
                usort($theorySubjects, fn($a, $b) => $b['remaining_hours'] <=> $a['remaining_hours']);
                $dailySubCount = [];

                foreach ($theorySubjects as &$thSub) {
                    $subId = (int)$thSub['id'];
                    $facultyId = (int)$thSub['assigned_faculty_id'];

                    while ($thSub['remaining_hours'] > 0) {
                        $placed = false;

                        $dayOrder = range(1, $daysCount);
                        usort($dayOrder, function($d1, $d2) use ($dailySubCount, $subId) {
                            $c1 = $dailySubCount[$subId][$d1] ?? 0;
                            $c2 = $dailySubCount[$subId][$d2] ?? 0;
                            return $c1 <=> $c2;
                        });

                        foreach ($dayOrder as $day) {
                            if ($placed) break;

                            for ($period = 1; $period <= $numPeriods; $period++) {
                                if (($dailySubCount[$subId][$day] ?? 0) >= 2 && $thSub['remaining_hours'] <= 4) {
                                    continue;
                                }

                                if (!empty($classOccupied[$yearId][$day][$period])) continue;
                                if (!empty($facultyOccupied[$facultyId][$day][$period])) continue;
                                
                                $chosenRoom = $defaultClassroom;
                                if (!empty($roomOccupied[$chosenRoom['id']][$day][$period])) {
                                    $chosenRoom = $this->findFreeRoom($classrooms, 'Theory Classroom', $roomOccupied, $day, $period);
                                }
                                if (!$chosenRoom) continue;

                                $isAvail = $availabilityMap[$facultyId][$day][$period] ?? 1;
                                if ($isAvail == 0) continue;

                                // Place slot
                                $classOccupied[$yearId][$day][$period] = true;
                                $facultyOccupied[$facultyId][$day][$period] = true;
                                $roomOccupied[$chosenRoom['id']][$day][$period] = true;
                                $facultyWorkload[$facultyId] = ($facultyWorkload[$facultyId] ?? 0) + 1;
                                $subjectPlaced[$subId] = ($subjectPlaced[$subId] ?? 0) + 1;
                                $dailySubCount[$subId][$day] = ($dailySubCount[$subId][$day] ?? 0) + 1;

                                $generatedEntries[] = [
                                    'academic_year_id' => $yearId,
                                    'day_of_week' => $day,
                                    'period_index' => $period,
                                    'subject_id' => $subId,
                                    'faculty_id' => $facultyId,
                                    'classroom_id' => $chosenRoom['id'],
                                    'is_locked' => 0
                                ];

                                $thSub['remaining_hours']--;
                                $placed = true;
                                break;
                            }
                        }

                        if (!$placed) {
                            // If not placed in optimal mode, break to fallback relaxation
                            break;
                        }
                    }
                }
                unset($thSub);

                // === PASS 3: Fallback Relaxation for Any Remaining Unplaced Subject Hours ===
                $allSubjectsForYear = array_merge($labSubjects, $theorySubjects);
                foreach ($allSubjectsForYear as &$remSub) {
                    $subId = (int)$remSub['id'];
                    $facultyId = (int)$remSub['assigned_faculty_id'];

                    while ($remSub['remaining_hours'] > 0) {
                        $placed = false;

                        for ($day = 1; $day <= $daysCount; $day++) {
                            if ($placed) break;
                            for ($period = 1; $period <= $numPeriods; $period++) {
                                if (!empty($classOccupied[$yearId][$day][$period])) continue;
                                if (!empty($facultyOccupied[$facultyId][$day][$period])) continue;

                                // Find ANY free room
                                $anyRoom = $this->findAnyFreeRoom($classrooms, $roomOccupied, $day, $period);
                                if (!$anyRoom) continue;

                                // Place fallback
                                $classOccupied[$yearId][$day][$period] = true;
                                $facultyOccupied[$facultyId][$day][$period] = true;
                                $roomOccupied[$anyRoom['id']][$day][$period] = true;
                                $facultyWorkload[$facultyId] = ($facultyWorkload[$facultyId] ?? 0) + 1;
                                $subjectPlaced[$subId] = ($subjectPlaced[$subId] ?? 0) + 1;

                                $generatedEntries[] = [
                                    'academic_year_id' => $yearId,
                                    'day_of_week' => $day,
                                    'period_index' => $period,
                                    'subject_id' => $subId,
                                    'faculty_id' => $facultyId,
                                    'classroom_id' => $anyRoom['id'],
                                    'is_locked' => 0
                                ];

                                $remSub['remaining_hours']--;
                                $placed = true;
                                break;
                            }
                        }

                        if (!$placed) break;
                    }
                }
                unset($remSub);
            }

            // 7. Insert all generated entries into database
            $insertStmt = $this->db->prepare("
                INSERT INTO timetable_entries 
                (`timetable_id`, `academic_year_id`, `day_of_week`, `period_index`, `subject_id`, `faculty_id`, `classroom_id`, `is_locked`)
                VALUES (:ttId, :yearId, :day, :period, :subId, :facId, :roomId, :locked)
            ");

            // Create/Update Timetable master records for each year
            $timetablesSummary = [];
            foreach ($years as $year) {
                $yId = (int)$year['id'];
                
                $ttStmt = $this->db->prepare("SELECT id FROM timetables WHERE academic_year_id = :yearId LIMIT 1");
                $ttStmt->execute([':yearId' => $yId]);
                $existingTt = $ttStmt->fetch();

                if ($existingTt) {
                    $timetableId = (int)$existingTt['id'];
                    $updateMaster = $this->db->prepare("
                        UPDATE timetables 
                        SET title = :title, updated_at = NOW(), version = version + 1
                        WHERE id = :id
                    ");
                    $updateMaster->execute([
                        ':title' => "Timetable - {$year['year_name']} (Auto-Generated)",
                        ':id' => $timetableId
                    ]);
                } else {
                    $createMaster = $this->db->prepare("
                        INSERT INTO timetables (`title`, `academic_year_id`, `academic_session`, `version`, `is_published`)
                        VALUES (:title, :yearId, :session, 1, 1)
                    ");
                    $createMaster->execute([
                        ':title' => "Timetable - {$year['year_name']} (Auto-Generated)",
                        ':yearId' => $yId,
                        ':session' => $year['academic_session'] ?? '2026-2027'
                    ]);
                    $timetableId = (int)$this->db->lastInsertId();
                }

                $timetablesSummary[$yId] = $timetableId;
            }

            // Execute batch inserts
            foreach ($generatedEntries as $entry) {
                $yId = $entry['academic_year_id'];
                $ttId = $timetablesSummary[$yId] ?? 1;

                $insertStmt->execute([
                    ':ttId' => $ttId,
                    ':yearId' => $yId,
                    ':day' => $entry['day_of_week'],
                    ':period' => $entry['period_index'],
                    ':subId' => $entry['subject_id'],
                    ':facId' => $entry['faculty_id'],
                    ':roomId' => $entry['classroom_id'],
                    ':locked' => $entry['is_locked']
                ]);
            }

            $this->db->commit();

            // 8. Run Audit and Score Evaluation
            $qualityResults = $this->evaluator->evaluateQuality($targetYearId);
            $auditConflicts = $this->detector->auditTimetable(null, $targetYearId);

            // Store conflict logs
            $this->db->exec("DELETE FROM conflict_logs");
            $logStmt = $this->db->prepare("
                INSERT INTO conflict_logs 
                (`conflict_type`, `severity`, `day_of_week`, `period_index`, `faculty_id`, `classroom_id`, `subject_id`, `message`, `details_json`)
                VALUES (:type, :severity, :day, :period, :facId, :roomId, :subId, :msg, :details)
            ");
            foreach ($auditConflicts as $conf) {
                $logStmt->execute([
                    ':type' => $conf['type'],
                    ':severity' => $conf['severity'] ?? 'high',
                    ':day' => $conf['day_of_week'] ?? null,
                    ':period' => $conf['period_index'] ?? null,
                    ':facId' => $conf['faculty_id'] ?? null,
                    ':roomId' => $conf['classroom_id'] ?? null,
                    ':subId' => $conf['subject_id'] ?? null,
                    ':msg' => $conf['message'],
                    ':details' => json_encode($conf['details'] ?? [])
                ]);
            }

            // Update Quality Score in Master Timetables
            foreach ($timetablesSummary as $yId => $ttId) {
                $updScore = $this->db->prepare("
                    UPDATE timetables 
                    SET quality_score = :score, quality_details = :details, total_conflicts = :conflicts
                    WHERE id = :ttId
                ");
                $updScore->execute([
                    ':score' => $qualityResults['score'],
                    ':details' => json_encode($qualityResults),
                    ':conflicts' => count($auditConflicts),
                    ':ttId' => $ttId
                ]);
            }

            return [
                'success' => true,
                'total_generated' => count($generatedEntries),
                'academic_years_count' => count($years),
                'quality_score' => $qualityResults['score'],
                'quality_grade' => $qualityResults['grade'],
                'quality_details' => $qualityResults,
                'total_conflicts' => count($auditConflicts),
                'conflicts' => $auditConflicts,
                'message' => "Timetable generation completed successfully with Quality Score of {$qualityResults['score']}/100."
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Find a room of specific type free across multiple consecutive periods
     */
    private function findFreeRoomForBlock(array $classrooms, string $roomType, array $roomOccupied, int $day, int $p1, int $p2): ?array {
        foreach ($classrooms as $room) {
            if ($room['room_type'] === $roomType && (int)$room['is_active'] === 1) {
                $rId = (int)$room['id'];
                $isFree = true;
                for ($p = $p1; $p <= $p2; $p++) {
                    if (!empty($roomOccupied[$rId][$day][$p])) {
                        $isFree = false;
                        break;
                    }
                }
                if ($isFree) return $room;
            }
        }
        return null;
    }

    private function findAnyFreeRoom(array $classrooms, array $roomOccupied, int $day, int $period): ?array {
        foreach ($classrooms as $room) {
            $rId = (int)$room['id'];
            if ((int)$room['is_active'] === 1 && empty($roomOccupied[$rId][$day][$period])) {
                return $room;
            }
        }
        return null;
    }

    private function getConsecutiveSlotPairs(array $periods, int $blockSize): array {
        $pairs = [];
        $pCount = count($periods);

        for ($i = 0; $i < $pCount; $i++) {
            $p1 = (int)$periods[$i]['period_index'];
            if ($blockSize === 1) {
                $pairs[] = [$p1];
                continue;
            }

            if ($i + 1 < $pCount) {
                $isBreakAfter = (int)($periods[$i]['is_break_after'] ?? 0);
                if ($isBreakAfter === 0) {
                    $p2 = (int)$periods[$i+1]['period_index'];
                    $pairs[] = [$p1, $p2];
                }
            }
        }

        return $pairs;
    }

    private function findDefaultRoomForYear(array $classrooms, string $yearName): ?array {
        if (str_contains($yearName, '1st')) {
            $match = array_filter($classrooms, fn($r) => str_contains($r['room_number'], '201') || str_contains($r['room_name'], '1st'));
            if (!empty($match)) return reset($match);
        } elseif (str_contains($yearName, '2nd')) {
            $match = array_filter($classrooms, fn($r) => str_contains($r['room_number'], '202') || str_contains($r['room_name'], '2nd'));
            if (!empty($match)) return reset($match);
        } elseif (str_contains($yearName, '3rd')) {
            $match = array_filter($classrooms, fn($r) => str_contains($r['room_number'], '203') || str_contains($r['room_name'], '3rd'));
            if (!empty($match)) return reset($match);
        }
        return $this->findSuitableRoom($classrooms, 'Theory Classroom');
    }

    private function findSuitableRoom(array $classrooms, string $preferredType, string $fallbackType = 'Theory Classroom'): array {
        foreach ($classrooms as $room) {
            if ($room['room_type'] === $preferredType && (int)$room['is_active'] === 1) {
                return $room;
            }
        }
        foreach ($classrooms as $room) {
            if ($room['room_type'] === $fallbackType && (int)$room['is_active'] === 1) {
                return $room;
            }
        }
        return $classrooms[0];
    }

    private function findFreeRoom(array $classrooms, string $roomType, array $roomOccupied, int $day, int $period): ?array {
        foreach ($classrooms as $room) {
            $rId = (int)$room['id'];
            if ($room['room_type'] === $roomType && (int)$room['is_active'] === 1) {
                if (empty($roomOccupied[$rId][$day][$period])) {
                    return $room;
                }
            }
        }
        return null;
    }

    private function getScheduleSettings(): array {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM schedule_settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function getPeriods(): array {
        $stmt = $this->db->query("SELECT * FROM periods ORDER BY period_index ASC");
        return $stmt->fetchAll();
    }

    private function getAcademicYears(?int $targetYearId): array {
        if ($targetYearId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM academic_years WHERE id = :id AND is_active = 1");
            $stmt->execute([':id' => $targetYearId]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT * FROM academic_years WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    private function getClassrooms(): array {
        $stmt = $this->db->query("SELECT * FROM classrooms WHERE is_active = 1 ORDER BY room_type DESC, id ASC");
        return $stmt->fetchAll();
    }

    private function getStaffMap(): array {
        $stmt = $this->db->query("SELECT * FROM staff WHERE is_active = 1");
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private function getStaffAvailabilityMap(): array {
        $stmt = $this->db->query("SELECT staff_id, day_of_week, period_index, is_available FROM staff_availability");
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(int)$row['staff_id']][(int)$row['day_of_week']][(int)$row['period_index']] = (int)$row['is_available'];
        }
        return $map;
    }

    private function getSubjectsForYear(int $yearId): array {
        $stmt = $this->db->prepare("SELECT * FROM subjects WHERE academic_year_id = :yId AND is_active = 1 ORDER BY weekly_hours DESC");
        $stmt->execute([':yId' => $yearId]);
        return $stmt->fetchAll();
    }

    private function getLockedEntries(?int $yearId): array {
        $sql = "SELECT * FROM timetable_entries WHERE is_locked = 1";
        if ($yearId !== null) {
            $sql .= " AND academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $yearId]);
        } else {
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll();
    }
}
