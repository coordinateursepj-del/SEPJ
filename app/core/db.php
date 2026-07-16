<?php
/**
 * Database Connection - PDO Singleton
 * SEPJ Gabès
 */

require_once ROOT_PATH . '/app/config/database.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log error, show user-friendly message
error_log("Database connection failed [host=" . DB_HOST . ", port=" . DB_PORT . ", db=" . DB_NAME . "]: " . get_class($e) . " (" . $e->getCode() . ") " . $e->getMessage());
                die("اتصال قاعدة البيانات فشل. الرجاء المحاولة لاحقاً.<br>Database connection failed. Please try again later.");
            }
        }

        return self::$instance;
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get DB connection quickly
 */
function db(): PDO
{
    return Database::getInstance();
}