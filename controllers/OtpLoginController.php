<?php
/**
 * SMS OTP login — send and verify one-time codes.
 */

function ezymart_otp_json(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ezymart_otp_start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

ezymart_otp_start_session();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../bll/AuthBLL.php';
require_once __DIR__ . '/../dal/LoginOtpDAL.php';
require_once __DIR__ . '/../dal/UserDAL.php';
require_once __DIR__ . '/../helpers/sms.php';

$action = trim((string)($_POST['action'] ?? ''));
$phoneRaw = trim((string)($_POST['phone'] ?? ''));
$code = preg_replace('/\D+/', '', trim((string)($_POST['code'] ?? '')));
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = ezymart_normalize_phone($phoneRaw);

if ($phone === null && !empty($_SESSION['otp_login_phone'])) {
    $phone = (string)$_SESSION['otp_login_phone'];
}

if ($phone === null) {
    ezymart_otp_json(['success' => false, 'message' => 'Please enter a valid phone number.'], 400);
}

if (!ezymart_sms_is_configured()) {
    ezymart_otp_json(['success' => false, 'message' => 'SMS login is not available right now. Please use email login.'], 503);
}

try {
    $otpDAL = new LoginOtpDAL();
    $authBLL = new AuthBLL();
    $userDAL = new UserDAL();

    if ($action === 'send_otp') {
        if ($otpDAL->countRecentSends($phone, 15) >= 5) {
            ezymart_otp_json([
                'success' => false,
                'message' => 'Too many OTP requests. Please wait a few minutes and try again.',
            ], 429);
        }

        $otpCode = $otpDAL->createOtp($phone, 5);
        $message = 'Your login code is ' . $otpCode . '. Valid for 5 minutes. Do not share this code.';
        $sent = ezymart_sms_send($phone, $message, 'login_otp');

        if (!$sent) {
            ezymart_otp_json(['success' => false, 'message' => 'Could not send OTP. Please try again.'], 500);
        }

        $_SESSION['otp_login_phone'] = $phone;

        $existingUser = $userDAL->findByPhone($phone);

        ezymart_otp_json([
            'success' => true,
            'message' => 'A 6-digit code has been sent to your phone.',
            'is_new_user' => !$existingUser,
            'normalized_phone' => $phone,
        ]);
    }

    if ($action === 'verify_otp') {
        $verifyPhone = $_SESSION['otp_login_phone'] ?? $phone;
        if ($verifyPhone !== $phone) {
            $phone = $verifyPhone;
        }

        if ($code === '' || strlen($code) !== 6) {
            ezymart_otp_json(['success' => false, 'message' => 'Please enter the 6-digit code.'], 400);
        }

        if (!$otpDAL->verifyOtp($phone, $code)) {
            ezymart_otp_json(['success' => false, 'message' => 'Invalid or expired code. Please try again.'], 400);
        }

        $result = $authBLL->loginOrRegisterByPhone($phone, $name !== '' ? $name : null, $email !== '' ? $email : null);
        if (!empty($result['needs_profile'])) {
            ezymart_otp_json([
                'success' => false,
                'needs_profile' => true,
                'message' => $result['message'],
            ], 400);
        }
        if (!$result['success']) {
            ezymart_otp_json(['success' => false, 'message' => $result['message']], 400);
        }

        unset($_SESSION['otp_login_phone']);

        ezymart_otp_json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => $authBLL->getRedirectAfterLogin(),
        ]);
    }

    ezymart_otp_json(['success' => false, 'message' => 'Invalid action.'], 400);
} catch (Throwable $e) {
    error_log('EZYMART OTP login error: ' . $e->getMessage());
    ezymart_otp_json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
