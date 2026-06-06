<?php
/**
 * Login / Logout — production
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Redirect;
use App\Helpers\View;
use App\Models\User;

final class AuthController
{
    /** @return array<string, mixed> */
    private function appConfig(): array
    {
        return require dirname(__DIR__) . '/config/app.php';
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            Redirect::to($this->safeNext($_GET['next'] ?? null));
        }
        $app = $this->appConfig();
        $error = $_GET['err'] ?? null;
        $errMsg = null;
        if ($error === '1') {
            $errMsg = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        } elseif ($error === 'csrf') {
            $errMsg = 'เซสชันหมดอายุ กรุณาลองอีกครั้ง';
        }
        $okOut = isset($_GET['ok']) && $_GET['ok'] === 'out';
        View::render('layouts/auth', [
            'title' => 'เข้าสู่ระบบ',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'contentView' => 'auth/login',
            'contentData' => [
                'error' => $errMsg,
                'okOut' => $okOut,
                'next' => $this->safeNext($_GET['next'] ?? null),
                'debug' => !empty($app['debug']),
                'showLoginHint' => true,
            ],
        ]);
    }

    public function login(): void
    {
        if (Auth::check()) {
            Redirect::to($this->safeNext($_POST['next'] ?? ($_GET['next'] ?? null)));
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/login');
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Redirect::to('/login?err=csrf');
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $next = $this->safeNext($_POST['next'] ?? null);

        if ($email === '' || $password === '') {
            Redirect::to('/login?err=1&next=' . rawurlencode($next));
        }

        $row = User::findActiveByEmail($email);
        if ($row === null || !password_verify($password, (string) $row['password_hash'])) {
            Redirect::to('/login?err=1&next=' . rawurlencode($next));
        }

        Auth::loginWithUserRow($row);

        Redirect::to($next);
    }

    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/');
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Redirect::to('/');
        }
        Auth::logout();
        Redirect::to('/login?ok=out');
    }

    private function safeNext(?string $next): string
    {
        $next = trim((string) $next);
        if ($next === '' || $next === '/') {
            return '/';
        }
        if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return '/';
        }

        return $next;
    }
}
