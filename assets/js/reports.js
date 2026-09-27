/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Reports, Workload Analytics & Audit Export Controller
 */

const ReportsController = {
    activeReportTab: 'workload',

    async init() {
        this.bindEvents();
        await this.loadWorkloadReport();
    },

    bindEvents() {
        const tabWorkload = document.getElementById('reportTabWorkload');
        const tabRoom = document.getElementById('reportTabRoom');
        const tabAudit = document.getElementById('reportTabAudit');

        if (tabWorkload) {
            tabWorkload.addEventListener('click', () => {
                this.switchTab('workload');
                this.loadWorkloadReport();
            });
        }
        if (tabRoom) {
            tabRoom.addEventListener('click', () => {
                this.switchTab('room');
                this.loadRoomReport();
            });
        }
        if (tabAudit) {
            tabAudit.addEventListener('click', () => {
                this.switchTab('audit');
                this.loadAuditReport();
            });
        }
    },

    switchTab(tab) {
        this.activeReportTab = tab;
        document.querySelectorAll('.report-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.report-section').forEach(s => s.style.display = 'none');

        if (tab === 'workload') {
            document.getElementById('reportTabWorkload').classList.add('active');
            document.getElementById('sectionWorkloadReport').style.display = 'block';
        } else if (tab === 'room') {
            document.getElementById('reportTabRoom').classList.add('active');
            document.getElementById('sectionRoomReport').style.display = 'block';
        } else if (tab === 'audit') {
            document.getElementById('reportTabAudit').classList.add('active');
            document.getElementById('sectionAuditReport').style.display = 'block';
        }
    },

    async loadWorkloadReport() {
        const tableBody = document.getElementById('workloadTableBody');
        if (!tableBody) return;

        try {
            const res = await App.api('reports.php?action=workload_report');
            const data = res.data;

            tableBody.innerHTML = data.map(row => {
                const total = parseInt(row.total_scheduled_hours || 0, 10);
                const max = parseInt(row.max_weekly_hours, 10);
                const isOverloaded = row.is_overloaded;

                return `
                    <tr>
                        <td>
                            <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(row.staff_code)}</span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(row.full_name)}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(row.designation)}</div>
                        </td>
                        <td>
                            <span class="badge-cat ${row.role === 'HOD' ? 'badge-project' : 'badge-major-theory'}">${App.escapeHtml(row.role)}</span>
                        </td>
                        <td><strong style="color:var(--text-white);">${row.max_weekly_hours} hrs</strong></td>
                        <td>${row.theory_hours || 0} hrs</td>
                        <td><span style="color:var(--info);">${row.lab_hours || 0} hrs</span></td>
                        <td>
                            <strong style="color:${isOverloaded ? 'var(--danger)' : 'var(--success)'}; font-size:15px;">
                                ${total} hrs
                            </strong>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div class="progress-track" style="flex:1; height:6px;">
                                    <div class="progress-fill" style="width:${Math.min(100, row.utilization_percentage)}%; background:${isOverloaded ? 'var(--danger)' : 'var(--primary)'};"></div>
                                </div>
                                <span style="font-size:11.5px; font-weight:700; color:var(--text-secondary); width:40px;">${row.utilization_percentage}%</span>
                            </div>
                        </td>
                        <td>
                            <span class="quality-grade-badge ${isOverloaded ? 'danger' : 'success'}" style="font-size:11px; padding:2px 8px;">
                                ${isOverloaded ? '⚠ Overloaded' : '✓ Balanced'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async loadRoomReport() {
        const tableBody = document.getElementById('roomTableBody');
        if (!tableBody) return;

        try {
            const res = await App.api('reports.php?action=room_utilization');
            const data = res.data;

            tableBody.innerHTML = data.map(r => `
                <tr>
                    <td>
                        <span style="font-family:'JetBrains Mono', monospace; font-weight:700; color:var(--primary);">${App.escapeHtml(r.room_number)}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--text-white);">${App.escapeHtml(r.room_name)}</div>
                        <div style="font-size:11.5px; color:var(--text-muted);">${App.escapeHtml(r.block_floor || '')}</div>
                    </td>
                    <td>
                        <span class="badge-cat ${r.room_type.includes('Lab') ? 'badge-major-lab' : 'badge-major-theory'}">${App.escapeHtml(r.room_type)}</span>
                    </td>
                    <td>${r.capacity} Seats</td>
                    <td>
                        <strong style="color:var(--text-white);">${r.occupied_slots} / ${r.total_capacity_slots} slots</strong>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="progress-track" style="flex:1; height:6px;">
                                <div class="progress-fill" style="width:${r.utilization_percentage}%;"></div>
                            </div>
                            <span style="font-size:11.5px; font-weight:700; color:var(--text-secondary); width:40px;">${r.utilization_percentage}%</span>
                        </div>
                    </td>
                </tr>
            `).join('');

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async loadAuditReport() {
        try {
            const res = await App.api('reports.php?action=conflict_audit');
            const data = res.data;

            const scoreEl = document.getElementById('auditQualityScore');
            const gradeBadge = document.getElementById('auditQualityGrade');
            const conflictList = document.getElementById('auditConflictList');

            if (scoreEl) scoreEl.innerText = data.quality_score;
            if (gradeBadge) {
                gradeBadge.className = `quality-grade-badge ${data.quality_details?.badge_color || 'success'}`;
                gradeBadge.innerText = `Grade ${data.quality_grade} • ${data.quality_details?.grade_text || ''}`;
            }

            if (conflictList) {
                if (data.conflicts.length === 0) {
                    conflictList.innerHTML = `
                        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:var(--radius-md); padding:25px; text-align:center; color:var(--success);">
                            <div style="font-size:32px; margin-bottom:8px;">✓</div>
                            <div style="font-weight:700; font-size:15px;">Zero Timetable Conflicts Detected</div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">All academic schedules are fully verified and meet departmental guidelines.</div>
                        </div>
                    `;
                } else {
                    conflictList.innerHTML = data.conflicts.map(c => `
                        <div class="conflict-banner ${c.severity === 'high' ? '' : 'warning'}" style="margin-bottom:12px;">
                            <div class="conflict-icon">${c.severity === 'high' ? '✖' : '⚠'}</div>
                            <div style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="color:var(--text-white); font-size:13.5px;">${App.escapeHtml(c.type.toUpperCase().replace(/_/g, ' '))}</strong>
                                    <span class="quality-grade-badge ${c.severity === 'high' ? 'danger' : 'warning'}" style="font-size:10.5px; padding:2px 7px;">${App.escapeHtml(c.severity.toUpperCase())}</span>
                                </div>
                                <div style="font-size:12.5px; color:var(--text-secondary); margin-top:4px;">${App.escapeHtml(c.message)}</div>
                            </div>
                        </div>
                    `).join('');
                }
            }

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    exportWorkloadCsv() {
        window.location.href = `${App.baseUrl}/api/reports.php?action=export_workload_csv`;
    },

    printActiveReport() {
        window.print();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('reportsView')) {
        ReportsController.init();
    }
});
