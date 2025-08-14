<?php

use PHPUnit\Framework\TestCase;

class UserCreateTest extends TestCase
{
    protected $path;
    protected $username;

    protected function setUp(): void
    {
        $jsonPath = __DIR__ . '/../test-config.json';
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("test-config.json tidak ditemukan di: $jsonPath");
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        $rawPath = $data['testFile'];
        $this->username = $data['username'] ?? 'default_user';

        $fullPath = __DIR__ . '/../../public/' . str_replace('\\', '/', $rawPath);
        echo "\nChecking path: " . $fullPath . "\n";

        $this->path = realpath($fullPath);

        if (!$this->path) {
            throw new \RuntimeException("File path not found: " . $fullPath);
        }
    }
    
    public function testValidPostCreatesUser()
    {
        $_POST['name'] = 'Test User';
        $_POST['email'] = 'testuser_' . uniqid() . '@example.com';

        ob_start();
        include $this->path;
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('success', $response['status'] ?? '');
        $this->assertEquals('User berhasil ditambahkan', $response['message'] ?? '');
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals($_POST['name'], $response['data']['name']);
        $this->assertEquals($_POST['email'], $response['data']['email']);
    }

    public function testMissingFieldsReturnsError()
    {
        $_POST = []; // Kosongkan input

        ob_start();
        include $this->path;
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status'] ?? '');
        $this->assertEquals('Data tidak lengkap', $response['message'] ?? '');
    }

    public function testInvalidPostReturnsError()
    {
        $_POST['name'] = ''; // Nama kosong
        $_POST['email'] = 'invalid-email'; // Email tidak valid

        ob_start();
        include $this->path;
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status'] ?? '');
        $this->assertEquals('Data tidak lengkap', $response['message'] ?? '');
    }
}