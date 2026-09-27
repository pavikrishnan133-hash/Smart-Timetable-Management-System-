<?php
$pageTitle = 'Reports & Quality Audit';
$pageSubtitle = 'Institutional analytics, staff workload reports, classroom utilization, and conflict audit logs';
$pageScript = 'reports.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="reportsView">
    <!-- Reports Navigation Bar -->
    <div class="card no-print" style="margin-bottom: 25px;">
        <div class="card-body" style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" id="reportTabWorkload" class="btn btn-secondary btn-sm report-tab-btn active">
                    👥 Faculty Workload Report
                </button>
                <button type="button" id="reportTabRoom" class="btn btn-secondary btn-sm report-tab-btn">
                    🏛 Classroom Utilization Report
                </button>
                <button type="button" id="reportTabAudit" class="btn btn-secondary btn-sm report-tab-btn">
                    🛡 Timetable Conflict & Quality Audit
                </button>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="ReportsController.printActiveReport()">
                    🖨 Print Report
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="ReportsController.exportWorkloadCsv()">
                    📥 Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Section 1: Faculty Workload Report -->
    <div id="sectionWorkloadReport" class="report-section">
        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title">👥 Faculty Teaching Workload Report</span>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Tracks individual weekly teaching hours, theory vs lab distribution, and workload compliance.
                    </p>
                </div>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff Code</th>
                            <th>Faculty Name</th>
                            <th>Role</th>
                            <th>Max Limit</th>
                            <th>Theory (Hrs)</th>
                            <th>Lab (Hrs)</th>
                            <th>Total Assigned</th>
                            <th>Utilization</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="workloadTableBody">
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                Loading workload analytics...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Classroom Utilization Report -->
    <div id="sectionRoomReport" class="report-section" style="display: none;">
        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title">🏛 Classroom & Specialized Lab Utilization</span>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Monitors occupancy rates and equipment load across theory halls and computer labs.
                    </p>
                </div>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Facility Name & Location</th>
                            <th>Facility Type</th>
                            <th>Capacity</th>
                            <th>Occupied Slots</th>
                            <th>Utilization Rate</th>
                        </tr>
                    </thead>
                    <tbody id="roomTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                Loading room utilization data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 3: Timetable Conflict & Quality Audit Report -->
    <div id="sectionAuditReport" class="report-section" style="display: none;">
        <div class="grid-equal-2col" style="margin-bottom: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Overall Timetable Quality Score</span>
                </div>
                <div class="card-body" style="text-align: center; padding: 35px 20px;">
                    <div style="font-size: 48px; font-weight: 800; color: var(--text-white);" id="auditQualityScore">--</div>
                    <div style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">Out of 100 Points</div>
                    <div style="margin-top: 15px;">
                        <span id="auditQualityGrade" class="quality-grade-badge success" style="font-size: 13px; padding: 4px 14px;">Grade A+</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">🔍 Audit Verification Highlights</span>
                </div>
                <div class="card-body" style="font-size: 13px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 10px;">
                    <div>✓ <strong>Hard Clash Detector:</strong> Multi-class double-booking audit.</div>
                    <div>✓ <strong>Room Integrity:</strong> Specialized computer lab assignment validation.</div>
                    <div>✓ <strong>Workload Caps:</strong> Verification of faculty maximum hours.</div>
                    <div>✓ <strong>Consecutive Labs:</strong> 2-period contiguous lab allocation check.</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">📋 Comprehensive Anomaly & Conflict Log</span>
            </div>
            <div class="card-body" id="auditConflictList">
                <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                    Auditing current schedule...
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
