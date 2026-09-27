<?php
// Sidebar Include
$db = getDB();
$totalStaff = (int)$db->query("SELECT COUNT(*) FROM staff WHERE is_active = 1")->fetchColumn();
$totalStudents = (int)$db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$totalSubjects = (int)$db->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1")->fetchColumn();
$totalYears = (int)$db->query("SELECT COUNT(*) FROM academic_years WHERE is_active = 1")->fetchColumn();
$totalRooms = (int)$db->query("SELECT COUNT(*) FROM classrooms WHERE is_active = 1")->fetchColumn();
$totalConflicts = (int)$db->query("SELECT COUNT(*) FROM conflict_logs")->fetchColumn();
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-icon">
            <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Emblem" style="width: 28px; height: 28px;">
        </div>
        <div class="sidebar-brand-info">
            <span class="sidebar-brand-title">SMART TIMETABLE</span>
            <span class="sidebar-brand-subtitle">B.Voc SD & SA</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Navigation</div>

        <a href="<?= $baseUrl ?>/admin/dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="<?= $baseUrl ?>/admin/timetable.php" class="nav-item <?= $currentPage === 'timetable' ? 'active' : '' ?>">
            <span class="nav-icon">📅</span>
            <span>Master Timetable</span>
        </a>

        <a href="<?= $baseUrl ?>/admin/generate_timetable.php" class="nav-item <?= $currentPage === 'generate_timetable' ? 'active' : '' ?>">
            <span class="nav-icon">⚡</span>
            <span>Smart Generator</span>
            <span class="nav-badge" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border: none;">AI</span>
        </a>

        <div class="nav-section-title">Academic Management</div>

        <a href="<?= $baseUrl ?>/admin/staff.php" class="nav-item <?= $currentPage === 'staff' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            <span>Staff / Faculty</span>
            <span class="nav-badge"><?= $totalStaff ?></span>
        </a>

        <a href="<?= $baseUrl ?>/admin/students.php" class="nav-item <?= $currentPage === 'students' ? 'active' : '' ?>">
            <span class="nav-icon">🎓</span>
            <span>Students</span>
            <span class="nav-badge"><?= $totalStudents ?></span>
        </a>

        <a href="<?= $baseUrl ?>/admin/subjects.php" class="nav-item <?= $currentPage === 'subjects' ? 'active' : '' ?>">
            <span class="nav-icon">📚</span>
            <span>Subjects</span>
            <span class="nav-badge"><?= $totalSubjects ?></span>
        </a>

        <a href="<?= $baseUrl ?>/admin/academic_years.php" class="nav-item <?= $currentPage === 'academic_years' ? 'active' : '' ?>">
            <span class="nav-icon">🏫</span>
            <span>Academic Years</span>
            <span class="nav-badge"><?= $totalYears ?></span>
        </a>

        <a href="<?= $baseUrl ?>/admin/classrooms.php" class="nav-item <?= $currentPage === 'classrooms' ? 'active' : '' ?>">
            <span class="nav-icon">🏛</span>
            <span>Classrooms & Labs</span>
            <span class="nav-badge"><?= $totalRooms ?></span>
        </a>

        <div class="nav-section-title">Reports & Analytics</div>

        <a href="<?= $baseUrl ?>/admin/reports.php" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
            <span class="nav-icon">📑</span>
            <span>Reports & Exports</span>
            <?php if ($totalConflicts > 0): ?>
                <span class="nav-badge" style="background: var(--danger); color: white; border: none;"><?= $totalConflicts ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? 'Administrator') ?></div>
                <div class="user-role"><?= htmlspecialchars($currentUser['role'] ?? 'Admin') ?></div>
            </div>
            <a href="<?= $baseUrl ?>/auth/logout.php" title="Sign Out" style="color: var(--text-muted); font-size: 18px;">
                🚪
            </a>
        </div>
    </div>
</aside>
