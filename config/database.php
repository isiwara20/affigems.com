<?php
/**
 * Database Configuration
 * Handles MySQL connection via PDO
 */

class Database {
    private $host = "127.0.0.1";
    private $port = "3306";
    private $db_name = "arigato_db";
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

        foreach ($portsToTry as $port) {
            try {
                $dsn = "mysql:host={$this->host};port={$port};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                $this->port = $port;
                return $this->conn;
            } catch (PDOException $exception) {
                $msg = $exception->getMessage();

                // If DB is missing, try creating it (common on fresh installs).
                if (
                    stripos($msg, 'Unknown database') !== false
                    || stripos($msg, 'SQLSTATE[HY000] [1049]') !== false
                ) {
                    try {
                        $dsnNoDb = "mysql:host={$this->host};port={$port};charset=utf8mb4";
                        $tmp = new PDO($dsnNoDb, $this->username, $this->password, $options);
                        $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $tmp = null;

                        $dsn = "mysql:host={$this->host};port={$port};dbname={$this->db_name};charset=utf8mb4";
                        $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                        $this->port = $port;
                        return $this->conn;
                    } catch (PDOException $e2) {
                        error_log("Connection error (db create failed): " . $e2->getMessage());
                        $this->conn = null;
                        continue;
                    }
                }

                error_log("Connection error: " . $msg);
                $this->conn = null;
            }
        }

        return null;
    }
}
