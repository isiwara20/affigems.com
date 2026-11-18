<?php
require_once __DIR__ . '/../config/database.php';

class SmsLogDAL
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS sms_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_name VARCHAR(150) NULL,
            recipient VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
            error_message VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_campaign (campaign_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function create(array $data): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO sms_logs (campaign_name, recipient, message, status, error_message)
             VALUES (:campaign_name, :recipient, :message, :status, :error_message)'
        );
        return $stmt->execute([
            ':campaign_name' => $data['campaign_name'] ?? null,
            ':recipient' => $data['recipient'] ?? '',
            ':message' => $data['message'] ?? '',
            ':status' => ($data['status'] ?? 'sent') === 'failed' ? 'failed' : 'sent',
            ':error_message' => $data['error_message'] ?? null,
        ]);
    }

    public function getRecent(int $limit = 50): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query('SELECT * FROM sms_logs ORDER BY created_at DESC');
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return string[]
     */
    public function getDistinctOrderPhones(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT phone FROM orders WHERE phone IS NOT NULL AND phone <> '' ORDER BY created_at DESC LIMIT 5000"
        );
        $phones = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $phone) {
            $normalized = ezymart_normalize_phone((string)$phone);
            if ($normalized) {
                $phones[$normalized] = $normalized;
            }
        }
        return array_values($phones);
    }

    /**
     * @return string[]
     */
    public function getDistinctUserPhones(): array
    {
        $stmt = $this->conn->query(
            "SELECT DISTINCT phone FROM users WHERE phone IS NOT NULL AND phone <> '' LIMIT 5000"
        );
        $phones = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $phone) {
            $normalized = ezymart_normalize_phone((string)$phone);
            if ($normalized) {
                $phones[$normalized] = $normalized;
            }
        }
        return array_values($phones);
    }
}
