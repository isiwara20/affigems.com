<?php
require_once __DIR__ . '/../config/database.php';

class WarrantyRegistryDAL
{
    private $conn;
    private $table = 'warranty_registry';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            order_item_id INT NOT NULL,
            product_id INT NOT NULL,
            user_id INT NULL,
            invoice_number VARCHAR(80) NOT NULL,
            serial_number VARCHAR(100) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            warranty_period VARCHAR(20) NOT NULL,
            purchase_date DATE NOT NULL,
            expires_at DATE NOT NULL,
            unit_index TINYINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_serial (serial_number),
            INDEX idx_invoice (invoice_number),
            INDEX idx_order (order_id),
            INDEX idx_user (user_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function isOrderRegistered(int $orderId): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE order_id = :order_id LIMIT 1");
        $stmt->execute([':order_id' => $orderId]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table}
             (order_id, order_item_id, product_id, user_id, invoice_number, serial_number, product_name, warranty_period, purchase_date, expires_at, unit_index)
             VALUES (:order_id, :order_item_id, :product_id, :user_id, :invoice_number, :serial_number, :product_name, :warranty_period, :purchase_date, :expires_at, :unit_index)"
        );

        return $stmt->execute([
            ':order_id' => (int)$data['order_id'],
            ':order_item_id' => (int)$data['order_item_id'],
            ':product_id' => (int)$data['product_id'],
            ':user_id' => $data['user_id'] ?? null,
            ':invoice_number' => $data['invoice_number'],
            ':serial_number' => $data['serial_number'],
            ':product_name' => $data['product_name'],
            ':warranty_period' => $data['warranty_period'],
            ':purchase_date' => $data['purchase_date'],
            ':expires_at' => $data['expires_at'],
            ':unit_index' => (int)($data['unit_index'] ?? 1),
        ]);
    }

    public function getBySerial(string $serial): ?array
    {
        $serial = trim($serial);
        $stmt = $this->conn->prepare(
            "SELECT wr.*, p.image AS product_image
             FROM {$this->table} wr
             LEFT JOIN products p ON p.id = wr.product_id
             WHERE wr.serial_number = :serial
             LIMIT 1"
        );
        $stmt->execute([':serial' => $serial]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByOrderItemUnit(int $orderItemId, int $unitIndex): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE order_item_id = :order_item_id AND unit_index = :unit_index
             LIMIT 1"
        );
        $stmt->execute([
            ':order_item_id' => $orderItemId,
            ':unit_index' => $unitIndex,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateSerial(int $id, string $serial): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET serial_number = :serial_number WHERE id = :id"
        );
        return $stmt->execute([
            ':serial_number' => trim($serial),
            ':id' => $id,
        ]);
    }

    public function serialInUse(string $serial, ?int $excludeId = null): bool
    {
        $serial = trim($serial);
        if ($serial === '') {
            return false;
        }

        $sql = "SELECT id FROM {$this->table} WHERE serial_number = :serial";
        $params = [':serial' => $serial];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function getByInvoiceOrOrder(string $reference): array
    {
        $normalized = strtoupper(trim($reference));
        $stmt = $this->conn->prepare(
            "SELECT wr.*, p.image AS product_image
             FROM {$this->table} wr
             LEFT JOIN products p ON p.id = wr.product_id
             WHERE wr.invoice_number = :invoice
                OR wr.invoice_number = :invoice_prefixed
                OR wr.serial_number = :serial
             ORDER BY product_name ASC, unit_index ASC"
        );
        $stmt->execute([
            ':invoice' => $normalized,
            ':invoice_prefixed' => 'INV-' . $normalized,
            ':serial' => trim($reference),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT wr.*, p.image AS product_image
             FROM {$this->table} wr
             LEFT JOIN products p ON p.id = wr.product_id
             WHERE wr.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
