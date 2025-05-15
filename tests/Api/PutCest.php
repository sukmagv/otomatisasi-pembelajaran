<?php
use Tests\Support\ApiTester;

class PutCest
{
    protected static $filePath;
    protected static $dataFile;
    protected static $resetDone = false;

    public function _before(ApiTester $I)
    {
        if (!self::$filePath) {
            $configFile = codecept_root_dir() . 'tests/test-config.json';
    
            if (!file_exists($configFile)) {
                throw new \RuntimeException("Config file tidak ditemukan: {$configFile}");
            }
    
            $config = json_decode(file_get_contents($configFile), true);
            $realPath = $config['testFile'] ?? null;
    
            // Pastikan file ada
            if (!$realPath || !file_exists($realPath)) {
                throw new \RuntimeException("File tidak ditemukan atau path kosong: {$realPath}");
            }
    
            // Set path relatif
            $relative = str_replace(public_path(), '', $realPath);
            self::$filePath = ltrim(str_replace('\\', '/', $relative), '/');
            self::$dataFile = dirname($realPath) . '/data-temp.json';
        }

        if (!self::$resetDone && file_exists(self::$dataFile)) {
            // Siapkan data awal
            $dummy = [
                1 => ["id" => 1, "name" => "Old Name"],
            ];
            file_put_contents(self::$dataFile, json_encode($dummy, JSON_PRETTY_PRINT));
            self::$resetDone = true;
        }
    }

    public function testUpdateSuccess(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT(self::$filePath . '?id=1', ['name' => 'Updated Name']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 200,
            'message' => 'Item Updated',
            'produk' => ['id' => 1, 'name' => 'Updated Name']
        ]);
    }

    public function testUpdateInvalidInput(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT(self::$filePath . '?id=1', []); // tidak ada "name"
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'status' => 400,
            'error' => 'Invalid Input'
        ]);
    }

    public function testUpdateNotFound(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT(self::$filePath . '?id=999', ['name' => 'Should Fail']);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'status' => 404,
            'error' => 'Item Not Found'
        ]);
    }
}
