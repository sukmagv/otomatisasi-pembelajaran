<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../temp_logic/post.php';

class UserCreateTest extends TestCase
{
    protected $conn;

    protected function setUp(): void
    {
        $this->conn = include __DIR__ . '/../prepend/db_override.php';
    }

    public function testValidNameAndEmail()
    {
        $input = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ];

        $result = runTestLogic($this->conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('User berhasil ditambahkan', $result['message']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test User', $result['data']['name']);
        $this->assertEquals('testuser@example.com', $result['data']['email']);
    }

    public function testValidNameMissingEmail()
    {
        $input = [
            'name' => 'Test User',
            // email tidak ada
        ];

        $result = runTestLogic($this->conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Data tidak lengkap', $result['message']);
    }

    public function testMissingNameValidEmail()
    {
        $input = [
            // name tidak ada
            'email' => 'testuser@example.com',
        ];

        $result = runTestLogic($this->conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Data tidak lengkap', $result['message']);
    }

    public function testMissingNameAndEmail()
    {
        $input = []; // name dan email tidak dikirim

        $result = runTestLogic($this->conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Data tidak lengkap', $result['message']);
    }
}
