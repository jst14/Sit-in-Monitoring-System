<?php
// labs_status_fetch.php - Fetch laboratory status and active students count
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

    // Check if labs table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'labs'");
    
    if (!$tableCheck) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist, return default labs
        $labs = [
            ['id' => 3, 'name' => 'Lab 524', 'is_open' => true, 'active_students' => 0, 'max_capacity' => 40, 'availability' => 40],
            ['id' => 4, 'name' => 'Lab 526', 'is_open' => true, 'active_students' => 0, 'max_capacity' => 40, 'availability' => 40],
            ['id' => 5, 'name' => 'Lab 528', 'is_open' => true, 'active_students' => 0, 'max_capacity' => 40, 'availability' => 40],
            ['id' => 6, 'name' => 'Lab 530', 'is_open' => true, 'active_students' => 0, 'max_capacity' => 40, 'availability' => 40],
        ];
        ob_end_clean();
        echo json_encode(['success' => true, 'labs' => $labs]);
        $conn->close();
        exit();
    }

    // Query labs with active session count
    $query = "SELECT l.id, l.lab_name FROM labs l ORDER BY l.lab_name ASC";
    
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $labs = [];
    while ($row = $result->fetch_assoc()) {
        $lab_id = intval($row['id']);
        
        // Count active sessions in this lab
        $sit_query = "SELECT COUNT(*) as active_count FROM sit_in_sessions WHERE lab_id = ? AND status = 'active'";
        $sit_stmt = $conn->prepare($sit_query);
        
        if (!$sit_stmt) {
            throw new Exception('Prepare error: ' . $conn->error);
        }
        
        $sit_stmt->bind_param('i', $lab_id);
        $sit_stmt->execute();
        $sit_result = $sit_stmt->get_result();
        $sit_data = $sit_result->fetch_assoc();
        $active_students = intval($sit_data['active_count'] ?? 0);
        $sit_stmt->close();

        // Default capacity and availability
        $max_capacity = 40;
        $availability = max(0, $max_capacity - $active_students);

        $labs[] = [
            'id' => $lab_id,
            'name' => $row['lab_name'],
            'is_open' => 1,
            'active_students' => $active_students,
            'max_capacity' => $max_capacity,
            'availability' => $availability
        ];
    }

    $conn->close();
    
    ob_end_clean();
    echo json_encode(['success' => true, 'labs' => $labs]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('Labs Status Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to load laboratory status']);
}
?>

