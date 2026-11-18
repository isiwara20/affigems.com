<?php
require_once __DIR__ . '/../config/database.php';

class MegaDealDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }
    }

    public function getActiveDeals() {
        $stmt = $this->conn->prepare("
            SELECT md.*, p.name, p.slug, p.image, p.price as product_price, p.stock, 
                   c.name as category_name
            FROM mega_deals md 
            JOIN products p ON md.product_id = p.id 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE md.status = 'active' AND p.status = 'active'
            AND (md.start_date IS NULL OR md.start_date <= CURDATE())
            AND (md.end_date IS NULL OR md.end_date >= CURDATE())
            ORDER BY md.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Active mega deal products formatted for the shop product grid.
     */
    public function getActiveDealProducts() {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.name, p.slug, p.image, p.stock, p.featured,
                   md.deal_price as price, md.original_price,
                   c.name as category_name
            FROM mega_deals md
            JOIN products p ON md.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE md.status = 'active' AND p.status = 'active'
            AND (md.start_date IS NULL OR md.start_date <= CURDATE())
            AND (md.end_date IS NULL OR md.end_date >= CURDATE())
            ORDER BY md.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllDeals() {
        $stmt = $this->conn->prepare("
            SELECT md.*, p.name, p.slug, p.image, p.price as product_price, p.stock
            FROM mega_deals md 
            JOIN products p ON md.product_id = p.id 
            ORDER BY md.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function addDeal($productId, $dealPrice, $startDate = null, $endDate = null) {
        // Get original price from products table
        $stmt = $this->conn->prepare("SELECT price, original_price FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) return false;

        // Store the real original price (use existing original_price if set, otherwise use price)
        $realOriginalPrice = $product['original_price'] && $product['original_price'] > 0 ? $product['original_price'] : $product['price'];

        // Update the product price to deal price
        $stmt = $this->conn->prepare("UPDATE products SET original_price = :original_price, price = :deal_price WHERE id = :id");
        $stmt->execute([':original_price' => $realOriginalPrice, ':deal_price' => $dealPrice, ':id' => $productId]);

        $stmt = $this->conn->prepare("
            INSERT INTO mega_deals (product_id, deal_price, original_price, start_date, end_date, status) 
            VALUES (:product_id, :deal_price, :original_price, :start_date, :end_date, 'active')
            ON DUPLICATE KEY UPDATE deal_price = :deal_price2, original_price = :original_price2, 
            start_date = :start_date2, end_date = :end_date2, status = 'active'
        ");
        return $stmt->execute([
            ':product_id' => $productId,
            ':deal_price' => $dealPrice,
            ':original_price' => $realOriginalPrice,
            ':start_date' => $startDate ?: null,
            ':end_date' => $endDate ?: null,
            ':deal_price2' => $dealPrice,
            ':original_price2' => $realOriginalPrice,
            ':start_date2' => $startDate ?: null,
            ':end_date2' => $endDate ?: null
        ]);
    }

    public function removeDeal($id) {
        // Restore original price first
        $stmt = $this->conn->prepare("SELECT product_id, original_price FROM mega_deals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $deal = $stmt->fetch();
        
        if ($deal) {
            $stmt = $this->conn->prepare("UPDATE products SET price = :original_price, original_price = NULL WHERE id = :product_id");
            $stmt->execute([':original_price' => $deal['original_price'], ':product_id' => $deal['product_id']]);
        }

        $stmt = $this->conn->prepare("DELETE FROM mega_deals WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toggleStatus($id) {
        // Get current status and deal info
        $stmt = $this->conn->prepare("SELECT * FROM mega_deals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $deal = $stmt->fetch();
        
        if (!$deal) return false;

        if ($deal['status'] === 'active') {
            // Deactivating - restore original price
            $stmt = $this->conn->prepare("UPDATE products SET price = :original_price, original_price = NULL WHERE id = :product_id");
            $stmt->execute([':original_price' => $deal['original_price'], ':product_id' => $deal['product_id']]);
        } else {
            // Activating - apply deal price
            $stmt = $this->conn->prepare("UPDATE products SET original_price = :original_price, price = :deal_price WHERE id = :product_id");
            $stmt->execute([':original_price' => $deal['original_price'], ':deal_price' => $deal['deal_price'], ':product_id' => $deal['product_id']]);
        }

        $stmt = $this->conn->prepare("
            UPDATE mega_deals SET status = IF(status='active','inactive','active') WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function getDealByProduct($productId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM mega_deals 
            WHERE product_id = :product_id AND status = 'active'
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
        ");
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetch();
    }
}
