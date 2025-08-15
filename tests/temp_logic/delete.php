<?php
function runTestLogic($conn, $input) {
    ob_start();
    $_POST = $input;





$id = $_POST['id'] ?? null;

if (!$id) {
    // http_response_code(400);
    print(json_encode(['status'=>'error','message'=>'ID user harus diisi']));
    $result = ob_get_clean(); return json_decode($result, true);
}

$sql = "DELETE FROM users WHERE id = ".intval($id);

$res = $conn->query($sql);

if ($res && $conn->affected_rows > 0) {
    // http_response_code(200);
    print(json_encode(['status'=>'success','message'=>'User berhasil dihapus']));
} else {
    // http_response_code(404);
    print(json_encode(['status'=>'error','message'=>'User tidak ditemukan']));
}

$conn->close();

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