<?php
// db_override.php
if (!function_exists('overrideDbVars')) {
    function overrideDbVars()
    {
        $GLOBALS['host'] = 'localhost';
        $GLOBALS['user'] = 'root';
        $GLOBALS['password'] = '';
        $GLOBALS['dbname'] = 'test_db';
    }
}

if (defined('PHPUNIT_RUNNING')) {
    overrideDbVars();
    register_shutdown_function('overrideDbVars');
}

$mockStmt = new class {
    public $insert_id = 1;
    public $affected_rows = 1;
    public $bound_id = null;

    public function bind_param($types, &...$vars) {
        $this->bound_id = &$vars[0];
        return true;
    }

    public function execute() {
        return true;
    }

    public function get_result() {
        $id = $this->bound_id ?? null;
        return new class($id) {
            private $id;
            public function __construct($id) {
                $this->id = $id;
            }

            public function fetch_assoc() {
                if ($this->id === 999999999) {
                    return null;
                }
                return ['id' => $this->id, 'name' => 'Mock User', 'email' => 'mock@example.com'];
            }

            public function fetch_all($type = MYSQLI_ASSOC) {
                return [
                    ['id' => 1, 'name' => 'Mock User', 'email' => 'mock@example.com'],
                    ['id' => 2, 'name' => 'Another User', 'email' => 'another@example.com'],
                ];
            }
        };
    }

    public function close() {
        return true;
    }
};

    $mockConn = new class($mockStmt) {
        public $affected_rows = 1;
        public $insert_id = 1;
        private $stmt;

        public function __construct($stmt) {
            $this->stmt = $stmt;
        }

        public function prepare($query) {
            return $this->stmt;
        }

        public function query($sql) {
        // Jika query adalah SELECT, kembalikan hasil mock
        if (preg_match('/^\s*SELECT/i', $sql)) {
            // Deteksi SELECT dengan ID
            if (preg_match('/WHERE id\s*=\s*(\d+)/i', $sql, $matches)) {
                $id = (int)$matches[1];
                $this->stmt->bound_id = $id; // Set manual untuk get_result()
            } else {
                $this->stmt->bound_id = null; // Get all users
            }

            return $this->stmt->get_result();
        }

        // Untuk UPDATE atau lainnya
        if (preg_match('/WHERE id = (\d+)/', $sql, $matches)) {
            $id = (int)$matches[1];
            $this->affected_rows = in_array($id, [1, 2]) ? 1 : 0;
        }
        return true;
    }


    public function real_escape_string($string) {
        return addslashes($string);
    }

    public function close() {
        return true;
    }
};

return $mockConn;

