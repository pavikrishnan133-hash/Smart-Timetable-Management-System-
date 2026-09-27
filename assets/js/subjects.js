/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Subjects Management Controller
 */

const SubjectController = {
    subjectsList: [],
    academicYears: [],
    staffList: [],

    async init() {
        await Promise.all([
            this.loadAcademicYears(),
            this.loadStaffList(),
            this.loadSubjects()
        ]);
        this.bindEvents();
    },

    bindEvents() {
        const addBtn = document.getElementById('btnAddSubject');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.openCreateModal());
        }

        const form = document.getElementById('subjectForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSave(e));
        }

        const filterYear = document.getElementById('filterYear');
        const filterCat = document.getElementById('filterCategory');
        if (filterYear) filterYear.addEventListener('change', () => this.loadSubjects());
        if (filterCat) filterCat.addEventListener('change', () => this.loadSubjects());

        const categorySelect = document.getElementById('subjectCategory');
        const roomTypeSelect = document.getElementById('subjectRoomType');
        const isConsecutiveInput = document.getElementById('subjectConsecutive');

        if (categorySelect && roomTypeSelect && isConsecutiveInput) {
            categorySelect.addEventListener('change', () => {
                const val = categorySelect.value;
                if (val.includes('Lab')) {
                    roomTypeSelect.value = 'Computer Lab';
                    isConsecutiveInput.checked = true;
                } else if (val === 'Project') {
                    roomTypeSelect.value = 'Computer Lab';
                    isConsecutiveInput.checked = true;
                } else {
                    roomTypeSelect.value = 'Theory Classroom';
                    isConsecutiveInput.checked = false;
                }
            });
        }
    },

    async loadAcademicYears() {
        try {
            const res = await App.api('academic_years.php?action=list');
            this.academicYears = res.data;

            const filterYear = document.getElementById('filterYear');
            const selectYear = document.getElementById('subjectYear');

            if (filterYear) {
                filterYear.innerHTML = '<option value="">All Academic Years</option>' +
                    this.academicYears.map(y => `<option value="${y.id}">${App.escapeHtml(y.year_name)}</option>`).join('');
            }
            if (selectYear) {
                selectYear.innerHTML = '<option value="">Select Academic Year</option>' +
                    this.academicYears.map(y => `<option value="${y.id}">${App.escapeHtml(y.year_name)} (${y.semester})</option>`).join('');
            }
        } catch (err) {
            console.error('Failed to load years:', err);
        }
    },

    async loadStaffList() {
        try {
            const res = await App.api('staff.php?action=list');
            this.staffList = res.data;

            const facultySelect = document.getElementById('subjectFaculty');
            if (facultySelect) {
                facultySelect.innerHTML = '<option value="">Select Assigned Faculty</option>' +
                    this.staffList.map(s => `<option value="${s.id}">${App.escapeHtml(s.full_name)} (${s.designation} - ${s.role})</option>`).join('');
            }
        } catch (err) {
            console.error('Failed to load staff list:', err);
        }
    },

    async loadSubjects() {
        const tableBody = document.getElementById('subjectsTableBody');
        if (!tableBody) return;

        const yearId = document.getElementById('filterYear') ? document.getElementById('filterYear').value : '';
        const category = document.getElementById('filterCategory') ? document.getElementById('filterCategory').value : '';

        let endpoint = 'subjects.php?action=list';
        if (yearId) endpoint += `&academic_year_id=${yearId}`;
        if (category) endpoint += `&category=${encodeURIComponent(category)}`;

        try {
            const res = await App.api(endpoint);
            this.subjectsList = res.data;

            if (this.subjectsList.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No subjects found matching the filter.</td></tr>`;
                return;
            }

            const getCategoryBadge = (cat) => {
                const map = {
                    'Major Theory': 'badge-major-theory',
                    'Major Lab': 'badge-major-lab',
                    'Skill Theory': 'badge-skill-theory',
                    'Skill Lab': 'badge-skill-lab',
                    'Common Theory': 'badge-common-theory',
                    'Project': 'badge-project'
                };
                return `<span class="badge-cat ${map[cat] || 'badge-major-theory'}">${App.escapeHtml(cat)}</span>`;
            };

            tableBody.innerHTML = this.subjectsList.map(s => {
                const req = parseInt(s.weekly_hours, 10);
                const scheduled = parseInt(s.scheduled_hours || 0, 10);
                const isMatch = req === scheduled;

                return `
                    <tr>
                        <td>
                            <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(s.subject_code)}</span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(s.subject_name)}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(s.year_name)} (${s.semester})</div>
                        </td>
                        <td>${getCategoryBadge(s.category)}</td>
                        <td>
                            <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(s.faculty_name)}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(s.faculty_role)}</div>
                        </td>
                        <td>
                            <span class="badge-cat ${isMatch ? 'badge-major-lab' : 'badge-common-theory'}" style="font-weight:700;">
                                ${scheduled} / ${req} hrs
                            </span>
                        </td>
                        <td>
                            <div style="font-size:12px; color:var(--text-secondary);">${App.escapeHtml(s.preferred_room_type)}</div>
                            ${parseInt(s.is_consecutive_required, 10) === 1 ? '<span style="font-size:10.5px; color:var(--info);">⚡ Consecutive Block</span>' : ''}
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button class="btn btn-secondary btn-sm" onclick="SubjectController.openEditModal(${s.id})" title="Edit Subject">
                                    ✎ Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="SubjectController.deleteSubject(${s.id})" title="Delete Subject">
                                    ✕
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    openCreateModal() {
        document.getElementById('subjectModalTitle').innerText = 'Add Dynamic Subject';
        document.getElementById('subjectForm').reset();
        document.getElementById('subjectId').value = '';
        document.getElementById('subjectCategory').value = 'Major Theory';
        document.getElementById('subjectHours').value = '4';
        document.getElementById('subjectRoomType').value = 'Theory Classroom';
        document.getElementById('subjectConsecutive').checked = false;
        App.openModal('subjectModal');
    },

    async openEditModal(id) {
        try {
            const res = await App.api(`subjects.php?action=get&id=${id}`);
            const s = res.data;

            document.getElementById('subjectModalTitle').innerText = 'Edit Subject';
            document.getElementById('subjectId').value = s.id;
            document.getElementById('subjectCode').value = s.subject_code;
            document.getElementById('subjectName').value = s.subject_name;
            document.getElementById('subjectYear').value = s.academic_year_id;
            document.getElementById('subjectCategory').value = s.category;
            document.getElementById('subjectHours').value = s.weekly_hours;
            document.getElementById('subjectFaculty').value = s.assigned_faculty_id;
            document.getElementById('subjectRoomType').value = s.preferred_room_type;
            document.getElementById('subjectConsecutive').checked = parseInt(s.is_consecutive_required, 10) === 1;
            document.getElementById('subjectIsActive').checked = parseInt(s.is_active, 10) === 1;

            App.openModal('subjectModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async handleSave(e) {
        e.preventDefault();
        const id = document.getElementById('subjectId').value;
        const action = id ? 'update' : 'create';

        const payload = {
            action,
            id,
            subject_code: document.getElementById('subjectCode').value,
            subject_name: document.getElementById('subjectName').value,
            academic_year_id: document.getElementById('subjectYear').value,
            category: document.getElementById('subjectCategory').value,
            weekly_hours: document.getElementById('subjectHours').value,
            assigned_faculty_id: document.getElementById('subjectFaculty').value,
            preferred_room_type: document.getElementById('subjectRoomType').value,
            is_consecutive_required: document.getElementById('subjectConsecutive').checked ? 1 : 0,
            is_active: document.getElementById('subjectIsActive').checked ? 1 : 0
        };

        try {
            const res = await App.api('subjects.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('subjectModal');
            await this.loadSubjects();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async deleteSubject(id) {
        if (!confirm('Are you sure you want to delete this subject? All scheduled timetable slots for this subject will also be cleared.')) {
            return;
        }

        try {
            const res = await App.api('subjects.php', {
                method: 'POST',
                body: { action: 'delete', id }
            });
            App.toast(res.message, 'success');
            await this.loadSubjects();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('subjectsView')) {
        SubjectController.init();
    }
});
