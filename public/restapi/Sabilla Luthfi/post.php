<?php
header('Content-Type: application/json');
require 'db.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';

if ($name && $email) {
    $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        http_response_code(201);
        echo json_encode(['status'=>'success','message'=>'User berhasil ditambahkan', 'data' => [
            'id' => $stmt->insert_id,
            'name' => $name,
            'email' => $email
        ]]);
    } else {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Gagal menambahkan user']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']);
}

$conn->close();