<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // Check if software table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'software'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        throw new Exception('Software table not found');
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
    
    $result = $mysqli->query($query);

    if (!$result) {
        throw new Exception('Query error: ' . $mysqli->error);
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

    http_response_code(200);
    echo json_encode(['success' => true, 'software' => $software]);

} catch (Exception $e) {
    error_log('Software List Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load software list',
        'error' => $e->getMessage()
    ]);
}
?>
