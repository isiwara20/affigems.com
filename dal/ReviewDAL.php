<?php
require_once __DIR__ . '/../config/database.php';

class ReviewDAL {
    private $conn;
    private $table = "reviews";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            rating INT NOT NULL DEFAULT 5,
            comment TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->ensureSchema();
    }

    private function ensureSchema(): void {
        try {
            $this->conn->exec(
                "ALTER TABLE {$this->table}
                 ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'"
            );
        } catch (\Exception $e) {
            // Column already exists
        }
    }

    public function getByProduct($productId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE product_id = :product_id AND status = 'approved'
             ORDER BY created_at DESC"
        );
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }

    public function getAll(?string $status = null) {
        $sql = "SELECT r.*, p.name AS product_name
                FROM {$this->table} r
                LEFT JOIN products p ON r.product_id = p.id";
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= " WHERE r.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAverageRating($productId) {
        $stmt = $this->conn->prepare(
            "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
             FROM {$this->table}
             WHERE product_id = :product_id AND status = 'approved'"
        );
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetch();
    }

    /**
     * @param int[] $productIds
     * @return array<int, array{avg_rating: float|null, total: int}>
     */
    public function getAverageRatingsByProductIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS total
                FROM {$this->table}
                WHERE product_id IN ($placeholders) AND status = 'approved'
                GROUP BY product_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($productIds);

        $ratings = [];
        foreach ($stmt->fetchAll() as $row) {
            $ratings[(int)$row['product_id']] = [
                'avg_rating' => $row['avg_rating'] !== null ? (float)$row['avg_rating'] : 0,
                'total' => (int)$row['total'],
            ];
        }
        return $ratings;
    }

    public function create($productId, $userId, $userName, $rating, $comment) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table}
             (product_id, user_id, user_name, rating, comment, status)
             VALUES (:product_id, :user_id, :user_name, :rating, :comment, 'pending')"
        );
        return $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':user_name' => $userName,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);
    }

    public function updateStatus(int $id, string $status): bool {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET status = :status WHERE id = :id"
        );
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    public function approve(int $id): bool {
        return $this->updateStatus($id, 'approved');
    }

    public function reject(int $id): bool {
        return $this->updateStatus($id, 'rejected');
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getPendingCount(): int {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS count FROM {$this->table} WHERE status = 'pending'"
        );
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    public function getRecentCount() {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS count
             FROM {$this->table}
             WHERE status = 'approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}
