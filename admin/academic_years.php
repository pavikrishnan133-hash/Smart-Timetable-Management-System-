<?php
$pageTitle = 'Academic Years & Batches';
$pageSubtitle = 'Manage academic classes, semesters, batch strength, and session records';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="academicYearsView">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">🎓 Academic Years & Batches (B.Voc SD & SA)</span>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Dynamic class management: Add, edit, or delete academic years and monitor curriculum hour allocations.
                </p>
            </div>
            <button class="btn btn-primary" onclick="openCreateYearModal()">
                ＋ Add Academic Year
            </button>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Year & Program Name</th>
                        <th>Current Semester</th>
                        <th>Batch Strength</th>
                        <th>Curriculum Quota</th>
                        <th>Timetable Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="yearsTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Loading academic years...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Year Modal -->
<div id="yearModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="yearModalTitle" class="modal-title">Add Academic Year</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('yearModal')">&times;</button>
        </div>
        <form id="yearForm" onsubmit="handleSaveYear(event)">
            <div class="modal-body">
                <input type="hidden" id="yearId">

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="yearCode">Class / Batch Code *</label>
                        <input type="text" id="yearCode" class="form-control" placeholder="e.g. BVOC-SDSA-Y1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="yearSemester">Semester *</label>
                        <input type="text" id="yearSemester" class="form-control" placeholder="e.g. Semester 1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="yearName">Academic Year Name *</label>
                    <input type="text" id="yearName" class="form-control" placeholder="e.g. 1st Year B.Voc (SD & SA)" required>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="yearBatchStrength">Student Batch Strength</label>
                        <input type="number" id="yearBatchStrength" class="form-control" min="5" max="150" value="30">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="yearSession">Academic Session</label>
                        <input type="text" id="yearSession" class="form-control" value="2026-2027">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="yearIsActive" checked>
                        <span style="font-weight: 600; color: var(--text-primary);">Active Status</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('yearModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Academic Year</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadYears() {
        const tbody = document.getElementById('yearsTableBody');
        try {
            const res = await App.api('academic_years.php?action=list');
            const years = res.data;

            if (years.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No academic years found.</td></tr>';
                return;
            }

            tbody.innerHTML = years.map(y => `
                <tr>
                    <td><span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(y.code)}</span></td>
                    <td>
                        <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(y.year_name)}</div>
                        <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(y.academic_session)}</div>
                    </td>
                    <td><span class="badge-cat badge-major-theory">${App.escapeHtml(y.semester)}</span></td>
                    <td><strong>${y.batch_strength} Students</strong></td>
                    <td>
                        <div style="font-weight:600; color:var(--text-white);">${y.subjects_count} Subjects</div>
                        <div style="font-size:11.5px; color:var(--text-muted);">${y.total_required_hours} hrs/week required</div>
                    </td>
                    <td>
                        <span class="quality-grade-badge ${y.total_scheduled_hours > 0 ? 'success' : 'warning'}" style="font-size:11px; padding:2px 8px;">
                            ${y.total_scheduled_hours > 0 ? `✓ Scheduled (${y.total_scheduled_hours} hrs)` : 'Pending Generation'}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <button class="btn btn-secondary btn-sm" onclick="openEditYearModal(${y.id})">✎ Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteYear(${y.id})">✕</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }

    function openCreateYearModal() {
        document.getElementById('yearModalTitle').innerText = 'Add Academic Year';
        document.getElementById('yearForm').reset();
        document.getElementById('yearId').value = '';
        document.getElementById('yearSession').value = '2026-2027';
        document.getElementById('yearIsActive').checked = true;
        App.openModal('yearModal');
    }

    async function openEditYearModal(id) {
        try {
            const res = await App.api(`academic_years.php?action=get&id=${id}`);
            const y = res.data;

            document.getElementById('yearModalTitle').innerText = 'Edit Academic Year';
            document.getElementById('yearId').value = y.id;
            document.getElementById('yearCode').value = y.code;
            document.getElementById('yearName').value = y.year_name;
            document.getElementById('yearSemester').value = y.semester;
            document.getElementById('yearBatchStrength').value = y.batch_strength;
            document.getElementById('yearSession').value = y.academic_session;
            document.getElementById('yearIsActive').checked = parseInt(y.is_active, 10) === 1;

            App.openModal('yearModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }

    async function handleSaveYear(e) {
        e.preventDefault();
        const id = document.getElementById('yearId').value;
        const action = id ? 'update' : 'create';

        const payload = {
            action,
            id,
            code: document.getElementById('yearCode').value,
            year_name: document.getElementById('yearName').value,
            semester: document.getElementById('yearSemester').value,
            batch_strength: document.getElementById('yearBatchStrength').value,
            academic_session: document.getElementById('yearSession').value,
            is_active: document.getElementById('yearIsActive').checked ? 1 : 0
        };

        try {
            const res = await App.api('academic_years.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('yearModal');
            loadYears();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }

    async function deleteYear(id) {
        if (!confirm('Are you sure you want to delete this Academic Year? All linked subjects and schedule slots will be permanently removed.')) {
            return;
        }

        try {
            const res = await App.api('academic_years.php', { method: 'POST', body: { action: 'delete', id } });
            App.toast(res.message, 'success');
            loadYears();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', loadYears);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
