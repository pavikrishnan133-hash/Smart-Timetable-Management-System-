<?php
/**
 * Smart Academic Timetable Management System
 * Database Configuration & Connection Handler
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'timetable_management_db');

class Database {
    private static ?PDO $instance = null;

    /**
     * Get singleton PDO database connection instance
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, attempt automatic creation
                if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    self::autoCreateDatabase();
                    return self::getConnection();
                }

                // Return clean JSON if API request or throw exception
                if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database connection error: ' . $e->getMessage()
                    ]);
                    exit;
                }
                
                die("<h3>Database Connection Error</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><p>Please ensure MySQL is running in XAMPP and run <a href='../database/init_db.php'>database/init_db.php</a>.</p>");
            }
        }
        return self::$instance;
    }

    /**
     * Auto-create database if not existing
     */
    private static function autoCreateDatabase(): void {
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            // Execute schema and seed if files exist
            $schemaFile = __DIR__ . '/../database/schema.sql';
            $seedFile = __DIR__ . '/../database/seed_data.sql';
            
            $dbPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $dbPdo->exec($sql);
            }
            if (file_exists($seedFile)) {
                $sql = file_get_contents($seedFile);
                $dbPdo->exec($sql);
            }
        } catch (Exception $ex) {
            error_log("Auto DB creation failed: " . $ex->getMessage());
        }
    }
}

/**
 * Shorthand helper to retrieve PDO connection
 */
function getDB(): PDO {
    return Database::getConnection();
}
