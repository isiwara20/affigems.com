    </main>
    </div><!-- end #page-wrapper -->
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <!-- Brand Column -->
                    <div class="footer-col footer-brand-col">
                        <div class="footer-brand-name">
                            <?php echo render_asset_image('assets/images/logobottom.png', ['alt' => 'ARIGATO.LK', 'class' => 'footer-logo-img', 'loading' => 'lazy']); ?>
                        </div>
                        <p class="brand-desc">ARIGATO.LK is your trusted online mobile store by <?php echo STORE_OWNER; ?>. Shop mobiles, accessories and tech products with island wide delivery across Sri Lanka.</p>
                        <div class="footer-contact">
                            <p class="footer-contact-line">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <button type="button" class="footer-address-link" onclick="openFooterMapPopup()" aria-label="View store location on map"><?php echo STORE_LOCATION; ?></button>
                            </p>
                            <p class="footer-contact-line footer-contact-optional"><i class="fas fa-globe" aria-hidden="true"></i><a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a></p>
                            <p class="footer-contact-line"><i class="fas fa-phone" aria-hidden="true"></i><a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></p>
                            <p class="footer-contact-line"><i class="fas fa-envelope" aria-hidden="true"></i><a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></p>
                        </div>
                        <div class="footer-social">
                            <a href="<?php echo htmlspecialchars(STORE_FACEBOOK_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="social-facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_INSTAGRAM_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="social-instagram"><i class="fab fa-instagram"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_TIKTOK_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok" class="social-tiktok"><i class="fab fa-tiktok"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_WHATSAPP_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="social-whatsapp"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>

                    <!-- Shop Column -->
                    <div class="footer-col">
                        <details class="footer-accordion" open>
                            <summary>Collections</summary>
                            <ul>
                            <?php
                            if (!class_exists('CategoryDAL')) {
                                require_once __DIR__ . '/../../dal/CategoryDAL.php';
                            }
                            try {
                                $footerCatDAL = new CategoryDAL();
                                $footerCats = array_slice($footerCatDAL->getActive(), 0, 4);
                                foreach ($footerCats as $fCat) {
                                    echo '<li><a href="' . BASE_URL . 'shop.php?category=' . htmlspecialchars($fCat['slug']) . '">' . htmlspecialchars($fCat['name']) . '</a></li>';
                                }
                            } catch (Exception $e) {
                                echo '<li><a href="' . BASE_URL . 'shop.php">All Products</a></li>';
                            }
                            ?>
                            </ul>
                        </details>
                    </div>

                    <!-- Useful Links Column -->
                    <div class="footer-col">
                        <details class="footer-accordion" open>
                            <summary>Customer Service</summary>
                            <ul>
                            <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                            <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                            <li><a href="<?php echo BASE_URL; ?>faq.php">FAQ</a></li>
                            <li><a href="<?php echo BASE_URL; ?>shipping-policy.php">Shipping Policy</a></li>
                            <li><a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a></li>
                            <li><a href="<?php echo BASE_URL; ?>track-order.php">Track Order</a></li>
                            <li><a href="<?php echo BASE_URL; ?>terms.php">Terms &amp; Conditions</a></li>
                            <li><a href="<?php echo BASE_URL; ?>privacy.php">Privacy Policy</a></li>
                            </ul>
                        </details>
                    </div>

                    <!-- My Account Column -->
                    <div class="footer-col">
                        <details class="footer-accordion" open>
                            <summary>My Account</summary>
                            <ul>
                            <li><a href="<?php echo BASE_URL; ?>login.php">Login / Register</a></li>
                            <li><a href="<?php echo BASE_URL; ?>my-orders.php">My Orders</a></li>
                            <li><a href="<?php echo BASE_URL; ?>wishlist.php">Wishlist</a></li>
                            <li><a href="<?php echo BASE_URL; ?>track-order.php">Track Order</a></li>
                            </ul>
                        </details>
                    </div>

                    <!-- Stay Updated Column -->
                    <div class="footer-col footer-newsletter-col">
                        <details class="footer-accordion" open>
                            <summary>Stay Updated</summary>
                            <div class="footer-accordion-body">
                        <p>Subscribe to get updates on new arrivals, offers and more.</p>
                        <form class="newsletter-form" data-ajax onsubmit="handleNewsletterSubscribe(event)">
                            <div class="newsletter-input-wrap">
                                <input type="email" id="newsletterEmail" placeholder="Enter your email" aria-label="Email for newsletter" required>
                                <button type="submit" class="btn-subscribe">Subscribe</button>
                            </div>
                        </form>
                        <div class="payment-section">
                            <p class="payment-title">We Accept</p>
                            <div class="payment-icons">
                                <?php echo render_asset_image('assets/images/payment-methods.png', [
                                    'alt' => 'Visa, Mastercard, Cash on Delivery, Bank Transfer',
                                    'class' => 'payment-methods-banner',
                                    'loading' => 'lazy',
                                    'width' => 420,
                                    'height' => 38,
                                ]); ?>
                            </div>
                        </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom-bar">
            <div class="container footer-bottom-content">
                <p class="footer-bottom-primary">&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All Rights Reserved. Owned by <?php echo STORE_OWNER; ?>. Developed by <a href="https://www.facebook.com/chandilagroups/" target="_blank" rel="noopener noreferrer">Chandila</a>.</p>
                <p class="footer-bottom-secondary"><?php echo STORE_TAGLINE; ?> — <?php echo STORE_DOMAIN; ?></p>
            </div>
        </div>
    </footer>

    <!-- Footer Map Popup -->
    <div class="footer-map-overlay" id="footerMapOverlay" onclick="if(event.target===this)closeFooterMapPopup()">
        <div class="footer-map-modal" role="dialog" aria-modal="true" aria-labelledby="footerMapTitle">
            <button type="button" class="footer-map-close" onclick="closeFooterMapPopup()" aria-label="Close map">&times;</button>
            <h3 id="footerMapTitle"><i class="fas fa-map-marker-alt"></i> Our Location</h3>
            <p class="footer-map-address"><?php echo htmlspecialchars(STORE_LOCATION); ?></p>
            <div class="footer-map-frame">
                <iframe
                    src="<?php echo htmlspecialchars(store_map_embed_url()); ?>"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="ARIGATO.LK store location map"></iframe>
            </div>
            <a href="<?php echo htmlspecialchars(store_map_open_url()); ?>" target="_blank" rel="noopener noreferrer" class="footer-map-open-btn" data-turbo="false">
                <i class="fas fa-external-link-alt"></i> Open in Google Maps
            </a>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="logout-overlay" id="logoutOverlay">
        <div class="logout-modal">
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out of your account?</p>
            <div class="logout-modal-actions">
                <button class="logout-cancel-btn" onclick="hideLogoutConfirm()">Cancel</button>
                <a href="<?php echo BASE_URL; ?>controllers/AuthController.php?action=logout" class="logout-confirm-btn" data-turbo="false" onclick="clearRestockWaitingStorage()">Yes, Logout</a>
            </div>
        </div>
    </div>

    <style>
    .logout-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .logout-overlay.show { display: flex; animation: fadeInOverlay 0.2s ease; }
    .logout-modal { background: #fff; border-radius: 16px; padding: 36px; max-width: 380px; width: 90%; text-align: center; animation: scaleIn 0.25s ease; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
    .logout-modal-icon { width: 60px; height: 60px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 24px; color: #ef4444; }
    .logout-modal h3 { font-size: 18px; font-weight: 700; color: #0d1137; margin-bottom: 8px; }
    .logout-modal p { font-size: 13px; color: #666; margin-bottom: 24px; line-height: 1.5; }
    .logout-modal-actions { display: flex; gap: 12px; justify-content: center; }
    .logout-cancel-btn { padding: 11px 24px; background: #f0f2f8; color: #333; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; transition: background 0.2s; }
    .logout-cancel-btn:hover { background: #e4e8f0; }
    .logout-confirm-btn { padding: 11px 24px; background: #ef4444; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; text-decoration: none; display: inline-flex; align-items: center; transition: background 0.2s; }
    .logout-confirm-btn:hover { background: #dc2626; }
    @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }
    @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .footer-address-link:hover { color: #fff; }

    .footer-map-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 16px;
        backdrop-filter: blur(4px);
    }
    .footer-map-overlay.show { display: flex; animation: fadeInOverlay 0.2s ease; }
    .footer-map-modal {
        background: #fff;
        border-radius: 18px;
        padding: 24px;
        width: min(560px, 100%);
        max-height: 90vh;
        overflow: auto;
        position: relative;
        animation: scaleIn 0.25s ease;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    }
    .footer-map-close {
        position: absolute;
        top: 12px;
        right: 14px;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 50%;
        background: #f0f2f8;
        color: #333;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
    }
    .footer-map-close:hover { background: #e4e8f0; }
    .footer-map-modal h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0d1137;
        margin: 0 36px 8px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .footer-map-modal h3 i { color: #E0A810; }
    .footer-map-address {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
        margin: 0 0 14px;
    }
    .footer-map-frame {
        width: 100%;
        height: 280px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #F5E6B8;
        margin-bottom: 14px;
    }
    .footer-map-open-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        background: #E0A810;
        color: #1a1a1a;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
    }
    .footer-map-open-btn:hover { color: #1a1a1a; opacity: 0.95; background: #D49A0A; }
    @media (max-width: 480px) {
        .footer-map-frame { height: 220px; }
        .footer-map-modal { padding: 20px 16px; }
    }
    </style>

    <script>
    function clearRestockWaitingStorage() {
        try {
            var keysToRemove = [];
            for (var i = 0; i < sessionStorage.length; i++) {
                var key = sessionStorage.key(i);
                if (key && key.indexOf('ezymart_restock_') === 0) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(function(key) {
                sessionStorage.removeItem(key);
            });
        } catch (e) {}
    }

    function showLogoutConfirm(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('logoutOverlay').classList.add('show');
        // Close profile dropdown
        var pd = document.getElementById('profileDropdown');
        if (pd) pd.classList.remove('show');
    }
    function hideLogoutConfirm() {
        document.getElementById('logoutOverlay').classList.remove('show');
    }
    // Close on overlay click
    document.getElementById('logoutOverlay').addEventListener('click', function(e) {
        if (e.target === this) hideLogoutConfirm();
    });

    function openFooterMapPopup() {
        document.getElementById('footerMapOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeFooterMapPopup() {
        document.getElementById('footerMapOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeFooterMapPopup();
    });
    </script>

    <!-- Prevent form resubmission on page reload -->
    <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // ---- Global Double-Click Prevention ----
    // Prevents duplicate form submissions on all forms site-wide
    (function() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.tagName !== 'FORM') return;
            
            // Skip AJAX-handled forms (newsletter, etc.) - they handle their own state
            if (form.hasAttribute('data-ajax')) return;
            
            var card = form.closest('.ez-product-card');
            var useCardOverlay = card && form.hasAttribute('data-card-overlay');
            var btn = form.querySelector('button[type="submit"], input[type="submit"], .btn-place-order, .btn-login');
            if (!btn && !useCardOverlay) return;
            
            // If already submitting, block the second click
            if (form.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            
            // Mark as submitting
            form.dataset.submitting = 'true';

            if (useCardOverlay) {
                var overlay = card.querySelector('.ez-product-card__processing');
                if (overlay) {
                    overlay.hidden = false;
                    overlay.setAttribute('aria-hidden', 'false');
                }
                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                }
                card.querySelectorAll('button[type="submit"]').forEach(function(otherBtn) {
                    if (otherBtn !== btn) {
                        otherBtn.disabled = true;
                        otherBtn.style.opacity = '0.7';
                        otherBtn.style.cursor = 'not-allowed';
                    }
                });

                setTimeout(function() {
                    form.dataset.submitting = 'false';
                    if (overlay) {
                        overlay.hidden = true;
                        overlay.setAttribute('aria-hidden', 'true');
                    }
                    card.querySelectorAll('button[type="submit"]').forEach(function(otherBtn) {
                        otherBtn.disabled = false;
                        otherBtn.style.opacity = '';
                        otherBtn.style.cursor = '';
                    });
                }, 30000);
                return;
            }
            
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
            
            // Store original text
            var originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Safety: re-enable after 30 seconds in case something goes wrong
            setTimeout(function() {
                form.dataset.submitting = 'false';
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
                btn.innerHTML = originalHTML;
            }, 30000);
        });
    })();

    // Product card wishlist — AJAX toggle (no page reload / scroll jump)
    (function() {
        function resetWishOverlay(card, btn) {
            if (card) {
                var overlay = card.querySelector('.ez-product-card__processing');
                if (overlay) {
                    overlay.hidden = true;
                    overlay.setAttribute('aria-hidden', 'true');
                }
            }
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
            }
        }

        function updateWishlistUi(form, btn, inWishlist) {
            var actionInput = form.querySelector('input[name="action"]');
            var icon = btn ? btn.querySelector('i') : null;
            var label = btn ? btn.querySelector('.product-wishlist-btn__label') : null;

            if (actionInput) {
                actionInput.value = inWishlist ? 'remove' : 'add';
            }
            if (btn) {
                btn.classList.toggle('is-active', inWishlist);
                btn.setAttribute('aria-label', inWishlist ? 'Remove from wishlist' : 'Add to wishlist');
            }
            if (icon) {
                icon.className = inWishlist ? 'fas fa-heart' : 'far fa-heart';
            }
            if (label) {
                label.textContent = inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist';
            }
        }

        function bindProductCardWishlistForms() {
            document.querySelectorAll('[data-wishlist-toggle]:not([data-wish-bound])').forEach(function(form) {
                form.setAttribute('data-wish-bound', '1');

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var card = form.closest('.ez-product-card');
                    var btn = form.querySelector('button[type="submit"]');
                    var overlay = card ? card.querySelector('.ez-product-card__processing') : null;

                    if (form.dataset.submitting === 'true') return;
                    form.dataset.submitting = 'true';

                    if (overlay) {
                        overlay.hidden = false;
                        overlay.setAttribute('aria-hidden', 'false');
                    }
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '0.7';
                        btn.style.cursor = 'not-allowed';
                    }

                    var body = new URLSearchParams(new FormData(form));
                    body.set('ajax', '1');

                    var apiUrl = form.getAttribute('action') || '';
                    if (apiUrl.indexOf('ajax=1') === -1) {
                        apiUrl += (apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'ajax=1';
                    }

                    fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: body.toString(),
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        return res.text().then(function(text) {
                            var data = null;
                            try {
                                data = text ? JSON.parse(text) : null;
                            } catch (parseErr) {
                                throw new Error('Could not update wishlist. Please refresh the page and try again.');
                            }
                            if (!data) {
                                throw new Error('Could not update wishlist. Please refresh the page and try again.');
                            }
                            if (!res.ok || !data.success) {
                                if (data.login_required) {
                                    throw { loginRequired: true };
                                }
                                throw new Error(data.message || 'Could not update wishlist.');
                            }
                            return data;
                        });
                    })
                    .then(function(data) {
                        updateWishlistUi(form, btn, !!data.in_wishlist);

                        var meta = document.querySelector('meta[name="lumak-wish-count"]');
                        if (meta && typeof data.wishlist_count === 'number') {
                            meta.setAttribute('content', String(data.wishlist_count));
                        }
                        if (typeof window.syncHeaderFromMeta === 'function') {
                            window.syncHeaderFromMeta();
                        }
                    })
                    .catch(function(err) {
                        if (err && err.loginRequired) {
                            window.location.href = <?php echo json_encode(BASE_URL . 'login.php'); ?>;
                            return;
                        }
                        alert((err && err.message) ? err.message : 'Could not update wishlist. Please try again.');
                    })
                    .finally(function() {
                        form.dataset.submitting = 'false';
                        resetWishOverlay(card, btn);
                    });
                });
            });
        }

        bindProductCardWishlistForms();
        document.addEventListener('turbo:load', bindProductCardWishlistForms);
    })();

    // Wishlist page — remove item with centered overlay
    (function() {
        function updateWishlistPageCount(count) {
            var el = document.getElementById('wishlistPageCount');
            if (!el) return;
            el.textContent = '(' + count + ' Item' + (count === 1 ? '' : 's') + ')';
        }

        function showWishlistEmptyState() {
            var grid = document.getElementById('wishlistGrid');
            var main = grid ? grid.closest('.profile-main') : null;
            if (!main) return;
            main.innerHTML =
                '<div class="wishlist-empty">' +
                '<i class="far fa-heart"></i>' +
                '<h3>Your wishlist is empty</h3>' +
                '<p>Save items you love to your wishlist and buy them later.</p>' +
                '<a href="<?php echo BASE_URL; ?>shop.php"><i class="fas fa-store"></i> Browse Products</a>' +
                '</div>';
        }

        function bindWishlistRemoveForms() {
            document.querySelectorAll('[data-wishlist-remove]:not([data-wish-bound])').forEach(function(form) {
                form.setAttribute('data-wish-bound', '1');

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var card = form.closest('.wishlist-card');
                    var btn = form.querySelector('button[type="submit"]');
                    var overlay = card ? card.querySelector('.wishlist-card__processing') : null;

                    if (form.dataset.submitting === 'true') return;
                    form.dataset.submitting = 'true';

                    if (card) card.classList.add('is-removing');
                    if (overlay) {
                        overlay.hidden = false;
                        overlay.setAttribute('aria-hidden', 'false');
                    }
                    if (btn) btn.disabled = true;

                    var body = new URLSearchParams(new FormData(form));
                    body.set('ajax', '1');

                    var apiUrl = form.getAttribute('action') || '';
                    if (apiUrl.indexOf('ajax=1') === -1) {
                        apiUrl += (apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'ajax=1';
                    }

                    fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: body.toString(),
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        return res.text().then(function(text) {
                            var data = null;
                            try {
                                data = text ? JSON.parse(text) : null;
                            } catch (err) {
                                throw new Error('Could not remove item. Please refresh and try again.');
                            }
                            if (!res.ok || !data || !data.success) {
                                throw new Error((data && data.message) ? data.message : 'Could not remove item.');
                            }
                            return data;
                        });
                    })
                    .then(function(data) {
                        var meta = document.querySelector('meta[name="lumak-wish-count"]');
                        if (meta && typeof data.wishlist_count === 'number') {
                            meta.setAttribute('content', String(data.wishlist_count));
                            updateWishlistPageCount(data.wishlist_count);
                        }
                        if (typeof window.syncHeaderFromMeta === 'function') {
                            window.syncHeaderFromMeta();
                        }

                        if (!card) return;

                        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.96)';

                        setTimeout(function() {
                            card.remove();
                            var grid = document.getElementById('wishlistGrid');
                            if (grid && grid.children.length === 0) {
                                showWishlistEmptyState();
                            }
                        }, 260);
                    })
                    .catch(function(err) {
                        if (card) card.classList.remove('is-removing');
                        if (overlay) {
                            overlay.hidden = true;
                            overlay.setAttribute('aria-hidden', 'true');
                        }
                        if (btn) btn.disabled = false;
                        alert((err && err.message) ? err.message : 'Could not remove item. Please try again.');
                    })
                    .finally(function() {
                        form.dataset.submitting = 'false';
                    });
                });
            });
        }

        bindWishlistRemoveForms();
        document.addEventListener('turbo:load', bindWishlistRemoveForms);
    })();

    // Footer accordions — collapsed on mobile for shorter scroll
    (function() {
        var footerMq = window.matchMedia('(max-width: 768px)');

        function syncFooterAccordions() {
            document.querySelectorAll('.footer-accordion').forEach(function(panel) {
                if (footerMq.matches) {
                    panel.removeAttribute('open');
                } else {
                    panel.setAttribute('open', '');
                }
            });
        }

        syncFooterAccordions();
        if (typeof footerMq.addEventListener === 'function') {
            footerMq.addEventListener('change', syncFooterAccordions);
        } else if (typeof footerMq.addListener === 'function') {
            footerMq.addListener(syncFooterAccordions);
        }
        document.addEventListener('turbo:load', syncFooterAccordions);
    })();

    // Newsletter Subscribe
    function handleNewsletterSubscribe(e) {
        e.preventDefault();
        var email = document.getElementById('newsletterEmail').value.trim();
        if (!email) return;

        // Prevent double-click
        var btn = e.target.querySelector('.btn-subscribe');
        if (btn.disabled) return;
        btn.disabled = true;
        var originalText = btn.textContent;
        btn.textContent = 'Sending...';
        btn.style.opacity = '0.7';

        fetch('<?php echo BASE_URL; ?>controllers/SubscribeController.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=subscribe&email=' + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('newsletterEmail').value = '';
                showSubscribeOverlay(data.message, true);
            } else {
                showSubscribeOverlay(data.error, false);
            }
        })
        .catch(function() {
            showSubscribeOverlay('Something went wrong. Please try again.', false);
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = originalText;
            btn.style.opacity = '';
        });
    }

    function showSubscribeOverlay(message, success) {
        var overlay = document.getElementById('subscribeOverlay');
        var icon = document.getElementById('subscribeOverlayIcon');
        var title = document.getElementById('subscribeOverlayTitle');
        var text = document.getElementById('subscribeOverlayText');

        icon.innerHTML = success ? '<i class="fas fa-check-circle" style="color:#16a34a;"></i>' : '<i class="fas fa-exclamation-circle" style="color:#ef4444;"></i>';
        title.textContent = success ? 'Subscribed!' : 'Oops!';
        text.textContent = message;
        overlay.classList.add('show');
    }

    function hideSubscribeOverlay() {
        document.getElementById('subscribeOverlay').classList.remove('show');
    }
    </script>

    <!-- Subscribe Success Overlay -->
    <div class="subscribe-overlay" id="subscribeOverlay" onclick="if(event.target===this)hideSubscribeOverlay()">
        <div class="subscribe-modal">
            <div class="subscribe-modal-icon" id="subscribeOverlayIcon">
                <i class="fas fa-check-circle" style="color:#16a34a;"></i>
            </div>
            <h3 id="subscribeOverlayTitle">Subscribed!</h3>
            <p id="subscribeOverlayText">You have been successfully subscribed to our newsletter.</p>
            <button class="subscribe-modal-btn" onclick="hideSubscribeOverlay()">Got it</button>
        </div>
    </div>

    <style>
    .subscribe-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
    .subscribe-overlay.show { display:flex; animation:fadeInOverlay 0.2s ease; }
    .subscribe-modal { background:#fff; border-radius:18px; padding:40px; max-width:360px; width:90%; text-align:center; animation:scaleIn 0.25s ease; box-shadow:0 20px 50px rgba(0,0,0,0.15); }
    .subscribe-modal-icon { font-size:48px; margin-bottom:16px; }
    .subscribe-modal h3 { font-size:20px; font-weight:700; color:#0d1137; margin-bottom:8px; }
    .subscribe-modal p { font-size:13px; color:#666; line-height:1.6; margin-bottom:24px; }
    .subscribe-modal-btn { padding:12px 30px; background:#E0A810; color:#1a1a1a; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s; }
    .subscribe-modal-btn:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(0,82,212,0.3); }
    </style>

    <?php 
    // Mobile bottom navigation + chatbot (storefront only)
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo '<div class="mobile-chrome-stack" id="mobileChromeStack" data-turbo-permanent>';
        require_once __DIR__ . '/mobile-bottom-nav.php';
        require_once __DIR__ . '/chatbot.php';
        echo '</div>';
    }
    ?>
    <script>
    (function() {
        function metaContent(name) {
            var el = document.querySelector('meta[name="' + name + '"]');
            return el ? el.getAttribute('content') : '';
        }

        function syncHeaderFromMeta() {
            var cartCount = parseInt(metaContent('lumak-cart-count'), 10) || 0;
            var cartTotal = metaContent('lumak-cart-total');
            var wishCount = parseInt(metaContent('lumak-wish-count'), 10) || 0;
            var currency = metaContent('lumak-currency');

            var cartBadge = document.getElementById('headerCartBadge');
            var cartTotalEl = document.getElementById('headerCartTotal');
            var wishBadge = document.getElementById('headerWishBadge');
            var wishSub = document.getElementById('headerWishSub');

            document.querySelectorAll('.js-header-cart-badge').forEach(function(badge) {
                if (badge.textContent !== String(cartCount)) badge.textContent = cartCount;
            });
            if (cartTotalEl && cartTotal && cartTotalEl.textContent !== cartTotal) cartTotalEl.textContent = cartTotal;
            if (wishBadge) {
                if (wishBadge.textContent !== String(wishCount)) wishBadge.textContent = wishCount;
                wishBadge.style.display = wishCount > 0 ? '' : 'none';
            }
            if (wishSub) {
                var wishText = wishCount + ' Item' + (wishCount !== 1 ? 's' : '');
                if (wishSub.textContent !== wishText) wishSub.textContent = wishText;
            }
            if (currency) {
                document.querySelectorAll('.js-current-currency').forEach(function(el) {
                    if (el.textContent !== currency) el.textContent = currency;
                });
            }
        }

        function closeHeaderMenus() {
            var mobileMenu = document.getElementById('mobileMenu');
            var menuIcon = document.getElementById('menuIcon');
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                if (menuIcon) {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
            ['langDropdown', 'currencyDropdown', 'profileDropdown', 'allCatDropdown'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('show');
            });
            document.querySelectorAll('.currency-dropdown.show, .lang-dropdown.show').forEach(function(el) {
                el.classList.remove('show');
            });
            var catBtn = document.getElementById('allCatBtn');
            if (catBtn) catBtn.classList.remove('active');
            if (typeof window.closeMobileCatSheet === 'function') window.closeMobileCatSheet();
        }

        var topBarScrollBound = false;
        var topBarHeight = 38;
        var topBarHideAt = 50;
        var topBarShowAt = 4;

        function updateTopBarOnScroll() {
            var chrome = document.getElementById('site-chrome');
            if (!chrome) return;

            var y = window.scrollY;
            var hidden = chrome.classList.contains('is-scrolled');

            if (!hidden && y > topBarHideAt) {
                chrome.classList.add('is-scrolled');
                window.scrollTo(0, Math.max(0, y - topBarHeight));
                closeHeaderMenus();
            } else if (hidden && y <= topBarShowAt) {
                chrome.classList.remove('is-scrolled');
            }
        }

        function initTopBarScroll() {
            updateTopBarOnScroll();
            if (topBarScrollBound) return;
            topBarScrollBound = true;
            var ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(function() {
                        updateTopBarOnScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        }

        document.addEventListener('turbo:load', syncHeaderFromMeta);
        document.addEventListener('turbo:load', initTopBarScroll);
        document.addEventListener('turbo:before-visit', closeHeaderMenus);
        document.addEventListener('turbo:before-render', function() {
            if (window.scrollY < 5) window.scrollTo(0, 0);
        });
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                syncHeaderFromMeta();
                initTopBarScroll();
            });
        } else {
            syncHeaderFromMeta();
            initTopBarScroll();
        }

        window.syncHeaderFromMeta = syncHeaderFromMeta;
    })();
    </script>
</body>
</html>
