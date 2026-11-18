<?php
/**
 * OAuth Configuration
 *
 * GOOGLE:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a project -> APIs & Services -> Credentials
 * 3. Create OAuth 2.0 Client ID (Web Application)
 * 4. Set Authorized redirect URI to your live callback URL below
 *
 * FACEBOOK:
 * 1. Go to https://developers.facebook.com/
 * 2. Create an App -> add Facebook Login
 * 3. Set Valid OAuth Redirect URI to your live callback URL below
 */

require_once __DIR__ . '/app.php';

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', absolute_url('controllers/OAuthController.php?provider=google'));
}

if (!defined('FACEBOOK_APP_ID')) {
    define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID_HERE');
}
if (!defined('FACEBOOK_APP_SECRET')) {
    define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET_HERE');
}
if (!defined('FACEBOOK_REDIRECT_URI')) {
    define('FACEBOOK_REDIRECT_URI', absolute_url('controllers/OAuthController.php?provider=facebook'));
}
