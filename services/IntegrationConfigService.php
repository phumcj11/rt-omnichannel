<?php
/**
 * รวม config ช่องทาง — local.php/env + ค่าที่ user บันทึกใน UI (DB ชนะ)
 */
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Db;
use App\Helpers\HttpClient;
use App\Models\AppSetting;
use App\Models\FacebookPage;

final class IntegrationConfigService extends BaseService
{
    private const FB_DB_KEYS = [
        'page_access_token' => 'fb_page_access_token',
        'app_secret' => 'fb_app_secret',
        'app_id' => 'fb_app_id',
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

        foreach (['page_access_token', 'app_secret', 'verify_token', 'page_id', 'app_id'] as $k) {
            if (isset($cfg[$k]) && is_string($cfg[$k])) {
                $cfg[$k] = trim($cfg[$k]);
            }
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
            AppSetting::set('fb_app_secret', self::normalizeSecret((string) $input['app_secret']));
        }
        if (($input['app_id'] ?? '') !== '') {
            AppSetting::set('fb_app_id', trim($input['app_id']));
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
        $targetPageId = trim($pageId ?? '') !== '' ? trim($pageId ?? '') : trim((string) ($cfg['page_id'] ?? ''));
        $appId = trim((string) ($cfg['app_id'] ?? ''));
        $secret = trim((string) ($cfg['app_secret'] ?? ''));

        if ($appId !== '' && $secret !== '') {
            $debug = self::testViaDebugToken($token, $appId, $secret, $version, $targetPageId);
            if ($debug !== null) {
                return $debug;
            }
        }

        $me = self::graphGet('me?fields=id,name', $token, $version);
        if ($me['ok']) {
            return self::finalizePageTest($me, $targetPageId);
        }

        if ($targetPageId !== '') {
            $page = self::graphGet($targetPageId . '?fields=id,name', $token, $version);
            if ($page['ok']) {
                return self::finalizePageTest($page, $targetPageId);
            }

            return ['ok' => false, 'error' => self::friendlyFacebookError($page['error'] ?? $me['error'] ?? 'Token ไม่ถูกต้อง')];
        }

        return ['ok' => false, 'error' => self::friendlyFacebookError($me['error'] ?? 'Token ไม่ถูกต้อง')];
    }

    /**
     * @return array{ok: bool, page_name?: string, page_id?: string, error?: string}|null
     */
    private static function testViaDebugToken(
        string $token,
        string $appId,
        string $secret,
        string $version,
        string $targetPageId
    ): ?array {
        $url = 'https://graph.facebook.com/' . $version . '/debug_token?input_token='
            . urlencode($token) . '&access_token=' . urlencode($appId . '|' . $secret);
        $raw = HttpClient::get($url);
        if ($raw === null || $raw === '') {
            return null;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['error'])) {
            $msg = is_array($payload['error']) ? (string) ($payload['error']['message'] ?? '') : '';

            return ['ok' => false, 'error' => self::friendlyFacebookError($msg !== '' ? $msg : 'ตรวจ Token ไม่สำเร็จ')];
        }

        $info = $payload['data'] ?? null;
        if (!is_array($info) || empty($info['is_valid'])) {
            return [
                'ok' => false,
                'error' => 'Page Access Token ไม่ valid — สร้างใหม่ที่ Meta → Messenger → API Setup → Generate Token',
            ];
        }

        $type = strtoupper((string) ($info['type'] ?? ''));
        if ($type !== 'PAGE') {
            return [
                'ok' => false,
                'error' => 'Token นี้ไม่ใช่ Page Access Token (ได้ type: ' . strtolower($type) . ') — ต้อง Generate จาก Messenger → API Setup ของ Page นี้เท่านั้น',
            ];
        }

        $profileId = (string) ($info['profile_id'] ?? '');
        if ($targetPageId !== '' && $profileId !== '' && $profileId !== $targetPageId) {
            return [
                'ok' => false,
                'error' => 'Token เป็นของ Page ID ' . $profileId . ' ไม่ตรงกับที่ตั้งไว้ ' . $targetPageId,
            ];
        }

        $pageName = '';
        if ($profileId !== '') {
            $nameCheck = self::graphGet($profileId . '?fields=name', $token, $version);
            if ($nameCheck['ok']) {
                $pageName = (string) ($nameCheck['page_name'] ?? '');
            }
        }

        return [
            'ok' => true,
            'page_id' => $profileId !== '' ? $profileId : $targetPageId,
            'page_name' => $pageName !== '' ? $pageName : 'Facebook Page',
        ];
    }

