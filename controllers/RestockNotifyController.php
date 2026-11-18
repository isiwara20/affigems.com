<?php
/**
 * Restock Notify Controller — subscribe customers when a product is out of stock.
 */

function ezymart_restock_is_ajax(): bool
{
    return !empty($_POST['ajax'])
        || !empty($_GET['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'restock-notify-api.php';
}

function ezymart_restock_json(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ezymart_restock_start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!ezymart_restock_is_ajax() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (ezymart_restock_is_ajax()) {
        ezymart_restock_json(['success' => false, 'message' => 'Invalid request.'], 405);
    }
    http_response_code(404);
    exit;
}

ezymart_restock_start_session();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dal/RestockNotifyDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';

$action = trim((string)($_POST['action'] ?? ''));
if (!in_array($action, ['subscribe', 'status'], true)) {
    ezymart_restock_json(['success' => false, 'message' => 'Invalid action.'], 400);
}

$productId = (int)($_POST['product_id'] ?? 0);
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$phone = trim((string)($_POST['phone'] ?? ''));
$variationKey = trim((string)($_POST['variation_key'] ?? ''));
$userId = !empty($_SESSION['logged_in']) && empty($_SESSION['is_admin']) ? (int)($_SESSION['user_id'] ?? 0) : null;

if ($productId <= 0) {
    ezymart_restock_json(['success' => false, 'message' => 'Invalid product.'], 400);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ezymart_restock_json(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}

try {
    $productDAL = new ProductDAL();
    $product = $productDAL->getById($productId);

    if (!$product || ($product['status'] ?? '') !== 'active') {
        ezymart_restock_json(['success' => false, 'message' => 'Product not found.'], 404);
    }

    $notifyDAL = new RestockNotifyDAL();

    if ($action === 'status') {
        $subscribed = $notifyDAL->isSubscribed($productId, $email, $variationKey);
        $storedPhone = '';
        if ($subscribed) {
            $pending = $notifyDAL->getPending($productId, $variationKey);
            foreach ($pending as $row) {
                if (strtolower((string)($row['email'] ?? '')) === $email) {
                    $storedPhone = (string)($row['phone'] ?? '');
                    break;
                }
            }
        }

        ezymart_restock_json([
            'success' => true,
            'subscribed' => $subscribed,
            'phone' => $storedPhone,
        ]);
    }

    $variationDAL = new ProductVariationDAL();
    $variationData = $variationDAL->getProductVariationData($productId);
    $hasVariations = !empty($variationData['has_variations']);

    if ($hasVariations) {
        if ($variationKey !== '') {
            $match = null;
            foreach ($variationData['variations'] as $variation) {
                if ((string)($variation['label'] ?? '') === $variationKey) {
                    $match = $variation;
                    break;
                }
            }
            if (!$match) {
                ezymart_restock_json(['success' => false, 'message' => 'Please select a valid product option.'], 400);
            }
            if ((int)($match['stock'] ?? 0) > 0) {
                ezymart_restock_json(['success' => false, 'message' => 'This option is already in stock.'], 400);
            }
        } elseif ((int)($product['stock'] ?? 0) > 0) {
            ezymart_restock_json(['success' => false, 'message' => 'This product is already in stock.'], 400);
        }
    } elseif ((int)($product['stock'] ?? 0) > 0) {
        ezymart_restock_json(['success' => false, 'message' => 'This product is already in stock.'], 400);
    }

    if ($notifyDAL->isSubscribed($productId, $email, $variationKey)) {
        ezymart_restock_json([
            'success' => true,
            'already_subscribed' => true,
            'waiting' => true,
            'message' => 'You are already registered on our restock waiting list for this item.',
        ]);
    }

    $notifyDAL->subscribe($productId, $variationKey, $email, $phone !== '' ? $phone : null, $userId);

    ezymart_restock_json([
        'success' => true,
        'waiting' => true,
        'message' => 'You have been added to our restock waiting list. We will notify you when this item is available again.',
    ]);
} catch (Throwable $e) {
    error_log('EZYMART restock subscribe error: ' . $e->getMessage());
    ezymart_restock_json(['success' => false, 'message' => 'Could not save your alert. Please try again.'], 500);
}
