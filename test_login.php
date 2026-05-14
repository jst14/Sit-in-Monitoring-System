<?php
$conn = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }

// Check for existing students
$result = $conn->query('SELECT id_number, first_name, last_name FROM users WHERE role="student" LIMIT 5');
if ($result->num_rows > 0) {
    echo "Found students:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['id_number'] . ': ' . $row['first_name'] . ' ' . $row['last_name'] . "\n";
    }
} else {
    echo "No students found. Creating test student...\n";
    $id = '20210500';
    $pass = password_hash('test1234', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (id_number, first_name, last_name, email, password, role, course, year_level, address) VALUES ('$id', 'Test', 'Student', 'test@university.edu', '$pass', 'student', 'BSIT', '3', 'Test Address')");
    echo "Created test student with ID: $id and password: test1234\n";
}
$conn->close();
?>
