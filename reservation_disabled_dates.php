<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS reservation_disabled_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    disabled_date DATE NOT NULL,
    reason VARCHAR(255) DEFAULT 'No classes',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_lab_date (lab_id, disabled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = trim($input['action'] ?? '');

try {
    if ($action === 'disable') {
        $labId = (int) ($input['lab_id'] ?? 0);
        $date = trim($input['date'] ?? '');
        $reason = trim($input['reason'] ?? 'No classes');

        if (!$labId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Lab ID and date are required.']);
            exit;
        }

        // Check if already disabled
        $stmt = $pdo->prepare('SELECT id FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ?');
        $stmt->execute([$labId, $date]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This date is already disabled for the selected lab.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO reservation_disabled_dates (lab_id, disabled_date, reason, created_by, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$labId, $date, $reason, $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Date disabled successfully.']);

    } elseif ($action === 'enable') {
        $labId = (int) ($input['lab_id'] ?? 0);
        $date = trim($input['date'] ?? '');

        if (!$labId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Lab ID and date are required.']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ?');
        $stmt->execute([$labId, $date]);

        echo json_encode(['success' => true, 'message' => 'Date enabled successfully.']);

    } elseif ($action === 'fetch') {
        $labId = (int) ($input['lab_id'] ?? 0);

        if ($labId) {
            $stmt = $pdo->prepare('SELECT lab_id, disabled_date, reason FROM reservation_disabled_dates WHERE lab_id = ? ORDER BY disabled_date DESC');
            $stmt->execute([$labId]);
        } else {
            $stmt = $pdo->prepare('SELECT d.lab_id, d.disabled_date, d.reason, l.lab_name FROM reservation_disabled_dates d JOIN labs l ON l.id = d.lab_id ORDER BY d.disabled_date DESC');
            $stmt->execute();
        }

        $disabledDates = $stmt->fetchAll();
        echo json_encode(['success' => true, 'disabled_dates' => $disabledDates]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>