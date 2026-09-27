<?php
$pageTitle = 'Dynamic Subject Management';
$pageSubtitle = 'Manage curriculum subjects, weekly hours, categories, and faculty assignments';
$pageScript = 'subjects.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="subjectsView">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">📚 Department Curriculum Subjects</span>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Completely dynamic syllabus: Add, edit, or remove subjects with customized weekly hours, assigned faculty, and lab requirements.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <select id="filterYear" class="form-control" style="width: 200px; padding: 7px 12px; font-size: 13px;">
                    <option value="">All Academic Years</option>
                </select>
                <select id="filterCategory" class="form-control" style="width: 170px; padding: 7px 12px; font-size: 13px;">
                    <option value="">All Categories</option>
                    <option value="Major Theory">Major Theory</option>
                    <option value="Major Lab">Major Lab</option>
                    <option value="Skill Theory">Skill Theory</option>
                    <option value="Skill Lab">Skill Lab</option>
                    <option value="Common Theory">Common Theory</option>
                    <option value="Project">Project</option>
                </select>
                <button id="btnAddSubject" class="btn btn-primary">
                    ＋ Add New Subject
                </button>
            </div>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Title & Class</th>
                        <th>Category</th>
                        <th>Assigned Faculty</th>
                        <th>Required Weekly Hours</th>
                        <th>Preferred Room Type</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="subjectsTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Loading subjects...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Subject Modal -->
<div id="subjectModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="subjectModalTitle" class="modal-title">Add Dynamic Subject</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('subjectModal')">&times;</button>
        </div>
        <form id="subjectForm">
            <div class="modal-body">
                <input type="hidden" id="subjectId">

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="subjectCode">Subject Code *</label>
                        <input type="text" id="subjectCode" class="form-control" placeholder="e.g. SDSA101" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subjectYear">Academic Year *</label>
                        <select id="subjectYear" class="form-control" required>
                            <!-- Injected via JavaScript -->
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subjectName">Subject Full Title *</label>
                    <input type="text" id="subjectName" class="form-control" placeholder="e.g. Programming in C & Data Structures" required>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="subjectCategory">Subject Category *</label>
                        <select id="subjectCategory" class="form-control" required>
                            <option value="Major Theory">Major Theory</option>
                            <option value="Major Lab">Major Lab</option>
                            <option value="Skill Theory">Skill Theory</option>
                            <option value="Skill Lab">Skill Lab</option>
                            <option value="Common Theory">Common Theory</option>
                            <option value="Project">Project</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subjectHours">Required Weekly Hours *</label>
                        <input type="number" id="subjectHours" class="form-control" min="1" max="12" value="4" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subjectFaculty">Primary Assigned Faculty *</label>
                    <select id="subjectFaculty" class="form-control" required>
                        <!-- Injected via JavaScript -->
                    </select>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="subjectRoomType">Preferred Room Type *</label>
                        <select id="subjectRoomType" class="form-control" required>
                            <option value="Theory Classroom">Theory Classroom (LH-201/202/203)</option>
                            <option value="Computer Lab">Computer Lab (SD Lab / SA Lab)</option>
                            <option value="General Lab">General Lab (Hardware & IoT Lab)</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; flex-direction: column; justify-content: center; margin-top: 15px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 4px;">
                            <input type="checkbox" id="subjectConsecutive">
                            <span style="font-weight: 600; color: var(--text-white);">Require Consecutive Periods</span>
                        </label>
                        <small style="font-size: 11px; color: var(--text-muted);">Allocates 2 periods in unbroken blocks for practicals.</small>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="subjectIsActive" checked>
                        <span style="font-weight: 600; color: var(--text-primary);">Active in Syllabus</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('subjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
