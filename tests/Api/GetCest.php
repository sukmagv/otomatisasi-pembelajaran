<?php

use Tests\Support\ApiTester;
use Tests\Support\Helper\SubmissionTestHelper;

class GetCest
{
    protected static string $phpBinary = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';

    // nullable string, diinisialisasi null agar tidak error saat akses pertama kali
    protected static ?string $getFilePath = null;

    public function _before(ApiTester $I)
    {
        if (!self::$getFilePath) {
            $configFile = codecept_root_dir() . 'tests/test-config.json';
            if (!file_exists($configFile)) {
                throw new \RuntimeException("Config file tidak ditemukan: {$configFile}");
            }

            $config = json_decode(file_get_contents($configFile), true);
            $realPath = $config['testFile'] ?? null;
            if (!$realPath || !file_exists($realPath)) {
                throw new \RuntimeException("File get.php tidak ditemukan: {$realPath}");
            }

            self::$getFilePath = $realPath;
        }
    }

    protected function runGetWithId(?int $id): array
    {
        // Generate override file yang set variabel $id (misal: $id = 5;)
        $overridePath = SubmissionTestHelper::generateAutoPrependFile(['id' => $id], 'override_get_id.php', '$id');

        $command = escapeshellcmd(self::$phpBinary)
            . ' -d auto_prepend_file=' . escapeshellarg($overridePath)
            . ' ' . escapeshellarg(self::$getFilePath);

        $output = shell_exec($command);

        SubmissionTestHelper::cleanupOverrideFile();

        $decoded = json_decode($output, true);
        $httpCode = 200;

        if (!is_array($decoded)) {
            $httpCode = 500;
        } elseif (($decoded['status'] ?? '') === 'error') {
            if (str_contains($decoded['message'] ?? '', 'tidak ditemukan')) {
                $httpCode = 404;
            } else {
                $httpCode = 400;
            }
        }

        return ['http_code' => $httpCode, 'body' => $output];
    }

    protected function assertJsonResponse(ApiTester $I, array $result, int $expectedCode, string $mustContain)
    {
        $output = $result['body'];
        $code = $result['http_code'];

        $I->comment("Output: $output");
        $I->assertEquals($expectedCode, $code, "Expected HTTP $expectedCode, got $code");
        $I->assertStringContainsString($mustContain, $output);
    }

    public function testGetAllUsers(ApiTester $I)
    {
        // id null untuk get all
        $result = $this->runGetWithId(null);
        $this->assertJsonResponse($I, $result, 200, 'success');
    }

    public function testGetUserByValidId(ApiTester $I)
    {
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        $result = $conn->query("SELECT id FROM users LIMIT 1");
        $row = $result->fetch_assoc();
        $conn->close();

        if (!$row) {
            $I->fail("Tidak ada data user yang tersedia untuk diuji. Buat data user terlebih dahulu.");
            return;
        }

        $validId = (int) $row['id'];
        $result = $this->runGetWithId($validId);
        $this->assertJsonResponse($I, $result, 200, 'success');
    }

    public function testGetUserByInvalidId(ApiTester $I)
    {
        $result = $this->runGetWithId(9999999);
        $this->assertJsonResponse($I, $result, 404, 'error');
    }
}
