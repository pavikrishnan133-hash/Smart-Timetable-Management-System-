<?php
$pageTitle = 'Master Academic Timetable';
$pageSubtitle = 'Interactive 2D Timetable Grid with Class, Faculty, and Classroom Views';
$pageScript = 'timetable-view.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="timetableView">
    <!-- Institutional Print Header (Shown only during printing) -->
    <div class="print-header" style="display: none;">
        <div class="print-college-name">SCHOOL OF VOCATIONAL STUDIES & ADVANCED TECHNOLOGY</div>
        <div class="print-dept-name">DEPARTMENT OF B.VOC (SOFTWARE DEVELOPMENT & SYSTEM ADMINISTRATION)</div>
        <div id="printTimetableTitle" class="print-tt-title">MASTER ACADEMIC TIMETABLE</div>
    </div>

    <!-- Controls Bar -->
    <div class="card no-print" style="margin-bottom: 25px;">
        <div class="card-body" style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <!-- View Mode Switcher -->
            <div style="display: flex; gap: 6px; background: var(--bg-surface); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <button type="button" id="tabViewClass" class="btn btn-secondary btn-sm view-tab-btn active" style="border: none;">
                    🎓 Class View
                </button>
                <button type="button" id="tabViewFaculty" class="btn btn-secondary btn-sm view-tab-btn" style="border: none;">
                    👤 Faculty View
                </button>
                <button type="button" id="tabViewRoom" class="btn btn-secondary btn-sm view-tab-btn" style="border: none;">
                    🏛 Classroom View
                </button>
            </div>

            <!-- Target Selection Dropdown -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Select Target:</span>
                <select id="timetableTargetSelect" class="form-control" style="width: 260px; font-weight: 600; padding: 8px 14px;">
                    <!-- Injected via JavaScript -->
                </select>
            </div>

            <!-- Actions: Print, Clear, Regenerate -->
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="TimetableView.printTimetable()" title="Print A4 Landscape Sheet">
                    🖨 Print / PDF
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="TimetableView.clearAllSlots()" title="Clear Unpinned Slots">
                    🗑 Clear Unpinned
                </button>
                <a href="<?= $baseUrl ?>/admin/generate_timetable.php" class="btn btn-primary btn-sm">
                    ⚡ Regenerate
                </a>
            </div>
        </div>
    </div>

    <!-- Dynamic Conflict Alert Banner -->
    <div id="timetableConflictAlerts" style="display: none;">
        <!-- Injected via JavaScript -->
    </div>

    <!-- Master Timetable Grid Wrapper -->
    <div id="timetableGridContainer" class="timetable-wrapper">
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            Loading master timetable matrix...
        </div>
    </div>

    <!-- Print Signatures Block (Visible only in print) -->
    <div class="print-signatures" style="display: none;">
        <div class="sig-box">Timetable Coordinator</div>
        <div class="sig-box">Head of Department (HOD)</div>
        <div class="sig-box">Dean / Principal</div>
    </div>
</div>

<!-- Interactive Manual Slot Editor Modal -->
<div id="slotEditorModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="slotModalTitle" class="modal-title">Edit Timetable Slot</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('slotEditorModal')">&times;</button>
        </div>
        <form id="slotEditorForm">
            <div class="modal-body">
                <input type="hidden" id="editSlotEntryId">
                <input type="hidden" id="editSlotDay">
                <input type="hidden" id="editSlotPeriod">

                <!-- Real-time Clash Warning Banner in Modal -->
                <div id="slotValidationWarningBox" class="conflict-banner" style="display: none; margin-bottom: 15px; padding: 10px 14px;">
                    <!-- Injected via JS -->
                </div>

                <div class="form-group">
                    <label class="form-label" for="editSlotYear">Academic Year / Class *</label>
                    <select id="editSlotYear" class="form-control" required>
                        <!-- Injected via JavaScript -->
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editSlotSubject">Subject to Assign *</label>
                    <select id="editSlotSubject" class="form-control" required>
                        <!-- Injected via JavaScript -->
                    </select>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="editSlotFaculty">Assigned Faculty *</label>
                        <select id="editSlotFaculty" class="form-control" required>
                            <!-- Injected via JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editSlotRoom">Assigned Classroom / Lab *</label>
                        <select id="editSlotRoom" class="form-control" required>
                            <!-- Injected via JavaScript -->
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editSlotNotes">Notes / Special Instructions (Optional)</label>
                    <input type="text" id="editSlotNotes" class="form-control" placeholder="e.g. Bring Practical Records">
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="editSlotLocked">
                        <span style="font-weight: 600; color: var(--warning);">🔒 Lock (Pin) this Slot</span>
                    </label>
                    <small style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">
                        Locked slots are preserved and will not be altered during automated regeneration.
                    </small>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: space-between;">
                <button type="button" id="btnDeleteSlot" class="btn btn-danger btn-sm" onclick="TimetableView.deleteActiveSlot()">
                    🗑 Clear Slot
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="App.closeModal('slotEditorModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
