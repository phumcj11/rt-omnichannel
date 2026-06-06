<?php
/**
 * View helper — render PHP view ภายใต้โฟลเดอร์ views
 */
declare(strict_types=1);

namespace App\Helpers;

final class View
{
    private static string $base = '';

    public static function setBase(string $path): void
    {
        self::$base = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = []): void
    {
        $file = self::$base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!is_readable($file)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
