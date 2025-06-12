<?php

$configPath = __DIR__ . '/test-config.json';
if (!file_exists($configPath)) {
    throw new Exception("Konfigurasi test tidak ditemukan.");
}

$config = json_decode(file_get_contents($configPath), true);
$mainFile = $config['testFile'] ?? null;
if (!$mainFile) {
    throw new Exception("Path file utama tidak ditemukan di konfigurasi.");
}

if (basename($mainFile) !== 'db.php') {
    throw new Exception("File yang diuji harus bernama db.php");
}

// Path file absolut (misal dari base_path/public)
$filePath = base_path('public/' . $mainFile);

if (!file_exists($filePath)) {
    throw new Exception("File db.php tidak ditemukan: " . $filePath);
}

$GLOBALS['includedDbFile'] = $filePath;

// Include file db.php yang menginisialisasi $conn
include $filePath;

if (!isset($conn)) {
    throw new Exception("Variabel \$conn tidak ditemukan di file db.php");
}

return $conn;
