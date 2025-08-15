<?php
header('Content-Type: application/json');
require 'db.php';

$id = $_POST['id'] ?? null;

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;

if (!$id || (!$name && !$email)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'ID dan minimal satu field update harus diisi']);
    exit;
}

$updates = [];
if ($name) $updates[] = "name = '".$conn->real_escape_string($name)."'";
if ($email) $updates[] = "email = '".$conn->real_escape_string($email)."'";

$sql = "UPDATE users SET ".implode(',', $updates)." WHERE id = ".intval($id);

$res = $conn->query($sql);

if ($res && $conn->affected_rows > 0) {
    http_response_code(200);
    echo json_encode(['status'=>'success','message'=>'User berhasil diperbarui']);
} else {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'User tidak ditemukan atau data sama']);
}

$conn->close();
