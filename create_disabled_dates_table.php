<?php
// create_disabled_dates_table.php
// Run this once to create the reservation_disabled_dates table
// DELETE this file after running!

require_once 'config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reservation_disabled_dates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lab_id INT NOT NULL,
            disabled_date DATE NOT NULL,
            reason VARCHAR(255) DEFAULT 'No classes',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_lab_date (lab_id, disabled_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo '<b style="color:green">✓ Table reservation_disabled_dates created successfully!</b><br>';
    echo '<b style="color:orange">DELETE create_disabled_dates_table.php after running!</b>';

} catch (Exception $e) {
    echo '<b style="color:red">Error creating table: ' . $e->getMessage() . '</b>';
}
?>