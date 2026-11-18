<?php
/**
 * Cart Controller
 * Handles add, update, remove, clear cart actions
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/CartDAL.php';
require_once __DIR__ . '/../dal/ProductDAL.php';
require_once __DIR__ . '/../dal/ProductVariationDAL.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
    $redirectUrl = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'shop.php';
    $_SESSION['redirect_after_login'] = $redirectUrl;
    $_SESSION['login_error'] = 'Please login to add items to cart.';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$cartDAL = new CartDAL();
$productDAL = new ProductDAL();
$variationDAL = new ProductVariationDAL();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function cartRedirectWithError(string $message, string $redirect): void
{
    $_SESSION['cart_error'] = $message;
    header('Location: ' . $redirect);
    exit;
}

function validateCartItem(int $productId, int $variationId, int $quantity): array
{
    global $productDAL, $variationDAL;

    $product = $productDAL->getById($productId);
    if (!$product || ($product['status'] ?? '') !== 'active') {
        throw new Exception('This product is not available.');
    }

    $hasVariations = !empty($product['has_variations']);
    if ($hasVariations && $variationId <= 0) {
        throw new Exception('Please select all product options before adding to cart.');
    }
    if (!$hasVariations && $variationId > 0) {
        $variationId = 0;
    }

    $stock = (int)$product['stock'];
    if ($variationId > 0) {
        $variation = $variationDAL->getVariationById($variationId);
        if (!$variation || (int)$variation['product_id'] !== $productId || $variation['status'] !== 'active') {
            throw new Exception('Selected product variation is not available.');
        }
        $stock = (int)$variation['stock'];
    }

    if ($stock <= 0) {
        throw new Exception('This product is out of stock.');
    }
    if ($quantity > $stock) {
        throw new Exception('Only ' . $stock . ' item(s) available in stock.');
    }

    return ['product' => $product, 'variation_id' => $variationId];
}

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $variationId = (int)($_POST['variation_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $redirect = $_POST['redirect_url'] ?? BASE_URL . 'cart.php';

        if ($productId <= 0) {
            cartRedirectWithError('Invalid product.', $redirect);
        }

        try {
            $validated = validateCartItem($productId, $variationId, $quantity);
            $cartDAL->addItem($userId, $productId, $quantity, $validated['variation_id']);
        } catch (Exception $e) {
            cartRedirectWithError($e->getMessage(), $redirect);
        }

        header('Location: ' . $redirect);
        exit;

    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $variationId = (int)($_POST['variation_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($productId > 0 && $quantity > 0) {
            try {
                $validated = validateCartItem($productId, $variationId, $quantity);
                $cartDAL->updateQuantity($userId, $productId, $quantity, $validated['variation_id']);
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                cartRedirectWithError($e->getMessage(), BASE_URL . 'cart.php');
            }
        } elseif ($productId > 0 && $quantity <= 0) {
            $cartDAL->removeItem($userId, $productId, $variationId);
        }

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            $cartItems = $cartDAL->getByUser($userId);
            $subtotal = 0;
            $cartCount = count($cartItems);
            foreach ($cartItems as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $discount = 0;
            $appliedCode = $_SESSION['applied_coupon'] ?? '';
            if (!empty($appliedCode)) {
                require_once __DIR__ . '/../dal/CouponDAL.php';
                try {
                    $couponDAL = new CouponDAL();
                    $result = $couponDAL->validateCoupon($appliedCode, $subtotal);
                    if ($result['valid']) {
                        $discount = $result['discount'];
                    }
                } catch (Exception $e) {}
            }
            require_once __DIR__ . '/../dal/SiteSettingsDAL.php';
            $settingsDAL = new SiteSettingsDAL();
            $deliveryFeeAmount = (int)$settingsDAL->get('delivery_fee', '250');
            $freeDeliveryThreshold = (int)$settingsDAL->get('free_delivery_threshold', '2500');
            $deliveryFee = ($freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold) ? 0 : $deliveryFeeAmount;
            $total = $subtotal - $discount + $deliveryFee;
            echo json_encode([
                'success' => true,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'count' => $cartCount
            ]);
            exit;
        }
        header('Location: ' . BASE_URL . 'cart.php');
        exit;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        $variationId = (int)($_POST['variation_id'] ?? 0);
        if ($productId > 0) {
            $cartDAL->removeItem($userId, $productId, $variationId);
        }
        header('Location: ' . BASE_URL . 'cart.php');
        exit;

    case 'clear':
        $cartDAL->clearCart($userId);
        header('Location: ' . BASE_URL . 'cart.php');
        exit;

    default:
        header('Location: ' . BASE_URL . 'cart.php');
        exit;
}
