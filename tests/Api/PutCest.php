<?php

use Tests\Support\ApiTester;

class PutCest
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
        if ($filename !== 'put.php') {
            throw new \Exception("File yang diuji bukan 'put.php', tetapi '{$filename}'");
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

    public function testUpdateUserSuccess(ApiTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => $id,
            'name' => 'Updated codecept user',
            'email' => 'updatedcodeceptuser@gmail.com'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'User berhasil diperbarui'
        ]);
    }

    public function testUpdateUserPartialSuccess(ApiTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        // Update hanya salah satu field (name saja)
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => $id,
            'name' => 'Name Only Updated codecept user'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success'
        ]);
    }

    public function testUpdateUserFailNoId(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'name' => 'Should Fail'
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'ID dan minimal satu field update harus diisi'
        ]);
    }

    public function testUpdateUserFailNoFields(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => 99999999, // id valid tapi tanpa field update
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'ID dan minimal satu field update harus diisi'
        ]);
    }

    public function testUpdateUserNotFound(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => 99999999, // id tidak ada di db
            'name' => 'No User'
        ]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'User tidak ditemukan atau data sama'
        ]);
    }
}
