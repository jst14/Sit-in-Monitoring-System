<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    if ($mysqli->connect_error) {
        throw new Exception('DB connection failed');
    }

    // Check for users
    $result = $mysqli->query("SELECT id_number, first_name, last_name FROM users LIMIT 5");
    
    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'user_count' => count($users),
        'sample_users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
