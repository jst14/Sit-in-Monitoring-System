<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$body = trim($input['body'] ?? '');

if (empty($body)) {
    echo json_encode(['success' => false, 'message' => 'Announcement body is required.']);
    exit;
}

// Insert announcement
$stmt = $pdo->prepare(
    'INSERT INTO announcements (title, body, posted_by, is_active, created_at)
     VALUES (?, ?, ?, 1, NOW())'
);
$title = 'New Announcement'; // Or make it dynamic
$posted_by = $_SESSION['name'] ?? 'Admin';
$stmt->execute([$title, $body, $posted_by]);
$announcement_id = $pdo->lastInsertId();

// Get all users to notify
$userStmt = $pdo->query('SELECT id FROM users');
$users = $userStmt->fetchAll();

// Insert notifications for all users
$notifStmt = $pdo->prepare(
    'INSERT INTO notifications (user_id, type, message, is_read, created_at)
     VALUES (?, ?, ?, 0, NOW())'
);
$message = 'New announcement posted by ' . $posted_by . '.';
foreach ($users as $user) {
    $notifStmt->execute([$user['id'], 'info', $message]);
}

echo json_encode(['success' => true, 'message' => 'Announcement posted successfully.']);
?>