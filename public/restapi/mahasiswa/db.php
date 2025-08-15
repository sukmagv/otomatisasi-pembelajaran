<?php
$servername = "localhost";
$username = "root"; // user DB untuk testing
$password = "";     // password DB untuk testing
$dbname = "test_db"; // nama database khusus testing

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}