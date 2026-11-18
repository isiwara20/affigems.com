<?php
/**
 * Business Logic Layer - Authentication
 * Handles login, registration, and session management
 */

require_once __DIR__ . '/../dal/UserDAL.php';
require_once __DIR__ . '/../dal/AdminDAL.php';

class AuthBLL {
    private $userDAL;
    private $adminDAL;

    public function __construct() {
        $this->userDAL = new UserDAL();
        $this->adminDAL = new AdminDAL();
    }

    /**
     * Authenticate user or admin with email and password
     */
    public function login($email, $password, $remember = false) {
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        // First check admin table
        $admin = $this->adminDAL->findByEmail($email);
        if ($admin && password_verify($password, $admin['password'])) {
            // Set admin session
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_role'] = $admin['role'];
            $_SESSION['is_admin'] = true;
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'message' => 'Login successful.', 'is_admin' => true];
        }

        // Then check user table
        $user = $this->userDAL->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Set user session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['is_admin'] = false;
        $_SESSION['logged_in'] = true;

        // Handle "Remember Me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->userDAL->updateRememberToken($user['id'], $token);
            setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
        }

        return ['success' => true, 'message' => 'Login successful.', 'is_admin' => false];
    }

    /**
     * Register a new user
     */
    public function register($name, $email, $password, $confirmPassword) {
        // Validate input
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        // Check if email already exists in users
        $existingUser = $this->userDAL->findByEmail($email);
        if ($existingUser) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        // Check if email exists in admins
        $existingAdmin = $this->adminDAL->findByEmail($email);
        if ($existingAdmin) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        // Hash password and create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $result = $this->userDAL->create($name, $email, $hashedPassword);

        if ($result) {
            return ['success' => true, 'message' => 'Registration successful. Please login.'];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if logged in user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    /**
     * Auto-login via remember token
     */
    public function autoLogin() {
        if (!$this->isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $user = $this->userDAL->findByRememberToken($_COOKIE['remember_token']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'customer';
                $_SESSION['is_admin'] = false;
                $_SESSION['logged_in'] = true;
                return true;
            }
        }
        return false;
    }

    public function establishCustomerSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['is_admin'] = false;
        $_SESSION['logged_in'] = true;
    }

    public function getRedirectAfterLogin(): string
    {
        $redirectUrl = $_SESSION['redirect_after_login'] ?? BASE_URL . 'index.php';
        unset($_SESSION['redirect_after_login']);
        return $redirectUrl;
    }

    /**
     * Login or auto-register a customer using a verified phone number.
     */
    public function loginOrRegisterByPhone(string $phone, ?string $name = null, ?string $email = null): array
    {
        require_once __DIR__ . '/../helpers/sms.php';

        $normalized = ezymart_normalize_phone($phone);
        if (!$normalized) {
            return ['success' => false, 'message' => 'Invalid phone number.'];
        }

        $user = $this->userDAL->findByPhone($normalized);
        if (!$user) {
            $name = trim((string)($name ?? ''));
            $email = trim((string)($email ?? ''));

            if ($name === '' || $email === '') {
                return [
                    'success' => false,
                    'needs_profile' => true,
                    'message' => 'Please enter your full name and email address to create your account.',
                ];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Please enter a valid email address.'];
            }

            if ($this->userDAL->findByEmail($email)) {
                return [
                    'success' => false,
                    'message' => 'An account with this email already exists. Please use email login or a different email.',
                ];
            }

            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $created = $this->userDAL->create($name, $email, $password, $normalized);
            if (!$created) {
                return ['success' => false, 'message' => 'Could not create your account. Please try again.'];
            }
            $user = $this->userDAL->findByPhone($normalized);
        }

        if (!$user) {
            return ['success' => false, 'message' => 'Could not sign you in. Please try again.'];
        }

        $this->establishCustomerSession($user);
        return ['success' => true, 'message' => 'Login successful.'];
    }
}
