<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT id, username, nama_lengkap, email, role FROM users";
$result = $conn->query($sql);

$users = [];
while($row = $result->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
?>