<?php
require_once __DIR__ . '/../config/database.php';

class CategoryDAL {
    private $conn;
    private $table = "categories";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }
        require_once __DIR__ . '/../helpers/db_schema.php';
        db_repair_auto_increment_table($this->conn, 'categories');
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    public function create($name, $slug, $description, $image, $status) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (name, slug, description, image, status) VALUES (:name, :slug, :description, :image, :status)");
        return $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description,
            ':image' => $image,
            ':status' => $status
        ]);
    }

    public function update($id, $name, $slug, $description, $image, $status) {
        if ($image) {
            $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET name=:name, slug=:slug, description=:description, image=:image, status=:status WHERE id=:id");
            return $stmt->execute([':name'=>$name, ':slug'=>$slug, ':description'=>$description, ':image'=>$image, ':status'=>$status, ':id'=>$id]);
        } else {
            $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET name=:name, slug=:slug, description=:description, status=:status WHERE id=:id");
            return $stmt->execute([':name'=>$name, ':slug'=>$slug, ':description'=>$description, ':status'=>$status, ':id'=>$id]);
        }
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getProductCount($categoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = :id");
        $stmt->execute([':id' => $categoryId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function updateSortOrder($id, $sortOrder) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET sort_order = :sort_order WHERE id = :id");
        return $stmt->execute([':sort_order' => (int)$sortOrder, ':id' => (int)$id]);
    }
}
