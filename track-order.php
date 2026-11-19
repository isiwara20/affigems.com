<?php
/**
 * Track Order Page
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/dal/OrderDAL.php';

$pageTitle = "Track Order - " . SITE_NAME;
$order = null;
$items = [];
$searched = false;

if (isset($_GET['order_number']) && !empty($_GET['order_number'])) {
    $searched = true;
    $orderNumber = trim($_GET['order_number']);
    try {
        $orderDAL = new OrderDAL();
        $order = $orderDAL->getByOrderNumber($orderNumber);
        if ($order) {
            $items = $orderDAL->getOrderItems($order['id']);
        }
    } catch (Exception $e) {}
}

require_once __DIR__ . '/views/partials/header.php';
?>

<style>
/* ===== Track Order - ARIGATO.LK Theme ===== */
.track-hero {
    position: relative;
    padding: clamp(40px, 6vw, 56px) 0 clamp(48px, 7vw, 64px);
    background: #001f52;
    overflow: hidden;
    text-align: center;
    color: #fff;
}
.track-hero::before,
.track-hero::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.track-hero::before {
    width: min(400px, 80vw);
    height: min(400px, 80vw);
    top: -160px;
    right: -100px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
}
.track-hero::after {
    width: min(300px, 60vw);
    height: min(300px, 60vw);
    bottom: -120px;
    left: -60px;
    background: radial-gradient(circle, rgba(236,72,153,0.12) 0%, transparent 70%);
}
.track-hero-inner { position: relative; z-index: 1; max-width: 560px; margin: 0 auto; }
.track-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.22);
    backdrop-filter: blur(8px);
    padding: 8px 18px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.track-hero h1 {
    font-size: clamp(1.6rem, 4vw, 2.2rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 10px;
    letter-spacing: -0.02em;
}
.track-hero p {
    font-size: clamp(13px, 2vw, 15px);
    color: rgba(255,255,255,0.88);
    line-height: 1.65;
    margin: 0;
}

.track-section {
    padding: 0 0 60px;
    margin-top: -28px;
    position: relative;
    z-index: 2;
}
.track-container { max-width: 760px; margin: 0 auto; }

/* Search Form */
.track-search {
    background: #fff;
    border: 1px solid #dbe5f5;
    border-radius: 16px;
    padding: 28px 30px;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 40, 104, 0.1);
}
.track-search-form { display: flex; gap: 12px; }
.track-search-form input {
    flex: 1;
    padding: 15px 20px;
    border: 1.5px solid #dbe5f5;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    text-transform: uppercase;
    transition: all 0.3s;
    background: #f8fafd;
}
.track-search-form input:focus {
    border-color: #003b94;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 40, 104, 0.12);
}
.track-search-form input::placeholder { text-transform: none; color: #aaa; }
.track-search-form button {
    padding: 15px 32px;
    background: #001f52;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(0, 40, 104, 0.25);
    white-space: nowrap;
}
.track-search-form button:hover {
    background: #002868;
    box-shadow: 0 6px 22px rgba(0, 40, 104, 0.35);
    transform: translateY(-1px);
}

/* Cards */
.track-result {
    background: #fff;
    border: 1px solid #dbe5f5;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 40, 104, 0.06);
}
.track-order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e8f0fe;
}
.track-order-header h2 { font-size: 18px; font-weight: 700; color: #002868; }
.track-order-header .order-date {
    font-size: 12px;
    color: #002868;
    background: #e8f0fe;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 500;
}

/* Status Timeline */
.status-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 40px 0 50px;
    padding: 0 10px;
}
.status-timeline::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 50px;
    right: 50px;
    height: 4px;
    background: #f4f7fc;
    z-index: 1;
    border-radius: 4px;
}
.status-timeline::after {
    content: '';
    position: absolute;
    top: 24px;
    left: 50px;
    height: 4px;
    background: #001f52;
    z-index: 2;
    border-radius: 4px;
    animation: progressGrow 1.2s ease-out forwards;
}
.status-timeline.step-1::after { width: 0%; }
.status-timeline.step-2::after { width: 33%; }
.status-timeline.step-3::after { width: 66%; }
.status-timeline.step-4::after { width: calc(100% - 100px); }
.status-timeline.cancelled::after {
    background: #ef4444;
    width: calc(100% - 100px);
}

@keyframes progressGrow { from { width: 0%; } }

