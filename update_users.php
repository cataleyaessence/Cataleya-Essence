<?php
require_once __DIR__ . '/config/database.php';

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/sql/update_users_table.sql';
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
                // Ignore duplicate column errors
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    echo "Error executing statement: " . $e->getMessage() . "\n";
                    echo "Statement: " . $statement . "\n\n";
                }
            }
        }
    }
    
    echo "Users table updated successfully!\n";
    echo "Added columns: phone, address_line1, address_line2, city, province, postal_code\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
