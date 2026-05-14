<?php
// upload_photo.php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);
}
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

try {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No file received.']);
        exit();
    }

    $file    = $_FILES['profile_photo'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

    if (!in_array($file['type'], $allowed)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP allowed.']);
        exit();
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Max file size is 2MB.']);
        exit();
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newname = $uploadDir . 'profile_' . (int)$_SESSION['user_id'] . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $newname)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to save file. Check uploads/ folder permissions.']);
        exit();
    }

    require_once 'config.php';
    $conn = db_connect();
    $uid  = (int) $_SESSION['user_id'];

    // FIX: column is `profile_pic` (not `profile_photo`) — matches users table schema
    $stmt = $conn->prepare("UPDATE `users` SET profile_pic = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param('si', $newname, $uid);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // FIX: session stores flat keys (set by login.php), not nested under 'user'
    $_SESSION['profile_pic'] = $newname;

    // Append a cache-busting timestamp so the browser always fetches the new image
    $cacheBustedPath = $newname . '?v=' . time();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Photo updated!',
        'path'    => $cacheBustedPath,   // use this as the <img> src
        'raw'     => $newname,           // raw path without query string (for storage)
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}