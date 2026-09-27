/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Staff & Faculty Controller + Availability Matrix Editor
 */

const StaffController = {
    staffList: [],
    currentStaffId: null,
    availabilityMatrix: {},

    async init() {
        await this.loadStaff();
        this.bindEvents();
    },

    bindEvents() {
        const addBtn = document.getElementById('btnAddStaff');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.openCreateModal());
        }

        const form = document.getElementById('staffForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSave(e));
        }

        const roleSelect = document.getElementById('staffRole');
        const maxWorkloadInput = document.getElementById('staffMaxHours');
        if (roleSelect && maxWorkloadInput) {
            roleSelect.addEventListener('change', () => {
                if (roleSelect.value === 'HOD') {
                    maxWorkloadInput.value = 14;
                } else {
                    maxWorkloadInput.value = 18;
                }
            });
        }
    },

    async loadStaff() {
        const tableBody = document.getElementById('staffTableBody');
        if (!tableBody) return;

        try {
            const res = await App.api('staff.php?action=list');
            this.staffList = res.data;

            if (this.staffList.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No faculty members found. Click "Add Staff" to create one.</td></tr>`;
                return;
            }

            tableBody.innerHTML = this.staffList.map(s => {
                const assigned = parseInt(s.current_assigned_hours || 0, 10);
                const max = parseInt(s.max_weekly_hours, 10);
                const isOverloaded = assigned > max;
                const workloadPercent = max > 0 ? Math.round((assigned / max) * 100) : 0;

                return `
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:12px; height:12px; border-radius:50%; background:${s.color_code};"></div>
                                <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(s.staff_code)}</span>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(s.full_name)}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(s.designation)}</div>
                        </td>
                        <td>
                            <span class="badge-cat ${s.role === 'HOD' ? 'badge-project' : 'badge-major-theory'}">${App.escapeHtml(s.role)}</span>
                        </td>
                        <td>
                            <div style="font-size:12px; color:var(--text-secondary);">${App.escapeHtml(s.email)}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(s.phone || '-')}</div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                                <strong style="color:${isOverloaded ? 'var(--danger)' : 'var(--text-white)'};">${assigned} / ${max} hrs</strong>
                                <span style="font-size:11px; color:${isOverloaded ? 'var(--danger)' : 'var(--text-muted)'};">${workloadPercent}%</span>
                            </div>
                            <div class="progress-track" style="height:5px;">
                                <div class="progress-fill" style="width:${Math.min(100, workloadPercent)}%; background:${isOverloaded ? 'var(--danger)' : (workloadPercent >= 80 ? 'var(--primary)' : 'var(--success)')};"></div>
                            </div>
                        </td>
                        <td>
                            <span class="quality-grade-badge ${parseInt(s.is_active, 10) === 1 ? 'success' : 'danger'}" style="padding:2px 8px; font-size:11px;">
                                ${parseInt(s.is_active, 10) === 1 ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button class="btn btn-secondary btn-sm" onclick="StaffController.openAvailabilityModal(${s.id})" title="Configure Availability Matrix">
                                    🗓 Avail
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="StaffController.openEditModal(${s.id})" title="Edit Staff">
                                    ✎ Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="StaffController.deleteStaff(${s.id})" title="Delete Staff">
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
        document.getElementById('staffModalTitle').innerText = 'Add New Faculty / Staff';
        document.getElementById('staffForm').reset();
        document.getElementById('staffId').value = '';
        document.getElementById('staffRole').value = 'Faculty';
        document.getElementById('staffMaxHours').value = '18';
        document.getElementById('staffColor').value = '#3B82F6';
        App.openModal('staffModal');
    },

    async openEditModal(id) {
        try {
            const res = await App.api(`staff.php?action=get&id=${id}`);
            const s = res.data;

            document.getElementById('staffModalTitle').innerText = 'Edit Faculty / Staff';
            document.getElementById('staffId').value = s.id;
            document.getElementById('staffCode').value = s.staff_code;
            document.getElementById('staffFullName').value = s.full_name;
            document.getElementById('staffEmail').value = s.email;
            document.getElementById('staffPhone').value = s.phone || '';
            document.getElementById('staffDesignation').value = s.designation;
            document.getElementById('staffRole').value = s.role;
            document.getElementById('staffMaxHours').value = s.max_weekly_hours;
            document.getElementById('staffColor').value = s.color_code || '#3B82F6';
            document.getElementById('staffIsActive').checked = parseInt(s.is_active, 10) === 1;

            App.openModal('staffModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async handleSave(e) {
        e.preventDefault();
        const id = document.getElementById('staffId').value;
        const action = id ? 'update' : 'create';

        const payload = {
            action,
            id,
            staff_code: document.getElementById('staffCode').value,
            full_name: document.getElementById('staffFullName').value,
            email: document.getElementById('staffEmail').value,
            phone: document.getElementById('staffPhone').value,
            designation: document.getElementById('staffDesignation').value,
            role: document.getElementById('staffRole').value,
            max_weekly_hours: document.getElementById('staffMaxHours').value,
            color_code: document.getElementById('staffColor').value,
            is_active: document.getElementById('staffIsActive').checked ? 1 : 0
        };

        try {
            const res = await App.api('staff.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('staffModal');
            await this.loadStaff();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async deleteStaff(id) {
        if (!confirm('Are you sure you want to delete this staff member?')) return;

        try {
            const res = await App.api('staff.php', {
                method: 'POST',
                body: { action: 'delete', id }
            });
            App.toast(res.message, 'success');
            await this.loadStaff();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    // Availability Matrix Modal Handlers
    async openAvailabilityModal(staffId) {
        this.currentStaffId = staffId;
        const staff = this.staffList.find(s => parseInt(s.id, 10) === staffId);
        if (!staff) return;

        document.getElementById('availStaffName').innerText = `${staff.full_name} (${staff.staff_code})`;

        try {
            const [matrixRes, periodsRes] = await Promise.all([
                App.api(`staff.php?action=get_availability&staff_id=${staffId}`),
                App.api('schedule_settings.php?action=get')
            ]);

            const matrix = matrixRes.data;
            const periods = periodsRes.data.periods;
            const workingDays = JSON.parse(periodsRes.data.settings.working_days || '["Monday","Tuesday","Wednesday","Thursday","Friday"]');

            this.availabilityMatrix = {};
            matrix.forEach(m => {
                const k = `${m.day_of_week}_${m.period_index}`;
                this.availabilityMatrix[k] = parseInt(m.is_available, 10);
            });

            this.renderAvailabilityTable(workingDays, periods);
            App.openModal('availabilityModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    renderAvailabilityTable(workingDays, periods) {
        const tableContainer = document.getElementById('availTableContainer');
        if (!tableContainer) return;

        let html = `
            <table class="avail-matrix-table">
                <thead>
                    <tr>
                        <th style="background:var(--bg-surface); color:var(--text-secondary); text-align:left;">Day \\ Period</th>
        `;

        periods.forEach(p => {
            html += `<th>${App.escapeHtml(p.period_name)}<br><span style="font-size:10.5px; font-weight:normal; color:var(--text-muted);">${App.formatTime(p.start_time)}</span></th>`;
        });

        html += `</tr></thead><tbody>`;

        workingDays.forEach((dayName, idx) => {
            const dayNum = idx + 1;
            html += `
                <tr>
                    <td style="font-weight:700; color:var(--text-white); text-align:left; background:rgba(17,24,39,0.5);">${App.escapeHtml(dayName)}</td>
            `;

            periods.forEach(p => {
                const key = `${dayNum}_${p.period_index}`;
                const isAvail = this.availabilityMatrix[key] !== undefined ? this.availabilityMatrix[key] : 1;

                html += `
                    <td>
                        <button type="button" class="avail-slot-btn ${isAvail === 1 ? 'available' : 'unavailable'}" 
                            data-day="${dayNum}" data-period="${p.period_index}" 
                            onclick="StaffController.toggleSlotAvailability(this)">
                            ${isAvail === 1 ? '✓ Avail' : '✕ Unavail'}
                        </button>
                    </td>
                `;
            });

            html += `</tr>`;
        });

        html += `</tbody></table>`;
        tableContainer.innerHTML = html;
    },

    toggleSlotAvailability(btn) {
        const day = btn.dataset.day;
        const period = btn.dataset.period;
        const key = `${day}_${period}`;

        const currentVal = this.availabilityMatrix[key] !== undefined ? this.availabilityMatrix[key] : 1;
        const newVal = currentVal === 1 ? 0 : 1;

        this.availabilityMatrix[key] = newVal;

        if (newVal === 1) {
            btn.className = 'avail-slot-btn available';
            btn.innerText = '✓ Avail';
        } else {
            btn.className = 'avail-slot-btn unavailable';
            btn.innerText = '✕ Unavail';
        }
    },

    setAllAvailability(val) {
        const buttons = document.querySelectorAll('.avail-slot-btn');
        buttons.forEach(btn => {
            const day = btn.dataset.day;
            const period = btn.dataset.period;
            const key = `${day}_${period}`;
            this.availabilityMatrix[key] = val;

            if (val === 1) {
                btn.className = 'avail-slot-btn available';
                btn.innerText = '✓ Avail';
            } else {
                btn.className = 'avail-slot-btn unavailable';
                btn.innerText = '✕ Unavail';
            }
        });
    },

    async saveAvailability() {
        if (!this.currentStaffId) return;

        const slots = [];
        for (const [k, val] of Object.entries(this.availabilityMatrix)) {
            const [d, p] = k.split('_');
            slots.push({
                day_of_week: parseInt(d, 10),
                period_index: parseInt(p, 10),
                is_available: val,
                preference: val === 1 ? 'neutral' : 'unavailable'
            });
        }

        try {
            const res = await App.api('staff.php', {
                method: 'POST',
                body: {
                    action: 'save_availability',
                    staff_id: this.currentStaffId,
                    slots: JSON.stringify(slots)
                }
            });

            App.toast(res.message, 'success');
            App.closeModal('availabilityModal');
        } catch (err) {
            App.toast(err.message, 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('staffView')) {
        StaffController.init();
    }
});
