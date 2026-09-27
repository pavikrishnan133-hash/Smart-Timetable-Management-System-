<?php
/**
 * Smart Academic Timetable Management System
 * Authentication & Admin Profile API Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = getDB();

switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            sendJsonResponse(false, 'Please provide both username and password.', [], 400);
        }

        $stmt = $db->prepare("
    SELECT * FROM admins 
    WHERE username = :username 
    OR email = :email 
    LIMIT 1
");

$stmt->execute([
    ':username' => $username,
    ':email' => $username
]);

$admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Set session variables
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_full_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'Admin';
            $_SESSION['admin_avatar'] = $admin['avatar'] ?? null;
            $_SESSION['logged_in_at'] = time();

            sendJsonResponse(true, 'Login successful. Redirecting to dashboard...', [
                'redirect' => getBaseUrl() . '/admin/dashboard.php',
                'admin' => getLoggedInAdmin()
            ]);
        } else {
            sendJsonResponse(false, 'Invalid username or password. Please try again.', [], 401);
        }
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        sendJsonResponse(true, 'Logged out successfully.', [
            'redirect' => getBaseUrl() . '/auth/login.php'
        ]);
        break;

    case 'current_user':
        if (!isLoggedIn()) {
            sendJsonResponse(false, 'Not logged in', [], 401);
        }
        sendJsonResponse(true, 'Active session', ['admin' => getLoggedInAdmin()]);
        break;

    case 'update_profile':
        requireApiAuth();
        $adminId = $_SESSION['admin_id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        if (empty($fullName) || empty($email)) {
            sendJsonResponse(false, 'Full Name and Email are required.', [], 400);
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                sendJsonResponse(false, 'Password must be at least 6 characters long.', [], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE admins SET full_name = :name, email = :email, password_hash = :hash WHERE id = :id");
            $stmt->execute([':name' => $fullName, ':email' => $email, ':hash' => $hash, ':id' => $adminId]);
        } else {
            $stmt = $db->prepare("UPDATE admins SET full_name = :name, email = :email WHERE id = :id");
            $stmt->execute([':name' => $fullName, ':email' => $email, ':id' => $adminId]);
        }

        $_SESSION['admin_full_name'] = $fullName;
        $_SESSION['admin_email'] = $email;

        sendJsonResponse(true, 'Profile updated successfully.', ['admin' => getLoggedInAdmin()]);
        break;

    default:
        sendJsonResponse(false, 'Invalid authentication action specified.', [], 400);
        break;
}
