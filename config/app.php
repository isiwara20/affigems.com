<?php
/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Optimize session cookie settings
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Output buffering with gzip for faster rendering
if (!ob_get_level()) {
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

// Base URL - adjust if needed
define('BASE_URL', '/Arigato_Ecommerce/');
define('SITE_NAME', 'ARIGATO.LK');
define('ADMIN_BRAND', 'ARIGATO.LK');
define('STORE_OWNER', 'Arigato Mobile (Pvt) Ltd');
define('STORE_BUSINESS_REG', '');
define('STORE_DIRECTOR', 'Mohamed Shifan');
define('STORE_DIRECTOR_TITLE', 'Owner');
define('STORE_TAGLINE', 'Mobile & Tech Store');

/**
 * Canonical site URL for SMS, emails, and other outbound links.
 * Uses PUBLIC_SITE_URL if set, otherwise https://STORE_DOMAIN + BASE_URL.
 */
function public_site_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    if (defined('PUBLIC_SITE_URL') && PUBLIC_SITE_URL !== '') {
        $base = rtrim(PUBLIC_SITE_URL, '/');
        return $base;
    }

    try {
        if (!class_exists('SiteSettingsDAL', false)) {
            require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
        }
        $fromDb = trim((new SiteSettingsDAL())->get('public_site_url') ?? '');
        if ($fromDb !== '') {
            $base = rtrim($fromDb, '/');
            return $base;
        }
    } catch (Throwable $e) {
        // fall through to domain-based default
    }

    $domain = defined('STORE_DOMAIN') ? STORE_DOMAIN : 'arigato.lk';
    $path = rtrim(BASE_URL, '/');
    $base = 'https://' . $domain . $path;

    return $base;
}

function public_site_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = public_site_base_url();
    if ($path === '') {
        return $base . '/';
    }
    return $base . '/' . $path;
}

/**
 * Build an absolute URL for Open Graph / social sharing.
 */
function absolute_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $isLocal = $host === 'localhost'
        || $host === '127.0.0.1'
        || str_starts_with($host, 'localhost:')
        || str_starts_with($host, '127.0.0.1:');

    // Outbound links (SMS/email) must never use localhost.
    if ($isLocal || $host === '') {
        return public_site_url($path);
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim(BASE_URL, '/');
    if ($path === '') {
        return $scheme . '://' . $host . $base . '/';
    }
    return $scheme . '://' . $host . $base . '/' . $path;
}

// Store contact details
define('STORE_PHONE_1', '077 899 8990');
define('STORE_PHONE_2', '071 489 9899');
define('STORE_EMAIL', 'info@arigato.lk');
define('STORE_DOMAIN', 'arigato.lk');
define('STORE_LOCATION', '779/3, Pannipitiya Road, Pelawatta, Battaramulla');
define('STORE_WHATSAPP', '94778998990');

// Map — Pelawatta, Battaramulla, Sri Lanka
define('STORE_MAP_URL', 'https://www.google.com/maps/search/?api=1&query=779%2F3+Pannipitiya+Road+Pelawatta+Battaramulla');
define('STORE_MAP_LAT', 0);
define('STORE_MAP_LNG', 0);
define('STORE_MAP_ZOOM', 16);

if (!function_exists('store_map_embed_url')) {
    function store_map_embed_url(): string
    {
        if (defined('STORE_MAP_LAT') && defined('STORE_MAP_LNG') && STORE_MAP_LAT && STORE_MAP_LNG) {
            $query = sprintf(
                '%s,%s (%s)',
                STORE_MAP_LAT,
                STORE_MAP_LNG,
                SITE_NAME . ' - ' . STORE_LOCATION
            );
            return 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&hl=en&z=' . STORE_MAP_ZOOM . '&output=embed';
        }

        $query = sprintf(
            '%s (%s)',
            STORE_LOCATION,
            SITE_NAME
        );
        return 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&hl=en&z=' . STORE_MAP_ZOOM . '&output=embed';
    }
}

