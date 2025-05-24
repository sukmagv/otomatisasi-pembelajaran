<?php

namespace Tests\Support\Helper;

use Codeception\Module;

class SubmissionTestHelper extends Module
{
    /**
     * Generate override file untuk auto_prepend_file.
     *
     * @param array $data Data yang ingin diinject.
     * @param string $fileName Nama file override (contoh: 'override_get_id.php')
     * @param string $variable Nama variabel yang ingin diisi (contoh: '$id', '$data')
     * @return string Path file override yang dihasilkan
     */
    
    public static function generateAutoPrependFile(array|string $data, string $fileName = 'override.php', string $variable = '$data'): string
    {
        $code  = "<?php\n";

        $variables = array_map('trim', explode(',', $variable));

        if (count($variables) === 1) {
            $varName = trim($variables[0], '$');
            // Jika data array dan keynya sama dengan variabel, ambil nilainya
            if (is_array($data) && array_key_exists($varName, $data)) {
                $value = $data[$varName];
            } else {
                $value = $data;
            }
            $code .= "\$GLOBALS['{$varName}'] = " . var_export($value, true) . ";\n";
        } else {
            foreach ($variables as $i => $var) {
                $key = array_keys($data)[$i] ?? null;
                if ($key !== null) {
                    $varName = trim($var, '$');
                    $code .= "\$GLOBALS['{$varName}'] = " . var_export($data[$key], true) . ";\n";
                }
            }
        }

        $configFile = codecept_root_dir() . 'tests/test-config.json';
        $config = json_decode(file_get_contents($configFile), true);
        $targetDir = dirname($config['testFile'] ?? '') ?: __DIR__;

        $overridePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($overridePath, $code);

        return $overridePath;
    }

    /**
     * Hapus override file berdasarkan nama (default: override.php)
     *
     * @param string $fileName
     */
    public static function cleanupOverrideFile(string $fileName = 'override.php'): void
    {
        $configFile = codecept_root_dir() . 'tests/test-config.json';
        $config = json_decode(file_get_contents($configFile), true);
        $targetDir = dirname($config['testFile'] ?? '') ?: __DIR__;
        $overridePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($overridePath)) {
            unlink($overridePath);
        }
    }

    public function deleteUserByUsername(string $username): void
    {
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        if ($conn->connect_error) {
            throw new \Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    public function cleanupInsertedUser(string $username, string $email): void
    {
        $conn = new \mysqli('127.0.0.1', 'root', '', 'test_db');
        if ($conn->connect_error) {
            throw new \Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}
