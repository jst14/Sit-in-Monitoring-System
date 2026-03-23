<?php

session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$idNumber = trim($_POST['idNumber'] ?? '');
$password = $_POST['password']      ?? '';

if (!$idNumber || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, id_number, first_name, last_name, middle_name,
            email, address, course, year_level,
            sessions_left, profile_pic, `password`
     FROM users
     WHERE id_number = ?
     LIMIT 1"
);
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'ID Number not found.']);
    exit;
}

if (!password_verify($password, $user['password'])) {
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

echo json_encode(['success' => true, 'message' => 'Login successful.']);
?>