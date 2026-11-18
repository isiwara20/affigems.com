<?php
require_once __DIR__ . '/../config/database.php';

class WarrantyClaimDAL
{
    private $conn;
    private $table = 'warranty_claims';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            claim_number VARCHAR(24) NOT NULL UNIQUE,
            registry_id INT NULL,
            order_id INT NULL,
            user_id INT NULL,
            invoice_number VARCHAR(80) NOT NULL,
            invoice_date DATE NULL,
            serial_number VARCHAR(100) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            customer_name VARCHAR(120) NOT NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(30) NOT NULL,
            issue_type VARCHAR(150) NOT NULL,
            issue_description TEXT NOT NULL,
            attachment VARCHAR(255) NULL,
            status ENUM('pending','approved','replaced','rejected') NOT NULL DEFAULT 'pending',
            admin_notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_claim_number (claim_number),
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Support older tables (if already created without 'rejected' option).
        try {
            $this->conn->exec("ALTER TABLE {$this->table} MODIFY status ENUM('pending','approved','replaced','rejected') NOT NULL DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Ignore if schema already matches or ALTER is blocked.
        }
    }

    public function create(array $data): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table}
             (claim_number, registry_id, order_id, user_id, invoice_number, invoice_date, serial_number, product_name,
              customer_name, email, phone, issue_type, issue_description, attachment, status)
             VALUES (:claim_number, :registry_id, :order_id, :user_id, :invoice_number, :invoice_date, :serial_number, :product_name,
                     :customer_name, :email, :phone, :issue_type, :issue_description, :attachment, :status)"
        );

        return $stmt->execute([
            ':claim_number' => $data['claim_number'],
            ':registry_id' => $data['registry_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':user_id' => $data['user_id'] ?? null,
            ':invoice_number' => $data['invoice_number'],
            ':invoice_date' => $data['invoice_date'] ?: null,
            ':serial_number' => $data['serial_number'],
            ':product_name' => $data['product_name'],
            ':customer_name' => $data['customer_name'],
            ':email' => $data['email'] ?: null,
            ':phone' => $data['phone'],
            ':issue_type' => $data['issue_type'],
            ':issue_description' => $data['issue_description'],
            ':attachment' => $data['attachment'] ?? null,
            ':status' => $data['status'] ?? 'pending',
        ]);
    }

    public function getAll(?string $status = null): array
    {
        if ($status && $status !== 'all') {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE status = :status ORDER BY created_at DESC");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->conn->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByClaimNumber(string $claimNumber): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE claim_number = :claim_number LIMIT 1");
        $stmt->execute([':claim_number' => trim($claimNumber)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Only one active claim per purchased product unit is allowed.
     * A new claim can be filed only if previous claim was rejected.
     */
    public function hasOpenClaimForRegistry(int $registryId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM {$this->table}
             WHERE registry_id = :registry_id
               AND status IN ('pending','approved','replaced')
             LIMIT 1"
        );
        $stmt->execute([':registry_id' => $registryId]);
        return (bool)$stmt->fetch();
    }

    public function updateStatus(int $id, string $status, ?string $adminNotes = null): bool
    {
        $allowed = ['pending', 'approved', 'replaced', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET status = :status, admin_notes = COALESCE(:admin_notes, admin_notes) WHERE id = :id"
        );
        return $stmt->execute([
            ':status' => $status,
            ':admin_notes' => $adminNotes,
            ':id' => $id,
        ]);
    }

    public function countPending(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }
}
