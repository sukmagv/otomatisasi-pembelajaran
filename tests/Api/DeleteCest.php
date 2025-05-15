<?php
use Tests\Support\ApiTester;

class DeleteCest
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
            // Siapkan data awal dengan item id 1
            $dummy = [
                1 => ["id" => 1, "name" => "To Be Deleted"]
            ];
            file_put_contents(self::$dataFile, json_encode($dummy, JSON_PRETTY_PRINT));
            self::$resetDone = true;
        }
    }

    public function testDeleteSuccess(ApiTester $I)
    {
        $I->sendDELETE(self::$filePath . '?id=1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 200,
            'message' => 'Item Deleted'
        ]);
    }

    public function testDeleteNotFound(ApiTester $I)
    {
        $I->sendDELETE(self::$filePath . '?id=999');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'status' => 404,
            'error' => 'Item Not Found'
        ]);
    }
}
