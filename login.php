<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$idNumber = trim($_POST['idNumber'] ?? '');
$password = $_POST['password']      ?? '';

if (!$idNumber || !$password) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, id_number, first_name, last_name, middle_name,
            email, address, course, year_level,
            sessions_left, profile_pic, `password`, role
     FROM users
     WHERE id_number = ?
     LIMIT 1"
);
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if (!$user) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID Number not found.']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

$_SESSION['user_id']       = $user['id'];
$_SESSION['id_number']     = $user['id_number'];
$_SESSION['first_name']    = $user['first_name'];
$_SESSION['last_name']     = $user['last_name'];
$_SESSION['middle_name']   = $user['middle_name'] ?? '';
$_SESSION['email']         = $user['email'];
$_SESSION['address']       = $user['address']     ?? '';
$_SESSION['course']        = $user['course'];
$_SESSION['year_level']    = $user['year_level'];
$_SESSION['sessions_left'] = $user['sessions_left'];
$_SESSION['profile_pic']   = $user['profile_pic'] ?? '';
$_SESSION['role']          = $user['role'] ?? 'student';
$_SESSION['just_logged_in'] = true;  // Flag to show login success modal only once

// Check if user had a sit-in session that ended recently (within last 5 minutes due to logout)
try {
    $recentSessionStmt = $pdo->prepare(
        "SELECT s.id, l.lab_name, DATE(s.time_out) as date, TIME(s.time_out) as time
         FROM sit_in_sessions s
         LEFT JOIN labs l ON l.id = s.lab_id
         WHERE s.user_id = ? AND s.status = 'completed' 
         AND s.time_out >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
         ORDER BY s.time_out DESC LIMIT 1"
    );
    $recentSessionStmt->execute([$user['id']]);
    $recentSession = $recentSessionStmt->fetch();

    if ($recentSession) {
        // Create notification about the session ending due to logout
        $message = sprintf(
            'Your sit-in session in %s ended due to logout on %s at %s.',
            $recentSession['lab_name'] ?: 'Unknown Lab',
            $recentSession['date'],
            substr($recentSession['time'], 0, 5)
        );
        $notifStmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, message, is_read, created_at)
             VALUES (?, ?, ?, 0, NOW())'
        );
        $notifStmt->execute([$user['id'], 'warning', $message]);
    }
} catch (Exception $e) {
    // Continue with login even if notification creation fails
}

ob_end_clean();
echo json_encode(['success' => true, 'message' => 'Login successful.', 'role' => $user['role'] ?? 'student']);
?>