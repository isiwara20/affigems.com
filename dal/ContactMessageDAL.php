<?php
require_once __DIR__ . '/../config/database.php';

class ContactMessageDAL
{
    private $conn;
    private $table = 'contact_messages';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference_no VARCHAR(24) NOT NULL UNIQUE,
            full_name VARCHAR(120) NOT NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(150) NULL,
            topic VARCHAR(80) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_contact_ref (reference_no),
            INDEX idx_contact_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function create(array $data): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table}
             (reference_no, full_name, phone, email, topic, subject, message)
             VALUES (:reference_no, :full_name, :phone, :email, :topic, :subject, :message)"
        );

        return $stmt->execute([
            ':reference_no' => $data['reference_no'],
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'] ?: null,
            ':email' => $data['email'] ?: null,
            ':topic' => $data['topic'],
            ':subject' => $data['subject'],
            ':message' => $data['message'],
        ]);
    }

    public function getByReferenceNo(string $referenceNo): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT reference_no, full_name, phone, email, topic, subject, message
             FROM {$this->table}
             WHERE reference_no = :reference_no
             LIMIT 1"
        );
        $stmt->execute([':reference_no' => $referenceNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'reference_no' => (string)$row['reference_no'],
            'full_name' => (string)$row['full_name'],
            'phone' => (string)($row['phone'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'topic' => (string)$row['topic'],
            'subject' => (string)$row['subject'],
            'message' => (string)$row['message'],
        ];
    }
}
