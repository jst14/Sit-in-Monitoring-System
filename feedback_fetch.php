<?php
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

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$showAll = isset($input['show_all']) && (int) $input['show_all'] === 1;

try {
    $where = 'WHERE (s.satisfaction IS NOT NULL OR s.feedback IS NOT NULL)';
    if (!$showAll) {
        $where .= ' AND s.status = ?';
        $params = ['completed'];
    } else {
        $params = [];
    }

    $stmt = $pdo->prepare(
        'SELECT u.id_number,
                COALESCE(l.lab_name, "Unknown") AS lab_name,
                DATE(s.time_in) AS date,
                s.satisfaction,
                s.feedback
         FROM sit_in_sessions s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN labs l ON l.id = s.lab_id
         ' . $where . '
         ORDER BY s.time_in DESC'
    );

    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $feedback = array_map(function ($row) {
        $message = trim($row['feedback'] ?? '');
        if ($row['satisfaction'] && $message !== '') {
            $message = ucfirst($row['satisfaction']) . ': ' . $message;
        } elseif ($row['satisfaction']) {
            $message = ucfirst($row['satisfaction']);
        }
        return [
            'id_number' => $row['id_number'],
            'lab_name'  => $row['lab_name'],
            'date'      => $row['date'],
            'message'   => $message ?: 'No feedback message provided',
        ];
    }, $rows);

    echo json_encode(['success' => true, 'feedback' => $feedback]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to load feedback reports.']);
}
?>
