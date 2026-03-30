<?php
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS dating_app");
$conn->select_db("dating_app");

$sql = file_get_contents("schema.sql");

if ($conn->multi_query($sql)) {
    do {
        // flush all results
    } while ($conn->more_results() && $conn->next_result());

    echo "Schema imported successfully.";
} else {
    echo "Error importing schema: " . $conn->error;
}

$conn->close();
?>