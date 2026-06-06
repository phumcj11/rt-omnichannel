<?php
/**
 * แท็กสำหรับ filter / แสดงในรายการแชท
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class Tag
{
    /** @return list<array{id:int,name:string,color_hex:string}> */
    public static function all(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query('SELECT id, name, color_hex FROM tags ORDER BY name ASC');
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
