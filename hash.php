<?php
$conn = new mysqli("localhost", "root", "", "dating_app");

$result = $conn->query("SELECT account_id, password FROM account");

while ($row = $result->fetch_assoc()) {
    // skip already hashed passwords
    if (str_starts_with($row['password'], '$2y$')) continue;

    $hashed = password_hash($row['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE account SET password = ? WHERE account_id = ?");
    $stmt->bind_param("si", $hashed, $row['account_id']);
    $stmt->execute();
}