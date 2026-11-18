<?php
require_once __DIR__ . '/../config/database.php';

class SubscriberDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL UNIQUE,
            status ENUM('active','unsubscribed') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function subscribe($email) {
        $stmt = $this->conn->prepare("INSERT INTO subscribers (email) VALUES (:email) ON DUPLICATE KEY UPDATE status = 'active'");
        return $stmt->execute([':email' => $email]);
    }

    public function exists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM subscribers WHERE email = :email AND status = 'active'");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ? true : false;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM subscribers ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->conn->prepare("SELECT * FROM subscribers WHERE status = 'active' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'");
        $stmt->execute();
        $r = $stmt->fetch();
        return (int)($r['count'] ?? 0);
    }

    public function unsubscribe($email) {
        $stmt = $this->conn->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE email = :email");
        return $stmt->execute([':email' => $email]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM subscribers WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }
}
