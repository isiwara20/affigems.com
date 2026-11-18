<?php
/**
 * ARIGATO.LK — product warranty periods, registry, and claim helpers.
 */

require_once __DIR__ . '/invoice.php';

function ezymart_warranty_period_options(): array
{
    return [
        '' => 'No warranty',
        '3_months' => '3 Months',
        '6_months' => '6 Months',
        '1_year' => '1 Year',
        '2_years' => '2 Years',
        '3_years' => '3 Years',
    ];
}

function ezymart_warranty_period_label(?string $period): string
{
    $options = ezymart_warranty_period_options();
    $period = trim((string)$period);
    return $options[$period] ?? 'No warranty';
}

function ezymart_warranty_period_months(?string $period): int
{
    $map = [
        '3_months' => 3,
        '6_months' => 6,
        '1_year' => 12,
        '2_years' => 24,
        '3_years' => 36,
    ];
    return $map[trim((string)$period)] ?? 0;
}

function ezymart_warranty_expiry_date(string $purchaseDate, string $period): ?string
{
    $months = ezymart_warranty_period_months($period);
    if ($months <= 0) {
        return null;
    }

    try {
        $date = new DateTime($purchaseDate);
        $date->modify('+' . $months . ' months');
        return $date->format('Y-m-d');
    } catch (Throwable $e) {
        return null;
    }
}

function ezymart_warranty_is_active(?string $expiresAt): bool
{
    if (!$expiresAt) {
        return false;
    }

    try {
        $expiry = new DateTime($expiresAt);
        $today = new DateTime('today');
        return $expiry >= $today;
    } catch (Throwable $e) {
        return false;
    }
}

function ezymart_warranty_format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }

    try {
        return (new DateTime($date))->format('d / m / Y');
    } catch (Throwable $e) {
        return '—';
    }
}

function ezymart_warranty_serial(string $orderNumber, int $orderItemId, int $unitIndex): string
{
    return sprintf('ITC-%s-%d-%02d', strtoupper($orderNumber), $orderItemId, $unitIndex);
}

function ezymart_warranty_normalize_reference(string $input): string
{
    $input = trim($input);
    $input = preg_replace('/\s+/', '', $input) ?? $input;
    return strtoupper(preg_replace('/^INV-?/i', '', $input) ?? $input);
}

function ezymart_warranty_generate_claim_number(): string
{
    return 'WC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function ezymart_warranty_claim_status_label(string $status): string
{
    $map = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'replaced' => 'Replaced',
        'rejected' => 'Rejected',
    ];
    return $map[strtolower($status)] ?? ucfirst($status);
}

/**
 * Save admin-entered product serials and sync warranty registry for warranty products.
 *
 * @param array<int, array<int, string>> $serials order_item_id => [unit_index => serial]
 * @return array{success:bool,message:string}
 */
function ezymart_warranty_save_order_serials(int $orderId, array $serials): array
{
    require_once __DIR__ . '/../dal/OrderDAL.php';
    require_once __DIR__ . '/../dal/OrderItemSerialDAL.php';
    require_once __DIR__ . '/../dal/WarrantyRegistryDAL.php';

    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    if (strtolower((string)($order['order_status'] ?? '')) === 'cancelled') {
        return ['success' => false, 'message' => 'Cannot assign serials to a cancelled order.'];
    }

    $items = $orderDAL->getOrderItems($orderId);
    $itemIds = array_map(static fn($item) => (int)$item['id'], $items);
    $serialDAL = new OrderItemSerialDAL();
    $registryDAL = new WarrantyRegistryDAL();

    foreach ($serials as $orderItemId => $unitSerials) {
        $orderItemId = (int)$orderItemId;
        if (!in_array($orderItemId, $itemIds, true) || !is_array($unitSerials)) {
            continue;
        }

        foreach ($unitSerials as $unitIndex => $serial) {
            $unitIndex = (int)$unitIndex;
            $serial = trim((string)$serial);
            if ($serial === '') {
                continue;
            }

            if ($serialDAL->serialInUse($serial, $orderItemId, $unitIndex)) {
                return ['success' => false, 'message' => 'Serial number "' . $serial . '" is already assigned to another product.'];
            }

            $existingRegistry = $registryDAL->getByOrderItemUnit($orderItemId, $unitIndex);
            if ($existingRegistry && $registryDAL->serialInUse($serial, (int)$existingRegistry['id'])) {
                return ['success' => false, 'message' => 'Serial number "' . $serial . '" is already registered for warranty.'];
            }
        }
    }

    foreach ($serials as $orderItemId => $unitSerials) {
        $orderItemId = (int)$orderItemId;
        if (!in_array($orderItemId, $itemIds, true) || !is_array($unitSerials)) {
            continue;
        }

        foreach ($unitSerials as $unitIndex => $serial) {
            $unitIndex = (int)$unitIndex;
            $serial = trim((string)$serial);
            $serialDAL->upsert($orderId, $orderItemId, $unitIndex, $serial);
        }
    }

    ezymart_warranty_sync_order_serials($orderId);

    return ['success' => true, 'message' => 'Product serial numbers saved.'];
}

