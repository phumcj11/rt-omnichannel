<?php
/**
 * Idempotency สำหรับ webhook retries
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDOException;

final class WebhookDedup
{
    public static function isDuplicate(string $provider, string $dedupKey): bool
    {
        $pdo = Db::pdo();
        try {
            $st = $pdo->prepare(
                'INSERT INTO webhook_event_dedup (provider, dedup_key, created_at) VALUES (:p, :k, NOW())'
            );
            $st->execute(['p' => $provider, 'k' => $dedupKey]);

            return false;
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                return true;
            }
            throw $e;
        }
    }
}
