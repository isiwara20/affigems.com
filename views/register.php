<?php
$pageTitle = "Register - ARIGATO.LK";
require_once __DIR__ . '/../config/app.php';
$pagePreloadImages = [asset_image_url('assets/images/login-couple2.png')];
require_once __DIR__ . '/partials/header.php';

// Get flash messages
$registerError = $_SESSION['register_error'] ?? '';
$registerSuccess = $_SESSION['register_success'] ?? '';
$registerName = $_SESSION['register_name'] ?? '';
$registerEmail = $_SESSION['register_email'] ?? '';
$registerPhone = $_SESSION['register_phone'] ?? '';
unset($_SESSION['register_error'], $_SESSION['register_success'], $_SESSION['register_name'], $_SESSION['register_email'], $_SESSION['register_phone']);
?>

<!-- Register Section -->
<section class="login-section">
    <div class="container">
        <div class="login-wrapper">
            <!-- Left: Register Form -->
            <div class="login-form-container">
                <h1>Create Your Account</h1>
                <p class="login-subtitle">Join ARIGATO.LK and start shopping for quality tech products</p>

                <?php if ($registerError): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($registerError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($registerSuccess): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($registerSuccess); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>controllers/AuthController.php" method="POST" class="login-form" data-turbo="false">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="name" name="name" 
                                   placeholder="Enter your full name" 
                                   value="<?php echo htmlspecialchars($registerName); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" 
                                   value="<?php echo htmlspecialchars($registerEmail); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone-alt input-icon"></i>
                            <input type="tel" id="phone" name="phone" 
                                   placeholder="Enter your phone number" 
                                   value="<?php echo htmlspecialchars($registerPhone); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" 
                                   placeholder="Create a password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password', 'eyeIcon1')">
                                <i class="fas fa-eye" id="eyeIcon1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                                <i class="fas fa-eye" id="eyeIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="<?php echo BASE_URL; ?>terms.php" class="forgot-password">Terms & Conditions</a> and <a href="<?php echo BASE_URL; ?>privacy.php" class="forgot-password">Privacy Policy</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Create Account</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <p class="register-link">
                    Already have an account? <a href="<?php echo BASE_URL; ?>login.php">Login Now</a>
                </p>
            </div>

            <!-- Right: Branding Panel -->
            <div class="login-brand-panel">
                <?php echo render_asset_image('assets/images/login-couple2.png', [
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
