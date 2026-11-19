<?php
/**
 * ARIGATO.LK — back-in-stock notification helpers.
 */

require_once __DIR__ . '/email.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/../dal/RestockNotifyDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

function lumak_email_restock_template(string $productName, string $productUrl, ?string $variationLabel = null): string
{
    $variationNote = '';
    if ($variationLabel) {
        $variationNote = '<p style="margin:0 0 14px;font-size:13px;line-height:1.7;color:#555;">'
            . 'Option: <strong>' . htmlspecialchars($variationLabel) . '</strong></p>';
    }

    $content = '<h2 style="margin:0 0 12px;font-size:22px;color:#002868;text-align:center;">Back in Stock!</h2>'
        . '<p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#555;">Good news — an item on your restock alert list is available again.</p>'
        . lumak_email_info_box([
            ['Product', $productName],
        ])
        . $variationNote
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#555;">Hurry — stock may sell out quickly.</p>'
        . lumak_email_button($productUrl, 'Shop Now');

    return lumak_email_wrap('Restock Alert', $content);
}

function ezymart_restock_product_url(array $product): string
{
    if (!function_exists('public_site_url')) {
        require_once __DIR__ . '/../config/app.php';
    }

    return public_site_url('product.php?slug=' . rawurlencode((string)($product['slug'] ?? '')));
}

function ezymart_restock_sms_message(string $productName, string $productUrl, ?string $variationLabel = null): string
{
    $settings = ezymart_sms_settings();
    $store = $settings['store_name'] !== '' ? $settings['store_name'] : (defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems & Jewels');

    $item = $productName;
    if ($variationLabel) {
        $item .= ' (' . $variationLabel . ')';
    }

    // Plain ASCII only — special chars like em-dash show as "?" on many phones.
    $msg = sprintf(
        '%s: %s is back in stock. View: %s',
        $store,
        $item,
        $productUrl
    );

    return ezymart_sms_truncate($msg, 621);
}

/**
 * Send pending restock alerts for a product (optionally scoped to one variation key).
 */
function ezymart_send_restock_notifications(int $productId, ?string $variationKey = null): void
{
    try {
        $notifyDAL = new RestockNotifyDAL();
        $productDAL = new ProductDAL();
        $product = $productDAL->getById($productId);

        if (!$product || ($product['status'] ?? '') !== 'active') {
            return;
        }

        $variationKey = $variationKey ?? '';
        $pending = $notifyDAL->getPending($productId, $variationKey === '' ? '' : $variationKey);
        if ($pending === []) {
            return;
        }

        $productName = (string)($product['name'] ?? 'Product');
        $productUrl = ezymart_restock_product_url($product);
        $variationLabel = $variationKey !== '' ? $variationKey : null;
        $brand = lumak_email_brand_name();
        $subject = $brand . ' — ' . $productName . ' is back in stock';
        $html = lumak_email_restock_template($productName, $productUrl, $variationLabel);
        $smsMessage = ezymart_restock_sms_message($productName, $productUrl, $variationLabel);
        $notifiedIds = [];

        foreach ($pending as $row) {
            $id = (int)($row['id'] ?? 0);
            $email = trim((string)($row['email'] ?? ''));
            $phone = trim((string)($row['phone'] ?? ''));

            if ($email !== '') {
                lumak_send_html_mail($email, null, $subject, $html);
            }

            if ($phone !== '' && ezymart_sms_is_configured()) {
                $normalized = ezymart_normalize_phone($phone);
                if ($normalized) {
                    try {
                        ezymart_sms_send($normalized, $smsMessage, 'restock_alert');
                    } catch (Throwable $e) {
                        error_log('ARIGATO restock SMS error: ' . $e->getMessage());
                    }
                }
            }

            if ($id > 0) {
                $notifiedIds[] = $id;
            }
        }

        $notifyDAL->markNotified($notifiedIds);
    } catch (Throwable $e) {
        error_log('ARIGATO restock notify error: ' . $e->getMessage());
    }
}

/**
 * Queue restock notifications when stock transitions from out to in.
 *
 * @param array<string, int> $oldVariationStocks options_label => stock
 * @param array<string, int> $newVariationStocks options_label => stock
 */
function ezymart_restock_check_and_notify(
    int $productId,
    int $oldProductStock,
    int $newProductStock,
    array $oldVariationStocks = [],
    array $newVariationStocks = []
): void {
    $wasOut = $oldProductStock <= 0;
    $nowIn = $newProductStock > 0;

    if ($wasOut && $nowIn) {
        AsyncMailQueue::add(static function () use ($productId) {
            ezymart_send_restock_notifications($productId, '');
        });
    }

    foreach ($newVariationStocks as $label => $newStock) {
        $oldStock = (int)($oldVariationStocks[$label] ?? 0);
        if ($oldStock <= 0 && $newStock > 0) {
            AsyncMailQueue::add(static function () use ($productId, $label) {
                ezymart_send_restock_notifications($productId, $label);
            });
        }
    }
}

/**
 * @return array<string, int> options_label => stock
 */
function ezymart_restock_variation_stock_map(int $productId): array
{
    try {
        require_once __DIR__ . '/../dal/ProductVariationDAL.php';
        $variationDAL = new ProductVariationDAL();
        $data = $variationDAL->getProductVariationData($productId);
        $map = [];

        foreach ($data['variations'] ?? [] as $variation) {
            $label = trim((string)($variation['options_label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $map[$label] = (int)($variation['stock'] ?? 0);
        }

        return $map;
    } catch (Throwable $e) {
        return [];
    }
}
