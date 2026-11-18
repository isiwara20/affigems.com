<?php
$pageTitle = "Terms & Conditions - ARIGATO.LK";
require_once __DIR__ . '/partials/header.php';
?>

<section class="page-section">
    <div class="container">
        <div class="page-content">
            <h1 class="page-title">Terms &amp; Conditions</h1>
            <p class="page-updated">Effective Date: 01 January 2026 &bull; Last updated: July 2026</p>

            <div class="page-body">
                <p>Welcome to <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a>, operated by <?php echo STORE_OWNER; ?> (Business Registration: <?php echo STORE_BUSINESS_REG; ?>).</p>
                <p>By accessing or using this website, you agree to comply with and be bound by these Terms &amp; Conditions. If you do not agree with any part of these terms, please do not use our website or services.</p>

                <h2>1. Company Information</h2>
                <ul>
                    <li><strong>Legal Name:</strong> <?php echo STORE_OWNER; ?></li>
                    <li><strong>Business Registration Number:</strong> <?php echo STORE_BUSINESS_REG; ?></li>
                    <li><strong>Trading Name:</strong> <?php echo SITE_NAME; ?></li>
                    <li><strong>Address:</strong> <?php echo STORE_LOCATION; ?></li>
                    <li><strong>Phone:</strong> <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></li>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></li>
                    <li><strong>Website:</strong> <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a></li>
                </ul>

                <h2>2. Acceptance of Terms</h2>
                <p>By using <?php echo SITE_NAME; ?> and its related services, you acknowledge that you have read, understood, and agreed to these Terms &amp; Conditions, our <a href="<?php echo BASE_URL; ?>privacy.php">Privacy Policy</a>, <a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a>, <a href="<?php echo BASE_URL; ?>shipping-policy.php">Shipping Policy</a>, and any additional policies published on the website.</p>
                <p>Continued use of the website after updates to these terms constitutes acceptance of the revised terms.</p>

                <h2>3. Eligibility</h2>
                <ul>
                    <li>You must be at least <strong>18 years old</strong>, or have parental or legal guardian consent, to use this website and place orders.</li>
                    <li>By placing an order, you confirm that you are legally capable of entering into a binding contract under the laws of Sri Lanka.</li>
                    <li>We reserve the right to refuse service to anyone who does not meet eligibility requirements.</li>
                </ul>

                <h2>4. Customer Accounts</h2>
                <p>To add items to your cart and complete checkout at <?php echo SITE_NAME; ?>, you <strong>must register and maintain an active customer account</strong>. Guest checkout is not available.</p>
                <p>Users may create accounts using:</p>
                <ul>
                    <li>Email registration</li>
                    <li>Google login (where available)</li>
                    <li>Facebook login (where available)</li>
                </ul>
                <p>You are responsible for:</p>
                <ul>
                    <li>Maintaining the confidentiality and security of your account credentials</li>
                    <li>Protecting your password</li>
                    <li>All activities conducted under your account</li>
                    <li>Providing accurate, current, and complete registration information</li>
                </ul>
                <p>We reserve the right to suspend or terminate accounts involved in fraudulent, abusive, or suspicious activity, or that violate these Terms &amp; Conditions.</p>

                <h2>5. Products and Services</h2>
                <p><?php echo SITE_NAME; ?> is an online store specialising in <strong>smartphones, accessories, and gadgets, peripherals, and related technology products</strong>. Our catalogue may include, without limitation:</p>
                <ul>
                    <li>Laptops, desktops, monitors, and related hardware</li>
                    <li>Mobile accessories and gadgets</li>
                    <li>Networking devices, storage, and office tech</li>
                    <li>Other digital and technology products where listed</li>
                </ul>
                <p>We are committed to offering authentic products sourced and selected with care. Product availability, brands, and catalogue items may change without prior notice. Product images and descriptions are for illustration; slight variations in packaging, colour, or model revision may occur depending on manufacturer updates.</p>

                <h2>6. Product Information and Authenticity</h2>
                <ul>
                    <li>We strive to display accurate product images, ingredients, descriptions, and prices. Colours and appearance may vary slightly due to screen settings or lighting.</li>
                    <li>Unless stated otherwise on a product page, items sold are genuine branded or quality-assured products as described.</li>
                    <li>Product availability is subject to stock. We reserve the right to limit quantities per customer or per order.</li>
                    <li>If a product becomes unavailable after you place an order, we may cancel that item or the full order and issue a refund for any amount paid.</li>
                </ul>

                <h2>7. Pricing</h2>
                <ul>
                    <li>All prices are displayed in <strong>Sri Lankan Rupees (LKR)</strong> unless otherwise stated.</li>
                    <li>Display prices in other currencies (where offered) are converted for convenience and may vary slightly at checkout.</li>
                    <li>We reserve the right to modify prices, correct pricing errors, and cancel orders resulting from incorrect pricing or system errors.</li>
                    <li>Promotional prices apply only for the stated promotional period and subject to stated terms.</li>
                </ul>

                <h2>8. Orders</h2>
                <p>By placing an order through <?php echo SITE_NAME; ?>, you agree that:</p>
                <ul>
                    <li>All information you provide (name, address, phone, email, payment details) is accurate and complete.</li>
                    <li>You are authorised to use the payment method submitted.</li>
                    <li>We may verify customer details before processing or dispatching an order.</li>
                    <li>Orders may be cancelled or refused due to stock limitations, verification failures, suspected fraud, or violation of these terms.</li>
                </ul>
                <p>Order statuses may include, without limitation:</p>
                <ul>
                    <li>Pending</li>
                    <li>Processing</li>
                    <li>Packed</li>
                    <li>Shipped</li>
                    <li>Delivered</li>
                    <li>Cancelled</li>
                </ul>
                <p>An order is confirmed only after you receive an order confirmation from us (by email or through your account). We reserve the right to reject or cancel any order at our discretion.</p>

                <h2>9. Payments</h2>
                <p>Accepted payment methods may include, as shown at checkout:</p>
                <ul>
                    <li>Cash on Delivery (COD), where available</li>
                    <li>Bank transfer</li>
                    <li>QR payments</li>
                    <li>Online payment gateway (credit/debit cards including Visa, Mastercard, American Express, and other methods displayed at checkout)</li>
                </ul>
                <p>Orders may be processed only after payment verification where applicable. All online payments are processed through secure, encrypted connections. We do not store full card details on our servers.</p>

                <h2>10. Shipping and Delivery</h2>
                <p><?php echo SITE_NAME; ?> provides <strong>island wide delivery within Sri Lanka</strong>. We do not currently offer international shipping through this website.</p>
                <p>Delivery times may vary depending on:</p>
                <ul>
                    <li>Customer location within Sri Lanka</li>
                    <li>Product availability and order processing time</li>
                    <li>Courier partner operations</li>
                    <li>Weather conditions, public holidays, and events outside our control</li>
                </ul>
                <p>Estimated delivery times are guides only and are not guaranteed. Full details are set out in our <a href="<?php echo BASE_URL; ?>shipping-policy.php">Shipping Policy</a>. Risk of loss for products passes to you upon delivery to the address you provide.</p>

                <h2>11. Returns and Refunds</h2>
                <p>Returns and refunds are handled in accordance with our <a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a> and applicable consumer protection laws in Sri Lanka.</p>
                <p>In summary:</p>
                <ul>
                    <li>Returns may be accepted subject to product condition, hygiene requirements, and policy terms.</li>
                    <li>Opened, used, tampered, or physically damaged products may not be returnable except where faulty, damaged in transit, or incorrectly supplied.</li>
                    <li>Products damaged by misuse, improper storage, or unauthorised modification may not qualify for refunds.</li>
                    <li>Approved refunds are processed within the timeframe stated in our Refund Policy.</li>
                </ul>

                <h2>12. Warranties and Product Use</h2>
                <p>Manufacturer warranties apply where available and as stated on the product or packaging. <?php echo STORE_OWNER; ?> does not provide warranties beyond those offered by the manufacturer unless expressly stated in writing.</p>
                <p><strong>Technology product disclaimer:</strong> Product compatibility, performance, and suitability may vary depending on your devices, software, usage environment, and configuration. You are responsible for reviewing specifications and compatibility details before purchase. We are not liable for issues arising from incompatible hardware, unsupported software, improper installation, or misuse.</p>
                <p>Warranty and quality claims do not cover:</p>
                <ul>
                    <li>Normal wear or consumption of product</li>
                    <li>Improper storage (e.g. excessive heat or sunlight)</li>
                    <li>Use contrary to product labelling or instructions</li>
                    <li>Physical damage after delivery</li>
                </ul>

                <h2>13. Promotions, Coupons and Discounts</h2>
                <ul>
                    <li>Promotional codes, coupons, mega deals, and special offers are subject to their stated terms, expiry dates, and eligibility criteria.</li>
                    <li>Unless otherwise stated, only one promotion may apply per order.</li>
                    <li>We reserve the right to modify, suspend, or withdraw promotions at any time without prior notice.</li>
                    <li>Promotional benefits have no cash value and cannot be exchanged for cash unless expressly stated.</li>
                </ul>

                <h2>14. Intellectual Property</h2>
                <p>All content on <?php echo SITE_NAME; ?> including, without limitation:</p>
                <ul>
                    <li>Logos, trade names, and branding</li>
                    <li>Graphics, images, and photographs</li>
                    <li>Website design, layout, and software</li>
                    <li>Text, descriptions, and marketing materials</li>
                </ul>
                <p>are owned by or licensed to <?php echo STORE_OWNER; ?> and are protected by intellectual property laws. You may not copy, reproduce, distribute, modify, or create derivative works from our content without prior written permission.</p>
                <p>Third-party brand names, logos, and product trademarks belong to their respective owners and are used for identification purposes only.</p>

                <h2>15. Prohibited Activities</h2>
                <p>Users shall not:</p>
                <ul>
                    <li>Use the website for any unlawful, fraudulent, or misleading purpose</li>
                    <li>Attempt unauthorised access to our systems, accounts, or data</li>
                    <li>Upload malicious software, viruses, or harmful code</li>
                    <li>Submit false orders, reviews, or customer information</li>
                    <li>Resell products in a manner that misrepresents <?php echo SITE_NAME; ?> or infringes third-party rights</li>
                    <li>Abuse promotions, payment systems, or customer support channels</li>
                    <li>Violate any applicable local, national, or international law</li>
                </ul>
                <p>Violations may result in account termination, order cancellation, and legal action where appropriate.</p>

                <h2>16. Limitation of Liability</h2>
                <p>To the maximum extent permitted by law, <?php echo STORE_OWNER; ?> and <?php echo SITE_NAME; ?> shall not be liable for:</p>
                <ul>
                    <li>Indirect, incidental, special, or consequential damages</li>
                    <li>Loss of profits, revenue, or business opportunities</li>
                    <li>Data loss or service interruptions</li>
                    <li>Delays or failures caused by third-party couriers, payment processors, or service providers</li>
                    <li>Events beyond our reasonable control (force majeure)</li>
                </ul>
                <p>Our total liability for any claim relating to a specific order shall not exceed the amount you paid for the products giving rise to that claim, except where liability cannot be limited under applicable law.</p>

                <h2>17. Third-Party Services</h2>
                <p>The website may integrate with or link to third-party services including, without limitation:</p>
                <ul>
                    <li>Payment gateways and financial processors</li>
                    <li>Social login providers (Google, Facebook)</li>
                    <li>Courier and logistics partners</li>
                    <li>Analytics and communication tools</li>
                </ul>
                <p>We are not responsible for the availability, policies, security practices, or actions of third-party services. Your use of third-party services may be subject to their own terms and privacy policies.</p>

                <h2>18. Privacy</h2>
                <p>Your use of <?php echo SITE_NAME; ?> is also governed by our <a href="<?php echo BASE_URL; ?>privacy.php">Privacy Policy</a>, which explains how we collect, use, store, and protect your personal information.</p>
                <p>By using the website and creating an account, you consent to the collection and processing of information as described in the Privacy Policy.</p>

                <h2>19. Reviews and User Content</h2>
                <p>Customers who are logged in may submit product reviews. By submitting a review, you grant us a non-exclusive licence to display, reproduce, and use your review content on the website and in marketing materials. You confirm that your review is honest, based on your experience, and does not violate any third-party rights or applicable law.</p>
                <p>We reserve the right to remove reviews that are abusive, misleading, spam, or otherwise inappropriate.</p>

                <h2>20. Modifications to These Terms</h2>
                <p>We reserve the right to update, amend, or replace these Terms &amp; Conditions at any time. Changes become effective immediately upon publication on this page unless otherwise stated.</p>
                <p>It is your responsibility to review these terms periodically. Material changes may also be communicated via email or a notice on the website where appropriate.</p>

                <h2>21. Governing Law</h2>
                <p>These Terms &amp; Conditions shall be governed by and interpreted in accordance with the laws of the <strong>Democratic Socialist Republic of Sri Lanka</strong>.</p>
                <p>Any disputes arising from or relating to these terms or your use of <?php echo SITE_NAME; ?> shall be subject to the exclusive jurisdiction of the courts of Sri Lanka, except where mandatory consumer protection law provides otherwise.</p>

                <h2>22. Severability</h2>
                <p>If any provision of these Terms &amp; Conditions is found to be invalid or unenforceable by a court of competent jurisdiction, the remaining provisions shall continue in full force and effect.</p>

                <h2>23. Contact Information</h2>
                <p>For questions about these Terms &amp; Conditions, orders, or our products, please contact us:</p>
                <ul>
                    <li><strong>Company:</strong> <?php echo STORE_OWNER; ?></li>
                    <li><strong>Business Registration:</strong> <?php echo STORE_BUSINESS_REG; ?></li>
                    <li><strong>Address:</strong> <?php echo STORE_LOCATION; ?></li>
                    <li><strong>Phone:</strong> <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></li>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></li>
                    <li><strong>Website:</strong> <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a></li>
                    <li><strong>WhatsApp:</strong> <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener"><?php echo STORE_PHONE_1; ?></a></li>
                    <li><strong>Contact form:</strong> <a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
