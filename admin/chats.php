<?php
$currentPage = 'chats';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ChatDAL.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$chatDAL = new ChatDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$selectedChat = $_GET['id'] ?? null;
$chats = $chatDAL->getHumanChats();
$selectedConversation = null;
$selectedMessages = [];
$chatUserName = 'Guest';
$chatUserEmail = '';

if ($selectedChat) {
    $selectedConversation = $chatDAL->getConversation($selectedChat);
    $selectedMessages = $chatDAL->getMessages($selectedChat);
    $chatDAL->markAsRead($selectedChat, 'user');
    
    if ($selectedConversation && $selectedConversation['user_id']) {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = :id");
        $stmt->execute([':id' => $selectedConversation['user_id']]);
        $chatUser = $stmt->fetch();
        if ($chatUser) {
            $chatUserName = $chatUser['name'];
            $chatUserEmail = $chatUser['email'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Chat Support - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .chat-page-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            height: 100%;
            background: #f5f7fa;
            overflow: hidden;
        }

        /* Sidebar */
        .chat-panel-sidebar {
            background: #fff;
            border-right: 1px solid #e8ecf1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
        }
        .chat-panel-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid #f0f2f8;
            flex-shrink: 0;
        }
        .chat-panel-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: #0d1137;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chat-panel-header h3 i { color: #e8600a; }
        .chat-panel-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            min-height: 0;
        }
        .chat-panel-list::-webkit-scrollbar { width: 4px; }
        .chat-panel-list::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        .chat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.15s;
            margin-bottom: 4px;
        }
        .chat-item:hover { background: #f8f9fc; }
        .chat-item.active { background: #eef0f8; border-left: 3px solid #1a1f4e; }
        .chat-item-avatar {
            width: 40px; height: 40px;
            background: #f0f2f8;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: #1a1f4e; flex-shrink: 0;
        }
        .chat-item-info { flex: 1; min-width: 0; }
        .chat-item-name { font-size: 13px; font-weight: 600; color: #0d1137; margin-bottom: 2px; }
        .chat-item-msg { font-size: 11px; color: #6b7099; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-item-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
        .chat-item-badge { min-width: 18px; height: 18px; padding: 0 5px; background: #ef4444; color: #fff; border-radius: 10px; font-size: 9px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .chat-item-status { font-size: 9px; padding: 2px 7px; border-radius: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        .chat-item-status.new { background: #fef3c7; color: #92400e; }
        .chat-item-status.live { background: #d1fae5; color: #065f46; }

        /* Main Chat Area */
        .chat-panel-main {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-main-top {
            padding: 16px 24px;
            background: #fff;
            border-bottom: 1px solid #e8ecf1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-main-top-left { display: flex; align-items: center; gap: 12px; }
        .chat-main-top-left .user-avatar {
            width: 38px; height: 38px;
            background: #001f52;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px;
        }
        .chat-main-top-left h4 { font-size: 14px; font-weight: 600; color: #0d1137; margin: 0; }
        .chat-main-top-left p { font-size: 11px; color: #6b7099; margin: 0; }
        .btn-end-chat {
            padding: 8px 16px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
            border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer;
            font-family: 'Poppins', sans-serif; transition: all 0.2s; display: flex; align-items: center; gap: 5px;
        }
        .btn-end-chat:hover { background: #fee2e2; }

        /* Messages */
        .chat-main-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .chat-main-body::-webkit-scrollbar { width: 5px; }
        .chat-main-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 5px; }

        .msg-row { display: flex; gap: 10px; max-width: 75%; }
        .msg-row.from-user { align-self: flex-start; }
        .msg-row.from-admin { align-self: flex-end; flex-direction: row-reverse; max-width: 60%; }
        .msg-row.from-bot { align-self: flex-start; max-width: 80%; opacity: 0.7; }
        .msg-avatar {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 12px;
        }
        .msg-row.from-user .msg-avatar { background: #e8ecf5; color: #1a1f4e; }
        .msg-row.from-admin .msg-avatar { background: #1a1f4e; color: #fff; }
        .msg-row.from-bot .msg-avatar { background: #f0f2f8; color: #6b7099; }
        .msg-body {}
        .msg-sender { font-size: 10px; font-weight: 600; color: #6b7099; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.3px; }
        .msg-row.from-admin .msg-sender { text-align: right; }
        .msg-bubble {
            padding: 12px 16px; border-radius: 14px; font-size: 13px; line-height: 1.6; word-wrap: break-word;
        }
        .msg-row.from-user .msg-bubble { background: #fff; border: 1px solid #e8ecf1; color: #333; border-radius: 14px 14px 14px 4px; }
        .msg-row.from-admin .msg-bubble { background: #001f52; color: #fff; border-radius: 14px 14px 4px 14px; }
        .msg-row.from-bot .msg-bubble { background: #f5f7fa; border: 1px solid #e8ecf1; color: #666; border-radius: 14px 14px 14px 4px; font-style: italic; }
        .msg-time { font-size: 10px; color: #aaa; margin-top: 4px; }
        .msg-row.from-admin .msg-time { text-align: right; }

        /* Input */
        .chat-main-footer {
            padding: 16px 24px;
            background: #fff;
            border-top: 1px solid #e8ecf1;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .chat-main-footer input {
            flex: 1; padding: 13px 18px;
            border: 1.5px solid #e4e8f0; border-radius: 12px;
            font-family: 'Poppins', sans-serif; font-size: 13px; outline: none;
            transition: border-color 0.2s;
        }
        .chat-main-footer input:focus { border-color: #1a1f4e; }
        .chat-main-footer button {
            padding: 13px 24px; background: #001f52;
            color: #fff; border: none; border-radius: 12px;
            font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
        }
        .chat-main-footer button:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(26,31,78,0.3); }

        /* Empty states */
        .chat-empty-state {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #6b7099; padding: 40px;
        }
        .chat-empty-state i { font-size: 48px; color: #ddd; margin-bottom: 16px; }
        .chat-empty-state h4 { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 6px; }
        .chat-empty-state p { font-size: 13px; color: #999; }

        @media (max-width: 768px) {
            .chat-page-layout { grid-template-columns: 1fr; }
            .chat-panel-sidebar { display: <?php echo $selectedChat ? 'none' : 'flex'; ?>; }
            .chat-panel-main { display: <?php echo $selectedChat ? 'flex' : 'none'; ?>; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content" style="padding:0; height:calc(100vh - 65px); overflow:hidden;">
                <div class="chat-page-layout">
                <!-- Left Panel - Chat List -->
                <div class="chat-panel-sidebar">
                    <div class="chat-panel-header">
                        <h3><i class="fas fa-headset"></i> Support Chats</h3>
                    </div>
                    <div class="chat-panel-list">
                        <?php if (empty($chats)): ?>
                        <div style="padding:40px 20px; text-align:center; color:#999;">
                            <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:12px; color:#ddd;"></i>
                            <p style="font-size:13px; margin:0;">No active support requests</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($chats as $chat): ?>
                        <a href="<?php echo BASE_URL; ?>admin/chats.php?id=<?php echo $chat['id']; ?>" class="chat-item <?php echo $selectedChat == $chat['id'] ? 'active' : ''; ?>">
                            <div class="chat-item-avatar"><i class="fas fa-user"></i></div>
                            <div class="chat-item-info">
                                <div class="chat-item-name"><?php echo htmlspecialchars($chat['user_name'] ?? 'Guest'); ?></div>
                                <div class="chat-item-msg"><?php echo htmlspecialchars(substr($chat['last_message'] ?? '', 0, 40)); ?></div>
                            </div>
                            <div class="chat-item-meta">
                                <span class="chat-item-status <?php echo $chat['status'] === 'human_requested' ? 'new' : 'live'; ?>">
                                    <?php echo $chat['status'] === 'human_requested' ? 'New' : 'Live'; ?>
                                </span>
                                <?php if ($chat['unread_count'] > 0): ?>
                                <span class="chat-item-badge"><?php echo $chat['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Panel - Chat Messages -->
                <div class="chat-panel-main">
                    <?php if ($selectedConversation): ?>
                    <!-- Header -->
                    <div class="chat-main-top">
                        <div class="chat-main-top-left">
                            <div class="user-avatar"><i class="fas fa-user"></i></div>
                            <div>
                                <h4><?php echo htmlspecialchars($chatUserName); ?></h4>
                                <p><?php echo htmlspecialchars($chatUserEmail); ?></p>
                            </div>
                        </div>
                        <form method="POST" action="<?php echo BASE_URL; ?>controllers/ChatController.php" style="margin:0;">
                            <input type="hidden" name="action" value="close_chat">
                            <input type="hidden" name="conversation_id" value="<?php echo $selectedChat; ?>">
                            <button type="submit" class="btn-end-chat" onclick="return confirm('Close this conversation?')">
                                <i class="fas fa-times-circle"></i> End Chat
                            </button>
                        </form>
                    </div>

                    <!-- Messages -->
                    <div class="chat-main-body" id="adminChatMessages">
                        <?php foreach ($selectedMessages as $msg): ?>
                        <div class="msg-row from-<?php echo $msg['sender']; ?>">
                            <div class="msg-avatar">
                                <i class="fas fa-<?php echo $msg['sender'] === 'user' ? 'user' : ($msg['sender'] === 'admin' ? 'headset' : 'robot'); ?>"></i>
                            </div>
                            <div class="msg-body">
                                <div class="msg-sender"><?php echo $msg['sender'] === 'admin' ? 'You' : ucfirst($msg['sender']); ?></div>
                                <div class="msg-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <div class="msg-time"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Input -->
                    <div class="chat-main-footer">
                        <input type="text" id="adminReplyInput" placeholder="Type your reply..." onkeypress="if(event.key==='Enter')sendAdminReply()">
                        <button onclick="sendAdminReply()"><i class="fas fa-paper-plane"></i> Send</button>
                    </div>

                    <script>
                    function sendAdminReply() {
                        var input = document.getElementById('adminReplyInput');
                        var msg = input.value.trim();
                        if (!msg) return;
                        input.value = '';

                        fetch('<?php echo BASE_URL; ?>controllers/ChatController.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'action=admin_reply&conversation_id=<?php echo $selectedChat; ?>&message=' + encodeURIComponent(msg)
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                var container = document.getElementById('adminChatMessages');
                                var div = document.createElement('div');
                                div.className = 'msg-row from-admin';
                                div.innerHTML = '<div class="msg-avatar"><i class="fas fa-headset"></i></div><div class="msg-body"><div class="msg-sender">You</div><div class="msg-bubble">' + msg.replace(/\n/g, '<br>') + '</div><div class="msg-time">Just now</div></div>';
                                container.appendChild(div);
                                container.scrollTop = container.scrollHeight;
                            }
                        });
                    }

                    // Poll for new user messages
                    var lastMsgId = <?php echo !empty($selectedMessages) ? end($selectedMessages)['id'] : 0; ?>;
                    setInterval(function() {
                        fetch('<?php echo BASE_URL; ?>controllers/ChatController.php?action=admin_get_messages&conversation_id=<?php echo $selectedChat; ?>&after_id=' + lastMsgId)
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success && data.messages.length > 0) {
                                var container = document.getElementById('adminChatMessages');
                                data.messages.forEach(function(msg) {
                                    if (msg.id > lastMsgId && msg.sender === 'user') {
                                        var div = document.createElement('div');
                                        div.className = 'msg-row from-user';
                                        div.innerHTML = '<div class="msg-avatar"><i class="fas fa-user"></i></div><div class="msg-body"><div class="msg-sender">User</div><div class="msg-bubble">' + msg.message.replace(/\n/g, '<br>') + '</div><div class="msg-time">Just now</div></div>';
                                        container.appendChild(div);
                                        container.scrollTop = container.scrollHeight;
                                    }
                                    lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
                                });
                            }
                        });
                    }, 4000);

                    // Scroll to bottom on load
                    document.getElementById('adminChatMessages').scrollTop = document.getElementById('adminChatMessages').scrollHeight;
                    </script>

                    <?php else: ?>
                    <div class="chat-empty-state">
                        <i class="fas fa-comments"></i>
                        <h4>No conversation selected</h4>
                        <p>Select a chat from the left to start responding</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </main>
    </div>
</body>
</html>
