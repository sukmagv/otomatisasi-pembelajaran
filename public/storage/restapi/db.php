<?php
// db.php

// Ambil konfigurasi DB dari environment Laravel (.env)
$host = getenv('DB_APITEST_HOST') ?: '127.0.0.1';
$user = getenv('DB_APITEST_USERNAME') ?: 'root';
$pass = getenv('DB_APITEST_PASSWORD') ?: '';
$db   = getenv('DB_APITEST_DATABASE') ?: 'test_db';

// Buat koneksi mysqli
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Set charset (optional tapi disarankan)
$conn->set_charset("utf8");
