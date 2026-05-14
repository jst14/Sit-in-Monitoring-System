<?php
// admin_software_upload.php - Handle software file uploads and management
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once 'config.php';
    $conn = db_connect();
    
    if ($conn->connect_error) {
        throw new Exception('DB Connection: ' . $conn->connect_error);
    }

    $action = isset($_GET['action']) ? $_GET['action'] : null;

    // ════════════════════════════════════════
    // GET LABS
    // ════════════════════════════════════════
    if ($action === 'get_labs') {
        $result = $conn->query("SELECT id, lab_name FROM labs ORDER BY lab_name ASC");
        $labs = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labs[] = [
                    'id' => intval($row['id']),
                    'name' => $row['lab_name']
                ];
            }
        }
        
        // If no labs from DB, return default labs
        if (empty($labs)) {
            $labs = [
                ['id' => 1, 'name' => 'Lab 524'],
                ['id' => 2, 'name' => 'Lab 526'],
                ['id' => 3, 'name' => 'Lab 528'],
                ['id' => 4, 'name' => 'Lab 530'],
                ['id' => 5, 'name' => 'Lab 542'],
                ['id' => 6, 'name' => 'MAC']
            ];
        }
        
        ob_end_clean();
        echo json_encode(['success' => true, 'labs' => $labs]);
        exit();
    }

    // ════════════════════════════════════════
    // GET SOFTWARE OVERVIEW
    // ════════════════════════════════════════
    if ($action === 'get_overview') {
        $overview = [];
        
        // Get count of software per lab
        $result = $conn->query("
            SELECT l.lab_name, COUNT(s.id) AS software_count
            FROM labs l
            LEFT JOIN software s ON l.id = s.lab_id
            GROUP BY l.id, l.lab_name
            ORDER BY l.lab_name ASC
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $overview[] = [
                    'lab_name' => $row['lab_name'],
                    'software_count' => intval($row['software_count'])
                ];
            }
        }
        
        // If no data, return default structure
        if (empty($overview)) {
            $overview = [
                ['lab_name' => 'Lab 524', 'software_count' => 0],
                ['lab_name' => 'Lab 526', 'software_count' => 0],
                ['lab_name' => 'Lab 528', 'software_count' => 0],
                ['lab_name' => 'Lab 530', 'software_count' => 0],
                ['lab_name' => 'Lab 542', 'software_count' => 0],
                ['lab_name' => 'MAC', 'software_count' => 0]
            ];
        }
        
        ob_end_clean();
        echo json_encode(['success' => true, 'overview' => $overview]);
        exit();
    }

    // ════════════════════════════════════════
    // GET SOFTWARE LIST
    // ════════════════════════════════════════
    if ($action === 'get_software') {
        $lab_id = isset($_GET['lab_id']) ? intval($_GET['lab_id']) : null;
        
        if ($lab_id) {
            $query = "SELECT s.id, s.software_name, s.category, s.file_path, s.uploaded_at, l.lab_name 
                      FROM software s 
                      LEFT JOIN labs l ON s.lab_id = l.id 
                      WHERE s.lab_id = $lab_id 
                      ORDER BY s.uploaded_at DESC";
        } else {
            $query = "SELECT s.id, s.software_name, s.category, s.file_path, s.uploaded_at, l.lab_name 
                      FROM software s 
                      LEFT JOIN labs l ON s.lab_id = l.id 
                      ORDER BY s.uploaded_at DESC";
        }
        
        $result = $conn->query($query);
        $software = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $software[] = [
                    'id' => intval($row['id']),
                    'name' => $row['software_name'],
                    'category' => $row['category'],
                    'file_path' => $row['file_path'],
                    'lab_name' => $row['lab_name'],
                    'uploaded_at' => $row['uploaded_at']
                ];
            }
        }
        
        ob_end_clean();
        echo json_encode(['success' => true, 'software' => $software]);
        exit();
    }

    // ════════════════════════════════════════
    // UPLOAD FILE
    // ════════════════════════════════════════
    if ($action === 'upload') {
        $software_name = isset($_POST['software_name']) ? trim($_POST['software_name']) : null;
        $category = isset($_POST['category']) ? trim($_POST['category']) : null;
        $labs = isset($_POST['labs']) ? json_decode($_POST['labs'], true) : [];

        if (!$software_name) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Software name is required']);
            exit();
        }

        if (!$category) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Category is required']);
            exit();
        }

        if (empty($labs)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Please select at least one lab']);
            exit();
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            exit();
        }

        // Allowed extensions
        $allowed_ext = ['zip', 'exe', 'msi', 'apk', 'pdf', 'docx'];
        $file_name = $_FILES['file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: zip, exe, msi, apk, pdf, docx']);
            exit();
        }

        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/uploads/software/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $unique_filename = time() . '_' . uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_filename;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit();
        }

        // Insert into database for each selected lab
        $uploaded_count = 0;
        foreach ($labs as $lab_id) {
            $lab_id = intval($lab_id);
            $stmt = $conn->prepare("INSERT INTO software (software_name, category, file_path, lab_id, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $software_name, $category, $unique_filename, $lab_id);
            if ($stmt->execute()) {
                $uploaded_count++;
            }
        }

        if ($uploaded_count > 0) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => "Software uploaded to $uploaded_count lab(s) successfully"]);
        } else {
            // Remove file if db insert failed
            @unlink($file_path);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to save software information']);
        }
        exit();
    }

    // ════════════════════════════════════════
    // DELETE SOFTWARE
    // ════════════════════════════════════════
    if ($action === 'delete') {
        $software_id = isset($_POST['id']) ? intval($_POST['id']) : null;

        if (!$software_id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid software ID']);
            exit();
        }

        // Get file path
        $result = $conn->query("SELECT file_path FROM software WHERE id = $software_id");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $file_path = __DIR__ . '/uploads/software/' . $row['file_path'];
            
            // Delete file
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        // Delete from database
        $result = $conn->query("DELETE FROM software WHERE id = $software_id");
        
        if ($result) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Software deleted successfully']);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to delete software']);
        }
        exit();
    }

    // Default response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
