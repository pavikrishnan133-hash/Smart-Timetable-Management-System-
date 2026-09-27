<?php
$pageTitle = 'Schedule Settings & Timings';
$pageSubtitle = 'Configure working days, period timings, lunch/tea breaks, and departmental parameters';
$pageScript = 'schedule.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="scheduleView">
    <div class="grid-2col">
        <!-- Main Form Settings -->
        <div style="display: flex; flex-direction: column; gap: 25px;">
            <!-- General Parameters & Working Days -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">⚙ Working Days & Department Parameters</span>
                </div>
                <form id="settingsForm">
                    <div class="card-body">
                        <div class="grid-equal-2col" style="margin-bottom: 0;">
                            <div class="form-group">
                                <label class="form-label" for="institutionName">Department Title *</label>
                                <input type="text" id="institutionName" class="form-control" value="B.Voc SD & SA Department" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="collegeName">College / School Title</label>
                                <input type="text" id="collegeName" class="form-control" value="School of Vocational Studies & Advanced Technology">
                            </div>
                        </div>

                        <div class="grid-equal-2col" style="margin-bottom: 0;">
                            <div class="form-group">
                                <label class="form-label" for="departmentHod">Head of Department (HOD)</label>
                                <input type="text" id="departmentHod" class="form-control" value="Dr. Ramesh Kumar">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="academicSession">Academic Session</label>
                                <input type="text" id="academicSession" class="form-control" value="2026-2027">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Working Days (Select all active days)</label>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 6px;">
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                foreach ($days as $d):
                                ?>
                                    <label style="display: flex; align-items: center; gap: 6px; background: var(--bg-surface); padding: 8px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer;">
                                        <input type="checkbox" class="day-checkbox" value="<?= $d ?>" <?= $d !== 'Saturday' ? 'checked' : '' ?>>
                                        <span style="font-weight: 600; font-size: 13px; color: var(--text-white);"><?= $d ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </div>
                </form>
            </div>

            <!-- Periods & Breaks Builder -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <span class="card-title">⏱ Periods & Break Timings Builder</span>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            Configure individual period duration, start/end hours, and tea/lunch breaks.
                        </p>
                    </div>
                    <button type="button" id="btnAddPeriod" class="btn btn-secondary btn-sm">＋ Add Period</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="data-table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center;">#</th>
                                    <th>Period Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Break / Recess Configuration</th>
                                    <th style="text-align: center; width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="periodsTableBody">
                                <!-- Injected via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer" style="display: flex; justify-content: flex-end;">
                    <button type="button" id="btnSavePeriods" class="btn btn-primary">Save Period Timings</button>
                </div>
            </div>
        </div>

        <!-- Right Side: Interactive Schedule Timeline Preview -->
        <div>
            <div class="card" style="position: sticky; top: 90px;">
                <div class="card-header">
                    <span class="card-title">📋 Live Daily Schedule Timeline</span>
                </div>
                <div class="card-body" id="scheduleTimelinePreview">
                    <!-- Injected via JavaScript -->
                </div>
                <div class="card-footer" style="font-size: 12px; color: var(--text-muted);">
                    Default Schedule: 5 Periods × 55 mins + 15 min Refreshment Break.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
