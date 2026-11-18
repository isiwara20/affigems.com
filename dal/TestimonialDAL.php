<?php
require_once __DIR__ . '/../config/database.php';

class TestimonialDAL {
    private $conn;
    private $table = "testimonials";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NULL,
            rating INT NOT NULL DEFAULT 5,
            message TEXT NOT NULL,
            order_number VARCHAR(50) NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getApproved($limit = 10) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE status = 'approved' ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($userId, $name, $email, $rating, $message, $orderNumber) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (user_id, name, email, rating, message, order_number) VALUES (:user_id, :name, :email, :rating, :message, :order_number)");
        return $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':email' => $email,
            ':rating' => $rating,
            ':message' => $message,
            ':order_number' => $orderNumber
        ]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
