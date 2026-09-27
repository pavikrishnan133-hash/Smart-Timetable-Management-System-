<?php
$pageTitle = 'Staff & Faculty Management';
$pageSubtitle = 'Manage department faculty, roles, workload limits, and individual availability matrices';
$pageScript = 'staff.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="staffView">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">👥 Faculty & Staff Directory</span>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Configure maximum weekly hours (Default: HOD=14 hrs, Faculty=18 hrs) and customize teaching availability.
                </p>
            </div>
            <button id="btnAddStaff" class="btn btn-primary">
                ＋ Add New Faculty
            </button>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Faculty Name & Designation</th>
                        <th>Role</th>
                        <th>Contact Information</th>
                        <th>Workload (Assigned / Max)</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="staffTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Loading staff directory...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Staff Modal -->
<div id="staffModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="staffModalTitle" class="modal-title">Add New Faculty</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('staffModal')">&times;</button>
        </div>
        <form id="staffForm">
            <div class="modal-body">
                <input type="hidden" id="staffId">

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="staffCode">Staff Code *</label>
                        <input type="text" id="staffCode" class="form-control" placeholder="e.g. FAC008" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="staffRole">Role *</label>
                        <select id="staffRole" class="form-control" required>
                            <option value="Faculty">Faculty (Default 18 hrs max)</option>
                            <option value="HOD">HOD (Default 14 hrs max)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="staffFullName">Full Name & Title *</label>
                    <input type="text" id="staffFullName" class="form-control" placeholder="e.g. Dr. Rajesh Sharma" required>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="staffEmail">Email Address *</label>
                        <input type="email" id="staffEmail" class="form-control" placeholder="e.g. rajesh@bvoc-sdsa.edu" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="staffPhone">Phone Number</label>
                        <input type="text" id="staffPhone" class="form-control" placeholder="e.g. +91 98401 00000">
                    </div>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="staffDesignation">Designation *</label>
                        <input type="text" id="staffDesignation" class="form-control" placeholder="e.g. Assistant Professor" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="staffMaxHours">Max Weekly Workload (Hours) *</label>
                        <input type="number" id="staffMaxHours" class="form-control" min="2" max="35" value="18" required>
                        <small style="color: var(--text-muted); font-size: 11px;">Admin configurable maximum teaching periods per week.</small>
                    </div>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="staffColor">Timetable Tag Color</label>
                        <input type="color" id="staffColor" class="form-control" value="#3B82F6" style="height: 42px; padding: 4px; cursor: pointer;">
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="staffIsActive" checked>
                            <span style="font-weight: 600; color: var(--text-primary);">Active Status</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('staffModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Faculty Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Availability Matrix Modal -->
<div id="availabilityModal" class="modal-backdrop">
    <div class="modal-dialog lg">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Staff Availability Matrix</h3>
                <div id="availStaffName" style="font-size: 12.5px; color: var(--primary); font-weight: 600; margin-top: 2px;"></div>
            </div>
            <button type="button" class="modal-close" onclick="App.closeModal('availabilityModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 15px;">
                Click any slot to toggle availability (Green = Available, Red = Unavailable). The generator will strictly avoid assigning slots when marked unavailable.
            </p>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 12px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="StaffController.setAllAvailability(1)">Mark All Available</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="StaffController.setAllAvailability(0)">Mark All Unavailable</button>
            </div>

            <div id="availTableContainer" class="data-table-wrapper">
                <!-- Injected via JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('availabilityModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="StaffController.saveAvailability()">Save Availability Matrix</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
