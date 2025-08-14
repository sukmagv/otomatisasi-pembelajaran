<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../temp_logic/put.php';

class UserUpdateTest extends TestCase
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

    // R1: ID valid dan field update ada
    public function testUpdateUserName()
    {
        $conn = $this->getConnection();
        $id = $this->createDummyUser($conn);

        $input = [
            'id' => $id,
            'name' => 'Updated Name'
        ];

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('User berhasil diperbarui', $result['message']);
    }

    // R2: ID valid, ada dua field yang diupdate
    public function testUpdateMultipleFields()
    {
        $conn = $this->getConnection();
        $id = $this->createDummyUser($conn);

        $input = [
            'id' => $id,
            'name' => 'Another Name',
            'email' => 'updated@example.com'
        ];

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
    }

    // R3: ID tidak diberikan
    public function testUpdateWithoutId()
    {
        $conn = $this->getConnection();

        $input = [
            'name' => 'No ID'
        ];

        $result = runTestLogic($conn, $input);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('ID dan minimal satu field update harus diisi', $result['message']);
    }

    // R4: ID valid tapi tidak ada field yang diupdate
    public function testUpdateWithNoFields()
    {
        $conn = $this->getConnection();
        $id = $this->createDummyUser($conn);

        $input = [
            'id' => $id // Valid ID, tapi tidak ada data baru
        ];

        $result = runTestLogic($conn, $input);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('ID dan minimal satu field update harus diisi', $result['message']);
    }

    // R5: ID tidak valid tapi field update ada
    public function testUpdateWithInvalidId()
    {
        $conn = $this->getConnection();

        $input = [
            'id' => 99999, // Diasumsikan tidak ada di DB
            'name' => 'Ghost Update'
        ];

        $result = runTestLogic($conn, $input);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User tidak ditemukan atau data sama', $result['message']);
    }
}
