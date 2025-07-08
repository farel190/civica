<?php
$host = "localhost";
$user = "root";
$pass = ""; // sesuaikan password MySQL Anda
$db   = "civicacare"; // nama database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>