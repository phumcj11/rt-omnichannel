<?php
/**
 * สร้าง URL ภายใต้ base_path ของโปรเจกต์
 */
declare(strict_types=1);

namespace App\Helpers;

final class Url
{
    public static function base(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cfg = require dirname(__DIR__) . '/config/app.php';
        $cached = rtrim((string) ($cfg['base_path'] ?? ''), '/');
        return $cached;
    }

    public static function to(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $b = self::base();
        return $b === '' ? $path : $b . $path;
    }
}
