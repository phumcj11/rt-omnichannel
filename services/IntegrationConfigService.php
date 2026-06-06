<?php
/**
 * รวม config ช่องทาง — local.php/env + ค่าที่ user บันทึกใน UI (DB ชนะ)
 */
declare(strict_types=1);

namespace App\Services;

use App\Models\AppSetting;
use App\Models\FacebookPage;

final class IntegrationConfigService extends BaseService
{
    private const FB_DB_KEYS = [
        'page_access_token' => 'fb_page_access_token',
        'app_secret' => 'fb_app_secret',
        'verify_token' => 'fb_verify_token',
        'page_id' => 'fb_page_id',
        'graph_version' => 'fb_graph_version',
        'default_branch_id' => 'fb_default_branch_id',
    ];

    /** @return array<string, mixed> */
    public static function facebook(): array
    {
        $app = self::app();
        $cfg = is_array($app['facebook'] ?? null) ? $app['facebook'] : [];

        $db = AppSetting::getMany(array_values(self::FB_DB_KEYS));
        foreach (self::FB_DB_KEYS as $cfgKey => $dbKey) {
            $val = $db[$dbKey] ?? null;
            if ($val !== null && $val !== '') {
                $cfg[$cfgKey] = $cfgKey === 'default_branch_id' ? (int) $val : $val;
            }
        }

        if (empty($cfg['graph_version'])) {
            $cfg['graph_version'] = 'v21.0';
        }
        if (empty($cfg['default_branch_id'])) {
            $cfg['default_branch_id'] = 1;
        }

        return $cfg;
    }

    /**
     * @param array<string, string> $input
     */
    public static function saveFacebook(array $input): void
    {
        if (($input['page_id'] ?? '') !== '') {
            AppSetting::set('fb_page_id', trim($input['page_id']));
        }
        if (($input['verify_token'] ?? '') !== '') {
            AppSetting::set('fb_verify_token', trim($input['verify_token']));
        }
        if (($input['page_access_token'] ?? '') !== '') {
            AppSetting::set('fb_page_access_token', trim($input['page_access_token']));
        }
        if (($input['app_secret'] ?? '') !== '') {
            AppSetting::set('fb_app_secret', trim($input['app_secret']));
        }

        $cfg = self::facebook();
        $pageId = trim((string) ($cfg['page_id'] ?? ''));
        $token = trim((string) ($cfg['page_access_token'] ?? ''));
        if ($pageId !== '' && $token !== '') {
            FacebookPage::upsertPrimary($pageId, $token, '', (int) ($cfg['default_branch_id'] ?? 1));
        }
    }

    /**
     * @param array{page_id:string,page_access_token:string,page_name?:string,branch_id?:int} $input
     */
    public static function saveFacebookPage(array $input): bool
    {
        return FacebookPage::addSecondary($input);
    }

    public static function tokenForPageId(string $pageId): ?string
    {
        $pageId = trim($pageId);
        if ($pageId === '') {
            return null;
        }

        $fromTable = FacebookPage::tokenForPageId($pageId);
        if ($fromTable !== null) {
            return $fromTable;
        }

        $cfg = self::facebook();
        if (trim((string) ($cfg['page_id'] ?? '')) === $pageId) {
            $token = trim((string) ($cfg['page_access_token'] ?? ''));

            return $token !== '' ? $token : null;
        }

        return null;
    }

    /** @return list<array<string,mixed>> */
    public static function facebookPages(): array
    {
        try {
            return FacebookPage::allActive();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function syncLegacyFacebookPages(): void
    {
        $cfg = self::facebook();
        FacebookPage::syncLegacyPrimary(
            (string) ($cfg['page_id'] ?? ''),
            (string) ($cfg['page_access_token'] ?? ''),
            (int) ($cfg['default_branch_id'] ?? 1)
        );
    }

    public static function isLocalhostUrl(): bool
    {
        $url = self::webhookUrl();
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '');

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || str_ends_with($host, '.local');
    }

    public static function suggestVerifyToken(): string
    {
        return 'omni_' . bin2hex(random_bytes(8));
    }

    /**
     * @return array{ok: bool, page_name?: string, page_id?: string, error?: string}
     */
    public static function testFacebookConnection(?string $pageId = null): array
    {
        $cfg = self::facebook();
        $token = '';
        if ($pageId !== null && trim($pageId) !== '') {
            $token = (string) (self::tokenForPageId($pageId) ?? '');
        }
        if ($token === '') {
            $token = trim((string) ($cfg['page_access_token'] ?? ''));
        }
        if ($token === '') {
            return ['ok' => false, 'error' => 'ยังไม่ได้ใส่ Page Access Token'];
        }

        $version = (string) ($cfg['graph_version'] ?? 'v21.0');
        $url = 'https://graph.facebook.com/' . $version . '/me?fields=id,name&access_token=' . urlencode($token);
        $raw = @file_get_contents($url);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'เรียก Facebook API ไม่สำเร็จ — ตรวจอินเทอร์เน็ต'];
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'ได้ response ที่อ่านไม่ได้จาก Facebook'];
        }
        if (isset($data['error'])) {
            $msg = is_array($data['error']) ? (string) ($data['error']['message'] ?? 'Token ไม่ถูกต้อง') : 'Token ไม่ถูกต้อง';

            return ['ok' => false, 'error' => $msg];
        }

        return [
            'ok' => true,
            'page_id' => (string) ($data['id'] ?? ''),
            'page_name' => (string) ($data['name'] ?? ''),
        ];
    }

    public static function webhookUrl(): string
    {
        $app = self::app();
        $base = rtrim((string) ($app['url'] ?? ''), '/');
        if ($base === '') {
            $bp = rtrim((string) ($app['base_path'] ?? ''), '/');
            $base = $bp !== '' ? $bp : '';
        }

        return $base . '/webhooks/facebook.php';
    }
}
