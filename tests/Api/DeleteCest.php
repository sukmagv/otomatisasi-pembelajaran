<?php

use Tests\Support\ApiTester;

class DeleteCest
{
    protected $path;
    protected $userId;
    public function _before(ApiTester $I)
    {
        $jsonPath = codecept_root_dir() . 'tests/test-config.json';
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        $originalPath = str_replace('\\', '/', $data['testFile']);
    $this->path = $originalPath;

        // Validasi: hanya izinkan file post.php
        $filename = basename($this->path);
        if ($filename !== 'delete.php') {
            throw new \Exception("File yang diuji bukan 'delete.php', tetapi '{$filename}'");
        }

        // Simpan username (jika ada)
    $username = $data['username'] ?? 'unknown';

    // Overwrite testFile ke get.php sementara
    $data['testFile'] = "storage/restapi/{$username}/get.php";
    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));

    // Ambil userId dari get.php
    $getPath = "/storage/restapi/{$username}/get.php";
    $filePath = codecept_output_dir() . 'test_user_id.json';
    $id = null;

    if (file_exists($filePath)) {
        $user = json_decode(file_get_contents($filePath), true);
        if (!empty($user['id'])) {
            $id = $user['id'];
        }
    }

    if (!$id) {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($getPath, [
            'name' => 'codecept user',
            'email' => 'codeceptuser@gmail.com',
        ]);
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);

        if (!empty($response['data'])) {
            if (isset($response['data']['id'])) {
                $id = $response['data']['id'];
            } elseif (is_array($response['data']) && isset($response['data'][0]['id'])) {
                $randomUser = $response['data'][array_rand($response['data'])];
                $id = $randomUser['id'];
            }
        }
    }

    if (!$id) {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($getPath);
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);

        if (!empty($response['data']) && is_array($response['data'])) {
            $lastUser = end($response['data']);
            if (isset($lastUser['id'])) {
                $id = $lastUser['id'];
            }
        }
    }

    if (!$id) {
        $I->fail("Gagal mendapatkan user ID dari get.php.");
    }

    file_put_contents($filePath, json_encode(['id' => $id], JSON_PRETTY_PRINT));
    $this->userId = $id;

    // Kembalikan testFile ke path aslinya
    $data['testFile'] = str_replace('/', '\\', $originalPath);
    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testDeleteUserSuccess(ApiTester $I)
    {
        // $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        // $id = $user['id'];

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => $this->userId
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'User berhasil dihapus'
        ]);
    }

    public function testDeleteUserFailNoId(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'ID user harus diisi'
        ]);
    }

    public function testDeleteUserNotFound(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => 99999999
        ]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'User tidak ditemukan'
        ]);
    }
}
