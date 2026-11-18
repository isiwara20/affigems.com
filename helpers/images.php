<?php
/**
 * Static asset image helpers — prefer WebP when a sidecar file exists.
 * Logos and favicons stay as PNG (never swapped to WebP).
 */

function asset_image_is_logo_or_favicon(string $relativePath): bool
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $base = strtolower(basename($relativePath));

    if (in_array($base, ['logo.png', 'logobottom.png', 'logo.webp', 'logobottom.webp'], true)) {
        return true;
    }
    if (preg_match('/^favicon/i', $base) || preg_match('/^apple-touch-icon/i', $base)) {
        return true;
    }

    return false;
}

function asset_image_rel(string $relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $root = dirname(__DIR__);
    $abs = $root . '/' . $relativePath;

    if (!asset_image_is_logo_or_favicon($relativePath) && preg_match('/\.(jpe?g|png)$/i', $relativePath)) {
        $webpRel = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relativePath);
        if (is_file($root . '/' . $webpRel)) {
            return $webpRel;
        }
    }

    return is_file($abs) ? $relativePath : $relativePath;
}

function asset_image_url(string $relativePath): string
{
    return BASE_URL . ltrim(asset_image_rel($relativePath), '/');
}

/** Absolute URL (OG, email) — prefers WebP sidecar when available. */
function absolute_asset_image_url(string $relativePath): string
{
    if (!function_exists('absolute_url')) {
        return asset_image_url($relativePath);
    }
    return absolute_url(asset_image_rel($relativePath));
}

/** Resolve a stored media path (uploads or static) to the best URL. */
function media_image_url(?string $relativePath): string
{
    if ($relativePath === null || $relativePath === '') {
        return '';
    }
    return asset_image_url($relativePath);
}

/**
 * @param array{alt?:string,class?:string,loading?:string,fetchpriority?:string,width?:int,height?:int,decoding?:string,onerror?:string} $attrs
 */
function render_asset_image(string $relativePath, array $attrs = []): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $root = dirname(__DIR__);
    $skipWebp = asset_image_is_logo_or_favicon($relativePath);
    $webpRel = (!$skipWebp && preg_match('/\.(jpe?g|png)$/i', $relativePath))
        ? preg_replace('/\.(jpe?g|png)$/i', '.webp', $relativePath)
        : null;
    $hasWebp = $webpRel && is_file($root . '/' . $webpRel);

    $alt = htmlspecialchars($attrs['alt'] ?? '', ENT_QUOTES, 'UTF-8');
    $parts = [];
    foreach (['class', 'loading', 'fetchpriority', 'decoding'] as $key) {
        if (!empty($attrs[$key])) {
            $parts[] = $key . '="' . htmlspecialchars((string)$attrs[$key], ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    // Default lazy-load unless eager/fetchpriority high is set
    if (empty($attrs['loading']) && empty($attrs['fetchpriority'])) {
        $parts[] = 'loading="lazy"';
    }
    if (empty($attrs['decoding'])) {
        $parts[] = 'decoding="async"';
    }
    foreach (['width', 'height'] as $key) {
        if (isset($attrs[$key])) {
            $parts[] = $key . '="' . (int)$attrs[$key] . '"';
        }
    }
    if (!empty($attrs['onerror'])) {
        $parts[] = 'onerror="' . htmlspecialchars((string)$attrs['onerror'], ENT_QUOTES, 'UTF-8') . '"';
    }
    $attrStr = $parts ? ' ' . implode(' ', $parts) : '';

    $fallbackUrl = BASE_URL . $relativePath;
    if ($hasWebp) {
        $webpUrl = BASE_URL . $webpRel;
        return '<picture>'
            . '<source srcset="' . htmlspecialchars($webpUrl, ENT_QUOTES, 'UTF-8') . '" type="image/webp">'
            . '<img src="' . htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '"' . $attrStr . '>'
            . '</picture>';
    }

    $src = asset_image_url($relativePath);
    return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '"' . $attrStr . '>';
}
