<?php
/**
 * Test Dashboard Navigation, Drawers & API Operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_full_name'] = 'Administrator';
$_SESSION['admin_role'] = 'Admin';
$_SESSION['logged_in'] = true;

echo "====================================================================\n";
echo "  DASHBOARD & DRAWER INTEGRATION VERIFICATION SUITE\n";
echo "====================================================================\n\n";

$passed = 0;
$total = 0;

function assertCheck(string $name, bool $condition, string $details = ''): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] {$name}\n";
    } else {
        echo " [FAIL] {$name} - Details: {$details}\n";
    }
}

// 1. Audit Dashboard HTML Output
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_role'] = 'Admin';
$_SESSION['logged_in'] = true;

ob_start();
require __DIR__ . '/../admin/dashboard.php';
$dashHtml = ob_get_clean();

echo "--- 1. Testing Admin Dashboard HTML & Elements ---\n";
assertCheck("Configure Schedule Removed", strpos($dashHtml, 'Configure Schedule') === false, "Found 'Configure Schedule' in dashboard HTML");
assertCheck("Add Faculty Button Present", strpos($dashHtml, 'Dashboard.openAddFacultyDrawer()') !== false && strpos($dashHtml, '+ Add Faculty') !== false, "Add Faculty button not found");
assertCheck("Add Student Button Present", strpos($dashHtml, 'Dashboard.openAddStudentDrawer()') !== false && strpos($dashHtml, '+ Add Student') !== false, "Add Student button not found");
assertCheck("Add Faculty Drawer Backdrop Exists", strpos($dashHtml, 'id="addFacultyDrawer"') !== false && strpos($dashHtml, 'class="drawer-backdrop"') !== false, "Faculty drawer backdrop not found");
assertCheck("Add Faculty Form Exists", strpos($dashHtml, 'id="addFacultyForm"') !== false, "Faculty form not found");
assertCheck("Add Student Drawer Backdrop Exists", strpos($dashHtml, 'id="addStudentDrawer"') !== false && strpos($dashHtml, 'class="drawer-backdrop"') !== false, "Student drawer backdrop not found");
assertCheck("Add Student Form Exists", strpos($dashHtml, 'id="addStudentForm"') !== false, "Student form not found");
assertCheck("Total Faculty Metric Card Present", strpos($dashHtml, 'id="statTotalStaff"') !== false, "statTotalStaff not found");
assertCheck("Enrolled Students Metric Card Present", strpos($dashHtml, 'id="statTotalStudents"') !== false, "statTotalStudents not found");

// 2. Test CSS Rules for Drawers
echo "\n--- 2. Testing CSS Drawer Design Tokens ---\n";
$cssContent = file_get_contents(__DIR__ . '/../assets/css/style.css');
assertCheck("CSS Drawer Backdrop Rule", strpos($cssContent, '.drawer-backdrop') !== false, "Missing .drawer-backdrop");
assertCheck("CSS Drawer Panel Rule", strpos($cssContent, '.drawer-panel') !== false, "Missing .drawer-panel");
assertCheck("CSS Drawer Slide Animation Rule", strpos($cssContent, 'transform: translateX(100%)') !== false && strpos($cssContent, 'transform: translateX(0)') !== false, "Missing slide animation");
assertCheck("CSS Responsive Drawer Rule", strpos($cssContent, 'width: 100vw') !== false, "Missing responsive drawer width");

// 3. Test JavaScript Framework Methods
echo "\n--- 3. Testing JavaScript Drawer Helpers ---\n";
$appJsContent = file_get_contents(__DIR__ . '/../assets/js/app.js');
assertCheck("App.openDrawer Exists", strpos($appJsContent, 'openDrawer(drawerId)') !== false, "Missing openDrawer");
assertCheck("App.closeDrawer Exists", strpos($appJsContent, 'closeDrawer(drawerId)') !== false, "Missing closeDrawer");
assertCheck("App.closeAllDrawers Exists", strpos($appJsContent, 'closeAllDrawers()') !== false, "Missing closeAllDrawers");
assertCheck("Escape Key Listener Present", strpos($appJsContent, 'Escape') !== false, "Missing Escape key listener");
assertCheck("Backdrop Click Listener Present", strpos($appJsContent, 'drawer-backdrop') !== false, "Missing backdrop click listener");

$dashJsContent = file_get_contents(__DIR__ . '/../assets/js/dashboard.js');
assertCheck("Dashboard.openAddFacultyDrawer Exists", strpos($dashJsContent, 'openAddFacultyDrawer()') !== false, "Missing openAddFacultyDrawer");
assertCheck("Dashboard.submitAddFaculty Exists", strpos($dashJsContent, 'submitAddFaculty(event)') !== false, "Missing submitAddFaculty");
assertCheck("Dashboard.openAddStudentDrawer Exists", strpos($dashJsContent, 'openAddStudentDrawer()') !== false, "Missing openAddStudentDrawer");
assertCheck("Dashboard.submitAddStudent Exists", strpos($dashJsContent, 'submitAddStudent(event)') !== false, "Missing submitAddStudent");

// 4. Test Student & Faculty Database Operations
echo "\n--- 4. Testing Student & Faculty API Operations ---\n";
$db = getDB();

// Test Adding a Faculty via DB query matching API behavior
$facCode = "FAC_TEST_" . time();
$facEmail = "test_fac_" . time() . "@bvoc-sdsa.edu";
$stmt = $db->prepare("INSERT INTO staff (staff_code, full_name, email, phone, designation, department, role, max_weekly_hours, color_code, is_active) VALUES (:c, :n, :e, :p, :des, :dept, :r, :m, :col, 1)");
$stmt->execute([
    ':c' => $facCode,
    ':n' => 'Dr. Test Professor',
    ':e' => $facEmail,
    ':p' => '+91 99887 76655',
    ':des' => 'Associate Professor',
    ':dept' => 'B.Voc SD & SA',
    ':r' => 'Faculty',
    ':m' => 18,
    ':col' => '#3B82F6'
]);
$newFacId = (int)$db->lastInsertId();
assertCheck("Faculty Creation with Department & Staff Code", $newFacId > 0, "Failed to insert faculty");

$db->exec("DELETE FROM staff WHERE id = {$newFacId}");
$delFacCheck = (int)$db->query("SELECT COUNT(*) FROM staff WHERE id = {$newFacId}")->fetchColumn();
assertCheck("Faculty Clean Removal", $delFacCheck === 0, "Failed to delete test faculty");

// Test Student Insertion
$stuReg = "STU_TEST_" . time();
$stuEmail = "stu_test_" . time() . "@student.bvoc.edu";
$stuStmt = $db->prepare("INSERT INTO students (register_number, full_name, email, phone, academic_year_id, section, is_active) VALUES (:r, :n, :e, :p, 1, 'Section A', 1)");
$stuStmt->execute([
    ':r' => $stuReg,
    ':n' => 'Test Student Name',
    ':e' => $stuEmail,
    ':p' => '+91 91234 56789'
]);
$newStuId = (int)$db->lastInsertId();
assertCheck("Student Enrollment with Academic Year & Section", $newStuId > 0, "Failed to insert student");

$db->exec("DELETE FROM students WHERE id = {$newStuId}");
$delStuCheck = (int)$db->query("SELECT COUNT(*) FROM students WHERE id = {$newStuId}")->fetchColumn();
assertCheck("Student Clean Removal", $delStuCheck === 0, "Failed to delete test student");

echo "\n====================================================================\n";
echo "  TEST SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed / $total) * 100, 1) . "%)\n";
echo "====================================================================\n";
