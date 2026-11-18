<?php
/**
 * Data Access Layer - User
 * Handles all database operations related to users
 */

require_once __DIR__ . '/../config/database.php';

class UserDAL {
    private $conn;
    private $table = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed. Please ensure MySQL is running and the database 'eazymart_db' exists.");
        }
        $this->ensurePhoneColumn();
    }

    private function ensurePhoneColumn(): void
    {
        try {
            $this->conn->exec("ALTER TABLE {$this->table} ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL");
        } catch (Throwable $e) {
            // Older MySQL versions may not support IF NOT EXISTS — ignore if column already exists.
        }
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findByPhone(string $phone) {
        $query = "SELECT * FROM " . $this->table . " WHERE phone = :phone LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create a new user
     */
    public function create($name, $email, $password, $phone = null) {
        $query = "INSERT INTO " . $this->table . " (name, email, password, phone, created_at) 
                  VALUES (:name, :email, :password, :phone, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        if ($phone === null || $phone === '') {
            $stmt->bindValue(':phone', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        }
        return $stmt->execute();
    }

    /**
     * Update remember token
     */
    public function updateRememberToken($userId, $token) {
        $query = "UPDATE " . $this->table . " SET remember_token = :token WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Find user by remember token
     */
    public function findByRememberToken($token) {
        $query = "SELECT * FROM " . $this->table . " WHERE remember_token = :token LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
