<?php
$pageTitle = 'Smart Timetable Generator';
$pageSubtitle = 'Automated Constraint Satisfaction Engine with Live Quality Scoring & Conflict Detection';
$pageScript = 'generator.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="generatorView">
    <!-- Generator Wizard Launch Card -->
    <div class="card" style="margin-bottom: 25px; background: linear-gradient(135deg, rgba(30, 27, 75, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%); border: 1px solid rgba(99, 102, 241, 0.3);">
        <div class="card-body" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div style="max-width: 650px;">
                    <div class="quality-grade-badge primary" style="margin-bottom: 10px;">⚡ Automated Constraint Solver</div>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-white); margin-bottom: 8px;">Intelligent Academic Schedule Engine</h2>
                    <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">
                        Generates a balanced, conflict-free timetable across all active academic years concurrently. Respects subject weekly required hours, assigned faculty, faculty workload limits, availability matrices, and allocates 2-period lab blocks in specialized computer/hardware labs.
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; min-width: 280px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="genAcademicYear">Generation Scope</label>
                        <select id="genAcademicYear" class="form-control" style="background: var(--bg-card); font-weight: 600;">
                            <!-- Injected via JavaScript -->
                        </select>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); cursor: pointer;">
                        <input type="checkbox" id="genPreserveLocked" checked>
                        <span>Preserve Locked / Pinned Slots</span>
                    </label>

                    <button id="btnStartGeneration" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 20px rgba(59, 130, 246, 0.5);">
                        ⚡ Start Intelligent Generation
                    </button>
                </div>
            </div>

            <!-- Progress status box -->
            <div id="genProgressBox" style="display: none; margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">
                    <span id="genProgressStatus">Evaluating constraints and availability...</span>
                </div>
                <div class="progress-track" style="height: 8px;">
                    <div id="genProgressBar" class="progress-fill" style="width: 20%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Column Quality Score & Conflict Audit Breakdown -->
    <div class="grid-2col">
        <!-- Quality Score Metrics Breakdown -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📊 Timetable Quality Score Analysis</span>
                <span id="genGradeBadge" class="quality-grade-badge success">Grade A+</span>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 25px; margin-bottom: 25px;">
                    <div class="score-circle-container">
                        <svg class="score-circle-svg" viewBox="0 0 120 120">
                            <circle class="score-circle-bg" cx="60" cy="60" r="52"></circle>
                            <circle id="genScoreCircle" class="score-circle-fill" cx="60" cy="60" r="52"></circle>
                        </svg>
                        <div class="score-center-text">
                            <div class="score-number" id="genScoreValue">--</div>
                            <div class="score-denom">/ 100</div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; margin-bottom: 4px;">Constraint Satisfaction Index</h4>
                        <p style="font-size: 12.5px; color: var(--text-secondary);">
                            Comprehensive evaluation across syllabus completeness, lab continuity, faculty workload balance, and daily subject distribution.
                        </p>
                    </div>
                </div>

                <!-- Detailed Component Progress Bars -->
                <div id="genMetricsContainer">
                    <!-- Injected via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Real-Time Conflict Auditor & Recommendations -->
        <div style="display: flex; flex-direction: column; gap: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🛡 Conflict Detection Audit</span>
                </div>
                <div class="card-body">
                    <div id="genConflictAlertBox" class="conflict-banner warning">
                        <div class="conflict-icon">✓</div>
                        <div>
                            <strong>0 Clashes Detected</strong>
                            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                                No double-booking clashes found.
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <h5 style="font-size: 13px; font-weight: 700; color: var(--text-white); margin-bottom: 10px;">Automated Optimization Tips:</h5>
                        <ul id="genRecommendationsList" style="list-style: none; padding: 0; margin: 0;">
                            <!-- Injected via JavaScript -->
                        </ul>
                    </div>
                </div>
                <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="<?= $baseUrl ?>/admin/timetable.php" class="btn btn-primary btn-sm">Inspect Master Grid &rarr;</a>
                    <a href="<?= $baseUrl ?>/admin/reports.php" class="btn btn-secondary btn-sm">View Full Audit Report</a>
                </div>
            </div>

            <!-- Algorithm Rules Card -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📜 Solver Constraints Applied</span>
                </div>
                <div class="card-body" style="font-size: 12.5px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 8px;">
                    <div>• <strong>Hard Constraint 1:</strong> Zero faculty double-booking across any class or period.</div>
                    <div>• <strong>Hard Constraint 2:</strong> Zero classroom / computer lab collision.</div>
                    <div>• <strong>Hard Constraint 3:</strong> Respect individual faculty availability preferences.</div>
                    <div>• <strong>Hard Constraint 4:</strong> Enforce maximum weekly workload caps (HOD: 14h, Faculty: 18h).</div>
                    <div>• <strong>Hard Constraint 5:</strong> Allocate 4-hour lab subjects into 2-period contiguous blocks in computer labs.</div>
                    <div>• <strong>Soft Constraint 1:</strong> Evenly disperse theory subjects across separate days.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
