<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1GetTest extends Unit
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

    public function testReturnAllDataWhenIdIsNull()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*is_null\s*\(\s*\$id\s*\)\s*\)/', $this->code, "Harus ada pengecekan apakah ID null");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Respon harus 200 jika ID null");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"data"\s*=>\s*array_values\s*\(\s*\$data\s*\)/', $this->code, "Harus mengembalikan seluruh data jika ID null");
    }

    public function testReturnSingleItemIfExists()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)\s*\)/', $this->code, "Harus ada pengecekan apakah ID ada di data");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Respon harus 200 jika item ditemukan");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"data"\s*=>\s*\$data\s*\[\s*\$id\s*\]\s*\]/', $this->code, "Harus mengembalikan data jika ID ditemukan");
    }

    public function testReturn404IfItemNotFound()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*404\s*\)/', $this->code, "Respon harus 404 jika item tidak ditemukan");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*404\s*,\s*"error"\s*=>\s*"Item Not Found"\s*\]/', $this->code, "Harus ada pesan error 'Item Not Found' jika ID tidak ditemukan");
    }

    public function testCodeExecutionOrder()
    {
        $headerPos = strpos($this->code, 'header("Content-Type: application/json");');
        $requirePos = strpos($this->code, 'require "data.php";');
        $getDataPos = strpos($this->code, '$data = getData();');
        $getIdPos = strpos($this->code, '$id = isset($_GET[\'id\']) ? $_GET[\'id\'] : null;');
        $nullCheckPos = strpos($this->code, 'if (is_null($id))');
        $existsCheckPos = strpos($this->code, 'if (isset($data[$id]))');
        $response404Pos = strpos($this->code, 'http_response_code(404);');

        // Pastikan urutan eksekusi sesuai dengan logika bisnis
        $this->assertNotFalse($headerPos, "Header harus ada");
        $this->assertNotFalse($requirePos, "Require data.php harus ada");
        $this->assertNotFalse($getDataPos, "Pemanggilan getData harus ada");
        $this->assertNotFalse($getIdPos, "Pengambilan ID harus ada");
        $this->assertNotFalse($nullCheckPos, "Pengecekan ID null harus ada");
        $this->assertNotFalse($existsCheckPos, "Pengecekan apakah ID ada harus ada");
        $this->assertNotFalse($response404Pos, "Respon 404 harus ada jika item tidak ditemukan");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "Header harus sebelum require");
        $this->assertTrue($requirePos < $getDataPos, "Require harus sebelum pemanggilan getData");
        $this->assertTrue($getDataPos < $getIdPos, "getData harus sebelum pengambilan ID");
        $this->assertTrue($getIdPos < $nullCheckPos, "Pengambilan ID harus sebelum pengecekan null");
        $this->assertTrue($nullCheckPos < $existsCheckPos, "Pengecekan null harus sebelum pengecekan apakah item ada");
        $this->assertTrue($existsCheckPos < $response404Pos, "Pengecekan apakah item ada harus sebelum response 404");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('header("Content-Type: application/json");', $this->code, "Header JSON harus ditulis dengan benar");
        $this->assertStringContainsString('require "data.php";', $this->code, "Require file data.php harus ada");
        $this->assertStringContainsString('function getData()', $this->code, "Fungsi getData harus ada");
        $this->assertStringContainsString('json_decode', $this->code, "Harus ada penggunaan json_decode jika diperlukan");
        $this->assertStringContainsString('json_encode', $this->code, "Harus ada penggunaan json_encode");
    }
}
