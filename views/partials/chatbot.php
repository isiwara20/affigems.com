<?php $chatbotLogoUrl = asset_image_url('assets/images/logo.png'); ?>
<!-- Floating WhatsApp Button -->
<a href="<?php echo htmlspecialchars(STORE_WHATSAPP_URL); ?>?text=<?php echo rawurlencode('Hello Affinity Gems & Jewels, I would like to inquire about your Sri Lankan sapphires and fine gemstones.'); ?>" 
   target="_blank" 
   rel="noopener noreferrer" 
   class="whatsapp-floating-trigger" 
   id="whatsappFloatingTrigger"
   aria-label="Chat on WhatsApp"
   title="Chat with us on WhatsApp">
    <i class="fab fa-whatsapp"></i>
    <span class="whatsapp-floating-tooltip">Chat on WhatsApp</span>
</a>

<!-- Chat Widget -->
<div class="chatbot-trigger" id="chatbotTrigger" onclick="toggleChatbot()" title="Chat with Assistant" aria-label="Open chat assistant">
    <i class="fas fa-comments"></i>
    <span class="chatbot-badge" id="chatbotBadge" style="display:none;">0</span>
</div>

<div class="chatbot-widget" id="chatbotWidget">
    <!-- Header -->
    <div class="chatbot-header">
        <div class="chatbot-header-info">
            <div class="chatbot-avatar">
                <img src="<?php echo htmlspecialchars($chatbotLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Affinity Assistant">
            </div>
            <div class="chatbot-header-text">
                <h4><?php echo htmlspecialchars(SITE_NAME); ?></h4>
                <span class="chatbot-status"><i class="fas fa-circle"></i> Online</span>
            </div>
        </div>
        <div class="chatbot-header-actions">
            <button onclick="toggleChatbot()" class="chatbot-close-btn" aria-label="Close chat"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="chatbot-messages" id="chatbotMessages"></div>

    <!-- Quick Replies -->
    <div class="chatbot-quick-replies" id="chatbotQuickReplies"></div>

    <!-- Input Area -->
    <div class="chatbot-input-area">
        <input type="text" id="chatbotInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter')sendChatMessage()">
        <button class="chatbot-send-btn" onclick="sendChatMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
var chatConversationId = null;
var chatLastMessageId = 0;
var chatPollInterval = null;
var chatIsOpen = false;
var chatRenderedIds = {};

function toggleChatbot() {
    var widget = document.getElementById('chatbotWidget');
    var trigger = document.getElementById('chatbotTrigger');
    chatIsOpen = !chatIsOpen;
    
    if (chatIsOpen) {
        widget.classList.add('open');
        trigger.classList.add('hidden');
        if (!chatConversationId) startChat();
        document.getElementById('chatbotInput').focus();
    } else {
        widget.classList.remove('open');
        trigger.classList.remove('hidden');
    }
}

function startChat() {
    fetch('<?php echo BASE_URL; ?>controllers/ChatController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=start'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            chatConversationId = data.conversation_id;
            renderMessages(data.messages);
            startPolling();
        }
    });
}

function sendChatMessage() {
    var input = document.getElementById('chatbotInput');
    var msg = input.value.trim();
    if (!msg || !chatConversationId) return;
    
    input.value = '';
    appendMessage('user', msg);
    hideQuickReplies();
    showTyping();
    
    fetch('<?php echo BASE_URL; ?>controllers/ChatController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=send&conversation_id=' + chatConversationId + '&message=' + encodeURIComponent(msg)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Add a typing delay for natural feel
        setTimeout(function() {
            hideTyping();
            if (data.success && data.bot_response) {
                var resp = data.bot_response;
                if (resp.message) appendMessage('bot', resp.message);
                if (resp.categories && resp.categories.length > 0) {
                    showCategories(resp.categories);
                }
                if (resp.products && resp.products.length > 0) {
                    appendProducts(resp.products);
                }
                if (resp.quick_replies && resp.quick_replies.length > 0) {
                    showQuickReplies(resp.quick_replies);
                }
                if (resp.last_id) chatLastMessageId = resp.last_id;
                scrollToBottom();
            }
        }, 1200);
    });
}

function sendQuickReply(text) {
    document.getElementById('chatbotInput').value = text;
    sendChatMessage();
}

function selectCategory(categorySlug, categoryName) {
    hideQuickReplies();
    appendMessage('user', categoryName);
    showTyping();
    
    fetch('<?php echo BASE_URL; ?>controllers/ChatController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=send&conversation_id=' + chatConversationId + '&message=' + encodeURIComponent('show category: ' + categorySlug)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        setTimeout(function() {
            hideTyping();
            if (data.success && data.bot_response) {
                var resp = data.bot_response;
                if (resp.message) appendMessage('bot', resp.message);
                if (resp.products && resp.products.length > 0) {
                    appendProducts(resp.products);
                }
                if (resp.quick_replies && resp.quick_replies.length > 0) {
                    showQuickReplies(resp.quick_replies);
                }
                if (resp.last_id) chatLastMessageId = resp.last_id;
                scrollToBottom();
            }
        }, 1200);
    });
}

function appendMessage(sender, text) {
    var container = document.getElementById('chatbotMessages');
    var div = document.createElement('div');
    div.className = 'chat-msg chat-msg-' + sender;
    
    var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    
    if (sender === 'bot' || sender === 'admin') {
        div.innerHTML = '<div class="chat-msg-avatar"><img src="<?php echo htmlspecialchars($chatbotLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Bot"></div><div class="chat-msg-content"><div class="chat-bubble chat-bubble-bot">' + formatMessage(text) + '</div><span class="chat-time">' + time + '</span></div>';
    } else {
        div.innerHTML = '<div class="chat-msg-content"><div class="chat-bubble chat-bubble-user">' + escapeHtml(text) + '</div><span class="chat-time">' + time + '</span></div>';
    }
    
    container.appendChild(div);
    scrollToBottom();
}

