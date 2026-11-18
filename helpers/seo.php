<?php
/**
 * ARIGATO.LK — SEO meta description helpers
 */

function ezymart_truncate_meta_description(string $text, int $max = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 3)) . '...';
}

/**
 * Product meta description: product name + "Price in Sri Lanka" for local SEO.
 */
function ezymart_product_meta_description(string $productName): string
{
    $name = trim($productName);
    $domain = defined('STORE_DOMAIN') ? STORE_DOMAIN : 'arigato.lk';

    $full = sprintf(
        '%s Price in Sri Lanka. Buy %s at the best price in Sri Lanka with islandwide delivery. Shop at %s.',
        $name,
        $name,
        $domain
    );
    if (mb_strlen($full) <= 160) {
        return $full;
    }

    $medium = sprintf(
        '%s Price in Sri Lanka. Buy original %s online with islandwide delivery at %s.',
        $name,
        $name,
        $domain
    );
    if (mb_strlen($medium) <= 160) {
        return $medium;
    }

    $short = sprintf('%s Price in Sri Lanka. Shop mobiles & tech accessories at %s.', $name, $domain);
    return ezymart_truncate_meta_description($short, 160);
}

function ezymart_product_page_title(string $productName): string
{
    return trim($productName) . ' Price in Sri Lanka - ARIGATO.LK';
}
