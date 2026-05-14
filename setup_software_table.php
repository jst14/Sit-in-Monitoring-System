<?php
// setup_software_table.php - Create software table if it doesn't exist
session_start();

// Check if admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.html");
    exit();
}

require_once 'config.php';
$conn = db_connect();

try {
    // Create software table
    $sql = "
    CREATE TABLE IF NOT EXISTS software (
        id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        software_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        lab_id INT(10) UNSIGNED,
        uploaded_by INT(10) UNSIGNED,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_lab_id (lab_id),
        INDEX idx_category (category)
    )
    ";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['setup_message'] = 'Software table created successfully!';
    } else {
        $_SESSION['setup_error'] = 'Error creating table: ' . $conn->error;
    }

} catch (Exception $e) {
    $_SESSION['setup_error'] = 'Error: ' . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: admin_dashboard.php");
exit();
?>
