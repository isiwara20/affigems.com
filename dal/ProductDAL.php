<?php
require_once __DIR__ . '/../config/database.php';

class ProductDAL {
    private $conn;
    private $table = "products";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        try {
            $this->conn->exec("ALTER TABLE products ADD COLUMN sort_order INT DEFAULT 0 AFTER featured");
        } catch (\Exception $e) {}
        try {
            $this->conn->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) NULL AFTER original_price");
        } catch (\Exception $e) {}
        try {
            $this->conn->exec("ALTER TABLE products ADD COLUMN warranty_period VARCHAR(20) NULL DEFAULT NULL AFTER stock");
        } catch (\Exception $e) {}
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.sort_order ASC, p.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    public function getByCategory($categoryId) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = :category_id AND p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC");
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function getBySubcategory($subcategoryId) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.subcategory_id = :subcategory_id AND p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC");
        $stmt->execute([':subcategory_id' => $subcategoryId]);
        return $stmt->fetchAll();
    }

    public function getFeatured($limit = 6) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLatest($limit = 8) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRelated($categoryId, $excludeId, $limit = 6) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = :category_id AND p.id != :exclude_id AND p.status = 'active' ORDER BY RAND() LIMIT :limit");
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (category_id, name, slug, description, details, price, original_price, cost_price, stock, image, images, author, status, featured) VALUES (:category_id, :name, :slug, :description, :details, :price, :original_price, :cost_price, :stock, :image, :images, :author, :status, :featured)");
        return $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'],
            ':details' => $data['details'],
            ':price' => $data['price'],
            ':original_price' => $data['original_price'],
            ':cost_price' => $data['cost_price'] ?? null,
            ':stock' => $data['stock'],
            ':image' => $data['image'],
            ':images' => $data['images'],
            ':author' => $data['author'],
            ':status' => $data['status'],
            ':featured' => $data['featured']
        ]);
    }

    public function update($id, $data) {
        $fields = "category_id=:category_id, name=:name, slug=:slug, description=:description, details=:details, price=:price, original_price=:original_price, cost_price=:cost_price, stock=:stock, author=:author, status=:status, featured=:featured";
        $params = [
            ':id' => $id,
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'],
            ':details' => $data['details'],
            ':price' => $data['price'],
            ':original_price' => $data['original_price'],
            ':cost_price' => $data['cost_price'] ?? null,
            ':stock' => $data['stock'],
            ':author' => $data['author'],
            ':status' => $data['status'],
            ':featured' => $data['featured']
        ];

        if (!empty($data['image'])) {
            $fields .= ", image=:image";
            $params[':image'] = $data['image'];
        }
        if (!empty($data['images'])) {
            $fields .= ", images=:images";
            $params[':images'] = $data['images'];
        }

        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET $fields WHERE id=:id");
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function search($query) {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT p.*, c.name as category_name FROM " . $this->table . " p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.name LIKE :search OR p.description LIKE :search2 OR p.author LIKE :search3) AND p.status = 'active' ORDER BY p.sort_order ASC, p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':search2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':search3', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function decrementStock($productId, $quantity) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET stock = stock - :qty WHERE id = :id AND stock >= :qty");
        return $stmt->execute([':qty' => (int)$quantity, ':id' => (int)$productId]);
    }

    /**
     * @param int[] $productIds
     * @return array<int, int>
     */
    public function getSoldCountsByProductIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT product_id, COALESCE(SUM(quantity), 0) AS total_sold
                FROM order_items
                WHERE product_id IN ($placeholders)
                GROUP BY product_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($productIds);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int)$row['product_id']] = (int)$row['total_sold'];
        }
        return $counts;
    }

    public function updateSortOrder($id, $sortOrder) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET sort_order = :sort_order WHERE id = :id");
        return $stmt->execute([':sort_order' => (int)$sortOrder, ':id' => (int)$id]);
    }
}
