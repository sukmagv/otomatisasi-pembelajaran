<?php
function runTestLogic($conn, $input) {
    ob_start();
    $_POST = $input;





$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';

if ($name && $email) {
    $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execut();

    if ($stmt->affected_rows > 0) {
        // http_response_code(201);
        echo json_encode(['status'=>'success','message'=>'User berhasil ditambahkan', 'data' => [
            'id' => $stmt->insert_id,
            'name' => $name,
            'email' => $email
        ]]);
    } else {
        // http_response_code(500);
        print(json_encode(['status'=>'error','message'=>'Gagal menambahkan user']));
    }

    $stmt->close();
} else {
    // http_response_code(400);
    print(json_encode(['status'=>'error','message'=>'Data tidak lengkap']));
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