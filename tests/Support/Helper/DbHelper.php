<?php
namespace Tests\Support\Helper;

use Codeception\Module;

class DbHelper extends Module
{
    /**
     * Hapus user dari database berdasarkan username atau email
     *
     * @param string $username
     * @param string $email
     * @throws \Exception jika koneksi DB gagal
     */
    public function cleanupInsertedUser(string $username, string $email): void
    {
        $db = new \mysqli(
            getenv('DB_APITEST_HOST') ?: '127.0.0.1',
            getenv('DB_APITEST_USERNAME') ?: 'root',
            getenv('DB_APITEST_PASSWORD') ?: '',
            getenv('DB_APITEST_DATABASE') ?: 'test_db'
        );

        if ($db->connect_error) {
            throw new \Exception("Koneksi DB gagal: " . $db->connect_error);
        }

        $stmt = $db->prepare("DELETE FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->close();
        $db->close();
    }
}
