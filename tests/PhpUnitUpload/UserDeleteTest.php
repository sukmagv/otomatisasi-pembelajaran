<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../temp_logic/delete.php';

class UserDeleteTest extends TestCase
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

    // R1: ID diberikan dan valid
    public function testDeleteUserSuccess()
    {
        $conn = $this->getConnection();
        $id = $this->createDummyUser($conn);

        $input = ['id' => $id]; // Ganti dengan ID valid yang ada di DB

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('User berhasil dihapus', $result['message']);
    }

    // R2: ID tidak diberikan
    public function testDeleteWithoutId()
    {
        $conn = $this->getConnection();

        $result = runTestLogic($conn, []); // Tidak ada parameter

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('ID user harus diisi', $result['message']);
    }

    // R3: ID diberikan tapi tidak ditemukan
    public function testDeleteWithInvalidId()
    {
        $conn = $this->getConnection();

        $input = ['id' => 99999]; // ID tidak ada di DB

        $result = runTestLogic($conn, $input);

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User tidak ditemukan', $result['message']);
    }
}
