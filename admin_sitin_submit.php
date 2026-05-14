<?php
// admin_sitin_submit.php
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

    $data           = json_decode(file_get_contents('php://input'), true);
    $id_number      = trim($data['id_number'] ?? '');
    $purpose        = trim($data['purpose']   ?? '');
    $lab            = trim($data['lab_id']    ?? '');
    $computer_number = isset($data['computer_number']) ? (int) $data['computer_number'] : null;

    if (!$id_number || !$purpose || !$lab) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }

    // Get the student's DB id and check sessions
    $stmt = $conn->prepare("SELECT id, sessions_left AS remaining_sessions FROM users WHERE id_number = ? LIMIT 1");
    $stmt->bind_param('s', $id_number);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit();
    }
    if ($row['remaining_sessions'] <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student has no remaining sessions.']);
        exit();
    }

    // Check if student already has an active sit-in
    $stmt = $conn->prepare("SELECT id FROM sit_in_sessions WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student is already sitting in.']);
        exit();
    }

    // Resolve selected lab to lab_id
    $labInput = trim($lab);
    if (is_numeric($labInput)) {
        // If it's a number, it's already a lab_id - validate it exists
        $lab_id = (int) $labInput;
        $labStmt = $conn->prepare("SELECT id, lab_name FROM labs WHERE id = ? AND is_active = 1 LIMIT 1");
        $labStmt->bind_param('i', $lab_id);
        $labStmt->execute();
        $labRow = $labStmt->get_result()->fetch_assoc();
        $labStmt->close();
        if (!$labRow) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Selected laboratory is not available.']);
            exit;
        }
    } else {
        // If it's a name, look it up
        $labName = $labInput;
        if ($labName && !preg_match('/^Lab\s+/i', $labName) && strtoupper($labName) !== 'MAC') {
            $labName = 'Lab ' . $labName;
        }
        $labStmt = $conn->prepare("SELECT id FROM labs WHERE lab_name = ? AND is_active = 1 LIMIT 1");
        $labStmt->bind_param('s', $labName);
        $labStmt->execute();
        $labRow = $labStmt->get_result()->fetch_assoc();
        $labStmt->close();
        if (!$labRow) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Selected laboratory is not available.']);
            exit;
        }
        $lab_id = $labRow['id'];
    }

    // Insert sit-in record (status is active; time_out will be set when user logs out)
    $stmt = $conn->prepare(
        "INSERT INTO sit_in_sessions (user_id, lab_id, purpose, computer_number, time_in, status, created_at)
         VALUES (?, ?, ?, ?, NOW(), 'active', NOW())"
    );
    $stmt->bind_param('iisi', $row['id'], $lab_id, $purpose, $computer_number);
    $stmt->execute();
    $new_sit_id = $conn->insert_id;
    $stmt->close();

    $conn->close();

    ob_end_clean();
    echo json_encode(['success' => true, 'sit_id' => $new_sit_id]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}