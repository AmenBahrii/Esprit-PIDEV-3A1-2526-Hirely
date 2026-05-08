<?php
// Setup database and tables
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS hirely_interview_app');
    echo "✓ Database created\n";
    
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hirely_interview_app', 'root', '');
    
    // User table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'recruiter',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Recruiter Profile table
    $pdo->exec("CREATE TABLE IF NOT EXISTS recruiter_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        department VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    )");
    
    // Application table
    $pdo->exec("CREATE TABLE IF NOT EXISTS application (
        id INT AUTO_INCREMENT PRIMARY KEY,
        candidate_name VARCHAR(255) NOT NULL,
        job_id VARCHAR(100),
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Interview Type table
    $pdo->exec("CREATE TABLE IF NOT EXISTS interview_type (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description LONGTEXT
    )");
    
    // Interview table
    $pdo->exec("CREATE TABLE IF NOT EXISTS interview (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT,
        user_id INT,
        interview_type_id INT,
        schedule_date DATETIME,
        format VARCHAR(50),
        location VARCHAR(255),
        meeting_link VARCHAR(500),
        status VARCHAR(50) DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL,
        FOREIGN KEY (interview_type_id) REFERENCES interview_type(id) ON DELETE SET NULL
    )");
    
    // Evaluation Criteria table
    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluation_criteria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description LONGTEXT,
        weight DECIMAL(5,2) DEFAULT 1.0
    )");
    
    // Interview Evaluation table
    $pdo->exec("CREATE TABLE IF NOT EXISTS interview_evaluation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interview_id INT,
        user_id INT,
        overall_rating INT,
        recommendation VARCHAR(50),
        hire_decision VARCHAR(50),
        strengths LONGTEXT,
        weaknesses LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (interview_id) REFERENCES interview(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
    )");
    
    // Evaluation Score table
    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluation_score (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interview_evaluation_id INT,
        evaluation_criteria_id INT,
        score INT,
        comment LONGTEXT,
        FOREIGN KEY (interview_evaluation_id) REFERENCES interview_evaluation(id) ON DELETE CASCADE,
        FOREIGN KEY (evaluation_criteria_id) REFERENCES evaluation_criteria(id) ON DELETE SET NULL
    )");
    
    // Notification table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message LONGTEXT,
        type VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    )");
    
    echo "✓ All 9 tables created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
