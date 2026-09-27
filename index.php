<?php
/**
 * SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
 * Entry Point Router
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header("Location: " . getBaseUrl() . "/admin/dashboard.php");
} else {
    header("Location: " . getBaseUrl() . "/auth/login.php");
}
exit;
