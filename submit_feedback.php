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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$sitId = (int) ($input['sit_id'] ?? 0);
$satisfaction = trim($input['satisfaction'] ?? '');
$feedback = trim($input['feedback'] ?? '');

if (!$sitId || !in_array($satisfaction, ['satisfied','unsatisfied'], true)) {
    echo json_encode(['success' => false, 'message' => 'Please choose satisfied or unsatisfied.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id, status FROM sit_in_sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$sitId]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['user_id'] !== (int) $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Sit-in record not found.']);
        exit;
    }

    if ($row['status'] !== 'completed') {
        echo json_encode(['success' => false, 'message' => 'Feedback is only allowed after a completed sit-in.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'UPDATE sit_in_sessions
         SET satisfaction = ?, feedback = ?, feedback_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([$satisfaction, $feedback, $sitId]);

    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, message, is_read, created_at)
         VALUES (?, ?, ?, 0, NOW())'
    );
    $notifStmt->execute([
        $_SESSION['user_id'],
        'success',
        'Your feedback has been recorded and will be read by the admin.'
    ]);

    echo json_encode(['success' => true, 'message' => 'Feedback saved successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to save feedback.']);
}
?>
