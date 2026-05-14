<?php
// create_software_table.php - Direct table creation without auth check
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
        echo json_encode([
            'success' => true,
            'message' => 'Software table created successfully!',
            'schema' => [
                'fields' => [
                    'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
                    'software_name' => 'VARCHAR(255) - Software/File name',
                    'category' => 'VARCHAR(100) - Type of software (IDE, Programming, Database, Tools, etc.)',
                    'file_path' => 'VARCHAR(500) - Stored file path',
                    'lab_id' => 'INT - Laboratory ID (links to labs table)',
                    'uploaded_by' => 'INT - User ID who uploaded (links to users table)',
                    'uploaded_at' => 'TIMESTAMP - Upload date/time'
                ]
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating table: ' . $conn->error
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
