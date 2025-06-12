<?php

use Tests\Support\UnitTester;

class DbConnectTest extends \Codeception\Test\Unit
{
    public function testDatabaseConnection()
    {
        try {
            $conn = include __DIR__ . '/../db-test-wrapper.php';
        } catch (Exception $e) {
            $this->fail("Gagal mendapatkan koneksi database: " . $e->getMessage());
            return;
        }

        $this->assertArrayHasKey('includedDbFile', $GLOBALS, 'File DB yang digunakan tidak tercatat');
        $this->assertStringEndsWith('db.php', $GLOBALS['includedDbFile'], 'File yang digunakan bukan db.php');
        $this->assertFileExists($GLOBALS['includedDbFile'], 'File db.php tidak ditemukan');

        $this->assertInstanceOf(mysqli::class, $conn);

        $result = $conn->query("SHOW TABLES");

        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, $result->num_rows);

        $conn->close();
    }
}
