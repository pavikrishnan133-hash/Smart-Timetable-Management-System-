<?php
/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Automated Full-Stack Verification & API Test Suite
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/TimetableEngine.php';
require_once __DIR__ . '/../classes/ConflictDetector.php';
require_once __DIR__ . '/../classes/QualityEvaluator.php';

echo "====================================================================\n";
echo "  SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM - TEST RUNNER\n";
echo "====================================================================\n\n";

$db = getDB();
$passed = 0;
$total = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] {$name}\n";
    } else {
        echo " [FAIL] {$name} - Details: {$details}\n";
    }
}

// 1. Test Database Schema & Seed Records
echo "--- 1. Testing Database & Seed Records ---\n";
$admin = $db->query("SELECT * FROM admins WHERE username = 'admin'")->fetch();
assertTest("Default Admin Exists", !empty($admin), "Admin not found in DB");
assertTest("Admin Password Hash Valid", password_verify('admin123', $admin['password_hash']), "Password hash verification failed");

$staffCount = (int)$db->query("SELECT COUNT(*) FROM staff")->fetchColumn();
assertTest("Staff Seed Count (>= 7)", $staffCount >= 7, "Found {$staffCount} staff");

$subCount = (int)$db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
assertTest("Subjects Seed Count (>= 21)", $subCount >= 21, "Found {$subCount} subjects");

$yearsCount = (int)$db->query("SELECT COUNT(*) FROM academic_years")->fetchColumn();
assertTest("Academic Years Seed Count (>= 3)", $yearsCount >= 3, "Found {$yearsCount} years");

$roomsCount = (int)$db->query("SELECT COUNT(*) FROM classrooms")->fetchColumn();
assertTest("Classrooms Seed Count (>= 6)", $roomsCount >= 6, "Found {$roomsCount} rooms");

$periodsCount = (int)$db->query("SELECT COUNT(*) FROM periods")->fetchColumn();
assertTest("Periods Seed Count (>= 5)", $periodsCount >= 5, "Found {$periodsCount} periods");

// 2. Test Timetable Generation Engine
echo "\n--- 2. Testing Automated Constraint Satisfaction Generator ---\n";
$engine = new TimetableEngine($db);
$genResult = $engine->generate();

assertTest("Timetable Engine Execution", $genResult['success'] === true, "Generation returned failure");
assertTest("Total Slots Generated (>= 70)", $genResult['total_generated'] >= 70, "Generated {$genResult['total_generated']} slots");
assertTest("Zero Hard Clashes (Faculty & Room)", $genResult['quality_details']['hard_clashes_count'] === 0, "Hard clashes count: {$genResult['quality_details']['hard_clashes_count']}");
assertTest("High Timetable Quality Score (>= 75)", $genResult['quality_score'] >= 75, "Quality Score is {$genResult['quality_score']}");

// 3. Test Conflict Detector
echo "\n--- 3. Testing Conflict Detector ---\n";
$detector = new ConflictDetector($db);
$facultyClashes = $detector->detectFacultyClashes();
assertTest("Conflict Detector: Zero Faculty Clashes", count($facultyClashes) === 0, "Found " . count($facultyClashes) . " faculty clashes");

$roomClashes = $detector->detectRoomClashes();
assertTest("Conflict Detector: Zero Room Clashes", count($roomClashes) === 0, "Found " . count($roomClashes) . " room clashes");

$workloadOverruns = $detector->detectWorkloadOverruns();
assertTest("Conflict Detector: Workload Limits Respected", count($workloadOverruns) === 0, "Found " . count($workloadOverruns) . " overloaded faculty");

// 4. Test Quality Score Evaluator
echo "\n--- 4. Testing Timetable Quality Score Evaluator ---\n";
$evaluator = new QualityEvaluator($db);
$quality = $evaluator->evaluateQuality();

assertTest("Quality Score Range (0 - 100)", $quality['score'] >= 0 && $quality['score'] <= 100, "Score: {$quality['score']}");
assertTest("Quality Metrics Breakdown Present", isset($quality['metrics']['syllabus_completeness']) && isset($quality['metrics']['lab_rule_integrity']), "Metrics missing");
assertTest("Grade Calculation (A+, A, or B+)", in_array($quality['grade'], ['A+', 'A', 'B+']), "Grade: {$quality['grade']}");

