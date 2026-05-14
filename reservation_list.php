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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT r.id, r.purpose, r.reserved_date, r.time_start, r.time_end, r.status, r.computer_number,
            l.lab_name, u.id_number, u.first_name, u.last_name
     FROM reservations r
     JOIN labs l ON l.id = r.lab_id
     JOIN users u ON u.id = r.user_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC'
);
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll();

foreach ($reservations as &$reservation) {
    $reservation['time_start'] = substr($reservation['time_start'], 0, 5);
    $reservation['time_end']   = substr($reservation['time_end'], 0, 5);
}

echo json_encode(['success' => true, 'reservations' => $reservations]);
?>
