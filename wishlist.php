<?php
/**
 * Wishlist Page
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/dal/WishlistDAL.php';

$pageTitle = 'Wishlist - ' . ADMIN_BRAND;

// Must be logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . 'wishlist.php';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$wishlistDAL = new WishlistDAL();
$wishlistItems = $wishlistDAL->getByUser($_SESSION['user_id']);
$wishlistCount = count($wishlistItems);

require_once __DIR__ . '/views/partials/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo BASE_URL; ?>profile.php">My Account</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">Wishlist</span>
        </nav>
    </div>
</div>

<style>
.wishlist-section { padding: 30px 0 60px; }
.wishlist-section .profile-layout { grid-template-columns: 220px 1fr; }
.wishlist-section .profile-main { min-width: 0; width: 100%; }
.wishlist-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.wishlist-header h1 { font-size: 1.5rem; font-weight: 700; color: #0d1137; }
.wishlist-header h1 span { font-weight: 400; color: #888; font-size: 1rem; }
.wishlist-continue { font-size: 13px; color: #0d1137; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; }
.wishlist-continue:hover { color: #e8600a; }

.wishlist-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

.wishlist-card { background: #fff; border: 1px solid #eee; border-radius: 14px; overflow: hidden; position: relative; transition: all 0.25s; }
.wishlist-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.07); }
.wishlist-card-media { position: relative; }
.wishlist-card-img { width: 100%; height: 200px; background: #f8f8f8; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.wishlist-card-img img { width: 100%; height: 100%; object-fit: cover; }
.wishlist-card-img .placeholder { font-size: 2.5rem; color: #ddd; }
.wishlist-remove-form { position: absolute; top: 12px; right: 12px; z-index: 3; margin: 0; }
.wishlist-remove { width: 32px; height: 32px; background: #fff; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1); color: #ef4444; font-size: 14px; transition: all 0.2s; }
.wishlist-remove:hover { background: #ef4444; color: #fff; }
.wishlist-card__processing {
    position: absolute;
    inset: 0;
    z-index: 5;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(2px);
    pointer-events: none;
}
.wishlist-card__processing[hidden] { display: none !important; }
.wishlist-card__processing-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 18px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 8px 28px rgba(229, 169, 26, 0.16);
    color: #E5A91A;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    line-height: 1.3;
    min-width: 120px;
}
.wishlist-card__processing-inner > i { font-size: 24px; line-height: 1; }
.wishlist-card.is-removing { pointer-events: none; opacity: 0.85; }
.wishlist-card-body { padding: 16px; }
.wishlist-card-body .wl-category { font-size: 10px; color: #e8600a; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px; }
.wishlist-card-body h3 { font-size: 13px; font-weight: 600; color: #0d1137; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.wishlist-card-body h3 a { color: #0d1137; text-decoration: none; }
.wishlist-card-body h3 a:hover { color: #e8600a; }
.wishlist-card-body .wl-author { font-size: 11px; color: #888; margin-bottom: 10px; }
.wishlist-card-body .wl-price { font-size: 15px; font-weight: 700; color: #0d1137; margin-bottom: 12px; }
.wishlist-card-body .wl-price .old { font-size: 12px; color: #bbb; text-decoration: line-through; margin-left: 6px; }
.wl-actions { display: flex; gap: 8px; }
.wl-btn-cart { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 9px; background: #0d1137; color: #fff; border: none; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.wl-btn-cart:hover { background: #1a1f4e; }
.wl-btn-view { padding: 9px 14px; background: #f0f2f8; color: #0d1137; border: none; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; transition: all 0.2s; }
.wl-btn-view:hover { background: #e4e8f0; }

.wishlist-empty { text-align: center; padding: 60px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 350px; }
.wishlist-empty i { font-size: 4rem; color: #ddd; margin-bottom: 16px; }
.wishlist-empty h3 { font-size: 18px; color: #0d1137; margin-bottom: 8px; }
.wishlist-empty p { font-size: 13px; color: #888; margin-bottom: 20px; }
.wishlist-empty a { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #0d1137; color: #fff; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; }

@media (max-width: 992px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 1100px) { .wishlist-section .profile-layout { grid-template-columns: 200px 1fr; } }
@media (max-width: 768px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; } .wishlist-card-img { height: 160px; } }
@media (max-width: 480px) { .wishlist-grid { grid-template-columns: 1fr 1fr; gap: 10px; } .wishlist-card-body { padding: 12px; } .wishlist-card-body h3 { font-size: 12px; } }
</style>

<section class="wishlist-section">
    <div class="container">
        <div class="wishlist-header">
            <h1>My Wishlist <span id="wishlistPageCount">(<?php echo $wishlistCount; ?> Item<?php echo $wishlistCount !== 1 ? 's' : ''; ?>)</span></h1>
            <a href="<?php echo BASE_URL; ?>shop.php" class="wishlist-continue"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
        </div>

        <div class="profile-layout">
            <!-- Left Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-sidebar-card">
                    <h3 class="profile-sidebar-title">My Account</h3>
                    <nav class="profile-nav">
                        <a href="<?php echo BASE_URL; ?>profile.php" class="profile-nav-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>my-orders.php" class="profile-nav-item">
                            <i class="fas fa-box"></i> My Orders
                        </a>
                        <a href="<?php echo BASE_URL; ?>wishlist.php" class="profile-nav-item active">
                            <i class="far fa-heart"></i> Wishlist
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=address" class="profile-nav-item">
                            <i class="fas fa-map-marker-alt"></i> Address Book
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=payment" class="profile-nav-item">
                            <i class="fas fa-credit-card"></i> Payment Methods
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=password" class="profile-nav-item">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile.php?section=notifications" class="profile-nav-item">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="#" onclick="showLogoutConfirm(event)" class="profile-nav-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>

                <div class="profile-sidebar-help">
                    <h4>Need Help?</h4>
                    <p>We're here to help you</p>
                    <a href="#" class="profile-support-btn" onclick="event.preventDefault(); toggleChatbot();">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="profile-main">
        <?php if (empty($wishlistItems)): ?>
        <div class="wishlist-empty">
            <i class="far fa-heart"></i>
            <h3>Your wishlist is empty</h3>
            <p>Save items you love to your wishlist and buy them later.</p>
            <a href="<?php echo BASE_URL; ?>shop.php"><i class="fas fa-store"></i> Browse Products</a>
        </div>
        <?php else: ?>
        <div class="wishlist-grid" id="wishlistGrid">
            <?php foreach ($wishlistItems as $item): ?>
            <div class="wishlist-card" data-product-id="<?php echo (int)$item['product_id']; ?>">
                <div class="wishlist-card-media">
                    <div class="wishlist-card-img">
                        <?php if ($item['image']): ?>
                        <img src="<?php echo htmlspecialchars(media_image_url($item['image'])); ?>" alt="">
                        <?php else: ?>
                        <span class="placeholder"><i class="fas fa-tshirt"></i></span>
                        <?php endif; ?>
                    </div>
                    <form action="<?php echo BASE_URL; ?>wishlist-api.php" method="POST" class="wishlist-remove-form" data-ajax data-wishlist-remove>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <button type="submit" class="wishlist-remove" title="Remove" aria-label="Remove from wishlist"><i class="fas fa-times"></i></button>
                    </form>
                    <div class="wishlist-card__processing" hidden aria-hidden="true">
                        <span class="wishlist-card__processing-inner">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                            <span>Processing...</span>
                        </span>
                    </div>
                </div>
                <div class="wishlist-card-body">
                    <span class="wl-category"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></span>
                    <h3><a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($item['slug']); ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
                    <span class="wl-author">by <?php echo htmlspecialchars($item['author']); ?></span>
                    <div class="wl-price">
                        <?php echo format_price($item['price']); ?>
                        <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                        <span class="old"><?php echo format_price($item['original_price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="wl-actions">
                        <form action="<?php echo BASE_URL; ?>controllers/CartController.php" method="POST" style="flex:1;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect_url" value="<?php echo BASE_URL; ?>wishlist.php">
                            <button type="submit" class="wl-btn-cart"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                        </form>
                        <a href="<?php echo BASE_URL; ?>product.php?slug=<?php echo htmlspecialchars($item['slug']); ?>" class="wl-btn-view"><i class="fas fa-eye"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
