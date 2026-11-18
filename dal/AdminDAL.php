<?php
/**
 * Data Access Layer - Admin
 * Handles all database operations related to admins
 */

require_once __DIR__ . '/../config/database.php';

class AdminDAL {
    private $conn;
    private $table = "admins";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }
    }

    /**
     * Find admin by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get all admins
     */
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT id, name, email, created_at FROM " . $this->table . " ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new admin
     */
    public function create($name, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (name, email, password) VALUES (:name, :email, :password)");
        return $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hashedPassword]);
    }

    /**
     * Delete admin by ID
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get admin count
     */
    public function getCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM " . $this->table);
        $stmt->execute();
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
