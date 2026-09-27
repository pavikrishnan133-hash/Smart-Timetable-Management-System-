/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Classrooms & Specialized Labs Controller
 */

const ClassroomController = {
    classroomsList: [],

    async init() {
        await this.loadClassrooms();
        this.bindEvents();
    },

    bindEvents() {
        const addBtn = document.getElementById('btnAddClassroom');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.openCreateModal());
        }

        const form = document.getElementById('classroomForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSave(e));
        }
    },

    async loadClassrooms() {
        const tableBody = document.getElementById('classroomsTableBody');
        if (!tableBody) return;

        try {
            const res = await App.api('classrooms.php?action=list');
            this.classroomsList = res.data;

            if (this.classroomsList.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">No classrooms found.</td></tr>`;
                return;
            }

            const getTypeBadge = (type) => {
                if (type === 'Computer Lab') return `<span class="badge-cat badge-major-lab">💻 ${type}</span>`;
                if (type === 'General Lab') return `<span class="badge-cat badge-skill-lab">⚙ ${type}</span>`;
                return `<span class="badge-cat badge-major-theory">🏛 ${type}</span>`;
            };

            tableBody.innerHTML = this.classroomsList.map(r => `
                <tr>
                    <td>
                        <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(r.room_number)}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(r.room_name)}</div>
                        <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(r.block_floor || '-')}</div>
                    </td>
                    <td>${getTypeBadge(r.room_type)}</td>
                    <td>
                        <span style="font-weight:700; color:var(--text-white);">${r.capacity} Seats</span>
                    </td>
                    <td>
                        <span class="quality-grade-badge ${parseInt(r.is_active, 10) === 1 ? 'success' : 'danger'}" style="padding:2px 8px; font-size:11px;">
                            ${parseInt(r.is_active, 10) === 1 ? 'Available' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="btn btn-secondary btn-sm" onclick="ClassroomController.openEditModal(${r.id})" title="Edit Classroom">
                                ✎ Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="ClassroomController.deleteClassroom(${r.id})" title="Delete Classroom">
                                ✕
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    openCreateModal() {
        document.getElementById('classroomModalTitle').innerText = 'Add Classroom / Lab';
        document.getElementById('classroomForm').reset();
        document.getElementById('classroomId').value = '';
        document.getElementById('roomType').value = 'Theory Classroom';
        document.getElementById('roomCapacity').value = '40';
        App.openModal('classroomModal');
    },

    async openEditModal(id) {
        try {
            const res = await App.api(`classrooms.php?action=get&id=${id}`);
            const r = res.data;

            document.getElementById('classroomModalTitle').innerText = 'Edit Classroom / Lab';
            document.getElementById('classroomId').value = r.id;
            document.getElementById('roomNumber').value = r.room_number;
            document.getElementById('roomName').value = r.room_name;
            document.getElementById('roomType').value = r.room_type;
            document.getElementById('roomCapacity').value = r.capacity;
            document.getElementById('roomFloor').value = r.block_floor || '';
            document.getElementById('roomIsActive').checked = parseInt(r.is_active, 10) === 1;

            App.openModal('classroomModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async handleSave(e) {
        e.preventDefault();
        const id = document.getElementById('classroomId').value;
        const action = id ? 'update' : 'create';

        const payload = {
            action,
            id,
            room_number: document.getElementById('roomNumber').value,
            room_name: document.getElementById('roomName').value,
            room_type: document.getElementById('roomType').value,
            capacity: document.getElementById('roomCapacity').value,
            block_floor: document.getElementById('roomFloor').value,
            is_active: document.getElementById('roomIsActive').checked ? 1 : 0
        };

        try {
            const res = await App.api('classrooms.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('classroomModal');
            await this.loadClassrooms();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async deleteClassroom(id) {
        if (!confirm('Are you sure you want to delete this room/lab?')) return;

        try {
            const res = await App.api('classrooms.php', {
                method: 'POST',
                body: { action: 'delete', id }
            });
            App.toast(res.message, 'success');
            await this.loadClassrooms();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('classroomsView')) {
        ClassroomController.init();
    }
});
