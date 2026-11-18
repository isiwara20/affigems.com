<?php
require_once __DIR__ . '/../config/database.php';

class OrderDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(20) NOT NULL,
            district VARCHAR(50) NOT NULL,
            city VARCHAR(100) NOT NULL,
            postal_code VARCHAR(10) NULL,
            address_1 VARCHAR(255) NOT NULL,
            address_2 VARCHAR(255) NULL,
            notes TEXT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) DEFAULT 0,
            delivery_fee DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            coupon_code VARCHAR(50) NULL,
            delivery_method VARCHAR(20) DEFAULT 'standard',
            payment_method VARCHAR(20) DEFAULT 'cod',
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            points_earned INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->ensureSchema();
    }

    /**
     * Add missing columns on existing installs (CREATE TABLE IF NOT EXISTS does not alter).
     */
    private function ensureSchema() {
        $migrations = [
            "ALTER TABLE orders ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0 AFTER discount",
            "ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER total",
            "ALTER TABLE orders ADD COLUMN delivery_method VARCHAR(20) DEFAULT 'standard' AFTER coupon_code",
            "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'cod' AFTER delivery_method",
            "ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending' AFTER payment_method",
            "ALTER TABLE orders ADD COLUMN order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending' AFTER payment_status",
            "ALTER TABLE orders ADD COLUMN points_earned INT DEFAULT 0 AFTER order_status",
            "ALTER TABLE order_items ADD COLUMN variation_id INT NULL",
            "ALTER TABLE order_items ADD COLUMN variation_label VARCHAR(255) NULL",
            "ALTER TABLE orders ADD COLUMN shipping_country VARCHAR(5) DEFAULT 'LK' AFTER phone",
            "ALTER TABLE orders ADD COLUMN tracking_id VARCHAR(120) NULL AFTER order_status",
            "ALTER TABLE orders ADD COLUMN courier_company VARCHAR(120) NULL AFTER tracking_id",
            "ALTER TABLE orders ADD COLUMN payment_gateway VARCHAR(30) NULL AFTER payment_method",
            "ALTER TABLE orders ADD COLUMN payment_surcharge DECIMAL(10,2) DEFAULT 0 AFTER payment_gateway",
            "ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(30) DEFAULT 'cod'",
        ];

        foreach ($migrations as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (\Exception $e) {
                // Column already exists
            }
        }
    }

    public function createOrder($data) {
        $stmt = $this->conn->prepare("INSERT INTO orders (order_number, user_id, full_name, email, phone, shipping_country, district, city, postal_code, address_1, address_2, notes, subtotal, discount, delivery_fee, total, coupon_code, delivery_method, payment_method, payment_status, order_status, points_earned) VALUES (:order_number, :user_id, :full_name, :email, :phone, :shipping_country, :district, :city, :postal_code, :address_1, :address_2, :notes, :subtotal, :discount, :delivery_fee, :total, :coupon_code, :delivery_method, :payment_method, :payment_status, :order_status, :points_earned)");
        $stmt->execute($data);
        return $this->conn->lastInsertId();
    }

    public function addOrderItem($orderId, $productId, $productName, $price, $quantity, $variationId = null, $variationLabel = null) {
        $total = $price * $quantity;
        $stmt = $this->conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total, variation_id, variation_label) VALUES (:order_id, :product_id, :product_name, :price, :quantity, :total, :variation_id, :variation_label)");
        return $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':product_name' => $productName,
            ':price' => $price,
            ':quantity' => $quantity,
            ':total' => $total,
            ':variation_id' => $variationId ?: null,
            ':variation_label' => $variationLabel ?: null,
        ]);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getByOrderNumber($orderNumber) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_number = :order_number");
        $stmt->execute([':order_number' => $orderNumber]);
        return $stmt->fetch();
    }

    public function getOrderItems($orderId) {
        $stmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public function updatePaymentStatus($orderId, $status) {
        $stmt = $this->conn->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $orderId]);
    }

    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $orderId]);
    }

    public function applyPaymentGatewaySurcharge(int $orderId, ?string $gateway, float $surcharge, float $newTotal): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE orders SET payment_gateway = :gateway, payment_surcharge = :surcharge, total = :total WHERE id = :id"
        );
        return $stmt->execute([
            ':gateway' => ($gateway !== null && $gateway !== '') ? $gateway : null,
            ':surcharge' => $surcharge,
            ':total' => $newTotal,
            ':id' => $orderId,
        ]);
    }

    public function updateShippingTracking(int $orderId, string $trackingId, string $courierCompany): bool {
        $stmt = $this->conn->prepare(
            "UPDATE orders
             SET tracking_id = :tracking_id, courier_company = :courier_company
             WHERE id = :id"
        );
        return $stmt->execute([
            ':tracking_id' => $trackingId,
            ':courier_company' => $courierCompany,
            ':id' => $orderId,
        ]);
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByPaymentMethod($method) {
        $stmt = $this->conn->prepare("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.payment_method = :method ORDER BY o.created_at DESC");
        $stmt->execute([':method' => $method]);
        return $stmt->fetchAll();
    }
}
