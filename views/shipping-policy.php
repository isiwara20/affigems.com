<?php
$pageTitle = "Shipping & Delivery Policy - ARIGATO.LK";
require_once __DIR__ . '/partials/header.php';
?>

<section class="page-section">
    <div class="container">
        <div class="page-content">
            <h1 class="page-title">Shipping &amp; Delivery Policy</h1>
            <p class="page-updated">Effective Date: 01 January 2026 &bull; Last updated: July 2026</p>

            <div class="page-body">
                <p>This Shipping &amp; Delivery Policy applies to all orders placed on <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a>, operated by <?php echo STORE_OWNER; ?> (Business Registration: <?php echo STORE_BUSINESS_REG; ?>).</p>
                <p>We proudly deliver <strong>island wide across Sri Lanka</strong> using <strong>Sri Lanka Post (SL Post COD Service)</strong> and other official postal logistics systems where applicable.</p>
                <p>By placing an order on <?php echo SITE_NAME; ?>, you agree to the terms outlined below. Please also review our <a href="<?php echo BASE_URL; ?>terms.php">Terms &amp; Conditions</a> and <a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a>.</p>

                <h2>1. Company Information</h2>
                <ul>
                    <li><strong>Legal Name:</strong> <?php echo STORE_OWNER; ?></li>
                    <li><strong>Business Registration:</strong> <?php echo STORE_BUSINESS_REG; ?></li>
                    <li><strong>Trading Name:</strong> <?php echo SITE_NAME; ?></li>
                    <li><strong>Address:</strong> <?php echo STORE_LOCATION; ?></li>
                    <li><strong>Phone:</strong> <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></li>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></li>
                    <li><strong>Website:</strong> <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a></li>
                </ul>

                <h2>2. Delivery Partner (Primary Service)</h2>
                <p>We primarily use:</p>
                <h3>Sri Lanka Post (SL Post COD Service)</h3>
                <p>All Cash on Delivery (COD) shipments are handled through the official <strong>Sri Lanka Post Cash on Delivery system</strong>.</p>
                <p>Features include:</p>
                <ul>
                    <li>Island wide delivery coverage across Sri Lanka</li>
                    <li>Government postal security and handling standards</li>
                    <li>Reliable tracking through the SL Post network</li>
                    <li>Cash collection at the delivery point for COD orders</li>
                    <li>Nationwide postal network including rural and remote areas</li>
                </ul>
                <p><strong>Tracking:</strong> Each parcel is assigned an <strong>SL Post tracking number (barcode / reference ID)</strong> for status updates once dispatched.</p>

                <h2>3. Delivery Coverage</h2>
                <p>We deliver to:</p>
                <ul>
                    <li>Colombo and suburbs</li>
                    <li>All districts across Sri Lanka</li>
                    <li>Rural and remote locations served by the island wide postal network</li>
                </ul>
                <p>No location within Sri Lanka is excluded unless restricted by SL Post regulations or circumstances beyond our control. We do not currently offer international shipping through <?php echo SITE_NAME; ?>.</p>

                <h2>4. Order Processing Time</h2>
                <p>All orders are typically processed within <strong>24–48 hours</strong> after order confirmation and payment verification (where applicable).</p>
                <ul>
                    <li>Orders placed on weekends or public holidays are processed on the next working day.</li>
                    <li>During peak seasons or high-volume periods, processing may take slightly longer — we will notify you if there is a significant delay.</li>
                </ul>
                <p>Processing includes:</p>
                <ul>
                    <li>Order verification and account confirmation</li>
                    <li>Stock confirmation</li>
                    <li>Secure packing and labelling of mobiles, accessories, and tech products</li>
                    <li>Handover to Sri Lanka Post or applicable courier partner</li>
                </ul>

                <h2>5. Delivery Time Frame</h2>
                <p>Delivery time depends on SL Post service standards, your location, and operational conditions.</p>
                <p><strong>Estimated delivery times after dispatch:</strong></p>
                <ul>
                    <li><strong>Colombo &amp; Western Province:</strong> 1–3 business days</li>
                    <li><strong>Other main cities:</strong> 2–5 business days</li>
                    <li><strong>Remote / rural areas:</strong> 3–7 business days</li>
                </ul>
                <p><strong>Please note:</strong> Delivery times are estimates only and may vary due to weather conditions, postal delays, public holidays, strikes, high seasonal order volume, or other events outside our control. Estimated times are not guaranteed.</p>

                <h2>6. Cash on Delivery (SL Post COD)</h2>
                <p>We offer <strong>Cash on Delivery (COD)</strong> through Sri Lanka Post where available.</p>
                <p><strong>COD rules:</strong></p>
                <ul>
                    <li>Customer pays the order amount when receiving the parcel</li>
                    <li>Maximum COD value may depend on SL Post policy and order value limits displayed at checkout</li>
                    <li>A valid phone number and complete delivery address are required</li>
                    <li>Customer or an authorised recipient must be available at the delivery location</li>
                </ul>
                <p><strong>If the customer is unavailable,</strong> SL Post may:</p>
                <ul>
                    <li>Retry delivery on a subsequent day</li>
                    <li>Hold the parcel at the nearest post office for collection</li>
                    <li>Return the parcel to sender after failed delivery attempts</li>
                </ul>

                <h2>7. Other Payment Methods and Dispatch</h2>
                <p>Where online payment (card, bank transfer, QR payment, or other methods shown at checkout) is used, orders are dispatched after successful payment verification. The same delivery time frames and SL Post handling procedures apply once the order is handed over to the postal network.</p>

                <h2>8. Delivery Charges</h2>
                <p>Delivery charges are calculated based on:</p>
                <ul>
                    <li>Product weight and package size</li>
                    <li>Product category</li>
                    <li>Delivery location within Sri Lanka</li>
                    <li>SL Post tariff system and applicable courier rates</li>
                </ul>
                <p>Charges are:</p>
                <ul>
                    <li>Displayed at checkout where applicable</li>
                    <li>May vary for COD orders and remote locations</li>
                    <li>Subject to SL Post official rates and promotional terms</li>
                </ul>
                <p>Free or discounted shipping may be offered during promotions or on qualifying minimum order values, as shown on our website or at checkout.</p>

                <h2>9. Order Tracking</h2>
                <p>Once your order is dispatched, you will receive:</p>
                <ul>
                    <li>SL Post tracking number (barcode / reference ID), where applicable</li>
                    <li>Order status update by email</li>
                    <li>SMS or WhatsApp notification (if enabled and contact details are provided)</li>
                </ul>
                <p>You can also track your order on our <a href="<?php echo BASE_URL; ?>track-order.php">Track Order</a> page using your order number, or via the Sri Lanka Post tracking system using the reference number provided.</p>

                <h2>10. Delivery Attempts</h2>
                <p>Sri Lanka Post may attempt delivery:</p>
                <ul>
                    <li>One to two times depending on location and service conditions</li>
                    <li>If unsuccessful, the parcel may be held at the nearest post office for collection</li>
                </ul>
                <p>Customers must:</p>
                <ul>
                    <li>Collect parcels from the post office promptly if held for pickup</li>
                    <li>Provide valid identification when collecting COD parcels</li>
                    <li>Ensure contact details are correct so delivery agents can reach you</li>
                </ul>

                <h2>11. Address Responsibility</h2>
                <p>Customers are responsible for:</p>
                <ul>
                    <li>Providing an accurate and complete delivery address at checkout</li>
                    <li>Providing a correct and active phone number</li>
                    <li>Ensuring someone is available to receive the parcel, or arranging collection from the post office</li>
                </ul>
                <p><?php echo SITE_NAME; ?> is not responsible for:</p>
                <ul>
                    <li>Delays or failed delivery due to incorrect or incomplete addresses</li>
                    <li>Delays due to wrong or unreachable contact details</li>
                    <li>Failed delivery due to customer unavailability</li>
                </ul>
                <p>Please double-check your delivery details before confirming your order. If a package is returned due to an address error provided by the customer, additional shipping fees may apply for re-dispatch.</p>

                <h2>12. Failed Delivery</h2>
                <p>If delivery fails after postal attempts:</p>
                <ul>
                    <li>The parcel may be returned to us as the sender</li>
                    <li>Re-delivery may incur additional charges</li>
                    <li>Customers should contact our support team immediately to arrange resolution</li>
                </ul>
                <p>We will work with you and SL Post to resolve failed deliveries where possible.</p>

                <h2>13. Damaged or Lost Parcels</h2>
                <p>Mobiles, accessories, and tech products require careful handling. If your parcel is:</p>
                <ul>
                    <li>Damaged during transit</li>
                    <li>Lost in the postal system</li>
                    <li>Incorrectly delivered</li>
                    <li>Missing items compared to your order confirmation</li>
                </ul>
                <p>You must report the issue within <strong>48 hours</strong> of delivery, delivery attempt, or expected delivery notification.</p>
                <p>We will:</p>
                <ul>
                    <li>Investigate with Sri Lanka Post and our internal records</li>
                    <li>Request photos of damaged packaging and products where applicable</li>
                    <li>Offer replacement or refund where applicable under our <a href="<?php echo BASE_URL; ?>refund-policy.php">Refund Policy</a></li>
                    <li>Follow SL Post claim procedures where required</li>
                </ul>

                <h2>14. Refused Delivery</h2>
                <p>If a customer refuses to accept a COD parcel without a valid reason:</p>
                <ul>
                    <li>Return shipping costs may be charged to the customer</li>
                    <li>Future COD orders may be restricted at our discretion</li>
                    <li>Refunds for refused orders may be subject to deduction of return and handling costs</li>
                </ul>

                <h2>15. Packaging of Mobiles &amp; Tech Products</h2>
                <p>All orders are packed with care to protect devices, accessories, cables, and fragile electronic items during transit. We use appropriate padding and secure packaging to help your products arrive safely.</p>
                <ul>
                    <li>Items are checked before packing</li>
                    <li>Fragile products receive additional protective wrapping where needed</li>
                    <li>Outer packaging is sealed and labelled for postal handling</li>
                </ul>
                <p>Despite careful packing, transit damage can occasionally occur — please report any issues within 48 hours as stated in Section 13.</p>

                <h2>16. Special Delivery Conditions</h2>
                <p>Delivery may be delayed due to:</p>
                <ul>
                    <li>Adverse weather conditions</li>
                    <li>Public holidays and festive seasons</li>
                    <li>Postal service delays or operational disruptions</li>
                    <li>National strikes or transport disruptions</li>
                    <li>High seasonal order volume</li>
                    <li>Events outside our reasonable control (force majeure)</li>
                </ul>
                <p>We always work to ensure the fastest possible delivery through the SL Post network and our operational partners.</p>

                <h2>17. International Shipping</h2>
                <p>Currently, <?php echo SITE_NAME; ?> operates and delivers <strong>within Sri Lanka only</strong>. International shipping is not available at this time. If international delivery options are introduced in the future, this policy will be updated accordingly.</p>

                <h2>18. Customer Support</h2>
                <p>For delivery-related inquiries, tracking assistance, or delivery issues, please contact:</p>
                <ul>
                    <li><strong>Company:</strong> <?php echo STORE_OWNER; ?></li>
                    <li><strong>Business Registration:</strong> <?php echo STORE_BUSINESS_REG; ?></li>
                    <li><strong>Address:</strong> <?php echo STORE_LOCATION; ?></li>
                    <li><strong>Phone:</strong> <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a> / <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_2); ?>"><?php echo STORE_PHONE_2; ?></a></li>
                    <li><strong>Email:</strong> <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a></li>
                    <li><strong>Website:</strong> <a href="https://<?php echo STORE_DOMAIN; ?>"><?php echo STORE_DOMAIN; ?></a></li>
                    <li><strong>WhatsApp:</strong> <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener"><?php echo STORE_PHONE_1; ?></a></li>
                    <li><strong>Contact form:</strong> <a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                    <li><strong>Track order:</strong> <a href="<?php echo BASE_URL; ?>track-order.php">Track Order</a></li>
                </ul>

                <h2>19. Policy Updates</h2>
                <p>We reserve the right to update or modify this Shipping &amp; Delivery Policy at any time. Changes take effect immediately upon posting on this page unless otherwise stated.</p>
                <p>We encourage customers to review this policy periodically. Continued placement of orders after updates constitutes acceptance of the revised policy.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
