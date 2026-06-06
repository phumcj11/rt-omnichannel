<?php
/**
 * Bootstrap — autoload, session (production-safe), error reporting
 */
declare(strict_types=1);

$basePath = dirname(__FILE__);

$config = require $basePath . '/config/app.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');

$sessionName = (string) ($config['session_name'] ?? 'OMNI_SESSID');
session_name($sessionName);

$basePathUrl = rtrim((string) ($config['base_path'] ?? ''), '/');
$cookiePath = $basePathUrl === '' ? '/' : $basePathUrl . '/';

$secure = $config['session_cookie_secure'] ?? null;
if ($secure === null) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

$sameSite = (string) ($config['session_samesite'] ?? 'Lax');
if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
    $sameSite = 'Lax';
}

session_set_cookie_params([
    'lifetime' => (int) ($config['session_cookie_lifetime'] ?? 0),
    'path' => $cookiePath,
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => $sameSite,
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($config['allow_dev_auto_login']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = (int) ($config['dev_impersonate_user_id'] ?? 1);
    $_SESSION['user_name'] = 'Dev (auto-login)';
    $_SESSION['user_email'] = '';
    $_SESSION['user_role'] = 'admin';
}

if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

spl_autoload_register(static function (string $class) use ($basePath): void {
    $prefixes = [
        'App\\Controllers\\' => $basePath . '/controllers/',
        'App\\Models\\' => $basePath . '/models/',
        'App\\Helpers\\' => $basePath . '/helpers/',
        'App\\Services\\' => $basePath . '/services/',
    ];
    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $file = $dir . $rel;
            if (is_readable($file)) {
                require $file;
            }

            return;
        }
    }
});
