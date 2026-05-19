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
        // Table doesn't exist, return default labs with disabled date check
        $today = date('Y-m-d');
        $defaultLabs = [
            ['id' => 3, 'name' => 'Lab 524'],
            ['id' => 4, 'name' => 'Lab 526'],
            ['id' => 5, 'name' => 'Lab 528'],
            ['id' => 6, 'name' => 'Lab 530'],
        ];
        
        $labs = [];
        foreach ($defaultLabs as $defaultLab) {
            // Count unavailable computers for this lab
            $unavail_query = "SELECT COUNT(*) as unavail_count FROM unavailable_computers WHERE lab_id = ?";
            $unavail_stmt = $conn->prepare($unavail_query);
            $unavailable_count = 0;
            
            if ($unavail_stmt) {
                $lab_id = $defaultLab['id'];
                $unavail_stmt->bind_param('i', $lab_id);
                $unavail_stmt->execute();
                $unavail_result = $unavail_stmt->get_result();
                $unavail_data = $unavail_result->fetch_assoc();
                $unavailable_count = intval($unavail_data['unavail_count'] ?? 0);
                $unavail_stmt->close();
            }
            
            // Check if lab is disabled today
            $disable_query = "SELECT id FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ?";
            $disable_stmt = $conn->prepare($disable_query);
            
            if ($disable_stmt) {
                $lab_id = $defaultLab['id'];
                $disable_stmt->bind_param('is', $lab_id, $today);
                $disable_stmt->execute();
                $disable_result = $disable_stmt->get_result();
                $is_open = $disable_result->num_rows > 0 ? 0 : 1;
                $disable_stmt->close();
            } else {
                $is_open = 1;
            }
            
            $labs[] = [
                'id' => $defaultLab['id'],
                'name' => $defaultLab['name'],
                'is_open' => $is_open,
                'active_students' => 0,
                'max_capacity' => 40,
                'availability' => max(0, 40 - $unavailable_count),
                'unavailable_count' => $unavailable_count
            ];
        }
        
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

        // Count unavailable computers for this lab
        $unavail_query = "SELECT COUNT(*) as unavail_count FROM unavailable_computers WHERE lab_id = ?";
        $unavail_stmt = $conn->prepare($unavail_query);
        
        if (!$unavail_stmt) {
            throw new Exception('Prepare error: ' . $conn->error);
        }
        
        $unavail_stmt->bind_param('i', $lab_id);
        $unavail_stmt->execute();
        $unavail_result = $unavail_stmt->get_result();
        $unavail_data = $unavail_result->fetch_assoc();
        $unavailable_count = intval($unavail_data['unavail_count'] ?? 0);
        $unavail_stmt->close();

        // Check if lab is disabled today
        $today = date('Y-m-d');
        $disable_query = "SELECT id FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ?";
        $disable_stmt = $conn->prepare($disable_query);
        
        if (!$disable_stmt) {
            throw new Exception('Prepare error: ' . $conn->error);
        }
        
        $disable_stmt->bind_param('is', $lab_id, $today);
        $disable_stmt->execute();
        $disable_result = $disable_stmt->get_result();
        $is_disabled_today = $disable_result->num_rows > 0 ? 1 : 0;
        $disable_stmt->close();

        // Default capacity and availability (subtract both active students and unavailable computers)
        $max_capacity = 40;
        $availability = max(0, $max_capacity - $active_students - $unavailable_count);
        
        // Lab is open only if it's not disabled today
        $is_open = $is_disabled_today ? 0 : 1;

        $labs[] = [
            'id' => $lab_id,
            'name' => $row['lab_name'],
            'is_open' => $is_open,
            'active_students' => $active_students,
            'max_capacity' => $max_capacity,
            'availability' => $availability,
            'unavailable_count' => $unavailable_count
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

