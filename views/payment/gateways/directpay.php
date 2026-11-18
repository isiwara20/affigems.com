<?php
/** @var array<string,mixed> $checkoutContext */
/** @var int $orderId */
/** @var string $orderNumber */
?>
<style>
    #card_container { min-height: 360px; }
    .dp-pay-actions { text-align:center; margin-top:16px; }
    .dp-btn-pay {
        display:inline-flex; align-items:center; gap:8px;
        padding:13px 28px; background:#D49A0A;
        color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer;
    }
    .dp-btn-pay:disabled { opacity:.6; cursor:not-allowed; }
    .dp-status { margin-top:14px; font-size:12px; color:#666; text-align:center; min-height:18px; }
    .dp-error {
        display:none; margin-top:16px; padding:14px; border-radius:10px;
        background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:13px; text-align:center;
    }
    .dp-error .retry-link {
        display:inline-block; margin-top:10px; color:#D4A017; font-weight:600; cursor:pointer; text-decoration:none;
    }
</style>

<h3>Card Payment</h3>
<div id="card_container">
    <div class="payment-loading" id="dpLoading" style="text-align:center; padding:60px 20px; color:#666; font-size:13px;">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#0d1137; margin-bottom:12px; display:block;"></i>
        Loading secure payment form...
    </div>
</div>

<div class="dp-pay-actions">
    <button type="button" class="dp-btn-pay" id="dpPayBtn" style="display:none;">
        <i class="fas fa-lock"></i> Pay securely with card
    </button>
    <div class="dp-status" id="dpStatus"></div>
    <div class="dp-error" id="dpError">
        <span id="dpErrorText">Payment could not be completed.</span><br>
        <a class="retry-link" href="<?php echo BASE_URL; ?>payment.php">Try again</a>
        &nbsp;·&nbsp;
        <a class="retry-link" href="<?php echo BASE_URL; ?>payment.php?change_gateway=1">Change method</a>
    </div>
</div>

<script>
const encoded_payload = <?php echo json_encode($checkoutContext['encoded_payload'] ?? ''); ?>;
const signature = <?php echo json_encode($checkoutContext['signature'] ?? ''); ?>;
const dpStage = <?php echo json_encode($checkoutContext['stage'] ?? 'PROD'); ?>;
const orderId = <?php echo (int)$orderId; ?>;
const orderNumber = <?php echo json_encode($orderNumber); ?>;

let dp = null;
let checkoutStarted = false;

function setStatus(msg) {
    const el = document.getElementById('dpStatus');
    if (el) el.textContent = msg || '';
}

function showError(msg) {
    const box = document.getElementById('dpError');
    const text = document.getElementById('dpErrorText');
    if (text) text.textContent = msg || 'Payment could not be completed.';
    if (box) box.style.display = 'block';
}

function hideLoading() {
    const el = document.getElementById('dpLoading');
    if (el) el.style.display = 'none';
}

function isSuccessPayload(data) {
    if (!data) return false;
    const status = String(data.status || data.payment_status || '').toUpperCase();
    return status === 'SUCCESS' || status === 'COMPLETED' || status === 'APPROVED'
        || status === 'CAPTURED' || status === 'PAID'
        || data.result === 1 || data.success === true;
}

function isFailedPayload(data) {
    if (!data) return false;
    const status = String(data.status || data.payment_status || '').toUpperCase();
    return ['FAILED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'ERROR', 'REJECTED'].includes(status);
}

function handleSuccess() {
    setStatus('Payment confirmed. Redirecting...');
    fetch(<?php echo json_encode(BASE_URL . 'controllers/PaymentCallback.php'); ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_paid&order_id=' + orderId
    }).finally(() => {
        window.location.href = <?php echo json_encode(BASE_URL . 'order-success.php'); ?>;
    });
}

function handleFailure(message) {
    checkoutStarted = false;
    const btn = document.getElementById('dpPayBtn');
    if (btn) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
    }
    setStatus('');
    showError(message || 'Payment could not be completed. Please try again.');
}

