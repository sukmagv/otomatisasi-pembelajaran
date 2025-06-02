<?php

use Tests\Support\ApiTester;

class DeleteCest
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
        if ($filename !== 'delete.php') {
            throw new \Exception("File yang diuji bukan 'delete.php', tetapi '{$filename}'");
        }
    }

    public function testDeleteUserSuccess(ApiTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST($this->path, [
            'id' => $id
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
