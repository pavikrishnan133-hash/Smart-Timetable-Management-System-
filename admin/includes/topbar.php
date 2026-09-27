<?php
// Topbar Include
$db = getDB();
$academicYearsList = $db->query("SELECT id, year_name, semester FROM academic_years WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// Determine the currently selected academic year from URL param
$selectedYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

// If no year is selected via URL and there are years available, default to the first one
if ($selectedYearId === 0 && !empty($academicYearsList)) {
    $selectedYearId = (int)$academicYearsList[0]['id'];
}

// Build base page URL to redirect back to the same page when switching years
$currentScript = $_SERVER['PHP_SELF'] ?? '/admin/dashboard.php';
// Rebuild query string WITHOUT academic_year_id so we can re-append it fresh
$existingParams = $_GET;
unset($existingParams['academic_year_id']);
$extraQuery = !empty($existingParams) ? '&' . http_build_query($existingParams) : '';
$yearSwitchBase = htmlspecialchars($currentScript) . '?academic_year_id=';
?>
<main class="app-main">
    <header class="app-topbar">
        <div class="topbar-left">
            <button id="mobileSidebarToggle" class="btn btn-secondary btn-sm" style="display: none; padding: 6px 10px;">
                ☰
            </button>
            <div class="page-title-box">
                <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                <p><?= htmlspecialchars($pageSubtitle ?? 'Department of Software Development & System Administration') ?></p>
            </div>
        </div>

        <div class="topbar-right">
            <!-- Active Academic Year Quick Switch -->
            <div class="academic-year-pill">
                <span>🎓 Session:</span>
                <select id="globalSessionSelect" onchange="window.location.href='<?= $yearSwitchBase ?>'+this.value+'<?= addslashes($extraQuery) ?>'">
                    <?php foreach ($academicYearsList as $y): ?>
                        <option value="<?= $y['id'] ?>" <?= (int)$y['id'] === $selectedYearId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['year_name']) ?> (<?= htmlspecialchars($y['semester']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Quick Action: Generate Timetable -->
            <a href="<?= $baseUrl ?>/admin/generate_timetable.php" class="btn btn-primary btn-sm">
                ⚡ Generate Schedule
            </a>

            <!-- User Menu -->
            <a href="<?= $baseUrl ?>/auth/logout.php" class="btn btn-secondary btn-sm" title="Log Out">
                Logout
            </a>
        </div>
    </header>

    <div class="app-content">
