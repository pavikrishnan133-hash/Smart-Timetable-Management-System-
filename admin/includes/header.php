<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
requireAuth();

$currentUser = getLoggedInAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Dashboard') ?> - Smart Academic Timetable (B.Voc SD & SA)</title>
    
    <!-- Design System & Custom Styling -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/print.css" media="print">
    <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>/assets/images/logo.svg">
</head>
<body>
    <div class="app-container">
