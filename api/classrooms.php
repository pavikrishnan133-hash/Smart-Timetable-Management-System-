<?php
/**
 * Smart Academic Timetable Management System
 * Classrooms & Specialized Labs Management API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireApiAuth();
$db = getDB();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'list':
        $stmt = $db->query("
            SELECT 
                r.*,
                COUNT(DISTINCT e.id) as scheduled_periods_count
            FROM classrooms r
            LEFT JOIN timetable_entries e ON r.id = e.classroom_id
            GROUP BY r.id
            ORDER BY r.room_type DESC, r.room_number ASC
        ");
        $rooms = $stmt->fetchAll();
        sendJsonResponse(true, $rooms);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM classrooms WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $room = $stmt->fetch();
        if (!$room) {
            sendJsonResponse(false, 'Classroom not found.', [], 404);
        }
        sendJsonResponse(true, $room);
        break;

    case 'create':
        $roomNumber = strtoupper(trim($_POST['room_number'] ?? ''));
        $roomName = trim($_POST['room_name'] ?? '');
        $roomType = trim($_POST['room_type'] ?? 'Theory Classroom');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $blockFloor = trim($_POST['block_floor'] ?? 'Main Block - 2nd Floor');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($roomNumber) || empty($roomName)) {
            sendJsonResponse(false, 'Room Number and Room Name are required.', [], 400);
        }

        $chk = $db->prepare("SELECT id FROM classrooms WHERE room_number = :num");
        $chk->execute([':num' => $roomNumber]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Room Number '{$roomNumber}' already exists.", [], 409);
        }

        $stmt = $db->prepare("
            INSERT INTO classrooms (`room_number`, `room_name`, `room_type`, `capacity`, `block_floor`, `is_active`)
            VALUES (:num, :name, :type, :cap, :floor, :active)
        ");
        $stmt->execute([
            ':num' => $roomNumber,
            ':name' => $roomName,
            ':type' => $roomType,
            ':cap' => $capacity,
            ':floor' => $blockFloor,
            ':active' => $isActive
        ]);

        sendJsonResponse(true, 'Classroom/Lab added successfully.', ['id' => (int)$db->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $roomNumber = strtoupper(trim($_POST['room_number'] ?? ''));
        $roomName = trim($_POST['room_name'] ?? '');
        $roomType = trim($_POST['room_type'] ?? 'Theory Classroom');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $blockFloor = trim($_POST['block_floor'] ?? 'Main Block - 2nd Floor');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (!$id || empty($roomNumber) || empty($roomName)) {
            sendJsonResponse(false, 'ID, Room Number, and Room Name are required.', [], 400);
        }

        $chk = $db->prepare("SELECT id FROM classrooms WHERE room_number = :num AND id != :id");
        $chk->execute([':num' => $roomNumber, ':id' => $id]);
        if ($chk->fetch()) {
            sendJsonResponse(false, "Room Number '{$roomNumber}' already exists.", [], 409);
        }

        $stmt = $db->prepare("
            UPDATE classrooms 
            SET `room_number` = :num, `room_name` = :name, `room_type` = :type, 
                `capacity` = :cap, `block_floor` = :floor, `is_active` = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':num' => $roomNumber,
            ':name' => $roomName,
            ':type' => $roomType,
            ':cap' => $capacity,
            ':floor' => $blockFloor,
            ':active' => $isActive,
            ':id' => $id
        ]);

        sendJsonResponse(true, 'Classroom updated successfully.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(false, 'Classroom ID required.', [], 400);
        }

        $stmt = $db->prepare("DELETE FROM classrooms WHERE id = :id");
        $stmt->execute([':id' => $id]);

        sendJsonResponse(true, 'Classroom deleted successfully.');
        break;

    default:
        sendJsonResponse(false, 'Invalid action specified.', [], 400);
        break;
}
