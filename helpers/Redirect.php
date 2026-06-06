<?php
/**
 * HTTP redirect ภายใต้ base path
 */
declare(strict_types=1);

namespace App\Helpers;

final class Redirect
{
    public static function to(string $path): never
    {
        header('Location: ' . Url::to($path), true, 302);
        exit;
    }
}
