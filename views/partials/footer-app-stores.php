<?php
$footerAppStoresClass = trim('footer-app-stores ' . ($footerAppStoresModifier ?? ''));
?>
<div class="<?php echo htmlspecialchars($footerAppStoresClass); ?>">
    <p class="footer-app-stores-title">
        Mobile App
        <span class="footer-app-soon">Coming Soon</span>
    </p>
    <div class="footer-app-badges">
        <span class="footer-app-badge footer-app-badge--apple" aria-label="App Store — coming soon">
            <i class="fab fa-apple" aria-hidden="true"></i>
            <span class="footer-app-badge-text">
                <small>Download on the</small>
                <strong>App Store</strong>
            </span>
        </span>
        <span class="footer-app-badge footer-app-badge--google" aria-label="Google Play — coming soon">
            <i class="fab fa-google-play" aria-hidden="true"></i>
            <span class="footer-app-badge-text">
                <small>GET IT ON</small>
                <strong>Google Play</strong>
            </span>
        </span>
    </div>
</div>
