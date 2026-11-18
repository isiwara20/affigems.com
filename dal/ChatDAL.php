<?php
require_once __DIR__ . '/../config/database.php';

class ChatDAL {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed.");
        }

        // Conversations table
        $this->conn->exec("CREATE TABLE IF NOT EXISTS chat_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            guest_name VARCHAR(100) NULL,
            guest_email VARCHAR(150) NULL,
            status ENUM('bot','awaiting_email','human_requested','human_active','closed') DEFAULT 'bot',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Messages table
        $this->conn->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender ENUM('user','bot','admin') DEFAULT 'user',
            message TEXT NOT NULL,
            message_type ENUM('text','products','quick_replies') DEFAULT 'text',
            metadata JSON NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation (conversation_id),
            INDEX idx_sender (sender)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure status column has awaiting_email option
        try {
            $this->conn->exec("ALTER TABLE chat_conversations MODIFY COLUMN status ENUM('bot','awaiting_email','human_requested','human_active','closed') DEFAULT 'bot'");
        } catch (PDOException $e) {}
    }

    // --- Conversations ---

    public function createConversation($userId = null, $guestName = null, $guestEmail = null) {
        $stmt = $this->conn->prepare("INSERT INTO chat_conversations (user_id, guest_name, guest_email, status) VALUES (:user_id, :guest_name, :guest_email, 'bot')");
        $stmt->execute([
            ':user_id' => $userId,
            ':guest_name' => $guestName,
            ':guest_email' => $guestEmail
        ]);
        return $this->conn->lastInsertId();
    }

    public function getConversation($id) {
        $stmt = $this->conn->prepare("SELECT * FROM chat_conversations WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    public function getActiveConversation($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM chat_conversations WHERE user_id = :user_id AND status != 'closed' ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([':user_id' => (int)$userId]);
        return $stmt->fetch();
    }

    public function updateStatus($conversationId, $status) {
        $stmt = $this->conn->prepare("UPDATE chat_conversations SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => (int)$conversationId]);
    }

    public function updateGuestEmail($conversationId, $email) {
        $stmt = $this->conn->prepare("UPDATE chat_conversations SET guest_email = :email WHERE id = :id");
        return $stmt->execute([':email' => $email, ':id' => (int)$conversationId]);
    }

    public function getHumanChats() {
        $stmt = $this->conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email, 
            (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender = 'user' AND is_read = 0) as unread_count
            FROM chat_conversations c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.status IN ('human_requested','human_active') 
            ORDER BY c.updated_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllChats() {
        $stmt = $this->conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email,
            (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender = 'user' AND is_read = 0) as unread_count
            FROM chat_conversations c 
            LEFT JOIN users u ON c.user_id = u.id 
            ORDER BY c.updated_at DESC LIMIT 50");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // --- Messages ---

    public function addMessage($conversationId, $sender, $message, $type = 'text', $metadata = null) {
        $stmt = $this->conn->prepare("INSERT INTO chat_messages (conversation_id, sender, message, message_type, metadata) VALUES (:conversation_id, :sender, :message, :message_type, :metadata)");
        $stmt->execute([
            ':conversation_id' => (int)$conversationId,
            ':sender' => $sender,
            ':message' => $message,
            ':message_type' => $type,
            ':metadata' => $metadata ? json_encode($metadata) : null
        ]);
        
        // Update conversation timestamp
        $this->conn->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = :id")->execute([':id' => (int)$conversationId]);
        
        return $this->conn->lastInsertId();
    }

    public function getMessages($conversationId, $afterId = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM chat_messages WHERE conversation_id = :cid AND id > :after_id ORDER BY created_at ASC");
        $stmt->execute([':cid' => (int)$conversationId, ':after_id' => (int)$afterId]);
        return $stmt->fetchAll();
    }

    public function getMessage(int $messageId) {
        $stmt = $this->conn->prepare("SELECT * FROM chat_messages WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $messageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markAsRead($conversationId, $sender) {
        $stmt = $this->conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :cid AND sender = :sender AND is_read = 0");
        return $stmt->execute([':cid' => (int)$conversationId, ':sender' => $sender]);
    }

    public function getUnreadAdminCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM chat_messages m JOIN chat_conversations c ON m.conversation_id = c.id WHERE m.sender = 'user' AND m.is_read = 0 AND c.status IN ('human_requested','human_active')");
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
}
