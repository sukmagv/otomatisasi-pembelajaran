<?php

use Tests\Support\ApiTester;

class GetCest
{
    protected $path;
    protected $userId;
    public function _before(ApiTester $I)
    {
        $jsonPath = codecept_root_dir() . 'tests/test-config.json';
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        $rawPath = $data['testFile'];
        $this->path = str_replace('\\', '/', $rawPath);

        // Validasi: hanya izinkan file post.php
        $filename = basename($this->path);
        if ($filename !== 'get.php') {
            throw new \Exception("File yang diuji bukan 'get.php', tetapi '{$filename}'");
        }

        // Proses ambil ID user
    $filePath = codecept_output_dir() . 'test_user_id.json';
    $id = null;

    // Step 1: Ambil dari file jika ada
    if (file_exists($filePath)) {
        $user = json_decode(file_get_contents($filePath), true);
        if (!empty($user['id'])) {
            $id = $user['id'];
        }
    }

    // Step 2: Jika kosong, cari user berdasarkan name dan email
    if (!$id) {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path, [
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

    // Step 3: Jika tetap tidak ada, ambil ID terakhir di database
    if (!$id) {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path);
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);

        if (!empty($response['data']) && is_array($response['data'])) {
            $lastUser = end($response['data']);
            if (isset($lastUser['id'])) {
                $id = $lastUser['id'];
            }
        }
    }

    // Step 4: Jika tetap gagal, hentikan test
    if (!$id) {
        $I->fail("Tidak berhasil menemukan user ID dari file, pencarian, maupun ID terakhir.");
    }

    // Step 5: Simpan ke file agar reusable
    file_put_contents($filePath, json_encode(['id' => $id], JSON_PRETTY_PRINT));

    // Step 6: Simpan ke properti object
    $this->userId = $id;
    }

    public function testGetUserByIdSuccess(ApiTester $I)
    {
        // $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        // $id = $user['id'];

        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path, ['id' => $this->userId]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);
        $I->seeResponseContainsJson(['data' => ['id' => $this->userId]]);
    }

    public function testGetUserByIdNotFound(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path, ['id' => 99999999]);
        $I->seeResponseCodeIs(404);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'User tidak ditemukan',
        ]);
    }

    public function testGetAllUsers(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);
        $I->seeResponseContainsJson(['data' => []]); // Minimal data harus array (bisa kosong)
    }
}
