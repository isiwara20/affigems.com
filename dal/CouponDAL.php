<?php
require_once __DIR__ . '/../config/database.php';

class CouponDAL {
    private $conn;
    private $table = "coupons";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
            discount_value DECIMAL(10,2) NOT NULL,
            min_order DECIMAL(10,2) DEFAULT 0,
            max_uses INT DEFAULT 0,
            used_count INT DEFAULT 0,
            expires_at DATE NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => strtoupper($code)]);
        return $stmt->fetch();
    }

    public function create($code, $discountType, $discountValue, $minOrder, $maxUses, $expiresAt, $status) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (code, discount_type, discount_value, min_order, max_uses, expires_at, status) VALUES (:code, :discount_type, :discount_value, :min_order, :max_uses, :expires_at, :status)");
        return $stmt->execute([
            ':code' => strtoupper($code),
            ':discount_type' => $discountType,
            ':discount_value' => $discountValue,
            ':min_order' => $minOrder,
            ':max_uses' => $maxUses,
            ':expires_at' => $expiresAt ?: null,
            ':status' => $status
        ]);
    }

    public function update($id, $code, $discountType, $discountValue, $minOrder, $maxUses, $expiresAt, $status) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET code=:code, discount_type=:discount_type, discount_value=:discount_value, min_order=:min_order, max_uses=:max_uses, expires_at=:expires_at, status=:status WHERE id=:id");
        return $stmt->execute([
            ':id' => $id,
            ':code' => strtoupper($code),
            ':discount_type' => $discountType,
            ':discount_value' => $discountValue,
            ':min_order' => $minOrder,
            ':max_uses' => $maxUses,
            ':expires_at' => $expiresAt ?: null,
            ':status' => $status
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function incrementUsage($id) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET used_count = used_count + 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function validateCoupon($code, $orderTotal) {
        $coupon = $this->getByCode($code);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }
        if ($coupon['status'] !== 'active') {
            return ['valid' => false, 'message' => 'This coupon is no longer active.'];
        }
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }
        if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
        }
        if ($orderTotal < $coupon['min_order']) {
            return ['valid' => false, 'message' => 'Minimum order of LKR ' . number_format($coupon['min_order'], 0) . ' required.'];
        }

        // Calculate discount
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($orderTotal * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => round($discount, 2),
            'message' => 'Coupon applied! You save LKR ' . number_format($discount, 0)
        ];
    }
}
