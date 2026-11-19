<?php
/**
 * Database Configuration
 * Handles MySQL connection via PDO
 */

class Database {
    private $host = "127.0.0.1";
    private $port = "3306";
    private $db_name = "affigems_db";
    private $fallback_db = "arigato_db";
    private $username = "root";
    private $password = "";
    private $conn;
    private static $instance = null;

    /**
     * Get singleton database connection (reuses connection per request)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->getConnection();
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }
        $portsToTry = array_values(array_unique([
            (string)$this->port,
            '3306',
            '3307',
        ]));

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $databasesToTry = [$this->db_name, $this->fallback_db];

        foreach ($portsToTry as $port) {
            foreach ($databasesToTry as $candidateDb) {
                try {
                    $dsn = "mysql:host={$this->host};port={$port};dbname={$candidateDb};charset=utf8mb4";
                    $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                    $this->port = $port;
                    $this->db_name = $candidateDb;
                    $this->ensureSchema();
                    return $this->conn;
                } catch (PDOException $exception) {
                    $msg = $exception->getMessage();
                    // If not unknown DB, don't try another DB on same port
                    if (stripos($msg, 'Unknown database') === false && stripos($msg, '1049') === false) {
                        break;
                    }
                }
            }

            // If neither DB exists, try creating primary DB
            try {
                $dsnNoDb = "mysql:host={$this->host};port={$port};charset=utf8mb4";
                $tmp = new PDO($dsnNoDb, $this->username, $this->password, $options);
                $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $tmp = null;

                $dsn = "mysql:host={$this->host};port={$port};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                $this->port = $port;
                $this->ensureSchema();
                return $this->conn;
            } catch (PDOException $e2) {
                $this->conn = null;
            }
        }

        return null;
    }

    /**
     * Ensure core tables are initialized if connecting to a fresh database
     */
    private function ensureSchema(): void {
        if ($this->conn === null) return;
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'admins'");
            if ($stmt && $stmt->rowCount() === 0) {
                $sqlFile = __DIR__ . '/../database/setup.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    if (!empty($sql)) {
                        $this->conn->exec($sql);
                    }
                }
            }
        } catch (Throwable $e) {}
    }
}
