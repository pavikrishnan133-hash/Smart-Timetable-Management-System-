/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Student Management Controller
 */

const StudentController = {
    studentsList: [],
    academicYears: [],

    async init() {
        await Promise.all([
            this.loadAcademicYears(),
            this.loadStudents()
        ]);
        this.bindEvents();
    },

    bindEvents() {
        const filterYear = document.getElementById('filterYear');
        if (filterYear) {
            filterYear.addEventListener('change', () => this.loadStudents());
        }

        const searchInput = document.getElementById('searchStudent');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.renderStudentsTable());
        }
    },

    async loadAcademicYears() {
        try {
            const res = await App.api('academic_years.php?action=list');
            this.academicYears = res.data;

            const filterYear = document.getElementById('filterYear');
            const selectYear = document.getElementById('studentYear');

            // Read academic_year_id from URL query params if present
            const urlParams = new URLSearchParams(window.location.search);
            const urlYearId = urlParams.get('academic_year_id');

            if (filterYear) {
                filterYear.innerHTML = '<option value="">All Academic Years</option>' +
                    this.academicYears.map(y => `<option value="${y.id}" ${urlYearId == y.id ? 'selected' : ''}>${App.escapeHtml(y.year_name)} (${y.semester})</option>`).join('');
            }
            if (selectYear) {
                selectYear.innerHTML = '<option value="">Select Academic Year</option>' +
                    this.academicYears.map(y => `<option value="${y.id}">${App.escapeHtml(y.year_name)} (${y.semester})</option>`).join('');
            }
        } catch (err) {
            console.error('Failed to load academic years:', err);
        }
    },

    async loadStudents() {
        const tableBody = document.getElementById('studentsTableBody');
        if (!tableBody) return;

        const yearId = document.getElementById('filterYear') ? document.getElementById('filterYear').value : '';
        let endpoint = 'students.php?action=list';
        if (yearId) endpoint += `&academic_year_id=${yearId}`;

        try {
            const res = await App.api(endpoint);
            this.studentsList = res.data;
            this.renderStudentsTable();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    renderStudentsTable() {
        const tableBody = document.getElementById('studentsTableBody');
        if (!tableBody) return;

        const searchQuery = (document.getElementById('searchStudent')?.value || '').toLowerCase().trim();

        let filtered = this.studentsList;
        if (searchQuery) {
            filtered = filtered.filter(s =>
                (s.full_name || '').toLowerCase().includes(searchQuery) ||
                (s.register_number || '').toLowerCase().includes(searchQuery) ||
                (s.email || '').toLowerCase().includes(searchQuery) ||
                (s.section || '').toLowerCase().includes(searchQuery)
            );
        }

        if (filtered.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No students found matching your criteria.</td></tr>`;
            return;
        }

        tableBody.innerHTML = filtered.map(s => `
            <tr>
                <td>
                    <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary); font-size:13px;">
                        ${App.escapeHtml(s.register_number)}
                    </span>
                </td>
                <td>
                    <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(s.full_name)}</div>
                    <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(s.email)}</div>
                </td>
                <td>
                    <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(s.academic_year_name || '-')}</div>
                    <span class="badge-cat badge-major-theory" style="font-size:10.5px; padding:2px 7px;">${App.escapeHtml(s.academic_year_semester || '')}</span>
                </td>
                <td>
                    <span class="badge-cat badge-common-theory">${App.escapeHtml(s.section || 'Section A')}</span>
                </td>
                <td>
                    <div style="font-size:12px; color:var(--text-secondary);">${App.escapeHtml(s.phone || '-')}</div>
                </td>
                <td>
                    <span class="quality-grade-badge ${parseInt(s.is_active, 10) === 1 ? 'success' : 'danger'}" style="padding:2px 8px; font-size:11px;">
                        ${parseInt(s.is_active, 10) === 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div style="display:flex; gap:6px; justify-content:flex-end;">
                        <button class="btn btn-secondary btn-sm" onclick="StudentController.openEditModal(${s.id})" title="Edit Student">
                            ✎ Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="StudentController.deleteStudent(${s.id})" title="Delete Student">
                            ✕
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    openCreateModal() {
        document.getElementById('studentModalTitle').innerText = 'Add New Student';
        document.getElementById('studentForm').reset();
        document.getElementById('studentId').value = '';
        document.getElementById('studentSection').value = 'Section A';
        document.getElementById('studentIsActive').checked = true;

        // Auto select filtered year if active
        const filterYearVal = document.getElementById('filterYear')?.value;
        if (filterYearVal) {
            document.getElementById('studentYear').value = filterYearVal;
        }

        App.openModal('studentModal');
    },

    async openEditModal(id) {
        try {
            const res = await App.api(`students.php?action=get&id=${id}`);
            const s = res.data;

            document.getElementById('studentModalTitle').innerText = 'Edit Student Details';
            document.getElementById('studentId').value = s.id;
            document.getElementById('studentFullName').value = s.full_name;
            document.getElementById('studentRegNumber').value = s.register_number;
            document.getElementById('studentEmail').value = s.email;
            document.getElementById('studentPhone').value = s.phone || '';
            document.getElementById('studentYear').value = s.academic_year_id;
            document.getElementById('studentSection').value = s.section || 'Section A';
            document.getElementById('studentIsActive').checked = parseInt(s.is_active, 10) === 1;

            App.openModal('studentModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async handleSave(e) {
        e.preventDefault();
        const id = document.getElementById('studentId').value;
        const action = id ? 'update' : 'create';

        const payload = {
            action,
            id,
            full_name: document.getElementById('studentFullName').value.trim(),
            register_number: document.getElementById('studentRegNumber').value.trim(),
            email: document.getElementById('studentEmail').value.trim(),
            phone: document.getElementById('studentPhone').value.trim(),
            academic_year_id: document.getElementById('studentYear').value,
            section: document.getElementById('studentSection').value,
            is_active: document.getElementById('studentIsActive').checked ? 1 : 0
        };

        const btn = document.getElementById('btnSaveStudent');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Saving...';
        }

        try {
            const res = await App.api('students.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('studentModal');
            await this.loadStudents();
        } catch (err) {
            App.toast(err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'Save Student';
            }
        }
    },

    async deleteStudent(id) {
        if (!confirm('Are you sure you want to delete this student record?')) return;

        try {
            const res = await App.api('students.php', {
                method: 'POST',
                body: { action: 'delete', id }
            });
            App.toast(res.message, 'success');
            await this.loadStudents();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('studentsView')) {
        StudentController.init();
    }
});
