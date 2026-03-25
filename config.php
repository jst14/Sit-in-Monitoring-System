<?php

$host     = 'localhost';
$dbname   = 'sit_in_monitoring';
$username = 'root';
$password = '';

// db_connect() must be outside the try block so it is always globally available
function db_connect() {
    $conn = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}
?>