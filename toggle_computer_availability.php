<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$computerNumber = (int) ($input['computer_number'] ?? 0);
$labId = (int) ($input['lab_id'] ?? 0);
$unavailable = (int) ($input['unavailable'] ?? 0);

if (!$computerNumber || !$labId) {
    echo json_encode(['success' => false, 'message' => 'Invalid computer number or lab ID.']);
    exit;
}

try {
    if ($unavailable) {
        // Mark computer as unavailable - insert into unavailable_computers table
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO unavailable_computers (lab_id, computer_number, marked_at)
             VALUES (?, ?, NOW())'
        );
        $stmt->execute([$labId, $computerNumber]);
        echo json_encode(['success' => true, 'message' => "Computer $computerNumber marked as unavailable."]);
    } else {
        // Mark computer as available - delete from unavailable_computers table
        $stmt = $pdo->prepare(
            'DELETE FROM unavailable_computers WHERE lab_id = ? AND computer_number = ?'
        );
        $stmt->execute([$labId, $computerNumber]);
        echo json_encode(['success' => true, 'message' => "Computer $computerNumber marked as available."]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
