<?php
require 'vendor/autoload.php';

// Load environment
$dotenv = @file_get_contents('.env.local') ?: @file_get_contents('.env');
$user = 'root';
$pass = '';
$host = '127.0.0.1';
$port = 3306;
$db = 'hirely';

if ($dotenv) {
    $lines = explode("\n", $dotenv);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
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
    
    echo "[*] Disabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "[*] Running schema update...\n";
    passthru("php bin/console doctrine:schema:update --force --no-interaction 2>&1");
    
    echo "\n[*] Re-enabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "[*] Validating schema...\n";
    passthru("php bin/console doctrine:schema:validate 2>&1");
    
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
?>
