<?php
// Disable FK checks, update schema, re-enable FK checks
$dotenv = file_get_contents('.env.local') ?: file_get_contents('.env');
preg_match('/DATABASE_URL="?([^"]+)"?/', $dotenv, $matches);
$dbUrl = $matches[1] ?? getenv('DATABASE_URL');

// Parse database URL
$parsed = parse_url($dbUrl);
$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? 3306;
$user = $parsed['user'] ?? 'root';
$pass = $parsed['pass'] ?? '';
$db = ltrim($parsed['path'] ?? '', '/');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    echo "[OK] Foreign key checks disabled\n";
    
    // Run schema update
    system('php bin/console doctrine:schema:update --force --no-interaction 2>&1');
    
    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "[OK] Foreign key checks re-enabled\n";
    
    // Validate schema
    system('php bin/console doctrine:schema:validate');
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
