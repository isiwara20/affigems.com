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
        $this->ensureTable();
    }

    /**
     * Ensure admins table and default admin credentials exist
     */
    private function ensureTable() {
        try {
            $this->conn->exec("CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('super_admin', 'admin') DEFAULT 'admin',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $check = $this->conn->query("SELECT COUNT(*) FROM `admins`");
            if ($check && (int)$check->fetchColumn() === 0) {
                $hash = '$2y$10$aUO5yA8qs2Kxc46vLoXnLu0tdlFEg89GydWBVQEin4pZrcLUohveO'; // Admin@123
                $seed = $this->conn->prepare("INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES 
                    ('Affinity Admin', 'murshidmunzeer@gmail.com', :hash1, 'super_admin'),
                    ('Affinity Admin (Legacy)', 'info@arigato.lk', :hash2, 'super_admin')");
                $seed->execute([':hash1' => $hash, ':hash2' => $hash]);
            }
        } catch (Throwable $e) {}
    }

    /**
     * Find admin by email
     */
    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->ensureTable();
            try {
                $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch();
            } catch (Throwable $t) {
                return false;
            }
        }
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
