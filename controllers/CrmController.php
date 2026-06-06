<?php
/**
 * CRM — placeholder (pipeline preview)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use Throwable;

final class CrmController
{
    public function index(): void
    {
        $app = require dirname(__DIR__) . '/config/app.php';
        $stages = [];
        $leads = [];
        $dbError = null;
        try {
            $pdo = \App\Helpers\Db::pdo();
            $stages = $pdo->query(
                'SELECT id, name, slug, sort_order, is_won, is_lost FROM pipeline_stages ORDER BY sort_order ASC'
            )->fetchAll(\PDO::FETCH_ASSOC);
            $leads = $pdo->query(
                'SELECT l.id, l.title, l.score, l.deal_value, l.currency, l.status,
                        ps.name AS stage_name, ct.display_name AS contact_name
                 FROM leads l
                 INNER JOIN pipeline_stages ps ON ps.id = l.pipeline_stage_id
                 INNER JOIN contacts ct ON ct.id = l.contact_id
                 ORDER BY l.updated_at DESC LIMIT 20'
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $dbError = !empty($app['debug']) ? $e->getMessage() : 'โหลดข้อมูลไม่สำเร็จ';
        }

        View::render('layouts/app', [
            'title' => 'CRM',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'crm',
            'contentView' => 'crm/index',
            'contentData' => [
                'stages' => $stages,
                'leads' => $leads,
                'dbError' => $dbError,
            ],
        ]);
    }
}
