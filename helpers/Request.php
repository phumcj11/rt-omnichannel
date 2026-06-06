<?php
/**
 * Request path ภายใต้ base_path (ให้ตรงกับ Router)
 */
declare(strict_types=1);

namespace App\Helpers;

final class Request
{
    /**
     * @param array<string, mixed> $app config/app.php merged
     */
    public static function path(array $app): string
    {
        $base = rtrim((string) ($app['base_path'] ?? ''), '/');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
