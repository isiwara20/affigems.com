<?php
/**
 * ARIGATO.LK — invoice settings, HTML preview, PDF generation, and email delivery.
 */

require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
require_once __DIR__ . '/../dal/OrderDAL.php';

function lumak_invoice_log(string $message): void
{
    $dir = lumak_invoice_storage_dir();
    $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    @file_put_contents($dir . '/invoice.log', $line, FILE_APPEND | LOCK_EX);
    error_log('ARIGATO.LK invoice: ' . $message);
}

function lumak_invoice_load_fpdf(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $path = __DIR__ . '/../libs/FPDF/fpdf.php';
    if (!is_file($path)) {
        throw new \RuntimeException('FPDF library not found at ' . $path);
    }
    require_once $path;
    $loaded = true;
}

function lumak_invoice_defaults(): array
{
    return [
        'enabled' => '1',
        'auto_send_order_placed' => '1',
        'auto_send_payment_confirmed' => '1',
        'auto_send_processing' => '0',
        'auto_send_shipped' => '1',
        'auto_send_paid' => '1',
        'invoice_title' => 'TAX INVOICE',
        'invoice_prefix' => 'INV',
        'accent_color' => '#002868',
        'secondary_color' => '#002868',
        'company_name' => defined('ADMIN_BRAND') ? ADMIN_BRAND : 'Affinity Gems & Jewels',
        'company_tagline' => 'Your Direct Source for Sri Lankan Sapphires',
        'company_address' => defined('STORE_LOCATION') ? STORE_LOCATION : '',
        'company_phone' => defined('STORE_PHONE_1') ? STORE_PHONE_1 : '',
        'company_email' => defined('STORE_EMAIL') ? STORE_EMAIL : '',
        'company_website' => defined('STORE_DOMAIN') ? STORE_DOMAIN : '',
        'show_logo' => '1',
        'tax_label' => 'Tax',
        'tax_rate' => '0',
        'footer_note' => 'Thank you for choosing Affinity Gems and Jewels Pvt Ltd!',
        'payment_note' => 'Please retain this invoice for your records. For gemstone inquiries and valuation queries, contact Murshid Munzeer at murshidmunzeer@gmail.com.',
        'terms_text' => 'All Ceylon sapphires and gemstones are certified authentic. Handcrafted with excellence.',
        'email_subject' => 'Your Invoice #{invoice_number} - Affinity Gems & Jewels',
        'email_message' => 'Hi {customer_name}, thank you for your order #{order_number}. Please find your invoice attached as a PDF.',
        'paid_seal_position' => 'over_totals',
        'paid_seal_offset_x' => '0',
        'paid_seal_offset_y' => '0',
        'paid_seal_rotation' => '-14',
    ];
}

function lumak_invoice_get_settings(?SiteSettingsDAL $settingsDAL = null): array
{
    $defaults = lumak_invoice_defaults();
    $dal = $settingsDAL ?: new SiteSettingsDAL();
    $json = $dal->get('invoice_settings', '');
    $saved = [];
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $saved = $decoded;
        }
    }

    $settings = array_merge($defaults, $saved);

    if (empty($settings['company_name'])) {
        $settings['company_name'] = $dal->get('store_name', $defaults['company_name']);
    }
    if (empty($settings['company_phone'])) {
        $settings['company_phone'] = $dal->get('store_phone', $defaults['company_phone']);
    }
    if (empty($settings['company_email'])) {
        $settings['company_email'] = $dal->get('store_email', $defaults['company_email']);
    }
    if (empty($settings['company_address'])) {
        $settings['company_address'] = $dal->get('store_address', $defaults['company_address']);
    }

    return lumak_invoice_normalize_paid_seal_settings($settings);
}

function lumak_invoice_save_settings(array $data): void
{
    $settings = lumak_invoice_normalize_paid_seal_settings(array_merge(lumak_invoice_defaults(), $data));
    $dal = new SiteSettingsDAL();
    $dal->set('invoice_settings', json_encode($settings, JSON_UNESCAPED_UNICODE));
}

function lumak_invoice_paid_seal_positions(): array
{
    return [
        'over_totals' => 'Over totals (right)',
        'top_right' => 'Top right',
        'top_left' => 'Top left',
        'center' => 'Center',
        'bottom_right' => 'Bottom right',
        'bottom_left' => 'Bottom left',
    ];
}

