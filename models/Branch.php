<?php
/**
 * สาขา
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class Branch
{
    /** @return list<array{id:int,name:string,code:string}> */
    public static function all(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query(
            'SELECT id, name, code FROM branches WHERE is_active = 1 ORDER BY name ASC'
        );
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
