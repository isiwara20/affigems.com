<?php
require_once __DIR__ . '/SiteSettingsDAL.php';

class ShippingRatesDAL {
    private SiteSettingsDAL $settings;

    public const SRI_LANKA_CODE = 'LK';

    public const DEFAULT_REST_OF_WORLD = 9940;

    public const DEFAULT_COUNTRIES = [
        ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸', 'fee' => 12400, 'enabled' => true],
        ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧', 'fee' => 11800, 'enabled' => true],
        ['code' => 'AU', 'name' => 'Australia', 'flag' => '🇦🇺', 'fee' => 10150, 'enabled' => true],
        ['code' => 'CA', 'name' => 'Canada', 'flag' => '🇨🇦', 'fee' => 15200, 'enabled' => true],
        ['code' => 'JP', 'name' => 'Japan', 'flag' => '🇯🇵', 'fee' => 8150, 'enabled' => true],
        ['code' => 'KR', 'name' => 'South Korea', 'flag' => '🇰🇷', 'fee' => 7600, 'enabled' => true],
        ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪', 'fee' => 10100, 'enabled' => true],
        ['code' => 'FR', 'name' => 'France', 'flag' => '🇫🇷', 'fee' => 10000, 'enabled' => true],
        ['code' => 'SG', 'name' => 'Singapore', 'flag' => '🇸🇬', 'fee' => 6700, 'enabled' => true],
        ['code' => 'MY', 'name' => 'Malaysia', 'flag' => '🇲🇾', 'fee' => 8150, 'enabled' => true],
    ];

    public function __construct() {
        $this->settings = new SiteSettingsDAL();
    }

    public function getConfig(): array {
        $raw = $this->settings->get('country_shipping_rates', '');
        $decoded = $raw ? json_decode($raw, true) : null;

        if (!is_array($decoded) || empty($decoded['countries'])) {
            return [
                'rest_of_world' => self::DEFAULT_REST_OF_WORLD,
                'countries' => self::DEFAULT_COUNTRIES,
            ];
        }

        $countries = [];
        foreach ($decoded['countries'] as $row) {
            if (empty($row['code']) || empty($row['name'])) {
                continue;
            }
            $countries[] = [
                'code' => strtoupper(trim($row['code'])),
                'name' => trim($row['name']),
                'flag' => trim($row['flag'] ?? ''),
                'fee' => max(0, (int)($row['fee'] ?? 0)),
                'enabled' => !empty($row['enabled']),
            ];
        }

        if (empty($countries)) {
            $countries = self::DEFAULT_COUNTRIES;
        }

        return [
            'rest_of_world' => max(0, (int)($decoded['rest_of_world'] ?? self::DEFAULT_REST_OF_WORLD)),
            'countries' => $countries,
        ];
    }

    public function saveConfig(array $config): void {
        $countries = [];
        foreach ($config['countries'] ?? [] as $row) {
            if (empty($row['code']) || empty($row['name'])) {
                continue;
            }
            $countries[] = [
                'code' => strtoupper(trim($row['code'])),
                'name' => trim($row['name']),
                'flag' => trim($row['flag'] ?? ''),
                'fee' => max(0, (int)($row['fee'] ?? 0)),
                'enabled' => !empty($row['enabled']),
            ];
        }

        $payload = [
            'rest_of_world' => max(0, (int)($config['rest_of_world'] ?? self::DEFAULT_REST_OF_WORLD)),
            'countries' => $countries,
        ];

        $this->settings->set('country_shipping_rates', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function getEnabledCountries(): array {
        $config = $this->getConfig();
        return array_values(array_filter($config['countries'], function ($row) {
            return !empty($row['enabled']);
        }));
    }

    public function getCountryLabel(string $code): string {
        $code = strtoupper(trim($code));
        if ($code === self::SRI_LANKA_CODE || $code === '') {
            return 'Sri Lanka';
        }

        foreach ($this->getConfig()['countries'] as $row) {
            if ($row['code'] === $code) {
                $flag = $row['flag'] ? $row['flag'] . ' ' : '';
                return $flag . $row['name'];
            }
        }

        return 'Rest of World';
    }

    /**
     * Calculate delivery fee for a country and cart subtotal.
     */
    public function calculateFee(string $countryCode, float $subtotal): int {
        $countryCode = strtoupper(trim($countryCode ?: self::SRI_LANKA_CODE));

        if ($countryCode === self::SRI_LANKA_CODE) {
            $deliveryFeeAmount = (int)$this->settings->get('delivery_fee', '250');
            $freeDeliveryThreshold = (int)$this->settings->get('free_delivery_threshold', '2500');
            if ($freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold) {
                return 0;
            }
            return $deliveryFeeAmount;
        }

        $config = $this->getConfig();
        foreach ($config['countries'] as $row) {
            if (!empty($row['enabled']) && $row['code'] === $countryCode) {
                return (int)$row['fee'];
            }
        }

        return (int)$config['rest_of_world'];
    }

    public function getFeeMapForCheckout(): array {
        $config = $this->getConfig();
        $map = [
            self::SRI_LANKA_CODE => [
                'fee' => (int)$this->settings->get('delivery_fee', '250'),
                'free_threshold' => (int)$this->settings->get('free_delivery_threshold', '2500'),
                'label' => 'Sri Lanka',
            ],
            '_rest' => ['fee' => (int)$config['rest_of_world'], 'label' => 'Rest of World'],
        ];

        foreach ($config['countries'] as $row) {
            if (empty($row['enabled'])) {
                continue;
            }
            $map[$row['code']] = [
                'fee' => (int)$row['fee'],
                'label' => trim(($row['flag'] ?? '') . ' ' . $row['name']),
            ];
        }

        return $map;
    }
}
