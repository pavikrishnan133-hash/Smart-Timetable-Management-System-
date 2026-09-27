<?php
$pageTitle = 'Classrooms & Specialized Labs';
$pageSubtitle = 'Manage lecture halls, software development labs, system administration & hardware facilities';
$pageScript = 'classrooms.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div id="classroomsView">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">🏛 Classrooms & Specialized Labs</span>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Define room types (Theory Classroom, Computer Lab, General Lab) and seating capacities.
                </p>
            </div>
            <button id="btnAddClassroom" class="btn btn-primary">
                ＋ Add Classroom / Lab
            </button>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Facility Name & Location</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="classroomsTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Loading classrooms & labs...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Classroom Modal -->
<div id="classroomModal" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="classroomModalTitle" class="modal-title">Add Classroom / Lab</h3>
            <button type="button" class="modal-close" onclick="App.closeModal('classroomModal')">&times;</button>
        </div>
        <form id="classroomForm">
            <div class="modal-body">
                <input type="hidden" id="classroomId">

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="roomNumber">Room / Lab Number *</label>
                        <input type="text" id="roomNumber" class="form-control" placeholder="e.g. LAB-SD01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="roomType">Classroom Type *</label>
                        <select id="roomType" class="form-control" required>
                            <option value="Theory Classroom">Theory Classroom</option>
                            <option value="Computer Lab">Computer Lab</option>
                            <option value="General Lab">General Lab</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="roomName">Classroom / Lab Name *</label>
                    <input type="text" id="roomName" class="form-control" placeholder="e.g. Software Development & Cloud Lab" required>
                </div>

                <div class="grid-equal-2col" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" for="roomCapacity">Seating / PC Capacity *</label>
                        <input type="number" id="roomCapacity" class="form-control" min="5" max="200" value="40" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="roomFloor">Block & Floor Location</label>
                        <input type="text" id="roomFloor" class="form-control" placeholder="e.g. IT Wing - 3rd Floor">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="roomIsActive" checked>
                        <span style="font-weight: 600; color: var(--text-primary);">Available for Scheduling</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('classroomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Facility</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
