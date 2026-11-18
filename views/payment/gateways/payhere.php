<?php
/** @var array<string,mixed> $checkoutContext */
?>
<h3>Pay with PayHere</h3>
<p style="font-size:13px; color:#666; margin-bottom:20px;">You will be redirected to PayHere to complete your payment securely.</p>
<form method="POST" action="<?php echo htmlspecialchars($checkoutContext['action'] ?? ''); ?>" id="payhereForm">
    <?php foreach (($checkoutContext['fields'] ?? []) as $name => $value): ?>
    <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars((string)$value); ?>">
    <?php endforeach; ?>
    <button type="submit" style="width:100%; padding:14px 20px; background:#D49A0A; color:#1a1a1a; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer;">
        <i class="fas fa-lock"></i> Proceed to PayHere
    </button>
</form>
<script>document.getElementById('payhereForm').submit();</script>
