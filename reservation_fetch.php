<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$labId = (int) ($input['lab_id'] ?? 0);
$date  = trim($input['date'] ?? '');
$where = 'WHERE r.status = ?';
$params = ['pending'];
if ($labId) {
    $where .= ' AND r.lab_id = ?';
    $params[] = $labId;
}
if ($date) {
    $where .= ' AND r.reserved_date = ?';
    $params[] = $date;
}

$stmt = $pdo->prepare(
    'SELECT r.id, u.id_number, u.first_name, u.last_name,
            l.lab_name, r.purpose, r.reserved_date, r.time_start, r.time_end, r.status, r.computer_number
     FROM reservations r
     JOIN users u ON u.id = r.user_id
     JOIN labs l ON l.id = r.lab_id
     ' . $where . '
     ORDER BY r.created_at DESC'
);
$stmt->execute($params);
$pending = $stmt->fetchAll();

foreach ($pending as &$reservation) {
    $reservation['student_name'] = trim($reservation['first_name'] . ' ' . $reservation['last_name']);
    $reservation['time_start'] = substr($reservation['time_start'], 0, 5);
    $reservation['time_end']   = substr($reservation['time_end'], 0, 5);
}

$logWhere = 'WHERE 1=1';
$logParams = [];
if ($labId) {
    $logWhere .= ' AND r.lab_id = ?';
    $logParams[] = $labId;
}
if ($date) {
    $logWhere .= ' AND r.reserved_date = ?';
    $logParams[] = $date;
}

$stmt = $pdo->prepare(
    'SELECT r.id, u.id_number, u.first_name, u.last_name,
            l.lab_name, r.purpose, r.reserved_date, r.time_start, r.time_end, r.status, r.computer_number
     FROM reservations r
     JOIN users u ON u.id = r.user_id
     JOIN labs l ON l.id = r.lab_id
     ' . $logWhere . '
     ORDER BY r.created_at DESC
     LIMIT 20'
);
$stmt->execute($logParams);
$logs = $stmt->fetchAll();
foreach ($logs as &$reservation) {
    $reservation['student_name'] = trim($reservation['first_name'] . ' ' . $reservation['last_name']);
    $reservation['time_start'] = substr($reservation['time_start'], 0, 5);
    $reservation['time_end']   = substr($reservation['time_end'], 0, 5);
}

$usedWhere = 'WHERE r.status = ?';
$usedParams = ['approved'];
if ($labId) {
    $usedWhere .= ' AND r.lab_id = ?';
    $usedParams[] = $labId;
}
if ($date) {
    $usedWhere .= ' AND r.reserved_date = ?';
    $usedParams[] = $date;
}
$stmt = $pdo->prepare(
    'SELECT COUNT(*) as used_count
     FROM reservations r
     ' . $usedWhere
);
$stmt->execute($usedParams);
$usedCount = (int) ($stmt->fetchColumn() ?: 0);

$occupiedNumbers = [];
if ($labId) {
    // Get occupied computers from approved reservations for the selected date
    if ($date) {
        $stmt = $pdo->prepare(
            'SELECT DISTINCT computer_number
             FROM reservations
             WHERE lab_id = ? AND reserved_date = ? AND status = ? AND computer_number IS NOT NULL'
        );
        $stmt->execute([$labId, $date, 'approved']);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $occupiedNumbers[] = (int) $row['computer_number'];
        }
    }
    
    // Also get occupied computers from active sit-in sessions in this lab
    $stmt = $pdo->prepare(
        'SELECT DISTINCT computer_number
         FROM sit_in_sessions
         WHERE lab_id = ? AND status = ? AND computer_number IS NOT NULL'
    );
    $stmt->execute([$labId, 'active']);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $occupiedNumbers[] = (int) $row['computer_number'];
    }
    
    // Remove duplicates
    $occupiedNumbers = array_values(array_unique($occupiedNumbers));
}

$labName = '';
if ($labId) {
    $stmt = $pdo->prepare('SELECT lab_name FROM labs WHERE id = ? LIMIT 1');
    $stmt->execute([$labId]);
    $labName = $stmt->fetchColumn() ?: '';
}

$disabledDate = null;
if ($labId && $date) {
    $stmt = $pdo->prepare('SELECT reason FROM reservation_disabled_dates WHERE lab_id = ? AND disabled_date = ? LIMIT 1');
    $stmt->execute([$labId, $date]);
    $disabledDate = $stmt->fetchColumn() ?: null;
}

$available = max(0, 35 - $usedCount);

echo json_encode([
    'success' => true,
    'requests' => $pending,
    'logs'     => $logs,
    'stats'    => [
        'lab_id'      => $labId,
        'lab_name'    => $labName,
        'used'        => $usedCount,
        'available'   => $available,
        'pending'     => count($pending),
        'occupied'    => $occupiedNumbers,
        'disabled'    => (bool) $disabledDate,
        'disabled_reason' => $disabledDate,
    ]
]);
