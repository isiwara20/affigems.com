<?php
/** @var int $orderId */
/** @var float $baseAmount */
/** @var string $orderNumber */
/** @var array<string,mixed> $paymentInfo */
/** @var string[] $availableGateways */
/** @var array<string,string> $gatewayLabels */
/** @var array<string,string> $gatewayDescriptions */

$gatewayBranding = ezymart_payment_gateway_branding();
$cardBrands = ezymart_payment_card_brands();
$gatewayCount = count($availableGateways);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Payment Method - <?php echo htmlspecialchars(ADMIN_BRAND); ?></title>
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png" sizes="64x64">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png" sizes="180x180">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #E5A91A;
            --brand-dark: #D4A017;
            --ink: #0d1137;
            --muted: #6b7099;
            --line: #e8ecf4;
            --surface: #ffffff;
            --page: #eef2f8;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:var(--page); color:var(--ink); min-height:100vh; }

        .topbar {
            background:#8A6508;
            padding:18px 24px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .topbar img { height:38px; }
        .topbar-badge {
            font-size:12px; color:#c7d2fe;
            display:inline-flex; align-items:center; gap:7px;
            background:rgba(255,255,255,.08); padding:8px 14px; border-radius:999px;
        }
        .topbar-badge i { color:#4ade80; }

        .page { max-width:860px; margin:0 auto; padding:28px 20px 100px; }

        .order-banner {
            background:var(--surface);
            border:1px solid var(--line);
            border-radius:16px;
            padding:20px 22px;
            margin-bottom:22px;
            display:flex; align-items:center; justify-content:space-between;
            gap:14px; flex-wrap:wrap;
        }
        .order-banner h1 { font-size:20px; font-weight:700; margin-bottom:4px; }
        .order-banner p { font-size:12px; color:var(--muted); }
        .order-banner .amount-pill {
            background:#fff7ed; border:1px solid #fed7aa;
            padding:10px 16px; border-radius:12px;
            font-size:12px; font-weight:600; color:#9a3412;
        }
        .order-banner .amount-pill strong { display:block; font-size:17px; color:#c2410c; margin-top:2px; }

        .step-hint {
            font-size:12px; font-weight:600; color:var(--muted);
            margin-bottom:14px; text-transform:uppercase; letter-spacing:.3px;
        }

        /* 2 square blocks per row */
        .gateway-grid {
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:16px;
        }

        .pay-tile {
            position:relative;
            display:flex;
            flex-direction:column;
            background:var(--surface);
            border:2px solid var(--line);
            border-radius:18px;
            overflow:hidden;
            cursor:pointer;
            min-height:280px;
            aspect-ratio:1 / 1;
            max-height:320px;
            transition:border-color .2s, box-shadow .2s, transform .2s;
            box-shadow:0 4px 16px rgba(13,17,55,.05);
        }
        .pay-tile:hover {
            border-color:#b8c9ef;
            transform:translateY(-3px);
            box-shadow:0 12px 28px rgba(0,82,212,.12);
        }
        .pay-tile.selected {
            border-color:var(--brand);
            box-shadow:0 14px 32px rgba(0,82,212,.18);
        }
        .pay-tile input { display:none; }

        .tile-accent { height:4px; flex-shrink:0; }

        .tile-check {
            position:absolute; top:12px; right:12px;
            width:26px; height:26px; border-radius:50%;
            border:2px solid #d1d5db; background:#fff;
            display:flex; align-items:center; justify-content:center;
            font-size:11px; color:transparent; z-index:2;
            transition:all .2s;
        }
        .pay-tile.selected .tile-check {
            background:var(--brand); border-color:var(--brand); color:#fff;
        }

        .tile-body {
            flex:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            text-align:center;
            padding:16px 16px 14px;
        }

        .logo-stage {
            width:100%;
            height:72px;
            border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            padding:10px 14px;
            margin-bottom:12px;
            flex-shrink:0;
        }
        .logo-stage img {
            max-width:100%;
            max-height:52px;
            object-fit:contain;
        }

        .tile-title {
            font-size:15px;
            font-weight:700;
            color:var(--ink);
            margin-bottom:4px;
        }
        .tile-desc {
            font-size:11px;
            line-height:1.5;
            color:var(--muted);
            margin-bottom:10px;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
            min-height:33px;
        }

        .type-pill {
            font-size:9px; font-weight:700; letter-spacing:.4px;
            text-transform:uppercase;
            padding:4px 9px; border-radius:999px;
            background:#eff6ff; color:#1d4ed8;
            margin-bottom:12px;
        }
        .type-pill.bnpl { background:#faf5ff; color:#7e22ce; }

        .cards-row {
            display:flex; align-items:center; justify-content:center;
            flex-wrap:wrap; gap:6px;
            margin-top:auto;
            margin-bottom:10px;
            width:100%;
        }
        .cards-row img {
            height:24px; width:auto;
            border-radius:5px;
        }

        .tile-foot {
            width:100%;
            padding-top:10px;
            border-top:1px dashed #e5e7eb;
            margin-top:auto;
        }
        .tile-fee {
            font-size:10px;
            color:var(--muted);
            margin-bottom:4px;
            min-height:14px;
        }
        .tile-fee strong { color:#b45309; }
        .tile-total {
            font-size:17px;
            font-weight:800;
            color:#ea580c;
        }

        .sticky-actions {
            position:fixed; left:0; right:0; bottom:0; z-index:20;
            background:rgba(255,255,255,.95);
            backdrop-filter:blur(10px);
            border-top:1px solid var(--line);
            padding:14px 20px calc(14px + env(safe-area-inset-bottom));
        }
        .sticky-inner { max-width:860px; margin:0 auto; display:flex; gap:12px; }
        .btn-back {
            padding:13px 16px; border-radius:12px;
            border:1.5px solid var(--line); background:#fff;
            color:var(--muted); font-size:13px; font-weight:600;
            text-decoration:none; display:inline-flex; align-items:center; gap:6px;
        }
        .btn-continue {
            flex:1; padding:14px 20px; border:none; border-radius:12px;
            background:var(--brand);
            color:#fff; font-size:14px; font-weight:700; cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-continue:disabled { opacity:.45; cursor:not-allowed; }

        @media (max-width: 600px) {
            .gateway-grid { grid-template-columns:1fr; }
            .pay-tile { aspect-ratio:auto; max-height:none; min-height:260px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <img src="<?php echo asset_image_url('assets/images/logobottom.png'); ?>" alt="<?php echo htmlspecialchars(ADMIN_BRAND); ?>">
        <div class="topbar-badge"><i class="fas fa-shield-alt"></i> Secure payment</div>
    </header>

    <main class="page">
        <section class="order-banner">
            <div>
                <h1>Select how to pay</h1>
                <p>Order <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
            </div>
            <div class="amount-pill">
                Order total
                <strong>LKR <?php echo number_format($baseAmount, 2); ?></strong>
            </div>
        </section>

        <div class="step-hint">Choose your payment provider</div>

        <form method="POST" id="gatewayForm">
            <div class="gateway-grid">
                <?php foreach ($availableGateways as $gid):
                    $brand = $gatewayBranding[$gid] ?? [];
                    $percent = ezymart_payment_gateway_surcharge_percent($gid);
                    $surchargeAmt = ezymart_payment_calculate_surcharge($baseAmount, $gid);
                    $payTotal = ezymart_payment_total_with_surcharge($baseAmount, $gid);
                    $isBnpl = ($brand['type'] ?? '') === 'bnpl';
                    $cards = $brand['cards'] ?? ['visa', 'mastercard'];
                    $accent = $brand['accent'] ?? '#E5A91A';
                    $logoBg = $brand['logo_bg'] ?? '#111827';
                ?>
                <label class="pay-tile" data-gateway="<?php echo htmlspecialchars($gid); ?>">
                    <input type="radio" name="select_gateway" value="<?php echo htmlspecialchars($gid); ?>" required>
                    <div class="tile-accent" style="background:<?php echo htmlspecialchars($accent); ?>;"></div>
                    <span class="tile-check" aria-hidden="true"><i class="fas fa-check"></i></span>

                    <div class="tile-body">
                        <div class="logo-stage" style="background:<?php echo htmlspecialchars($logoBg); ?>;">
                            <img src="<?php echo htmlspecialchars($brand['logo'] ?? ''); ?>" alt="<?php echo htmlspecialchars($gatewayLabels[$gid] ?? $gid); ?>" loading="lazy">
                        </div>

                        <div class="tile-title"><?php echo htmlspecialchars($gatewayLabels[$gid] ?? ucfirst($gid)); ?></div>
                        <div class="tile-desc"><?php echo htmlspecialchars($gatewayDescriptions[$gid] ?? ''); ?></div>
                        <span class="type-pill <?php echo $isBnpl ? 'bnpl' : ''; ?>">
                            <?php echo $isBnpl ? 'Pay Later' : 'Card'; ?>
                        </span>

                        <div class="cards-row">
                            <?php foreach ($cards as $cardKey):
                                $card = $cardBrands[$cardKey] ?? null;
                                if (!$card) continue;
                            ?>
                            <img src="<?php echo htmlspecialchars($card['icon']); ?>" alt="<?php echo htmlspecialchars($card['label']); ?>" title="<?php echo htmlspecialchars($card['label']); ?>">
                            <?php endforeach; ?>
                        </div>

                        <div class="tile-foot">
                            <div class="tile-fee">
                                <?php if ($percent > 0): ?>
                                <strong>+<?php echo rtrim(rtrim(number_format($percent, 2), '0'), '.'); ?>%</strong>
                                fee · LKR <?php echo number_format($surchargeAmt, 2); ?>
                                <?php else: ?>
                                No extra fee
                                <?php endif; ?>
                            </div>
                            <div class="tile-total">LKR <?php echo number_format($payTotal, 2); ?></div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="sticky-actions">
                <div class="sticky-inner">
                    <a href="<?php echo BASE_URL; ?>checkout.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                    <button type="submit" class="btn-continue" id="continueBtn" disabled>
                        <i class="fas fa-lock"></i> Continue to payment
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
    const tiles = document.querySelectorAll('.pay-tile');
    const btn = document.getElementById('continueBtn');
    tiles.forEach(tile => {
        tile.addEventListener('click', function() {
            tiles.forEach(t => t.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            btn.disabled = false;
        });
    });
    </script>
</body>
</html>
