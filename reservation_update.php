<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

$resId = (int) ($input['id'] ?? 0);
$action = trim($input['action'] ?? '');
if (!$resId || !in_array($action, ['approve', 'deny'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation request data.']);
    exit;
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$computerNumber = isset($input['computer_number']) ? (int) $input['computer_number'] : null;
if ($status === 'approved' && $computerNumber !== null && ($computerNumber < 1 || $computerNumber > 40)) {
    echo json_encode(['success' => false, 'message' => 'Invalid computer number.']);
    exit;
}

$pdo->beginTransaction();
try {
    if ($status === 'approved' && $computerNumber !== null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservations
             WHERE lab_id = (SELECT lab_id FROM reservations WHERE id = ?)
               AND reserved_date = (SELECT reserved_date FROM reservations WHERE id = ?)
               AND computer_number = ?
               AND status = ?
               AND id <> ?'
        );
        $stmt->execute([$resId, $resId, $computerNumber, 'approved', $resId]);
        if ((int) $stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'This computer is already assigned for the chosen lab and date.']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE reservations SET status = ?, computer_number = ? WHERE id = ? AND status = ?');
        $stmt->execute([$status, $computerNumber, $resId, 'pending']);
    } else {
        $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ? AND status = ?');
        $stmt->execute([$status, $resId, 'pending']);
    }

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Reservation request not found or already processed.']);
        exit;
    }

    // If approval, create a sit-in session
    if ($status === 'approved') {
        $stmt = $pdo->prepare(
            'SELECT r.user_id, r.lab_id, r.purpose, r.reserved_date, r.time_start, r.computer_number, u.sessions_left
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             WHERE r.id = ? LIMIT 1'
        );
        $stmt->execute([$resId]);
        $resRow = $stmt->fetch();
        
        if ($resRow && $resRow['sessions_left'] > 0) {
            // Check if student already has an active sit-in
            $checkStmt = $pdo->prepare('SELECT id FROM sit_in_sessions WHERE user_id = ? AND status = ?');
            $checkStmt->execute([$resRow['user_id'], 'active']);
            
            if (!$checkStmt->fetch()) {
                // Create sit-in session automatically
                // Session starts now when approved, time_out will be set when user logs out
                $sitInStmt = $pdo->prepare(
                    'INSERT INTO sit_in_sessions (user_id, lab_id, purpose, time_in, status, computer_number, created_at)
                     VALUES (?, ?, ?, NOW(), ?, ?, NOW())'
                );
                $sitInStmt->execute([
                    $resRow['user_id'],
                    $resRow['lab_id'],
                    $resRow['purpose'],
                    'active',
                    $resRow['computer_number']
                ]);
            }
        }
    }

    $stmt = $pdo->prepare(
        'SELECT r.user_id, l.lab_name, r.reserved_date, r.time_start
         FROM reservations r
         JOIN labs l ON l.id = r.lab_id
         WHERE r.id = ? LIMIT 1'
    );
    $stmt->execute([$resId]);
    $row = $stmt->fetch();
    if ($row) {
        $message = sprintf(
            'Your reservation for %s on %s at %s has been %s.',
            $row['lab_name'],
            $row['reserved_date'],
            substr($row['time_start'], 0, 5),
            $status === 'approved' ? 'approved' : 'denied'
        );
        $type = $status === 'approved' ? 'success' : 'danger';

        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())'
        );
        $stmt->execute([$row['user_id'], $type, $message]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation request ' . ($status === 'approved' ? 'approved' : 'rejected') . '.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Unable to update reservation. Please try again.']);
}
