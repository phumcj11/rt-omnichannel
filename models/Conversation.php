<?php
/**
 * Conversation + Inbox queries
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;
final class Conversation
{
    /**
     * @param array{
     *   channel_id?:string,
     *   branch_id?:string,
     *   language?:string,
     *   tag_id?:string,
     *   status?:string,
     *   priority?:string,
     *   assign?:string,
     *   q?:string
     * } $filters
     * @return list<array<string,mixed>>
     */
    public static function listInbox(array $filters): array
    {
        $pdo = Db::pdo();
        $sql = <<<'SQL'
SELECT
  c.id,
  c.status,
  c.priority,
  c.language,
  c.sla_due_at,
  c.first_response_at,
  c.last_message_at,
  c.last_inbound_at,
  c.unread_count,
  c.is_wholesale,
  c.is_complaint,
  c.repeat_customer_ping,
  c.assigned_user_id,
  ct.display_name AS contact_name,
  ct.phone AS contact_phone,
  ct.is_foreign_customer,
  ch.name AS channel_name,
  ch.code AS channel_code,
  ch.icon AS channel_icon,
  b.name AS branch_name,
  u.name AS assignee_name,
  (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message_preview,
  (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message_at_exact,
  (SELECT GROUP_CONCAT(DISTINCT CONCAT(t.id, ':', t.name, ':', t.color_hex) ORDER BY t.name SEPARATOR '||')
     FROM conversation_tags ctg
     INNER JOIN tags t ON t.id = ctg.tag_id
     WHERE ctg.conversation_id = c.id
  ) AS tags_packed
FROM conversations c
INNER JOIN contacts ct ON ct.id = c.contact_id
INNER JOIN channels ch ON ch.id = c.channel_id
LEFT JOIN branches b ON b.id = c.branch_id
LEFT JOIN users u ON u.id = c.assigned_user_id
WHERE 1=1
SQL;

        $params = [];

        if (!empty($filters['channel_id'])) {
            $sql .= ' AND c.channel_id = :channel_id';
            $params['channel_id'] = (int) $filters['channel_id'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= ' AND c.branch_id = :branch_id';
            $params['branch_id'] = (int) $filters['branch_id'];
        }
        if (!empty($filters['language'])) {
            $sql .= ' AND c.language = :language';
            $params['language'] = $filters['language'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND c.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= ' AND c.priority = :priority';
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['tag_id'])) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM conversation_tags x WHERE x.conversation_id = c.id AND x.tag_id = :tag_id
            )';
            $params['tag_id'] = (int) $filters['tag_id'];
        }
        if (!empty($filters['assign'])) {
            if ($filters['assign'] === 'unassigned') {
                $sql .= ' AND c.assigned_user_id IS NULL';
            } elseif ($filters['assign'] === 'me') {
                $sql .= ' AND c.assigned_user_id = :me_id';
                $params['me_id'] = (int) ($filters['current_user_id'] ?? 0);
            } elseif (ctype_digit((string) $filters['assign'])) {
                $sql .= ' AND c.assigned_user_id = :assign_uid';
                $params['assign_uid'] = (int) $filters['assign'];
            }
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (
                ct.display_name LIKE :q OR ct.phone LIKE :q OR CAST(c.id AS CHAR) LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY c.last_message_at DESC, c.id DESC LIMIT 200';

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,mixed>|null */
    public static function findWithRelations(int $id): ?array
    {
        $pdo = Db::pdo();
        $sql = <<<'SQL'
SELECT
  c.*,
  ct.display_name AS contact_name,
  ct.email AS contact_email,
  ct.phone AS contact_phone,
  ct.language AS contact_language,
  ct.country_code,
  ct.is_foreign_customer,
  ct.notes AS contact_notes,
  ch.name AS channel_name,
  ch.code AS channel_code,
  b.name AS branch_name,
  u.name AS assignee_name,
  u.email AS assignee_email
FROM conversations c
INNER JOIN contacts ct ON ct.id = c.contact_id
INNER JOIN channels ch ON ch.id = c.channel_id
LEFT JOIN branches b ON b.id = c.branch_id
LEFT JOIN users u ON u.id = c.assigned_user_id
WHERE c.id = :id
LIMIT 1
SQL;
        $st = $pdo->prepare($sql);
        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return list<array{id:int,name:string,color_hex:string}>
     */
    public static function tagsForConversation(int $conversationId): array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT t.id, t.name, t.color_hex
             FROM tags t
             INNER JOIN conversation_tags ct ON ct.tag_id = t.id
             WHERE ct.conversation_id = :cid
             ORDER BY t.name'
        );
        $st->execute(['cid' => $conversationId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateAssign(int $conversationId, ?int $userId): bool
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'UPDATE conversations SET assigned_user_id = :uid, updated_at = NOW() WHERE id = :id'
        );
        return $st->execute(['uid' => $userId, 'id' => $conversationId]);
    }

    public static function updateStatus(int $conversationId, string $status): bool
    {
        $allowed = ['new', 'open', 'pending', 'resolved', 'closed'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'UPDATE conversations SET status = :st, updated_at = NOW() WHERE id = :id'
        );
        return $st->execute(['st' => $status, 'id' => $conversationId]);
    }

    /**
     * เพิ่มแท็กถ้ายังไม่มี
     */
    public static function addTag(int $conversationId, int $tagId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'INSERT IGNORE INTO conversation_tags (conversation_id, tag_id) VALUES (:c, :t)'
        );
        $st->execute(['c' => $conversationId, 't' => $tagId]);
    }

    /**
     * หาแชทที่ยังไม่ปิด หรือสร้างใหม่
     */
    public static function findOrCreateForInbound(
        int $contactId,
        int $channelId,
        int $branchId,
        ?string $slaDueAt,
        ?string $externalPageId = null
    ): int {
        $pdo = Db::pdo();
        $sql = "SELECT id, status FROM conversations
             WHERE contact_id = :cid AND channel_id = :chid AND status != 'closed'";
        $params = ['cid' => $contactId, 'chid' => $channelId];
        if ($externalPageId !== null && $externalPageId !== '') {
            $sql .= ' AND external_page_id = :epid';
            $params['epid'] = $externalPageId;
        }
        $sql .= ' ORDER BY id DESC LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            $id = (int) $row['id'];
            $status = (string) $row['status'];
            $newStatus = in_array($status, ['resolved', 'pending'], true) ? 'open' : $status;
            if ($newStatus === 'new') {
                $newStatus = 'open';
            }
            $up = $pdo->prepare(
                'UPDATE conversations
                 SET status = :st,
                     sla_due_at = :sla,
                     external_page_id = COALESCE(external_page_id, :epid),
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $up->execute([
                'st' => $newStatus,
                'sla' => $slaDueAt,
                'epid' => $externalPageId,
                'id' => $id,
            ]);

            return $id;
        }

        $ins = $pdo->prepare(
            'INSERT INTO conversations (
               contact_id, channel_id, external_page_id, branch_id, status, priority, language,
               sla_due_at, last_message_at, last_inbound_at, unread_count, created_at, updated_at
             ) VALUES (
               :cid, :chid, :epid, :bid, \'new\', \'normal\', \'th\',
               :sla, NOW(), NOW(), 0, NOW(), NOW()
             )'
        );
        $ins->execute([
            'cid' => $contactId,
            'chid' => $channelId,
            'epid' => $externalPageId,
            'bid' => $branchId,
            'sla' => $slaDueAt,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function afterInboundMessage(int $conversationId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'UPDATE conversations
             SET last_message_at = NOW(),
                 last_inbound_at = NOW(),
                 unread_count = unread_count + 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $st->execute(['id' => $conversationId]);
    }
}
