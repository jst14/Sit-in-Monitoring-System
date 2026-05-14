<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    // Calculate duration in SQL
    $durationCalc = "FLOOR(TIMESTAMPDIFF(MINUTE, s.time_in, IFNULL(s.time_out, NOW())) / 60) AS duration_hours,
                     MOD(TIMESTAMPDIFF(MINUTE, s.time_in, IFNULL(s.time_out, NOW())), 60) AS duration_minutes";
    
    $stmt = $pdo->prepare(
        "SELECT 
            s.id AS sit_id,
            u.id_number,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            COALESCE(l.lab_name, 'Unknown') AS lab_name,
            s.purpose,
            s.time_in,
            s.time_out,
            s.status,
            s.satisfaction,
            s.feedback,
            s.feedback_at,
            s.computer_number,
            $durationCalc
         FROM sit_in_sessions s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN labs l ON l.id = s.lab_id
         WHERE s.user_id = ? AND s.time_in IS NOT NULL AND s.time_in != '0000-00-00 00:00:00'
         ORDER BY s.time_in DESC"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        // Safely format times and dates with proper timezone handling
        if ($row['time_in'] && $row['time_in'] !== '0000-00-00 00:00:00') {
            $timeInTimestamp = strtotime($row['time_in']);
            if ($timeInTimestamp !== false) {
                $row['login'] = date('h:i A', $timeInTimestamp);
                $row['date'] = date('M d, Y', $timeInTimestamp);
            } else {
                $row['login'] = '—';
                $row['date'] = '—';
            }
        } else {
            $row['login'] = '—';
            $row['date'] = '—';
        }
        
        // For active sessions, never show a logout time
        if ($row['status'] === 'active') {
            $row['logout'] = '—';
        } elseif ($row['time_out'] && $row['time_out'] !== '0000-00-00 00:00:00') {
            $timeOutTimestamp = strtotime($row['time_out']);
            if ($timeOutTimestamp !== false) {
                $row['logout'] = date('h:i A', $timeOutTimestamp);
            } else {
                $row['logout'] = '—';
            }
        } else {
            $row['logout'] = '—';
        }
        
        // Format duration for display
        // For active sessions, show '—' instead of calculated duration
        if ($row['status'] === 'active') {
            $row['duration_display'] = '—';
        } else {
            $row['duration_display'] = '0m';
            if (isset($row['duration_hours']) && isset($row['duration_minutes'])) {
                $h = (int)$row['duration_hours'];
                $m = (int)$row['duration_minutes'];
                if ($h > 0) {
                    $row['duration_display'] = "{$h}h {$m}m";
                } else {
                    $row['duration_display'] = "{$m}m";
                }
            }
        }
    }

    echo json_encode(['success' => true, 'history' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load history.']);
}