    /**
     * @return array{ok: bool, page_id?: string, page_name?: string, error?: string}
     */
    private static function graphGet(string $path, string $token, string $version): array
    {
        $url = 'https://graph.facebook.com/' . $version . '/' . ltrim($path, '/');
        $url .= (str_contains($path, '?') ? '&' : '?') . 'access_token=' . urlencode($token);
        $raw = HttpClient::get($url);
        if ($raw === null || $raw === '') {
            $hint = HttpClient::lastError();
            $msg = 'เรียก Facebook API ไม่สำเร็จ';
            if ($hint !== '') {
                $msg .= ' — ' . $hint;
            }

            return ['ok' => false, 'error' => $msg];
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

    /**
     * @param array{ok: bool, page_id?: string, page_name?: string, error?: string} $result
     * @return array{ok: bool, page_name?: string, page_id?: string, error?: string}
     */
    private static function finalizePageTest(array $result, string $targetPageId): array
    {
        if (!$result['ok']) {
            return $result;
        }
        $gotId = (string) ($result['page_id'] ?? '');
        if ($targetPageId !== '' && $gotId !== '' && $gotId !== $targetPageId) {
            return [
                'ok' => false,
                'error' => 'Token เป็นของ Page ID ' . $gotId . ' ไม่ตรงกับที่ตั้งไว้ ' . $targetPageId,
            ];
        }

        return [
            'ok' => true,
            'page_id' => $gotId !== '' ? $gotId : $targetPageId,
            'page_name' => (string) ($result['page_name'] ?? ''),
        ];
    }

    private static function friendlyFacebookError(string $msg): string
    {
        if ($msg === '') {
            return 'Token ไม่ถูกต้อง';
        }
        if (str_contains($msg, 'pages_read_engagement') || str_contains($msg, '#100')) {
            return 'Token ไม่ใช่ Page Access Token ของ Messenger หรือสิทธิ์ไม่พอ — ไป Meta → Use cases → Messenger → Customize → API Setup → Generate Token ของ Page นี้ (อย่าใช้ Token จาก Marketing API / Graph Explorer แบบ User Token)';
        }

        return $msg;
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

    /**
     * รวม header จากหลายแหล่ง (shared hosting มักไม่ใส่ custom header ใน $_SERVER)
     *
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    public static function mergeWebhookServer(array $server): array
    {
        $merged = $server;

        $headerLists = [];
        if (function_exists('getallheaders')) {
            /** @var array<string, string>|false $headers */
            $headers = getallheaders();
            if (is_array($headers)) {
                $headerLists[] = $headers;
            }
        }
        if (function_exists('apache_request_headers')) {
            /** @var array<string, string>|false $headers */
            $headers = apache_request_headers();
            if (is_array($headers)) {
                $headerLists[] = $headers;
            }
        }

        foreach ($headerLists as $headers) {
            foreach ($headers as $name => $value) {
                $key = 'HTTP_' . strtoupper(str_replace('-', '_', (string) $name));
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                }
            }
        }

        foreach (['HTTP_X_HUB_SIGNATURE_256', 'HTTP_X_HUB_SIGNATURE'] as $key) {
            if (!empty($merged[$key])) {
                continue;
            }
            $redirectKey = 'REDIRECT_' . $key;
            if (!empty($merged[$redirectKey])) {
                $merged[$key] = $merged[$redirectKey];
            }
            $env = getenv($key);
            if ($env !== false && $env !== '') {
                $merged[$key] = $env;
            }
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function signatureHeaderFromServer(array $server, string $algo = '256'): string
    {
        $keys = $algo === '1'
            ? ['HTTP_X_HUB_SIGNATURE', 'REDIRECT_HTTP_X_HUB_SIGNATURE']
            : ['HTTP_X_HUB_SIGNATURE_256', 'REDIRECT_HTTP_X_HUB_SIGNATURE_256'];

        foreach ($keys as $key) {
            if (!empty($server[$key])) {
                return (string) $server[$key];
            }
        }

        $headerName = $algo === '1' ? 'x-hub-signature' : 'x-hub-signature-256';
        if (function_exists('getallheaders')) {
            /** @var array<string, string>|false $headers */
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string) $name) === $headerName) {
                        return (string) $value;
                    }
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function hasWebhookSignatureHeader(array $server): bool
    {
        $h256 = self::signatureHeaderFromServer($server, '256');
        if ($h256 !== '' && str_starts_with($h256, 'sha256=')) {
            return true;
        }
        $h1 = self::signatureHeaderFromServer($server, '1');

        return $h1 !== '' && str_starts_with($h1, 'sha1=');
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function verifyWebhookSignature(string $rawBody, array $server, string $secret): bool
    {
        $secret = trim($secret);
        if ($secret === '') {
            return false;
        }

        $header256 = self::signatureHeaderFromServer($server, '256');
        if ($header256 !== '' && str_starts_with($header256, 'sha256=')) {
            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

            return hash_equals($expected, $header256);
        }

        $header1 = self::signatureHeaderFromServer($server, '1');
        if ($header1 !== '' && str_starts_with($header1, 'sha1=')) {
            $expected = 'sha1=' . hash_hmac('sha1', $rawBody, $secret);

            return hash_equals($expected, $header1);
        }

        return false;
    }

    /**
     * ตรวจ webhook ล่าสุดกับ App Secret ที่บันทึกอยู่ตอนนี้ (แยก log เก่ากับค่าปัจจุบัน)
     *
     * @return array{
     *   has_log: bool,
     *   log_id?: int,
     *   created_at?: string,
     *   stored_signature_ok?: bool,
     *   has_signature_header?: bool,
     *   current_secret_ok?: bool,
     *   failure_reason?: string,
     *   log_error?: string|null
     * }
     */
    public static function analyzeLatestWebhookLog(): array
    {
        try {
            $st = Db::pdo()->query(
                "SELECT id, raw_body, headers_json, signature_ok, created_at, error_message
                 FROM webhook_logs
                 WHERE provider = 'facebook' AND raw_body <> ''
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $row = $st->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return ['has_log' => false];
        }

        if ($row === false) {
            return ['has_log' => false];
        }

        $secret = trim((string) (self::facebook()['app_secret'] ?? ''));
        /** @var array<string, mixed> $headers */
        $headers = json_decode((string) ($row['headers_json'] ?? '{}'), true);
        if (!is_array($headers)) {
            $headers = [];
        }
        $rawBody = (string) ($row['raw_body'] ?? '');

        $result = [
            'has_log' => true,
            'log_id' => (int) $row['id'],
            'created_at' => (string) ($row['created_at'] ?? ''),
            'stored_signature_ok' => !empty($row['signature_ok']),
            'has_signature_header' => false,
            'current_secret_ok' => false,
            'failure_reason' => '',
            'log_error' => $row['error_message'] ?? null,
        ];

        if (!empty($row['signature_ok'])) {
            $result['has_signature_header'] = true;
            $result['current_secret_ok'] = true;

            return $result;
        }

        $headers = self::mergeWebhookServer($headers);
        $result['has_signature_header'] = self::hasWebhookSignatureHeader($headers);

        if ($secret === '') {
            $result['failure_reason'] = 'no_secret';

            return $result;
        }
        if (!$result['has_signature_header']) {
            $result['failure_reason'] = 'missing_header';

            return $result;
        }

        $result['current_secret_ok'] = self::verifyWebhookSignature($rawBody, $headers, $secret);
        if (!$result['current_secret_ok']) {
            $result['failure_reason'] = 'signature_mismatch';
        }

        return $result;
    }

    /**
     * @return array{conversations: int, inbound_messages: int}
     */
    public static function facebookInboxStats(): array
    {
        try {
            $pdo = Db::pdo();
            $conv = (int) $pdo->query(
                "SELECT COUNT(*)
                 FROM conversations c
                 INNER JOIN channels ch ON ch.id = c.channel_id
                 WHERE ch.code = 'facebook_messenger'"
            )->fetchColumn();
            $msgs = (int) $pdo->query(
                "SELECT COUNT(*)
                 FROM messages m
                 INNER JOIN conversations c ON c.id = m.conversation_id
                 INNER JOIN channels ch ON ch.id = c.channel_id
                 WHERE ch.code = 'facebook_messenger' AND m.direction = 'inbound'"
            )->fetchColumn();

            return ['conversations' => $conv, 'inbound_messages' => $msgs];
        } catch (\Throwable) {
            return ['conversations' => 0, 'inbound_messages' => 0];
        }
    }

    private static function normalizeSecret(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            $value = substr($value, 3);
        }

        return trim($value);
    }

    /**
     * ตรวจว่า App ID + App Secret ตรงกับ Meta (ใช้ client_credentials)
     *
     * @return array{ok: bool, error?: string}
     */
    public static function verifyAppCredentials(): array
    {
        $cfg = self::facebook();
        $appId = trim((string) ($cfg['app_id'] ?? ''));
        $secret = trim((string) ($cfg['app_secret'] ?? ''));
        if ($appId === '') {
            return ['ok' => false, 'error' => 'ยังไม่ได้ใส่ App ID'];
        }
        if ($secret === '') {
            return ['ok' => false, 'error' => 'ยังไม่ได้ใส่ App Secret — Meta webhook จะ fail ทุกครั้ง'];
        }

        $version = (string) ($cfg['graph_version'] ?? 'v21.0');
        $url = 'https://graph.facebook.com/' . $version . '/oauth/access_token?'
            . 'client_id=' . urlencode($appId)
            . '&client_secret=' . urlencode($secret)
            . '&grant_type=client_credentials';
        $raw = HttpClient::get($url);
        if ($raw === null || $raw === '') {
            return ['ok' => false, 'error' => 'เรียก Meta ไม่สำเร็จ — ' . HttpClient::lastError()];
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'ได้ response ที่อ่านไม่ได้จาก Meta'];
        }
        if (isset($data['error'])) {
            $msg = is_array($data['error']) ? (string) ($data['error']['message'] ?? 'App ID/Secret ไม่ถูกต้อง') : 'App ID/Secret ไม่ถูกต้อง';

            return ['ok' => false, 'error' => $msg . ' — คัดลอก App ID + App Secret ใหม่จาก Meta → Basic'];
        }
        if (empty($data['access_token'])) {
            return ['ok' => false, 'error' => 'Meta ไม่คืน access_token — ตรวจ App ID/Secret'];
        }

        return ['ok' => true];
    }
}
