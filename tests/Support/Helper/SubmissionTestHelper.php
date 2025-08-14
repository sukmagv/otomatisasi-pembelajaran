<?php

namespace Tests\Support\Helper;

use Tests\Support\ApiTester;

class SubmissionTestHelper extends \Codeception\Module
{
    public function resolveUserId(ApiTester $I): int
    {
        // Ambil path dari config jika valid dan merupakan get.php
        $configPath = codecept_root_dir() . 'tests/test-config.json';
        $config = json_decode(file_get_contents($configPath), true);
        $rawPath = $config['testFile'] ?? '';
        $normalizedPath = str_replace('\\', '/', $rawPath);
        $getPath = basename($normalizedPath) === 'get.php' ? $normalizedPath : '/get.php';

        $filePath = codecept_output_dir() . 'test_user_id.json';
        $id = null;

        // 1. Cek file
        if (file_exists($filePath)) {
            $user = json_decode(file_get_contents($filePath), true);
            if (!empty($user['id'])) {
                $id = $user['id'];
            }
        }

        // 2. Cek by name/email
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

        // 3. Cek last user
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
            $I->fail("Gagal mendapatkan user ID.");
        }

        file_put_contents($filePath, json_encode(['id' => $id], JSON_PRETTY_PRINT));
        return $id;
    }

}

