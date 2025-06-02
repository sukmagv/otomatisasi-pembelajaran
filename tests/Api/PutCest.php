<?php

use Tests\Support\ApiTester;

class PutCest
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
        if ($filename !== 'put.php') {
            throw new \Exception("File yang diuji bukan 'put.php', tetapi '{$filename}'");
        }
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
