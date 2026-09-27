<?php
$pageTitle = 'Student Management';
$pageSubtitle = 'Manage enrolled students, batch assignments, register numbers, and contact details';
$pageScript = 'students.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="studentsView">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">🎓 Department Enrolled Students</span>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Manage student roster across B.Voc SD & SA academic batches, sections, and registration details.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" id="searchStudent" class="form-control" placeholder="🔍 Search by name, reg no, email..." style="width: 250px; padding: 7px 12px; font-size: 13px;">
                <select id="filterYear" class="form-control" style="width: 200px; padding: 7px 12px; font-size: 13px;">
                    <option value="">All Academic Years</option>
                </select>
                <button id="btnAddStudent" class="btn btn-primary" onclick="StudentController.openCreateModal()">
                    ＋ Add New Student
                </button>
            </div>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Register Number</th>
                        <th>Student Name</th>
                        <th>Academic Year & Semester</th>
                        <th>Section</th>
                        <th>Contact Information</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Loading student directory...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Student Modal -->
<div id="studentModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="studentModalTitle" class="modal-title">Add New Student</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('studentModal')">&times;</button>
        </div>
        <form id="studentForm" onsubmit="StudentController.handleSave(event)">
            <div class="modal-body">
                <input type="hidden" id="studentId">

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="studentFullName">Full Name *</label>
                        <input type="text" id="studentFullName" class="form-control" placeholder="e.g. Diya Natarajan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="studentRegNumber">Register Number *</label>
                        <input type="text" id="studentRegNumber" class="form-control" placeholder="e.g. 2026BVOC002" required>
                    </div>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="studentEmail">Email Address *</label>
                        <input type="email" id="studentEmail" class="form-control" placeholder="e.g. diya.n@student.bvoc.edu" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="studentPhone">Phone Number</label>
                        <input type="tel" id="studentPhone" class="form-control" placeholder="e.g. +91 98402 33445">
                    </div>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="studentYear">Academic Year Batch *</label>
                        <select id="studentYear" class="form-control" required>
                            <option value="">Select Academic Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="studentSection">Section *</label>
                        <select id="studentSection" class="form-control" required>
                            <option value="Section A">Section A</option>
                            <option value="Section B">Section B</option>
                            <option value="Section C">Section C</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="studentIsActive" checked>
                        <span style="font-weight: 600; color: var(--text-primary);">Active Enrollment Status</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('studentModal')">Cancel</button>
                <button type="submit" id="btnSaveStudent" class="btn btn-primary">Save Student</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
