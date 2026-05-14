<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

try {
    // Fetch all active labs
    $stmt = $pdo->prepare(
        'SELECT id, lab_name
         FROM labs
         ORDER BY lab_name ASC'
    );
    $stmt->execute();
    $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'labs' => $labs]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
