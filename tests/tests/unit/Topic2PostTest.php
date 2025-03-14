<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1PostTest extends Unit
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
        $this->assertMatchesRegularExpression('/require\s+"data.php"/', $this->code, "Harus ada 'require \"data.php\"' untuk mengambil fungsi getData dan saveData");
    }

    public function testGetDataFunctionCall()
    {
        $this->assertMatchesRegularExpression('/\$data\s*=\s*getData\s*\(\s*\)/', $this->code, "Harus ada pemanggilan getData untuk mengambil data");
    }

    public function testJsonDecodeUsage()
    {
        $this->assertMatchesRegularExpression('/\$input\s*=\s*json_decode\s*\(\s*file_get_contents\s*\(\s*"php:\/\/input"\s*\)/', $this->code, "Harus ada json_decode(file_get_contents('php://input')) untuk mengambil input JSON");
    }

    public function testInputValidationCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!isset\(\s*\$input\s*\[\s*[\'"]id[\'"]\s*\]\s*\)\s*\|\|\s*!isset\(\s*\$input\s*\[\s*[\'"]name[\'"]\s*\]\s*\)\s*\)/', $this->code, "Harus ada validasi input untuk memastikan 'id' dan 'name' disertakan");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*400\s*\)/', $this->code, "Harus ada http_response_code(400) jika input tidak valid");
    }

    public function testItemAlreadyExistsCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$data\s*\[\s*\$input\s*\[\s*[\'"]id[\'"]\s*\]\s*\]\s*\)\s*\)/', $this->code, "Harus ada pengecekan apakah ID sudah ada dalam data");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*400\s*\)/', $this->code, "Harus ada http_response_code(400) jika item sudah ada");
    }

    public function testSaveDataFunctionCall()
    {
        $this->assertMatchesRegularExpression('/saveData\s*\(\s*\$data\s*\)/', $this->code, "Harus ada pemanggilan saveData setelah menambahkan item baru");
    }

    public function testHttpResponse201()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*201\s*\)/', $this->code, "Harus ada http_response_code(201) jika item berhasil ditambahkan");
    }

    public function testJsonResponseOnSuccess()
    {
        $this->assertMatchesRegularExpression('/echo\s+json_encode\s*\(\s*\[\s*"status"\s*=>\s*201\s*,\s*"message"\s*=>\s*"Item Created"/', $this->code, "Harus ada JSON response yang mengindikasikan sukses dengan status 201 dan pesan 'Item Created'");
    }

    public function testCodeExecutionOrder()
    {
        $headerPos = strpos($this->code, 'header("Content-Type: application/json");');
        $requirePos = strpos($this->code, 'require "data.php";');
        $getDataPos = strpos($this->code, '$data = getData();');
        $jsonDecodePos = strpos($this->code, '$input = json_decode(file_get_contents("php://input"), true);');
        $validationPos = strpos($this->code, 'if (!isset($input[\'id\']) || !isset($input[\'name\']))');
        $existsCheckPos = strpos($this->code, 'if (isset($data[$input[\'id\']]))');
        $saveDataPos = strpos($this->code, 'saveData($data);');
        $response201Pos = strpos($this->code, 'http_response_code(201);');

        // Pastikan urutan eksekusi sesuai dengan logika bisnis
        $this->assertNotFalse($headerPos, "Header harus ada");
        $this->assertNotFalse($requirePos, "Require data.php harus ada");
        $this->assertNotFalse($getDataPos, "Pemanggilan getData harus ada");
        $this->assertNotFalse($jsonDecodePos, "Pengambilan input JSON harus ada");
        $this->assertNotFalse($validationPos, "Validasi input harus ada");
        $this->assertNotFalse($existsCheckPos, "Pengecekan item sudah ada harus ada");
        $this->assertNotFalse($saveDataPos, "Penyimpanan data baru harus ada");
        $this->assertNotFalse($response201Pos, "Respon sukses 201 harus ada");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "Header harus dieksekusi sebelum require");
        $this->assertTrue($requirePos < $getDataPos, "Require harus sebelum pemanggilan getData");
        $this->assertTrue($getDataPos < $jsonDecodePos, "getData harus sebelum pengambilan input JSON");
        $this->assertTrue($jsonDecodePos < $validationPos, "Pengambilan input JSON harus sebelum validasi");
        $this->assertTrue($validationPos < $existsCheckPos, "Validasi harus sebelum pengecekan apakah item sudah ada");
        $this->assertTrue($existsCheckPos < $saveDataPos, "Pengecekan item sudah ada harus sebelum saveData");
        $this->assertTrue($saveDataPos < $response201Pos, "saveData harus sebelum mengirimkan respon 201");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('header("Content-Type: application/json");', $this->code, "Header JSON harus ditulis dengan benar");
        $this->assertStringContainsString('require "data.php";', $this->code, "Require file data.php harus ada");
        $this->assertStringContainsString('function getData()', $this->code, "Fungsi getData harus ada");
        $this->assertStringContainsString('function saveData(', $this->code, "Fungsi saveData harus ada");
        $this->assertStringContainsString('json_decode', $this->code, "Harus ada penggunaan json_decode");
        $this->assertStringContainsString('json_encode', $this->code, "Harus ada penggunaan json_encode");
    }
}
