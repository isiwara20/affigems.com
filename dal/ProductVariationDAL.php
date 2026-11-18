<?php
require_once __DIR__ . '/../config/database.php';

class ProductVariationDAL
{
    private $conn;

    public const PRESET_OPTIONS = [
        'Size',
        'Color',
        'Material',
        'Weight',
        'Design / Style',
        'Capacity',
        'Pack Quantity',
    ];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception('Database connection failed.');
        }
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS product_variation_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            option_name VARCHAR(100) NOT NULL,
            option_values TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_product_variation_options_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS product_variations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            sku VARCHAR(100) NULL,
            options_json TEXT NOT NULL,
            options_label VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NULL,
            stock INT NOT NULL DEFAULT 0,
            image VARCHAR(255) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_product_variations_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $migrations = [
            "ALTER TABLE products ADD COLUMN has_variations TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE cart ADD COLUMN variation_id INT NOT NULL DEFAULT 0",
            "ALTER TABLE order_items ADD COLUMN variation_id INT NULL",
            "ALTER TABLE order_items ADD COLUMN variation_label VARCHAR(255) NULL",
        ];
        foreach ($migrations as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (Exception $e) {
                // Column already exists
            }
        }

        try {
            $this->conn->exec('ALTER TABLE cart DROP INDEX unique_cart_item');
        } catch (Exception $e) {
        }
        try {
            $this->conn->exec('ALTER TABLE cart ADD UNIQUE KEY unique_cart_item (user_id, product_id, variation_id)');
        } catch (Exception $e) {
        }
    }

    public function getProductVariationData(int $productId): array
    {
        $stmt = $this->conn->prepare('SELECT has_variations FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        $hasVariations = !empty($product['has_variations']);

        if (!$hasVariations) {
            return [
                'has_variations' => false,
                'options' => [],
                'variations' => [],
            ];
        }

        return [
            'has_variations' => true,
            'options' => $this->getOptionsByProduct($productId),
            'variations' => $this->getVariationsByProduct($productId),
        ];
    }

    public function getOptionsByProduct(int $productId): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM product_variation_options WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll();
        $options = [];
        foreach ($rows as $row) {
            $values = json_decode($row['option_values'], true);
            if (!is_array($values)) {
                $values = [];
            }
            $options[] = [
                'id' => (int)$row['id'],
                'name' => $row['option_name'],
                'values' => array_values(array_filter(array_map('trim', $values))),
            ];
        }
        return $options;
    }

    public function getVariationsByProduct(int $productId, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM product_variations WHERE product_id = :product_id';
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll();
        $variations = [];
        foreach ($rows as $row) {
            $options = json_decode($row['options_json'], true);
            if (!is_array($options)) {
                $options = [];
            }
            $variations[] = [
                'id' => (int)$row['id'],
                'sku' => $row['sku'],
                'options' => $options,
                'label' => $row['options_label'],
                'price' => $row['price'] !== null ? (float)$row['price'] : null,
                'stock' => (int)$row['stock'],
                'image' => $row['image'],
                'status' => $row['status'],
            ];
        }
        return $variations;
    }

    public function getVariationById(int $variationId): ?array
    {
        $stmt = $this->conn->prepare('SELECT pv.*, p.has_variations, p.price AS base_price, p.status AS product_status
            FROM product_variations pv
            JOIN products p ON p.id = pv.product_id
            WHERE pv.id = :id LIMIT 1');
        $stmt->execute([':id' => $variationId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $options = json_decode($row['options_json'], true);
        return [
            'id' => (int)$row['id'],
            'product_id' => (int)$row['product_id'],
            'sku' => $row['sku'],
            'options' => is_array($options) ? $options : [],
            'label' => $row['options_label'],
            'price' => $row['price'] !== null ? (float)$row['price'] : (float)$row['base_price'],
            'stock' => (int)$row['stock'],
            'image' => $row['image'],
            'status' => $row['status'],
            'product_status' => $row['product_status'],
            'has_variations' => (int)$row['has_variations'],
        ];
    }

    public function saveProductVariations(int $productId, bool $hasVariations, array $options, array $variations): void
    {
        $stmt = $this->conn->prepare('UPDATE products SET has_variations = :has_variations WHERE id = :id');
        $stmt->execute([
            ':has_variations' => $hasVariations ? 1 : 0,
            ':id' => $productId,
        ]);

        $this->conn->prepare('DELETE FROM product_variation_options WHERE product_id = :product_id')
            ->execute([':product_id' => $productId]);
        $this->conn->prepare('DELETE FROM product_variations WHERE product_id = :product_id')
            ->execute([':product_id' => $productId]);

        if (!$hasVariations) {
            return;
        }

        $optionStmt = $this->conn->prepare('INSERT INTO product_variation_options (product_id, option_name, option_values, sort_order) VALUES (:product_id, :option_name, :option_values, :sort_order)');
        foreach ($options as $index => $option) {
            $name = trim((string)($option['name'] ?? ''));
            $values = $option['values'] ?? [];
            if ($name === '' || empty($values)) {
                continue;
            }
            $optionStmt->execute([
                ':product_id' => $productId,
                ':option_name' => $name,
                ':option_values' => json_encode(array_values($values), JSON_UNESCAPED_UNICODE),
                ':sort_order' => $index,
            ]);
        }

        $variationStmt = $this->conn->prepare('INSERT INTO product_variations (product_id, sku, options_json, options_label, price, stock, status, sort_order) VALUES (:product_id, :sku, :options_json, :options_label, :price, :stock, :status, :sort_order)');
        $totalStock = 0;
        foreach ($variations as $index => $variation) {
            $opts = $variation['options'] ?? [];
            if (empty($opts) || !is_array($opts)) {
                continue;
            }
            $label = $this->buildOptionsLabel($opts);
            $price = isset($variation['price']) && $variation['price'] !== '' ? (float)$variation['price'] : null;
            $stock = max(0, (int)($variation['stock'] ?? 0));
            $totalStock += $stock;
            $sku = trim((string)($variation['sku'] ?? ''));
            $variationStmt->execute([
                ':product_id' => $productId,
                ':sku' => $sku !== '' ? $sku : null,
                ':options_json' => json_encode($opts, JSON_UNESCAPED_UNICODE),
                ':options_label' => $label,
                ':price' => $price,
                ':stock' => $stock,
                ':status' => ($variation['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
                ':sort_order' => $index,
            ]);
        }

        $stockStmt = $this->conn->prepare('UPDATE products SET stock = :stock WHERE id = :id');
        $stockStmt->execute([':stock' => $totalStock, ':id' => $productId]);
    }

    public function buildOptionsLabel(array $options): string
    {
        $parts = [];
        foreach ($options as $name => $value) {
            $parts[] = $name . ': ' . $this->formatOptionValueForLabel((string)$name, (string)$value);
        }
        return implode(' / ', $parts);
    }

    private function formatOptionValueForLabel(string $name, string $value): string
    {
        if (strtolower(trim($name)) === 'color') {
            if (strpos($value, '|') !== false) {
                return trim(explode('|', $value, 2)[0]);
            }
            if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
                return strtoupper($value);
            }
        }
        return $value;
    }

    public function decrementVariationStock(int $variationId, int $quantity): bool
    {
        $stmt = $this->conn->prepare('UPDATE product_variations SET stock = stock - :qty WHERE id = :id AND stock >= :qty');
        $ok = $stmt->execute([':qty' => $quantity, ':id' => $variationId]);
        if (!$ok || $stmt->rowCount() === 0) {
            return false;
        }

        $variation = $this->getVariationById($variationId);
        if ($variation) {
            $sumStmt = $this->conn->prepare('SELECT COALESCE(SUM(stock), 0) AS total_stock FROM product_variations WHERE product_id = :product_id AND status = \'active\'');
            $sumStmt->execute([':product_id' => $variation['product_id']]);
            $total = (int)($sumStmt->fetch()['total_stock'] ?? 0);
            $this->conn->prepare('UPDATE products SET stock = :stock WHERE id = :id')
                ->execute([':stock' => $total, ':id' => $variation['product_id']]);
        }
        return true;
    }
}