.timeline-step { text-align: center; position: relative; z-index: 3; flex: 1; }
.timeline-dot {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f8fafd;
    border: 3px solid #dbe5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 16px;
    color: #c4b5fd;
    transition: all 0.4s ease;
    box-shadow: 0 2px 8px rgba(0, 40, 104, 0.06);
}
.timeline-step.active .timeline-dot {
    background: #001f52;
    border-color: #003b94;
    color: #fff;
    box-shadow: 0 4px 16px rgba(0, 40, 104, 0.35);
    animation: dotPulse 0.6s ease-out;
}
.timeline-step.active .timeline-label { color: #002868; font-weight: 600; }
.timeline-step.cancelled .timeline-dot {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
}
.timeline-label { font-size: 12px; color: #999; font-weight: 500; }

@keyframes dotPulse {
    0% { transform: scale(0.5); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

.timeline-step:nth-child(1).active .timeline-dot { animation-delay: 0s; }
.timeline-step:nth-child(2).active .timeline-dot { animation-delay: 0.2s; }
.timeline-step:nth-child(3).active .timeline-dot { animation-delay: 0.4s; }
.timeline-step:nth-child(4).active .timeline-dot { animation-delay: 0.6s; }

/* Order Details */
.track-details { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
.track-detail-card {
    background: #f8fafd;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #dbe5f5;
}
.track-detail-card h4 {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #003b94;
    margin-bottom: 12px;
    font-weight: 700;
}
.track-detail-card p { font-size: 13px; color: #333; line-height: 1.7; }
.track-detail-card p strong { color: #002868; }

/* Order Items */
.track-items h3 {
    font-size: 14px;
    font-weight: 600;
    color: #002868;
    margin-bottom: 14px;
    padding-top: 16px;
    border-top: 1px solid #e8f0fe;
}
.track-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border: 1px solid #dbe5f5;
    border-radius: 10px;
    margin-bottom: 10px;
    background: #fff;
    transition: border-color 0.2s, background 0.2s;
}
.track-item:hover { border-color: #c2d4f2; background: #f8fafd; }
.track-item-img {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #dbe5f5;
    background: #f8fafd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #c4b5fd;
    font-size: 18px;
    flex-shrink: 0;
}
.track-item-info { flex: 1; }
.track-item-info h4 { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 2px; }
.track-item-info span { font-size: 11px; color: #888; }
.track-item-price { font-size: 14px; font-weight: 700; color: #002868; white-space: nowrap; }

/* Empty / Info State */
.track-info-state { text-align: center; padding: 40px 28px; }
.track-info-icon {
    width: 72px;
    height: 72px;
    background: #f4f7fc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    border: 2px solid #dbe5f5;
}
.track-info-icon i { font-size: 28px; color: #003b94; }
.track-info-state h3 { font-size: 16px; font-weight: 700; color: #002868; margin-bottom: 8px; }
.track-info-state > p {
    font-size: 13px;
    color: #888;
    max-width: 400px;
    margin: 0 auto 28px;
    line-height: 1.7;
}
.track-info-steps { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
.track-info-step { text-align: center; }
.track-info-step-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 18px;
}
.track-info-step-icon.purple { background: #e8f0fe; color: #003b94; }
.track-info-step-icon.violet { background: #f4f7fc; color: #002868; }
.track-info-step-icon.lavender { background: #f8fafd; color: #003b94; border: 1px solid #dbe5f5; }
.track-info-step-icon.done { background: #ecfdf5; color: #16a34a; }
.track-info-step span { font-size: 11px; color: #666; font-weight: 500; }

.track-help-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 24px;
    font-size: 12px;
    font-weight: 600;
    color: #003b94;
    text-decoration: none;
}
.track-help-link:hover { color: #002868; }

/* Testimonial */
.track-testimonial { margin-top: 20px; }
.track-testimonial h3 {
    font-size: 15px;
    font-weight: 600;
    color: #002868;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.track-testimonial h3 i { color: #003b94; }
.track-testimonial > p { font-size: 12px; color: #888; margin-bottom: 18px; }
.track-testimonial textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #dbe5f5;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    outline: none;
    resize: vertical;
    background: #f8fafd;
    transition: all 0.3s;
}
.track-testimonial textarea:focus {
    border-color: #003b94;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 40, 104, 0.1);
}
.track-testimonial label { font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 6px; }
.btn-track-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    background: #001f52;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-track-submit:hover {
    background: #002868;
    box-shadow: 0 4px 16px rgba(0, 40, 104, 0.25);
    transform: translateY(-1px);
}
.track-success-msg {
    background: #ecfdf5;
    color: #16a34a;
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 13px;
    text-align: center;
}

/* Testimonial Stars */
.testimonial-stars { display: flex; flex-direction: row-reverse; gap: 3px; justify-content: flex-end; }
.testimonial-stars input { display: none; }
.testimonial-stars label { cursor: pointer; font-size: 1.4rem; color: #ddd; transition: color 0.15s; }
.testimonial-stars input:checked ~ label,
.testimonial-stars label:hover,
.testimonial-stars label:hover ~ label { color: #f5a623; }

/* Not Found */
.track-not-found { text-align: center; padding: 40px; }
.track-not-found i { font-size: 3rem; color: #c2d4f2; margin-bottom: 14px; }
.track-not-found h3 { font-size: 16px; color: #002868; margin-bottom: 6px; }
.track-not-found p { font-size: 13px; color: #888; }

@media (max-width: 768px) {
    .track-search-form { flex-direction: column; }
    .track-details { grid-template-columns: 1fr; }
    .track-order-header { flex-direction: column; align-items: flex-start; gap: 8px; }
    .track-hero h1 { font-size: 1.4rem; }
    .track-result { padding: 22px 18px; }
    .track-search { padding: 22px 18px; }
    .track-item { padding: 12px; }
    .track-item-info h4 { font-size: 12px; }
    .status-timeline { margin: 28px 0 36px; padding: 0; }
    .status-timeline::before { top: 20px; left: 30px; right: 30px; }
    .status-timeline::after { top: 20px; left: 30px; }
    .timeline-dot { width: 40px; height: 40px; font-size: 13px; }
    .timeline-label { font-size: 10px; }
    .track-info-steps { gap: 16px; }
}

@media (max-width: 480px) {
    .track-section { padding-bottom: 40px; }
    .track-search-form button {
        width: 100%;
        justify-content: center;
        padding: 15px;
    }
    .track-search-form { align-items: stretch; }
    .timeline-dot { width: 34px; height: 34px; font-size: 11px; }
    .timeline-label { font-size: 9px; }
    .status-timeline::before { top: 16px; left: 20px; right: 20px; }
    .status-timeline::after { top: 16px; left: 20px; }
}
</style>

<!-- Breadcrumb -->
<div class="product-page-breadcrumb">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span>Track Order</span>
    </div>
</div>

<!-- Hero -->
<header class="track-hero">
    <div class="container">
        <div class="track-hero-inner">
            <span class="track-hero-badge"><i class="fas fa-shipping-fast"></i> Order Tracking</span>
            <h1>Track Your Order</h1>
            <p>Enter your order number to check delivery status in real-time</p>
        </div>
    </div>
</header>

<section class="track-section">
    <div class="container">
        <div class="track-container">
            <!-- Search -->
            <div class="track-search">
                <form method="GET" class="track-search-form">
                    <input type="text" name="order_number" placeholder="Enter Order Number (e.g. AB250612XXXX)" value="<?php echo htmlspecialchars($_GET['order_number'] ?? ''); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i> Track</button>
                </form>
            </div>

            <?php if (!$searched): ?>
            <!-- Default Info Before Search -->
            <div class="track-result track-info-state">
                <div class="track-info-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Where's my order?</h3>
                <p>Enter your order number above to track your delivery status in real-time. You can find your order number in the confirmation email or your account orders page.</p>
                <div class="track-info-steps">
                    <div class="track-info-step">
                        <div class="track-info-step-icon purple"><i class="fas fa-box"></i></div>
                        <span>Order Placed</span>
                    </div>
                    <div class="track-info-step">
                        <div class="track-info-step-icon violet"><i class="fas fa-cog"></i></div>
                        <span>Processing</span>
                    </div>
                    <div class="track-info-step">
                        <div class="track-info-step-icon lavender"><i class="fas fa-shipping-fast"></i></div>
                        <span>Shipped</span>
                    </div>
                    <div class="track-info-step">
                        <div class="track-info-step-icon done"><i class="fas fa-check-circle"></i></div>
                        <span>Delivered</span>
                    </div>
                </div>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <a href="<?php echo BASE_URL; ?>my-orders.php" class="track-help-link">
                    <i class="fas fa-list-alt"></i> View all orders in My Account
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($searched && $order): ?>
            <!-- Order Found -->
            <div class="track-result">
                <div class="track-order-header">
                    <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                    <span class="order-date">Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                </div>

                <!-- Status Timeline -->
                <?php
                $statusSteps = ['pending' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4, 'cancelled' => 0];
                $currentStep = $statusSteps[$order['order_status']] ?? 1;
                $isCancelled = $order['order_status'] === 'cancelled';
                $stepClass = $isCancelled ? 'cancelled' : 'step-' . $currentStep;
                ?>
                <div class="status-timeline <?php echo $stepClass; ?>">
                    <div class="timeline-step <?php echo $currentStep >= 1 && !$isCancelled ? 'active' : ''; ?> <?php echo $isCancelled ? 'cancelled' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-clipboard-check"></i></div>
                        <span class="timeline-label">Order Placed</span>
                    </div>
                    <div class="timeline-step <?php echo $currentStep >= 2 && !$isCancelled ? 'active' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-cog"></i></div>
                        <span class="timeline-label">Processing</span>
                    </div>
                    <div class="timeline-step <?php echo $currentStep >= 3 && !$isCancelled ? 'active' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-truck"></i></div>
                        <span class="timeline-label">Shipped</span>
                    </div>
                    <div class="timeline-step <?php echo $currentStep >= 4 && !$isCancelled ? 'active' : ''; ?>">
                        <div class="timeline-dot"><i class="fas fa-check-circle"></i></div>
                        <span class="timeline-label">Delivered</span>
                    </div>
                </div>

                <?php if ($isCancelled): ?>
                <div style="text-align:center; background:#fef2f2; padding:14px; border-radius:10px; margin-bottom:20px;">
                    <p style="color:#ef4444; font-size:13px; font-weight:600;"><i class="fas fa-times-circle"></i> This order has been cancelled.</p>
                </div>
                <?php endif; ?>

                <!-- Details -->
                <div class="track-details">
                    <div class="track-detail-card">
                        <h4>Delivery Address</h4>
                        <p>
                            <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                            <?php echo htmlspecialchars($order['address_1']); ?><br>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['district']); ?>
                        </p>
                    </div>
                    <div class="track-detail-card">
                        <h4>Order Info</h4>
                        <p>
                            <strong>Payment:</strong> <?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card Payment'; ?><br>
                            <strong>Status:</strong> <?php echo ucfirst($order['payment_status']); ?><br>
                            <strong>Total:</strong> LKR <?php echo number_format($order['total'], 0); ?>
                        </p>
                    </div>
                </div>

                <!-- Items -->
                <div class="track-items">
                    <h3>Order Items</h3>
                    <?php foreach ($items as $item): ?>
                    <div class="track-item">
                        <div class="track-item-img"><i class="fas fa-tshirt"></i></div>
                        <div class="track-item-info">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <span>Qty: <?php echo $item['quantity']; ?></span>
                        </div>
                        <span class="track-item-price">LKR <?php echo number_format($item['total'], 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Testimonial / Service Review -->
            <?php
            $testimonialSuccess = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testimonial_action']) && $_POST['testimonial_action'] === 'submit') {
                require_once __DIR__ . '/dal/TestimonialDAL.php';
                $tRating = (int)($_POST['t_rating'] ?? 5);
                $tMessage = trim($_POST['t_message'] ?? '');
                $tName = htmlspecialchars($order['full_name']);
                $tEmail = $order['email'] ?? '';
                $tUserId = $order['user_id'];
                $tOrderNum = $order['order_number'];
                
                if (!empty($tMessage)) {
                    $testimonialDAL = new TestimonialDAL();
                    $testimonialDAL->create($tUserId, $tName, $tEmail, $tRating, $tMessage, $tOrderNum);
                    $testimonialSuccess = true;
                }
            }
            ?>
            <div class="track-result track-testimonial">
                <h3><i class="fas fa-comment-dots"></i> Share Your Experience</h3>
                <p>How was your experience with <?php echo SITE_NAME; ?>? Your feedback helps us improve!</p>
                
                <?php if ($testimonialSuccess): ?>
                <div class="track-success-msg">
                    <i class="fas fa-check-circle"></i> Thank you! Your testimonial has been submitted and is pending approval.
                </div>
                <?php else: ?>
                <form method="POST" action="<?php echo BASE_URL; ?>track-order.php?order_number=<?php echo htmlspecialchars($order['order_number']); ?>">
                    <input type="hidden" name="testimonial_action" value="submit">
                    
                    <div style="margin-bottom:14px;">
                        <label>Your Rating</label>
                        <div class="testimonial-stars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="t_rating" value="<?php echo $i; ?>" id="tstar<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                            <label for="tstar<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label>Your Message</label>
                        <textarea name="t_message" rows="3" required placeholder="Tell us about your experience..."></textarea>
                    </div>

                    <button type="submit" class="btn-track-submit">
                        <i class="fas fa-paper-plane"></i> Submit Testimonial
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <?php elseif ($searched && !$order): ?>
            <!-- Not Found -->
            <div class="track-result">
                <div class="track-not-found">
                    <i class="fas fa-search"></i>
                    <h3>Order Not Found</h3>
                    <p>We couldn't find an order with that number. Please check and try again.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
