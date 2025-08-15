<?php
function runTestLogic($conn, $input) {
    ob_start();
    


$host = "localhost";
$user = "admin";
$password = "12345";
$dbname = "test";



if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

?>

    $result = ob_get_clean();
    $decoded = json_decode($result, true);

    if (is_null($decoded)) {
        return [
            'status' => 'error',
            'message' => 'Invalid or empty JSON output',
            'raw' => $result
        ];
    }

    return $decoded;
}