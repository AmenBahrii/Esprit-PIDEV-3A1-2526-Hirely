<?php
/**
 * Script to find and delete orphaned records in the hirely database
 * Orphaned records are those with user_id values that don't exist in the users table
 */

// Database connection parameters
$db_config = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'password' => '',
    'database' => 'hirely'
];

try {
    // Connect to MySQL
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4",
        $db_config['user'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "? Connected to MySQL database: {$db_config['database']}\n\n";
    
    // Get all tables in the database
    $tables_stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$db_config['database']}' AND TABLE_TYPE = 'BASE TABLE'");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables in database.\n";
    echo "Checking for user_id foreign keys...\n\n";
    
    // Array to store deleted records
    $deleted_records = [];
    $total_deleted = 0;
    
    // Check each table for user_id column
    foreach ($tables as $table) {
        // Get columns from the table
        $columns_stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$db_config['database']}' AND TABLE_NAME = '$table'");
        $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if table has user_id column
        if (!in_array('user_id', $columns)) {
            continue;
        }
        
        echo "Table: $table\n";
        
        // Find orphaned records (user_id values not in users table)
        $orphaned_query = "SELECT * FROM $table WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)";
        $orphaned_stmt = $pdo->query($orphaned_query);
        $orphaned_records = $orphaned_stmt->fetchAll();
        
        if (count($orphaned_records) > 0) {
            echo "  Found " . count($orphaned_records) . " orphaned records\n";
            
            // Display which user_ids are orphaned
            $orphaned_user_ids = array_unique(array_column($orphaned_records, 'user_id'));
            echo "  Orphaned user_ids: " . implode(", ", $orphaned_user_ids) . "\n";
            
            // Get primary key name (usually 'id')
            $pk_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$db_config['database']}' AND TABLE_NAME = '$table' AND COLUMN_KEY = 'PRI' LIMIT 1";
            $pk_stmt = $pdo->query($pk_query);
            $pk_result = $pk_stmt->fetch();
            $primary_key = $pk_result ? $pk_result['COLUMN_NAME'] : 'id';
            
            // Collect IDs to delete
            $ids_to_delete = array_column($orphaned_records, $primary_key);
            
            // Delete orphaned records
            $ids_str = implode(",", $ids_to_delete);
            $delete_query = "DELETE FROM $table WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)";
            
            $pdo->exec($delete_query);
            
            $deleted_count = count($ids_to_delete);
            $deleted_records[$table] = [
                'count' => $deleted_count,
                'ids' => $ids_to_delete,
                'orphaned_user_ids' => $orphaned_user_ids
            ];
            
            $total_deleted += $deleted_count;
            echo "  ? Deleted $deleted_count records\n";
        } else {
            echo "  No orphaned records found\n";
        }
        
        echo "\n";
    }
    
    echo "=====================================\n";
    echo "SUMMARY OF DELETED RECORDS\n";
    echo "=====================================\n";
    echo "Total records deleted: $total_deleted\n\n";
    
    if (count($deleted_records) > 0) {
        foreach ($deleted_records as $table => $data) {
            echo "$table: {$data['count']} records deleted\n";
            echo "  - Record IDs: " . implode(", ", $data['ids']) . "\n";
            echo "  - Orphaned user_ids: " . implode(", ", $data['orphaned_user_ids']) . "\n";
        }
    } else {
        echo "No orphaned records were found or deleted.\n";
    }
    
    echo "\n=====================================\n";
    echo "Now attempting schema validation...\n";
    echo "=====================================\n\n";
    
    $pdo = null;
    
} catch (PDOException $e) {
    echo "? Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "? Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
