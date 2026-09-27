<?php
$pageTitle = 'Executive Dashboard';
$pageSubtitle = 'B.Voc SD & SA Department Academic Overview';
$pageScript = 'dashboard.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="dashboardView">
    <!-- Top Quick Action Strip -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 18px 24px; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--text-white);">Academic Operations & Generator</h2>
            <p style="font-size: 12.5px; color: var(--text-secondary);">Manage curriculum, faculty constraints, student enrollments, and automated timetable generation.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="Dashboard.openAddFacultyDrawer()">
                👥 + Add Faculty
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="Dashboard.openAddStudentDrawer()">
                🎓 + Add Student
            </button>
            <a href="<?= $baseUrl ?>/admin/subjects.php" class="btn btn-secondary btn-sm">
                📚 + Add Subject
            </a>
            <button id="btnQuickGenerate" class="btn btn-primary btn-sm" onclick="Dashboard.quickGenerate()">
                ⚡ Generate Timetable
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid-cards">
        <div class="metric-card">
            <div class="metric-icon blue">👥</div>
            <div class="metric-info">
                <span class="metric-label">Total Faculty</span>
                <span class="metric-value" id="statTotalStaff">--</span>
                <span class="metric-desc">HOD & Professors</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon rose">🎓</div>
            <div class="metric-info">
                <span class="metric-label">Enrolled Students</span>
                <span class="metric-value" id="statTotalStudents">--</span>
                <span class="metric-desc">Active Batch Students</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon purple">📚</div>
            <div class="metric-info">
                <span class="metric-label">Active Subjects</span>
                <span class="metric-value" id="statTotalSubjects">--</span>
                <span class="metric-desc">Theory & Lab Courses</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon cyan">🏛</div>
            <div class="metric-info">
                <span class="metric-label">Academic Years</span>
                <span class="metric-value" id="statTotalYears">--</span>
                <span class="metric-desc">1st, 2nd, 3rd Years</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon green">🏢</div>
            <div class="metric-info">
                <span class="metric-label">Classrooms & Labs</span>
                <span class="metric-value" id="statTotalRooms">--</span>
                <span class="metric-desc">Theory Halls & PC Labs</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon amber">⏱</div>
            <div class="metric-info">
                <span class="metric-label">Scheduled Hours</span>
                <span class="metric-value" id="statScheduledHours">--</span>
                <span class="metric-desc">Weekly Periods Booked</span>
            </div>
        </div>
    </div>

    <!-- 2-Column Layout: Quality Score & Conflict Health Status -->
    <div class="grid-2col">
        <!-- Quality Score Widget -->
        <div class="quality-score-widget">
            <div class="score-circle-container">
                <svg class="score-circle-svg" viewBox="0 0 120 120">
                    <circle class="score-circle-bg" cx="60" cy="60" r="52"></circle>
                    <circle id="dashScoreCircle" class="score-circle-fill" cx="60" cy="60" r="52"></circle>
                </svg>
                <div class="score-center-text">
                    <div class="score-number" id="dashQualityScore">--</div>
                    <div class="score-denom">/ 100</div>
                </div>
            </div>

            <div class="quality-details">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div id="dashQualityGrade" class="quality-grade-badge success">Calculating...</div>
                        <h3 style="font-size: 18px; margin-bottom: 2px;">Timetable Quality Score</h3>
                        <p style="font-size: 12.5px; color: var(--text-muted);">Algorithmic rating based on zero-clash integrity, syllabus completion, and lab rules.</p>
                    </div>
                </div>

                <!-- Breakdown progress bars -->
                <div id="dashMetricsBars" style="margin-top: 15px;">
                    <!-- Injected via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Conflict Health Status Card -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">🛡 Conflict Health Status</span>
                <div id="dashConflictBadge">
                    <span class="quality-grade-badge primary">Auditing...</span>
                </div>
            </div>
            <div class="card-body" id="dashConflictsList">
                <div style="text-align: center; color: var(--text-muted); padding: 20px;">
                    Auditing current timetable across all academic years...
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 12px; color: var(--text-muted);">Checked via real-time conflict detector</span>
                <a href="<?= $baseUrl ?>/admin/reports.php" class="btn btn-secondary btn-sm">View Full Audit Report &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Optimization Recommendations & Department Highlights -->
    <div class="grid-equal-2col">
        <div class="card">
            <div class="card-header">
                <span class="card-title">💡 System Recommendations</span>
                <a href="<?= $baseUrl ?>/admin/generate_timetable.php" class="btn btn-secondary btn-sm">Open Generator</a>
            </div>
            <div class="card-body">
                <ul id="dashRecommendations" style="list-style: none; padding: 0; margin: 0;">
                    <li style="color: var(--text-muted); font-size: 13px;">Analyzing schedule constraints...</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">🏛 Department Highlights (B.Voc SD & SA)</span>
                <a href="<?= $baseUrl ?>/admin/timetable.php" class="btn btn-primary btn-sm">View Master Grid</a>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px; color: var(--text-secondary);">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <span>Head of Department:</span>
                        <strong style="color: var(--text-white);">Dr. Ramesh Kumar (Max 14 hrs/wk)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <span>Dedicated Lab Facilities:</span>
                        <strong style="color: var(--primary);">SD Lab 1, SA Lab 2, Hardware Lab</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <span>Standard Weekly Schedule:</span>
                        <strong style="color: var(--text-white);">5 Working Days × 5 Periods (25 Periods/Yr)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Lab Scheduling Strategy:</span>
                        <strong style="color: var(--success);">2-Period Consecutive Blocks (No Mid-Break Splits)</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SLIDE-IN DRAWER: ADD FACULTY -->
