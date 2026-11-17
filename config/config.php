<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Douala');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_name('forfait_pulse');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$privateConfig = __DIR__ . '/private.local.php';
if (is_file($privateConfig)) {
    require_once $privateConfig;
}

define('SUPPORT_WHATSAPP', '+237678519844');
define('BUMXPRESS_SANDBOX_URL', 'https://sandbox.api.business.bumxpress.com');
define('BUMXPRESS_PRODUCTION_URL', 'https://api.business.bumxpress.com');

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : trim((string) $value);
}

function bumxpress_base_url(): string
{
    return env_value('BUMXPRESS_ENVIRONMENT', 'sandbox') === 'production'
        ? BUMXPRESS_PRODUCTION_URL
        : BUMXPRESS_SANDBOX_URL;
}

function bumxpress_is_configured(): bool
{
    return env_value('BUMXPRESS_API_KEY') !== ''
        && env_value('BUMXPRESS_API_SECRET') !== ''
        && env_value('BUMXPRESS_WEBHOOK_SECRET') !== '';
}
