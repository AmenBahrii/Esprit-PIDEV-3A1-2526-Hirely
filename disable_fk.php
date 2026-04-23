<?php
$envFile = '.env.local';
if (!file_exists($envFile)) {
    $envFile = '.env';
}

$env = file_get_contents($envFile);
preg_match('/DATABASE_URL=mysql:\/\/([^:]+):([^@]+)@([^:\/]+):(\d+)\/(.+)/', $env, $matches);

if (count($matches) < 6) {
    echo "Could not parse DATABASE_URL from .env file\n";
    exit(1);
}

$user = $matches[1];
$pass = $matches[2];
$host = $matches[3];
$port = $matches[4];
$db = $matches[5];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    echo "Foreign key checks disabled\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
