<?php
require_once __DIR__ . '/../config/database.php';

class AddressDAL {
    private $conn;
    private $table = "user_saved_addresses";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS " . $this->table . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            label VARCHAR(50) DEFAULT 'Home',
            full_name VARCHAR(100) NULL,
            phone VARCHAR(20) NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NULL,
            district VARCHAR(100) NULL,
            postal_code VARCHAR(10) NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([':user_id' => (int)$userId]);
        return $stmt->fetchAll();
    }

    public function getById($id, $userId) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => (int)$id, ':user_id' => (int)$userId]);
        return $stmt->fetch();
    }

    public function create($userId, $data) {
        if (!empty($data['is_default'])) {
            $this->clearDefault($userId);
        }

        $count = $this->getCount($userId);
        if ($count === 0) {
            $data['is_default'] = 1;
        }

        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (user_id, label, full_name, phone, address, city, district, postal_code, is_default) VALUES (:user_id, :label, :full_name, :phone, :address, :city, :district, :postal_code, :is_default)");
        return $stmt->execute([
            ':user_id' => (int)$userId,
            ':label' => $data['label'] ?? 'Home',
            ':full_name' => $data['full_name'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':address' => $data['address'] ?? '',
            ':city' => $data['city'] ?? '',
            ':district' => $data['district'] ?? '',
            ':postal_code' => $data['postal_code'] ?? '',
            ':is_default' => (int)($data['is_default'] ?? 0)
        ]);
    }

    public function update($id, $userId, $data) {
        if (!empty($data['is_default'])) {
            $this->clearDefault($userId);
        }

        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET label = :label, full_name = :full_name, phone = :phone, address = :address, city = :city, district = :district, postal_code = :postal_code, is_default = :is_default WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([
            ':label' => $data['label'] ?? 'Home',
            ':full_name' => $data['full_name'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':address' => $data['address'] ?? '',
            ':city' => $data['city'] ?? '',
            ':district' => $data['district'] ?? '',
            ':postal_code' => $data['postal_code'] ?? '',
            ':is_default' => (int)($data['is_default'] ?? 0),
            ':id' => (int)$id,
            ':user_id' => (int)$userId
        ]);
    }

    public function delete($id, $userId) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => (int)$id, ':user_id' => (int)$userId]);
    }

    public function setDefault($id, $userId) {
        $this->clearDefault($userId);
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET is_default = 1 WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => (int)$id, ':user_id' => (int)$userId]);
    }

    private function clearDefault($userId) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET is_default = 0 WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => (int)$userId]);
    }

    public function getCount($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id");
        $stmt->execute([':user_id' => (int)$userId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    public function getDefault($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE user_id = :user_id AND is_default = 1 LIMIT 1");
        $stmt->execute([':user_id' => (int)$userId]);
        return $stmt->fetch();
    }
}
