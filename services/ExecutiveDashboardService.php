<?php
/**
 * Executive Dashboard — สรุป KPI จาก conversations / leads / messages
 */
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Db;
use PDO;

final class ExecutiveDashboardService extends BaseService
{
    private const ACTIVE_STATUSES = "'new','open','pending'";

    /**
     * @return array{
     *   kpis: array{
     *     open_conversations: int,
     *     total_unread: int,
     *     sla_overdue: int,
     *     open_leads: int,
     *     pipeline_value_thb: float,
     *     messages_today: int
     *   },
     *   by_channel: list<array<string, mixed>>,
     *   pipeline_stages: list<array<string, mixed>>,
     *   sla_hot: list<array<string, mixed>>,
     *   generated_at: string
     * }
     */
    public function snapshot(): array
    {
        $pdo = Db::pdo();

        $kpis = $this->fetchKpis($pdo);
        $byChannel = $this->fetchByChannel($pdo);
        $pipelineStages = $this->fetchPipelineStages($pdo);
        $slaHot = $this->fetchSlaHot($pdo, 8);

        return [
            'kpis' => $kpis,
            'by_channel' => $byChannel,
            'pipeline_stages' => $pipelineStages,
            'sla_hot' => $slaHot,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** @return array<string, int|float> */
    private function fetchKpis(PDO $pdo): array
    {
        $openStatuses = self::ACTIVE_STATUSES;

        $st = $pdo->query(
            "SELECT
               COUNT(*) AS open_conversations,
               COALESCE(SUM(c.unread_count), 0) AS total_unread
             FROM conversations c
             WHERE c.status IN ($openStatuses)"
        );
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $st2 = $pdo->query(
            "SELECT COUNT(*) AS n FROM conversations c
             WHERE c.status IN ($openStatuses)
               AND c.sla_due_at IS NOT NULL
               AND c.sla_due_at < NOW()"
        );
        $slaRow = $st2->fetch(PDO::FETCH_ASSOC) ?: ['n' => 0];

        $st3 = $pdo->query(
            "SELECT COUNT(*) AS n, COALESCE(SUM(deal_value), 0) AS v
             FROM leads WHERE status = 'open'"
        );
        $leadRow = $st3->fetch(PDO::FETCH_ASSOC) ?: ['n' => 0, 'v' => 0];

        $st4 = $pdo->query(
            'SELECT COUNT(*) AS n FROM messages WHERE DATE(created_at) = CURDATE()'
        );
        $msgRow = $st4->fetch(PDO::FETCH_ASSOC) ?: ['n' => 0];

        return [
            'open_conversations' => (int) ($row['open_conversations'] ?? 0),
            'total_unread' => (int) ($row['total_unread'] ?? 0),
            'sla_overdue' => (int) ($slaRow['n'] ?? 0),
            'open_leads' => (int) ($leadRow['n'] ?? 0),
            'pipeline_value_thb' => (float) ($leadRow['v'] ?? 0),
            'messages_today' => (int) ($msgRow['n'] ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchByChannel(PDO $pdo): array
    {
        $openStatuses = self::ACTIVE_STATUSES;
        $sql = <<<SQL
SELECT
  ch.id,
  ch.name AS channel_name,
  ch.code AS channel_code,
  ch.icon AS channel_icon,
  COUNT(*) AS open_count,
  COALESCE(SUM(c.unread_count), 0) AS unread_sum
FROM conversations c
INNER JOIN channels ch ON ch.id = c.channel_id
WHERE c.status IN ($openStatuses)
GROUP BY ch.id, ch.name, ch.code, ch.icon
ORDER BY open_count DESC, ch.name ASC
SQL;
        $st = $pdo->query($sql);
        if ($st === false) {
            return [];
        }
        return array_values($st->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPipelineStages(PDO $pdo): array
    {
        $sql = <<<'SQL'
SELECT
  ps.id,
  ps.name,
  ps.slug,
  ps.sort_order,
  ps.is_won,
  ps.is_lost,
  COUNT(l.id) AS lead_count,
  COALESCE(SUM(CASE WHEN l.status = 'open' AND l.deal_value IS NOT NULL THEN l.deal_value ELSE 0 END), 0) AS value_sum
FROM pipeline_stages ps
LEFT JOIN leads l ON l.pipeline_stage_id = ps.id AND l.status = 'open'
GROUP BY ps.id, ps.name, ps.slug, ps.sort_order, ps.is_won, ps.is_lost
ORDER BY ps.sort_order ASC
SQL;
        $st = $pdo->query($sql);
        if ($st === false) {
            return [];
        }
        return array_values($st->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSlaHot(PDO $pdo, int $limit): array
    {
        $openStatuses = self::ACTIVE_STATUSES;
        $lim = max(1, min(50, $limit));
        $sql = <<<SQL
SELECT
  c.id,
  c.status,
  c.priority,
  c.sla_due_at,
  c.unread_count,
  ct.display_name AS contact_name,
  ch.name AS channel_name,
  ch.code AS channel_code
FROM conversations c
INNER JOIN contacts ct ON ct.id = c.contact_id
INNER JOIN channels ch ON ch.id = c.channel_id
WHERE c.status IN ($openStatuses)
  AND c.sla_due_at IS NOT NULL
  AND c.sla_due_at < NOW()
ORDER BY c.sla_due_at ASC
LIMIT $lim
SQL;
        $st = $pdo->query($sql);
        if ($st === false) {
            return [];
        }
        return array_values($st->fetchAll(PDO::FETCH_ASSOC));
    }
}
