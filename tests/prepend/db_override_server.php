<?php
// tests/prepend/db_override_server.php

// Path ke file db.php asli
$originalDbFile = __DIR__ . '/../../public/restapi/mahasiswa/db.php';

// Bikin file override sementara di memory
$overrideCode = <<<'PHP'
<?php
$servername = "localhost";
$username = "root"; // user DB untuk testing
$password = "";     // password DB untuk testing
$dbname = "test_db"; // nama database khusus testing

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
PHP;

// Tulis override ini ke path file asli sebelum dijalankan
file_put_contents($originalDbFile, $overrideCode);
