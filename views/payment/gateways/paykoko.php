<?php
/** @var array<string,mixed> $checkoutContext */
?>
<h3>Pay with PayKoko</h3>
<?php if (($checkoutContext['mode'] ?? '') === 'error'): ?>
<div class="payment-error">
    <strong>Unable to start PayKoko checkout</strong><br>
    <?php echo htmlspecialchars($checkoutContext['error'] ?? 'Please verify PayKoko API settings in Admin.'); ?>
</div>
<?php else: ?>
<p style="font-size:13px; color:#666;">Redirecting to PayKoko (Buy Now, Pay Later)...</p>
<div style="text-align:center; padding:40px 0;"><i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#E5A91A;"></i></div>
<?php endif; ?>
