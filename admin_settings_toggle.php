<?php
// admin_settings_toggle.php - Handle leaderboard visibility toggle
ini_set('display_errors', 0);
error_reporting(0);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        // Save setting
        $setting_name = $input['setting_name'] ?? '';
        $setting_value = $input['setting_value'] ?? '';

        if (empty($setting_name)) {
            echo json_encode(['success' => false, 'message' => 'Setting name is required']);
            exit();
        }

        // Check if setting exists
        $stmt = $conn->prepare("SELECT id FROM admin_settings WHERE setting_name = ?");
        $stmt->bind_param("s", $setting_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing setting
            $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_name = ?");
            $stmt->bind_param("ss", $setting_value, $setting_name);
        } else {
            // Insert new setting
            $stmt = $conn->prepare("INSERT INTO admin_settings (setting_name, setting_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $setting_name, $setting_value);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Setting saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save setting']);
        }
        $stmt->close();

    } elseif ($method === 'GET') {
        // Get all settings
        $stmt = $conn->prepare("SELECT setting_name, setting_value FROM admin_settings");
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = [];

        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }

        echo json_encode(['success' => true, 'settings' => $settings]);
        $stmt->close();
    }

    $conn->close();

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
