<?php
// admin_sitin_fetch.php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    echo json_encode([]);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();

    // Optional filter: ?filter=active  or  ?filter=all  (default all)
    $filter = $_GET['filter'] ?? 'all';

    if ($filter === 'active') {
        $stmt = $conn->prepare(
            "SELECT s.id AS sit_id,
                    u.id_number,
                    CONCAT(u.first_name,' ',u.last_name) AS name,
                    s.purpose,
                    COALESCE(l.lab_name, 'Unknown') AS lab,
                    u.sessions_left AS session,
                    CASE WHEN s.status = 'active' THEN 'Active'
                         WHEN s.status = 'completed' THEN 'Done'
                         ELSE 'Cancelled' END AS status,
                    s.time_in AS login,
                    s.time_out AS logout,
                    DATE(s.time_in) AS date,
                    s.computer_number
             FROM sit_in_sessions s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN labs l ON l.id = s.lab_id
             WHERE s.status = 'active'
             ORDER BY s.time_in DESC"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT s.id AS sit_id,
                    u.id_number,
                    CONCAT(u.first_name,' ',u.last_name) AS name,
                    s.purpose,
                    COALESCE(l.lab_name, 'Unknown') AS lab,
                    u.sessions_left AS session,
                    CASE WHEN s.status = 'active' THEN 'Active'
                         WHEN s.status = 'completed' THEN 'Done'
                         ELSE 'Cancelled' END AS status,
                    s.time_in AS login,
                    s.time_out AS logout,
                    DATE(s.time_in) AS date,
                    s.computer_number
             FROM sit_in_sessions s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN labs l ON l.id = s.lab_id
             ORDER BY s.time_in DESC"
        );
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    ob_end_clean();
    echo json_encode($rows);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([]);
}