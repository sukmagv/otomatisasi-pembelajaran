<?php
use Tests\Support\ApiTester;

class GetCest
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

        // Reset hanya satu kali
        if (!self::$resetDone && file_exists(self::$dataFile)) {
            // Isi dengan data dummy untuk pengujian
            $dummy = [
                1 => ["id" => 1, "name" => "Item One"],
                2 => ["id" => 2, "name" => "Item Two"]
            ];
            file_put_contents(self::$dataFile, json_encode($dummy));
            self::$resetDone = true;
        }
    }

    public function testGetAllData(ApiTester $I)
    {
        $I->sendGET(self::$filePath);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "status" => 200,
        ]);
        $I->seeResponseMatchesJsonType([
            "status" => "integer",
            "data"   => "array"
        ]);
    }

    public function testGetExistingItem(ApiTester $I)
    {
        $I->sendGET(self::$filePath . '?id=1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            "status" => 200,
            "data"   => ["id" => 1, "name" => "Item One"]
        ]);
    }

    public function testGetNonExistingItem(ApiTester $I)
    {
        $I->sendGET(self::$filePath . '?id=999');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            "status" => 404,
            "error"  => "Item Not Found"
        ]);
    }
}
