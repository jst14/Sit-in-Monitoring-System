<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // Check if labs table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'labs'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        throw new Exception('Labs table not found');
    }

    // Fetch all labs
    $query = "SELECT id, lab_name FROM labs ORDER BY lab_name ASC";
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception('Query error: ' . $mysqli->error);
    }

    $labs = [];
    while ($row = $result->fetch_assoc()) {
        $lab_id = intval($row['id']);
        
        // Count active sessions in this lab
        $sit_query = "SELECT COUNT(*) as active_count FROM sit_in_sessions WHERE lab_id = ? AND status = 'active'";
        $sit_stmt = $mysqli->prepare($sit_query);
        
        if (!$sit_stmt) {
            throw new Exception('Prepare error: ' . $mysqli->error);
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

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'labs' => $labs
    ]);
    
} catch (Exception $e) {
    error_log('Labs Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load lab status',
        'error' => $e->getMessage()
    ]);
}
?>
