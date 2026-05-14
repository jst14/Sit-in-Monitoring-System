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
    $stmt = $pdo->prepare(
        'SELECT id, title, body, posted_by, created_at
         FROM announcements
         WHERE is_active = 1
         ORDER BY created_at DESC
         LIMIT 10'
    );
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    echo json_encode(['success' => true, 'announcements' => $announcements]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>