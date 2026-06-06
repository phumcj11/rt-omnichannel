<?php
/**
 * Base สำหรับ Service layer — ดึง config / log (ขยายใน Phase ถัดไป)
 */
declare(strict_types=1);

namespace App\Services;

abstract class BaseService
{
    /** @return array<string, mixed> */
    protected static function app(): array
    {
        return require dirname(__DIR__) . '/config/app.php';
    }
}
