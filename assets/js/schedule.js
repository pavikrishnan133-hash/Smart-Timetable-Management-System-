/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Schedule Settings & Periods Configuration Controller
 */

const ScheduleController = {
    settings: {},
    periodsList: [],

    async init() {
        await this.loadScheduleData();
        this.bindEvents();
    },

    bindEvents() {
        const formSettings = document.getElementById('settingsForm');
        if (formSettings) {
            formSettings.addEventListener('submit', (e) => this.handleSaveSettings(e));
        }

        const btnAddPeriod = document.getElementById('btnAddPeriod');
        if (btnAddPeriod) {
            btnAddPeriod.addEventListener('click', () => this.addNewPeriodRow());
        }

        const btnSavePeriods = document.getElementById('btnSavePeriods');
        if (btnSavePeriods) {
            btnSavePeriods.addEventListener('click', () => this.handleSavePeriods());
        }
    },

    async loadScheduleData() {
        try {
            const res = await App.api('schedule_settings.php?action=get');
            this.settings = res.data.settings;
            this.periodsList = res.data.periods;

            // Fill General Settings
            document.getElementById('institutionName').value = this.settings.institution_name || 'B.Voc SD & SA Department';
            document.getElementById('collegeName').value = this.settings.college_name || '';
            document.getElementById('departmentHod').value = this.settings.department_hod || '';
            document.getElementById('academicSession').value = this.settings.default_academic_session || '2026-2027';

            // Working Days
            const workingDays = JSON.parse(this.settings.working_days || '["Monday","Tuesday","Wednesday","Thursday","Friday"]');
            const dayCheckboxes = document.querySelectorAll('.day-checkbox');
            dayCheckboxes.forEach(cb => {
                cb.checked = workingDays.includes(cb.value);
            });

            this.renderPeriodsTable();
            this.renderTimelinePreview();

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    renderPeriodsTable() {
        const container = document.getElementById('periodsTableBody');
        if (!container) return;

        container.innerHTML = this.periodsList.map((p, idx) => `
            <tr data-index="${idx}">
                <td style="font-weight:700; color:var(--primary); text-align:center;">
                    <input type="hidden" class="period-idx" value="${p.period_index || idx + 1}">
                    ${p.period_index || idx + 1}
                </td>
                <td>
                    <input type="text" class="form-control period-name" value="${App.escapeHtml(p.period_name || 'Period ' + (idx + 1))}">
                </td>
                <td>
                    <input type="time" class="form-control period-start" value="${p.start_time || '08:30'}">
                </td>
                <td>
                    <input type="time" class="form-control period-end" value="${p.end_time || '09:25'}">
                </td>
                <td>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; cursor:pointer;">
                            <input type="checkbox" class="period-break-toggle" ${parseInt(p.is_break_after, 10) === 1 ? 'checked' : ''} onchange="ScheduleController.toggleBreakInputs(this)">
                            <span>Recess / Break after this period</span>
                        </label>
                        <div class="break-details-box" style="display:${parseInt(p.is_break_after, 10) === 1 ? 'flex' : 'none'}; gap:6px;">
                            <input type="text" class="form-control break-name" placeholder="Break Name (e.g. Tea Break)" value="${App.escapeHtml(p.break_name || '')}" style="font-size:12px;">
                            <input type="time" class="form-control break-start" value="${p.break_start_time || ''}" style="width:110px; font-size:12px;">
                            <input type="time" class="form-control break-end" value="${p.break_end_time || ''}" style="width:110px; font-size:12px;">
                        </div>
                    </div>
                </td>
                <td style="text-align:center;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="ScheduleController.removePeriodRow(this)">✕</button>
                </td>
            </tr>
        `).join('');
    },

    toggleBreakInputs(checkbox) {
        const box = checkbox.closest('td').querySelector('.break-details-box');
        if (box) {
            box.style.display = checkbox.checked ? 'flex' : 'none';
        }
    },

    addNewPeriodRow() {
        const count = document.querySelectorAll('#periodsTableBody tr').length + 1;
        const newPeriod = {
            period_index: count,
            period_name: `Period ${count}`,
            start_time: '13:20:00',
            end_time: '14:15:00',
            is_break_after: 0
        };
        this.periodsList.push(newPeriod);
        this.renderPeriodsTable();
    },

    removePeriodRow(btn) {
        const row = btn.closest('tr');
        const idx = parseInt(row.dataset.index, 10);
        this.periodsList.splice(idx, 1);
        this.renderPeriodsTable();
    },

    async handleSaveSettings(e) {
        e.preventDefault();

        const selectedDays = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(cb => cb.value);

        if (selectedDays.length === 0) {
            App.toast('Please select at least one working day.', 'error');
            return;
        }

        const settingsData = {
            institution_name: document.getElementById('institutionName').value,
            college_name: document.getElementById('collegeName').value,
            department_hod: document.getElementById('departmentHod').value,
            default_academic_session: document.getElementById('academicSession').value,
            working_days: selectedDays,
            working_days_count: selectedDays.length
        };

        try {
            const res = await App.api('schedule_settings.php', {
                method: 'POST',
                body: {
                    action: 'update_settings',
                    settings: settingsData
                }
            });

            App.toast(res.message, 'success');
            await this.loadScheduleData();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async handleSavePeriods() {
        const rows = document.querySelectorAll('#periodsTableBody tr');
        const periods = [];

        rows.forEach((row, i) => {
            const idx = i + 1;
            const name = row.querySelector('.period-name').value;
            const start = row.querySelector('.period-start').value;
            const end = row.querySelector('.period-end').value;
            const isBreak = row.querySelector('.period-break-toggle').checked ? 1 : 0;
            const bName = isBreak ? row.querySelector('.break-name').value : null;
            const bStart = isBreak ? row.querySelector('.break-start').value : null;
            const bEnd = isBreak ? row.querySelector('.break-end').value : null;

            periods.push({
                period_index: idx,
                period_name: name,
                start_time: start,
                end_time: end,
                is_break_after: isBreak,
                break_name: bName,
                break_start_time: bStart,
                break_end_time: bEnd
            });
        });

        try {
            const res = await App.api('schedule_settings.php', {
                method: 'POST',
                body: {
                    action: 'update_periods',
                    periods
                }
            });

            App.toast(res.message, 'success');
            await this.loadScheduleData();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    renderTimelinePreview() {
        const container = document.getElementById('scheduleTimelinePreview');
        if (!container) return;

        let html = '<div style="display:flex; flex-direction:column; gap:10px;">';

        this.periodsList.forEach(p => {
            html += `
                <div style="display:flex; align-items:center; gap:15px; background:var(--bg-card-alt); border:1px solid var(--border-color); padding:12px 18px; border-radius:var(--radius-md);">
                    <div style="width:32px; height:32px; border-radius:50%; background:rgba(59,130,246,0.15); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700;">
                        ${p.period_index}
                    </div>
                    <div style="flex:1;">
                        <strong style="color:var(--text-white); font-size:13.5px;">${App.escapeHtml(p.period_name)}</strong>
                        <div style="font-size:12px; color:var(--text-muted);">${App.formatTime(p.start_time)} - ${App.formatTime(p.end_time)}</div>
                    </div>
                    <span class="badge-cat badge-major-theory">Active Slot</span>
                </div>
            `;

            if (parseInt(p.is_break_after, 10) === 1) {
                html += `
                    <div style="display:flex; align-items:center; gap:15px; background:rgba(245,158,11,0.08); border:1px dashed rgba(245,158,11,0.3); padding:8px 18px; border-radius:var(--radius-md); margin: -2px 0;">
                        <div style="font-size:18px;">☕</div>
                        <div style="flex:1;">
                            <strong style="color:var(--warning); font-size:12.5px;">${App.escapeHtml(p.break_name || 'Recess Break')}</strong>
                            <div style="font-size:11px; color:var(--text-muted);">${App.formatTime(p.break_start_time)} - ${App.formatTime(p.break_end_time)}</div>
                        </div>
                        <span class="badge-cat badge-common-theory">Break Time</span>
                    </div>
                `;
            }
        });

        html += '</div>';
        container.innerHTML = html;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('scheduleView')) {
        ScheduleController.init();
    }
});
