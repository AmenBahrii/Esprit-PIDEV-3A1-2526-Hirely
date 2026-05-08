<?php
$conn = mysqli_connect('127.0.0.1', 'root', '');
if (!$conn) {
    echo 'Connection Error: ' . mysqli_connect_error();
    exit;
}

if (!mysqli_query($conn, 'CREATE DATABASE IF NOT EXISTS hirely_interview_app')) {
    echo mysqli_error($conn);
} else {
    echo 'Database created successfully!';
}
mysqli_close($conn);
?>