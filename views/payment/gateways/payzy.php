<?php
/** @var array<string,mixed> $checkoutContext */
?>
<h3>Pay with Payzy</h3>
<?php if (($checkoutContext['mode'] ?? '') === 'error'): ?>
<div class="payment-error">
    <strong>Unable to start Payzy checkout</strong><br>
    <?php echo htmlspecialchars($checkoutContext['error'] ?? 'Please verify Payzy API settings in Admin.'); ?>
</div>
<?php else: ?>
<p style="font-size:13px; color:#666;">Redirecting to Payzy...</p>
<div style="text-align:center; padding:40px 0;"><i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#E5A91A;"></i></div>
<?php endif; ?>
