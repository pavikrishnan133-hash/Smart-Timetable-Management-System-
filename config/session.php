<?php
/**
 * Smart Academic Timetable Management System
 * Session and Security Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Check if an administrator is currently authenticated
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get current logged in admin data array
 */
function getLoggedInAdmin(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'full_name' => $_SESSION['admin_full_name'] ?? 'Administrator',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'Admin',
        'avatar' => $_SESSION['admin_avatar'] ?? null
    ];
}

/**
 * Require authentication for page views. Redirects to login if not logged in.
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $baseUrl = getBaseUrl();
        header("Location: {$baseUrl}/auth/login.php");
        exit;
    }
}

/**
 * Require authentication for API endpoints. Returns 401 JSON if not logged in.
 */
function requireApiAuth(): void {
    if (!isLoggedIn()) {
        sendJsonResponse(false, 'Unauthorized. Please log in to proceed.', [], 401);
    }
}

/**
 * Generate CSRF token for forms
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to get project base URL dynamically
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine script directory relative to document root
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Strip subdirectories like /admin, /api, /auth, /database
    $cleanPath = preg_replace('#/(admin|api|auth|database|config)(/.*)?$#', '', $scriptDir);
    $cleanPath = rtrim($cleanPath, '/');
    
    return $protocol . $host . $cleanPath;
}

/**
 * Clean & sanitize text input
 */
function sanitizeInput(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON API response and exit
 */
function sendJsonResponse(bool $success, $messageOrData, array $extra = [], int $httpCode = 200): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
    }
    
    $response = ['success' => $success];
    
    if (is_string($messageOrData)) {
        $response['message'] = $messageOrData;
    } elseif (is_array($messageOrData)) {
        $response['data'] = $messageOrData;
    }
    
    $response = array_merge($response, $extra);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
