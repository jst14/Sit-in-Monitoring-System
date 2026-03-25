<?php

header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$idNumber   = trim($_POST['idNumber']      ?? '');
$email      = trim($_POST['email']         ?? '');
$lastName   = trim($_POST['lastName']      ?? '');
$firstName  = trim($_POST['firstName']     ?? '');
$middleName = trim($_POST['middleName']    ?? '');
$address    = trim($_POST['address']       ?? '');
$course     = trim($_POST['course']        ?? '');
$yearLevel  = intval($_POST['courseLevel'] ?? 0); 
$password   = $_POST['password']           ?? '';
$repeatPw   = $_POST['repeatPassword']     ?? '';

if (!$idNumber || !$email || !$lastName || !$firstName ||
    !$address  || !$course || !$yearLevel || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

if ($password !== $repeatPw) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE id_number = ? OR email = ? LIMIT 1");
$check->execute([$idNumber, $email]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ID Number or Email is already registered.']);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    "INSERT INTO users
        (id_number, email, first_name, last_name, middle_name,
         address, course, year_level, `password`)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if ($stmt->execute([$idNumber, $email, $firstName, $lastName,
                    $middleName, $address, $course, $yearLevel, $hash])) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
?>