/**
 * Register or update warranty registry rows using admin-entered serial numbers.
 */
function ezymart_warranty_sync_order_serials(int $orderId): void
{
    require_once __DIR__ . '/../dal/OrderDAL.php';
    require_once __DIR__ . '/../dal/ProductDAL.php';
    require_once __DIR__ . '/../dal/OrderItemSerialDAL.php';
    require_once __DIR__ . '/../dal/WarrantyRegistryDAL.php';

    $orderDAL = new OrderDAL();
    $order = $orderDAL->getById($orderId);
    if (!$order) {
        return;
    }

    if (strtolower((string)($order['order_status'] ?? '')) === 'cancelled') {
        return;
    }

    $paymentMethod = strtolower((string)($order['payment_method'] ?? ''));
    $paymentStatus = strtolower((string)($order['payment_status'] ?? ''));
    if ($paymentMethod === 'card' && $paymentStatus !== 'paid') {
        return;
    }

    $serialDAL = new OrderItemSerialDAL();
    $serialMap = $serialDAL->getMapByOrderId($orderId);
    if ($serialMap === []) {
        return;
    }

    $productDAL = new ProductDAL();
    $registryDAL = new WarrantyRegistryDAL();
    $items = $orderDAL->getOrderItems($orderId);
    $invoiceNumber = lumak_invoice_number($order);
    $purchaseDate = date('Y-m-d', strtotime($order['created_at']));
    $userId = !empty($order['user_id']) ? (int)$order['user_id'] : null;

    foreach ($items as $item) {
        $orderItemId = (int)$item['id'];
        $unitSerials = $serialMap[$orderItemId] ?? [];
        if ($unitSerials === []) {
            continue;
        }

        $product = $productDAL->getById((int)$item['product_id']);
        $period = trim((string)($product['warranty_period'] ?? ''));
        if ($period === '' || $period === 'none') {
            continue;
        }

        $expiresAt = ezymart_warranty_expiry_date($purchaseDate, $period);
        if (!$expiresAt) {
            continue;
        }

        foreach ($unitSerials as $unitIndex => $serial) {
            $unitIndex = (int)$unitIndex;
            $serial = trim((string)$serial);
            if ($serial === '') {
                continue;
            }

            $existing = $registryDAL->getByOrderItemUnit($orderItemId, $unitIndex);
            if ($existing) {
                if ($existing['serial_number'] !== $serial) {
                    $registryDAL->updateSerial((int)$existing['id'], $serial);
                }
                continue;
            }

            $registryDAL->create([
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'product_id' => (int)$item['product_id'],
                'user_id' => $userId,
                'invoice_number' => $invoiceNumber,
                'serial_number' => $serial,
                'product_name' => (string)$item['product_name'],
                'warranty_period' => $period,
                'purchase_date' => $purchaseDate,
                'expires_at' => $expiresAt,
                'unit_index' => $unitIndex,
            ]);
        }
    }
}

function ezymart_warranty_register_for_order(int $orderId): void
{
    // Warranty registry is created when admin enters product serial numbers during processing.
    ezymart_warranty_sync_order_serials($orderId);
}
