<?php
require 'vendor/autoload.php';

$dotenv = @file_get_contents('.env.local') ?: @file_get_contents('.env');
$user = 'root';
$pass = '';
$host = '127.0.0.1';
$port = 3306;
$db = 'hirely';

// Parse from DATABASE_URL env var - look for the UNCOMMENTED line
if ($dotenv) {
    $lines = explode("\n", $dotenv);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue; // skip comments
        if (strpos($line, 'DATABASE_URL=') === 0) {
            $url = str_replace('DATABASE_URL="', '', str_replace('"', '', $line));
            if (preg_match('/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/', $url, $m)) {
                list(,$user,$pass,$host,$port,$db) = $m;
            }
            break;
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get valid user IDs
    $validUsers = $pdo->query("SELECT user_id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $validUserIds = implode(',', $validUsers);
    
    echo "[INFO] Found " . count($validUsers) . " valid users\n";
    
    // Delete orphaned records in application table
    if ($validUserIds) {
        $result = $pdo->exec("DELETE FROM application WHERE user_id NOT IN ($validUserIds)");
        if ($result > 0) echo "[OK] Deleted $result orphaned application records\n";
    }
    
    // Delete orphaned records in interviews table
    if ($validUserIds) {
        $result = $pdo->exec("DELETE FROM interviews WHERE recruiter_id IS NOT NULL AND recruiter_id NOT IN ($validUserIds)");
        if ($result > 0) echo "[OK] Deleted $result orphaned interview records\n";
    }
    
    // Delete orphaned records in interview_evaluations table
    if ($validUserIds) {
        $result = $pdo->exec("DELETE FROM interview_evaluations WHERE recruiter_id IS NOT NULL AND recruiter_id NOT IN ($validUserIds)");
        if ($result > 0) echo "[OK] Deleted $result orphaned evaluation records\n";
    }
    
    echo "[SUCCESS] Cleanup complete\n";
    
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
?>
