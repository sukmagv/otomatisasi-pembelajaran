<?php

use Tests\Support\ApiTester;
use Tests\Support\Helper\SubmissionTestHelper;

class PutCest
{
    protected static string $phpBinary = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';
    protected static string $putTestFilePath;
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
            throw new \RuntimeException("File put.php tidak ditemukan: {$realPath}");
        }

        self::$putTestFilePath = $realPath;
    }

    protected function runPutWithIdAndData(int $id, array $data): array
    {
        $overridePath = SubmissionTestHelper::generateAutoPrependFile(
            ['id' => $id, 'data' => $data],
            'override_put_data.php',
            '$id,$data'
        );

        $command = escapeshellcmd(self::$phpBinary)
            . ' -d auto_prepend_file=' . escapeshellarg($overridePath)
            . ' ' . escapeshellarg(self::$putTestFilePath);

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

    public function testSuccessfulUpdate(ApiTester $I)
    {
        // Ambil ID baru yang belum ada di database
        $id = $this->getNextAvailableId();

        // Insert user original (sebelum diupdate)
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        $stmt = $conn->prepare("INSERT INTO users (id, username, name, email, prodi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $id, $username, $name, $email, $prodi);

        $username = 'putuser_original';
        $name     = 'Put User';
        $email    = 'put@example.com';
        $prodi    = 'Teknik Mesin';
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Data yang akan digunakan untuk update
        $data = [
            'username' => 'putuser_updated_' . uniqid(),
            'name'     => 'Put User Updated',
            'email'    => 'put.updated.' . uniqid() . '@example.com',
            'prodi'    => 'Teknik Komputer'
        ];

        $result = $this->runPutWithIdAndData($id, $data);
        $this->assertJsonResponse($I, $result, 200, 'success');

        self::$usersToDelete[] = $data;
    }

    public function testUpdateWithMissingFields(ApiTester $I)
    {
        $data = [
            'username' => 'putuser_missing'
        ];

        $result = $this->runPutWithIdAndData(1, $data);
        $this->assertJsonResponse($I, $result, 400, 'error');
    }

    public function testUpdateNonexistentId(ApiTester $I)
    {
        $id = $this->getNextAvailableId(); // pastikan ID ini belum ada
        $data = [
            'username' => 'user_nexist',
            'name'     => 'Does Not Exist',
            'email'    => 'usernx@example.com',
            'prodi'    => 'Teknik Kimia'
        ];

        $result = $this->runPutWithIdAndData($id, $data);
        $this->assertJsonResponse($I, $result, 404, 'error');

        // tetap tambahkan ke cleanup untuk jaga-jaga kalau test gagal dan user terbuat
        self::$usersToDelete[] = $data;
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
