<?php
/**
 * บันทึก webhook ลงไฟล์ (สำรองเมื่อ DB ล้ม หรือ debug ว่า request ถึงเซิร์ฟเวอร์หรือไม่)
 */
declare(strict_types=1);

namespace App\Helpers;

final class WebhookTrace
{
    private const FILE = 'webhook-facebook.log';

    public static function log(string $line): void
    {
        $dir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . self::FILE;
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($path, "[$ts] $line\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * @return list<string>
     */
    public static function tail(int $lines = 15): array
    {
        $path = dirname(__DIR__) . '/storage/logs/' . self::FILE;
        if (!is_readable($path)) {
            return [];
        }
        $content = (string) file_get_contents($path);
        if ($content === '') {
            return [];
        }
        $all = preg_split('/\r\n|\n|\r/', trim($content)) ?: [];

        return array_slice($all, -max(1, $lines));
    }
}
