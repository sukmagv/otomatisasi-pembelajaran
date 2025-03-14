<?php


namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1DataTest extends Unit
{
    private $filePath;
    private $code;

    protected function _before()
    {
        // Ambil path file dari environment variable yang dikirim dari CLI
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

    public function testFileVariableExists()
    {
        $this->assertMatchesRegularExpression('/\s*\$file\s*=\s*["\']data.json["\']\s*;/', $this->code, "Variabel \$file harus dideklarasikan dengan benar");
    }

    public function testGlobalFileVariableUsage()
    {
        $this->assertMatchesRegularExpression('/global\s+\$file\s*;/', $this->code, "Variabel \$file harus digunakan secara global dalam fungsi");
    }

    public function testFileExistsCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!file_exists\s*\(\s*\$file\s*\)\s*\)/', $this->code, "Harus ada pengecekan file_exists sebelum membuat data.json");
    }

    public function testFilePutContentsWithJsonEncode()
    {
        $this->assertMatchesRegularExpression('/file_put_contents\s*\(\s*\$file\s*,\s*json_encode\s*\(/', $this->code, "Harus ada file_put_contents dengan json_encode");
    }

    public function testGetDataFunctionExists()
    {
        $this->assertMatchesRegularExpression('/function\s+getData\s*\(\s*\)\s*\{/', $this->code, "Fungsi getData harus ada");
        $this->assertMatchesRegularExpression('/return\s+json_decode\s*\(\s*file_get_contents\s*\(\s*\$file\s*\)/', $this->code, "Fungsi getData harus menggunakan file_get_contents");
    }

    public function testSaveDataFunctionExists()
    {
        $this->assertMatchesRegularExpression('/function\s+saveData\s*\(\s*\$data\s*\)\s*\{/', $this->code, "Fungsi saveData harus ada");
        $this->assertMatchesRegularExpression('/file_put_contents\s*\(\s*\$file\s*,\s*json_encode\s*\(\s*\$data\s*,\s*JSON_PRETTY_PRINT\s*\)\s*\)/', $this->code, "Fungsi saveData harus menggunakan file_put_contents dengan json_encode");
    }

    public function testCodeExecutionOrder()
    {
        // Pastikan deklarasi variabel $file ada sebelum function getData atau saveData
        $filePos = strpos($this->code, '$file = "data.json";');
        $getDataPos = strpos($this->code, 'function getData()');
        $saveDataPos = strpos($this->code, 'function saveData(');

        $this->assertNotFalse($filePos, "Variabel \$file harus ada");
        $this->assertNotFalse($getDataPos, "Fungsi getData harus ada");
        $this->assertNotFalse($saveDataPos, "Fungsi saveData harus ada");

        // Variabel $file harus dideklarasikan sebelum fungsi
        $this->assertTrue($filePos < $getDataPos, "Deklarasi variabel \$file harus sebelum fungsi getData");
        $this->assertTrue($filePos < $saveDataPos, "Deklarasi variabel \$file harus sebelum fungsi saveData");

        // file_exists() check harus sebelum fungsi apa pun
        $fileExistsPos = strpos($this->code, 'if (!file_exists($file))');
        $this->assertTrue($fileExistsPos < $getDataPos, "Pengecekan file_exists harus sebelum getData");
        $this->assertTrue($fileExistsPos < $saveDataPos, "Pengecekan file_exists harus sebelum saveData");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('$file = "data.json";', $this->code, "Variabel \$file harus dideklarasikan dengan benar");
        $this->assertStringContainsString('function getData()', $this->code, "Fungsi getData harus ada");
        $this->assertStringContainsString('function saveData(', $this->code, "Fungsi saveData harus ada");
        $this->assertStringContainsString('file_put_contents', $this->code, "Harus ada penggunaan file_put_contents");
        $this->assertStringContainsString('json_encode', $this->code, "Harus ada penggunaan json_encode");
        $this->assertStringContainsString('json_decode', $this->code, "Harus ada penggunaan json_decode");
        $this->assertStringContainsString('file_get_contents', $this->code, "Harus ada penggunaan file_get_contents");
    }
}
