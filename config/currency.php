<?php
/**
 * Currency configuration — base prices in database are stored in LKR.
 * Rate = how many LKR equal 1 unit of foreign currency.
 */
define('CURRENCY_BASE', 'LKR');

define('CURRENCIES', [
    'LKR' => [
        'code'   => 'LKR',
        'symbol' => 'LKR',
        'name'   => 'Sri Lankan Rupee',
        'rate'   => 1,
        'decimals' => 0,
    ],
    'USD' => [
        'code'   => 'USD',
        'symbol' => '$',
        'name'   => 'US Dollar',
        'rate'   => 305,
        'decimals' => 2,
    ],
    'EUR' => [
        'code'   => 'EUR',
        'symbol' => '€',
        'name'   => 'Euro',
        'rate'   => 330,
        'decimals' => 2,
    ],
    'GBP' => [
        'code'   => 'GBP',
        'symbol' => '£',
        'name'   => 'British Pound',
        'rate'   => 385,
        'decimals' => 2,
    ],
    'AUD' => [
        'code'   => 'AUD',
        'symbol' => 'A$',
        'name'   => 'Australian Dollar',
        'rate'   => 200,
        'decimals' => 2,
    ],
]);
