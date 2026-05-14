<?php
// software_list_fetch.php - Fetch available software across labs
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Check session before proceeding
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Suppress all display errors, only log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Include config
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    
    require_once 'config.php';
    
    // Create connection directly
    $conn = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');

    // Check if software table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'software'");
    
    if (!$tableCheck) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist, return empty list
        ob_end_clean();
        echo json_encode(['success' => true, 'software' => []]);
        $conn->close();
        exit();
    }

    // Simple query for software - just basic info
    $query = "SELECT 
                  MIN(s.id) as id,
                  s.software_name, 
                  s.category,
                  GROUP_CONCAT(DISTINCT l.lab_name ORDER BY l.lab_name SEPARATOR ', ') as labs
              FROM software s
              LEFT JOIN labs l ON l.id = s.lab_id
              GROUP BY s.software_name, s.category
              ORDER BY s.software_name ASC";
    
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $software = [];
    while ($row = $result->fetch_assoc()) {
        $labs = !empty($row['labs']) ? $row['labs'] : 'All Labs';
        
        $software[] = [
            'id' => intval($row['id']),
            'name' => $row['software_name'],
            'description' => $row['category'] ?? 'Software',
            'labs' => $labs
        ];
    }

    $conn->close();
    
    ob_end_clean();
    echo json_encode(['success' => true, 'software' => $software]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('Software List Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to load software list']);
}
?>

