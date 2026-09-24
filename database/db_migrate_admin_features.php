<?php
require_once __DIR__ . '/../api/db.php';

try {
    $pdo->exec('ALTER TABLE users ADD COLUMN dob DATE NULL AFTER email;');
    echo "Successfully added dob column to users table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column dob already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
