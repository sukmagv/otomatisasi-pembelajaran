<?php

use Tests\Support\ApiTester;
use Mockery;

class PostCest
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
    
        // Reset data file hanya sekali
        if (!self::$resetDone && file_exists(self::$dataFile)) {
            file_put_contents(self::$dataFile, json_encode([]));
            self::$resetDone = true;
        }
    }    

    public function testIncomplete(ApiTester $I)
    {
        // Gunakan filePath dinamis untuk pengujian
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST(self::$filePath, ['name' => 'Only Name']);

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'Invalid Input']);
    }

    public function testComplete(ApiTester $I)
    {
        // Gunakan filePath dinamis untuk pengujian
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST(self::$filePath, ['id' => 99, 'name' => 'Test Item']);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Item Created']);
    }

    public function testDuplicate(ApiTester $I)
    {
        // Gunakan filePath dinamis untuk pengujian
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST(self::$filePath, ['id' => 99, 'name' => 'Test Item']);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'Item Already Exists']);
    }

    public static function _afterSuite()
    {
        // Bersihkan setelah pengujian
        $configFile = codecept_root_dir() . 'tests/test-config.json';
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    public static function _after(ApiTester $I)
    {
        // Bersihkan mock
        Mockery::close();
    }
}
