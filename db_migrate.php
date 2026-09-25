<?php
require 'api/db.php';
try {
    $pdo->exec('ALTER TABLE period_records ADD COLUMN estimated_ovulation_date DATE NULL AFTER end_date;');
    echo 'Done';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
