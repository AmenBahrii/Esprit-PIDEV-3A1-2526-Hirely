<?php
// Load sample data
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hirely_interview_app', 'root', '');
    
    // Check if data already exists
    $check = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
    if ($check > 0) {
        echo "✓ Sample data already loaded\n";
        exit(0);
    }
    
    // Insert users
    $pdo->exec("INSERT INTO user (email, password, role) VALUES 
        ('recruiter1@example.com', 'hashed_password_1', 'recruiter'),
        ('recruiter2@example.com', 'hashed_password_2', 'recruiter'),
        ('admin@example.com', 'hashed_password_admin', 'admin')");
    echo "✓ 3 users inserted\n";
    
    // Insert recruiter profiles
    $pdo->exec("INSERT INTO recruiter_profile (user_id, name, department) VALUES 
        (1, 'John Smith', 'Engineering'),
        (2, 'Sarah Johnson', 'Sales')");
    echo "✓ 2 recruiter profiles inserted\n";
    
    // Insert applications
    $pdo->exec("INSERT INTO application (candidate_name, job_id, status) VALUES 
        ('Alice Brown', 'JOB001', 'pending'),
        ('Bob Wilson', 'JOB002', 'in_progress'),
        ('Carol Davis', 'JOB001', 'pending'),
        ('David Miller', 'JOB003', 'pending'),
        ('Emma Taylor', 'JOB002', 'in_progress')");
    echo "✓ 5 applications inserted\n";
    
    // Insert interview types
    $pdo->exec("INSERT INTO interview_type (name, description) VALUES 
        ('Technical Interview', 'Assessment of technical skills'),
        ('HR Interview', 'Discussion of fit and culture'),
        ('Phone Screen', 'Initial phone screening'),
        ('Final Round', 'Final round with leadership')");
    echo "✓ 4 interview types inserted\n";
    
    // Insert interviews
    $pdo->exec("INSERT INTO interview (application_id, user_id, interview_type_id, schedule_date, format, location, status) VALUES 
        (1, 1, 1, '2026-04-15 10:00:00', 'in-person', 'Building A', 'scheduled'),
        (2, 1, 2, '2026-04-16 14:00:00', 'video', 'Zoom', 'scheduled'),
        (3, 2, 1, '2026-04-17 09:00:00', 'in-person', 'Building B', 'scheduled'),
        (4, 2, 3, '2026-04-18 13:00:00', 'phone', 'Phone Call', 'scheduled'),
        (5, 1, 2, '2026-04-19 15:00:00', 'video', 'Teams', 'scheduled')");
    echo "✓ 5 interviews inserted\n";
    
    // Insert evaluation criteria
    $pdo->exec("INSERT INTO evaluation_criteria (name, description, weight) VALUES 
        ('Technical Skills', 'Assessment of technical abilities', 0.30),
        ('Communication', 'Clarity and communication skills', 0.20),
        ('Problem Solving', 'Ability to solve problems', 0.25),
        ('Cultural Fit', 'Alignment with company culture', 0.15),
        ('Experience', 'Relevant experience level', 0.10)");
    echo "✓ 5 evaluation criteria inserted\n";
    
    // Insert interview evaluations
    $pdo->exec("INSERT INTO interview_evaluation (interview_id, user_id, overall_rating, recommendation, hire_decision) VALUES 
        (1, 1, 8, 'move_forward', 'pending'),
        (2, 1, 7, 'move_forward', 'pending')");
    echo "✓ 2 interview evaluations inserted\n";
    
    // Insert evaluation scores
    $pdo->exec("INSERT INTO evaluation_score (interview_evaluation_id, evaluation_criteria_id, score, comment) VALUES 
        (1, 1, 8, 'Strong technical background'),
        (1, 2, 7, 'Good communication skills'),
        (1, 3, 8, 'Excellent problem solving'),
        (2, 1, 7, 'Solid technical knowledge'),
        (2, 2, 8, 'Outstanding communicator')");
    echo "✓ 5 evaluation scores inserted\n";
    
    // Insert notifications
    $pdo->exec("INSERT INTO notification (user_id, message, type, is_read) VALUES 
        (1, 'New interview scheduled for tomorrow', 'interview', 0),
        (1, 'Evaluation submitted successfully', 'evaluation', 1),
        (2, 'You have a pending evaluation', 'evaluation', 0)");
    echo "✓ 3 notifications inserted\n";
    
    echo "\n✓ All sample data loaded successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