<!-- ========================================================================= -->
<div id="addFacultyDrawer" class="drawer-backdrop">
    <div class="drawer-panel">
        <div class="drawer-header">
            <div class="drawer-header-info">
                <div class="drawer-title">
                    <span style="color: var(--primary);">👥</span> Add New Faculty
                </div>
                <div class="drawer-subtitle">
                    Register a faculty member to manage workload limits & timetable assignments.
                </div>
            </div>
            <button type="button" class="drawer-close" onclick="App.closeDrawer('addFacultyDrawer')" title="Close Drawer (Esc)">
                &times;
            </button>
        </div>

        <form id="addFacultyForm" onsubmit="Dashboard.submitAddFaculty(event)" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            <div class="drawer-body">
                <div class="drawer-section-title">Personal & Identification</div>
                
                <div class="form-group">
                    <label class="form-label" for="drawerFacultyFullName">Faculty Name *</label>
                    <input type="text" id="drawerFacultyFullName" class="form-control" placeholder="e.g. Dr. K. Venkataraman" required>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyCode">Staff Code *</label>
                        <input type="text" id="drawerFacultyCode" class="form-control" placeholder="e.g. FAC008" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyRole">Role *</label>
                        <select id="drawerFacultyRole" class="form-control" onchange="Dashboard.onFacultyRoleChange(this.value)" required>
                            <option value="Faculty">Faculty (Max 18 hrs)</option>
                            <option value="HOD">HOD (Max 14 hrs)</option>
                        </select>
                    </div>
                </div>

                <div class="drawer-section-title" style="margin-top: 6px;">Contact Information</div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyEmail">Email Address *</label>
                        <input type="email" id="drawerFacultyEmail" class="form-control" placeholder="e.g. venkat@bvoc-sdsa.edu" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyPhone">Phone Number</label>
                        <input type="tel" id="drawerFacultyPhone" class="form-control" placeholder="e.g. +91 98401 23456">
                    </div>
                </div>

                <div class="drawer-section-title" style="margin-top: 6px;">Department & Workload</div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyDept">Department / Subject</label>
                        <input type="text" id="drawerFacultyDept" class="form-control" placeholder="e.g. Software Dev / AI" value="B.Voc SD & SA">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyDesig">Designation</label>
                        <input type="text" id="drawerFacultyDesig" class="form-control" placeholder="e.g. Assistant Professor" value="Assistant Professor">
                    </div>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyMaxHours">Max Weekly Workload</label>
                        <input type="number" id="drawerFacultyMaxHours" class="form-control" min="2" max="35" value="18" required>
                        <small style="color: var(--text-muted); font-size: 11px;">Maximum periods/week.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerFacultyColor">Timetable Tag Color</label>
                        <input type="color" id="drawerFacultyColor" class="form-control" value="#3B82F6" style="height: 42px; padding: 4px; cursor: pointer;">
                    </div>
                </div>

                <div class="drawer-switch-container">
                    <div class="drawer-switch-label">
                        <span class="drawer-switch-title">Active Faculty Status</span>
                        <span class="drawer-switch-desc">Include in timetable generation & scheduling constraints</span>
                    </div>
                    <label style="cursor: pointer; display: flex; align-items: center;">
                        <input type="checkbox" id="drawerFacultyIsActive" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                    </label>
                </div>
            </div>

            <div class="drawer-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeDrawer('addFacultyDrawer')">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitAddFaculty" class="btn btn-primary">
                    👥 Add Faculty
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SLIDE-IN DRAWER: ADD STUDENT -->
<!-- ========================================================================= -->
<div id="addStudentDrawer" class="drawer-backdrop">
    <div class="drawer-panel">
        <div class="drawer-header">
            <div class="drawer-header-info">
                <div class="drawer-title">
                    <span style="color: var(--primary);">🎓</span> Add New Student
                </div>
                <div class="drawer-subtitle">
                    Enroll a student into an Academic Year batch and Section.
                </div>
            </div>
            <button type="button" class="drawer-close" onclick="App.closeDrawer('addStudentDrawer')" title="Close Drawer (Esc)">
                &times;
            </button>
        </div>

        <form id="addStudentForm" onsubmit="Dashboard.submitAddStudent(event)" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            <div class="drawer-body">
                <div class="drawer-section-title">Student Profile</div>

                <div class="form-group">
                    <label class="form-label" for="drawerStudentFullName">Student Name *</label>
                    <input type="text" id="drawerStudentFullName" class="form-control" placeholder="e.g. Diya Natarajan" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="drawerStudentRegNumber">Register Number *</label>
                    <input type="text" id="drawerStudentRegNumber" class="form-control" placeholder="e.g. 2026BVOC018" required>
                    <small style="color: var(--text-muted); font-size: 11px;">Unique institutional registration / roll number.</small>
                </div>

                <div class="drawer-section-title" style="margin-top: 6px;">Contact Details</div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerStudentEmail">Email Address *</label>
                        <input type="email" id="drawerStudentEmail" class="form-control" placeholder="e.g. diya.n@student.bvoc.edu" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerStudentPhone">Phone Number</label>
                        <input type="tel" id="drawerStudentPhone" class="form-control" placeholder="e.g. +91 98402 33445">
                    </div>
                </div>

                <div class="drawer-section-title" style="margin-top: 6px;">Academic Placement</div>

                <div class="grid-equal-2col" style="margin-bottom: 0; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="drawerStudentYear">Academic Year *</label>
                        <select id="drawerStudentYear" class="form-control" required>
                            <option value="">Loading academic years...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="drawerStudentSection">Section *</label>
                        <select id="drawerStudentSection" class="form-control" required>
                            <option value="Section A">Section A</option>
                            <option value="Section B">Section B</option>
                            <option value="Section C">Section C</option>
                        </select>
                    </div>
                </div>

                <div class="drawer-switch-container">
                    <div class="drawer-switch-label">
                        <span class="drawer-switch-title">Active Student Status</span>
                        <span class="drawer-switch-desc">Enrolled in active department semester</span>
                    </div>
                    <label style="cursor: pointer; display: flex; align-items: center;">
                        <input type="checkbox" id="drawerStudentIsActive" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                    </label>
                </div>
            </div>

            <div class="drawer-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeDrawer('addStudentDrawer')">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitAddStudent" class="btn btn-primary">
                    🎓 Add Student
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

