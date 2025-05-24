<?php

use Tests\Support\ApiTester;
use Tests\Support\Helper\SubmissionTestHelper;

class PostCest
{
    protected static $postFilePath;
    protected static $usersToDelete = [];

    public function _before(ApiTester $I)
    {
        $users = ['testuser_complete', 'testuser_duplicate', 'testuser_incomplete'];
        foreach ($users as $username) {
            $I->deleteUserByUsername($username);
        }

        if (!self::$postFilePath) {
            $configFile = codecept_root_dir() . 'tests/test-config.json';
            if (!file_exists($configFile)) {
                throw new \RuntimeException("Config file tidak ditemukan: {$configFile}");
            }

            $config = json_decode(file_get_contents($configFile), true);
            $realPath = $config['testFile'] ?? null;

            if (!$realPath || !file_exists($realPath)) {
                throw new \RuntimeException("File post.php tidak ditemukan: {$realPath}");
            }

            self::$postFilePath = $realPath;
        }
    }

    protected function runPostWithInjectedData(array $data): array
    {
        $overridePath = SubmissionTestHelper::generateAutoPrependFile($data);
        $phpBinary = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';

        $command = escapeshellcmd($phpBinary)
            . ' -d auto_prepend_file=' . escapeshellarg($overridePath)
            . ' ' . escapeshellarg(self::$postFilePath);

        $output = shell_exec($command);
        SubmissionTestHelper::cleanupOverrideFile();

        $decoded = json_decode($output, true);
        $httpCode = 200;

        if (!is_array($decoded)) {
            $httpCode = 500;
        } elseif ($decoded['status'] === 'error') {
            if (str_contains($decoded['message'], 'terdaftar') || str_contains($decoded['message'], 'wajib')) {
                $httpCode = 400;
            } else {
                $httpCode = 500;
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

    public function testCompleteInput(ApiTester $I)
    {
        $data = [
            'username' => 'testuser_complete',
            'name' => 'Test User Complete',
            'email' => 'test_complete@example.com',
            'prodi' => 'Informatika'
        ];

        $result = $this->runPostWithInjectedData($data);
        $this->assertJsonResponse($I, $result, 200, 'success');
        self::$usersToDelete[] = $data;
    }

    public function testDuplicateInput(ApiTester $I)
    {
        $data = [
            'username' => 'testuser_duplicate',
            'name' => 'Test User Duplicate',
            'email' => 'test_duplicate@example.com',
            'prodi' => 'Informatika'
        ];

        $result1 = $this->runPostWithInjectedData($data);
        $this->assertJsonResponse($I, $result1, 200, 'success');

        $result2 = $this->runPostWithInjectedData($data);
        $this->assertJsonResponse($I, $result2, 400, 'error');

        self::$usersToDelete[] = $data;
    }

    public function testIncompleteInput(ApiTester $I)
    {
        $data = [
            'username' => 'testuser_incomplete',
            'email' => 'test_incomplete@example.com'
        ];

        $result = $this->runPostWithInjectedData($data);
        $this->assertJsonResponse($I, $result, 400, 'error');
    }

    public function _after(ApiTester $I)
    {
        foreach (self::$usersToDelete as $user) {
            $I->cleanupInsertedUser($user['username'], $user['email']);
        }

        self::$usersToDelete = [];
        SubmissionTestHelper::cleanupOverrideFile();
    }
}
