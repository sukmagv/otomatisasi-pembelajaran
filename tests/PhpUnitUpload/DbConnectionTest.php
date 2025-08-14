<?php

use PHPUnit\Framework\TestCase;

define('PHPUNIT_RUNNING', true);

class DbConnectionTest extends TestCase
{
    protected $conn;
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

        $dbPath = dirname($this->path) . '/db.php';
        if (!file_exists($dbPath)) {
            throw new \RuntimeException("db.php not found in directory: " . dirname($this->path));
        }

        $code = file_get_contents($dbPath);

        // Lakukan penggantian variabel koneksi
        $replaceMap = [
            '/\$host\s*=\s*["\'].*?["\'];/'     => '$host = "localhost";',
            '/\$user\s*=\s*["\'].*?["\'];/'     => '$user = "root";',
            '/\$password\s*=\s*["\'].*?["\'];/' => '$password = "";',
            '/\$dbname\s*=\s*["\'].*?["\'];/'   => '$dbname = "test_db";',
        ];

        foreach ($replaceMap as $pattern => $replacement) {
            $code = preg_replace($pattern, $replacement, $code);
        }

        // Jalankan hasil modifikasi
        $conn = (function () use ($code) {
            ob_start();
            eval("?>$code");
            ob_end_clean();
            return $conn ?? null;
        })();

        $this->conn = $conn;
    }

    public function testConnectionIsSuccessful()
    {
        $this->assertNotNull($this->conn, "Connection object is null");
        $this->assertInstanceOf(mysqli::class, $this->conn, "Not a mysqli object");

        $this->assertSame(0, $this->conn->connect_errno, "Failed to connect to database: " . $this->conn->connect_error);
    }

    protected function tearDown(): void
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
