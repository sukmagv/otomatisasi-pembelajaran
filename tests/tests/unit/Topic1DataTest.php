<?php


namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1DataTest extends Unit
{
    private $filePath;
    private $code;

    protected function _before()
    {
        // Retrieve the file path of the environment variable sent from the CLI
        $this->filePath = $_SERVER['testFile'] ?? $_ENV['testFile'] ?? null;
    
        // Make sure the file path is given
        $this->assertNotEmpty($this->filePath, "The file path must be given as the --testFile argument");
    
        // Make sure the file exists
        $this->assertFileExists($this->filePath, "Tested files must be present");
    
        // Retrieve file contents as a string
        $this->code = file_get_contents($this->filePath);
    }    

    public function testValidPHPCode()
    {
        $tokens = token_get_all($this->code);
        $this->assertNotEmpty($tokens, "PHP code must be valid without syntax errors");
    }

    public function testFileVariableExists()
    {
        $this->assertMatchesRegularExpression('/\s*\$file\s*=\s*["\']data.json["\']\s*;/', $this->code, "The variable \$file must be declared correctly");
    }

    public function testGlobalFileVariableUsage()
    {
        $this->assertMatchesRegularExpression('/global\s+\$file\s*;/', $this->code, "The variable \$file must be used globally in the function");
    }

    public function testFileExistsCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!file_exists\s*\(\s*\$file\s*\)\s*\)/', $this->code, "There must be a file_exists check before creating data.json");
    }

    public function testFilePutContentsWithJsonEncode()
    {
        $this->assertMatchesRegularExpression('/file_put_contents\s*\(\s*\$file\s*,\s*json_encode\s*\(/', $this->code, "Must have file_put_contents with json_encode");
    }

    public function testGetDataFunctionExists()
    {
        $this->assertMatchesRegularExpression('/function\s+getData\s*\(\s*\)\s*\{/', $this->code, "The getData function must exist");
        $this->assertMatchesRegularExpression('/return\s+json_decode\s*\(\s*file_get_contents\s*\(\s*\$file\s*\)/', $this->code, "The getData function must use file_get_contents");
    }

    public function testSaveDataFunctionExists()
    {
        $this->assertMatchesRegularExpression('/function\s+saveData\s*\(\s*\$data\s*\)\s*\{/', $this->code, "The saveData function must exist");
        $this->assertMatchesRegularExpression('/file_put_contents\s*\(\s*\$file\s*,\s*json_encode\s*\(\s*\$data\s*,\s*JSON_PRETTY_PRINT\s*\)\s*\)/', $this->code, "The saveData function must use file_put_contents with json_encode");
    }

    public function testCodeExecutionOrder()
    {
        // Pastikan deklarasi variabel $file ada sebelum function getData atau saveData
        $filePos = strpos($this->code, '$file = "data.json";');
        $getDataPos = strpos($this->code, 'function getData()');
        $saveDataPos = strpos($this->code, 'function saveData(');

        $this->assertNotFalse($filePos, "The \$file variable must exist");
        $this->assertNotFalse($getDataPos, "The getData function must exist");
        $this->assertNotFalse($saveDataPos, "The saveData function must exist");

        // Variabel $file harus dideklarasikan sebelum fungsi
        $this->assertTrue($filePos < $getDataPos, "The declaration of the variable \$file must be before the getData function");
        $this->assertTrue($filePos < $saveDataPos, "The declaration of the variable \$file must be before the saveData function");

        // file_exists() check harus sebelum fungsi apa pun
        $fileExistsPos = strpos($this->code, 'if (!file_exists($file))');
        $this->assertTrue($fileExistsPos < $getDataPos, "File_exists check must come before getData");
        $this->assertTrue($fileExistsPos < $saveDataPos, "File_exists check must come before saveData");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('$file = "data.json";', $this->code, "The variable \$file must be declared correctly");
        $this->assertStringContainsString('function getData()', $this->code, "The getData function must exist");
        $this->assertStringContainsString('function saveData(', $this->code, "The saveData function must exist");
        $this->assertStringContainsString('file_put_contents', $this->code, "There must be a use of file_put_contents");
        $this->assertStringContainsString('json_encode', $this->code, "There must be a use of json_encode");
        $this->assertStringContainsString('json_decode', $this->code, "There must be a use of json_encode");
        $this->assertStringContainsString('file_get_contents', $this->code, "There must be a use of file_put_contents");
    }
}
