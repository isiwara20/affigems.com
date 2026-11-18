<?php
/**
 * Print Shipping Label — priority mail layout for orders & WhatsApp orders.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/OrderDAL.php';
require_once __DIR__ . '/../helpers/images.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    exit('Unauthorized');
}

$orderDAL = new OrderDAL();
$orderId = (int)($_GET['id'] ?? 0);
$order = $orderDAL->getById($orderId);

if (!$order) {
    exit('Order not found');
}

$items = $orderDAL->getOrderItems($orderId);
$totalQty = (int)array_sum(array_column($items, 'quantity'));

$contentsList = array_map(static function ($item) {
    $name = trim((string)($item['product_name'] ?? ''));
    $qty = (int)($item['quantity'] ?? 1);
    return $name !== '' ? $name . ($qty > 1 ? ' x' . $qty : '') : '';
}, $items);
$contentsList = array_values(array_filter($contentsList));
$contents = $contentsList !== [] ? implode(', ', array_slice($contentsList, 0, 4)) : 'General merchandise';
if (count($contentsList) > 4) {
    $contents .= '…';
}

$deliveryMethod = strtolower(trim((string)($order['delivery_method'] ?? 'standard')));
$serviceBanner = in_array($deliveryMethod, ['express', 'priority', 'priority_mail'], true)
    ? 'PRIORITY MAIL'
    : 'STANDARD MAIL';

$countryCode = strtoupper(trim((string)($order['shipping_country'] ?? 'LK')));
$countryNames = [
    'LK' => 'Sri Lanka',
    'US' => 'US',
    'GB' => 'UK',
    'AU' => 'Australia',
    'IN' => 'India',
    'AE' => 'UAE',
];
$countryLabel = $countryNames[$countryCode] ?? $countryCode;

$stateLabel = trim((string)($order['district'] ?? ''));
$cityLabel = trim((string)($order['city'] ?? ''));
$zipLabel = trim((string)($order['postal_code'] ?? ''));
$addressLine = trim((string)($order['address_1'] ?? ''));
if (!empty($order['address_2'])) {
    $addressLine .= ($addressLine !== '' ? ', ' : '') . trim((string)$order['address_2']);
}

$deliveryNotes = trim((string)($order['notes'] ?? ''));
$orderValue = 'LKR ' . number_format((float)($order['total'] ?? 0), 0);
$barcodeValue = preg_replace('/\s+/', '', (string)$order['order_number']);
$logoUrl = asset_image_url('assets/images/logo.png');

function ezymart_shipping_barcode_svg(string $value): string
{
    $value = preg_replace('/\s+/', '', $value);
    if ($value === '') {
        $value = '0';
    }

    $height = 52;
    $quiet = 8;
    $x = $quiet;
    $bars = '';

    $appendBar = static function (int $width, bool $draw) use (&$bars, &$x, $height): void {
        if ($draw) {
            $bars .= '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="#000"/>';
        }
        $x += $width + 1;
    };

    foreach ([2, 1, 1, 2, 1, 3] as $width) {
        $appendBar($width, true);
    }
    $appendBar(2, false);

    foreach (str_split($value) as $char) {
        $code = ord($char);
        for ($bit = 0; $bit < 6; $bit++) {
            $appendBar((($code + $bit) % 3) + 1, ($code + $bit) % 2 === 0);
        }
    }

    foreach ([2, 1, 1, 1, 2] as $width) {
        $appendBar($width, true);
    }

    $width = $x + $quiet;
    return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Barcode">' . $bars . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Shipping Label — <?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e8e8e8;
            color: #000;
            padding: 16px;
        }

        .label {
            width: 4in;
            min-height: 6in;
            margin: 0 auto;
            border: 2px solid #000;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .label-row {
            display: grid;
            border-bottom: 1px solid #000;
        }

        .label-row:last-child {
            border-bottom: none;
        }

        /* Header */
        .label-header {
            grid-template-columns: 1.05in 1fr;
            min-height: 1.05in;
        }

        .label-logo {
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 8px;
        }

        .label-logo img {
            max-width: 100%;
            max-height: 0.82in;
            object-fit: contain;
        }

        .label-company {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 3px;
        }

        .label-company strong {
            font-size: 12px;
            line-height: 1.25;
        }

        .label-company span {
            font-size: 10px;
            line-height: 1.35;
        }

        /* Service banner */
        .label-service {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 7px 8px;
            border-bottom: 1px solid #000;
        }

        /* Two-column body */
        .label-details {
            grid-template-columns: 1fr 1fr;
            flex: 1;
        }

        .label-col {
            padding: 10px 10px 8px;
            font-size: 10px;
            line-height: 1.45;
        }

        .label-col + .label-col {
            border-left: 1px solid #000;
        }

        .label-col h4 {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 58px 8px 1fr;
            margin-bottom: 2px;
            align-items: start;
        }

        .field-row .lbl {
            font-weight: 400;
        }

        .field-row .sep {
            text-align: center;
        }

        .field-row .val {
            font-weight: 600;
            word-break: break-word;
        }

        /* Delivery instructions */
        .label-instructions {
            min-height: 0.95in;
            padding: 8px 10px 10px;
            border-bottom: 1px solid #000;
        }

        .label-instructions h4 {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .label-instructions p {
            font-size: 10px;
            line-height: 1.45;
            min-height: 0.55in;
            white-space: pre-wrap;
        }

        /* Footer */
        .label-footer {
            grid-template-columns: 1fr 1.35fr;
            min-height: 1.45in;
        }

        .label-handling {
            border-right: 1px solid #000;
            padding: 10px 10px 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .label-handling strong {
            font-size: 22px;
            letter-spacing: 0.04em;
            line-height: 1;
        }

        .label-handling span {
            display: block;
            font-size: 9px;
            font-weight: 700;
            margin-top: 4px;
            letter-spacing: 0.02em;
        }

        .handling-icons {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .handling-icons svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .label-barcode-wrap {
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .label-barcode-wrap svg {
            width: 100%;
            max-width: 2.2in;
            height: 0.72in;
        }

        .barcode-text {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .btn-print {
            display: block;
            margin: 16px auto 0;
            padding: 10px 28px;
            background: #0d1137;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }

        @media print {
            @page {
                size: 4in 6in;
                margin: 0;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .btn-print {
                display: none !important;
            }

            .label {
                width: 4in;
                min-height: 6in;
                border-width: 2px;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="label-row label-header">
            <div class="label-logo">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>">
            </div>
            <div class="label-company">
                <strong><?php echo htmlspecialchars(STORE_OWNER); ?></strong>
                <span><?php echo htmlspecialchars(STORE_LOCATION); ?></span>
                <span><?php echo htmlspecialchars(STORE_PHONE_1); ?><?php echo STORE_PHONE_2 ? ' / ' . htmlspecialchars(STORE_PHONE_2) : ''; ?></span>
            </div>
        </div>

        <div class="label-service"><?php echo htmlspecialchars($serviceBanner); ?></div>

        <div class="label-row label-details">
            <div class="label-col">
                <h4>TO :</h4>
                <div class="field-row"><span class="lbl">Name</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($order['full_name']); ?></span></div>
                <div class="field-row"><span class="lbl">Address</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($addressLine); ?></span></div>
                <div class="field-row"><span class="lbl">City</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($cityLabel); ?></span></div>
                <div class="field-row"><span class="lbl">State</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($stateLabel); ?></span></div>
                <div class="field-row"><span class="lbl">Zip Code</span><span class="sep">:</span><span class="val"><?php echo $zipLabel !== '' ? htmlspecialchars($zipLabel) : '—'; ?></span></div>
                <div class="field-row"><span class="lbl">Country</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($countryLabel); ?></span></div>
                <div class="field-row"><span class="lbl">Phone</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($order['phone']); ?></span></div>
            </div>
            <div class="label-col">
                <h4>Package Details:</h4>
                <div class="field-row"><span class="lbl">Weight</span><span class="sep">:</span><span class="val">—</span></div>
                <div class="field-row"><span class="lbl">Dimensions</span><span class="sep">:</span><span class="val">—</span></div>
                <div class="field-row"><span class="lbl">Contents</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($contents); ?></span></div>
                <div class="field-row"><span class="lbl">Value</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($orderValue); ?></span></div>
                <div class="field-row"><span class="lbl">Order #</span><span class="sep">:</span><span class="val"><?php echo htmlspecialchars($order['order_number']); ?></span></div>
                <div class="field-row"><span class="lbl">Items</span><span class="sep">:</span><span class="val"><?php echo $totalQty; ?></span></div>
                <div class="field-row"><span class="lbl">Payment</span><span class="sep">:</span><span class="val"><?php echo ($order['payment_method'] ?? '') === 'cod' ? 'COD' : strtoupper((string)($order['payment_status'] ?? 'paid')); ?></span></div>
            </div>
        </div>

        <div class="label-instructions">
            <h4>Delivery Instruction :</h4>
            <p><?php echo $deliveryNotes !== '' ? htmlspecialchars($deliveryNotes) : ' '; ?></p>
        </div>

        <div class="label-row label-footer">
            <div class="label-handling">
                <div>
                    <strong>FRAGILE</strong>
                    <span>PLEASE HANDLE WITH CARE</span>
                </div>
                <div class="handling-icons" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 21h8M12 17v4M7 4l1 9h8l1-9M9 4h6l-1 5H10L9 4z"/><path d="M10 13l-1 2M14 13l1 2"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 19V5M7 10l5-5 5 5M7 14l5-5 5 5"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3c2 3 5 5 5 9a5 5 0 0 1-10 0c0-4 3-6 5-9z"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 14l-2 3h12l-2-3"/><path d="M12 14V8M9 8h6"/><path d="M7 11l5-4 5 4"/></svg>
                </div>
            </div>
            <div class="label-barcode-wrap">
                <?php echo ezymart_shipping_barcode_svg($barcodeValue); ?>
                <div class="barcode-text"><?php echo htmlspecialchars($barcodeValue); ?></div>
            </div>
        </div>
    </div>

    <button class="btn-print" type="button" onclick="window.print()">Print Label</button>
</body>
</html>
