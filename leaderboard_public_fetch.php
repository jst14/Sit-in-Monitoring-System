<?php
// leaderboard_public_fetch.php - Fetch public leaderboard for landing page (no session required)
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
header('Content-Type: application/json');

try {
    require_once 'config.php';
    $conn = db_connect();

    if (!$conn) {
        ob_end_clean();
        echo json_encode(['success' => false, 'leaderboard' => []]);
        exit();
    }

    // Check if leaderboard is enabled
    $stmt = $conn->prepare("SELECT setting_value FROM admin_settings WHERE setting_name = 'leaderboard_enabled'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || $row['setting_value'] !== 'true') {
        ob_end_clean();
        echo json_encode(['success' => false, 'leaderboard' => []]);
        $conn->close();
        exit();
    }

    // Fetch public leaderboard: top students ranked by completed sit-ins
    // Each completed sit-in = +10 points. Points only awarded after session is completed.
    $stmt = $conn->prepare(
        "SELECT 
            u.id,
            u.id_number,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.first_name,
            u.last_name,
            u.course,
            u.year_level,
            u.profile_pic,
            COUNT(s.id) AS sit_in_count,
            COALESCE(u.points, 0) + COUNT(s.id) * 10 AS points,
            ROUND(COALESCE(SUM(CASE WHEN s.status = 'completed' THEN TIMESTAMPDIFF(SECOND, s.time_in, s.time_out) ELSE 0 END) / 3600, 0), 2) AS total_hours,
            ROUND(COALESCE(AVG(CASE WHEN s.status = 'completed' THEN TIMESTAMPDIFF(SECOND, s.time_in, s.time_out) ELSE NULL END) / 3600, 0), 2) AS avg_session
         FROM users u
         LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
         WHERE u.role = 'student'
         GROUP BY u.id, u.id_number, u.first_name, u.last_name, u.course, u.year_level, u.profile_pic, u.points
         HAVING sit_in_count > 0 OR COALESCE(u.points, 0) > 0
         ORDER BY points DESC, sit_in_count DESC
         LIMIT 10"
    );

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    // Build leaderboard with rank and trophy info
    $leaderboard = [];
    foreach ($rows as $index => $row) {
        $rank = $index + 1;
        $trophy = '';
        
        if ($rank === 1) {
            $trophy = '🥇';
        } elseif ($rank === 2) {
            $trophy = '🥈';
        } elseif ($rank === 3) {
            $trophy = '🥉';
        }
        
        $leaderboard[] = array_merge($row, [
            'rank' => $rank,
            'trophy' => $trophy
        ]);
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);

} catch (Throwable $e) {
    ob_end_clean();
    error_log("Leaderboard error: " . $e->getMessage());
    echo json_encode(['success' => false, 'leaderboard' => [], 'error' => $e->getMessage()]);
}
?>
