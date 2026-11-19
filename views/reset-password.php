<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "Reset Password - " . SITE_NAME;
$pagePreloadImages = [asset_image_url('assets/images/login-couple3.png')];
require_once __DIR__ . '/partials/header.php';

$token = $_GET['token'] ?? '';
$resetError = $_SESSION['reset_error'] ?? '';
$resetSuccess = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// Validate token
if (empty($token)) {
    $resetError = 'Invalid or missing reset token.';
}
?>

<!-- Reset Password Section -->
<section class="login-section">
    <div class="container">
        <div class="login-wrapper forgot-wrapper">
            <!-- Left: Reset Form -->
            <div class="login-form-container">
                <div class="forgot-icon-wrap">
                    <span class="forgot-lock-icon">
                        <i class="fas fa-key"></i>
                    </span>
                </div>
                <h1>Reset Password</h1>
                <p class="login-subtitle">Enter your new password below.</p>

                <?php if ($resetError): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($resetError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($resetSuccess): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($resetSuccess); ?>
                    </div>
                    <div class="back-to-login">
                        <a href="<?php echo BASE_URL; ?>login.php">
                            <i class="fas fa-arrow-left"></i> Go to Login
                        </a>
                    </div>
                <?php elseif (!empty($token)): ?>
                    <form action="<?php echo BASE_URL; ?>controllers/AuthController.php" method="POST" class="login-form">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" 
                                       placeholder="Enter new password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password', 'eyeIcon1')">
                                    <i class="fas fa-eye" id="eyeIcon1"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm new password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                                    <i class="fas fa-eye" id="eyeIcon2"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            Reset Password <i class="fas fa-check"></i>
                        </button>
                    </form>

                    <div class="back-to-login">
                        <a href="<?php echo BASE_URL; ?>login.php">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Branding Panel -->
            <div class="login-brand-panel">
                <?php echo render_asset_image('assets/images/login-couple3.png', [
                    'alt' => 'Style That Defines You',
                    'class' => 'brand-panel-img',
                    'fetchpriority' => 'high',
                    'loading' => 'eager',
                    'decoding' => 'async',
                ]); ?>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
