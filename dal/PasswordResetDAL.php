<?php
/**
 * Data Access Layer - Password Reset
 */

require_once __DIR__ . '/../config/database.php';

class PasswordResetDAL {
    private $conn;
    private $table = "password_resets";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        // Create table if not exists
        $this->conn->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Create a reset token
     */
    public function createToken($email, $token, $expiresAt) {
        // Invalidate old tokens for this email
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET used = 1 WHERE email = :email AND used = 0");
        $stmt->execute([':email' => $email]);

        // Insert new token
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (email, token, expires_at) VALUES (:email, :token, :expires_at)");
        return $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expires_at' => $expiresAt
        ]);
    }

    /**
     * Find valid token
     */
    public function findValidToken($token) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . $this->table . " WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed($token) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET used = 1 WHERE token = :token");
        return $stmt->execute([':token' => $token]);
    }
}
