<?php
/**
 * Chat Controller
 * Handles chatbot AJAX requests - bot responses, human assistance, admin replies
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ChatDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/CategoryDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';
require_once __DIR__ . '/../helpers/chat.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle close_chat before setting JSON header (it redirects)
if ($action === 'close_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    closeChat();
}

header('Content-Type: application/json');

switch ($action) {
    case 'start':
        startConversation();
        break;
    case 'send':
        sendMessage();
        break;
    case 'request_human':
        requestHuman();
        break;
    case 'get_messages':
        getMessages();
        break;
    case 'admin_reply':
        adminReply();
        break;
    case 'admin_get_chats':
        adminGetChats();
        break;
    case 'admin_get_messages':
        adminGetMessages();
        break;
    case 'close_chat':
        closeChat();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

function startConversation() {
    $userId = $_SESSION['user_id'] ?? null;
    
    try {
        $chatDAL = new ChatDAL();
        
        // Check for existing active conversation
        if ($userId) {
            $existing = $chatDAL->getActiveConversation($userId);
            if ($existing) {
                $messages = $chatDAL->getMessages($existing['id']);
                echo json_encode(['success' => true, 'conversation_id' => $existing['id'], 'messages' => $messages, 'status' => $existing['status']]);
                return;
            }
        }
        
        // Create new conversation
        $convId = $chatDAL->createConversation($userId);
        
        // Add welcome message
        $welcomeMsg = "Hi! I'm your Affinity Gems & Jewels assistant. I can help you explore our certified Sri Lankan sapphires, precious gemstones, fine jewelry, check orders, or answer questions. How can I help you today?";
        $chatDAL->addMessage($convId, 'bot', $welcomeMsg);
        
        $messages = $chatDAL->getMessages($convId);
        echo json_encode(['success' => true, 'conversation_id' => $convId, 'messages' => $messages, 'status' => 'bot']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to start chat: ' . $e->getMessage()]);
    }
}

function sendMessage() {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (!$conversationId || empty($message)) {
        echo json_encode(['error' => 'Missing data']);
        return;
    }
    
    try {
        $chatDAL = new ChatDAL();
        $conversation = $chatDAL->getConversation($conversationId);
        
        if (!$conversation) {
            echo json_encode(['error' => 'Conversation not found']);
            return;
        }
        
        // Save user message
        $userMsgId = $chatDAL->addMessage($conversationId, 'user', $message);
        
        // If awaiting email from guest
        if ($conversation['status'] === 'awaiting_email') {
            $email = filter_var(trim($message), FILTER_VALIDATE_EMAIL);
            if ($email) {
                // Save email to conversation and connect to human
                $chatDAL->updateGuestEmail($conversationId, $email);
                requestHumanFromBot($chatDAL, $conversationId, null, $email);
                $response = [
                    'type' => 'human_requested',
                    'message' => "Thanks! We've received your email ({$email}). Connecting you with our support team now. You'll also receive responses via email.",
                    'products' => [], 'categories' => [], 'quick_replies' => [], 'last_id' => 0
                ];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
                $response['last_id'] = $msgId;
                echo json_encode(['success' => true, 'bot_response' => $response]);
            } else {
                $response = [
                    'type' => 'text',
                    'message' => "That doesn't look like a valid email address. Please enter a valid email (e.g. name@example.com) so our team can reach you.",
                    'products' => [], 'categories' => [], 'quick_replies' => [], 'last_id' => 0
                ];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
                $response['last_id'] = $msgId;
                echo json_encode(['success' => true, 'bot_response' => $response]);
            }
            return;
        }
        
        // If in human mode, notify admin and wait for a reply.
        if ($conversation['status'] === 'human_active' || $conversation['status'] === 'human_requested') {
            lumak_queue_admin_chat_notification($conversationId, $userMsgId);
            lumak_chat_finish_json_response(
                ['success' => true, 'bot_response' => null, 'last_id' => $userMsgId],
                [
                    'handler' => 'chat_admin_notify',
                    'conversation_id' => $conversationId,
                    'message_id' => $userMsgId,
                ]
            );
        }
        
        // Generate bot response
        $response = generateBotResponse($message, $chatDAL, $conversationId);
        echo json_encode(['success' => true, 'bot_response' => $response]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to send message']);
    }
}

function generateBotResponse($message, $chatDAL, $conversationId) {
    $msgLower = strtolower($message);
    $response = ['type' => 'text', 'message' => '', 'products' => [], 'categories' => [], 'quick_replies' => [], 'last_id' => 0];
    
    // Handle category selection
    if (strpos($msgLower, 'show category: ') === 0) {
        $slug = trim(str_replace('show category: ', '', $msgLower));
        try {
            $categoryDAL = new CategoryDAL();
            $categories = $categoryDAL->getActive();
            $targetCat = null;
            foreach ($categories as $c) {
                if ($c['slug'] === $slug) { $targetCat = $c; break; }
            }
            
            if ($targetCat) {
                $productDAL = new ProductDAL();
                $products = $productDAL->getByCategory($targetCat['id']);
                if (!empty($products)) {
                    $response['message'] = "Here are the products in " . $targetCat['name'] . ":";
                    $response['type'] = 'products';
                    $response['products'] = array_map(function($p) {
                        return ['id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'price' => $p['price'], 'original_price' => $p['original_price'], 'image' => $p['image'], 'category' => $p['category_name'] ?? ''];
                    }, $products);
                    $response['quick_replies'] = ['Browse Products', 'Contact Human'];
                    $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'products', ['products' => $response['products'], 'quick_replies' => $response['quick_replies']]);
                    $response['last_id'] = $msgId;
                } else {
                    $response['message'] = "No products found in this category yet. Try another one!";
                    $response['quick_replies'] = ['Browse Products', 'Contact Human'];
                    $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
                    $response['last_id'] = $msgId;
                }
            }
        } catch (Exception $e) {
            $response['message'] = "Sorry, I couldn't load that category. Please try again.";
            $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
            $response['last_id'] = $msgId;
        }
        return $response;
    }
    
    // Show all products
    if (containsAny($msgLower, ['show all products', 'all products', 'browse all'])) {
        try {
            $productDAL = new ProductDAL();
            $products = $productDAL->getActive();
            if (!empty($products)) {
                $response['message'] = "Here are all our available products:";
                $response['type'] = 'products';
                $response['products'] = array_map(function($p) {
                    return ['id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'price' => $p['price'], 'original_price' => $p['original_price'], 'image' => $p['image'], 'category' => $p['category_name'] ?? ''];
                }, array_slice($products, 0, 10));
                $response['quick_replies'] = ['Browse Products', 'Contact Human'];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'products', ['products' => $response['products'], 'quick_replies' => $response['quick_replies']]);
                $response['last_id'] = $msgId;
            }
        } catch (Exception $e) {
            $response['message'] = "Sorry, I couldn't load products right now.";
            $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
            $response['last_id'] = $msgId;
        }
        return $response;
    }

    // Browse Products - show categories
    if (containsAny($msgLower, ['browse products', 'browse', 'categories', 'show categories'])) {
        try {
            $categoryDAL = new CategoryDAL();
            $categories = $categoryDAL->getActive();
            if (!empty($categories)) {
                $response['message'] = "Choose a category to explore:";
                $response['type'] = 'categories';
                $response['categories'] = array_map(function($c) {
                    return ['id' => $c['id'], 'name' => $c['name'], 'slug' => $c['slug'], 'image' => $c['image'] ?? ''];
                }, $categories);
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'categories', ['categories' => $response['categories']]);
                $response['last_id'] = $msgId;
            } else {
                $response['message'] = "No categories available right now. Try searching for a specific product!";
                $response['quick_replies'] = ['Contact Human'];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
                $response['last_id'] = $msgId;
            }
        } catch (Exception $e) {
            $response['message'] = "Sorry, I couldn't load categories. Please try again.";
            $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
            $response['last_id'] = $msgId;
        }
        return $response;
    }

    if (containsAny($msgLower, ['track', 'order status', 'where is my order', 'my order', 'track order'])) {
        $response['message'] = "Sure! Please enter your order number (e.g. ORD-12345) and I'll check the status for you.";
        $response['quick_replies'] = ['Browse Products', 'Contact Human'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        
    } elseif (preg_match('/^[A-Za-z0-9]{6,}$/', trim($message)) && !containsAny($msgLower, ['hello', 'hi', 'hey', 'browse', 'clothing', 'help', 'thank', 'human', 'delivery', 'payment', 'return'])) {
        // User entered an order number - look it up
        $orderNumber = strtoupper(trim($message));
        try {
            require_once __DIR__ . '/../dal/OrderDAL.php';
            $orderDAL = new OrderDAL();
            $order = $orderDAL->getByOrderNumber($orderNumber);
            
            if ($order) {
                $statusEmoji = ['pending' => 'â³', 'processing' => 'ðŸ“¦', 'shipped' => 'ðŸšš', 'delivered' => 'âœ…', 'cancelled' => 'âŒ'];
                $emoji = $statusEmoji[$order['order_status']] ?? 'ðŸ“‹';
                
                $response['message'] = "{$emoji} Order: {$order['order_number']}\n\n";
                $response['message'] .= "Status: " . ucfirst($order['order_status']) . "\n";
                $response['message'] .= "Payment: " . ucfirst($order['payment_status']) . "\n";
                $response['message'] .= "Total: LKR " . number_format($order['total'], 2) . "\n";
                $response['message'] .= "Placed: " . date('M j, Y', strtotime($order['created_at']));
                
                if ($order['order_status'] === 'shipped') {
                    $response['message'] .= "\n\nYour order is on the way! ðŸŽ‰";
                } elseif ($order['order_status'] === 'delivered') {
                    $response['message'] .= "\n\nYour order has been delivered! We hope you enjoy it. ðŸ“š";
                }
                
                $response['quick_replies'] = ['Browse Products', 'Contact Human'];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
                $response['last_id'] = $msgId;
                return $response;
            }
        } catch (Exception $e) {}
        
        // If not found as order number, fall through to default response
        $response['message'] = "I couldn't find an order with number \"" . htmlspecialchars(trim($message)) . "\". Please double-check and try again.";
        $response['quick_replies'] = ['Track Order', 'Browse Products', 'Contact Human'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        return $response;
        
    } elseif (containsAny($msgLower, ['delivery', 'shipping', 'deliver'])) {
        $response['message'] = "We offer free delivery on orders above LKR 2,500. Standard delivery takes 2-4 business days island-wide.";
        $response['quick_replies'] = ['Browse Products', 'Contact Human'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        
    } elseif (containsAny($msgLower, ['return', 'refund', 'exchange'])) {
        $response['message'] = "We accept returns within 7 days of delivery if the item is in original condition. Would you like to speak with our team?";
        $response['quick_replies'] = ['Contact Human', 'Browse Products'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        
    } elseif (containsAny($msgLower, ['payment', 'pay', 'card', 'cash on delivery', 'cod'])) {
        $response['message'] = "We accept Card payments (Visa/Mastercard) and Cash on Delivery. All online payments are securely processed.";
        $response['quick_replies'] = ['Browse Products', 'Contact Human'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        
    } elseif (containsAny($msgLower, ['hello', 'hi', 'hey', 'good morning', 'good evening'])) {
        $response['message'] = "Hello! Welcome to Affinity Gems & Jewels. I can help you explore our certified Sri Lankan sapphires, gemstone collections, custom jewelry, check orders, or connect you with our team. What are you looking for today?";
        $response['quick_replies'] = ['Browse Products', 'Track Order', 'Contact Human'];
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
        $response['last_id'] = $msgId;
        
    } elseif (containsAny($msgLower, ['human', 'agent', 'real person', 'speak to someone', 'contact human'])) {
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            // Guest - ask for email first
            $response['message'] = "To connect you with our support team, please share your email address so we can follow up with you.";
            $response['quick_replies'] = ['Browse Products'];
            $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
            $response['last_id'] = $msgId;
            // Mark conversation as awaiting email
            $chatDAL->updateStatus($conversationId, 'awaiting_email');
        } else {
            requestHumanFromBot($chatDAL, $conversationId);
            $response['message'] = "I'm connecting you with our support team. You'll receive a response shortly.";
            $response['type'] = 'human_requested';
            $response['last_id'] = 0;
        }
        
    } elseif (containsAny($msgLower, ['thank', 'thanks', 'bye', 'goodbye'])) {
        $response['message'] = "You're welcome!  Feel free to reach out anytime. Happy shopping!";
        $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message']);
        $response['last_id'] = $msgId;
        
    } else {
        // Try product search
        $searchTerms = extractSearchTerms($msgLower);
        $searchQuery = !empty($searchTerms) ? $searchTerms : $message;
        $foundProducts = false;
        
        try {
            $productDAL = new ProductDAL();
            $products = $productDAL->search($searchQuery);
            
            if (!empty($products)) {
                $products = array_slice($products, 0, 6);
                $response['message'] = "Here are some options I found:";
                $response['type'] = 'products';
                $response['products'] = array_map(function($p) {
                    return ['id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'price' => $p['price'], 'original_price' => $p['original_price'], 'image' => $p['image'], 'category' => $p['category_name'] ?? ''];
                }, $products);
                $response['quick_replies'] = ['Browse Products', 'Contact Human'];
                $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'products', ['products' => $response['products'], 'quick_replies' => $response['quick_replies']]);
                $response['last_id'] = $msgId;
                $foundProducts = true;
            }
        } catch (Exception $e) {}
        
        if (!$foundProducts) {
            $response['message'] = "I'd be happy to help! I can assist you with:\nâ€¢ Finding electronics and gadgets\nâ€¢ Order tracking and delivery info\nâ€¢ Payment and return queries\n\nWhat would you like to know?";
            $response['quick_replies'] = ['Browse Products', 'Track Order', 'Delivery Info', 'Contact Human'];
            $msgId = $chatDAL->addMessage($conversationId, 'bot', $response['message'], 'quick_replies', ['quick_replies' => $response['quick_replies']]);
            $response['last_id'] = $msgId;
        }
    }
    
    return $response;
}

function requestHuman() {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    if (!$conversationId) {
        echo json_encode(['error' => 'Missing conversation']);
        return;
    }
    
    try {
        $chatDAL = new ChatDAL();
        requestHumanFromBot($chatDAL, $conversationId);
        lumak_chat_finish_json_response(
            ['success' => true, 'message' => 'Connecting you with our support team...'],
            [
                'handler' => 'chat_admin_notify',
                'conversation_id' => $conversationId,
            ]
        );
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to request human assistance']);
    }
}

function requestHumanFromBot($chatDAL, $conversationId, $guestName = null, $guestEmail = null) {
    $chatDAL->updateStatus($conversationId, 'human_requested');
    $chatDAL->addMessage($conversationId, 'bot', "I'm connecting you with a human assistant. Our team has been notified and will respond shortly. Please wait...");

    try {
        lumak_queue_admin_chat_notification($conversationId);
    } catch (Exception $e) {
        // Don't fail the chat flow if email queueing fails
    }
}

function getMessages() {
    $conversationId = (int)($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);
    $afterId = (int)($_GET['after_id'] ?? $_POST['after_id'] ?? 0);
    
    if (!$conversationId) {
        echo json_encode(['error' => 'Missing conversation']);
        return;
    }
    
    try {
        $chatDAL = new ChatDAL();
        $conversation = $chatDAL->getConversation($conversationId);
        $messages = $chatDAL->getMessages($conversationId, $afterId);
        echo json_encode(['success' => true, 'messages' => $messages, 'status' => $conversation['status'] ?? 'bot']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get messages']);
    }
}

function adminReply() {
    // Verify admin
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (!$conversationId || empty($message)) {
        echo json_encode(['error' => 'Missing data']);
        return;
    }
    
    try {
        $chatDAL = new ChatDAL();
        $chatDAL->addMessage($conversationId, 'admin', $message);
        $chatDAL->updateStatus($conversationId, 'human_active');
        $chatDAL->markAsRead($conversationId, 'user');
        
        // Queue email to user about admin response (sent after response)
        $conversation = $chatDAL->getConversation($conversationId);
        if ($conversation['user_id']) {
            $userIdCopy = $conversation['user_id'];
            $messageCopy = $message;
            AsyncMailQueue::add(function() use ($userIdCopy, $messageCopy) {
                try {
                    sendAdminReplyEmail($userIdCopy, $messageCopy);
                } catch (Exception $e) {}
            });
        }
        
        // Send response immediately, then flush queued emails
        AsyncMailQueue::sendResponse(['success' => true]);
        AsyncMailQueue::flush();
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to send reply']);
    }
}

function sendAdminReplyEmail($userId, $replyMessage) {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['email'])) return;

    lumak_send_html_mail(
        $user['email'],
        $user['name'],
        'Reply from ' . lumak_email_brand_name() . ' Support',
        lumak_email_chat_reply_template($user['name'], $replyMessage),
        "Hi {$user['name']}, our support team replied: {$replyMessage}"
    );
}

function adminGetChats() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    try {
        $chatDAL = new ChatDAL();
        $chats = $chatDAL->getHumanChats();
        echo json_encode(['success' => true, 'chats' => $chats]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get chats']);
    }
}

function adminGetMessages() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $conversationId = (int)($_GET['conversation_id'] ?? 0);
    $afterId = (int)($_GET['after_id'] ?? 0);
    
    try {
        $chatDAL = new ChatDAL();
        $chatDAL->markAsRead($conversationId, 'user');
        $messages = $chatDAL->getMessages($conversationId, $afterId);
        $conversation = $chatDAL->getConversation($conversationId);
        echo json_encode(['success' => true, 'messages' => $messages, 'conversation' => $conversation]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get messages']);
    }
}

function closeChat() {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    if (!$conversationId) {
        header('Location: ' . BASE_URL . 'admin/chats.php');
        exit;
    }
    
    try {
        $chatDAL = new ChatDAL();
        $chatDAL->updateStatus($conversationId, 'closed');
        $chatDAL->addMessage($conversationId, 'bot', 'This conversation has been closed. Start a new chat if you need further help.');
    } catch (Exception $e) {}
    
    header('Location: ' . BASE_URL . 'admin/chats.php');
    exit;
}

// --- Helper Functions ---

function containsAny($haystack, $needles) {
    foreach ($needles as $needle) {
        if (strpos($haystack, $needle) !== false) return true;
    }
    return false;
}

function extractSearchTerms($message) {
    // Remove common filler words
    $fillers = ['i', 'am', 'looking', 'for', 'a', 'an', 'the', 'want', 'need', 'find', 'show', 'me', 'some', 'any', 'please', 'can', 'you', 'do', 'have', 'is', 'there'];
    $words = explode(' ', $message);
    $meaningful = array_filter($words, function($w) use ($fillers) {
        return strlen($w) > 2 && !in_array($w, $fillers);
    });
    return implode(' ', $meaningful);
}
