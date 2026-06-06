<?php
/**
 * ผู้ใช้งานระบบ (agent สำหรับมอบหมายงาน)
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class User
{
    /**
     * สำหรับ login เท่านั้น — อย่าส่งต่อ password_hash ไป view
     *
     * @return array{id:int,name:string,email:string,password_hash:string,role:string,is_active:int}|null
     */
    public static function findActiveByEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = :e LIMIT 1'
        );
        $st->execute(['e' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        if (empty($row['is_active'])) {
            return null;
        }

        return $row;
    }

    /** @return list<array{id:int,name:string,email:string,role:string}> */
    public static function agentsForAssign(): array
    {
        $pdo = Db::pdo();
        $st = $pdo->query(
            'SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY name ASC'
        );
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
