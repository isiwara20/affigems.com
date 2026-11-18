<?php
/**
 * Currency switcher — stores selection in session, reloads storefront prices.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));

if ($code === '') {
    echo json_encode(['success' => false, 'error' => 'Currency code required.']);
    exit;
}

if (!set_current_currency($code)) {
    echo json_encode(['success' => false, 'error' => 'Invalid currency.']);
    exit;
}

$cfg = get_currency_config($code);
echo json_encode([
    'success' => true,
    'currency' => $code,
    'symbol' => $cfg['symbol'],
    'name' => $cfg['name'],
    'config' => currency_js_config(),
]);
