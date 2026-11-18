        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="sidebar-brand" title="<?php echo ADMIN_BRAND; ?>">
                    <img src="<?php echo asset_image_url('assets/images/logobottom.png'); ?>" alt="<?php echo ADMIN_BRAND; ?>" class="sidebar-logo sidebar-logo-full" style="width:auto; height:36px; border-radius:0;">
                </a>
                <span class="sidebar-logo-mini">LM</span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-item <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i><span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <p class="nav-section-title">MANAGE</p>
                    <a href="<?php echo BASE_URL; ?>admin/categories.php" class="nav-item <?php echo ($currentPage ?? '') === 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i><span>Categories</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/subcategories.php" class="nav-item <?php echo ($currentPage ?? '') === 'subcategories' ? 'active' : ''; ?>">
                        <i class="fas fa-sitemap"></i><span>Subcategories</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/products.php" class="nav-item <?php echo ($currentPage ?? '') === 'products' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i><span>Products</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/orders.php" class="nav-item <?php echo ($currentPage ?? '') === 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i><span>Orders</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/whatsapp-orders.php" class="nav-item <?php echo ($currentPage ?? '') === 'whatsapp-orders' ? 'active' : ''; ?>">
                        <i class="fab fa-whatsapp"></i><span>WhatsApp Orders</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/warranty-claims.php" class="nav-item <?php echo ($currentPage ?? '') === 'warranty-claims' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i><span>Warranty Claims</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/customers.php" class="nav-item <?php echo ($currentPage ?? '') === 'customers' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i><span>Customers</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/reviews.php" class="nav-item <?php echo ($currentPage ?? '') === 'reviews' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i><span>Reviews</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/coupons.php" class="nav-item <?php echo ($currentPage ?? '') === 'coupons' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i><span>Coupons</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/mega-deals.php" class="nav-item <?php echo ($currentPage ?? '') === 'mega-deals' ? 'active' : ''; ?>">
                        <i class="fas fa-fire" style="color:#ef4444;"></i><span>Mega Deals</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/chats.php" class="nav-item <?php echo ($currentPage ?? '') === 'chats' ? 'active' : ''; ?>">
                        <i class="fas fa-headset"></i><span>Chat Support</span>
                    </a>
                </div>

                <div class="nav-section">
                    <p class="nav-section-title">CONTENT</p>
                    <a href="<?php echo BASE_URL; ?>admin/banners.php" class="nav-item <?php echo ($currentPage ?? '') === 'banners' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i><span>Banners</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/testimonials.php" class="nav-item <?php echo ($currentPage ?? '') === 'testimonials' ? 'active' : ''; ?>">
                        <i class="fas fa-quote-left"></i><span>Testimonials</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/subscribers.php" class="nav-item <?php echo ($currentPage ?? '') === 'subscribers' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope-open-text"></i><span>Subscribers</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/sms-campaign.php" class="nav-item <?php echo ($currentPage ?? '') === 'sms-campaign' ? 'active' : ''; ?>">
                        <i class="fas fa-sms"></i><span>SMS Campaign</span>
                    </a>
                </div>

                <div class="nav-section">
                    <p class="nav-section-title">SETTINGS</p>
                    <a href="<?php echo BASE_URL; ?>admin/site-settings.php" class="nav-item <?php echo ($currentPage ?? '') === 'site-settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i><span>Site Settings</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/payment-methods.php" class="nav-item <?php echo ($currentPage ?? '') === 'payment-methods' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i><span>Payment Methods</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/ordering-process.php" class="nav-item <?php echo ($currentPage ?? '') === 'ordering-process' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i><span>Ordering Process</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/invoice-template.php" class="nav-item <?php echo ($currentPage ?? '') === 'invoice-template' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i><span>Invoice Template</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/admins.php" class="nav-item <?php echo ($currentPage ?? '') === 'admins' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i><span>Manage Admins</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="#" onclick="showAdminLogoutConfirm(event)" class="nav-item logout-item">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>

        <script>
        // Persist sidebar scroll position across page loads
        (function() {
            var sidebar = document.getElementById('sidebar');
            var saved = sessionStorage.getItem('sidebarScroll');
            if (saved) sidebar.scrollTop = parseInt(saved);
            sidebar.addEventListener('scroll', function() {
                sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
            });
        })();
        </script>
