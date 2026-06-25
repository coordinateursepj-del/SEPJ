<?php
/**
 * SEPJ Gabès - Installation Check Script
 */

$checks = [];
$allPass = true;

// PHP Version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0', '>=');
$checks[] = ['name' => 'PHP Version', 'status' => $phpOk ? '✅' : '❌', 'detail' => $phpVersion . ($phpOk ? ' (8.0+ OK)' : ' (8.0+ required)')];
if(!$phpOk) $allPass = false;

// PDO MySQL
$pdoOk = extension_loaded('pdo_mysql');
$checks[] = ['name' => 'PDO MySQL', 'status' => $pdoOk ? '✅' : '❌', 'detail' => $pdoOk ? 'Enabled' : 'Required for database'];
if(!$pdoOk) $allPass = false;

// GD or Imagick
$gdOk = extension_loaded('gd');
$checks[] = ['name' => 'GD Image', 'status' => $gdOk ? '✅' : '⚠️', 'detail' => $gdOk ? 'Available' : 'Optional - for image processing'];

// finfo
$finfoOk = extension_loaded('fileinfo');
$checks[] = ['name' => 'FileInfo', 'status' => $finfoOk ? '✅' : '❌', 'detail' => $finfoOk ? 'Available' : 'Required for upload validation'];
if(!$finfoOk) $allPass = false;

// mbstring
$mbOk = extension_loaded('mbstring');
$checks[] = ['name' => 'MBString', 'status' => $mbOk ? '✅' : '❌', 'detail' => $mbOk ? 'Available' : 'Required for Arabic text'];
if(!$mbOk) $allPass = false;

// Uploads writable
$uploadsPath = dirname(__DIR__) . '/public/uploads';
$uploadsOk = is_dir($uploadsPath) && is_writable($uploadsPath);
$checks[] = ['name' => 'Uploads Directory', 'status' => $uploadsOk ? '✅' : '❌', 'detail' => $uploadsOk ? 'Writable' : 'Not writable - check permissions'];
if(!$uploadsOk) $allPass = false;

// Database connection
try {
    require_once dirname(__DIR__) . '/app/config/database.php';
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $testDb = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbOk = true;
    $checks[] = ['name' => 'Database Server', 'status' => '✅', 'detail' => 'Connected to MySQL at ' . DB_HOST];
    
    // Check if database exists
    $stmt = $testDb->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $dbExists = $stmt->fetch();
    if ($dbExists) {
        $testDb->exec("USE " . DB_NAME);
        $checks[] = ['name' => 'Database ' . DB_NAME, 'status' => '✅', 'detail' => 'Database exists'];
        
        // Check tables
        $tables = ['users', 'content_items', 'media', 'site_settings', 'navigation_items', 'contact_messages', 'audit_logs'];
        $existingTables = [];
        $stmt = $testDb->query("SHOW TABLES");
        while($row = $stmt->fetch(PDO::FETCH_NUM)) $existingTables[] = $row[0];
        
        foreach($tables as $table) {
            $hasTable = in_array($table, $existingTables);
            $checks[] = ['name' => 'Table: ' . $table, 'status' => $hasTable ? '✅' : '❌', 'detail' => $hasTable ? 'Exists' : 'Missing - import schema.sql'];
            if(!$hasTable) $allPass = false;
        }
    } else {
        $checks[] = ['name' => 'Database ' . DB_NAME, 'status' => '❌', 'detail' => 'Not found - create it in phpMyAdmin'];
        $allPass = false;
    }
} catch (Exception $e) {
    $dbOk = false;
    $checks[] = ['name' => 'Database', 'status' => '❌', 'detail' => $e->getMessage()];
    $allPass = false;
}

$totalChecks = count($checks);
$passed = count(array_filter($checks, fn($c) => $c['status'] === '✅'));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Check - SEPJ Gabès</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{background:linear-gradient(135deg,#0a0f0d,#064e3b);color:#f0fdf4;font-family:system-ui,sans-serif;min-height:100vh}.card{background:rgba(255,255,255,.05);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:2rem;max-width:720px;margin:2rem auto}</style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="card w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold">🌿 SEPJ Gabès</h1>
            <p class="text-emerald-300 mt-2">Installation Environment Check</p>
        </div>
        
        <div class="space-y-3">
            <?php foreach($checks as $c): ?>
            <div class="flex items-center justify-between p-3 rounded-lg <?= $c['status'] === '✅' ? 'bg-emerald-500/10' : 'bg-red-500/10' ?>">
                <div>
                    <span class="font-medium"><?= $c['name'] ?></span>
                    <span class="text-sm opacity-70 ml-2"><?= $c['detail'] ?></span>
                </div>
                <span class="text-lg"><?= $c['status'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 p-4 rounded-lg text-center text-sm <?= $allPass ? 'bg-emerald-500/20 text-emerald-300' : 'bg-yellow-500/20 text-yellow-300' ?>">
            <?php if($allPass): ?>
            ✅ All checks passed! (<?= $passed ?>/<?= $totalChecks ?>) - The system is ready to use.
            <?php else: ?>
            ⚠️ <?= $passed ?>/<?= $totalChecks ?> checks passed. Please fix the issues above before using the system.
            <?php endif; ?>
        </div>
        
        <div class="mt-6 text-center text-sm text-emerald-300/50">
            <a href="../public/" class="text-emerald-400 hover:text-emerald-300">← Public Site</a>
            <span class="mx-2">|</span>
            <a href="../admin/" class="text-emerald-400 hover:text-emerald-300">Admin Panel →</a>
        </div>
    </div>
</body>
</html>