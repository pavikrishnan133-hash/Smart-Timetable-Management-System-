<?php
/**
 * Smart Academic Timetable Management System
 * Schedule Settings & Period Configuration API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

switch ($action) {
    case 'get':
        $stmt = $db->query("SELECT setting_key, setting_value FROM schedule_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $periodStmt = $db->query("SELECT * FROM periods ORDER BY period_index ASC");
        $periods = $periodStmt->fetchAll();

        sendJsonResponse(true, [
            'settings' => $settings,
            'periods' => $periods
        ]);
        break;

    case 'update_settings':
        $settingsData = $_POST['settings'] ?? [];
        if (is_string($settingsData)) {
            $settingsData = json_decode($settingsData, true) ?: [];
        }

        if (empty($settingsData)) {
            sendJsonResponse(false, 'No settings provided.', [], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO schedule_settings (`setting_key`, `setting_value`)
            VALUES (:key, :val)
            ON DUPLICATE KEY UPDATE `setting_value` = :val
        ");

        $db->beginTransaction();
        foreach ($settingsData as $key => $val) {
            $valStr = is_array($val) ? json_encode($val) : (string)$val;
            $stmt->execute([':key' => $key, ':val' => $valStr]);
        }
        $db->commit();

        sendJsonResponse(true, 'Schedule settings updated successfully.');
        break;

    case 'update_periods':
        $periodsList = $_POST['periods'] ?? [];
        if (is_string($periodsList)) {
            $periodsList = json_decode($periodsList, true) ?: [];
        }

        if (empty($periodsList)) {
            sendJsonResponse(false, 'No period configurations provided.', [], 400);
        }

        $db->beginTransaction();
        $db->exec("DELETE FROM periods");

        $stmt = $db->prepare("
            INSERT INTO periods 
            (`period_index`, `period_name`, `start_time`, `end_time`, `is_break_after`, `break_name`, `break_start_time`, `break_end_time`)
            VALUES (:idx, :name, :start, :end, :isBreak, :bName, :bStart, :bEnd)
        ");

        foreach ($periodsList as $idx => $p) {
            $periodIndex = $p['period_index'] ?? ($idx + 1);
            $periodName = $p['period_name'] ?? ("Period " . $periodIndex);
            $startTime = $p['start_time'] ?? '08:30:00';
            $endTime = $p['end_time'] ?? '09:25:00';
            $isBreak = !empty($p['is_break_after']) ? 1 : 0;
            $breakName = $isBreak ? ($p['break_name'] ?? 'Break') : null;
            $breakStart = $isBreak ? ($p['break_start_time'] ?? null) : null;
            $breakEnd = $isBreak ? ($p['break_end_time'] ?? null) : null;

            $stmt->execute([
                ':idx' => $periodIndex,
                ':name' => $periodName,
                ':start' => $startTime,
                ':end' => $endTime,
                ':isBreak' => $isBreak,
                ':bName' => $breakName,
                ':bStart' => $breakStart,
                ':bEnd' => $breakEnd
            ]);
        }

        // Update periods_per_day in schedule_settings
        $updCount = $db->prepare("
            INSERT INTO schedule_settings (`setting_key`, `setting_value`)
            VALUES ('periods_per_day', :count)
            ON DUPLICATE KEY UPDATE `setting_value` = :count
        ");
        $updCount->execute([':count' => count($periodsList)]);

        $db->commit();
        sendJsonResponse(true, 'Period definitions updated successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
