<?php
/**
 * ERP Integration — stub สำหรับ Phase 5 (API mode / DB sync + cache tables)
 */
declare(strict_types=1);

namespace App\Services;

final class ErpIntegrationService extends BaseService
{
    /**
     * @return array<string, mixed>
     */
    public function syncProducts(): array
    {
        return ['ok' => false, 'message' => 'Not implemented — use erp_products_cache after cron/API'];
    }
}
