<?php
/**
 * Facebook Pages — รองรับหลายเพจ (Token ต่อ Page)
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class FacebookPage
{
    /** @return list<array<string,mixed>> */
    public static function allActive(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query(
            'SELECT fp.id, fp.page_id, fp.page_name, fp.branch_id, fp.is_active, fp.is_primary,
                    fp.updated_at, b.name AS branch_name
             FROM facebook_pages fp
             LEFT JOIN branches b ON b.id = fp.branch_id
             WHERE fp.is_active = 1
             ORDER BY fp.is_primary DESC, fp.page_name ASC, fp.id ASC'
        );

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,mixed>|null */
    public static function findByPageId(string $pageId): ?array
    {
        $pageId = trim($pageId);
        if ($pageId === '') {
            return null;
        }
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT id, page_id, page_name, page_access_token, branch_id, is_active, is_primary
             FROM facebook_pages
             WHERE page_id = :pid AND is_active = 1
             LIMIT 1'
        );
        $st->execute(['pid' => $pageId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function tokenForPageId(string $pageId): ?string
    {
        $row = self::findByPageId($pageId);
        if ($row === null) {
            return null;
        }
        $token = trim((string) ($row['page_access_token'] ?? ''));

        return $token !== '' ? $token : null;
    }

    public static function upsertPrimary(
        string $pageId,
        string $token,
        string $pageName = '',
        int $branchId = 1
    ): void {
        $pageId = trim($pageId);
        $token = trim($token);
        if ($pageId === '' || $token === '') {
            return;
        }

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE facebook_pages SET is_primary = 0 WHERE is_primary = 1');

            $st = $pdo->prepare(
                'INSERT INTO facebook_pages (
                   page_id, page_name, page_access_token, branch_id, is_active, is_primary, created_at, updated_at
                 ) VALUES (
                   :pid, :name, :token, :bid, 1, 1, NOW(), NOW()
                 )
                 ON DUPLICATE KEY UPDATE
                   page_name = IF(VALUES(page_name) != \'\', VALUES(page_name), page_name),
                   page_access_token = VALUES(page_access_token),
                   branch_id = VALUES(branch_id),
                   is_active = 1,
                   is_primary = 1,
                   updated_at = NOW()'
            );
            $st->execute([
                'pid' => $pageId,
                'name' => trim($pageName),
                'token' => $token,
                'bid' => $branchId > 0 ? $branchId : null,
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array{page_id:string,page_access_token:string,page_name?:string,branch_id?:int} $input
     */
    public static function addSecondary(array $input): bool
    {
        $pageId = trim($input['page_id'] ?? '');
        $token = trim($input['page_access_token'] ?? '');
        if ($pageId === '' || $token === '') {
            return false;
        }

        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'INSERT INTO facebook_pages (
               page_id, page_name, page_access_token, branch_id, is_active, is_primary, created_at, updated_at
             ) VALUES (
               :pid, :name, :token, :bid, 1, 0, NOW(), NOW()
             )
             ON DUPLICATE KEY UPDATE
               page_name = IF(VALUES(page_name) != \'\', VALUES(page_name), page_name),
               page_access_token = VALUES(page_access_token),
               branch_id = VALUES(branch_id),
               is_active = 1,
               updated_at = NOW()'
        );

        return $st->execute([
            'pid' => $pageId,
            'name' => trim((string) ($input['page_name'] ?? '')),
            'token' => $token,
            'bid' => !empty($input['branch_id']) ? (int) $input['branch_id'] : null,
        ]);
    }

    /** ย้ายค่าเดิมจาก app_settings ไปตาราง facebook_pages (ครั้งแรก) */
    public static function syncLegacyPrimary(string $pageId, string $token, int $branchId = 1): void
    {
        if (trim($pageId) === '' || trim($token) === '') {
            return;
        }
        $pdo = Db::pdo();
        $cnt = (int) $pdo->query('SELECT COUNT(*) FROM facebook_pages')->fetchColumn();
        if ($cnt > 0) {
            return;
        }
        self::upsertPrimary($pageId, $token, '', $branchId);
    }

    public static function countActive(): int
    {
        $pdo = Db::pdo();

        return (int) $pdo->query('SELECT COUNT(*) FROM facebook_pages WHERE is_active = 1')->fetchColumn();
    }
}
