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
            $url = str_replace(['DATABASE_URL="', '"'], '', $line);
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
    
    // Fix bad dates in application table
    $pdo->exec("UPDATE application SET applicationDate = '2025-01-01' WHERE applicationDate = '0000-00-00' OR applicationDate IS NULL OR applicationDate = ''");
    echo "[OK] Fixed applicationDate values\n";
    
    $pdo->exec("UPDATE application SET lastUpdateDate = NOW() WHERE lastUpdateDate = '0000-00-00' OR lastUpdateDate IS NULL");
    echo "[OK] Fixed lastUpdateDate values\n";
    
    $pdo->exec("UPDATE application SET availabilityDate = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE availabilityDate = '0000-00-00' OR availabilityDate IS NULL");
    echo "[OK] Fixed availabilityDate values\n";
    
    // Fix bad dates in other tables
    $pdo->exec("UPDATE interviews SET scheduled_date = NOW() WHERE scheduled_date IS NULL OR scheduled_date = '0000-00-00'");
    echo "[OK] Fixed interviews.scheduled_date\n";
    
    $pdo->exec("UPDATE interview_evaluations SET evaluated_at = NOW() WHERE evaluated_at IS NULL OR evaluated_at = '0000-00-00'");
    echo "[OK] Fixed interview_evaluations.evaluated_at\n";
    
    $pdo->exec("UPDATE onboardingplan SET start_date = NOW() WHERE start_date IS NULL OR start_date = '0000-00-00'");
    echo "[OK] Fixed onboardingplan.start_date\n";
    
    echo "[SUCCESS] Data cleanup complete\n";
    
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
?>
