<?php
/**
 * Smart Academic Timetable Management System
 * Timetable Quality Score Evaluator (0 - 100)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ConflictDetector.php';

class QualityEvaluator {
    private PDO $db;
    private ConflictDetector $detector;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDB();
        $this->detector = new ConflictDetector($this->db);
    }

    /**
     * Calculate comprehensive Timetable Quality Score (0 to 100)
     * 
     * @param int|null $academicYearId Optional specific academic year or all
     * @return array Score details, grade, metrics breakdown, and optimization tips
     */
    public function evaluateQuality(?int $academicYearId = null): array {
        // 1. Audit Conflicts
        $conflicts = $this->detector->auditTimetable(null, $academicYearId);
        $hardClashes = array_filter($conflicts, fn($c) => in_array($c['type'], ['faculty_clash', 'room_clash']));
        $hardClashCount = count($hardClashes);

        // 2. Syllabus Completeness (35 Points Max)
        $completenessMetric = $this->evaluateSyllabusCompleteness($academicYearId);

        // 3. Lab Rule Integrity (25 Points Max)
        $labMetric = $this->evaluateLabIntegrity($academicYearId);

        // 4. Faculty Workload Balance (20 Points Max)
        $workloadMetric = $this->evaluateWorkloadBalance();

        // 5. Daily Subject Diversity & Spread (20 Points Max)
        $diversityMetric = $this->evaluateSubjectDiversity($academicYearId);

        // Calculate Weighted Sum
        $rawScore = $completenessMetric['points'] 
                  + $labMetric['points'] 
                  + $workloadMetric['points'] 
                  + $diversityMetric['points'];

        // Penalize heavily for hard clashes
        $clashPenalty = $hardClashCount * 25;
        $finalScore = max(0, min(100, (int)round($rawScore - $clashPenalty)));

        // Determine Grade
        if ($finalScore >= 95) {
            $grade = 'A+';
            $gradeText = 'Exceptional Schedule';
            $badgeColor = 'success';
        } elseif ($finalScore >= 85) {
            $grade = 'A';
            $gradeText = 'Excellent & Balanced';
            $badgeColor = 'success';
        } elseif ($finalScore >= 75) {
            $grade = 'B+';
            $gradeText = 'Good Structure';
            $badgeColor = 'primary';
        } elseif ($finalScore >= 60) {
            $grade = 'B';
            $gradeText = 'Acceptable (Minor Gaps)';
            $badgeColor = 'warning';
        } else {
            $grade = 'C';
            $gradeText = 'Needs Optimization';
            $badgeColor = 'danger';
        }

        // Actionable Recommendations
        $recommendations = [];
        if ($hardClashCount > 0) {
            $recommendations[] = "Resolve {$hardClashCount} hard double-booking clash(es) immediately via the Timetable Editor or Regenerate.";
        }
        if ($completenessMetric['percentage'] < 100) {
            $recommendations[] = "Syllabus completeness is at {$completenessMetric['percentage']}%. Some subjects have unfulfilled weekly hours.";
        }
        if ($labMetric['percentage'] < 90) {
            $recommendations[] = "Ensure all 4-hour Lab subjects are booked in 2-period consecutive slots inside specialized computer/hardware labs.";
        }
        if ($diversityMetric['percentage'] < 80) {
            $recommendations[] = "Distribute theory subjects across different days rather than scheduling repeat theory periods on the same day.";
        }
        if (empty($recommendations)) {
            $recommendations[] = "All academic constraints, lab blocks, and faculty workloads are optimally balanced.";
        }

        return [
            'score' => $finalScore,
            'grade' => $grade,
            'grade_text' => $gradeText,
            'badge_color' => $badgeColor,
            'hard_clashes_count' => $hardClashCount,
            'total_conflicts_count' => count($conflicts),
            'metrics' => [
                'syllabus_completeness' => $completenessMetric,
                'lab_rule_integrity' => $labMetric,
                'workload_balance' => $workloadMetric,
                'subject_diversity' => $diversityMetric,
            ],
            'recommendations' => $recommendations,
            'conflicts_summary' => $conflicts
        ];
    }

    /**
     * Metric: Syllabus Completeness (Max 35 points)
     */
    private function evaluateSyllabusCompleteness(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                s.id, s.weekly_hours,
                COUNT(e.id) as allocated_hours
            FROM subjects s
            INNER JOIN academic_years y ON s.academic_year_id = y.id
            LEFT JOIN timetable_entries e ON s.id = e.subject_id
            WHERE s.is_active = 1 AND y.is_active = 1
        ";
        if ($academicYearId !== null) {
            $sql .= " AND s.academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql . " GROUP BY s.id");
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql . " GROUP BY s.id");
        }

        $totalRequired = 0;
        $totalAllocated = 0;

        while ($row = $stmt->fetch()) {
            $req = (int)$row['weekly_hours'];
            $alloc = (int)$row['allocated_hours'];
            $totalRequired += $req;
            $totalAllocated += min($req, $alloc); // Capped at required
        }

        $percentage = ($totalRequired > 0) ? round(($totalAllocated / $totalRequired) * 100, 1) : 100;
        $points = round(($percentage / 100) * 35, 1);

        return [
            'name' => 'Syllabus Quota Completion',
            'points' => $points,
            'max_points' => 35,
            'percentage' => $percentage,
            'allocated_hours' => $totalAllocated,
            'required_hours' => $totalRequired,
            'status' => ($percentage >= 98) ? 'Optimal' : (($percentage >= 80) ? 'Good' : 'Needs Review')
        ];
    }

    /**
     * Metric: Lab Consecutive & Room Integrity (Max 25 points)
     */
    private function evaluateLabIntegrity(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e.academic_year_id, e.subject_id, e.day_of_week, e.period_index,
                s.subject_name, s.category, s.is_consecutive_required,
                r.room_type
            FROM timetable_entries e
            INNER JOIN subjects s ON e.subject_id = s.id
            INNER JOIN classrooms r ON e.classroom_id = r.id
            WHERE s.category IN ('Major Lab', 'Skill Lab')
        ";
        if ($academicYearId !== null) {
            $sql .= " AND e.academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        $labEntries = $stmt->fetchAll();
        $totalLabPeriods = count($labEntries);

        if ($totalLabPeriods === 0) {
            return [
                'name' => 'Lab Rule Compliance',
                'points' => 25,
                'max_points' => 25,
                'percentage' => 100,
                'status' => 'N/A'
            ];
        }

        $validConsecutive = 0;
        $validRoom = 0;

        // Group by Year, Subject, Day
        $grouped = [];
        foreach ($labEntries as $e) {
            $key = $e['academic_year_id'] . '_' . $e['subject_id'] . '_' . $e['day_of_week'];
            $grouped[$key][] = (int)$e['period_index'];
            if (str_contains($e['room_type'], 'Lab')) {
                $validRoom++;
            }
        }

        foreach ($grouped as $periods) {
            sort($periods);
            if (count($periods) >= 2) {
                // Check if consecutive
                $isConsec = true;
                for ($i = 0; $i < count($periods) - 1; $i++) {
                    if ($periods[$i+1] - $periods[$i] !== 1) {
                        $isConsec = false;
                        break;
                    }
                }
                if ($isConsec) {
                    $validConsecutive += count($periods);
                }
            }
        }

        $consecRatio = $validConsecutive / $totalLabPeriods;
        $roomRatio = $validRoom / $totalLabPeriods;
        $compositeRatio = ($consecRatio * 0.6) + ($roomRatio * 0.4);

        $percentage = round($compositeRatio * 100, 1);
        $points = round(($percentage / 100) * 25, 1);

        return [
            'name' => 'Lab Rule Compliance',
            'points' => $points,
            'max_points' => 25,
            'percentage' => $percentage,
            'status' => ($percentage >= 90) ? 'Optimal' : (($percentage >= 70) ? 'Good' : 'Needs Review')
        ];
    }

    /**
     * Metric: Workload Balance across Faculty (Max 20 points)
     */
    private function evaluateWorkloadBalance(): array {
        $sql = "
            SELECT 
                f.id, f.full_name, f.max_weekly_hours,
                COUNT(e.id) as assigned_hours
            FROM staff f
            LEFT JOIN timetable_entries e ON f.id = e.faculty_id
            WHERE f.is_active = 1
            GROUP BY f.id
        ";
        $stmt = $this->db->query($sql);
        $facultyLoads = $stmt->fetchAll();

        if (empty($facultyLoads)) {
            return [
                'name' => 'Workload Equilibrium',
                'points' => 20,
                'max_points' => 20,
                'percentage' => 100,
                'status' => 'Optimal'
            ];
        }

        $overloaded = 0;
        $ratios = [];

        foreach ($facultyLoads as $fl) {
            $assigned = (int)$fl['assigned_hours'];
            $max = (int)$fl['max_weekly_hours'];
            if ($assigned > $max) {
                $overloaded++;
            }
            $ratios[] = ($max > 0) ? min(1.0, $assigned / $max) : 1.0;
        }

        $avgRatio = array_sum($ratios) / count($ratios);
        $penalty = ($overloaded * 0.25);
        $effectiveScore = max(0, ($avgRatio - $penalty));

        $percentage = round($effectiveScore * 100, 1);
        $points = round(($percentage / 100) * 20, 1);

        return [
            'name' => 'Workload Equilibrium',
            'points' => $points,
            'max_points' => 20,
            'percentage' => $percentage,
            'status' => ($overloaded === 0 && $percentage >= 80) ? 'Optimal' : (($overloaded === 0) ? 'Good' : 'Overloaded Staff')
        ];
    }

    /**
     * Metric: Daily Subject Spread & Diversity (Max 20 points)
     */
    private function evaluateSubjectDiversity(?int $academicYearId = null): array {
        $sql = "
            SELECT 
                e.academic_year_id, e.day_of_week, e.subject_id,
                s.category, COUNT(e.id) as daily_count
            FROM timetable_entries e
            INNER JOIN subjects s ON e.subject_id = s.id
            WHERE s.category NOT IN ('Major Lab', 'Skill Lab')
        ";
        if ($academicYearId !== null) {
            $sql .= " AND e.academic_year_id = :yearId";
            $stmt = $this->db->prepare($sql . " GROUP BY e.academic_year_id, e.day_of_week, e.subject_id");
            $stmt->execute([':yearId' => $academicYearId]);
        } else {
            $stmt = $this->db->query($sql . " GROUP BY e.academic_year_id, e.day_of_week, e.subject_id");
        }

        $theoryClustered = 0;
        $totalDailyTheory = 0;

        while ($row = $stmt->fetch()) {
            $count = (int)$row['daily_count'];
            $totalDailyTheory++;
            // If more than 2 theory periods of same subject in single day, penalize
            if ($count > 2) {
                $theoryClustered++;
            }
        }

        $diversityRatio = ($totalDailyTheory > 0) ? (1.0 - ($theoryClustered / $totalDailyTheory)) : 1.0;
        $percentage = round($diversityRatio * 100, 1);
        $points = round(($percentage / 100) * 20, 1);

        return [
            'name' => 'Subject Spread & Diversity',
            'points' => $points,
            'max_points' => 20,
            'percentage' => $percentage,
            'status' => ($percentage >= 90) ? 'Optimal' : 'Needs Better Spread'
        ];
    }
}
