<?php
header('Content-Type: application/json');
require 'db.php';

$id = $_POST['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'ID user harus diisi']);
    exit;
}

$sql = "DELETE FROM users WHERE id = ".intval($id);

$res = $conn->query($sql);

if ($res && $conn->affected_rows > 0) {
    http_response_code(200);
    echo json_encode(['status'=>'success','message'=>'User berhasil dihapus']);
} else {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'User tidak ditemukan']);
}

$conn->close();