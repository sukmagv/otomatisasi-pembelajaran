<?php
// db_server.php → koneksi database untuk testing

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'test_db'; // database khusus pengujian

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
