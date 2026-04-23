<?php
$file = 'migrations/Version20260423210841.php';
$content = file_get_contents($file);
$content = preg_replace('/DROP FOREIGN KEY ([^;]+);/i', 'DROP FOREIGN KEY IF EXISTS $1;', $content);
$content = preg_replace('/DROP INDEX ([^;]+);/i', 'DROP INDEX IF EXISTS $1;', $content);
file_put_contents($file, $content);
echo 'Migration file updated with IF EXISTS clauses';
