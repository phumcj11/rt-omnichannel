<?php
/**
 * Settings — SLA, ช่องทาง, การตั้งค่าทั่วไป
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Redirect;
use App\Helpers\View;
use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Channel;
use App\Services\IntegrationConfigService;
use Throwable;

final class SettingsController
{
    /** @return array<string, mixed> */
    private function appConfig(): array
    {
        return require dirname(__DIR__) . '/config/app.php';
    }

    public function index(): void
    {
        $app = $this->appConfig();
        View::render('layouts/app', [
            'title' => 'Settings',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'settings',
            'contentView' => 'settings/index',
            'contentData' => [],
        ]);
    }

    public function channels(): void
    {
        $app = $this->appConfig();
        $channels = [];
        $fb = IntegrationConfigService::facebook();
        $webhookUrl = IntegrationConfigService::webhookUrl();
        $verifyInDb = null;
        $webhookLogs = [];
        try {
            $verifyInDb = AppSetting::get('fb_verify_token');
            $st = \App\Helpers\Db::pdo()->query(
                "SELECT id, signature_ok, error_message, created_at,
                        LEFT(raw_body, 100) AS body_preview
                 FROM webhook_logs
                 WHERE provider = 'facebook'
                 ORDER BY id DESC
                 LIMIT 5"
            );
            $webhookLogs = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Throwable) {
        }

        try {
            $channels = Channel::all();
            IntegrationConfigService::syncLegacyFacebookPages();
        } catch (Throwable) {
        }

        $fbPages = IntegrationConfigService::facebookPages();
        $branches = [];
        try {
            $branches = Branch::all();
        } catch (Throwable) {
        }

        $flash = match ($_GET['ok'] ?? '') {
            'saved' => 'บันทึกการตั้งค่า Facebook แล้ว',
            'page_added' => 'เพิ่ม Facebook Page แล้ว',
            default => null,
        };
        $flashError = match ($_GET['err'] ?? '') {
            'csrf' => 'เซสชันหมดอายุ กรุณาลองใหม่',
            'forbidden' => 'สิทธิ์ไม่เพียงพอ (ต้องเป็น Admin หรือ Manager)',
            'page_invalid' => 'กรอก Page ID และ Page Access Token ให้ครบ',
            default => null,
        };

        View::render('layouts/app', [
            'title' => 'Channel Settings',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'settings-channels',
            'extraScripts' => ['/assets/js/settings-channels.js'],
            'contentView' => 'settings/channels',
            'contentData' => [
                'channels' => $channels,
                'facebook' => $fb,
                'webhookUrl' => $webhookUrl,
                'canEdit' => Auth::canManageSettings(),
                'tokenMask' => AppSetting::maskSecret((string) ($fb['page_access_token'] ?? '')),
                'secretMask' => AppSetting::maskSecret((string) ($fb['app_secret'] ?? '')),
                'suggestedVerify' => IntegrationConfigService::suggestVerifyToken(),
                'flash' => $flash,
                'flashError' => $flashError,
                'fbPages' => $fbPages,
                'fbPageCount' => count($fbPages),
                'branches' => $branches,
                'isLocalhost' => IntegrationConfigService::isLocalhostUrl(),
                'activeVerifyToken' => trim((string) ($fb['verify_token'] ?? '')),
                'verifyTokenInDb' => $verifyInDb !== null && trim($verifyInDb) !== '',
                'webhookLogs' => $webhookLogs,
            ],
        ]);
    }

    public function saveFacebookPage(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/settings/channels');
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Redirect::to('/settings/channels?err=csrf');
        }
        if (!Auth::canManageSettings()) {
            Redirect::to('/settings/channels?err=forbidden');
        }

        $ok = IntegrationConfigService::saveFacebookPage([
            'page_id' => (string) ($_POST['extra_page_id'] ?? ''),
            'page_name' => (string) ($_POST['extra_page_name'] ?? ''),
            'page_access_token' => (string) ($_POST['extra_page_access_token'] ?? ''),
            'branch_id' => (int) ($_POST['extra_branch_id'] ?? 0),
        ]);

        Redirect::to($ok ? '/settings/channels?ok=page_added' : '/settings/channels?err=page_invalid');
    }

    public function saveFacebook(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/settings/channels');
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Redirect::to('/settings/channels?err=csrf');
        }
        if (!Auth::canManageSettings()) {
            Redirect::to('/settings/channels?err=forbidden');
        }

        $verify = trim((string) ($_POST['verify_token'] ?? ''));
        if ($verify === '') {
            $verify = IntegrationConfigService::suggestVerifyToken();
        }

        IntegrationConfigService::saveFacebook([
            'page_id' => (string) ($_POST['page_id'] ?? ''),
            'verify_token' => $verify,
            'page_access_token' => (string) ($_POST['page_access_token'] ?? ''),
            'app_secret' => (string) ($_POST['app_secret'] ?? ''),
            'app_id' => (string) ($_POST['app_id'] ?? ''),
        ]);

        Redirect::to('/settings/channels?ok=saved');
    }

    public function testFacebook(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!Auth::canManageSettings()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(IntegrationConfigService::testFacebookConnection(), JSON_UNESCAPED_UNICODE);
    }

    public function sla(): void
    {
        $app = $this->appConfig();
        $rules = [];
        $dbError = null;
        try {
            $pdo = \App\Helpers\Db::pdo();
            $st = $pdo->query(
                'SELECT id, name, rule_kind, channel_code, category, minutes, priority, is_active
                 FROM sla_rules ORDER BY priority ASC, id ASC'
            );
            $rules = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $dbError = !empty($app['debug']) ? $e->getMessage() : 'โหลดข้อมูลไม่สำเร็จ';
        }

        View::render('layouts/app', [
            'title' => 'SLA Settings',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'settings-sla',
            'contentView' => 'settings/sla',
            'contentData' => [
                'rules' => $rules,
                'dbError' => $dbError,
            ],
        ]);
    }
}
