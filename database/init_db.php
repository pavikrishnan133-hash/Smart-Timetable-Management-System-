<?php
/**
 * Smart Academic Timetable Management System
 * Database Installer & Migration Script
 */

require_once __DIR__ . '/../config/database.php';

$isCli = (php_sapi_name() === 'cli');

function logMsg(string $msg, string $type = 'info'): void {
    global $isCli;
    if ($isCli) {
        echo "[{$type}] {$msg}\n";
    } else {
        $color = ($type === 'success') ? '#10B981' : (($type === 'error') ? '#EF4444' : '#3B82F6');
        echo "<div style='font-family: sans-serif; padding: 6px 12px; margin: 4px 0; border-left: 4px solid {$color}; background: #F8FAFC;'><strong>[" . strtoupper($type) . "]</strong> " . htmlspecialchars($msg) . "</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Database Initialization - B.Voc Timetable</title><style>body{font-family: Inter, system-ui, sans-serif; background: #0F172A; color: #E2E8F0; padding: 40px; display: flex; justify-content: center;} .card{background: #1E293B; border-radius: 12px; padding: 30px; max-width: 650px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5);} h1{color: #38BDF8; font-size: 22px; margin-bottom: 20px;} .btn{display: inline-block; margin-top: 20px; background: #38BDF8; color: #0F172A; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-decoration: none;}</style></head><body><div class='card'><h1>Smart Academic Timetable - Database Setup</h1>";
}

try {
    logMsg("Connecting to MySQL server at " . DB_HOST . ":" . DB_PORT . "...", "info");
    $rootPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    logMsg("Creating database `" . DB_NAME . "` if not exists...", "info");
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $rootPdo->exec("USE `" . DB_NAME . "`;");

    logMsg("Executing Schema Definitions (database/schema.sql)...", "info");
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at " . $schemaFile);
    }
    
    $schemaSql = file_get_contents($schemaFile);
    $rootPdo->exec($schemaSql);
    try {
        $rootPdo->exec("ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) DEFAULT 'B.Voc SD & SA' AFTER `designation`");
    } catch (Exception $e) {
        // column may already exist in older MySQL without IF NOT EXISTS
    }
    logMsg("Schema tables created successfully.", "success");

    logMsg("Executing Seed Data (database/seed_data.sql)...", "info");
    $seedFile = __DIR__ . '/seed_data.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("Seed data file not found at " . $seedFile);
    }
    
    $seedSql = file_get_contents($seedFile);
    $rootPdo->exec($seedSql);
    logMsg("Initial seed data loaded successfully.", "success");

    // Ensure default admin password hash is crisp & valid
    $adminPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
    $updateAdmin = $rootPdo->prepare("UPDATE `admins` SET `password_hash` = :hash WHERE `username` = 'admin'");
    $updateAdmin->execute([':hash' => $adminPasswordHash]);
    logMsg("Default Admin credentials verified: admin / admin123", "success");

    // Count records
    $staffCount = $rootPdo->query("SELECT COUNT(*) FROM `staff`")->fetchColumn();
    $subCount = $rootPdo->query("SELECT COUNT(*) FROM `subjects`")->fetchColumn();
    $yearsCount = $rootPdo->query("SELECT COUNT(*) FROM `academic_years`")->fetchColumn();
    $roomsCount = $rootPdo->query("SELECT COUNT(*) FROM `classrooms`")->fetchColumn();
    $studentsCount = $rootPdo->query("SELECT COUNT(*) FROM `students`")->fetchColumn();

    logMsg("Database summary: {$staffCount} Faculty/Staff, {$studentsCount} Students, {$subCount} Subjects, {$yearsCount} Academic Years, {$roomsCount} Classrooms.", "success");
    logMsg("Smart Academic Timetable database is fully prepared and ready for use!", "success");

    if (!$isCli) {
        echo "<a href='../auth/login.php' class='btn'>Launch Timetable Application &rarr;</a></div></body></html>";
    }

} catch (Exception $e) {
    logMsg("Initialization Failed: " . $e->getMessage(), "error");
    if (!$isCli) {
        echo "<p style='color:#EF4444;'>Error: " . htmlspecialchars($e->getMessage()) . "</p></div></body></html>";
    }
    exit(1);
}
