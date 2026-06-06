<?php
/**
 * ลูกค้า + identity ตามช่องทาง (PSID ฯลฯ)
 */
declare(strict_types=1);

namespace App\Models;

use App\Helpers\Db;
use PDO;
use PDOException;

final class Contact
{
    /**
     * @param array<string, mixed>|null $profileJson
     * @return array{contact_id: int, external_id: string}
     */
    public static function findOrCreateByExternalId(
        int $channelId,
        string $externalId,
        string $displayName,
        ?array $profileJson = null,
        int $branchId = 1
    ): array {
        $externalId = trim($externalId);
        $pdo = Db::pdo();

        $st = $pdo->prepare(
            'SELECT ci.contact_id, ci.external_id, c.display_name
             FROM contact_identities ci
             INNER JOIN contacts c ON c.id = ci.contact_id
             WHERE ci.channel_id = :ch AND ci.external_id = :ext
             LIMIT 1'
        );
        $st->execute(['ch' => $channelId, 'ext' => $externalId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            $contactId = (int) $row['contact_id'];
            if ($displayName !== '' && $displayName !== (string) $row['display_name']) {
                $up = $pdo->prepare(
                    'UPDATE contacts SET display_name = :n, updated_at = NOW() WHERE id = :id'
                );
                $up->execute(['n' => $displayName, 'id' => $contactId]);
            }
            if ($profileJson !== null) {
                $up2 = $pdo->prepare(
                    'UPDATE contact_identities SET profile_json = :j, updated_at = NOW()
                     WHERE channel_id = :ch AND external_id = :ext'
                );
                $up2->execute([
                    'j' => json_encode($profileJson, JSON_UNESCAPED_UNICODE),
                    'ch' => $channelId,
                    'ext' => $externalId,
                ]);
            }

            return ['contact_id' => $contactId, 'external_id' => $externalId];
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO contacts (branch_id, display_name, language, created_at, updated_at)
                 VALUES (:bid, :name, :lang, NOW(), NOW())'
            );
            $ins->execute([
                'bid' => $branchId,
                'name' => $displayName !== '' ? $displayName : 'Facebook User',
                'lang' => 'th',
            ]);
            $contactId = (int) $pdo->lastInsertId();

            $insId = $pdo->prepare(
                'INSERT INTO contact_identities (contact_id, channel_id, external_id, profile_json, created_at, updated_at)
                 VALUES (:cid, :ch, :ext, :prof, NOW(), NOW())'
            );
            $insId->execute([
                'cid' => $contactId,
                'ch' => $channelId,
                'ext' => $externalId,
                'prof' => $profileJson !== null ? json_encode($profileJson, JSON_UNESCAPED_UNICODE) : null,
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['contact_id' => $contactId, 'external_id' => $externalId];
    }

    public static function externalIdForConversation(int $conversationId, int $channelId): ?string
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT ci.external_id
             FROM conversations c
             INNER JOIN contact_identities ci
               ON ci.contact_id = c.contact_id AND ci.channel_id = c.channel_id
             WHERE c.id = :cid AND c.channel_id = :ch
             LIMIT 1'
        );
        $st->execute(['cid' => $conversationId, 'ch' => $channelId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? (string) $row['external_id'] : null;
    }
}
