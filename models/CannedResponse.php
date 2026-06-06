<?php
/**
 * ข้อความสำเร็จรูป (canned response)
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class CannedResponse
{
    /** @return list<array{id:int,title:string,shortcut:?string,body:string}> */
    public static function activeList(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query(
            'SELECT id, title, shortcut, body FROM canned_responses WHERE is_active = 1 ORDER BY title ASC'
        );
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
