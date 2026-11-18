<?php
/**
 * Batch static + upload image optimizer for ARIGATO.LK.
 * Creates WebP sidecars and optionally resizes large originals.
 *
 * Run: php scripts/optimize-static-images.php
 * Or visit once locally: /Arigato_Ecommerce/scripts/optimize-static-images.php?key=optimize
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $key = $_GET['key'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = in_array($host, ['localhost', '127.0.0.1'], true) || str_starts_with($host, 'localhost:');
    if (!$isLocal || $key !== 'optimize') {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../services/ImageOptimizer.php';

$assetsRoot = dirname(__DIR__) . '/assets';
$extensions = ['png', 'jpg', 'jpeg'];
$results = [];
$count = 0;
$skipped = 0;

$sizeRules = [
    'images/login-couple' => [720, 1400],
    'images/login-bg' => [1920, 1080],
    'uploads/banners' => [1600, 700],
    'uploads/products' => [900, 900],
    'uploads/categories' => [600, 600],
];

$isLogoOrFavicon = static function (string $rel): bool {
    $base = strtolower(basename(str_replace('\\', '/', $rel)));
    if (in_array($base, ['logo.png', 'logobottom.png'], true)) {
        return true;
    }
    return (bool)preg_match('/^(favicon|apple-touch-icon)/i', $base);
};

$resolveLimits = static function (string $rel) use ($sizeRules): array {
    $rel = str_replace('\\', '/', strtolower($rel));
    foreach ($sizeRules as $fragment => $limits) {
        if (str_contains($rel, $fragment)) {
            return $limits;
        }
    }
    if (str_contains($rel, 'uploads/')) {
        return [1200, 1200];
    }
    return [1400, 1400];
};

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($assetsRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $extensions, true)) {
        continue;
    }

    $path = $file->getPathname();
    $rel = substr($path, strlen($assetsRoot) + 1);
    if ($isLogoOrFavicon($rel)) {
        $skipped++;
        $results[] = "SKIP $rel (logo/favicon)";
        continue;
    }
    [$maxW, $maxH] = $resolveLimits($rel);
    $before = filesize($path);
    ImageOptimizer::optimizeStaticAsset($path, $maxW, $maxH, 80);
    $after = filesize($path);
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    $webpSize = is_file($webp) ? filesize($webp) : 0;

    $results[] = sprintf(
        '%s — %s KB -> %s KB (webp: %s KB)',
        $rel,
        number_format($before / 1024, 1),
        number_format($after / 1024, 1),
        $webpSize ? number_format($webpSize / 1024, 1) : 'n/a'
    );
    $count++;
}

echo "ARIGATO.LK image optimization complete ($count files, $skipped logos/favicons skipped)\n\n";
echo implode("\n", $results) . "\n";
