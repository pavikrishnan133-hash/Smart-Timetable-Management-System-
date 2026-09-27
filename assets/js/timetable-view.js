/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Interactive Master Timetable Viewer & Manual Slot Editor
 */

const TimetableView = {
    viewType: 'class', // 'class', 'faculty', 'classroom'
    currentId: null,
    gridData: {},
    periodsList: [],
    workingDays: [],
    allSubjects: [],
    allStaff: [],
    allClassrooms: [],
    allYears: [],

    activeEditCell: {
        yearId: null,
        day: null,
        period: null,
        entryId: null
    },

    async init() {
        await Promise.all([
            this.loadLookupData(),
            this.loadAcademicYearsFilter()
        ]);
        this.bindEvents();
        await this.loadGrid();
    },

    bindEvents() {
        const tabClass = document.getElementById('tabViewClass');
        const tabFaculty = document.getElementById('tabViewFaculty');
        const tabRoom = document.getElementById('tabViewRoom');
        const selector = document.getElementById('timetableTargetSelect');

        if (tabClass) {
            tabClass.addEventListener('click', () => this.switchView('class'));
        }
        if (tabFaculty) {
            tabFaculty.addEventListener('click', () => this.switchView('faculty'));
        }
        if (tabRoom) {
            tabRoom.addEventListener('click', () => this.switchView('classroom'));
        }
        if (selector) {
            selector.addEventListener('change', () => {
                this.currentId = selector.value;
                this.loadGrid();
            });
        }

        const slotForm = document.getElementById('slotEditorForm');
        if (slotForm) {
            slotForm.addEventListener('submit', (e) => this.handleSaveSlot(e));
        }

        // Live validation change listeners in Slot Editor modal
        const selFaculty = document.getElementById('editSlotFaculty');
        const selRoom = document.getElementById('editSlotRoom');
        const selSubject = document.getElementById('editSlotSubject');

        if (selSubject) {
            selSubject.addEventListener('change', () => {
                const subId = parseInt(selSubject.value, 10);
                const sub = this.allSubjects.find(s => s.id === subId);
                if (sub) {
                    if (selFaculty) selFaculty.value = sub.assigned_faculty_id;
                    if (selRoom) {
                        // find matching room
                        const room = this.allClassrooms.find(r => r.room_type === sub.preferred_room_type);
                        if (room) selRoom.value = room.id;
                    }
                }
                this.validateSlotLive();
            });
        }
        if (selFaculty) selFaculty.addEventListener('change', () => this.validateSlotLive());
        if (selRoom) selRoom.addEventListener('change', () => this.validateSlotLive());
    },

    async loadLookupData() {
        try {
            const [subRes, staffRes, roomsRes, yearsRes] = await Promise.all([
                App.api('subjects.php?action=list'),
                App.api('staff.php?action=list'),
                App.api('classrooms.php?action=list'),
                App.api('academic_years.php?action=list')
            ]);

            this.allSubjects = subRes.data;
            this.allStaff = staffRes.data;
            this.allClassrooms = roomsRes.data;
            this.allYears = yearsRes.data;

            // Populate Modal Selects
            const editFaculty = document.getElementById('editSlotFaculty');
            const editRoom = document.getElementById('editSlotRoom');
            const editYear = document.getElementById('editSlotYear');

            if (editFaculty) {
                editFaculty.innerHTML = this.allStaff.map(s => `<option value="${s.id}">${App.escapeHtml(s.full_name)} (${s.role})</option>`).join('');
            }
            if (editRoom) {
                editRoom.innerHTML = this.allClassrooms.map(r => `<option value="${r.id}">${App.escapeHtml(r.room_number)} - ${App.escapeHtml(r.room_name)} (${r.room_type})</option>`).join('');
            }
            if (editYear) {
                editYear.innerHTML = this.allYears.map(y => `<option value="${y.id}">${App.escapeHtml(y.year_name)}</option>`).join('');
            }

        } catch (err) {
            console.error('Failed to load lookups:', err);
        }
    },

    async loadAcademicYearsFilter() {
        this.updateTargetSelector();
    },

    updateTargetSelector() {
        const selector = document.getElementById('timetableTargetSelect');
        if (!selector) return;

        if (this.viewType === 'class') {
            selector.innerHTML = this.allYears.map(y => `<option value="${y.id}" ${this.currentId == y.id ? 'selected' : ''}>🎓 ${App.escapeHtml(y.year_name)} (${y.semester})</option>`).join('');
            if (!this.currentId && this.allYears.length > 0) this.currentId = this.allYears[0].id;
        } else if (this.viewType === 'faculty') {
            selector.innerHTML = this.allStaff.map(s => `<option value="${s.id}" ${this.currentId == s.id ? 'selected' : ''}>👤 ${App.escapeHtml(s.full_name)} (${s.role})</option>`).join('');
            if (!this.currentId && this.allStaff.length > 0) this.currentId = this.allStaff[0].id;
        } else if (this.viewType === 'classroom') {
            selector.innerHTML = this.allClassrooms.map(r => `<option value="${r.id}" ${this.currentId == r.id ? 'selected' : ''}>🏛 ${App.escapeHtml(r.room_number)} (${r.room_type})</option>`).join('');
            if (!this.currentId && this.allClassrooms.length > 0) this.currentId = this.allClassrooms[0].id;
        }
    },

    switchView(type) {
        this.viewType = type;
        this.currentId = null;

        document.querySelectorAll('.view-tab-btn').forEach(btn => btn.classList.remove('active'));
        if (type === 'class') document.getElementById('tabViewClass').classList.add('active');
        if (type === 'faculty') document.getElementById('tabViewFaculty').classList.add('active');
        if (type === 'classroom') document.getElementById('tabViewRoom').classList.add('active');

        this.updateTargetSelector();
        this.loadGrid();
    },

    async loadGrid() {
        const tableContainer = document.getElementById('timetableGridContainer');
        if (!tableContainer) return;

        let url = `timetable.php?action=get_grid&view_type=${this.viewType}`;
        if (this.currentId) url += `&id=${this.currentId}`;

        try {
            const res = await App.api(url);
            const data = res.data;

            this.periodsList = data.periods;
            this.workingDays = data.working_days;
            this.gridData = data.grid || {};

            this.renderGridTable(data);
            this.renderClashAlerts(data.conflicts || []);

            // Set Print Title
            const printTitle = document.getElementById('printTimetableTitle');
            if (printTitle) {
                const targetText = document.getElementById('timetableTargetSelect').options[document.getElementById('timetableTargetSelect').selectedIndex]?.text || '';
                printTitle.innerText = `ACADEMIC TIMETABLE - ${targetText.toUpperCase()} (B.Voc SD & SA)`;
            }

        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    renderGridTable(data) {
        const container = document.getElementById('timetableGridContainer');
        if (!container) return;

        let html = `
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="day-column-header">Day / Time</th>
        `;

        this.periodsList.forEach(p => {
            html += `
                <th>
                    <div>${App.escapeHtml(p.period_name)}</div>
                    <div style="font-size:11px; font-weight:normal; color:var(--text-muted); margin-top:2px;">
                        ${App.formatTime(p.start_time)} - ${App.formatTime(p.end_time)}
                    </div>
                </th>
            `;
        });

        html += `</tr></thead><tbody>`;

        this.workingDays.forEach((dayName, idx) => {
            const dayNum = idx + 1;
            html += `
                <tr>
                    <td class="day-cell">${App.escapeHtml(dayName)}</td>
            `;

            this.periodsList.forEach(p => {
                const pIdx = p.period_index;
                const entry = this.gridData[dayNum] ? this.gridData[dayNum][pIdx] : null;

                html += `<td>`;

                if (entry) {
                    const isLocked = parseInt(entry.is_locked, 10) === 1;
                    const catClass = entry.subject_category?.includes('Lab') ? 'badge-major-lab' : 'badge-major-theory';

                    html += `
                        <div class="timetable-slot-card" 
                            style="border-left-color: ${entry.faculty_color || 'var(--primary)'};"
                            onclick="TimetableView.openSlotEditor(${entry.academic_year_id}, ${dayNum}, ${pIdx}, ${entry.id})">
                            
                            ${isLocked ? '<div class="slot-lock-indicator" title="Locked Slot">🔒</div>' : ''}
                            
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span class="slot-subject-code">${App.escapeHtml(entry.subject_code)}</span>
                                <span class="badge-cat ${catClass}" style="font-size:9.5px; padding:1px 5px;">${App.escapeHtml(entry.subject_category || 'Theory')}</span>
                            </div>

                            <div class="slot-subject-name">${App.escapeHtml(entry.subject_name)}</div>

                            <div class="slot-faculty-badge">
                                <span>👤</span>
                                <span>${App.escapeHtml(entry.faculty_name)}</span>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                                <div class="slot-room-badge">
                                    🏛 ${App.escapeHtml(entry.room_number)}
                                </div>
                                ${this.viewType !== 'class' ? `<span style="font-size:10px; color:var(--text-muted);">${App.escapeHtml(entry.year_name || '')}</span>` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    const targetYearId = this.viewType === 'class' ? this.currentId : (this.allYears[0]?.id || 1);
                    html += `
                        <div class="slot-empty" onclick="TimetableView.openSlotEditor(${targetYearId}, ${dayNum}, ${pIdx}, null)">
                            + Empty Slot
                        </div>
                    `;
                }

                html += `</td>`;
            });

            html += `</tr>`;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;
    },

    renderClashAlerts(conflicts) {
        const box = document.getElementById('timetableConflictAlerts');
        if (!box) return;

        if (conflicts.length === 0) {
            box.style.display = 'none';
            return;
        }

        box.style.display = 'block';
        box.innerHTML = `
            <div class="conflict-banner" style="margin-bottom:15px;">
                <div class="conflict-icon">⚠</div>
                <div>
                    <strong style="color:var(--danger);">${conflicts.length} Timetable Conflict(s) / Warning(s) in this Schedule</strong>
                    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                        ${conflicts.map(c => `<div>• ${App.escapeHtml(c.message)}</div>`).join('')}
                    </div>
                </div>
            </div>
        `;
    },

    // Slot Editor Modal
    openSlotEditor(yearId, day, period, entryId) {
        this.activeEditCell = { yearId, day, period, entryId };

        const dayName = this.workingDays[day - 1] || `Day ${day}`;
        const periodObj = this.periodsList.find(p => parseInt(p.period_index, 10) === period);
        const periodName = periodObj ? periodObj.period_name : `Period ${period}`;

        document.getElementById('slotModalTitle').innerText = `Edit Slot: ${dayName}, ${periodName}`;
        document.getElementById('editSlotYear').value = yearId;
        document.getElementById('editSlotDay').value = day;
        document.getElementById('editSlotPeriod').value = period;
        document.getElementById('editSlotEntryId').value = entryId || '';

        // Filter subjects for this academic year
        const subSelect = document.getElementById('editSlotSubject');
        const yearSubjects = this.allSubjects.filter(s => parseInt(s.academic_year_id, 10) === parseInt(yearId, 10));

        if (subSelect) {
            subSelect.innerHTML = '<option value="">Select Subject</option>' +
                yearSubjects.map(s => `<option value="${s.id}">${App.escapeHtml(s.subject_code)} - ${App.escapeHtml(s.subject_name)} (${s.weekly_hours}h - ${s.category})</option>`).join('');
        }

        const entry = this.gridData[day] ? this.gridData[day][period] : null;

        if (entry && entryId) {
            document.getElementById('editSlotSubject').value = entry.subject_id;
            document.getElementById('editSlotFaculty').value = entry.faculty_id;
            document.getElementById('editSlotRoom').value = entry.classroom_id;
            document.getElementById('editSlotLocked').checked = parseInt(entry.is_locked, 10) === 1;
            document.getElementById('editSlotNotes').value = entry.notes || '';
            document.getElementById('btnDeleteSlot').style.display = 'inline-flex';
        } else {
            document.getElementById('editSlotSubject').value = '';
            document.getElementById('editSlotFaculty').value = this.allStaff[0]?.id || '';
            document.getElementById('editSlotRoom').value = this.allClassrooms[0]?.id || '';
            document.getElementById('editSlotLocked').checked = false;
            document.getElementById('editSlotNotes').value = '';
            document.getElementById('btnDeleteSlot').style.display = 'none';
        }

        document.getElementById('slotValidationWarningBox').style.display = 'none';
        App.openModal('slotEditorModal');
        this.validateSlotLive();
    },

    async validateSlotLive() {
        const yearId = document.getElementById('editSlotYear').value;
        const day = document.getElementById('editSlotDay').value;
        const period = document.getElementById('editSlotPeriod').value;
        const facultyId = document.getElementById('editSlotFaculty').value;
        const classroomId = document.getElementById('editSlotRoom').value;
        const entryId = document.getElementById('editSlotEntryId').value;

        if (!yearId || !facultyId || !classroomId) return;

        try {
            const res = await App.api('timetable.php', {
                method: 'POST',
                body: {
                    action: 'validate_slot',
                    academic_year_id: yearId,
                    day_of_week: day,
                    period_index: period,
                    faculty_id: facultyId,
                    classroom_id: classroomId,
                    exclude_entry_id: entryId
                }
            });

            const warningBox = document.getElementById('slotValidationWarningBox');
            if (res.data.has_conflict && res.data.warnings.length > 0) {
                warningBox.style.display = 'block';
                warningBox.innerHTML = res.data.warnings.map(w => `
                    <div style="font-size:12px; color:${w.type === 'unavailable_slot' ? 'var(--warning)' : 'var(--danger)'}; margin-bottom:4px;">
                        ⚠ ${App.escapeHtml(w.message)}
                    </div>
                `).join('');
            } else {
                warningBox.style.display = 'none';
            }
        } catch (err) {
            console.error('Validation error:', err);
        }
    },

    async handleSaveSlot(e) {
        e.preventDefault();

        const payload = {
            action: 'save_slot',
            academic_year_id: document.getElementById('editSlotYear').value,
            day_of_week: document.getElementById('editSlotDay').value,
            period_index: document.getElementById('editSlotPeriod').value,
            subject_id: document.getElementById('editSlotSubject').value,
            faculty_id: document.getElementById('editSlotFaculty').value,
            classroom_id: document.getElementById('editSlotRoom').value,
            is_locked: document.getElementById('editSlotLocked').checked ? 1 : 0,
            notes: document.getElementById('editSlotNotes').value,
            force_save: 1
        };

        if (!payload.subject_id) {
            App.toast('Please select a subject to assign to this slot.', 'error');
            return;
        }

        try {
            const res = await App.api('timetable.php', { method: 'POST', body: payload });
            App.toast(res.message, 'success');
            App.closeModal('slotEditorModal');
            await this.loadGrid();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async deleteActiveSlot() {
        const { yearId, day, period, entryId } = this.activeEditCell;
        if (!confirm('Are you sure you want to clear this timetable slot?')) return;

        try {
            const res = await App.api('timetable.php', {
                method: 'POST',
                body: {
                    action: 'delete_slot',
                    entry_id: entryId,
                    academic_year_id: yearId,
                    day_of_week: day,
                    period_index: period
                }
            });

            App.toast(res.message, 'success');
            App.closeModal('slotEditorModal');
            await this.loadGrid();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    async clearAllSlots() {
        if (!confirm('Are you sure you want to clear all unpinned timetable slots for this view?')) return;

        try {
            const res = await App.api('timetable.php', {
                method: 'POST',
                body: {
                    action: 'clear_all',
                    academic_year_id: this.viewType === 'class' ? this.currentId : '',
                    preserve_locked: 1
                }
            });

            App.toast(res.message, 'success');
            await this.loadGrid();
        } catch (err) {
            App.toast(err.message, 'error');
        }
    },

    printTimetable() {
        window.print();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('timetableView')) {
        TimetableView.init();
    }
});
