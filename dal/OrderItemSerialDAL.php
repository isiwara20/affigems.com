<?php
require_once __DIR__ . '/../config/database.php';

class OrderItemSerialDAL
{
    private $conn;
    private $table = 'order_item_serials';

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
            unit_index TINYINT UNSIGNED NOT NULL DEFAULT 1,
            serial_number VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_item_unit (order_item_id, unit_index),
            UNIQUE KEY uniq_serial (serial_number),
            INDEX idx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getByOrderId(int $orderId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE order_id = :order_id ORDER BY order_item_id ASC, unit_index ASC"
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<int, string>> order_item_id => [unit_index => serial]
     */
    public function getMapByOrderId(int $orderId): array
    {
        $map = [];
        foreach ($this->getByOrderId($orderId) as $row) {
            $itemId = (int)$row['order_item_id'];
            $unitIndex = (int)$row['unit_index'];
            $map[$itemId][$unitIndex] = (string)$row['serial_number'];
        }
        return $map;
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

    public function serialInUse(string $serial, ?int $excludeOrderItemId = null, ?int $excludeUnitIndex = null): bool
    {
        $serial = trim($serial);
        if ($serial === '') {
            return false;
        }

        $sql = "SELECT id FROM {$this->table} WHERE serial_number = :serial";
        $params = [':serial' => $serial];

        if ($excludeOrderItemId !== null && $excludeUnitIndex !== null) {
            $sql .= ' AND NOT (order_item_id = :order_item_id AND unit_index = :unit_index)';
            $params[':order_item_id'] = $excludeOrderItemId;
            $params[':unit_index'] = $excludeUnitIndex;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function upsert(int $orderId, int $orderItemId, int $unitIndex, string $serial): bool
    {
        $serial = trim($serial);
        if ($serial === '') {
            return $this->delete($orderItemId, $unitIndex);
        }

        $existing = $this->getByOrderItemUnit($orderItemId, $unitIndex);
        if ($existing) {
            $stmt = $this->conn->prepare(
                "UPDATE {$this->table}
                 SET serial_number = :serial_number, updated_at = NOW()
                 WHERE order_item_id = :order_item_id AND unit_index = :unit_index"
            );
            return $stmt->execute([
                ':serial_number' => $serial,
                ':order_item_id' => $orderItemId,
                ':unit_index' => $unitIndex,
            ]);
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (order_id, order_item_id, unit_index, serial_number)
             VALUES (:order_id, :order_item_id, :unit_index, :serial_number)"
        );
        return $stmt->execute([
            ':order_id' => $orderId,
            ':order_item_id' => $orderItemId,
            ':unit_index' => $unitIndex,
            ':serial_number' => $serial,
        ]);
    }

    public function delete(int $orderItemId, int $unitIndex): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE order_item_id = :order_item_id AND unit_index = :unit_index"
        );
        return $stmt->execute([
            ':order_item_id' => $orderItemId,
            ':unit_index' => $unitIndex,
        ]);
    }
}