// 5. Test Live Slot Clash Validation
echo "\n--- 5. Testing Live Slot Clash Validation ---\n";
// Let's test a slot that is already booked vs a free slot
$firstEntry = $db->query("SELECT * FROM timetable_entries LIMIT 1")->fetch();
if ($firstEntry) {
    // Attempting to assign same faculty to another class at the same day & period should detect a clash
    $otherYear = ($firstEntry['academic_year_id'] == 1) ? 2 : 1;
    $valResult = $detector->validateSlot(
        $otherYear,
        (int)$firstEntry['day_of_week'],
        (int)$firstEntry['period_index'],
        (int)$firstEntry['faculty_id'],
        (int)$firstEntry['classroom_id']
    );
    assertTest("Real-time Slot Clash Detector Warns on Busy Faculty/Room", $valResult['has_conflict'] === true, "Validator did not catch double booking");
}

// 6. Test Students & Faculty APIs
echo "\n--- 6. Testing Students & Faculty Data Operations ---\n";
$studentsCount = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
assertTest("Students Seed Count (>= 6)", $studentsCount >= 6, "Found {$studentsCount} students");

$testReg = "TEST_REG_" . time();
$testEmail = "test_student_" . time() . "@student.bvoc.edu";
$insertStmt = $db->prepare("INSERT INTO students (register_number, full_name, email, phone, academic_year_id, section, is_active) VALUES (:reg, :name, :email, :phone, 1, 'Section A', 1)");
$insertStmt->execute([':reg' => $testReg, ':name' => 'Test Student', ':email' => $testEmail, ':phone' => '1234567890']);
$createdStudentId = (int)$db->lastInsertId();
assertTest("Student Creation in Database", $createdStudentId > 0, "Failed to insert test student");

$fetchedStudent = $db->query("SELECT * FROM students WHERE id = {$createdStudentId}")->fetch();
assertTest("Student Retrieval by ID", !empty($fetchedStudent) && $fetchedStudent['register_number'] === $testReg, "Failed to fetch student");

$db->exec("DELETE FROM students WHERE id = {$createdStudentId}");
$deletedCheck = $db->query("SELECT COUNT(*) FROM students WHERE id = {$createdStudentId}")->fetchColumn();
assertTest("Student Clean Deletion", $deletedCheck == 0, "Student was not deleted");

// 7. Test File & Asset Integrity
echo "\n--- 7. Testing File & Asset Integrity ---\n";
$filesToCheck = [
    'assets/css/style.css',
    'assets/css/print.css',
    'assets/images/logo.svg',
    'assets/js/app.js',
    'assets/js/dashboard.js',
    'assets/js/staff.js',
    'assets/js/subjects.js',
    'assets/js/classrooms.js',
    'assets/js/schedule.js',
    'assets/js/generator.js',
    'assets/js/timetable-view.js',
    'assets/js/reports.js',
    'auth/login.php',
    'auth/logout.php',
    'admin/dashboard.php',
    'admin/staff.php',
    'admin/subjects.php',
    'admin/academic_years.php',
    'admin/classrooms.php',
    'admin/schedule_settings.php',
    'admin/generate_timetable.php',
    'admin/timetable.php',
    'admin/reports.php',
    'api/auth.php',
    'api/staff.php',
    'api/students.php',
    'api/subjects.php',
    'api/academic_years.php',
    'api/classrooms.php',
    'api/schedule_settings.php',
    'api/generator.php',
    'api/timetable.php',
    'api/reports.php',
    'database/schema.sql',
    'database/seed_data.sql',
    'database/init_db.php',
    'index.php',
    'README.md'
];

foreach ($filesToCheck as $f) {
    $fullPath = __DIR__ . '/../' . $f;
    assertTest("File Exists: {$f}", file_exists($fullPath) && filesize($fullPath) > 0, "File missing or empty");
}

echo "\n====================================================================\n";
echo "  TEST SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed / $total) * 100, 1) . "%)\n";
echo "====================================================================\n";
