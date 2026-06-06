<?php
/**
 * Executive Dashboard — Phase 3 (KPI จากฐานข้อมูลจริง)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Services\ExecutiveDashboardService;
use Throwable;

final class HomeController
{
    public function index(): void
    {
        $app = require dirname(__DIR__) . '/config/app.php';
        $dbError = null;
        $dashboard = null;
        try {
            $dashboard = (new ExecutiveDashboardService())->snapshot();
        } catch (Throwable $e) {
            $dbError = !empty($app['debug']) ? $e->getMessage() : 'ไม่สามารถโหลดข้อมูลแดชบอร์ดได้';
        }

        View::render('layouts/app', [
            'title' => 'Executive Dashboard',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'dashboard',
            'extraScripts' => ['/assets/js/dashboard.js'],
            'contentView' => 'home/index',
            'contentData' => [
                'dashboard' => $dashboard,
                'dbError' => $dbError,
            ],
        ]);
    }
}
