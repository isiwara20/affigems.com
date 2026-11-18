<?php
require_once __DIR__ . '/../config/database.php';

class LoginOtpDAL
{
    private $conn;
    private $table = 'login_otps';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_login_otp_phone (phone),
            INDEX idx_login_otp_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function countRecentSends(string $phone, int $minutes = 15): int
    {
        $minutes = max(1, (int)$minutes);
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM {$this->table}
             WHERE phone = :phone AND created_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)"
        );
        $stmt->execute([':phone' => $phone]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function createOtp(string $phone, int $ttlMinutes = 5): string
    {
        $ttlMinutes = max(1, min(30, (int)$ttlMinutes));
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($code, PASSWORD_DEFAULT);

        $delete = $this->conn->prepare("DELETE FROM {$this->table} WHERE phone = :phone");
        $delete->execute([':phone' => $phone]);

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (phone, otp_hash, expires_at)
             VALUES (:phone, :otp_hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE))"
        );
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':otp_hash', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':ttl', $ttlMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return $code;
    }

    public function verifyOtp(string $phone, string $code, int $maxAttempts = 5): bool
    {
        $code = preg_replace('/\D+/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE phone = :phone AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':phone' => $phone]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ((int)$row['attempts'] >= $maxAttempts) {
            return false;
        }

        $valid = password_verify($code, (string)$row['otp_hash']);

        if (!$valid) {
            $update = $this->conn->prepare(
                "UPDATE {$this->table} SET attempts = attempts + 1 WHERE id = :id"
            );
            $update->execute([':id' => (int)$row['id']]);
            return false;
        }

        $this->conn->prepare("DELETE FROM {$this->table} WHERE phone = :phone")
            ->execute([':phone' => $phone]);

        return true;
    }
}
