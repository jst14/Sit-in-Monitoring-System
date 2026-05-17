<?php
// manage_top3_notifications.php - Add/remove notifications for top 3 leaderboard leaders
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

    $data = json_decode(file_get_contents('php://input'), true);
    $action = trim($data['action'] ?? ''); // 'enable' or 'disable'

    if (!in_array($action, ['enable', 'disable'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

    if ($action === 'enable') {
        // Get top 3 students from completed sit-ins
        $stmt = $conn->prepare(
            "SELECT 
                u.id,
                u.id_number,
                CONCAT(u.first_name, ' ', u.last_name) AS name,
                COUNT(s.id) AS sit_in_count,
                COALESCE(u.points, 0) + COUNT(s.id) * 10 AS points
             FROM users u
             LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
             WHERE u.role = 'student'
             GROUP BY u.id, u.id_number, u.first_name, u.last_name, u.points
             HAVING sit_in_count > 0 OR COALESCE(u.points, 0) > 0
             ORDER BY points DESC
             LIMIT 3"
        );

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Create notifications for top 3
        if (!empty($rows)) {
            foreach ($rows as $index => $student) {
                $rank = $index + 1;
                $medal = match($rank) {
                    1 => '🥇',
                    2 => '🥈',
                    3 => '🥉',
                    default => ''
                };

                $message = "Congratulations! You are rank #{$rank} {$medal} in the sit-in leaderboard with {$student['points']} points!";

                // Check if notification already exists to avoid duplicates
                $checkStmt = $conn->prepare(
                    "SELECT id FROM notifications 
                     WHERE user_id = ? AND message LIKE '%rank #%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                );
                $checkStmt->bind_param('i', $student['id']);
                $checkStmt->execute();
                $existing = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();

                if (!$existing) {
                    $notifyStmt = $conn->prepare(
                        "INSERT INTO notifications (user_id, type, message, icon, created_at) 
                         VALUES (?, 'achievement', ?, 'fa-trophy', NOW())"
                    );
                    $notifyStmt->bind_param('is', $student['id'], $message);
                    $notifyStmt->execute();
                    $notifyStmt->close();
                }
            }
        }

        $conn->close();
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Top 3 notifications created']);

    } else {
        // action === 'disable'
        // Delete notifications about leaderboard rank
        $stmt = $conn->prepare(
            "DELETE FROM notifications 
             WHERE message LIKE '%rank #%' AND message LIKE '%leaderboard%'"
        );
        $stmt->execute();
        $stmt->close();

        $conn->close();
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Top 3 notifications removed']);
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
