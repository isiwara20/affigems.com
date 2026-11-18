<?php
require_once __DIR__ . '/../config/database.php';

class PointsDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        // Add points column to users table if not exists
        try {
            $this->conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS points INT DEFAULT 0");
        } catch (Exception $e) {
            // Column might already exist
        }

        // Create points history table
        $this->conn->exec("CREATE TABLE IF NOT EXISTS points_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            points INT NOT NULL,
            type ENUM('earned', 'redeemed') DEFAULT 'earned',
            description VARCHAR(255) NULL,
            order_id INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Get user's total points
     */
    public function getUserPoints($userId) {
        $stmt = $this->conn->prepare("SELECT points FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetch();
        return (int)($result['points'] ?? 0);
    }

    /**
     * Add points to user
     */
    public function addPoints($userId, $points, $description = '', $orderId = null) {
        // Update user points
        $stmt = $this->conn->prepare("UPDATE users SET points = points + :points WHERE id = :id");
        $stmt->execute([':points' => $points, ':id' => $userId]);

        // Log history
        $stmt = $this->conn->prepare("INSERT INTO points_history (user_id, points, type, description, order_id) VALUES (:user_id, :points, 'earned', :description, :order_id)");
        return $stmt->execute([
            ':user_id' => $userId,
            ':points' => $points,
            ':description' => $description,
            ':order_id' => $orderId
        ]);
    }

    /**
     * Redeem points from user
     */
    public function redeemPoints($userId, $points, $description = '', $orderId = null) {
        $current = $this->getUserPoints($userId);
        if ($current < $points) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE users SET points = points - :points WHERE id = :id");
        $stmt->execute([':points' => $points, ':id' => $userId]);

        $stmt = $this->conn->prepare("INSERT INTO points_history (user_id, points, type, description, order_id) VALUES (:user_id, :points, 'redeemed', :description, :order_id)");
        return $stmt->execute([
            ':user_id' => $userId,
            ':points' => $points,
            ':description' => $description,
            ':order_id' => $orderId
        ]);
    }

    /**
     * Get points history for a user
     */
    public function getHistory($userId, $limit = 20) {
        $stmt = $this->conn->prepare("SELECT * FROM points_history WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Calculate points for an order (1 point per 100 LKR)
     */
    public static function calculatePoints($orderTotal) {
        return (int)floor($orderTotal / 100);
    }
}
