/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Timetable Generator & Quality Scoring Wizard Controller
 */

const GeneratorController = {
    academicYears: [],

    async init() {
        await this.loadAcademicYears();
        await this.loadCurrentScore();
        this.bindEvents();
    },

    bindEvents() {
        const btnRun = document.getElementById('btnStartGeneration');
        if (btnRun) {
            btnRun.addEventListener('click', () => this.runGeneration());
        }

        const selYear = document.getElementById('genAcademicYear');
        if (selYear) {
            selYear.addEventListener('change', () => this.loadCurrentScore());
        }
    },

    async loadAcademicYears() {
        try {
            const res = await App.api('academic_years.php?action=list');
            this.academicYears = res.data;

            const sel = document.getElementById('genAcademicYear');
            if (sel) {
                sel.innerHTML = '<option value="">All Academic Years (Concurrent Generation)</option>' +
                    this.academicYears.map(y => `<option value="${y.id}">${App.escapeHtml(y.year_name)} (${y.semester})</option>`).join('');
            }
        } catch (err) {
            console.error('Failed to load academic years:', err);
        }
    },

    async loadCurrentScore() {
        const yearId = document.getElementById('genAcademicYear') ? document.getElementById('genAcademicYear').value : '';
        let url = 'generator.php?action=quality_score';
        if (yearId) url += `&academic_year_id=${yearId}`;

        try {
            const res = await App.api(url);
            this.renderScoreUI(res.data);
        } catch (err) {
            console.error('Failed to load quality score:', err);
        }
    },

    renderScoreUI(q) {
        const scoreVal = document.getElementById('genScoreValue');
        const gradeBadge = document.getElementById('genGradeBadge');
        const circle = document.getElementById('genScoreCircle');

        if (scoreVal) scoreVal.innerText = q.score;
        if (gradeBadge) {
            gradeBadge.className = `quality-grade-badge ${q.badge_color}`;
            gradeBadge.innerText = `Grade ${q.grade} • ${q.grade_text}`;
        }
        if (circle) {
            const offset = 326 - (326 * (q.score / 100));
            circle.style.strokeDashoffset = offset;
        }

        const metricsContainer = document.getElementById('genMetricsContainer');
        if (metricsContainer && q.metrics) {
            let html = '';
            for (const [k, m] of Object.entries(q.metrics)) {
                html += `
                    <div style="background:var(--bg-card-alt); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div>
                                <strong style="color:var(--text-white); font-size:13.5px;">${App.escapeHtml(m.name)}</strong>
                                <span class="badge-cat badge-major-theory" style="margin-left:8px; font-size:10.5px;">${m.status || 'Good'}</span>
                            </div>
                            <strong style="color:var(--primary); font-size:14px;">${m.points} / ${m.max_points} pts (${m.percentage}%)</strong>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width:${m.percentage}%;"></div>
                        </div>
                    </div>
                `;
            }
            metricsContainer.innerHTML = html;
        }

        const recoList = document.getElementById('genRecommendationsList');
        if (recoList && q.recommendations) {
            recoList.innerHTML = q.recommendations.map(r => `
                <li style="display:flex; gap:8px; margin-bottom:8px; font-size:13px; color:var(--text-secondary);">
                    <span style="color:var(--primary); font-weight:bold;">▸</span>
                    <span>${App.escapeHtml(r)}</span>
                </li>
            `).join('');
        }

        // Conflict alert box
        const conflictBox = document.getElementById('genConflictAlertBox');
        if (conflictBox) {
            const totalConflicts = q.total_conflicts_count || 0;
            if (totalConflicts === 0) {
                conflictBox.className = 'conflict-banner warning';
                conflictBox.style.borderColor = 'rgba(16,185,129,0.4)';
                conflictBox.style.background = 'rgba(16,185,129,0.1)';
                conflictBox.innerHTML = `
                    <div class="conflict-icon" style="color:var(--success);">✓</div>
                    <div>
                        <strong style="color:var(--success);">0 Clashes Detected</strong>
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">No double-booked staff or classrooms. Workload constraints satisfied.</div>
                    </div>
                `;
            } else {
                conflictBox.className = 'conflict-banner';
                conflictBox.innerHTML = `
                    <div class="conflict-icon">⚠</div>
                    <div>
                        <strong style="color:var(--danger);">${totalConflicts} Issue(s) / Conflict(s) Present</strong>
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Review details in the Quality Audit Report or regenerate timetable.</div>
                    </div>
                `;
            }
        }
    },

    async runGeneration() {
        const yearId = document.getElementById('genAcademicYear').value;
        const preserveLocked = document.getElementById('genPreserveLocked').checked ? 1 : 0;

        const btn = document.getElementById('btnStartGeneration');
        const progressBox = document.getElementById('genProgressBox');
        const progressBar = document.getElementById('genProgressBar');
        const progressStatus = document.getElementById('genProgressStatus');

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '⚙ Processing Constraints...';
        }

        if (progressBox) progressBox.style.display = 'block';
        if (progressBar) progressBar.style.width = '30%';
        if (progressStatus) progressStatus.innerText = 'Evaluating faculty workloads & room suitability...';

        setTimeout(() => {
            if (progressBar) progressBar.style.width = '70%';
            if (progressStatus) progressStatus.innerText = 'Allocating 2-period lab blocks and theory slots...';
        }, 300);

        try {
            const res = await App.api('generator.php', {
                method: 'POST',
                body: {
                    action: 'generate',
                    academic_year_id: yearId,
                    preserve_locked: preserveLocked
                }
            });

            if (progressBar) progressBar.style.width = '100%';
            if (progressStatus) progressStatus.innerText = 'Finalizing Quality Score & Audit Logs...';

            setTimeout(async () => {
                App.toast(res.message || 'Timetable generated successfully!', 'success');
                if (progressBox) progressBox.style.display = 'none';
                await this.loadCurrentScore();
            }, 400);

        } catch (err) {
            App.toast(err.message, 'error');
            if (progressBox) progressBox.style.display = 'none';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '⚡ Start Intelligent Generation';
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('generatorView')) {
        GeneratorController.init();
    }
});
