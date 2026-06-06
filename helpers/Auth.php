<?php
/**
 * Session auth — production ไม่ auto-login
 */
declare(strict_types=1);

namespace App\Helpers;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public static function userId(): ?int
    {
        if (!self::check()) {
            return null;
        }

        return (int) $_SESSION['user_id'];
    }

    public static function userName(): ?string
    {
        $n = $_SESSION['user_name'] ?? null;

        return is_string($n) && $n !== '' ? $n : null;
    }

    public static function userEmail(): ?string
    {
        $e = $_SESSION['user_email'] ?? null;

        return is_string($e) && $e !== '' ? $e : null;
    }

    public static function userRole(): string
    {
        $r = $_SESSION['user_role'] ?? '';

        return is_string($r) ? $r : '';
    }

    public static function canManageSettings(): bool
    {
        return in_array(self::userRole(), ['admin', 'manager'], true);
    }

    /**
     * @param array{id:int,name:string,email:string,password_hash:string,role:string,is_active:int} $row
     */
    public static function loginWithUserRow(array $row): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['user_name'] = (string) $row['name'];
        $_SESSION['user_email'] = (string) $row['email'];
        $_SESSION['user_role'] = (string) $row['role'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }
}
