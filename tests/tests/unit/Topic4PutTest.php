<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1UpdateTest extends Unit
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

    public function testInputJsonParsing()
    {
        $this->assertMatchesRegularExpression('/\$input\s*=\s*json_decode\s*\(\s*file_get_contents\s*\(\s*"php:\/\/input"\s*\)/', $this->code, "Harus ada parsing JSON dari input");
    }

    public function testReturn404IfIdNotFound()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!?\$id\s*\|\|\s*!isset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)\s*\)/', $this->code, "Harus ada pengecekan jika ID tidak ditemukan");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*404\s*\)/', $this->code, "Respon harus 404 jika ID tidak ditemukan");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*404\s*,\s*"error"\s*=>\s*"Item Not Found"\s*\]/', $this->code, "Harus ada pesan error 'Item Not Found' jika ID tidak ditemukan");
    }

    public function testReturn400IfNameIsMissing()
    {
        $this->assertMatchesRegularExpression('/if\s*\(!isset\s*\(\s*\$input\s*\[\s*[\'"]name[\'"]\s*\]\s*\)\s*\)/', $this->code, "Harus ada pengecekan apakah input name tersedia");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*400\s*\)/', $this->code, "Respon harus 400 jika name tidak tersedia");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*400\s*,\s*"error"\s*=>\s*"Invalid Input"\s*\]/', $this->code, "Harus ada pesan error 'Invalid Input' jika name tidak diberikan");
    }

    public function testUpdateItemAndSave()
    {
        $this->assertMatchesRegularExpression('/\$data\s*\[\s*\$id\s*\]\s*\[\s*[\'"]name[\'"]\s*\]\s*=\s*\$input\s*\[\s*[\'"]name[\'"]\s*\]\s*;/', $this->code, "Harus ada perintah untuk memperbarui name pada data");
        $this->assertMatchesRegularExpression('/saveData\s*\(\s*\$data\s*\)/', $this->code, "Harus ada pemanggilan saveData setelah update");
    }

    public function testReturn200IfUpdatedSuccessfully()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Respon harus 200 jika update berhasil");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"message"\s*=>\s*"Item Updated"\s*,\s*"produk"\s*=>\s*\$data\s*\[\s*\$id\s*\]\s*\]\s*\)/', $this->code, "Harus ada pesan 'Item Updated' setelah pembaruan");
    }

    public function testCodeExecutionOrder()
    {
        $headerPos = strpos($this->code, 'header("Content-Type: application/json");');
        $requirePos = strpos($this->code, 'require "data.php";');
        $getDataPos = strpos($this->code, '$data = getData();');
        $getIdPos = strpos($this->code, '$id = isset($_GET[\'id\']) ? $_GET[\'id\'] : null;');
        $inputParsePos = strpos($this->code, '$input = json_decode(file_get_contents("php://input"), true);');
        $idCheckPos = strpos($this->code, 'if (!$id || !isset($data[$id]))');
        $nameCheckPos = strpos($this->code, 'if (!isset($input[\'name\']))');
        $updatePos = strpos($this->code, '$data[$id][\'name\'] = $input[\'name\'];');
        $savePos = strpos($this->code, 'saveData($data);');
        $response200Pos = strpos($this->code, 'http_response_code(200);');

        // Pastikan urutan eksekusi sesuai dengan logika bisnis
        $this->assertNotFalse($headerPos, "Header harus ada");
        $this->assertNotFalse($requirePos, "Require data.php harus ada");
        $this->assertNotFalse($getDataPos, "Pemanggilan getData harus ada");
        $this->assertNotFalse($getIdPos, "Pengambilan ID harus ada");
        $this->assertNotFalse($inputParsePos, "Parsing JSON input harus ada");
        $this->assertNotFalse($idCheckPos, "Pengecekan ID harus ada");
        $this->assertNotFalse($nameCheckPos, "Pengecekan name harus ada");
        $this->assertNotFalse($updatePos, "Update item harus ada");
        $this->assertNotFalse($savePos, "Penyimpanan data harus ada");
        $this->assertNotFalse($response200Pos, "Respon 200 harus ada setelah update");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "Header harus sebelum require");
        $this->assertTrue($requirePos < $getDataPos, "Require harus sebelum pemanggilan getData");
        $this->assertTrue($getDataPos < $getIdPos, "getData harus sebelum pengambilan ID");
        $this->assertTrue($getIdPos < $inputParsePos, "Pengambilan ID harus sebelum parsing input");
        $this->assertTrue($inputParsePos < $idCheckPos, "Parsing input harus sebelum pengecekan ID");
        $this->assertTrue($idCheckPos < $nameCheckPos, "Pengecekan ID harus sebelum pengecekan name");
        $this->assertTrue($nameCheckPos < $updatePos, "Pengecekan name harus sebelum update data");
        $this->assertTrue($updatePos < $savePos, "Update data harus sebelum penyimpanan");
        $this->assertTrue($savePos < $response200Pos, "Penyimpanan harus sebelum respon 200");
    }
}
