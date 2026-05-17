<?php
session_start();

// Check for active sit-in session and end it before logout
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'config.php';
        $conn = db_connect();

        // Find active session for this user
        $stmt = $conn->prepare("SELECT id FROM sit_in_sessions WHERE user_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $sit_id = $row['id'];

            // End the session
            $stmt = $conn->prepare("UPDATE sit_in_sessions SET status = 'completed', time_out = NOW() WHERE id = ? AND status = 'active'");
            $stmt->bind_param('i', $sit_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                // Decrement sessions_left
                $updateUser = $conn->prepare("UPDATE users SET sessions_left = GREATEST(sessions_left - 1, 0) WHERE id = ?");
                $updateUser->bind_param('i', $_SESSION['user_id']);
                $updateUser->execute();
                $updateUser->close();

                // Award points (10 points per completed session)
                $pointsStmt = $conn->prepare("UPDATE users SET points = COALESCE(points, 0) + 10 WHERE id = ?");
                $pointsStmt->bind_param('i', $_SESSION['user_id']);
                $pointsStmt->execute();
                $pointsStmt->close();
            }
        }

        $conn->close();
    } catch (Throwable $e) {
        // Continue with logout even if ending session fails
    }
}

session_unset();
session_destroy();
header('Location: index.html');
exit;
?>