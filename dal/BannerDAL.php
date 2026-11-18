<?php
require_once __DIR__ . '/../config/database.php';

class BannerDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NULL,
            image VARCHAR(255) NOT NULL,
            link VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getActive() {
        $stmt = $this->conn->prepare("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($title, $image, $link, $sortOrder) {
        $stmt = $this->conn->prepare("INSERT INTO banners (title, image, link, sort_order) VALUES (:title, :image, :link, :sort_order)");
        return $stmt->execute([':title' => $title, ':image' => $image, ':link' => $link, ':sort_order' => (int)$sortOrder]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("SELECT image FROM banners WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        $banner = $stmt->fetch();
        if ($banner && $banner['image']) {
            $path = __DIR__ . '/../' . $banner['image'];
            if (file_exists($path)) unlink($path);
        }
        $stmt = $this->conn->prepare("DELETE FROM banners WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE banners SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => (int)$id]);
    }
}
