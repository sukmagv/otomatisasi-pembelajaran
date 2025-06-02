<?php

use Tests\Support\ApiTester;

class PostCest
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
        if ($filename !== 'post.php') {
            throw new \Exception("File yang diuji bukan 'post.php', tetapi '{$filename}'");
        }
    }

    public function testCreateUserViaAPI(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost($this->path, [
            'name' => 'codecept user',
            'email' => 'codeceptuser@gmail.com'
        ]);
        
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'User berhasil ditambahkan'
        ]);

        $data = json_decode($I->grabResponse(), true);
        file_put_contents(codecept_output_dir() . 'test_user_id.json', json_encode([
            'id' => $data['data']['id']
        ]));
    }

    public function testFailToCreateUserWithIncompleteData(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost($this->path, [
            'name' => 'codecept user'
            // no email
        ]);
        
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'message' => 'Data tidak lengkap'
        ]);
    }
}
