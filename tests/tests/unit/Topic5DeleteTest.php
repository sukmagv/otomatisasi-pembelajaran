<?php

namespace Tests\Unit;

use Codeception\Test\Unit;

class Topic1DeleteTest extends Unit
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

    public function testReturn404IfIdNotFound()
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*!?\$id\s*\|\|\s*!isset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)\s*\)/', $this->code, "There should be a check if the ID is not found");
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*404\s*\)/', $this->code, "Response should be 404 if ID not found");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*404\s*,\s*"error"\s*=>\s*"Item Not Found"\s*\]/', $this->code, "There should be an 'Item Not Found' error message if the ID is not found");
    }

    public function testDeleteItemFromData()
    {
        $this->assertMatchesRegularExpression('/unset\s*\(\s*\$data\s*\[\s*\$id\s*\]\s*\)/', $this->code, "There must be an unset command to delete data by ID");
    }

    public function testSaveDataAfterDeletion()
    {
        $this->assertMatchesRegularExpression('/saveData\s*\(\s*\$data\s*\)/', $this->code, "There must be a saveData call after deletion");
    }

    public function testReturn200IfDeletedSuccessfully()
    {
        $this->assertMatchesRegularExpression('/http_response_code\s*\(\s*200\s*\)/', $this->code, "Response should be 200 if deletion is successful");
        $this->assertMatchesRegularExpression('/json_encode\s*\(\s*\[\s*"status"\s*=>\s*200\s*,\s*"message"\s*=>\s*"Item Deleted"\s*\]\s*\)/', $this->code, "There should be an 'Item Deleted' message after the item is successfully deleted");
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
        $this->assertNotFalse($headerPos, "Header must exist");
        $this->assertNotFalse($requirePos, "Require data.php must exist");
        $this->assertNotFalse($getDataPos, "getData call must exist");
        $this->assertNotFalse($getIdPos, "ID retrieval must exist");
        $this->assertNotFalse($idCheckPos, "ID check must exist");
        $this->assertNotFalse($deletePos, "Item deletion must exist");
        $this->assertNotFalse($savePos, "Data saving must exist");
        $this->assertNotFalse($response200Pos, "Response 200 must exist after deletion");

        // Pastikan urutan eksekusi sesuai
        $this->assertTrue($headerPos < $requirePos, "The header must be executed before require");
        $this->assertTrue($requirePos < $getDataPos, "Require must be before the getData call");
        $this->assertTrue($getDataPos < $getIdPos, "getData must be before ID retrieval");
        $this->assertTrue($getIdPos < $idCheckPos, "ID retrieval must be prior to ID checking");
        $this->assertTrue($idCheckPos < $deletePos, "ID checking must be done before deletion");
        $this->assertTrue($deletePos < $savePos, "Removal must be before storage");
        $this->assertTrue($savePos < $response200Pos, "Saving data must be before 200 responses");
    }
}
