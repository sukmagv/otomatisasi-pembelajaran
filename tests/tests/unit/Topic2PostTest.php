<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1PostTest extends Unit
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

    public function testJsonDecodeUsage()
    {
        $this->assertMatchesRegularExpression('/\$input\s*=\s*json_decode\s*\(\s*file_get_contents\s*\(\s*"php:\/\/input"\s*\)/', $this->code, "There should be json_decode(file_get_contents('php://input')) to fetch JSON input");
    }

    public function testInputValidationCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!isset\(\s*\$input\s*\[\s*[\'"]id[\'"]\s*\]\s*\)\s*\|\|\s*!isset\(\s*\$input\s*\[\s*[\'"]name[\'"]\s*\]\s*\)\s*\)/', $this->code, "There should be input validation to ensure 'id' and 'name' are included");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*400\s*\)/', $this->code, "There must be http_response_code(400) if the input is invalid");
    }

    public function testItemAlreadyExistsCheck()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$data\s*\[\s*\$input\s*\[\s*[\'"]id[\'"]\s*\]\s*\]\s*\)\s*\)/', $this->code, "There should be a check whether the ID already exists in the data");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*400\s*\)/', $this->code, "Must have http_response_code(400) if item already exists");
    }

    public function testSaveDataFunctionCall()
    {
        $this->assertMatchesRegularExpression('/saveData\s*\(\s*\$data\s*\)/', $this->code, "There should be a saveData call after adding a new item");
    }

    public function testHttpResponse201()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*201\s*\)/', $this->code, "There should be http_response_code(201) if the item was successfully added");
    }

    public function testJsonResponseOnSuccess()
    {
        $this->assertMatchesRegularExpression('/echo\s+json_encode\s*\(\s*\[\s*"status"\s*=>\s*201\s*,\s*"message"\s*=>\s*"Item Created"/', $this->code, "There should be a JSON response indicating success with a status of 201 and the message 'Item Created'");
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
        $this->assertNotFalse($headerPos, "Header must exist");
        $this->assertNotFalse($requirePos, "Require data.php must exist");
        $this->assertNotFalse($getDataPos, "Fetching getData must exist");
        $this->assertNotFalse($jsonDecodePos, "Fetching JSON input must exist");
        $this->assertNotFalse($validationPos, "Input validation must exist");
        $this->assertNotFalse($existsCheckPos, "Checking if item already exists must exist");
        $this->assertNotFalse($saveDataPos, "Saving new data must exist");
        $this->assertNotFalse($response201Pos, "Successful 201 response must exist");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "The header must be executed before require");
        $this->assertTrue($requirePos < $getDataPos, "Require must be before the getData call");
        $this->assertTrue($getDataPos < $jsonDecodePos, "getData must be before fetching JSON input");
        $this->assertTrue($jsonDecodePos < $validationPos, "JSON input capture must be before validation");
        $this->assertTrue($validationPos < $existsCheckPos, "Validation must be prior to checking whether the item already exists.");
        $this->assertTrue($existsCheckPos < $saveDataPos, "Checking the item already exists must be before saveData");
        $this->assertTrue($saveDataPos < $response201Pos, "saveData must be before sending the 201 response");
    }

    public function testTypoCheck()
    {
        // Pastikan tidak ada kesalahan ketik umum
        $this->assertStringContainsString('header("Content-Type: application/json");', $this->code, "JSON headers must be written correctly");
        $this->assertStringContainsString('require "data.php";', $this->code, "Require file data.php must exist");
        $this->assertStringContainsString('function getData()', $this->code, "The getData function must exist");
        $this->assertStringContainsString('function saveData(', $this->code, "The saveData function must exist");
        $this->assertStringContainsString('json_decode', $this->code, "There must be a json_decode call");
        $this->assertStringContainsString('json_encode', $this->code, "There must be a json_encode call");
    }
}