function isSuccessStatus(status) {
    const s = String(status || '').toUpperCase();
    return ['SUCCESS', 'COMPLETED', 'APPROVED', 'CAPTURED', 'PAID'].includes(s);
}

function isFailedStatus(status) {
    const s = String(status || '').toUpperCase();
    return ['FAILED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'ERROR', 'REJECTED'].includes(s);
}

function queryPaymentStatusWithRetry(orderIdValue, attempt, onFail) {
    fetch(<?php echo json_encode(BASE_URL . 'controllers/PaymentQuery.php'); ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + encodeURIComponent(orderIdValue) + '&gateway=directpay'
    }).then(r => r.json()).then(result => {
        if (isSuccessStatus(result.status)) {
            handleSuccess();
        } else if (isFailedStatus(result.status)) {
            onFail('Your payment was declined or cancelled.');
        } else if (attempt < 3) {
            setTimeout(() => queryPaymentStatusWithRetry(orderIdValue, attempt + 1, onFail), 2000);
        } else {
            onFail('We could not verify the payment. If money was deducted, contact support with order ' + orderNumber + '.');
        }
    }).catch(() => {
        if (attempt < 3) {
            setTimeout(() => queryPaymentStatusWithRetry(orderIdValue, attempt + 1, onFail), 2000);
        } else {
            onFail('Could not verify payment status. Please check your card statement or try again.');
        }
    });
}

function startCheckout() {
    if (checkoutStarted || !dp) return;
    checkoutStarted = true;

    const btn = document.getElementById('dpPayBtn');
    if (btn) {
        btn.disabled = true;
        btn.style.display = 'none';
    }

    setStatus('Opening secure card form...');
    hideLoading();

    dp.doInContainerCheckout().then(data => {
        if (isSuccessPayload(data)) {
            handleSuccess();
            return;
        }
        if (isFailedPayload(data)) {
            handleFailure('Your payment was declined or cancelled.');
            return;
        }
        setStatus('Verifying payment...');
        queryPaymentStatusWithRetry(orderNumber, 0, handleFailure);
    }).catch(err => {
        console.warn('DirectPay checkout closed or errored:', err);
        setStatus('Verifying payment status...');
        // SDK often rejects when user closes modal — verify with API before failing.
        queryPaymentStatusWithRetry(orderNumber, 0, function(msg) {
            handleFailure(msg || 'Payment was cancelled or could not be completed.');
        });
    });
}

function initDirectPay() {
    if (typeof DirectPayIpg === 'undefined') {
        hideLoading();
        showError('Payment form failed to load. Check your internet connection and refresh.');
        return;
    }

    try {
        dp = new DirectPayIpg.Init({
            signature: signature,
            dataString: encoded_payload,
            stage: dpStage,
            container: 'card_container'
        });
    } catch (e) {
        console.error('DirectPay init error:', e);
        hideLoading();
        showError('Unable to initialize card payment. Please verify DirectPay settings in admin.');
        return;
    }

    const btn = document.getElementById('dpPayBtn');
    if (btn) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
        btn.addEventListener('click', startCheckout);
    }

    hideLoading();
    setStatus('Click the button below to enter your card details.');

    // Auto-start after short delay (user may also click button).
    setTimeout(startCheckout, 600);
}

window.addEventListener('load', function() {
    if (typeof DirectPayIpg !== 'undefined') {
        initDirectPay();
        return;
    }
    let waited = 0;
    const timer = setInterval(function() {
        waited += 200;
        if (typeof DirectPayIpg !== 'undefined') {
            clearInterval(timer);
            initDirectPay();
        } else if (waited >= 8000) {
            clearInterval(timer);
            hideLoading();
            showError('Payment SDK did not load. Please refresh or try another payment method.');
        }
    }, 200);
});
</script>
