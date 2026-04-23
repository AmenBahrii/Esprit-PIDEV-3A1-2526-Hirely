<?php
$file = 'migrations/Version20260423210841.php';
$content = file_get_contents($file);
// Fix duplicate IF EXISTS in FOREIGN KEY statements
$content = preg_replace('/DROP FOREIGN KEY IF EXISTS IF EXISTS/i', 'DROP FOREIGN KEY IF EXISTS', $content);
// Fix DROP INDEX statements - ensure IF EXISTS is positioned correctly
$content = preg_replace('/DROP INDEX IF EXISTS IF EXISTS/i', 'DROP INDEX IF EXISTS', $content);
file_put_contents($file, $content);
echo 'Migration file corrected';
