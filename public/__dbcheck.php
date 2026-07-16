<?php
/**
 * TEMPORARY diagnostic — prints the exact DB connection error.
 * Delete this file after use. Does NOT print the password.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);

require __DIR__ . '/../app/config/database.php';

echo "PHP version : " . PHP_VERSION . "\n";
echo "pdo_mysql   : " . (extension_loaded('pdo_mysql') ? 'loaded' : 'MISSING') . "\n";
echo "host        : " . DB_HOST . "\n";
echo "port        : " . DB_PORT . "\n";
echo "database    : " . DB_NAME . "\n";
echo "user        : " . DB_USER . "\n";
echo "----------------------------------------\n";

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $tables = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    echo "RESULT      : CONNECT OK\n";
    echo "tables in DB: " . $tables . "\n";
} catch (Throwable $e) {
    echo "RESULT      : CONNECT FAILED\n";
    echo "error class : " . get_class($e) . "\n";
    echo "error code  : " . $e->getCode() . "\n";
    echo "message     : " . $e->getMessage() . "\n";
}
