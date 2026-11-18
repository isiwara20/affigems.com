<?php
/**
 * ARIGATO.LK — Google Analytics 4 (GA4) via gtag.js
 */

require_once __DIR__ . '/../dal/SiteSettingsDAL.php';

function ezymart_analytics_settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }

    try {
        $dal = new SiteSettingsDAL();
        $all = $dal->getAll();
    } catch (Throwable $e) {
        $all = [];
    }

    $cache = [
        'enabled' => ($all['google_analytics_enabled'] ?? '0') === '1',
        'measurement_id' => ezymart_normalize_ga_measurement_id($all['google_analytics_id'] ?? ''),
    ];

    return $cache;
}

function ezymart_normalize_ga_measurement_id(?string $id): string
{
    $id = strtoupper(trim((string)$id));
    if ($id === '') {
        return '';
    }

    if (preg_match('/^G-[A-Z0-9]{6,}$/', $id)) {
        return $id;
    }

    return '';
}

function ezymart_analytics_is_configured(): bool
{
    $settings = ezymart_analytics_settings();
    return $settings['enabled'] && $settings['measurement_id'] !== '';
}

function ezymart_should_track_analytics(): bool
{
    if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return false;
    }

    return ezymart_analytics_is_configured();
}

function ezymart_render_google_analytics_head(): void
{
    if (!ezymart_should_track_analytics()) {
        return;
    }

    $measurementId = ezymart_analytics_settings()['measurement_id'];
    $safeId = htmlspecialchars($measurementId, ENT_QUOTES, 'UTF-8');

    echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $safeId . '"></script>' . "\n";
    echo '<script>' . "\n";
    echo 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}' . "\n";
    echo 'window.EZYMART_GA_ID=' . json_encode($measurementId) . ';' . "\n";
    echo 'gtag("js",new Date());' . "\n";
    echo 'gtag("config",' . json_encode($measurementId) . ',{send_page_view:true});' . "\n";
    echo 'document.addEventListener("turbo:load",function(){' . "\n";
    echo 'if(typeof gtag!=="function"||!window.EZYMART_GA_ID)return;' . "\n";
    echo 'gtag("config",window.EZYMART_GA_ID,{page_path:location.pathname+location.search,page_title:document.title});' . "\n";
    echo '});' . "\n";
    echo '</script>' . "\n";
}
