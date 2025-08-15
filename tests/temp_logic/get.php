<?php
function runTestLogic($conn, $input) {
    ob_start();
    $_GET = $input;





$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // http_response_code(200);
        print(json_encode(['status'=>'success','data'=>$user]));
    } else {
        // http_response_code(404);
        print(json_encode(['status'=>'error','message'=>'User tidak ditemukan']));
    }
} else {
    $result = $conn->query("SELECT id, name, email FROM users");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    // http_response_code(200);
    print(json_encode(['status'=>'success','data'=>$users]));
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