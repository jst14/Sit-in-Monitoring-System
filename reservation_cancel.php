<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$resId = (int) ($input['id'] ?? 0);
if (!$resId) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ? AND user_id = ? AND status = ?');
$stmt->execute(['cancelled', $resId, $_SESSION['user_id'], 'pending']);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or cannot be cancelled.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Reservation cancelled.']);
?>
