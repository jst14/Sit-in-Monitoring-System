<?php
// admin_award_points.php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (empty($data)) {
        $data = $_POST;
    }

    $id_number = trim($data['id_number'] ?? '');
    $points    = $data['points'] ?? null;
    $reason    = trim($data['reason'] ?? '');

    if (!$id_number) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student ID number is required.']);
        exit();
    }

    if (!is_numeric($points) || (int) $points <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Points must be a positive number.']);
        exit();
    }

    $points = (int) $points;

    $stmt = $conn->prepare("SELECT id, COALESCE(points, 0) AS current_points FROM users WHERE id_number = ? AND role = 'student' LIMIT 1");
    $stmt->bind_param('s', $id_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        $stmt->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit();
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $newPoints = $row['current_points'] + $points;
    $update = $conn->prepare("UPDATE users SET points = ? WHERE id = ? LIMIT 1");
    $update->bind_param('ii', $newPoints, $row['id']);
    $update->execute();
    $update->close();

    $conn->close();
    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => "Added {$points} points to {$id_number}.",
        'updated_points' => $newPoints,
        'id_number' => $id_number,
    ]);
    exit();
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unable to award points.']);
    exit();
}
