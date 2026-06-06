<?php
/**
 * ช่องทางแชท
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class Channel
{
    /** @return array{id:int,name:string,code:string,icon:?string,is_active:int}|null */
    public static function findByCode(string $code): ?array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT id, name, code, icon, is_active FROM channels WHERE code = :code LIMIT 1'
        );
        $st->execute(['code' => $code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @return list<array{id:int,name:string,code:string,icon:?string}> */
    public static function all(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query(
            'SELECT id, name, code, icon FROM channels WHERE is_active = 1 ORDER BY name ASC'
        );
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
