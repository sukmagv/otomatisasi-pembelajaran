<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../temp_logic/get.php';

class UserGetTest extends TestCase
{
    protected function getConnection()
    {
        return include __DIR__ . '/../prepend/db_override.php';
    }

    protected function createDummyUser($conn)
    {
        $name = 'Dummy User';
        $email = 'dummy@example.com';
        $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();

        return $conn->insert_id; // Dapatkan ID yang baru dibuat
    }

    // R1: ID diberikan dan valid (data ditemukan)
    public function testGetUserByValidId()
    {
        $conn = $this->getConnection();
        $id = $this->createDummyUser($conn);

        // Ganti 1 dengan ID valid sesuai isi test database
        $input = ['id' => $id];

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('id', $result['data']);
    }

    // R2: ID diberikan tapi tidak ditemukan di database
    public function testGetUserByInvalidId()
    {
        $conn = $this->getConnection();

        // ID 99999 diasumsikan tidak ada
        $input = ['id' => 999999999];

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User tidak ditemukan', $result['message']);
    }

    // R3: ID tidak diberikan → ambil semua data
    public function testGetAllUsersWithoutId()
    {
        $conn = $this->getConnection();

        $input = []; // Tidak ada ID

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }
}