function lumak_invoice_normalize_paid_seal_settings(array $settings): array
{
    $allowed = array_keys(lumak_invoice_paid_seal_positions());
    if (!in_array($settings['paid_seal_position'] ?? '', $allowed, true)) {
        $settings['paid_seal_position'] = 'over_totals';
    }
    $settings['paid_seal_offset_x'] = (string) max(-80, min(80, (int)($settings['paid_seal_offset_x'] ?? 0)));
    $settings['paid_seal_offset_y'] = (string) max(-80, min(80, (int)($settings['paid_seal_offset_y'] ?? 0)));
    $settings['paid_seal_rotation'] = (string) max(-45, min(45, (float)($settings['paid_seal_rotation'] ?? -14)));
    return $settings;
}

function lumak_invoice_paid_seal_coords(array $settings): array
{
    $position = $settings['paid_seal_position'] ?? 'over_totals';
    $offsetX = (int)($settings['paid_seal_offset_x'] ?? 0);
    $offsetY = (int)($settings['paid_seal_offset_y'] ?? 0);
    $rotation = (float)($settings['paid_seal_rotation'] ?? -14);

    $presets = [
        'over_totals' => [132, 168],
        'top_right' => [168, 52],
        'top_left' => [42, 52],
        'center' => [105, 145],
        'bottom_right' => [168, 248],
        'bottom_left' => [42, 248],
    ];

    $base = $presets[$position] ?? $presets['over_totals'];

    return [
        'x' => $base[0] + $offsetX,
        'y' => $base[1] + $offsetY,
        'rotation' => $rotation,
    ];
}

function lumak_invoice_paid_seal_inline_style(array $settings): string
{
    $position = $settings['paid_seal_position'] ?? 'over_totals';
    $offsetX = (int)($settings['paid_seal_offset_x'] ?? 0);
    $offsetY = (int)($settings['paid_seal_offset_y'] ?? 0);
    $rotation = (float)($settings['paid_seal_rotation'] ?? -14);

    $styles = [
        'position:absolute',
        'z-index:3',
        'pointer-events:none',
        'opacity:.9',
    ];

    switch ($position) {
        case 'top_right':
            $styles[] = 'top:' . (8 + $offsetY) . 'px';
            $styles[] = 'right:' . (-$offsetX) . 'px';
            $styles[] = 'transform:rotate(' . $rotation . 'deg)';
            break;
        case 'top_left':
            $styles[] = 'top:' . (8 + $offsetY) . 'px';
            $styles[] = 'left:' . $offsetX . 'px';
            $styles[] = 'transform:rotate(' . $rotation . 'deg)';
            break;
        case 'center':
            $styles[] = 'top:calc(50% + ' . $offsetY . 'px)';
            $styles[] = 'left:calc(50% + ' . $offsetX . 'px)';
            $styles[] = 'transform:translate(-50%,-50%) rotate(' . $rotation . 'deg)';
            break;
        case 'bottom_right':
            $styles[] = 'bottom:' . (80 - $offsetY) . 'px';
            $styles[] = 'right:' . (-$offsetX) . 'px';
            $styles[] = 'transform:rotate(' . $rotation . 'deg)';
            break;
        case 'bottom_left':
            $styles[] = 'bottom:' . (80 - $offsetY) . 'px';
            $styles[] = 'left:' . $offsetX . 'px';
            $styles[] = 'transform:rotate(' . $rotation . 'deg)';
            break;
        case 'over_totals':
        default:
            $styles[] = 'top:' . (-28 + $offsetY) . 'px';
            $styles[] = 'right:' . (8 - $offsetX) . 'px';
            $styles[] = 'transform:rotate(' . $rotation . 'deg)';
            break;
    }

    return implode(';', $styles);
}

function lumak_invoice_hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function lumak_invoice_logo_path(): ?string
{
    if (!function_exists('asset_image_rel')) {
        require_once __DIR__ . '/../config/app.php';
    }
    $rel = asset_image_rel('assets/images/logobottom.png');
    $path = realpath(__DIR__ . '/../' . $rel);
    return ($path && is_file($path)) ? $path : null;
}

function lumak_invoice_logo_url(): string
{
    if (!function_exists('absolute_asset_image_url')) {
        require_once __DIR__ . '/../config/app.php';
    }
    return absolute_asset_image_url('assets/images/logobottom.png');
}

function lumak_invoice_number(array $order, ?array $settings = null): string
{
    $settings = $settings ?: lumak_invoice_get_settings();
    $prefix = trim($settings['invoice_prefix'] ?? 'INV');
    $orderNo = $order['order_number'] ?? (string)($order['id'] ?? '');
    return $prefix . '-' . $orderNo;
}

function lumak_invoice_payment_label(string $method): string
{
    $map = [
        'cod' => 'Cash on Delivery',
        'card' => 'Card Payment',
        'whatsapp' => 'WhatsApp Order',
    ];
    return $map[$method] ?? ucfirst($method);
}

