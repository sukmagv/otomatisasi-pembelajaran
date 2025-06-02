<?php

use Tests\Support\ApiTester;

class GetCest
{
    protected $path;
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
    }

    public function testGetUserByIdSuccess(ApiTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET($this->path, ['id' => $id]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
        ]);
        $I->seeResponseContainsJson(['data' => ['id' => $id]]);
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
