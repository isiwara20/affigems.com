<?php
require_once __DIR__ . '/../config/database.php';

class SubcategoryDAL {
    private $conn;
    private $table = "subcategories";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        // Create table if not exists
        $this->conn->exec("CREATE TABLE IF NOT EXISTS subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add image column if not exists
        try {
            $this->conn->exec("ALTER TABLE subcategories ADD COLUMN image VARCHAR(255) NULL AFTER description");
        } catch (\Exception $e) {}

        try {
            $this->conn->exec("ALTER TABLE subcategories ADD COLUMN sort_order INT DEFAULT 0 AFTER image");
        } catch (\Exception $e) {}


        // Add subcategory_id to products table if not exists
        try {
            $this->conn->exec("ALTER TABLE products ADD COLUMN subcategory_id INT NULL DEFAULT NULL AFTER category_id");
        } catch (\Exception $e) {
            // Column already exists, ignore
        }

        require_once __DIR__ . '/../helpers/db_schema.php';
        db_repair_auto_increment_table($this->conn, 'categories');
        db_repair_auto_increment_table($this->conn, 'subcategories');
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM " . $this->table . " s LEFT JOIN categories c ON s.category_id = c.id ORDER BY c.sort_order ASC, c.name ASC, s.sort_order ASC, s.name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM " . $this->table . " s LEFT JOIN categories c ON s.category_id = c.id WHERE s.status = 'active' ORDER BY c.sort_order ASC, c.name ASC, s.sort_order ASC, s.name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByCategory($categoryId) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE category_id = :category_id AND status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function getAllByCategory($categoryId) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE category_id = :category_id ORDER BY sort_order ASC, name ASC");
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM " . $this->table . " s LEFT JOIN categories c ON s.category_id = c.id WHERE s.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM " . $this->table . " s LEFT JOIN categories c ON s.category_id = c.id WHERE s.slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    public function create($categoryId, $name, $slug, $description, $image, $status) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (category_id, name, slug, description, image, status) VALUES (:category_id, :name, :slug, :description, :image, :status)");
        return $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':image' => $image,
            ':status' => $status
        ]);
    }

    public function update($id, $categoryId, $name, $slug, $description, $image, $status) {
        if ($image) {
            $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET category_id=:category_id, name=:name, slug=:slug, description=:description, image=:image, status=:status WHERE id=:id");
            return $stmt->execute([
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':image' => $image,
                ':status' => $status,
                ':id' => $id
            ]);
        } else {
            $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET category_id=:category_id, name=:name, slug=:slug, description=:description, status=:status WHERE id=:id");
            return $stmt->execute([
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':status' => $status,
                ':id' => $id
            ]);
        }
    }

    public function delete($id) {
        // Set subcategory_id to NULL for products in this subcategory
        $stmt = $this->conn->prepare("UPDATE products SET subcategory_id = NULL WHERE subcategory_id = :id");
        $stmt->execute([':id' => $id]);
        
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getProductCount($subcategoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM products WHERE subcategory_id = :id");
        $stmt->execute([':id' => $subcategoryId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function updateSortOrder($id, $sortOrder) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET sort_order = :sort_order WHERE id = :id");
        return $stmt->execute([':sort_order' => (int)$sortOrder, ':id' => (int)$id]);
    }
}
