<?php

namespace App\Helpers;

class PHPUnitUploadHelper
{
    public static function prepareTestableFile(string $submissionPath): string
    {
        $uploadedFile = basename($submissionPath);
        $destPath = dirname(__DIR__, 2) . "/tests/temp_logic/{$uploadedFile}";

        if (!file_exists($submissionPath)) {
            throw new \Exception("File $uploadedFile tidak ditemukan.");
        }

        if (!file_exists(dirname($destPath))) {
            mkdir(dirname($destPath), 0777, true);
        }

        copy($submissionPath, $destPath);
        $originalCode = file_get_contents($destPath);

        if (strpos($originalCode, '<?php') === false) {
            throw new \Exception("File $uploadedFile bukan file PHP valid.");
        }

        // Hapus tag pembuka PHP untuk disisipkan ulang secara custom
        $originalCode = preg_replace('/^\s*<\?php\b/', '', $originalCode);

        // Cek apakah script menggunakan $_GET atau $_POST
        $usesGet  = stripos($originalCode, '$_GET') !== false;
        $usesPost = stripos($originalCode, '$_POST') !== false;

        $inputInjection = '';
        if ($usesPost) {
            $inputInjection = '$_POST = $input;';
        } elseif ($usesGet) {
            $inputInjection = '$_GET = $input;';
        }

        // Refactor: bersihkan/mengganti instruksi asli agar tidak mematikan proses
        $refactoredCode = preg_replace([
            '/require\s+[\'"]db\.php[\'"]\s*;/i',
            '/header\s*\(.*?\)\s*;/i',
            '/http_response_code\s*\((.*?)\)\s*;/i',
            '/echo\s+json_encode\s*\((.*?)\)\s*;/i',
            '/die\s*\(\s*json_encode\s*\((.*?)\)\s*\)\s*;/i',
            '/exit\s*\(\s*json_encode\s*\((.*?)\)\s*\)\s*;/i',
            '/exit\s*;/i',
            '/die\s*;/i',
            '/\$conn\s*=\s*.*?;/i',
        ], [
            '', // hapus require db
            '', // hapus header
            '// http_response_code($1);',
            'print(json_encode($1));',
            'print(json_encode($1)); $result = ob_get_clean(); return json_decode($result, true);',
            'print(json_encode($1)); $result = ob_get_clean(); return json_decode($result, true);',
            '$result = ob_get_clean(); return json_decode($result, true);',
            '$result = ob_get_clean(); return json_decode($result, true);',
            '', // hapus $conn manual
        ], $originalCode);

        // Bungkus dalam fungsi agar bisa dipanggil dari PHPUnit
        $wrappedCode = <<<PHP
<?php
function runTestLogic(\$conn, \$input) {
    ob_start();
    {$inputInjection}

{$refactoredCode}

    \$result = ob_get_clean();
    \$decoded = json_decode(\$result, true);

    if (is_null(\$decoded)) {
        return [
            'status' => 'error',
            'message' => 'Invalid or empty JSON output',
            'raw' => \$result
        ];
    }

    return \$decoded;
}
PHP;

        file_put_contents($destPath, $wrappedCode);
        return $destPath;
    }

    public static function cleanupTestableFile(string $uploadedFile): void
    {
        $destPath = dirname(__DIR__, 2) . "/tests/temp_logic/$uploadedFile";
        if (file_exists($destPath)) {
            unlink($destPath);
        }
    }
}