if (!function_exists('store_map_open_url')) {
    function store_map_open_url(): string
    {
        return STORE_MAP_URL;
    }
}

if (!function_exists('store_phone_digits')) {
    function store_phone_digits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}

if (!function_exists('store_phone_parts')) {
    /** @return string[] */
    function store_phone_parts(string $phone): array
    {
        $digits = store_phone_digits($phone);
        if (preg_match('/^0(\d{2})(\d{3})(\d{4})$/', $digits, $matches)) {
            return ['0' . $matches[1], $matches[2], $matches[3]];
        }

        $parts = preg_split('/\s+/', trim($phone), -1, PREG_SPLIT_NO_EMPTY);

        return $parts ?: [$phone];
    }
}

if (!function_exists('store_phone_tel_href')) {
    function store_phone_tel_href(string $phone): string
    {
        return 'tel:' . store_phone_digits($phone);
    }
}

if (!function_exists('render_store_phone_link')) {
    function render_store_phone_link(string $phone, array $options = []): string
    {
        $class = trim('store-phone-link ' . ($options['class'] ?? ''));
        $partClass = $options['part_class'] ?? 'store-phone-part';
        $extraAttrs = $options['attrs'] ?? [];

        $attrHtml = ' href="' . htmlspecialchars(store_phone_tel_href($phone), ENT_QUOTES, 'UTF-8') . '"'
            . ' aria-label="Call ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '"';
        foreach ($extraAttrs as $name => $value) {
            $attrHtml .= ' ' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        $partsHtml = '';
        foreach (store_phone_parts($phone) as $part) {
            $partsHtml .= '<span class="' . htmlspecialchars($partClass, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($part, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return '<a class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' . $attrHtml . '>'
            . $partsHtml
            . '</a>';
    }
}

if (!function_exists('render_store_phone_numbers')) {
    /** @param string[] $phones */
    function render_store_phone_numbers(array $phones, array $options = []): string
    {
        $tableClass = trim('store-phone-table ' . ($options['table_class'] ?? ''));
        $extraAttrs = $options['attrs'] ?? [];

        $attrHtml = '';
        foreach ($extraAttrs as $name => $value) {
            $attrHtml .= ' ' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        $html = '<table class="' . htmlspecialchars($tableClass, ENT_QUOTES, 'UTF-8') . '" role="presentation"><tbody>';

        foreach ($phones as $phone) {
            $phone = (string) $phone;
            $href = htmlspecialchars(store_phone_tel_href($phone), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
            $parts = store_phone_parts($phone);

            $html .= '<tr class="store-phone-row">';
            foreach ($parts as $part) {
                $html .= '<td class="store-phone-cell">';
                $html .= '<a class="store-phone-link" href="' . $href . '" aria-label="Call ' . $label . '"' . $attrHtml . '>';
                $html .= htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
                $html .= '</a></td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}

// Social media profiles
define('STORE_FACEBOOK_URL', 'https://www.facebook.com/share/1bRem2jefn/');
define('STORE_INSTAGRAM_URL', 'https://www.instagram.com/arigatomobile?igsh=Yjk1ZjBweHB0bXM1');
define('STORE_TIKTOK_URL', 'https://www.tiktok.com/@arigato.mobile?_r=1&_t=ZS-98GeJmdypSn');
define('STORE_WHATSAPP_URL', 'https://wa.me/qr/X3N7Q4CNOAYDF1');

// Currency (base prices in DB are LKR)
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/../helpers/currency.php';
require_once __DIR__ . '/../helpers/images.php';
require_once __DIR__ . '/../helpers/email.php';
require_once __DIR__ . '/../helpers/invoice.php';
require_once __DIR__ . '/../helpers/sms.php';
$__warrantyHelper = __DIR__ . '/../helpers/warranty.php';
if (is_file($__warrantyHelper)) {
    require_once $__warrantyHelper;
}
require_once __DIR__ . '/../helpers/analytics.php';

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
