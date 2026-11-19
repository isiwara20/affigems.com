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
            media_type ENUM('image','video') DEFAULT 'image',
            image VARCHAR(255) NULL,
            video VARCHAR(255) NULL,
            link VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Schema migration check for existing tables
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM banners LIKE 'media_type'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->conn->exec("ALTER TABLE banners ADD COLUMN media_type ENUM('image','video') DEFAULT 'image' AFTER title");
            }
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM banners LIKE 'video'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->conn->exec("ALTER TABLE banners ADD COLUMN video VARCHAR(255) NULL AFTER image");
            }
        } catch (Throwable $e) {}

        try {
            $this->conn->exec("ALTER TABLE banners MODIFY COLUMN image VARCHAR(255) NULL");
        } catch (Throwable $e) {}
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

    public function create($title, $image, $link, $sortOrder, $mediaType = 'image', $video = null) {
        $stmt = $this->conn->prepare("INSERT INTO banners (title, media_type, image, video, link, sort_order) VALUES (:title, :media_type, :image, :video, :link, :sort_order)");
        return $stmt->execute([
            ':title' => $title,
            ':media_type' => $mediaType === 'video' ? 'video' : 'image',
            ':image' => $image,
            ':video' => $video,
            ':link' => $link,
            ':sort_order' => (int)$sortOrder
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("SELECT image, video FROM banners WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        $banner = $stmt->fetch();
        if ($banner) {
            if (!empty($banner['image'])) {
                $imgPath = __DIR__ . '/../' . ltrim($banner['image'], '/');
                if (file_exists($imgPath)) @unlink($imgPath);
            }
            if (!empty($banner['video'])) {
                $vidPath = __DIR__ . '/../' . ltrim($banner['video'], '/');
                if (file_exists($vidPath)) @unlink($vidPath);
            }
        }
        $stmt = $this->conn->prepare("DELETE FROM banners WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE banners SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => (int)$id]);
    }
}
