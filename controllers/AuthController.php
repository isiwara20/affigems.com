<?php
/**
 * Controller - Authentication
 * Handles HTTP requests for login/register and delegates to BLL
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../bll/AuthBLL.php';
require_once __DIR__ . '/../services/AsyncMailQueue.php';

class AuthController {
    private $authBLL;

    public function __construct() {
        $this->authBLL = new AuthBLL();
    }

    /**
     * Handle login form submission
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            $result = $this->authBLL->login($email, $password, $remember);

            if ($result['success']) {
                // Redirect admin to admin dashboard, user to home or saved redirect
                if (isset($result['is_admin']) && $result['is_admin']) {
                    header('Location: ' . BASE_URL . 'admin/index.php');
                } else {
                    // Check for redirect after login (e.g., from cart)
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? BASE_URL . 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirectUrl);
                }
                exit;
            } else {
                $_SESSION['login_error'] = $result['message'];
                $_SESSION['login_email'] = $email;
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            }
        }
    }

    /**
     * Handle logout
     */
    public function logout() {
        $this->authBLL->logout();
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    /**
     * Handle register form submission
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $result = $this->authBLL->register($name, $email, $password, $confirmPassword);

            if ($result['success']) {
                $_SESSION['register_success'] = $result['message'];
                header('Location: ' . BASE_URL . 'register.php');
                exit;
            } else {
                $_SESSION['register_error'] = $result['message'];
                $_SESSION['register_name'] = $name;
                $_SESSION['register_email'] = $email;
                $_SESSION['register_phone'] = $phone;
                header('Location: ' . BASE_URL . 'register.php');
                exit;
            }
        }
    }

    /**
     * Handle forgot password form submission
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $_SESSION['fp_error'] = 'Please enter your email address.';
                header('Location: ' . BASE_URL . 'forgot-password.php');
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['fp_error'] = 'Please enter a valid email address.';
                $_SESSION['fp_email'] = $email;
                header('Location: ' . BASE_URL . 'forgot-password.php');
                exit;
            }

            // Check if email exists (in users or admins)
            require_once __DIR__ . '/../dal/PasswordResetDAL.php';
            require_once __DIR__ . '/../services/MailService.php';

            $userDAL = new UserDAL();
            $adminDAL = new AdminDAL();

            $user = $userDAL->findByEmail($email);
            $admin = $adminDAL->findByEmail($email);

            if ($user || $admin) {
                $name = $user ? $user['name'] : $admin['name'];

                // Generate token (expires in 1 hour)
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $resetDAL = new PasswordResetDAL();
                $resetDAL->createToken($email, $token, $expiresAt);

                // Build reset link (use full URL for email)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? (defined('STORE_DOMAIN') ? STORE_DOMAIN : 'affigems.com');
                $resetLink = $protocol . '://' . $host . rtrim(BASE_URL, '/') . '/reset-password.php?token=' . $token;

                // Queue email to send AFTER response (no page delay)
                $emailCopy = $email;
                $nameCopy = $name;
                $linkCopy = $resetLink;
                AsyncMailQueue::add(function() use ($emailCopy, $nameCopy, $linkCopy) {
                    $mailService = new MailService();
                    $result = $mailService->sendPasswordResetEmail($emailCopy, $nameCopy, $linkCopy);
                    if (!$result['success']) {
                        error_log("Password reset email failed for $emailCopy: " . $result['message']);
                    }
                });
            }

            // Always show success (don't reveal if email exists)
            $_SESSION['fp_success'] = 'A password reset link has been sent to your email.';
            header('Location: ' . BASE_URL . 'forgot-password.php');
            AsyncMailQueue::flushAfterRedirect();
            exit;
        }
    }

    /**
     * Handle reset password form submission
     */
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($token)) {
                $_SESSION['reset_error'] = 'Invalid reset token.';
                header('Location: ' . BASE_URL . 'reset-password.php?token=' . $token);
                exit;
            }

            if (strlen($password) < 6) {
                $_SESSION['reset_error'] = 'Password must be at least 6 characters.';
                header('Location: ' . BASE_URL . 'reset-password.php?token=' . $token);
                exit;
            }

            if ($password !== $confirmPassword) {
                $_SESSION['reset_error'] = 'Passwords do not match.';
                header('Location: ' . BASE_URL . 'reset-password.php?token=' . $token);
                exit;
            }

            require_once __DIR__ . '/../dal/PasswordResetDAL.php';

            $resetDAL = new PasswordResetDAL();
            $resetRecord = $resetDAL->findValidToken($token);

            if (!$resetRecord) {
                $_SESSION['reset_error'] = 'This reset link has expired or is invalid. Please request a new one.';
                header('Location: ' . BASE_URL . 'reset-password.php?token=' . $token);
                exit;
            }

            $email = $resetRecord['email'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Update password in users table
            $userDAL = new UserDAL();
            $user = $userDAL->findByEmail($email);

            if ($user) {
                $this->updateUserPassword($email, $hashedPassword, 'users');
            } else {
                // Check admins table
                require_once __DIR__ . '/../dal/AdminDAL.php';
                $adminDAL = new AdminDAL();
                $admin = $adminDAL->findByEmail($email);
                if ($admin) {
                    $this->updateUserPassword($email, $hashedPassword, 'admins');
                }
            }

            // Mark token as used
            $resetDAL->markAsUsed($token);

            $_SESSION['reset_success'] = 'Your password has been reset successfully. You can now login with your new password.';
            header('Location: ' . BASE_URL . 'reset-password.php?token=' . $token);
            exit;
        }
    }

    /**
     * Update password in specified table
     */
    private function updateUserPassword($email, $hashedPassword, $table) {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("UPDATE $table SET password = :password WHERE email = :email");
        $stmt->execute([':password' => $hashedPassword, ':email' => $email]);
    }
}

// Process requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $action = $_POST['action'] ?? 'login';

    switch ($action) {
        case 'login':
            $controller->login();
            break;
        case 'register':
            $controller->register();
            break;
        case 'forgot_password':
            $controller->forgotPassword();
            break;
        case 'reset_password':
            $controller->resetPassword();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            header('Location: ' . BASE_URL . 'login.php');
            exit;
    }
}

// Handle GET logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $controller = new AuthController();
    $controller->logout();
}
