<?php

// Ambil file konfigurasi dari test (misal disimpan oleh controller Laravel)
$configPath = __DIR__ . '/test-config.json';
if (!file_exists($configPath)) {
    throw new Exception("Konfigurasi test tidak ditemukan.");
}

$config = json_decode(file_get_contents($configPath), true);

// Ambil path ke file yang diuji
$mainFile = $config['testFile'] ?? null;
if (!$mainFile) {
    throw new Exception("Path file utama tidak ditemukan di konfigurasi.");
}

// Asumsikan file db.php berada di direktori yang sama dengan file utama
$targetDir = dirname($mainFile);
$dbPath = base_path('public/'. $targetDir . '/db.php');

// Cek keberadaan file db.php
if (!file_exists($dbPath)) {
    throw new Exception("db.php tidak ditemukan pada: " . $dbPath);
}

$GLOBALS['includedDbFile'] = $dbPath;

// Isolasi output agar tidak mencampur output db.php
ob_start();
include $dbPath;
ob_end_clean();

// Kembalikan koneksi jika tersedia
if (isset($conn)) {
    return $conn;
}

throw new Exception("Koneksi gagal: tidak ada objek \$conn.");
