<?php
require_once __DIR__ . '/../config/database.php';

class CartDAL {
    private $conn;
    private $table = "cart";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            variation_id INT NOT NULL DEFAULT 0,
            quantity INT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cart_item (user_id, product_id, variation_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        try {
            $this->conn->exec("ALTER TABLE cart ADD COLUMN variation_id INT NOT NULL DEFAULT 0");
        } catch (Exception $e) {}
        try {
            $this->conn->exec('ALTER TABLE cart DROP INDEX unique_cart_item');
        } catch (Exception $e) {}
        try {
            $this->conn->exec('ALTER TABLE cart ADD UNIQUE KEY unique_cart_item (user_id, product_id, variation_id)');
        } catch (Exception $e) {}
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("
            SELECT c.*, p.name, p.slug, p.image, p.author, p.original_price, p.has_variations,
            CASE
                WHEN c.variation_id > 0 AND pv.price IS NOT NULL THEN pv.price
                ELSE COALESCE(md.deal_price, p.price)
            END as price,
            CASE
                WHEN c.variation_id > 0 THEN pv.stock
                ELSE p.stock
            END as stock,
            CASE
                WHEN md.id IS NOT NULL AND (c.variation_id = 0 OR pv.price IS NULL) THEN p.price
                ELSE p.original_price
            END as original_price,
            pv.options_label AS variation_label,
            pv.sku AS variation_sku
            FROM " . $this->table . " c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variations pv ON pv.id = c.variation_id AND c.variation_id > 0
            LEFT JOIN mega_deals md ON md.product_id = p.id AND md.status = 'active'
                AND (md.start_date IS NULL OR md.start_date <= CURDATE())
                AND (md.end_date IS NULL OR md.end_date >= CURDATE())
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function addItem($userId, $productId, $quantity = 1, $variationId = 0) {
        $variationId = max(0, (int)$variationId);
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (user_id, product_id, variation_id, quantity)
            VALUES (:user_id, :product_id, :variation_id, :quantity)
            ON DUPLICATE KEY UPDATE quantity = quantity + :qty_add");
        return $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId,
            ':variation_id' => $variationId,
            ':quantity' => $quantity,
            ':qty_add' => $quantity
        ]);
    }

    public function updateQuantity($userId, $productId, $quantity, $variationId = 0) {
        $variationId = max(0, (int)$variationId);
        if ($quantity <= 0) {
            return $this->removeItem($userId, $productId, $variationId);
        }
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET quantity = :quantity
            WHERE user_id = :user_id AND product_id = :product_id AND variation_id = :variation_id");
        return $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $userId,
            ':product_id' => $productId,
            ':variation_id' => $variationId,
        ]);
    }

    public function removeItem($userId, $productId, $variationId = 0) {
        $variationId = max(0, (int)$variationId);
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . "
            WHERE user_id = :user_id AND product_id = :product_id AND variation_id = :variation_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId,
            ':variation_id' => $variationId,
        ]);
    }

    public function clearCart($userId) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    public function getCount($userId) {
        $stmt = $this->conn->prepare("SELECT SUM(quantity) as count FROM " . $this->table . " WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
}
