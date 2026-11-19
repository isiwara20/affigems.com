<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "FAQ - " . SITE_NAME;
require_once __DIR__ . '/partials/header.php';
?>

<section class="page-section">
    <div class="container">
        <div class="page-content">
            <h1 class="page-title">Frequently Asked Questions</h1>
            <p class="page-updated">Last updated: <?php echo date('F Y'); ?> &bull; <?php echo SITE_NAME; ?></p>

            <div class="page-body">
                <p>Quick answers about buying certified Sri Lankan sapphires, precious gemstones, and fine jewelry from <?php echo SITE_NAME; ?>, operated by <?php echo STORE_OWNER; ?>. For anything not covered here, <a href="<?php echo BASE_URL; ?>contact.php">contact our team</a> - we're happy to help.</p>

                <h2>About <?php echo SITE_NAME; ?></h2>
                <div class="faq-list">
                    <details class="faq-item" open>
                        <summary>What is <?php echo SITE_NAME; ?>?</summary>
                        <p><?php echo SITE_NAME; ?> is a premier direct source supplier of high-quality Sri Lankan sapphires and precious gemstones based at <?php echo STORE_LOCATION; ?>. We are registered with the State Gem Corporation of Sri Lanka and a proud member of the International Chamber of Commerce.</p>
                    </details>
                    <details class="faq-item">
                        <summary>What gemstones and jewelry do you offer?</summary>
                        <p>We specialize in natural Sri Lankan sapphires (Blue, Cornflower, Royal Blue, Padparadscha, Pink, Yellow), precious gemstones, fine handcrafted jewelry, custom cut stones, and bespoke rings. Browse our collection on the <a href="<?php echo BASE_URL; ?>shop.php">shop page</a>.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Are your gemstones 100% natural and certified?</summary>
                        <p>Yes. All our sapphires and gemstones are 100% natural, ethically sourced from Sri Lanka's renowned mines, and meticulously graded. Each fine gemstone comes with authentic gemological certification.</p>
                    </details>
                </div>

                <h2>Account &amp; Placing Orders</h2>
                <div class="faq-list">
                    <details class="faq-item" open>
                        <summary>Do I need an account to place an order?</summary>
                        <p><strong>Yes.</strong> You must have a registered account to add items to your cart and complete checkout at <?php echo SITE_NAME; ?>. <a href="<?php echo BASE_URL; ?>register.php">Create a free account</a> or <a href="<?php echo BASE_URL; ?>login.php">log in</a> to start shopping.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How do I place an order?</summary>
                        <ol class="page-ordered-list">
                            <li><a href="<?php echo BASE_URL; ?>register.php">Register</a> for an account or <a href="<?php echo BASE_URL; ?>login.php">log in</a> if you already have one.</li>
                            <li>Browse products and add items to your cart.</li>
                            <li>Go to checkout, enter your delivery details, and complete payment.</li>
                            <li>You will receive an order confirmation by email when your order is placed.</li>
                        </ol>
                    </details>
                    <details class="faq-item">
                        <summary>How do I create an account?</summary>
                        <p>Visit our <a href="<?php echo BASE_URL; ?>register.php">Register page</a>, enter your name, email, phone number, and password, then submit the form. Once registered, you can log in and place orders immediately.</p>
                    </details>
                    <details class="faq-item">
                        <summary>I forgot my password. What should I do?</summary>
                        <p>Use the <a href="<?php echo BASE_URL; ?>forgot-password.php">Forgot Password</a> page to receive a reset link by email. Follow the instructions in the email to set a new password.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Can I change or cancel my order?</summary>
                        <p>If your order has not yet been packed or shipped, contact us as soon as possible at <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a> or <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener">WhatsApp</a>. Once an order is dispatched, changes may not be possible.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How do I view my past orders?</summary>
                        <p>Log in to your account and go to <a href="<?php echo BASE_URL; ?>my-orders.php">My Orders</a> to see order history, status, and details.</p>
                    </details>
                </div>

                <h2>Shipping &amp; Delivery</h2>
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>Where do you deliver?</summary>
                        <p>We offer <strong>island wide delivery</strong> across Sri Lanka. We do not currently ship internationally. Our store is located at <?php echo STORE_LOCATION; ?>.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How long does delivery take?</summary>
                        <p>Orders within Sri Lanka typically arrive in <strong>2–5 business days</strong>, depending on your location and courier availability. Remote areas may take slightly longer. See our <a href="<?php echo BASE_URL; ?>shipping-policy.php">Shipping Policy</a> for full details.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How can I track my order?</summary>
                        <p>Once your order ships, you may receive tracking information by email when available. You can also use our <a href="<?php echo BASE_URL; ?>track-order.php">Track Order</a> page with your order number, or check status in <a href="<?php echo BASE_URL; ?>my-orders.php">My Orders</a> when logged in.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Do you offer free shipping?</summary>
                        <p>Free or discounted shipping may be available during promotions or on minimum order values. Any active offers are shown at checkout and on promotional banners on our homepage.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How are gemstones and jewelry packaged for delivery?</summary>
                        <p>All gemstones and jewelry items are housed in velvet-lined luxury presentation cases, sealed securely, and insured for transit to ensure they arrive in pristine condition.</p>
                    </details>
                </div>

                <h2>Payments</h2>
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>What payment methods do you accept?</summary>
                        <p>We accept major credit and debit cards (Visa, Mastercard, American Express), and other payment options shown at checkout. All payments are processed through secure payment gateways.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Is my payment information secure?</summary>
                        <p>Yes. We use encrypted connections (SSL) and do not store full card details on our servers. Payments are handled by trusted payment processors.</p>
                    </details>
                    <details class="faq-item">
                        <summary>What currency are prices shown in?</summary>
                        <p>Prices in our store are based on Sri Lankan Rupees (LKR). You may switch the display currency using the currency selector in the top bar; converted amounts are shown for your convenience.</p>
                    </details>
                </div>

                <h2>Returns &amp; Refunds</h2>
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>What is your return policy?</summary>
                        <p>We accept returns on eligible unused items in original packaging within the timeframe stated in our <a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a>. Items that are opened, used, physically damaged, or altered may not be returnable unless faulty or incorrectly supplied.</p>
                    </details>
                    <details class="faq-item">
                        <summary>How do I request a refund or return?</summary>
                        <p>Email us at <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a> with your order number, reason for return, and photos if the item arrived damaged. Our team will guide you through the next steps.</p>
                    </details>
                    <details class="faq-item">
                        <summary>What if my order arrives damaged?</summary>
                        <p>Please contact us within 48 hours of delivery with photos of the packaging and product. We will arrange a replacement or refund where applicable.</p>
                    </details>
                </div>

                <h2>Customer Support</h2>
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>How do I contact customer support?</summary>
                        <p>Reach us via the <a href="<?php echo BASE_URL; ?>contact.php">Contact page</a>, email at <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a>, phone at <?php echo STORE_PHONE_1; ?> / <?php echo STORE_PHONE_2; ?>, or <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener">WhatsApp</a>. You can also use the chat widget on our website.</p>
                    </details>
                    <details class="faq-item">
                        <summary>What are your support hours?</summary>
                        <p>Our team aims to respond as quickly as possible. For urgent order questions, WhatsApp is often the fastest way to reach us.</p>
                    </details>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
