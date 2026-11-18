<?php
/**
 * Search Controller
 * Handles AJAX search suggestions
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ProductDAL.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $productDAL = new ProductDAL();
    $results = $productDAL->search($query);
    
    $suggestions = array_map(function($p) {
        return [
            'id' => $p['id'],
            'name' => $p['name'],
            'slug' => $p['slug'],
            'price' => $p['price'],
            'formatted_price' => format_price($p['price']),
            'image' => $p['image'],
            'category' => $p['category_name'] ?? ''
        ];
    }, array_slice($results, 0, 6));
    
    echo json_encode(['results' => $suggestions]);
} catch (Exception $e) {
    echo json_encode(['results' => []]);
}
