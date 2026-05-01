<?php
require "vendor/autoload.php";
$dbUrl = "mysql://root:@127.0.0.1:3306/hirely?serverVersion=10.4.32-MariaDB";
$dsn = parse_url($dbUrl);
$conn = new PDO("mysql:host=".$dsn["host"].";dbname=".$dsn["path"].";charset=utf8mb4", $dsn["user"], $dsn["pass"]);

// Check job offers
echo "=== JOB OFFERS ===\n";
$stmt = $conn->query("SELECT jobOfferId, title, user_id FROM joboffer LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo json_encode($row) . "\n";
}

// Check applications
echo "\n=== APPLICATIONS ===\n";
$stmt = $conn->query("SELECT applicationId, email, jobOfferId, currentStatus FROM application LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo json_encode($row) . "\n";
}

// Check users and roles
echo "\n=== USERS WITH RECRUITER ROLE ===\n";
$stmt = $conn->query("SELECT u.user_id, u.firstName, u.lastName, r.name FROM users u LEFT JOIN role r ON u.role_id = r.role_id WHERE r.name = 'recruiter' LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo json_encode($row) . "\n";
}
