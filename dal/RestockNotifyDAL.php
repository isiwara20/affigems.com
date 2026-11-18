<?php
require_once __DIR__ . '/../config/database.php';

class RestockNotifyDAL
{
    private $conn;
    private $table = 'restock_notifications';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            variation_key VARCHAR(255) NOT NULL DEFAULT '',
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(20) NULL,
            user_id INT NULL,
            notified_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_restock_notify (product_id, variation_key, email),
            INDEX idx_product_pending (product_id, notified_at),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * @param string|null $variationKey
     */
    public function subscribe(int $productId, ?string $variationKey, string $email, ?string $phone, ?int $userId = null): bool
    {
        $email = strtolower(trim($email));
        $variationKey = $this->normalizeVariationKey($variationKey);
        $phone = $phone !== null ? trim($phone) : null;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (product_id, variation_key, email, phone, user_id)
             VALUES (:product_id, :variation_key, :email, :phone, :user_id)
             ON DUPLICATE KEY UPDATE
                phone = COALESCE(VALUES(phone), phone),
                user_id = COALESCE(VALUES(user_id), user_id),
                notified_at = NULL,
                created_at = CURRENT_TIMESTAMP"
        );

        return $stmt->execute([
            ':product_id' => $productId,
            ':variation_key' => $variationKey,
            ':email' => $email,
            ':phone' => $phone !== '' ? $phone : null,
            ':user_id' => $userId,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPending(int $productId, string $variationKey = ''): array
    {
        $variationKey = $this->normalizeVariationKey($variationKey);

        if ($variationKey === '') {
            $stmt = $this->conn->prepare(
                "SELECT * FROM {$this->table}
                 WHERE product_id = :product_id AND variation_key = '' AND notified_at IS NULL
                 ORDER BY created_at ASC"
            );
            $stmt->execute([':product_id' => $productId]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT * FROM {$this->table}
                 WHERE product_id = :product_id AND variation_key = :variation_key AND notified_at IS NULL
                 ORDER BY created_at ASC"
            );
            $stmt->execute([
                ':product_id' => $productId,
                ':variation_key' => $variationKey,
            ]);
        }

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param int[] $ids
     */
    public function markNotified(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET notified_at = NOW() WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
    }

    public function isSubscribed(int $productId, string $email, string $variationKey = ''): bool
    {
        $email = strtolower(trim($email));
        $variationKey = $this->normalizeVariationKey($variationKey);

        if ($variationKey === '') {
            $stmt = $this->conn->prepare(
                "SELECT id FROM {$this->table}
                 WHERE product_id = :product_id AND email = :email AND variation_key = '' AND notified_at IS NULL"
            );
            $stmt->execute([':product_id' => $productId, ':email' => $email]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT id FROM {$this->table}
                 WHERE product_id = :product_id AND email = :email AND variation_key = :variation_key AND notified_at IS NULL"
            );
            $stmt->execute([
                ':product_id' => $productId,
                ':email' => $email,
                ':variation_key' => $variationKey,
            ]);
        }

        return (bool)$stmt->fetch();
    }

    private function normalizeVariationKey(?string $variationKey): string
    {
        if ($variationKey === null) {
            return '';
        }

        return trim($variationKey);
    }
}
