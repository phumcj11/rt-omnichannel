<?php
/**
 * ข้อความในแชท
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;

final class Message
{
    /**
     * @return list<array<string,mixed>>
     */
    public static function forConversation(int $conversationId): array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT m.*, u.name AS agent_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.conversation_id = :cid
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $st->execute(['cid' => $conversationId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insertOutbound(
        int $conversationId,
        string $body,
        int $userId,
        string $messageType = 'text'
    ): ?int {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'INSERT INTO messages (conversation_id, direction, message_type, body, user_id, created_at)
             VALUES (:cid, \'outbound\', :mt, :body, :uid, NOW())'
        );
        $ok = $st->execute([
            'cid' => $conversationId,
            'mt' => $messageType,
            'body' => $body,
            'uid' => $userId,
        ]);
        if (!$ok) {
            return null;
        }
        $id = (int) $pdo->lastInsertId();
        self::touchConversationAfterMessage($pdo, $conversationId, $messageType);

        return $id;
    }

    /**
     * @param array<string, mixed>|null $payloadJson
     */
    public static function insertInbound(
        int $conversationId,
        string $body,
        ?string $externalMessageId = null,
        string $messageType = 'text',
        ?array $payloadJson = null
    ): ?int {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'INSERT INTO messages (
               conversation_id, direction, message_type, body, payload_json, external_message_id, created_at
             ) VALUES (
               :cid, \'inbound\', :mt, :body, :payload, :ext, NOW()
             )'
        );
        $ok = $st->execute([
            'cid' => $conversationId,
            'mt' => $messageType,
            'body' => $body,
            'payload' => $payloadJson !== null ? json_encode($payloadJson, JSON_UNESCAPED_UNICODE) : null,
            'ext' => $externalMessageId,
        ]);
        if (!$ok) {
            return null;
        }
        $id = (int) $pdo->lastInsertId();
        Conversation::afterInboundMessage($conversationId);

        return $id;
    }

    public static function setExternalMessageId(int $messageId, string $externalId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'UPDATE messages SET external_message_id = :ext WHERE id = :id'
        );
        $st->execute(['ext' => $externalId, 'id' => $messageId]);
    }

    /**
     * internal_note — เก็บเป็น outbound + message_type internal_note
     */
    public static function insertInternalNote(int $conversationId, string $body, int $userId): bool
    {
        return self::insertOutbound($conversationId, $body, $userId, 'internal_note') !== null;
    }

    private static function touchConversationAfterMessage(\PDO $pdo, int $conversationId, string $messageType): void
    {
        if ($messageType === 'internal_note') {
            $st = $pdo->prepare(
                'UPDATE conversations SET updated_at = NOW() WHERE id = :id'
            );
            $st->execute(['id' => $conversationId]);
            return;
        }
        $st = $pdo->prepare(
            'UPDATE conversations
             SET last_message_at = NOW(),
                 first_response_at = COALESCE(first_response_at, NOW()),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $st->execute(['id' => $conversationId]);
    }
}
