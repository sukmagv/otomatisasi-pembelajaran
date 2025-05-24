<?php

use Tests\Support\ApiTester;
use Tests\Support\Helper\SubmissionTestHelper;

class DeleteCest
{
    protected static string $phpBinary = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';
    protected static string $deleteTestFilePath;
    protected static $usersToDelete = [];

    public function _before(ApiTester $I)
    {
        $configFile = codecept_root_dir() . 'tests/test-config.json';
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Config file tidak ditemukan: {$configFile}");
        }

        $config = json_decode(file_get_contents($configFile), true);
        $realPath = $config['testFile'] ?? null;
        if (!$realPath || !file_exists($realPath)) {
            throw new \RuntimeException("File delete.php tidak ditemukan: {$realPath}");
        }

        self::$deleteTestFilePath = $realPath;
    }

    protected function runDeleteWithId(?int $id): array
    {
        $overridePath = SubmissionTestHelper::generateAutoPrependFile(['id' => $id], 'override_delete_id.php', '$id');

        $command = escapeshellcmd(self::$phpBinary)
            . ' -d auto_prepend_file=' . escapeshellarg($overridePath)
            . ' ' . escapeshellarg(self::$deleteTestFilePath);

        $output = shell_exec($command);
        SubmissionTestHelper::cleanupOverrideFile();

        $decoded = json_decode($output, true);
        $httpCode = 200;

        if (!is_array($decoded)) {
            $httpCode = 500;
        } elseif (($decoded['status'] ?? '') === 'error') {
            $httpCode = str_contains($decoded['message'], 'wajib') ? 400 : 404;
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

    protected function getNextAvailableId(): int
    {
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        $result = $conn->query("SELECT MAX(id) as max_id FROM users");
        $row = $result->fetch_assoc();
        $conn->close();

        return ($row['max_id'] ?? 0) + 1;
    }

    public function testDeleteExistingUser(ApiTester $I)
    {
        // Dapatkan ID baru yang belum ada
        $id = $this->getNextAvailableId();

        // Data user untuk disisipkan
        $username = 'deleteuser_' . uniqid();
        $email    = 'delete_' . uniqid() . '@example.com';

        // Insert user
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        $stmt = $conn->prepare("INSERT INTO users (id, username, name, email, prodi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $id, $username, $name, $email, $prodi);

        $name  = 'Delete User';
        $prodi = 'Teknik Kimia';
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Jalankan delete
        $result = $this->runDeleteWithId($id);
        $this->assertJsonResponse($I, $result, 200, 'success');

        self::$usersToDelete[] = [
            'username' => $username,
            'email'    => $email
        ];
    }

    public function testDeleteNonexistentUser(ApiTester $I)
    {
        $id = $this->getNextAvailableId();

        $result = $this->runDeleteWithId($id);
        $this->assertJsonResponse($I, $result, 404, 'error');
    }

    public function testDeleteWithoutId(ApiTester $I)
    {
        $result = $this->runDeleteWithId(null);
        $this->assertJsonResponse($I, $result, 404, 'error');
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
