<?php
// admin_sitin_timeout.php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();

    $data   = json_decode(file_get_contents('php://input'), true);
    $sit_id = intval($data['sit_id'] ?? 0);

    if (!$sit_id) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid sit_id.']);
        exit();
    }

    // Find active session and mark completed
    $stmt = $conn->prepare("SELECT user_id FROM sit_in_sessions WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('i', $sit_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Sit-in session not found or already closed.']);
        exit();
    }

    $user_id = $row['user_id'];


    // Only decrement if this is the first time-out for this session
    $stmt = $conn->prepare("SELECT status FROM sit_in_sessions WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $sit_id);
    $stmt->execute();
    $statusRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($statusRow && $statusRow['status'] === 'active') {
        $stmt = $conn->prepare("UPDATE sit_in_sessions SET status = 'completed', time_out = NOW() WHERE id = ? AND status = 'active'");
        $stmt->bind_param('i', $sit_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $updateUser = $conn->prepare("UPDATE users SET sessions_left = GREATEST(sessions_left - 1, 0) WHERE id = ?");
            $updateUser->bind_param('i', $user_id);
            $updateUser->execute();
            $updateUser->close();

            // Award points (10 points per completed session)
            $pointsStmt = $conn->prepare("UPDATE users SET points = COALESCE(points, 0) + 10 WHERE id = ?");
            $pointsStmt->bind_param('i', $user_id);
            $pointsStmt->execute();
            $pointsStmt->close();
        }
    } else {
        $affected = 0;
    }

    $conn->close();

    ob_end_clean();
    echo json_encode(['success' => $affected > 0]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}