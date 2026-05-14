<?php
$mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error;
    exit;
}
echo "Connection successful\n";
$tables = ['labs', 'sit_in_sessions', 'software'];
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    echo $table . ": " . ($result && $result->num_rows > 0 ? "EXISTS" : "NOT FOUND") . "\n";
    if ($result && $result->num_rows > 0) {
        $countResult = $mysqli->query("SELECT COUNT(*) as cnt FROM $table");
        if ($countResult) {
            $row = $countResult->fetch_assoc();
            echo "  └─ Records: " . $row['cnt'] . "\n";
        }
    }
}
$mysqli->close();
?>
