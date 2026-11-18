<?php
require_once __DIR__ . '/../config/database.php';

class WishlistDAL {
    private $conn;
    private $table = "wishlist";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist_item (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("SELECT w.*, p.name, p.slug, p.price, p.original_price, p.image, p.stock, p.author, c.name as category_name FROM " . $this->table . " w JOIN products p ON w.product_id = p.id LEFT JOIN categories c ON p.category_id = c.id WHERE w.user_id = :user_id ORDER BY w.created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function add($userId, $productId) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO " . $this->table . " (user_id, product_id) VALUES (:user_id, :product_id)");
        return $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    }

    public function remove($userId, $productId) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE user_id = :user_id AND product_id = :product_id");
        return $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    }

    public function isInWishlist($userId, $productId) {
        $stmt = $this->conn->prepare("SELECT id FROM " . $this->table . " WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
        return $stmt->fetch() ? true : false;
    }

    public function getCount($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    /**
     * @return int[]
     */
    public function getProductIdsForUser(int $userId): array
    {
        $stmt = $this->conn->prepare("SELECT product_id FROM " . $this->table . " WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'product_id'));
    }

    public function clear($userId) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }
}