function scrollToBottom() {
    var container = document.getElementById('chatbotMessages');
    setTimeout(function() { container.scrollTop = container.scrollHeight; }, 50);
}

function appendProducts(products) {
    var container = document.getElementById('chatbotMessages');
    var div = document.createElement('div');
    div.className = 'chat-msg chat-msg-bot';
    
    var html = '<div class="chat-msg-avatar"><img src="<?php echo htmlspecialchars($chatbotLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Bot"></div><div class="chat-msg-content"><div class="chat-products-list">';
    products.forEach(function(p) {
        var imgUrl = '<?php echo BASE_URL; ?>' + p.image;
        var productUrl = '<?php echo BASE_URL; ?>product.php?slug=' + p.slug;
        html += '<a href="' + productUrl + '" class="chat-product-item" target="_blank">';
        html += '<img src="' + imgUrl + '" alt="' + escapeHtml(p.name) + '">';
        html += '<div class="chat-product-item-info">';
        html += '<p class="chat-product-name">' + escapeHtml(p.name) + '</p>';
        html += '<span class="chat-product-price">LKR ' + Number(p.price).toLocaleString() + '</span>';
        html += '</div>';
        html += '<i class="fas fa-chevron-right"></i>';
        html += '</a>';
    });
    html += '</div></div>';
    
    div.innerHTML = html;
    container.appendChild(div);
    scrollToBottom();
}

function showCategories(categories) {
    var container = document.getElementById('chatbotMessages');
    var div = document.createElement('div');
    div.className = 'chat-msg chat-msg-bot';
    
    var html = '<div class="chat-msg-avatar"><img src="<?php echo htmlspecialchars($chatbotLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Bot"></div><div class="chat-msg-content"><div class="chat-categories-list">';
    categories.forEach(function(cat) {
        html += '<div class="chat-category-item" onclick="selectCategory(\'' + cat.slug + '\', \'' + escapeHtml(cat.name) + '\')">';
        html += '<span>' + escapeHtml(cat.name) + '</span>';
        html += '<i class="fas fa-chevron-right"></i>';
        html += '</div>';
    });
    html += '</div></div>';
    
    div.innerHTML = html;
    container.appendChild(div);
    scrollToBottom();
    hideQuickReplies();
}

function showQuickReplies(replies) {
    var container = document.getElementById('chatbotQuickReplies');
    container.innerHTML = '';
    replies.forEach(function(r) {
        var btn = document.createElement('button');
        btn.className = 'chatbot-quick-btn';
        btn.textContent = r;
        btn.onclick = function() { sendQuickReply(r); };
        container.appendChild(btn);
    });
    container.style.display = 'flex';
}

function hideQuickReplies() {
    var container = document.getElementById('chatbotQuickReplies');
    container.innerHTML = '';
    container.style.display = 'none';
}

function showTyping() {
    var container = document.getElementById('chatbotMessages');
    var div = document.createElement('div');
    div.className = 'chat-msg chat-msg-bot';
    div.id = 'chatTyping';
    div.innerHTML = '<div class="chat-msg-avatar"><img src="<?php echo htmlspecialchars($chatbotLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Bot"></div><div class="chat-msg-content"><div class="chat-bubble chat-bubble-bot"><span class="typing-dots"><span></span><span></span><span></span></span></div></div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function hideTyping() {
    var el = document.getElementById('chatTyping');
    if (el) el.remove();
}

function startPolling() {
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(pollMessages, 5000);
}

function pollMessages() {
    if (!chatConversationId || !chatIsOpen) return;
    fetch('<?php echo BASE_URL; ?>controllers/ChatController.php?action=get_messages&conversation_id=' + chatConversationId + '&after_id=' + chatLastMessageId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.messages.length > 0) {
            data.messages.forEach(function(msg) {
                if (msg.id > chatLastMessageId && !chatRenderedIds[msg.id]) {
                    // Only show admin messages via polling (bot/user already shown locally)
                    if (msg.sender === 'admin') {
                        appendMessage('admin', msg.message);
                        chatRenderedIds[msg.id] = true;
                    }
                    chatLastMessageId = parseInt(msg.id);
                }
            });
        }
    });
}

function renderMessages(messages) {
    var container = document.getElementById('chatbotMessages');
    container.innerHTML = '';
    chatRenderedIds = {};
    
    messages.forEach(function(msg) {
        chatRenderedIds[msg.id] = true;
        
        if (msg.sender === 'user') {
            appendMessage('user', msg.message);
        } else {
            appendMessage(msg.sender, msg.message);
            if (msg.message_type === 'products' && msg.metadata) {
                var meta = typeof msg.metadata === 'string' ? JSON.parse(msg.metadata) : msg.metadata;
                if (meta.products) appendProducts(meta.products);
            }
            if (msg.message_type === 'categories' && msg.metadata) {
                var meta = typeof msg.metadata === 'string' ? JSON.parse(msg.metadata) : msg.metadata;
                if (meta.categories) showCategories(meta.categories);
            }
            if ((msg.message_type === 'quick_replies' || msg.message_type === 'products') && msg.metadata) {
                var meta = typeof msg.metadata === 'string' ? JSON.parse(msg.metadata) : msg.metadata;
                if (meta.quick_replies) showQuickReplies(meta.quick_replies);
            }
        }
        chatLastMessageId = Math.max(chatLastMessageId, parseInt(msg.id));
    });
}

function formatMessage(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
