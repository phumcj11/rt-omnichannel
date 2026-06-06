<?php
/**
 * อ่านข้อมูลจากตาราง ERP cache — ค้นหาสินค้า / ราคา / สต็อกตามสาขา
 */
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Db;
use PDO;

final class ErpCatalogService extends BaseService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, ?int $branchId, int $limit = 25): array
    {
        $query = trim($query);
        if ($query === '' || $limit < 1) {
            return [];
        }

        $branchId = $branchId ?? 1;
        $pdo = Db::pdo();
        $like = '%' . $query . '%';

        $sql = <<<'SQL'
SELECT
  p.id,
  p.erp_sku,
  p.name_th,
  p.name_en,
  p.unit
FROM erp_products_cache p
WHERE p.is_active = 1
  AND (p.branch_id IS NULL OR p.branch_id = :bid)
  AND (
    p.name_th LIKE :q OR p.name_en LIKE :q2 OR p.erp_sku LIKE :q3
  )
ORDER BY p.name_th ASC
LIMIT :lim
SQL;

        $st = $pdo->prepare($sql);
        $st->bindValue(':bid', $branchId, PDO::PARAM_INT);
        $st->bindValue(':q', $like, PDO::PARAM_STR);
        $st->bindValue(':q2', $like, PDO::PARAM_STR);
        $st->bindValue(':q3', $like, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $sku = (string) $row['erp_sku'];
            $out[] = [
                'erp_sku' => $sku,
                'name_th' => (string) $row['name_th'],
                'name_en' => $row['name_en'] !== null ? (string) $row['name_en'] : null,
                'unit' => (string) $row['unit'],
                'price' => $this->fetchPrice($pdo, $sku, $branchId),
                'stock' => $this->fetchStock($pdo, $sku, $branchId),
            ];
        }

        return $out;
    }

    /**
     * @return array{price: float, currency: string}|null
     */
    private function fetchPrice(PDO $pdo, string $sku, int $branchId): ?array
    {
        $st = $pdo->prepare(
            'SELECT price, currency FROM erp_prices_cache
             WHERE erp_sku = :sku AND branch_id = :bid
             ORDER BY synced_at DESC LIMIT 1'
        );
        $st->execute(['sku' => $sku, 'bid' => $branchId]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r === false) {
            $st2 = $pdo->prepare(
                'SELECT price, currency FROM erp_prices_cache
                 WHERE erp_sku = :sku AND branch_id IS NULL
                 ORDER BY synced_at DESC LIMIT 1'
            );
            $st2->execute(['sku' => $sku]);
            $r = $st2->fetch(PDO::FETCH_ASSOC);
            if ($r === false) {
                return null;
            }
        }
        return [
            'price' => (float) $r['price'],
            'currency' => (string) $r['currency'],
        ];
    }

    /**
     * @return array{on_hand: float, reserved: float}|null
     */
    private function fetchStock(PDO $pdo, string $sku, int $branchId): ?array
    {
        $st = $pdo->prepare(
            'SELECT qty_on_hand, qty_reserved FROM erp_stocks_cache
             WHERE erp_sku = :sku AND branch_id = :bid
             LIMIT 1'
        );
        $st->execute(['sku' => $sku, 'bid' => $branchId]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r === false) {
            return null;
        }
        return [
            'on_hand' => (float) $r['qty_on_hand'],
            'reserved' => (float) $r['qty_reserved'],
        ];
    }
}
