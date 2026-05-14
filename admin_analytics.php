<?php
// admin_analytics.php - Enhanced analytics data for dashboard
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();
    
    if ($conn->connect_error) {
        throw new Exception('DB Connection: ' . $conn->connect_error);
    }

    // Get time period from request (daily, weekly, monthly)
    $period = isset($_GET['period']) ? $_GET['period'] : 'daily';
    
    // Initialize response data
    $registered = 0; 
    $current = 0; 
    $total = 0; 
    $purposes = [];
    $labs = [];
    $sitinsOverTime = [];

    // Total registered students
    $result = $conn->query("SELECT COUNT(*) AS cnt FROM `users` WHERE role = 'student'");
    if ($result) {
        $row = $result->fetch_assoc();
        $registered = intval($row['cnt']);
    }

    // Check if sit_in_sessions table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'sit_in_sessions'");
    
    if ($tableExists && $tableExists->num_rows > 0) {
        // Current active sit-ins
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM `sit_in_sessions` WHERE status='active'");
        if ($result) {
            $row = $result->fetch_assoc();
            $current = intval($row['cnt']);
        }

        // Total sit-ins
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM `sit_in_sessions`");
        if ($result) {
            $row = $result->fetch_assoc();
            $total = intval($row['cnt']);
        }

        // Sit-ins by purpose
        $result = $conn->query("SELECT purpose, COUNT(*) AS cnt FROM `sit_in_sessions` GROUP BY purpose");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['purpose']) {
                    $purposes[$row['purpose']] = intval($row['cnt']);
                }
            }
        }

        // Sit-ins by lab
        $result = $conn->query("SELECT COALESCE(l.lab_name, 'Unknown') AS lab_name, COUNT(*) AS cnt 
                              FROM `sit_in_sessions` s
                              LEFT JOIN labs l ON l.id = s.lab_id
                              GROUP BY l.lab_name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['lab_name']) {
                    $labs[$row['lab_name']] = intval($row['cnt']);
                }
            }
        }

        // Sit-ins over time (based on period)
        if ($period === 'daily') {
            // Last 7 days
            $query = "SELECT DATE(time_in) as date, COUNT(*) as cnt 
                     FROM `sit_in_sessions` 
                     WHERE time_in >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY DATE(time_in)
                     ORDER BY date ASC";
        } elseif ($period === 'weekly') {
            // Last 12 weeks
            $query = "SELECT YEARWEEK(time_in, 1) as week, COUNT(*) as cnt 
                     FROM `sit_in_sessions` 
                     WHERE time_in >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
                     GROUP BY YEARWEEK(time_in, 1)
                     ORDER BY week ASC";
        } else { 
            // monthly - Last 12 months
            $query = "SELECT DATE_FORMAT(time_in, '%Y-%m') as month, COUNT(*) as cnt 
                     FROM `sit_in_sessions` 
                     WHERE time_in >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(time_in, '%Y-%m')
                     ORDER BY month ASC";
        }

        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($period === 'daily') {
                    $sitinsOverTime[] = [
                        'label' => $row['date'],
                        'value' => intval($row['cnt'])
                    ];
                } elseif ($period === 'weekly') {
                    $sitinsOverTime[] = [
                        'label' => 'Week ' . $row['week'],
                        'value' => intval($row['cnt'])
                    ];
                } else {
                    $sitinsOverTime[] = [
                        'label' => $row['month'],
                        'value' => intval($row['cnt'])
                    ];
                }
            }
        }
    }

    $conn->close();
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'registered' => $registered,
        'current' => $current,
        'total' => $total,
        'purposes' => $purposes,
        'labs' => $labs,
        'sitinsOverTime' => $sitinsOverTime,
        'period' => $period
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}
?>
