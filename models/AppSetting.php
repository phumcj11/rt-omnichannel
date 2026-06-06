<?php
/**
 * ค่า key-value ใน app_settings (ใช้เก็บ token ช่องทางจาก UI)
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class AppSetting
{
    public static function get(string $key): ?string
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
        $st->execute(['k' => $key]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false || $row['setting_value'] === null) {
            return null;
        }

        return (string) $row['setting_value'];
    }

    public static function set(string $key, ?string $value): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $st->execute(['k' => $key, 'v' => $value]);
    }

    /**
     * @param list<string> $keys
     * @return array<string, string|null>
     */
    public static function getMany(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        $pdo = Db::pdo();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $st = $pdo->prepare(
            "SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ($placeholders)"
        );
        $st->execute($keys);
        $out = array_fill_keys($keys, null);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string) $row['setting_key']] = $row['setting_value'] !== null
                ? (string) $row['setting_value']
                : null;
        }

        return $out;
    }

    public static function maskSecret(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($value, 0, 4) . str_repeat('•', min(12, $len - 8)) . substr($value, -4);
    }
}
