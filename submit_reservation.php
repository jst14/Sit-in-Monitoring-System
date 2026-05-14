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

$userId = (int) $_SESSION['user_id'];
$labId  = (int) ($input['lab_id'] ?? 0);
$purpose = trim($input['purpose'] ?? '');
$date    = trim($input['date'] ?? '');
$time     = trim($input['time'] ?? '');

$computerNumber = isset($input['computer_number']) ? (int) $input['computer_number'] : 0;
if (!$labId || !$computerNumber || !$purpose || !$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if ($computerNumber < 1 || $computerNumber > 40) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid computer number.']);
    exit;
}

// Validate lab
$stmt = $pdo->prepare('SELECT id, lab_name FROM labs WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$labId]);
$lab = $stmt->fetch();
if (!$lab) {
    echo json_encode(['success' => false, 'message' => 'Selected laboratory is not available.']);
    exit;
}

// Check if date is disabled for this lab
$stmt = $pdo->prepare('SELECT reason FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ? LIMIT 1');
$stmt->execute([$labId, $date]);
$disabled = $stmt->fetch();
if ($disabled) {
    echo json_encode(['success' => false, 'message' => 'Reservations are disabled for this date: ' . $disabled['reason']]);
    exit;
}

// Validate date and time formats
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date or time format.']);
    exit;
}

// Check if computer is currently occupied (active sit-in)
$stmt = $pdo->prepare(
    'SELECT COUNT(*) as cnt FROM sit_in_sessions 
     WHERE lab_id = ? AND DATE(time_in) = ? AND status = ?'
);
$stmt->execute([$labId, $date, 'active']);
$occupied = $stmt->fetch();
if ($occupied['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Laboratory is currently occupied. Please select a different time slot.']);
    exit;
}

// Check if computer is already reserved for the same date (approved reservation)
$stmt = $pdo->prepare(
    'SELECT COUNT(*) as cnt FROM reservations 
     WHERE lab_id = ? AND computer_number = ? AND reserved_date = ? AND status = ?'
);
$stmt->execute([$labId, $computerNumber, $date, 'approved']);
$reserved = $stmt->fetch();
if ($reserved['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Computer ' . $computerNumber . ' is already reserved for this date. Please select a different PC.']);
    exit;
}

try {
    $start = new DateTime($time);
    $end   = clone $start;
    $end->modify('+1 hour');
    $timeEnd = $end->format('H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO reservations (user_id, lab_id, reserved_date, time_start, time_end, purpose, computer_number)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([$userId, $labId, $date, $time . ':00', $timeEnd, $purpose, $computerNumber]);

    // Create notification for successful reservation submission
    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, message, is_read, created_at)
         VALUES (?, ?, ?, 0, NOW())'
    );
    $notifStmt->execute([$userId, 'info', 'Your reservation request has been submitted and is pending approval.']);

    echo json_encode(['success' => true, 'message' => 'Reservation request submitted for approval.']);
} catch (Exception $e) {
    error_log('Reservation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to submit reservation. Please try again.']);
}
?>
