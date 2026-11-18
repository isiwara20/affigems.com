<?php
/**
 * Review Controller
 * Handles review submission by logged-in users
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/ReviewDAL.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_review') {
        // Check if user is logged in
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
            $_SESSION['login_error'] = 'Please login to submit a review.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');
        $slug = $_POST['product_slug'] ?? '';

        // Validate
        if ($productId === 0 || empty($comment)) {
            header('Location: ' . BASE_URL . 'product.php?slug=' . urlencode($slug) . '&review_error=1');
            exit;
        }

        if ($rating < 1) $rating = 1;
        if ($rating > 5) $rating = 5;

        $reviewDAL = new ReviewDAL();
        $userName = $_SESSION['user_name'] ?? 'Anonymous';
        $userId = $_SESSION['user_id'];

        $result = $reviewDAL->create($productId, $userId, $userName, $rating, $comment);

        if ($result) {
            header('Location: ' . BASE_URL . 'product.php?slug=' . urlencode($slug) . '&review_success=1#tab-reviews');
        } else {
            header('Location: ' . BASE_URL . 'product.php?slug=' . urlencode($slug) . '&review_error=1#tab-reviews');
        }
        exit;
    }
}

header('Location: ' . BASE_URL);
exit;