function lumak_invoice_is_paid(array $order): bool
{
    return strtolower(trim((string)($order['payment_status'] ?? ''))) === 'paid';
}

function lumak_invoice_paid_date(array $order): string
{
    $date = $order['updated_at'] ?? $order['created_at'] ?? 'now';
    return date('d M Y', strtotime($date));
}

function lumak_invoice_paid_seal_html(array $order, ?array $settings = null): string
{
    if (!lumak_invoice_is_paid($order)) {
        return '';
    }

    $settings = $settings ?: lumak_invoice_get_settings();
    $paidDate = htmlspecialchars(lumak_invoice_paid_date($order));
    $style = htmlspecialchars(lumak_invoice_paid_seal_inline_style($settings), ENT_QUOTES);

    return '<div class="paid-seal" style="' . $style . '" aria-hidden="true">'
        . '<div class="paid-seal-inner">'
        . '<span class="paid-seal-title">PAID</span>'
        . '<span class="paid-seal-sub">Payment Received</span>'
        . '<span class="paid-seal-date">' . $paidDate . '</span>'
        . '</div></div>';
}

function lumak_invoice_total_label(array $order): string
{
    return lumak_invoice_is_paid($order) ? 'TOTAL PAID' : 'TOTAL DUE';
}

function lumak_invoice_sample_order(): array
{
    return [
        'id' => 0,
        'order_number' => 'AB260701A1B2',
        'full_name' => 'Kim Taehyung',
        'email' => 'customer@example.com',
        'phone' => '+94 77 123 4567',
        'shipping_country' => 'LK',
        'district' => 'Colombo',
        'city' => 'Colombo 03',
        'postal_code' => '00300',
        'address_1' => '42 Flower Road',
        'address_2' => 'Apartment 5B',
        'subtotal' => 12500,
        'discount' => 500,
        'delivery_fee' => 350,
        'total' => 12350,
        'coupon_code' => 'SAVE10',
        'delivery_method' => 'standard',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'order_status' => 'processing',
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

function lumak_invoice_sample_items(): array
{
    return [
        [
            'product_name' => 'Premium Gift Box Set',
            'variation_label' => 'Version A',
            'quantity' => 2,
            'price' => 2500,
            'total' => 5000,
        ],
        [
            'product_name' => 'Plush Keychain',
            'variation_label' => '',
            'quantity' => 1,
            'price' => 3500,
            'total' => 3500,
        ],
        [
            'product_name' => 'Accessory Pouch',
            'variation_label' => 'Blue',
            'quantity' => 1,
            'price' => 4000,
            'total' => 4000,
        ],
    ];
}

function lumak_invoice_enrich_items(int $orderId, array $items): array
{
    require_once __DIR__ . '/../dal/OrderItemSerialDAL.php';
    $serialDAL = new OrderItemSerialDAL();
    $map = $serialDAL->getMapByOrderId($orderId);

    foreach ($items as &$item) {
        $itemId = (int)($item['id'] ?? 0);
        $unitSerials = $map[$itemId] ?? [];
        if ($unitSerials !== []) {
            ksort($unitSerials);
        }
        $item['unit_serials'] = $unitSerials;
    }
    unset($item);

    return $items;
}

function lumak_invoice_item_serial_label(array $item): string
{
    $serials = $item['unit_serials'] ?? [];
    if ($serials === []) {
        return '';
    }

    $parts = [];
    foreach ($serials as $unit => $serial) {
        if (count($serials) > 1) {
            $parts[] = 'Unit ' . (int)$unit . ': ' . $serial;
        } else {
            $parts[] = $serial;
        }
    }

    $prefix = count($serials) > 1 ? 'Serial numbers' : 'Serial number';
    return $prefix . ': ' . implode(', ', $parts);
}

function lumak_invoice_render_html(array $order, array $items, ?array $settings = null): string
{
    $settings = $settings ?: lumak_invoice_get_settings();
    $accent = htmlspecialchars($settings['accent_color'] ?? '#002868');
    $secondary = htmlspecialchars($settings['secondary_color'] ?? '#003b94');
    $invoiceNo = htmlspecialchars(lumak_invoice_number($order, $settings));
    $orderNo = htmlspecialchars($order['order_number'] ?? '');
    $showLogo = ($settings['show_logo'] ?? '1') === '1';

    $addressLines = array_filter([
        $order['address_1'] ?? '',
        $order['address_2'] ?? '',
        trim(($order['city'] ?? '') . ', ' . ($order['district'] ?? '')),
        $order['postal_code'] ?? '',
    ]);

    $itemRows = '';
    $i = 1;
    foreach ($items as $item) {
        $name = htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Item');
        if (!empty($item['variation_label'])) {
            $name .= ' <span style="color:#888;font-size:11px;">(' . htmlspecialchars($item['variation_label']) . ')</span>';
        }
        $serialLabel = lumak_invoice_item_serial_label($item);
        if ($serialLabel !== '') {
            $name .= '<br><span style="font-size:10px;color:#666;">' . htmlspecialchars($serialLabel) . '</span>';
        }
        $bg = ($i % 2 === 0) ? '#f8fafd' : '#ffffff';
        $itemRows .= '<tr style="background:' . $bg . ';">'
            . '<td style="padding:10px 12px;border-bottom:1px solid #f0e8ff;text-align:center;color:#888;">' . $i . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #f0e8ff;">' . $name . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #f0e8ff;text-align:center;">' . (int)($item['quantity'] ?? 1) . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #f0e8ff;text-align:right;">LKR ' . number_format((float)($item['price'] ?? 0), 2) . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #f0e8ff;text-align:right;font-weight:700;">LKR ' . number_format((float)($item['total'] ?? ($item['price'] ?? 0) * ($item['quantity'] ?? 1)), 2) . '</td>'
            . '</tr>';
        $i++;
    }

    $discountRow = '';
    if ((float)($order['discount'] ?? 0) > 0) {
        $discountLabel = 'Discount';
        if (!empty($order['coupon_code'])) {
            $discountLabel .= ' (' . htmlspecialchars($order['coupon_code']) . ')';
        }
        $discountRow = '<div class="total-line"><span>' . $discountLabel . '</span><strong style="color:#16a34a;">- LKR ' . number_format((float)$order['discount'], 2) . '</strong></div>';
    }

    $delivery = (float)($order['delivery_fee'] ?? 0);
    $deliveryText = $delivery <= 0 ? 'FREE' : 'LKR ' . number_format($delivery, 2);
    $isPaid = lumak_invoice_is_paid($order);
    $paymentStatusHtml = $isPaid
        ? '<strong style="color:#16a34a;">Paid</strong>'
        : htmlspecialchars(ucfirst($order['payment_status'] ?? 'pending'));
    $totalLabel = lumak_invoice_total_label($order);
    $paidSeal = lumak_invoice_paid_seal_html($order, $settings);
    $sealOverTotals = $isPaid && (($settings['paid_seal_position'] ?? 'over_totals') === 'over_totals');
    $sealOnBody = $isPaid && !$sealOverTotals;

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice ' . $invoiceNo . '</title>'
        . '<style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Poppins,Arial,sans-serif;background:#eef1f8;color:#333;padding:24px}
        .invoice{max-width:820px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 12px 40px rgba(0,65,168,.12)}
        .header{background:' . $accent . ';padding:28px 32px;color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center}
        .brand h1{font-size:24px;font-weight:800;margin-bottom:4px}
        .brand p{font-size:12px;opacity:.92;line-height:1.6}
        .logo{max-width:120px;max-height:48px}
        .body{padding:28px 32px 32px;position:relative}
        .title-row{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px}
        .title-row h2{font-size:28px;color:' . $accent . ';letter-spacing:.04em}
        .meta{font-size:12px;color:#666;line-height:1.9;text-align:right}
        .cards{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px}
        .card{background:#f8fafd;border:1px solid #dbe5f5;border-radius:12px;padding:16px 18px}
        .card h4{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:' . $accent . ';margin-bottom:8px}
        .card p{font-size:13px;line-height:1.7;color:#555}
        .card strong{color:#222;font-size:14px}
        table{width:100%;border-collapse:collapse;border:1px solid #dbe5f5;border-radius:12px;overflow:hidden}
        th{background:#e8f0fe;color:' . $accent . ';font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:11px 12px;text-align:left}
        th.center{text-align:center} th.right{text-align:right}
        .totals{margin-top:18px;display:flex;justify-content:flex-end;position:relative;' . ($sealOverTotals ? 'min-height:128px;padding-bottom:8px;' : '') . '}
        .totals-box{width:300px;position:relative;z-index:2}
        .paid-seal{z-index:1}
        .total-line{display:flex;justify-content:space-between;padding:7px 0;font-size:13px;color:#666;border-bottom:1px dashed #eee}
        .total-line.grand{margin-top:8px;padding:12px 14px;background:' . ($isPaid ? '#16a34a' : $accent) . ';color:#fff;border-radius:10px;border:none;font-size:15px;font-weight:700}
        .total-line.grand span,.total-line.grand strong{color:#fff}
        .paid-seal-inner{border:4px double #16a34a;border-radius:14px;padding:16px 24px;text-align:center;background:rgba(236,253,245,.94);box-shadow:0 8px 24px rgba(22,163,74,.18)}
        .paid-seal-title{display:block;font-size:36px;font-weight:900;color:#16a34a;letter-spacing:.14em;line-height:1}
        .paid-seal-sub{display:block;font-size:9px;text-transform:uppercase;letter-spacing:.18em;color:#15803d;margin-top:6px;font-weight:700}
        .paid-seal-date{display:block;font-size:10px;color:#166534;margin-top:5px;font-weight:600}
        .notes{margin-top:' . ($sealOverTotals ? '36px' : '24px') . ';display:grid;gap:14px;clear:both}
        .note h5{font-size:12px;color:' . $accent . ';margin-bottom:6px}
        .note p{font-size:12px;line-height:1.7;color:#666}
        .footer-note{margin-top:20px;padding-top:16px;border-top:1px solid #f0e8ff;text-align:center;font-size:12px;color:#888;font-style:italic}
        </style></head><body><div class="invoice">'
        . '<div class="header"><div class="brand"><h1>' . htmlspecialchars($settings['company_name'] ?? '') . '</h1>'
        . '<p>' . htmlspecialchars($settings['company_tagline'] ?? '') . '<br>'
        . htmlspecialchars(implode(' | ', array_filter([
            $settings['company_address'] ?? '',
            $settings['company_phone'] ?? '',
            $settings['company_email'] ?? '',
            $settings['company_website'] ?? '',
        ]))) . '</p></div>'
        . ($showLogo ? '<img class="logo" src="' . htmlspecialchars(lumak_invoice_logo_url()) . '" alt="Logo">' : '')
        . '</div><div class="body">'
        . ($sealOnBody ? $paidSeal : '')
        . '<div class="title-row"><h2>' . htmlspecialchars($settings['invoice_title'] ?? 'INVOICE') . '</h2>'
        . '<div class="meta"><div><strong>Invoice:</strong> ' . $invoiceNo . '</div>'
        . '<div><strong>Order:</strong> #' . $orderNo . '</div>'
        . '<div><strong>Date:</strong> ' . date('d M Y', strtotime($order['created_at'] ?? 'now')) . '</div>'
        . '<div><strong>Status:</strong> ' . ucfirst($order['order_status'] ?? 'pending') . '</div></div></div>'
        . '<div class="cards"><div class="card"><h4>Bill To</h4><p><strong>' . htmlspecialchars($order['full_name'] ?? '') . '</strong><br>'
        . htmlspecialchars($order['email'] ?? '') . '<br>' . htmlspecialchars($order['phone'] ?? '') . '<br>'
        . htmlspecialchars(implode(', ', $addressLines)) . '</p></div>'
        . '<div class="card"><h4>Invoice Details</h4><p>'
        . '<strong>Payment:</strong> ' . lumak_invoice_payment_label($order['payment_method'] ?? 'cod') . '<br>'
        . '<strong>Payment Status:</strong> ' . $paymentStatusHtml . '<br>'
        . '<strong>Delivery:</strong> ' . ucfirst($order['delivery_method'] ?? 'standard') . '</p></div></div>'
        . '<table><thead><tr><th class="center" style="width:40px">#</th><th>Description</th><th class="center" style="width:60px">Qty</th>'
        . '<th class="right" style="width:110px">Unit Price</th><th class="right" style="width:110px">Amount</th></tr></thead><tbody>'
        . $itemRows . '</tbody></table>'
        . '<div class="totals">' . ($sealOverTotals ? $paidSeal : '') . '<div class="totals-box">'
        . '<div class="total-line"><span>Subtotal</span><strong>LKR ' . number_format((float)($order['subtotal'] ?? 0), 2) . '</strong></div>'
        . $discountRow
        . '<div class="total-line"><span>Delivery</span><strong>' . $deliveryText . '</strong></div>'
        . '<div class="total-line grand"><span>' . htmlspecialchars($totalLabel) . '</span><strong>LKR ' . number_format((float)($order['total'] ?? 0), 2) . '</strong></div>'
        . '</div></div>'
        . '<div class="notes">'
        . (!empty($settings['payment_note']) ? '<div class="note"><h5>Payment Information</h5><p>' . nl2br(htmlspecialchars($settings['payment_note'])) . '</p></div>' : '')
        . (!empty($settings['terms_text']) ? '<div class="note"><h5>Terms &amp; Conditions</h5><p>' . nl2br(htmlspecialchars($settings['terms_text'])) . '</p></div>' : '')
        . '</div>'
        . (!empty($settings['footer_note']) ? '<div class="footer-note">' . htmlspecialchars($settings['footer_note']) . '</div>' : '')
        . '</div></div></body></html>';
}

function lumak_invoice_generate_pdf(array $order, array $items, ?array $settings = null): string
{
    $settings = $settings ?: lumak_invoice_get_settings();

    try {
        require_once __DIR__ . '/../services/InvoicePdfGenerator.php';
        $pdf = new InvoicePdfGenerator($settings);
        $pdf->build($order, $items);
        $data = $pdf->Output('S');
        if (is_string($data) && $data !== '' && strncmp($data, '%PDF', 4) === 0) {
            return $data;
        }
        lumak_invoice_log('InvoicePdfGenerator returned invalid output, using fallback PDF');
    } catch (\Throwable $e) {
        lumak_invoice_log('InvoicePdfGenerator error: ' . $e->getMessage());
    }

    return lumak_invoice_generate_pdf_fallback($order, $items, $settings);
}

function lumak_invoice_generate_pdf_fallback(array $order, array $items, ?array $settings = null): string
{
    lumak_invoice_load_fpdf();

    $settings = $settings ?: lumak_invoice_get_settings();
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, 'Invoice: ' . lumak_invoice_number($order, $settings), 0, 1);
    $pdf->Cell(0, 7, 'Order: #' . ($order['order_number'] ?? ''), 0, 1);
    $pdf->Cell(0, 7, 'Date: ' . date('d M Y', strtotime($order['created_at'] ?? 'now')), 0, 1);
    $pdf->Cell(0, 7, 'Customer: ' . ($order['full_name'] ?? ''), 0, 1);
    if (lumak_invoice_is_paid($order)) {
        $pdf->SetTextColor(22, 163, 74);
        $pdf->Cell(0, 7, 'Payment Status: PAID', 0, 1);
        $pdf->SetTextColor(0, 0, 0);
    }
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 8, 'Item', 1);
    $pdf->Cell(25, 8, 'Qty', 1);
    $pdf->Cell(40, 8, 'Amount', 1, 1);
    $pdf->SetFont('Arial', '', 10);
    foreach ($items as $item) {
        $name = $item['product_name'] ?? $item['name'] ?? 'Item';
        $serialLabel = lumak_invoice_item_serial_label($item);
        if ($serialLabel !== '') {
            $name .= ' | ' . $serialLabel;
        }
        $pdf->Cell(80, 8, substr($name, 0, 60), 1);
        $pdf->Cell(25, 8, (string)(int)($item['quantity'] ?? 1), 1);
        $pdf->Cell(40, 8, 'LKR ' . number_format((float)($item['total'] ?? 0), 2), 1, 1);
    }
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Total: LKR ' . number_format((float)($order['total'] ?? 0), 2), 0, 1);

    $data = $pdf->Output('S');
    if (!is_string($data) || $data === '' || strncmp($data, '%PDF', 4) !== 0) {
        throw new \RuntimeException('Fallback invoice PDF generation failed');
    }
    return $data;
}

function lumak_invoice_storage_dir(): string
{
    $dir = __DIR__ . '/../assets/uploads/invoices';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $fallback = __DIR__ . '/../assets/uploads/tmp';
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0755, true);
        }
        if (is_dir($fallback) && is_writable($fallback)) {
            return $fallback;
        }
    }
    return $dir;
}

/**
 * Generate invoice PDF and save a copy on disk (for reliable email attachment on cPanel).
 */
function lumak_invoice_prepare_pdf_file(int $orderId): ?array
{
    return lumak_invoice_prepare_pdf_for_payment($orderId);
}

/**
 * Payment emails: build PDF with logging, fallback-first for shared hosting.
 */
function lumak_invoice_prepare_pdf_for_payment(int $orderId): ?array
{
    lumak_invoice_log('prepare_pdf start order_id=' . $orderId);

    try {
        $settings = lumak_invoice_get_settings();
        $orderDAL = new OrderDAL();
        $order = $orderDAL->getById($orderId);
        if (!$order) {
            lumak_invoice_log('prepare_pdf order not found id=' . $orderId);
            return null;
        }

        $items = lumak_invoice_enrich_items($orderId, $orderDAL->getOrderItems($orderId));
        $pdfData = null;

        try {
            $pdfData = lumak_invoice_generate_pdf($order, $items, $settings);
            lumak_invoice_log('prepare_pdf full generator ok bytes=' . strlen($pdfData));
        } catch (\Throwable $e) {
            lumak_invoice_log('prepare_pdf full generator failed: ' . $e->getMessage());
            try {
                $pdfData = lumak_invoice_generate_pdf_fallback($order, $items, $settings);
                lumak_invoice_log('prepare_pdf fallback ok bytes=' . strlen($pdfData));
            } catch (\Throwable $e2) {
                lumak_invoice_log('prepare_pdf all generators failed: ' . $e2->getMessage());
                return null;
            }
        }

        if (!$pdfData || strncmp($pdfData, '%PDF', 4) !== 0) {
            lumak_invoice_log('prepare_pdf invalid pdf data');
            return null;
        }

        $filename = lumak_invoice_pdf_filename($order, $settings);
        $dir = lumak_invoice_storage_dir();
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        lumak_invoice_log('prepare_pdf save dir=' . $dir . ' writable=' . (is_writable($dir) ? 'yes' : 'no'));

        $written = @file_put_contents($path, $pdfData);
        if ($written === false) {
            lumak_invoice_log('prepare_pdf file write FAILED path=' . $path);
        } else {
            lumak_invoice_log('prepare_pdf saved path=' . $path . ' bytes=' . $written);
        }

        return [
            'data' => $pdfData,
            'filename' => $filename,
            'path' => ($written !== false ? $path : null),
            'invoice_no' => lumak_invoice_number($order, $settings),
            'order' => $order,
            'items' => $items,
        ];
    } catch (\Throwable $e) {
        lumak_invoice_log('prepare_pdf exception: ' . $e->getMessage());
        return null;
    }
}

function lumak_invoice_pdf_filename(array $order, ?array $settings = null): string
{
    $settings = $settings ?: lumak_invoice_get_settings();
    $invoiceNo = lumak_invoice_number($order, $settings);
    return preg_replace('/[^A-Za-z0-9_-]/', '_', $invoiceNo) . '.pdf';
}

/**
 * Build invoice PDF for an order (always generates when order exists — not gated by invoice enabled setting).
 */
function lumak_invoice_build_pdf_for_order(int $orderId): ?array
{
    try {
        $settings = lumak_invoice_get_settings();
        $orderDAL = new OrderDAL();
        $order = $orderDAL->getById($orderId);
        if (!$order) {
            return null;
        }

        $items = lumak_invoice_enrich_items($orderId, $orderDAL->getOrderItems($orderId));
        $pdfData = lumak_invoice_generate_pdf($order, $items, $settings);

        return [
            'data' => $pdfData,
            'filename' => lumak_invoice_pdf_filename($order, $settings),
            'invoice_no' => lumak_invoice_number($order, $settings),
            'order' => $order,
            'items' => $items,
        ];
    } catch (\Throwable $e) {
        error_log('Invoice PDF build failed for order #' . $orderId . ': ' . $e->getMessage());
        return null;
    }
}

/**
 * Build PDF bytes + filename for an order, or null when invoice sending is disabled / data missing.
 */
function lumak_invoice_build_pdf_attachment(int $orderId, ?array $settings = null): ?array
{
    try {
        $settings = $settings ?: lumak_invoice_get_settings();
        if (($settings['enabled'] ?? '1') !== '1') {
            return null;
        }

        $attachment = lumak_invoice_build_pdf_for_order($orderId);
        if (!$attachment || empty($attachment['order']['email'])) {
            return null;
        }

        return $attachment;
    } catch (\Throwable $e) {
        error_log('Invoice PDF build failed for order #' . $orderId . ': ' . $e->getMessage());
        return null;
    }
}

function lumak_invoice_replace_tokens(string $text, array $order, array $settings): string
{
    $replacements = [
        '{customer_name}' => $order['full_name'] ?? '',
        '{order_number}' => $order['order_number'] ?? '',
        '{invoice_number}' => lumak_invoice_number($order, $settings),
        '{total}' => 'LKR ' . number_format((float)($order['total'] ?? 0), 2),
        '{company_name}' => $settings['company_name'] ?? '',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

function lumak_invoice_signing_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    try {
        $dal = new SiteSettingsDAL();
        $fromSettings = trim($dal->get('invoice_link_secret') ?? '');
    } catch (Throwable $e) {
        $fromSettings = '';
    }

    if ($fromSettings !== '') {
        $secret = $fromSettings;
        return $secret;
    }

    $sms = function_exists('ezymart_sms_settings') ? ezymart_sms_settings() : [];
    $secret = hash('sha256', ($sms['api_key'] ?? 'ezymart') . '|invoice|' . (defined('SITE_NAME') ? SITE_NAME : 'EZYMART'));

    return $secret;
}

function lumak_invoice_customer_token(int $orderId, string $orderNumber): string
{
    return substr(hash_hmac('sha256', $orderId . '|' . $orderNumber, lumak_invoice_signing_secret()), 0, 32);
}

function lumak_invoice_verify_customer_token(int $orderId, string $orderNumber, string $token): bool
{
    $expected = lumak_invoice_customer_token($orderId, $orderNumber);
    return $token !== '' && hash_equals($expected, $token);
}

function lumak_invoice_customer_url(array $order): string
{
    $orderId = (int)($order['id'] ?? 0);
    $orderNumber = (string)($order['order_number'] ?? '');
    if ($orderId <= 0 || $orderNumber === '') {
        return '';
    }

    $token = lumak_invoice_customer_token($orderId, $orderNumber);
    return public_site_url('invoice.php?order_id=' . $orderId . '&token=' . rawurlencode($token));
}

function lumak_invoice_should_auto_send(string $trigger, ?array $settings = null): bool
{
    $settings = $settings ?: lumak_invoice_get_settings();
    if (($settings['enabled'] ?? '1') !== '1') {
        return false;
    }
    $map = [
        'order_placed' => 'auto_send_order_placed',
        'payment_confirmed' => 'auto_send_payment_confirmed',
        'processing' => 'auto_send_processing',
        'shipped' => 'auto_send_shipped',
        'paid' => 'auto_send_paid',
    ];
    $key = $map[$trigger] ?? null;
    return $key && (($settings[$key] ?? '0') === '1');
}

function lumak_invoice_send_for_order(int $orderId, string $trigger = 'manual'): bool
{
    $settings = lumak_invoice_get_settings();
    if (($settings['enabled'] ?? '1') !== '1') {
        return false;
    }
    if ($trigger !== 'manual' && !lumak_invoice_should_auto_send($trigger, $settings)) {
        return false;
    }

    try {
        $attachment = lumak_invoice_prepare_pdf_file($orderId);
        if (!$attachment || empty($attachment['order']['email'])) {
            return false;
        }

        $order = $attachment['order'];
        $invoiceNo = $attachment['invoice_no'];
        $subject = lumak_invoice_replace_tokens($settings['email_subject'] ?? 'Your Invoice', $order, $settings);
        $messageText = lumak_invoice_replace_tokens($settings['email_message'] ?? '', $order, $settings);
        $invoiceUrl = lumak_invoice_customer_url($order);

        $linkNote = '';
        if ($invoiceUrl !== '') {
            $linkNote = '<p style="margin:0 0 14px;font-size:13px;line-height:1.7;color:#555;">'
                . 'Online copy: <a href="' . htmlspecialchars($invoiceUrl) . '" style="color:#002868;">Download invoice PDF</a>'
                . '</p>';
        }

        $emailBody = lumak_email_wrap('Invoice', '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;">Your Invoice</h2>'
            . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">' . nl2br(htmlspecialchars($messageText)) . '</p>'
            . $linkNote
            . lumak_email_info_box([
                ['Invoice Number', $invoiceNo],
                ['Order Number', '#' . ($order['order_number'] ?? '')],
                ['Total', 'LKR ' . number_format((float)($order['total'] ?? 0), 0), '#002868'],
            ]));

        $emailSent = lumak_send_mail_with_attachment(
            $order['email'],
            $order['full_name'] ?? '',
            $subject,
            $emailBody,
            $attachment['data'] ?? '',
            $attachment['filename'],
            'application/pdf',
            $messageText,
            $attachment['path'] ?? null,
            false
        );

        if ($emailSent && !empty($order['phone']) && function_exists('ezymart_sms_notify_invoice')) {
            if ($invoiceUrl !== '') {
                try {
                    ezymart_sms_notify_invoice(
                        (string)$order['phone'],
                        (string)($order['order_number'] ?? ''),
                        $invoiceNo,
                        $invoiceUrl
                    );
                } catch (Throwable $e) {
                    error_log('Invoice SMS error (order #' . $orderId . '): ' . $e->getMessage());
                }
            }
        }

        return $emailSent;
    } catch (\Throwable $e) {
        error_log('Invoice send error (order #' . $orderId . '): ' . $e->getMessage());
        return false;
    }
}

function lumak_invoice_try_auto_send(int $orderId, string $trigger): void
{
    try {
        $sent = lumak_invoice_send_for_order($orderId, $trigger);
        if (!$sent) {
            error_log('Invoice auto-send skipped or failed for order #' . $orderId . ' (trigger: ' . $trigger . ')');
        }
    } catch (\Throwable $e) {
        error_log('Invoice auto-send error (order #' . $orderId . '): ' . $e->getMessage());
    }
}
