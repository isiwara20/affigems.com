<?php
require_once __DIR__ . '/../config/database.php';

class SiteSettingsDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            value TEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function get($key, $default = '') {
        $stmt = $this->conn->prepare("SELECT value FROM site_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? ($row['value'] ?? $default) : $default;
    }

    public function set($key, $value) {
        $stmt = $this->conn->prepare("INSERT INTO site_settings (setting_key, value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = :value2");
        return $stmt->execute([':key' => $key, ':value' => $value, ':value2' => $value]);
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM site_settings ORDER BY setting_key ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['value'];
        }
        return $settings;
    }
}
