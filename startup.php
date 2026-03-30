<?php
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents("schema.sql");

if ($conn->multi_query($sql)) {
    echo "Schema imported successfully.";
} else {
    echo "Error importing schema: " . $conn->error;
}

$conn->close();
?>