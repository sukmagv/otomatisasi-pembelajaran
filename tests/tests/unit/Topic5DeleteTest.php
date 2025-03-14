<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1DeleteTest extends Unit
{
    private $filePath;
    private $code;

    protected function _before()
    {
        // Ambil path file dari environment variable atau CLI
        $this->filePath = $_SERVER['testFile'] ?? $_ENV['testFile'] ?? null;

        // Pastikan path file diberikan
        $this->assertNotEmpty($this->filePath, "Path file harus diberikan sebagai argument --testFile");

        // Pastikan file ada
        $this->assertFileExists($this->filePath, "File yang diuji harus ada");

        // Ambil isi file sebagai string
        $this->code = file_get_contents($this->filePath);
    }

    public function testValidPHPCode()
    {
        $tokens = token_get_all($this->code);
        $this->assertNotEmpty($tokens, "Kode PHP harus valid tanpa error sintaks");
    }

    public function testHeaderJsonOutput()
    {
        $this->assertMatchesRegularExpression('/header\s*\(\s*"Content-Type:\s*application\/json"\s*\)/', $this->code, "Header harus mengatur output sebagai JSON");
    }

    public function testRequireDataFile()
    {
        $this->assertMatchesRegularExpression('/require\s+"data.php"/', $this->code, "Harus ada 'require \"data.php\"' untuk mengambil fungsi getData");
    }

    public function testGetDataFunctionCall()
    {
        $this->assertMatchesRegularExpression('/\$data\s*=\s*getData\s*\(\s*\)/', $this->code, "Harus ada pemanggilan getData untuk mengambil data");
    }

    public function testGetIdRetrieval()
    {
        $this->assertMatchesRegularExpression('/\$id\s*=\s*isset\s*\(\s*\$_GET\s*\[\s*[\'"]id[\'"]\s*\]\s*\)\s*\?/', $this->code, "Harus ada pengecekan isset pada \$_GET['id']");
    }

    public function testReturn404IfIdNotFound()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!?\$id\s*\|\|\s*!isset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)\s*\)/', $this->code, "Harus ada pengecekan jika ID tidak ditemukan");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*404\s*\)/', $this->code, "Respon harus 404 jika ID tidak ditemukan");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*404\s*,\s*"error"\s*=>\s*"Item Not Found"\s*\]/', $this->code, "Harus ada pesan error 'Item Not Found' jika ID tidak ditemukan");
    }

    public function testDeleteItemFromData()
    {
        $this->assertMatchesRegularExpression('/unset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)/', $this->code, "Harus ada perintah unset untuk menghapus data berdasarkan ID");
    }

    public function testSaveDataAfterDeletion()
    {
        $this->assertMatchesRegularExpression('/saveData\s*\(\s*\$data\s*\)/', $this->code, "Harus ada pemanggilan saveData setelah penghapusan");
    }

    public function testReturn200IfDeletedSuccessfully()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Respon harus 200 jika penghapusan berhasil");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"message"\s*=>\s*"Item Deleted"\s*\]\s*\)/', $this->code, "Harus ada pesan 'Item Deleted' setelah item berhasil dihapus");
    }

    public function testCodeExecutionOrder()
    {
        $headerPos = strpos($this->code, 'header("Content-Type: application/json");');
        $requirePos = strpos($this->code, 'require "data.php";');
        $getDataPos = strpos($this->code, '$data = getData();');
        $getIdPos = strpos($this->code, '$id = isset($_GET[\'id\']) ? $_GET[\'id\'] : null;');
        $idCheckPos = strpos($this->code, 'if (!$id || !isset($data[$id]))');
        $deletePos = strpos($this->code, 'unset($data[$id]);');
        $savePos = strpos($this->code, 'saveData($data);');
        $response200Pos = strpos($this->code, 'http_response_code(200);');

        // Pastikan urutan eksekusi sesuai dengan logika bisnis
        $this->assertNotFalse($headerPos, "Header harus ada");
        $this->assertNotFalse($requirePos, "Require data.php harus ada");
        $this->assertNotFalse($getDataPos, "Pemanggilan getData harus ada");
        $this->assertNotFalse($getIdPos, "Pengambilan ID harus ada");
        $this->assertNotFalse($idCheckPos, "Pengecekan ID harus ada");
        $this->assertNotFalse($deletePos, "Perintah penghapusan item harus ada");
        $this->assertNotFalse($savePos, "Penyimpanan data harus ada");
        $this->assertNotFalse($response200Pos, "Respon 200 harus ada setelah penghapusan");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "Header harus sebelum require");
        $this->assertTrue($requirePos < $getDataPos, "Require harus sebelum pemanggilan getData");
        $this->assertTrue($getDataPos < $getIdPos, "getData harus sebelum pengambilan ID");
        $this->assertTrue($getIdPos < $idCheckPos, "Pengambilan ID harus sebelum pengecekan ID");
        $this->assertTrue($idCheckPos < $deletePos, "Pengecekan ID harus sebelum penghapusan");
        $this->assertTrue($deletePos < $savePos, "Penghapusan harus sebelum penyimpanan");
        $this->assertTrue($savePos < $response200Pos, "Penyimpanan harus sebelum respon 200");
    }
}
