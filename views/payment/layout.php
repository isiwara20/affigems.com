<?php
/** @var string $pageTitle */
/** @var string $gatewayView */
/** @var array<string,mixed> $checkoutContext */
/** @var PaymentGatewayInterface $gateway */
/** @var int $orderId */
/** @var float $baseAmount */
/** @var float $surcharge */
/** @var float $finalAmount */
/** @var array<string,mixed> $paymentInfo */
/** @var string $orderNumber */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png" sizes="64x64">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png" sizes="180x180">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f8; min-height: 100vh; padding-bottom: 60px; }
        .payment-header { background: #0d1137; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .payment-header-left { display: flex; align-items: center; gap: 14px; }
        .payment-header-left img { height: 40px; }
        .payment-secure { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #a0a5c8; font-weight: 500; }
        .payment-secure i { font-size: 14px; color: #16a34a; }
        .payment-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .payment-summary, .card-form-wrap { background: #fff; border-radius: 16px; padding: 36px; border: 1px solid #eee; height: fit-content; }
        .payment-summary h3, .card-form-wrap h3 { font-size: 18px; font-weight: 700; color: #0d1137; margin-bottom: 28px; }
        .payment-detail-group { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
        .payment-detail-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .payment-detail-value { font-size: 14px; font-weight: 600; color: #0d1137; }
        .payment-total-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; margin-top: 10px; }
        .payment-total-value { font-size: 26px; font-weight: 800; color: #e8600a; }
        .gateway-badge { display:inline-block; margin-top:10px; padding:6px 12px; background:#eff6ff; color:#2563eb; border-radius:20px; font-size:11px; font-weight:600; }
        .payment-back { grid-column: 1 / -1; text-align: center; margin-top: 10px; }
        .payment-back a { color: #666; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .summary-line { display:flex; justify-content:space-between; font-size:13px; padding:6px 0; color:#555; }
        .summary-line.surcharge { color:#b45309; }
        .change-gateway { display:inline-block; margin-top:12px; font-size:12px; color:#E5A91A; text-decoration:none; }
        .card-form-wrap #card_container { min-height: 360px; }
        @media (max-width: 768px) { .payment-container { grid-template-columns: 1fr; margin: 20px auto; } }
    </style>
    <?php if (($checkoutContext['mode'] ?? '') === 'inline_js' && !empty($checkoutContext['sdk_url'])): ?>
    <script src="<?php echo htmlspecialchars($checkoutContext['sdk_url']); ?>"></script>
    <?php endif; ?>
</head>
<body>
    <div class="payment-header">
        <div class="payment-header-left">
            <img src="<?php echo asset_image_url('assets/images/logobottom.png'); ?>" alt="<?php echo htmlspecialchars(ADMIN_BRAND); ?>">
        </div>
        <div class="payment-secure">
            <i class="fas fa-shield-alt"></i> Secure Payment via <?php echo htmlspecialchars($gateway->getLabel()); ?>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-summary">
            <h3>Payment Summary</h3>
            <div class="payment-detail-group">
                <div class="payment-detail-label">Order Number</div>
                <div class="payment-detail-value"><?php echo htmlspecialchars($orderNumber); ?></div>
            </div>
            <div class="payment-detail-group">
                <div class="payment-detail-label">Customer Name</div>
                <div class="payment-detail-value"><?php echo htmlspecialchars(trim(($paymentInfo['fname'] ?? '') . ' ' . ($paymentInfo['lname'] ?? ''))); ?></div>
            </div>
            <div class="payment-detail-group">
                <div class="payment-detail-label">Customer Email</div>
                <div class="payment-detail-value"><?php echo htmlspecialchars($paymentInfo['email'] ?? ''); ?></div>
            </div>
            <div>
                <?php
                $baseAmount = (float)($_SESSION['payment_base_amount'] ?? $finalAmount);
                $surcharge = (float)($_SESSION['payment_surcharge'] ?? 0);
                ?>
                <?php if ($surcharge > 0): ?>
                <div class="summary-line"><span>Order amount</span><span>LKR <?php echo number_format($baseAmount, 2); ?></span></div>
                <div class="summary-line surcharge"><span>Payment fee (<?php echo htmlspecialchars($gateway->getLabel()); ?>)</span><span>LKR <?php echo number_format($surcharge, 2); ?></span></div>
                <?php endif; ?>
                <div class="payment-total-label">Total Payable</div>
                <div class="payment-total-value">LKR <?php echo number_format($finalAmount, 2); ?></div>
                <span class="gateway-badge"><?php echo htmlspecialchars($gateway->getLabel()); ?></span>
                <a href="<?php echo BASE_URL; ?>payment.php?change_gateway=1" class="change-gateway"><i class="fas fa-exchange-alt"></i> Change payment method</a>
            </div>
        </div>

        <div class="card-form-wrap">
            <?php require $gatewayView; ?>
        </div>

        <div class="payment-back">
            <a href="<?php echo BASE_URL; ?>checkout.php"><i class="fas fa-arrow-left"></i> Back to Checkout</a>
        </div>
    </div>

    <div style="position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e8ecf1; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; background: #fff; z-index: 10;">
        <p style="font-size: 12px; color: #6b7099;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(ADMIN_BRAND); ?>. All rights reserved.</p>
        <p style="font-size: 12px; color: #6b7099;">Curated quality products &amp; worldwide delivery</p>
    </div>
</body>
</html>
