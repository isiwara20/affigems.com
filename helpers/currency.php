<?php
/**
 * Currency helper — convert & format LKR base prices for display.
 */

function get_current_currency(): string
{
    $code = $_SESSION['currency'] ?? CURRENCY_BASE;
    return isset(CURRENCIES[$code]) ? $code : CURRENCY_BASE;
}

function set_current_currency(string $code): bool
{
    if (!isset(CURRENCIES[$code])) {
        return false;
    }
    $_SESSION['currency'] = $code;
    return true;
}

function get_currency_config(?string $code = null): array
{
    $code = $code ?? get_current_currency();
    return CURRENCIES[$code] ?? CURRENCIES[CURRENCY_BASE];
}

function convert_price($amountLkr, ?string $code = null): float
{
    $amountLkr = (float) $amountLkr;
    $cfg = get_currency_config($code);
    $rate = max((float) $cfg['rate'], 1);
    return $amountLkr / $rate;
}

function format_price($amountLkr, $decimals = null, ?string $code = null): string
{
    $cfg = get_currency_config($code);
    $converted = convert_price($amountLkr, $code);
    $dec = $decimals !== null ? (int) $decimals : (int) $cfg['decimals'];

    if ($cfg['code'] === 'LKR') {
        return 'LKR ' . number_format($converted, $dec);
    }

    return $cfg['symbol'] . number_format($converted, $dec);
}

function currency_js_config(): array
{
    $current = get_current_currency();
    $cfg = get_currency_config($current);
    $all = [];
    foreach (CURRENCIES as $code => $c) {
        $all[$code] = [
            'code' => $code,
            'symbol' => $c['symbol'],
            'rate' => $c['rate'],
            'decimals' => $c['decimals'],
        ];
    }
    return [
        'current' => $current,
        'symbol' => $cfg['symbol'],
        'rate' => $cfg['rate'],
        'decimals' => $cfg['decimals'],
        'currencies' => $all,
    ];
}

function format_price_js($amountLkr): string
{
    $cfg = get_currency_config();
    $converted = convert_price($amountLkr);
    if ($cfg['code'] === 'LKR') {
        return 'LKR ' . number_format($converted, $cfg['decimals']);
    }
    return $cfg['symbol'] . number_format($converted, $cfg['decimals']);
}
