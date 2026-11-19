<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "Refund Policy - " . SITE_NAME;
require_once __DIR__ . '/partials/header.php';
?>

<section class="page-section">
    <div class="container">
        <div class="page-content">
            <h1 class="page-title">Refund Policy</h1>
            <p class="page-updated">Last updated: <?php echo date('F Y'); ?></p>

            <div class="page-body">
                <p>We want you to be completely satisfied with every <?php echo SITE_NAME; ?> purchase. If something isn't right, this policy explains how returns, exchanges, and refunds work.</p>

                <h2>1. Return Eligibility</h2>
                <p>You may request a return if all of the following apply:</p>
                <ul>
                    <li>You contact us within <strong>7 days</strong> of receiving your order.</li>
                    <li>The item is <strong>unused</strong>, in its <strong>original condition</strong>, and in <strong>original packaging</strong> with tags attached (where applicable).</li>
                    <li>You provide your order number and a brief reason for the return.</li>
                </ul>

                <h2>2. Non-Returnable Items</h2>
                <p>The following items cannot be returned or refunded unless they arrive damaged or defective:</p>
                <ul>
                    <li>Personalized or custom-made products.</li>
                    <li>Opened sealed collectibles, photocards, or hygiene-sensitive items.</li>
                    <li>Items marked as final sale or clearance.</li>
                    <li>Gift cards or digital products.</li>
                    <li>Items damaged due to misuse after delivery.</li>
                </ul>

                <h2>3. Damaged or Wrong Items</h2>
                <p>If you receive a damaged, defective, or incorrect item:</p>
                <ul>
                    <li>Contact us within <strong>48 hours</strong> of delivery.</li>
                    <li>Send photos of the product and packaging.</li>
                    <li>We will arrange a replacement, exchange, or full refund — including shipping costs where we are at fault.</li>
                </ul>

                <h2>4. How to Request a Return</h2>
                <ol class="page-ordered-list">
                    <li>Email <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a> with your order number, item(s) to return, and reason.</li>
                    <li>Wait for return approval and instructions from our team.</li>
                    <li>Ship the item back in secure packaging. Return shipping costs are the customer's responsibility unless the return is due to our error.</li>
                    <li>Once we receive and inspect the item, we will process your refund or exchange.</li>
                </ol>

                <h2>5. Refund Processing</h2>
                <ul>
                    <li>Approved refunds are processed within <strong>7–10 business days</strong> after we receive the returned item.</li>
                    <li>Refunds are issued to the <strong>original payment method</strong> used at checkout.</li>
                    <li>Depending on your bank or card provider, it may take an additional 3–7 business days for the refund to appear in your account.</li>
                    <li>Original shipping fees are non-refundable unless the return is due to an error on our part (wrong item, defective product, etc.).</li>
                </ul>

                <h2>6. Exchanges</h2>
                <p>If you would like a different item instead of a refund, let us know when submitting your return request. Exchanges are subject to stock availability. If the replacement item costs more, you will pay the difference; if it costs less, we will refund the difference.</p>

                <h2>7. Cancellations</h2>
                <ul>
                    <li>Orders may be cancelled before they are packed or shipped. Contact us immediately at <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a>.</li>
                    <li>If your order has already been dispatched, you may need to follow the standard return process once it arrives.</li>
                    <li>Cancelled orders are refunded within 5–7 business days.</li>
                </ul>

                <h2>8. International Returns</h2>
                <p>For international orders, return shipping costs and any customs fees are the responsibility of the customer unless the return is due to our error. We recommend using a tracked shipping service for returns.</p>

                <h2>9. Changes to This Policy</h2>
                <p>We may update this Refund Policy from time to time. Changes will be posted on this page with an updated revision date.</p>

                <h2>10. Contact Us</h2>
                <p>Need help with a return or refund?</p>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></li>
                    <li><strong>Phone:</strong> <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></li>
                    <li><strong>Contact form:</strong> <a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
