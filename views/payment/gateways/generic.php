<?php
/** @var PaymentGatewayInterface $gateway */
/** @var array<string,mixed> $checkoutContext */
?>
<h3><?php echo htmlspecialchars($gateway->getLabel()); ?></h3>
<div class="payment-error">
    This payment gateway is not fully configured for checkout display.
</div>
