<?php
/**
 * Order Success Page
 */
require_once __DIR__ . '/config/app.php';

$orderNumber = $_SESSION['last_order_number'] ?? '';
$orderId = $_SESSION['last_order_id'] ?? '';
$successToken = $_SESSION['order_success_token'] ?? '';

if ($successToken === '' && $orderNumber === '') {
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

unset($_SESSION['last_order_id'], $_SESSION['last_order_number'], $_SESSION['order_success_token']);

$pageTitle = 'Order Confirmed - ' . ADMIN_BRAND;
require_once __DIR__ . '/views/partials/header.php';
?>

<style>
.success-page { padding: 60px 0; text-align: center; min-height: 60vh; display: flex; align-items: center; justify-content: center; }
.success-card { background: #fff; border-radius: 16px; padding: 50px 40px; max-width: 550px; margin: 0 auto; border: 1px solid #eee; box-shadow: 0 10px 40px rgba(0,0,0,0.04); }
.success-icon { width: 80px; height: 80px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 36px; color: #16a34a; }
.success-card h1 { font-size: 22px; font-weight: 700; color: #0d1137; margin-bottom: 8px; }
.success-card .order-num { font-size: 14px; color: #888; margin-bottom: 20px; }
.success-card .order-num strong { color: #0d1137; }
.success-card p { font-size: 13px; color: #666; line-height: 1.7; margin-bottom: 28px; }
.success-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.success-actions a { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
.btn-shop { background: #0d1137; color: #fff; }
.btn-shop:hover { background: #1a1f4e; }
.btn-orders { background: #fff; color: #0d1137; border: 1.5px solid #e4e8f0; }
.btn-orders:hover { border-color: #0d1137; }
.btn-whatsapp-open { background: #25d366; color: #fff; }
.btn-whatsapp-open:hover { background: #1da851; color: #fff; }
.success-card .wa-hint { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; margin-bottom: 22px; font-size: 13px; color: #166534; line-height: 1.6; }
</style>

<section class="success-page">
    <div class="success-card">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h1>Order Placed Successfully!</h1>
        <?php if ($orderNumber): ?>
        <p class="order-num">Order Number: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
        <?php endif; ?>
        <p id="successMessage">Thank you for your purchase! Your order has been confirmed and will be processed shortly. You will receive an email confirmation with your order details.</p>
        <div class="success-actions" id="successActions">
            <a href="<?php echo BASE_URL; ?>shop.php" class="btn-shop" data-turbo="false"><i class="fas fa-store"></i> Continue Shopping</a>
            <a href="<?php echo BASE_URL; ?>my-orders.php" class="btn-orders" data-turbo="false"><i class="fas fa-box"></i> My Orders</a>
        </div>
    </div>
</section>

<script>
(function() {
    var shopUrl = <?php echo json_encode(BASE_URL . 'shop.php'); ?>;
    if (window.history.replaceState) {
        history.replaceState({ orderComplete: true }, document.title, location.href);
    }
    window.addEventListener('popstate', function() {
        window.location.replace(shopUrl);
    });
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.replace(shopUrl);
        }
    });

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            || (navigator.maxTouchPoints > 1 && window.innerWidth < 1024);
    }

    function openWhatsAppLink(appUrl, apiUrl, webUrl) {
        var primary = appUrl || apiUrl || webUrl;
        if (!primary) return false;

        if (isMobileDevice() && appUrl) {
            window.location.href = appUrl;
            return true;
        }

        if (appUrl) {
            var link = document.createElement('a');
            link.href = appUrl;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(function() {
                if (apiUrl) window.open(apiUrl, '_blank', 'noopener,noreferrer');
                else if (webUrl) window.open(webUrl, '_blank', 'noopener,noreferrer');
            }, 1200);
            return true;
        }

        window.open(apiUrl || webUrl, '_blank', 'noopener,noreferrer');
        return true;
    }

    if (sessionStorage.getItem('ezymart_wa_pending') === '1') {
        var appUrl = sessionStorage.getItem('ezymart_wa_app') || '';
        var apiUrl = sessionStorage.getItem('ezymart_wa_api') || '';
        var webUrl = sessionStorage.getItem('ezymart_wa_web') || '';
        sessionStorage.removeItem('ezymart_wa_pending');
        sessionStorage.removeItem('ezymart_wa_app');
        sessionStorage.removeItem('ezymart_wa_api');
        sessionStorage.removeItem('ezymart_wa_web');

        var msg = document.getElementById('successMessage');
        if (msg) {
            msg.insertAdjacentHTML('beforebegin',
                '<div class="wa-hint"><strong>One more step:</strong> Send your order message in WhatsApp so we can confirm it.</div>'
            );
            msg.textContent = 'Your order is saved. Tap the button below to open the WhatsApp app with your order details pre-filled, then tap Send.';
        }

        var actions = document.getElementById('successActions');
        if (actions && (appUrl || apiUrl || webUrl)) {
            var waBtn = document.createElement('a');
            waBtn.href = appUrl || apiUrl || webUrl;
            waBtn.className = 'btn-whatsapp-open';
            waBtn.setAttribute('data-turbo', 'false');
            waBtn.innerHTML = '<i class="fab fa-whatsapp"></i> Open WhatsApp App';
            waBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openWhatsAppLink(appUrl, apiUrl, webUrl);
            });
            actions.insertBefore(waBtn, actions.firstChild);
        }

        if (isMobileDevice()) {
            setTimeout(function() {
                openWhatsAppLink(appUrl, apiUrl, webUrl);
            }, 500);
        }
    }
})();
</script>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
