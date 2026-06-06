<?php
/**
 * Front controller — auth gate + ทุก request เข้าที่นี่
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Helpers\Auth;
use App\Helpers\Request;
use App\Helpers\Redirect;
use App\Helpers\View;

View::setBase(__DIR__ . '/views');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$app = require __DIR__ . '/config/app.php';
$path = Request::path($app);

if (!Auth::check()) {
    if ($path === '/login') {
        // ให้ render หน้า login
    } elseif ($path === '/logout') {
        Redirect::to('/login');
    } elseif (preg_match('#^/inbox/\d+/erp-search$#', $path)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        Redirect::to('/login?next=' . rawurlencode($path));
    }
}

$router = require __DIR__ . '/routes.php';
$router->setBasePath((string) ($app['base_path'] ?? ''));
$router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
