<?php
/**
 * OAuth availability helpers.
 */

function ezymart_oauth_placeholder_values(): array
{
    return [
        'YOUR_GOOGLE_CLIENT_ID_HERE',
        'YOUR_GOOGLE_CLIENT_SECRET_HERE',
        'YOUR_FACEBOOK_APP_ID_HERE',
        'YOUR_FACEBOOK_APP_SECRET_HERE',
    ];
}

function ezymart_oauth_credential_is_set(?string $value): bool
{
    $value = trim((string)$value);
    if ($value === '') {
        return false;
    }

    return !in_array($value, ezymart_oauth_placeholder_values(), true);
}

function ezymart_google_oauth_enabled(): bool
{
    if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
        return false;
    }

    return ezymart_oauth_credential_is_set(GOOGLE_CLIENT_ID)
        && ezymart_oauth_credential_is_set(GOOGLE_CLIENT_SECRET);
}

function ezymart_facebook_oauth_enabled(): bool
{
    if (!defined('FACEBOOK_APP_ID') || !defined('FACEBOOK_APP_SECRET')) {
        return false;
    }

    return ezymart_oauth_credential_is_set(FACEBOOK_APP_ID)
        && ezymart_oauth_credential_is_set(FACEBOOK_APP_SECRET);
}

function ezymart_social_login_enabled(): bool
{
    return ezymart_google_oauth_enabled() || ezymart_facebook_oauth_enabled();
}
