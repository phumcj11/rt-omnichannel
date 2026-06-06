<?php
/**
 * SLA due time จาก sla_rules
 */
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Db;
use PDO;

final class SlaService extends BaseService
{
    public static function dueAtForChannel(string $channelCode, ?string $category = null): ?string
    {
        $pdo = Db::pdo();
        if ($category !== null && $category !== '') {
            $st = $pdo->prepare(
                "SELECT minutes FROM sla_rules
                 WHERE is_active = 1 AND rule_kind = 'category' AND category = :cat
                 ORDER BY priority ASC LIMIT 1"
            );
            $st->execute(['cat' => $category]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                return self::addMinutes((int) $row['minutes']);
            }
        }

        $st2 = $pdo->prepare(
            "SELECT minutes FROM sla_rules
             WHERE is_active = 1 AND rule_kind = 'channel' AND channel_code = :code
             ORDER BY priority ASC LIMIT 1"
        );
        $st2->execute(['code' => $channelCode]);
        $row2 = $st2->fetch(PDO::FETCH_ASSOC);
        if ($row2 === false) {
            return self::addMinutes(5);
        }

        return self::addMinutes((int) $row2['minutes']);
    }

    private static function addMinutes(int $minutes): string
    {
        $minutes = max(1, $minutes);

        return date('Y-m-d H:i:s', strtotime('+' . $minutes . ' minutes'));
    }
}
