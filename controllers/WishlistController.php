<?php
/**
 * Wishlist Controller
 */

function ezymart_wishlist_is_ajax(): bool
{
    return !empty($_POST['ajax'])
        || !empty($_GET['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

function ezymart_wishlist_json(array $payload, int $status = 200): void
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

function ezymart_wishlist_start_session(): void
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

function ezymart_wishlist_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && empty($_SESSION['is_admin']);
}

$isAjax = ezymart_wishlist_is_ajax() || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'wishlist-api.php';

if ($isAjax) {
    ezymart_wishlist_start_session();

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../dal/WishlistDAL.php';

    if (!ezymart_wishlist_logged_in()) {
        ezymart_wishlist_json([
            'success' => false,
            'message' => 'Please login to use your wishlist.',
            'login_required' => true,
        ], 401);
    }

    try {
        $wishlistDAL = new WishlistDAL();
        $userId = (int)$_SESSION['user_id'];
        $action = trim((string)($_POST['action'] ?? ''));
        $productId = (int)($_POST['product_id'] ?? 0);

        switch ($action) {
            case 'add':
                if ($productId > 0) {
                    $wishlistDAL->add($userId, $productId);
                }
                ezymart_wishlist_json([
                    'success' => true,
                    'action' => 'add',
                    'in_wishlist' => $productId > 0,
                    'wishlist_count' => $wishlistDAL->getCount($userId),
                ]);

            case 'remove':
                if ($productId > 0) {
                    $wishlistDAL->remove($userId, $productId);
                }
                ezymart_wishlist_json([
                    'success' => true,
                    'action' => 'remove',
                    'in_wishlist' => false,
                    'wishlist_count' => $wishlistDAL->getCount($userId),
                ]);

            case 'clear':
                $wishlistDAL->clear($userId);
                ezymart_wishlist_json([
                    'success' => true,
                    'action' => 'clear',
                    'in_wishlist' => false,
                    'wishlist_count' => 0,
                ]);

            default:
                ezymart_wishlist_json([
                    'success' => false,
                    'message' => 'Invalid wishlist action.',
                ], 400);
        }
    } catch (Throwable $e) {
        ezymart_wishlist_json([
            'success' => false,
            'message' => 'Wishlist is temporarily unavailable.',
        ], 500);
    }
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/WishlistDAL.php';

if (!ezymart_wishlist_logged_in()) {
    $redirectUrl = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'shop.php';
    $_SESSION['redirect_after_login'] = $redirectUrl;
    $_SESSION['login_error'] = 'Please login to use your wishlist.';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

try {
    $wishlistDAL = new WishlistDAL();
} catch (Throwable $e) {
    throw $e;
}

$userId = (int)$_SESSION['user_id'];
$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $wishlistDAL->add($userId, $productId);
        }
        $redirect = $_POST['redirect_url'] ?? BASE_URL . 'wishlist.php';
        header('Location: ' . $redirect);
        exit;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $wishlistDAL->remove($userId, $productId);
        }
        $redirect = $_POST['redirect_url'] ?? BASE_URL . 'wishlist.php';
        header('Location: ' . $redirect);
        exit;

    case 'clear':
        $wishlistDAL->clear($userId);
        header('Location: ' . BASE_URL . 'wishlist.php');
        exit;

    default:
        header('Location: ' . BASE_URL . 'wishlist.php');
        exit;
}
