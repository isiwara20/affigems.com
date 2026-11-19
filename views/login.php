<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "Login - " . SITE_NAME;
require_once __DIR__ . '/../helpers/sms.php';

$pagePreloadImages = [asset_image_url('assets/images/login-couple.png')];
require_once __DIR__ . '/partials/header.php';

$otpLoginEnabled = false; // Temporarily disabled in view. Uncomment line below to re-enable.
// $otpLoginEnabled = ezymart_sms_is_configured();
$altLoginEnabled = $otpLoginEnabled;

// Get flash messages
$loginError = $_SESSION['login_error'] ?? '';
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_email']);
?>

<!-- Login Section -->
<section class="login-section">
    <div class="container">
        <div class="login-wrapper">
            <!-- Left: Login Form -->
            <div class="login-form-container">
                <h1>Welcome Back!</h1>
                <p class="login-subtitle">Login to your <?php echo SITE_NAME; ?> account</p>

                <?php if ($loginError): ?>
                    <div class="alert alert-error login-inline-alert login-flash-alert" id="loginFlashAlert" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($loginError); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($altLoginEnabled): ?>
                <div class="login-method-tabs" role="tablist" aria-label="Login method">
                    <button type="button" class="login-method-tab is-active" data-login-tab="email" role="tab" aria-selected="true">Email</button>
                    <?php if ($otpLoginEnabled): ?>
                    <button type="button" class="login-method-tab" data-login-tab="phone" role="tab" aria-selected="false">Phone OTP</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="login-panels-stage">
                <div class="login-panel is-active" id="loginPanelEmail" data-login-panel="email" role="tabpanel">
                    <form action="<?php echo BASE_URL; ?>controllers/AuthController.php" method="POST" class="login-form" data-turbo="false">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="email" id="email" name="email"
                                       placeholder="Enter your email"
                                       value="<?php echo htmlspecialchars($loginEmail); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password"
                                       placeholder="Enter your password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-options">
                            <label class="remember-me">
                                <input type="checkbox" name="remember">
                                <span class="checkmark"></span>
                                Remember Me
                            </label>
                            <a href="<?php echo BASE_URL; ?>forgot-password.php" class="forgot-password">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn-login">
                            <span>Login</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <?php if ($otpLoginEnabled): ?>
                <div class="login-panel" id="loginPanelPhone" data-login-panel="phone" role="tabpanel" aria-hidden="true">
                    <form id="otpLoginForm" class="login-form" novalidate>
                        <div class="login-otp-field-stack">
                            <div class="form-group" id="otpPhoneGroup">
                                <label for="otpPhone">Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone-alt input-icon"></i>
                                    <input type="tel" id="otpPhone" name="phone" placeholder="07X XXX XXXX" autocomplete="tel" required>
                                </div>
                                <p class="login-field-hint">We will send a 6-digit code to verify your number.</p>
                            </div>

                            <div class="form-group login-otp-code-slot" id="otpCodeGroup" aria-hidden="true">
                                <label class="login-otp-code-label" id="otpCodeLabel">Verification Code</label>
                                <div class="login-otp-boxes" id="otpCodeBoxes" role="group" aria-labelledby="otpCodeLabel">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" autocomplete="one-time-code" data-otp-index="0" disabled aria-label="Digit 1 of 6">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" data-otp-index="1" disabled aria-label="Digit 2 of 6">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" data-otp-index="2" disabled aria-label="Digit 3 of 6">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" data-otp-index="3" disabled aria-label="Digit 4 of 6">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" data-otp-index="4" disabled aria-label="Digit 5 of 6">
                                    <input type="text" class="login-otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" data-otp-index="5" disabled aria-label="Digit 6 of 6">
                                </div>
                            </div>

                            <div class="login-otp-profile-slot" id="otpProfileGroup" hidden>
                                <p class="login-otp-profile-intro">New here? Enter your details to create your account.</p>
                                <div class="form-group login-otp-profile-field">
                                    <label for="otpName">Full Name</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" id="otpName" name="name" placeholder="Enter your full name" autocomplete="name">
                                    </div>
                                </div>
                                <div class="form-group login-otp-profile-field">
                                    <label for="otpEmail">Email Address</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" id="otpEmail" name="email" placeholder="Enter your email address" autocomplete="email">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="login-panel-spacer" aria-hidden="true"></div>

                        <div class="login-otp-message-slot">
                            <div class="login-otp-message is-collapsed" id="otpLoginMessage"></div>
                        </div>

                        <div class="login-otp-actions">
                            <button type="button" class="btn-login" id="otpSendBtn">
                                <span>Send OTP</span>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" class="btn-login btn-login-secondary" id="otpVerifyBtn" hidden>
                                <span>Verify &amp; Login</span>
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="login-otp-back" id="otpChangePhoneBtn" hidden>Change phone number</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                </div>

                <p class="register-link">
                    Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Register Now</a>
                </p>
            </div>

            <!-- Right: Branding Panel -->
            <div class="login-brand-panel">
                <?php echo render_asset_image('assets/images/login-couple.png', [
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
var LOGIN_FLASH_HIDE_MS = 4500;

function dismissLoginFlashAlert(el, delay) {
    if (!el || el.dataset.dismissBound) return;
    el.dataset.dismissBound = '1';
    setTimeout(function() {
        el.classList.add('is-fading');
        setTimeout(function() {
            el.remove();
        }, 300);
    }, delay || LOGIN_FLASH_HIDE_MS);
}

(function initLoginFlashAlert() {
    var alertEl = document.getElementById('loginFlashAlert');
    if (alertEl) dismissLoginFlashAlert(alertEl);
})();

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

(function initLoginMethods() {
    var tabs = document.querySelectorAll('[data-login-tab]');
    var panels = document.querySelectorAll('[data-login-panel]');
    var panelsStage = document.querySelector('.login-panels-stage');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.getAttribute('data-login-tab');
            tabs.forEach(function(t) {
                var active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(function(panel) {
                var show = panel.getAttribute('data-login-panel') === target;
                panel.classList.toggle('is-active', show);
                panel.setAttribute('aria-hidden', show ? 'false' : 'true');
            });
            if (panelsStage) {
                panelsStage.classList.toggle('is-phone-active', target === 'phone');
            }
        });
    });
})();

