<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Allow only admin or direct access for setup
if (!isset($_SESSION['user_id']) && !isset($_GET['setup_key'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // Clear existing software
    $mysqli->query("DELETE FROM software");

    // Software catalog with lab assignments
    $softwareList = [
        [
            'name' => 'MS Office 365',
            'description' => 'Word, Excel, PowerPoint',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ],
        [
            'name' => 'Visual Studio Code',
            'description' => 'Code editor & debugger',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ],
        [
            'name' => 'XAMPP',
            'description' => 'Apache + MySQL + PHP',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ],
        [
            'name' => 'MySQL Workbench',
            'description' => 'Database management tool',
            'labs' => [3, 8] // Lab 524, 530
        ],
        [
            'name' => 'NetBeans IDE',
            'description' => 'Java development',
            'labs' => [3, 4] // Lab 524, 526
        ],
        [
            'name' => 'IntelliJ IDEA',
            'description' => 'JetBrains Java IDE',
            'labs' => [4, 5] // Lab 526, 528
        ],
        [
            'name' => 'Git',
            'description' => 'Version control',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ],
        [
            'name' => 'Cisco Packet Tracer',
            'description' => 'Network simulation',
            'labs' => [5] // Lab 528
        ],
        [
            'name' => 'Oracle Virtual Box',
            'description' => 'Simulator Virtualization Tool',
            'labs' => [7, 3] // Lab 542, 524
        ],
        [
            'name' => 'VMware Workstation',
            'description' => 'Simulator Host Machine',
            'labs' => [3, 7] // Lab 524, 542
        ],
        [
            'name' => 'Notepad++',
            'description' => 'Code editor & debugger',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ],
        [
            'name' => 'Python 3.x',
            'description' => 'Python interpreter & pip',
            'labs' => [3, 4, 5, 6, 7, 8] // All labs
        ]
    ];

    $insertCount = 0;
    $errorCount = 0;

    // Insert software for each lab
    foreach ($softwareList as $software) {
        foreach ($software['labs'] as $lab_id) {
            $stmt = $mysqli->prepare(
                "INSERT INTO software (software_name, category, file_path, lab_id, uploaded_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            
            if (!$stmt) {
                throw new Exception('Prepare error: ' . $mysqli->error);
            }

            $file_path = '/software/' . strtolower(str_replace(' ', '_', $software['name'])) . '.exe';
            
            $stmt->bind_param(
                'sssi',
                $software['name'],
                $software['description'],
                $file_path,
                $lab_id
            );

            if ($stmt->execute()) {
                $insertCount++;
            } else {
                $errorCount++;
                error_log('Insert error for ' . $software['name'] . ': ' . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Get all software with lab info
    $query = "SELECT s.id, s.software_name, s.category, GROUP_CONCAT(l.lab_name SEPARATOR ', ') as available_labs
              FROM software s
              LEFT JOIN labs l ON l.id = s.lab_id
              GROUP BY s.software_name
              ORDER BY s.software_name ASC";
    
    $result = $mysqli->query($query);
    $softwareData = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $softwareData[] = $row;
        }
    }

    $mysqli->close();

    echo json_encode([
        'success' => true,
        'message' => "Software database populated successfully",
        'inserted' => $insertCount,
        'errors' => $errorCount,
        'software_list' => $softwareData
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Software Setup Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to populate software database',
        'error' => $e->getMessage()
    ]);
}
?>
