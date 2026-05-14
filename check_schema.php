<?php
$mysqli = new mysqli('localhost', 'root', '', 'sit_in_monitoring');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== LABS TABLE SCHEMA ===\n";
$result = $mysqli->query("DESCRIBE labs");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

echo "\n=== SOFTWARE TABLE SCHEMA ===\n";
$result = $mysqli->query("DESCRIBE software");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

echo "\n=== SIT_IN_SESSIONS TABLE SCHEMA ===\n";
$result = $mysqli->query("DESCRIBE sit_in_sessions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

$mysqli->close();
?>
