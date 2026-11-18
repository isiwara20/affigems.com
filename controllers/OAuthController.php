<?php
/**
 * OAuth Controller
 * Handles Google and Facebook OAuth login/register
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/oauth.php';
require_once __DIR__ . '/../dal/UserDAL.php';
require_once __DIR__ . '/../bll/AuthBLL.php';
require_once __DIR__ . '/../helpers/oauth.php';

class OAuthController {
    private $userDAL;

    public function __construct() {
        $this->userDAL = new UserDAL();
    }

    /**
     * Redirect user to Google OAuth
     */
    public function redirectToGoogle() {
        if (!ezymart_google_oauth_enabled()) {
            $_SESSION['login_error'] = 'Google login is not configured yet.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
        ];
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback($code) {
        // Exchange code for access token
        $tokenData = $this->httpPost('https://oauth2.googleapis.com/token', [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenData['access_token'])) {
            $_SESSION['login_error'] = 'Google authentication failed. Please try again.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        // Get user info
        $userInfo = $this->httpGet(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            $tokenData['access_token']
        );

        if (!isset($userInfo['email'])) {
            $_SESSION['login_error'] = 'Could not retrieve email from Google.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $this->loginOrRegisterUser($userInfo['name'] ?? '', $userInfo['email']);
    }

    /**
     * Redirect user to Facebook OAuth
     */
    public function redirectToFacebook() {
        if (!ezymart_facebook_oauth_enabled()) {
            $_SESSION['login_error'] = 'Facebook login is not configured yet.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email,public_profile',
        ];
        $url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback($code) {
        // Exchange code for access token
        $tokenData = $this->httpPost('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => FACEBOOK_APP_ID,
            'client_secret' => FACEBOOK_APP_SECRET,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'code' => $code,
        ]);

        if (!isset($tokenData['access_token'])) {
            $_SESSION['login_error'] = 'Facebook authentication failed. Please try again.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        // Get user info
        $userInfo = $this->httpGet(
            'https://graph.facebook.com/v18.0/me?fields=id,name,email',
            $tokenData['access_token']
        );

        if (!isset($userInfo['email'])) {
            $_SESSION['login_error'] = 'Could not retrieve email from Facebook.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $this->loginOrRegisterUser($userInfo['name'] ?? '', $userInfo['email']);
    }

    /**
     * Login existing user or register new one from OAuth
     */
    private function loginOrRegisterUser($name, $email) {
        $user = $this->userDAL->findByEmail($email);

        if (!$user) {
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $this->userDAL->create($name !== '' ? $name : 'Customer', $email, $randomPassword);
            $user = $this->userDAL->findByEmail($email);
        }

        if (!$user) {
            $_SESSION['login_error'] = 'Could not sign you in. Please try again.';
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $authBLL = new AuthBLL();
        $authBLL->establishCustomerSession($user);
        header('Location: ' . $authBLL->getRedirectAfterLogin());
        exit;
    }

    /**
     * HTTP POST request
     */
    private function httpPost($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * HTTP GET request with Bearer token
     */
    private function httpGet($url, $accessToken) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}

// === Route handling ===
$controller = new OAuthController();
$provider = $_GET['provider'] ?? '';

// If there's a code parameter, it's a callback
if (isset($_GET['code'])) {
    switch ($provider) {
        case 'google':
            $controller->handleGoogleCallback($_GET['code']);
            break;
        case 'facebook':
            $controller->handleFacebookCallback($_GET['code']);
            break;
        default:
            header('Location: ' . BASE_URL . 'login.php');
            exit;
    }
}
// Otherwise, redirect to the provider
else {
    switch ($provider) {
        case 'google':
            $controller->redirectToGoogle();
            break;
        case 'facebook':
            $controller->redirectToFacebook();
            break;
        default:
            header('Location: ' . BASE_URL . 'login.php');
            exit;
    }
}
