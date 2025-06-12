<?php
header('Content-Type: application/json');
require 'db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        http_response_code(200);
        echo json_encode(['status'=>'success','data'=>$user]);
    } else {
        http_response_code(404);
        echo json_encode(['status'=>'error','message'=>'User tidak ditemukan']);
    }
} else {
    $result = $conn->query("SELECT id, name, email FROM users");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    http_response_code(200);
    echo json_encode(['status'=>'success','data'=>$users]);
}
$conn->close();