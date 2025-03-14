<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1GetTest extends Unit
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

    public function testHeaderJsonOutput()
    {
        $this->assertMatchesRegularExpression('/header\s*\(\s*"Content-Type:\s*application\/json"\s*\)/', $this->code, "The header should set the output as JSON");
    }

    public function testRequireDataFile()
    {
        $this->assertMatchesRegularExpression('/require\s+"data.php"/', $this->code, "There must be a 'require \“data.php\”' to retrieve the getData and saveData functions");
    }

    public function testGetDataFunctionCall()
    {
        $this->assertMatchesRegularExpression('/\$data\s*=\s*getData\s*\(\s*\)/', $this->code, "There must be a getData call to retrieve the data");
    }

    public function testGetIdRetrieval()
    {
        $this->assertMatchesRegularExpression('/\$id\s*=\s*isset\s*\(\s*\$_GET\s*\[\s*[\'"]id[\'"]\s*\]\s*\)\s*\?/', $this->code, "There must be an isset check on \$_GET['id']");
    }

    public function testReturnAllDataWhenIdIsNull()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*is_null\s*\(\s*\$id\s*\)\s*\)/', $this->code, "There must be a check whether the ID is null");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Response must be 200 if ID is null");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"data"\s*=>\s*array_values\s*\(\s*\$data\s*\)/', $this->code, "Must return all data if ID is null");
    }

    public function testReturnSingleItemIfExists()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)\s*\)/', $this->code, "There should be a check if the ID is in the data");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Response should be 200 if the item is found");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"data"\s*=>\s*\$data\s*\[\s*\$id\s*\]\s*\]/', $this->code, "Must return data if ID is found");
    }

    public function testReturn404IfItemNotFound()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*404\s*\)/', $this->code, "Response should be 404 if item not found");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*404\s*,\s*"error"\s*=>\s*"Item Not Found"\s*\]/', $this->code, "There should be an 'Item Not Found' error message if the ID is not found");
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
        $this->assertNotFalse($headerPos, "Header must exist");
        $this->assertNotFalse($requirePos, "Require data.php must exist");
        $this->assertNotFalse($getDataPos, "getData call must exist");
        $this->assertNotFalse($getIdPos, "ID retrieval must exist");
        $this->assertNotFalse($nullCheckPos, "ID null check must exist");
        $this->assertNotFalse($existsCheckPos, "ID exists check must exist");
        $this->assertNotFalse($response404Pos, "Response 404 must exist if item not found");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "The header must be executed before require");
        $this->assertTrue($requirePos < $getDataPos, "Require must be before the getData call");
        $this->assertTrue($getDataPos < $getIdPos, "getData must be before ID retrieval");
        $this->assertTrue($getIdPos < $nullCheckPos, "ID retrieval must be before null checking");
        $this->assertTrue($nullCheckPos < $existsCheckPos, "Null checking must come before checking whether the item exists");
        $this->assertTrue($existsCheckPos < $response404Pos, "Checking whether the item exists must be before response 404");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('header("Content-Type: application/json");', $this->code, "JSON headers must be written correctly");
        $this->assertStringContainsString('require "data.php";', $this->code, "Require file data.php must exist");
        $this->assertStringContainsString('function getData()', $this->code, "The getData function must exist");
        $this->assertStringContainsString('json_encode', $this->code, "There must be a json_encode call");
        $this->assertStringContainsString('json_decode', $this->code, "There must be a json_decode call");
    }
}
