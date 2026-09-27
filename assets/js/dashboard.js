/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Dashboard Controller with Slide-In Drawers & Real-Time KPI Management
 */

const Dashboard = {
    academicYearsCache: [],

    async init() {
        await Promise.all([
            this.loadStats(),
            this.loadQualityScore(),
            this.loadConflictStatus(),
            this.preloadAcademicYears()
        ]);
    },

    async loadStats() {
        try {
            const [staffRes, subRes, yearsRes, roomsRes, studentsRes] = await Promise.all([
                App.api('staff.php?action=list'),
                App.api('subjects.php?action=list'),
                App.api('academic_years.php?action=list'),
                App.api('classrooms.php?action=list'),
                App.api('students.php?action=stats').catch(() => ({ data: { total: 0 } }))
            ]);

            const staffEl = document.getElementById('statTotalStaff');
            const subEl = document.getElementById('statTotalSubjects');
            const yearsEl = document.getElementById('statTotalYears');
            const roomsEl = document.getElementById('statTotalRooms');
            const studentsEl = document.getElementById('statTotalStudents');
            const hoursEl = document.getElementById('statScheduledHours');

            if (staffEl) staffEl.innerText = staffRes.data.length;
            if (subEl) subEl.innerText = subRes.data.length;
            if (yearsEl) yearsEl.innerText = yearsRes.data.length;
            if (roomsEl) roomsEl.innerText = roomsRes.data.length;
            if (studentsEl) studentsEl.innerText = studentsRes.data.total ?? studentsRes.data.length ?? 0;

            let totalScheduled = 0;
            yearsRes.data.forEach(y => {
                totalScheduled += parseInt(y.total_scheduled_hours || 0, 10);
            });
            if (hoursEl) hoursEl.innerText = totalScheduled;

            this.academicYearsCache = yearsRes.data;

        } catch (err) {
            console.error('Failed to load dashboard stats:', err);
        }
    },

    async preloadAcademicYears() {
        try {
            if (!this.academicYearsCache || this.academicYearsCache.length === 0) {
                const res = await App.api('academic_years.php?action=list');
                this.academicYearsCache = res.data;
            }
        } catch (err) {
            console.error('Failed to preload academic years:', err);
        }
    },

    async loadQualityScore() {
        try {
            const res = await App.api('generator.php?action=quality_score');
            const q = res.data;

            const scoreEl = document.getElementById('dashQualityScore');
            const gradeBadge = document.getElementById('dashQualityGrade');
            const circleFill = document.getElementById('dashScoreCircle');

            if (scoreEl) scoreEl.innerText = q.score;
            if (gradeBadge) {
                gradeBadge.className = `quality-grade-badge ${q.badge_color}`;
                gradeBadge.innerText = `Grade ${q.grade} • ${q.grade_text}`;
            }

            // Update SVG circle stroke (max circumference = 326)
            if (circleFill) {
                const offset = 326 - (326 * (q.score / 100));
                circleFill.style.strokeDashoffset = offset;
            }

            // Metrics progress bars
            const metricsContainer = document.getElementById('dashMetricsBars');
            if (metricsContainer && q.metrics) {
                let html = '';
                for (const [k, m] of Object.entries(q.metrics)) {
                    html += `
                        <div class="quality-metric-bar">
                            <div class="metric-bar-header">
                                <span>${App.escapeHtml(m.name)}</span>
                                <strong>${m.points} / ${m.max_points} pts (${m.percentage}%)</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: ${m.percentage}%;"></div>
                            </div>
                        </div>
                    `;
                }
                metricsContainer.innerHTML = html;
            }

            // Recommendations List
            const recoList = document.getElementById('dashRecommendations');
            if (recoList && q.recommendations) {
                recoList.innerHTML = q.recommendations.map(r => `
                    <li style="display: flex; gap: 8px; margin-bottom: 6px; font-size: 12.5px; color: var(--text-secondary);">
                        <span style="color: var(--primary);">▸</span>
                        <span>${App.escapeHtml(r)}</span>
                    </li>
                `).join('');
            }

        } catch (err) {
            console.error('Failed to load quality score:', err);
        }
    },

    async loadConflictStatus() {
        try {
            const res = await App.api('generator.php?action=audit_conflicts');
            const conflicts = res.data;
            const badge = document.getElementById('dashConflictBadge');
            const list = document.getElementById('dashConflictsList');

            if (badge) {
                if (conflicts.length === 0) {
                    badge.innerHTML = `<span class="quality-grade-badge success">✓ 0 Conflicts (Conflict-Free)</span>`;
                } else {
                    badge.innerHTML = `<span class="quality-grade-badge danger">⚠ ${conflicts.length} Conflict(s) Detected</span>`;
                }
            }

            if (list) {
                if (conflicts.length === 0) {
                    list.innerHTML = `
                        <div style="text-align:center; padding: 25px; color: var(--success);">
                            <div style="font-size: 28px; margin-bottom: 8px;">✓</div>
                            <div style="font-weight: 700; font-size: 14px;">All Schedules are Harmonized</div>
                            <div style="font-size: 12px; color: var(--text-muted);">No faculty clashes, room collisions, or workload violations detected.</div>
                        </div>
                    `;
                } else {
                    list.innerHTML = conflicts.slice(0, 5).map(c => `
                        <div class="conflict-banner ${c.severity === 'high' ? '' : 'warning'}" style="margin-bottom: 10px; padding: 10px 14px;">
                            <div class="conflict-icon">${c.severity === 'high' ? '✖' : '⚠'}</div>
                            <div style="font-size: 12.5px;">
                                <div style="font-weight: 700; color: var(--text-white);">${App.escapeHtml(c.type.toUpperCase().replace('_', ' '))}</div>
                                <div style="color: var(--text-secondary); margin-top: 2px;">${App.escapeHtml(c.message)}</div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } catch (err) {
            console.error('Failed to load conflict status:', err);
        }
    },

    /**
     * Slide-In Drawer: Add Faculty
     */
    openAddFacultyDrawer() {
        const form = document.getElementById('addFacultyForm');
        if (form) form.reset();

        const roleEl = document.getElementById('drawerFacultyRole');
        const maxHoursEl = document.getElementById('drawerFacultyMaxHours');
        const deptEl = document.getElementById('drawerFacultyDept');
        const desigEl = document.getElementById('drawerFacultyDesig');
        const colorEl = document.getElementById('drawerFacultyColor');
        const activeEl = document.getElementById('drawerFacultyIsActive');

        if (roleEl) roleEl.value = 'Faculty';
        if (maxHoursEl) maxHoursEl.value = '18';
        if (deptEl) deptEl.value = 'B.Voc SD & SA';
        if (desigEl) desigEl.value = 'Assistant Professor';
        if (colorEl) {
            const colors = ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#06B6D4', '#EC4899', '#6366F1'];
            colorEl.value = colors[Math.floor(Math.random() * colors.length)];
        }
        if (activeEl) activeEl.checked = true;

        App.openDrawer('addFacultyDrawer');
    },

    onFacultyRoleChange(role) {
        const maxHours = document.getElementById('drawerFacultyMaxHours');
        if (maxHours) {
            maxHours.value = (role === 'HOD') ? 14 : 18;
        }
    },

    async submitAddFaculty(event) {
        event.preventDefault();

        const name = document.getElementById('drawerFacultyFullName')?.value.trim();
        const code = document.getElementById('drawerFacultyCode')?.value.trim();
        const email = document.getElementById('drawerFacultyEmail')?.value.trim();
        const phone = document.getElementById('drawerFacultyPhone')?.value.trim();
        const role = document.getElementById('drawerFacultyRole')?.value || 'Faculty';
        const dept = document.getElementById('drawerFacultyDept')?.value.trim() || 'B.Voc SD & SA';
        const desig = document.getElementById('drawerFacultyDesig')?.value.trim() || 'Assistant Professor';
        const maxHours = document.getElementById('drawerFacultyMaxHours')?.value || '18';
        const color = document.getElementById('drawerFacultyColor')?.value || '#3B82F6';
        const isActive = document.getElementById('drawerFacultyIsActive')?.checked ? 1 : 0;

        // Validation
        if (!name) {
            App.toast('Please enter the Faculty Full Name.', 'warning');
            return;
        }
        if (!code) {
            App.toast('Please enter the Staff Code (e.g. FAC008).', 'warning');
            return;
        }
        if (!email) {
            App.toast('Please enter a valid email address.', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitAddFaculty');
        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Adding Faculty...';
        }

        try {
            const res = await App.api('staff.php?action=create', {
                method: 'POST',
                body: {
                    full_name: name,
                    staff_code: code,
                    email: email,
                    phone: phone,
                    role: role,
                    department: dept,
                    designation: desig,
                    max_weekly_hours: maxHours,
                    color_code: color,
                    is_active: isActive
                }
            });

            App.toast(res.message || 'Faculty member registered successfully!', 'success');
            App.closeDrawer('addFacultyDrawer');

            // Reset form
            document.getElementById('addFacultyForm')?.reset();

            // Refresh stats without full page reload
            await this.loadStats();

        } catch (err) {
            App.toast(err.message || 'Failed to add faculty member.', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText || '👥 Add Faculty';
            }
        }
    },

    /**
     * Slide-In Drawer: Add Student
     */
    async openAddStudentDrawer() {
        const form = document.getElementById('addStudentForm');
        if (form) form.reset();

        const activeEl = document.getElementById('drawerStudentIsActive');
        if (activeEl) activeEl.checked = true;

        const sectionEl = document.getElementById('drawerStudentSection');
        if (sectionEl) sectionEl.value = 'Section A';

        // Populate Academic Years dropdown
        const yearSelect = document.getElementById('drawerStudentYear');
        if (yearSelect) {
            if (!this.academicYearsCache || this.academicYearsCache.length === 0) {
                await this.preloadAcademicYears();
            }

            if (this.academicYearsCache && this.academicYearsCache.length > 0) {
                yearSelect.innerHTML = `
                    <option value="">-- Select Academic Year Batch --</option>
                    ${this.academicYearsCache.map(y => `
                        <option value="${y.id}">${App.escapeHtml(y.year_name)} (${App.escapeHtml(y.code)})</option>
                    `).join('')}
                `;
            } else {
                yearSelect.innerHTML = `<option value="">No academic years configured</option>`;
            }
        }

        App.openDrawer('addStudentDrawer');
    },

    async submitAddStudent(event) {
        event.preventDefault();

        const name = document.getElementById('drawerStudentFullName')?.value.trim();
        const regNumber = document.getElementById('drawerStudentRegNumber')?.value.trim();
        const email = document.getElementById('drawerStudentEmail')?.value.trim();
        const phone = document.getElementById('drawerStudentPhone')?.value.trim();
        const yearId = document.getElementById('drawerStudentYear')?.value;
        const section = document.getElementById('drawerStudentSection')?.value || 'Section A';
        const isActive = document.getElementById('drawerStudentIsActive')?.checked ? 1 : 0;

        // Validation
        if (!name) {
            App.toast('Please enter the Student Full Name.', 'warning');
            return;
        }
        if (!regNumber) {
            App.toast('Please enter the Register Number (e.g. 2026BVOC018).', 'warning');
            return;
        }
        if (!email) {
            App.toast('Please enter a valid email address.', 'warning');
            return;
        }
        if (!yearId) {
            App.toast('Please select an Academic Year batch.', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitAddStudent');
        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Enrolling Student...';
        }

        try {
            const res = await App.api('students.php?action=create', {
                method: 'POST',
                body: {
                    full_name: name,
                    register_number: regNumber,
                    email: email,
                    phone: phone,
                    academic_year_id: yearId,
                    section: section,
                    is_active: isActive
                }
            });

            App.toast(res.message || 'Student enrolled successfully!', 'success');
            App.closeDrawer('addStudentDrawer');

            // Reset form
            document.getElementById('addStudentForm')?.reset();

            // Refresh stats without full page reload
            await this.loadStats();

        } catch (err) {
            App.toast(err.message || 'Failed to add student.', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText || '🎓 Add Student';
            }
        }
    },

    async quickGenerate() {
        if (!confirm('Run Smart Timetable Generator now for all Academic Years? This will compute an optimal conflict-free schedule.')) {
            return;
        }

        const btn = document.getElementById('btnQuickGenerate');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Generating Schedule...';
        }

        try {
            const res = await App.api('generator.php', {
                method: 'POST',
                body: { action: 'generate', preserve_locked: 1 }
            });

            App.toast(res.message || 'Timetable generated successfully!', 'success');
            await this.init();
        } catch (err) {
            App.toast(err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '⚡ Generate Timetable';
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('dashboardView')) {
        Dashboard.init();
    }
});