/* Phone OTP login temporarily disabled in view.
(function initOtpLogin() {
    var sendBtn = document.getElementById('otpSendBtn');
    var verifyBtn = document.getElementById('otpVerifyBtn');
    var changeBtn = document.getElementById('otpChangePhoneBtn');
    var phoneInput = document.getElementById('otpPhone');
    var codeGroup = document.getElementById('otpCodeGroup');
    var profileGroup = document.getElementById('otpProfileGroup');
    var nameInput = document.getElementById('otpName');
    var emailInput = document.getElementById('otpEmail');
    var panelsStage = document.querySelector('.login-panels-stage');
    var otpBoxes = document.querySelectorAll('.login-otp-box');
    var messageEl = document.getElementById('otpLoginMessage');
    var resendTimer = null;
    var messageHideTimer = null;
    var isNewOtpUser = false;
    var otpNormalizedPhone = '';
    var isVerifyingOtp = false;
    var RESEND_SECONDS = 60;
    var SUCCESS_MSG_HIDE_MS = 4000;

    if (!sendBtn || !phoneInput) return;

    function clearResendTimer() {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
    }

    function clearMessageHideTimer() {
        if (messageHideTimer) {
            clearTimeout(messageHideTimer);
            messageHideTimer = null;
        }
    }

    function updateSendBtnLabel(text) {
        var span = sendBtn.querySelector('span');
        if (span) span.textContent = text;
    }

    function isVerifyStep() {
        return codeGroup && codeGroup.classList.contains('is-visible');
    }

    function getOtpCode() {
        var code = '';
        otpBoxes.forEach(function(box) {
            code += (box.value || '').replace(/\D/g, '');
        });
        return code;
    }

    function clearOtpBoxes() {
        otpBoxes.forEach(function(box) {
            box.value = '';
            box.classList.remove('is-filled');
        });
    }

    function setOtpBoxesEnabled(enabled) {
        otpBoxes.forEach(function(box) {
            box.disabled = !enabled;
        });
    }

    function focusOtpBox(index) {
        if (otpBoxes[index]) otpBoxes[index].focus();
    }

    function updateVerifyButtonVisibility() {
        if (!verifyBtn) return;
        verifyBtn.hidden = !isVerifyStep() || !isNewOtpUser;
    }

    function maybeAutoVerifyOtp() {
        if (!isVerifyStep() || isNewOtpUser || isVerifyingOtp) return;
        if (getOtpCode().length === 6) {
            submitOtpVerification();
        }
    }

    function initOtpBoxes() {
        otpBoxes.forEach(function(box, index) {
            box.addEventListener('input', function() {
                var digit = box.value.replace(/\D/g, '');
                box.value = digit ? digit.charAt(0) : '';
                box.classList.toggle('is-filled', box.value !== '');

                if (digit && index < otpBoxes.length - 1) {
                    focusOtpBox(index + 1);
                } else if (digit && index === otpBoxes.length - 1) {
                    maybeAutoVerifyOtp();
                }
            });

            box.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !box.value && index > 0) {
                    focusOtpBox(index - 1);
                    otpBoxes[index - 1].value = '';
                    otpBoxes[index - 1].classList.remove('is-filled');
                }
                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    focusOtpBox(index - 1);
                }
                if (e.key === 'ArrowRight' && index < otpBoxes.length - 1) {
                    e.preventDefault();
                    focusOtpBox(index + 1);
                }
            });

            box.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                if (!pasted) return;

                pasted.split('').forEach(function(ch, i) {
                    if (otpBoxes[i]) {
                        otpBoxes[i].value = ch;
                        otpBoxes[i].classList.add('is-filled');
                    }
                });

                focusOtpBox(Math.min(pasted.length, otpBoxes.length - 1));
                if (pasted.length === 6) {
                    maybeAutoVerifyOtp();
                }
            });

            box.addEventListener('focus', function() {
                box.select();
            });
        });
    }

    initOtpBoxes();

    function showOtpMessage(text, isError, autoHide) {
        clearMessageHideTimer();
        if (!messageEl) return;
        if (autoHide === undefined) autoHide = true;

        messageEl.classList.remove('is-collapsed', 'is-fading');
        messageEl.textContent = text;
        messageEl.className = 'login-otp-message ' + (isError ? 'is-error' : 'is-success');

        if (autoHide) {
            messageHideTimer = setTimeout(function() {
                messageEl.classList.add('is-fading');
                setTimeout(function() {
                    messageEl.classList.add('is-collapsed');
                    messageEl.classList.remove('is-fading');
                }, 300);
            }, SUCCESS_MSG_HIDE_MS);
        }
    }

    function startResendCountdown() {
        clearResendTimer();
        var remaining = RESEND_SECONDS;
        sendBtn.disabled = true;
        sendBtn.classList.add('is-countdown');
        updateSendBtnLabel('Resend OTP in ' + remaining + 's');

        resendTimer = setInterval(function() {
            remaining -= 1;
            if (remaining <= 0) {
                clearResendTimer();
                sendBtn.disabled = false;
                sendBtn.classList.remove('is-countdown');
                updateSendBtnLabel('Resend OTP');
                return;
            }
            updateSendBtnLabel('Resend OTP in ' + remaining + 's');
        }, 1000);
    }

    function setOtpProfileVisible(show) {
        if (!profileGroup) return;
        profileGroup.hidden = !show;
        if (panelsStage) {
            panelsStage.classList.toggle('is-otp-new-user', show);
        }
        if (!show) {
            if (nameInput) nameInput.value = '';
            if (emailInput) emailInput.value = '';
        }
    }

    function setOtpStep(step) {
        var sent = step === 'verify';
        if (panelsStage) {
            panelsStage.classList.toggle('is-otp-verify', sent);
        }
        if (codeGroup) {
            codeGroup.classList.toggle('is-visible', sent);
            codeGroup.setAttribute('aria-hidden', sent ? 'false' : 'true');
        }
        if (verifyBtn) updateVerifyButtonVisibility();
        if (changeBtn) changeBtn.hidden = !sent;
        if (phoneInput) phoneInput.readOnly = sent;
        setOtpBoxesEnabled(sent);

        if (!sent) {
            isVerifyingOtp = false;
            isNewOtpUser = false;
            otpNormalizedPhone = '';
            setOtpProfileVisible(false);
            clearResendTimer();
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.classList.remove('is-countdown');
                updateSendBtnLabel('Send OTP');
            }
            clearOtpBoxes();
        } else {
            setOtpProfileVisible(isNewOtpUser);
            updateVerifyButtonVisibility();
            focusOtpBox(0);
        }
    }

    function submitOtpVerification() {
        if (!verifyBtn || isVerifyingOtp) return;

        var phone = otpNormalizedPhone || phoneInput.value.trim();
        var code = getOtpCode();
        if (code.length !== 6) {
            showOtpMessage('Please enter the 6-digit verification code.', true);
            focusOtpBox(Math.min(code.length, 5));
            return;
        }

        if (isNewOtpUser) {
            var name = nameInput ? nameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            if (!name) {
                showOtpMessage('Please enter your full name.', true);
                if (nameInput) nameInput.focus();
                return;
            }
            if (!email) {
                showOtpMessage('Please enter your email address.', true);
                if (emailInput) emailInput.focus();
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showOtpMessage('Please enter a valid email address.', true);
                if (emailInput) emailInput.focus();
                return;
            }
        }

        isVerifyingOtp = true;
        if (!verifyBtn.hidden) {
            verifyBtn.disabled = true;
            verifyBtn.querySelector('span').textContent = 'Verifying...';
        }

        var body = new URLSearchParams();
        body.append('action', 'verify_otp');
        body.append('phone', phone);
        body.append('code', code);
        if (isNewOtpUser && nameInput && emailInput) {
            body.append('name', nameInput.value.trim());
            body.append('email', emailInput.value.trim());
        }

        fetch('<?php echo BASE_URL; ?>login-otp-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.needs_profile) {
                isNewOtpUser = true;
                setOtpProfileVisible(true);
                updateVerifyButtonVisibility();
            }
            showOtpMessage(data.message || 'Verification failed.', true);
            isVerifyingOtp = false;
            if (!verifyBtn.hidden) {
                verifyBtn.disabled = false;
                verifyBtn.querySelector('span').textContent = 'Verify & Login';
            }
        })
        .catch(function() {
            showOtpMessage('Verification failed. Please try again.', true);
            isVerifyingOtp = false;
            if (!verifyBtn.hidden) {
                verifyBtn.disabled = false;
                verifyBtn.querySelector('span').textContent = 'Verify & Login';
            }
        });
    }

    function requestOtp() {
        var phone = phoneInput.value.trim();
        if (!phone) {
            showOtpMessage('Please enter your phone number.', true);
            return;
        }

        var resending = isVerifyStep();
        sendBtn.disabled = true;
        sendBtn.classList.remove('is-countdown');
        updateSendBtnLabel(resending ? 'Resending...' : 'Sending...');
        if (messageEl && !resending) {
            messageEl.classList.add('is-collapsed');
            messageEl.classList.remove('is-fading');
            messageEl.textContent = '';
        }

        var body = new URLSearchParams();
        body.append('action', 'send_otp');
        body.append('phone', phone);

        fetch('<?php echo BASE_URL; ?>login-otp-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                isNewOtpUser = !!data.is_new_user;
                otpNormalizedPhone = data.normalized_phone || '';
                showOtpMessage('A 6-digit code has been sent to your phone.', false, true);
                clearOtpBoxes();
                setOtpStep('verify');
                startResendCountdown();
            } else {
                showOtpMessage(data.message || 'Could not send OTP.', true);
                if (!isVerifyStep()) {
                    sendBtn.disabled = false;
                    updateSendBtnLabel('Send OTP');
                } else {
                    clearResendTimer();
                    sendBtn.disabled = false;
                    sendBtn.classList.remove('is-countdown');
                    updateSendBtnLabel('Resend OTP');
                }
            }
        })
        .catch(function() {
            showOtpMessage('Could not send OTP. Please try again.', true);
            if (!isVerifyStep()) {
                sendBtn.disabled = false;
                updateSendBtnLabel('Send OTP');
            } else {
                clearResendTimer();
                sendBtn.disabled = false;
                sendBtn.classList.remove('is-countdown');
                updateSendBtnLabel('Resend OTP');
            }
        });
    }

    sendBtn.addEventListener('click', requestOtp);

    if (verifyBtn) {
        verifyBtn.addEventListener('click', submitOtpVerification);
    }

    if (changeBtn) {
        changeBtn.addEventListener('click', function() {
            clearResendTimer();
            clearMessageHideTimer();
            setOtpStep('phone');
            if (messageEl) {
                messageEl.classList.add('is-collapsed');
                messageEl.classList.remove('is-fading');
                messageEl.textContent = '';
            }
            phoneInput.focus();
        });
    }
})();
*/
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
