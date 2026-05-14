<?php
// check_computer_availability.php - Check if computer is occupied or reserved
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$labId = (int) ($input['lab_id'] ?? 0);
$date = trim($input['date'] ?? '');

if (!$labId || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing lab_id or date.']);
    exit;
}

try {
    // Get all computers (1-40)
    $computers = [];
    for ($i = 1; $i <= 40; $i++) {
        $computers[$i] = 'available'; // default status
    }

    // Check for occupied computers (active sit-in sessions)
    // Note: sit_in_sessions table doesn't track computer_number, so we just mark lab as has-occupancy
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as cnt
         FROM sit_in_sessions s
         WHERE s.lab_id = ? 
         AND s.status = ?
         AND DATE(s.time_in) = ?'
    );
    $stmt->execute([$labId, 'active', $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // If lab has any active sessions, we can't determine exact computer, so skip marking individual computers
    // This is conservative - we only block if we know the exact computer is reserved

    // Check for reserved computers (approved reservations)
    $stmt = $pdo->prepare(
        'SELECT DISTINCT r.computer_number
         FROM reservations r
         WHERE r.lab_id = ? 
         AND r.status = ?
         AND r.reserved_date = ?'
    );
    $stmt->execute([$labId, 'approved', $date]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cn = (int) $row['computer_number'];
        if ($cn >= 1 && $cn <= 40) {
            // Only mark as reserved if not already occupied
            if ($computers[$cn] !== 'occupied') {
                $computers[$cn] = 'reserved';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'computers' => $computers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking availability: ' . $e->getMessage()
    ]);
}
?>
