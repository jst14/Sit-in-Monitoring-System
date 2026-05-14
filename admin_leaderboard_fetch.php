<?php
// admin_leaderboard_fetch.php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    echo json_encode([]);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();

    // Fetch leaderboard: students ranked by number of sit-ins (completed or active)
    // Each sit-in = +10 points. Also compute completed total hours and average session hours.
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
         LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND (s.status = 'completed' OR s.status = 'active')
         WHERE u.role = 'student'
         GROUP BY u.id, u.id_number, u.first_name, u.last_name, u.course, u.year_level, u.profile_pic, u.points
         HAVING sit_in_count > 0 OR COALESCE(u.points, 0) > 0
         ORDER BY points DESC
         LIMIT 50"
    );

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    // Add rank and trophy info for top 3
    $leaderboard = [];
    foreach ($rows as $index => $row) {
        $rank = $index + 1;
        $trophy = '';
        
        if ($rank === 1) {
            $trophy = 'gold';
        } elseif ($rank === 2) {
            $trophy = 'silver';
        } elseif ($rank === 3) {
            $trophy = 'bronze';
        }
        
        $leaderboard[] = array_merge($row, [
            'rank' => $rank,
            'trophy' => $trophy
        ]);
    }

    ob_end_clean();
    echo json_encode($leaderboard);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([]);
}
