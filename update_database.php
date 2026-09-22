<?php
require_once __DIR__ . '/config/database.php';

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/sql/cataleya_db.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("Error: Could not read SQL file");
    }
    
    // Split the SQL into individual statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore duplicate table errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "Error executing statement: " . $e->getMessage() . "\n";
                    echo "Statement: " . $statement . "\n\n";
                }
            }
        }
    }
    
    echo "Database update completed successfully!\n";
    echo "New tables created: time_slots, daily_slot_availability\n";
    echo "Default time slots inserted.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
