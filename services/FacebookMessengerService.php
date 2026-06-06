<?php
/**
 * Facebook Messenger — webhook รับข้อความ + ส่งตอบผ่าน Graph API
 */
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Db;
use App\Helpers\HttpClient;
use App\Helpers\WebhookTrace;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WebhookDedup;
use PDO;
use App\Services\IntegrationConfigService;
use Throwable;

final class FacebookMessengerService extends BaseService
{
    private const PROVIDER = 'facebook';
    private const CHANNEL_CODE = 'facebook_messenger';

    /** @var array<string, mixed> */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = IntegrationConfigService::facebook();
    }

    public function handleVerification(): never
    {
        $mode = (string) ($_GET['hub_mode'] ?? '');
        $token = (string) ($_GET['hub_verify_token'] ?? '');
        $challenge = (string) ($_GET['hub_challenge'] ?? '');

        $expected = (string) ($this->cfg['verify_token'] ?? '');
        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            header('Content-Type: text/plain; charset=UTF-8');
            echo $challenge;
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Forbidden';
        exit;
    }

    /**
     * @param array<string, mixed> $server
     */
    public function handleWebhook(string $rawBody, array $server): void
    {
        $channel = Channel::findByCode(self::CHANNEL_CODE);
        $channelId = $channel !== null ? (int) $channel['id'] : null;

        $sigOk = $this->validateSignature($rawBody, $server);
        $logId = $this->logWebhook($channelId, $rawBody, $server, $sigOk);

        if (!$sigOk) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Invalid signature';
            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($rawBody, true);
        $payload = $this->normalizeWebhookPayload(is_array($payload) ? $payload : null);
        if (!is_array($payload) || ($payload['object'] ?? '') !== 'page') {
            $this->noteWebhookProcessing($logId, 0, 'payload ไม่ใช่ object=page');
            http_response_code(200);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'EVENT_RECEIVED';
            return;
        }

        if ($channel === null || empty($channel['is_active'])) {
            $this->noteWebhookProcessing($logId, 0, 'ช่องทาง facebook_messenger ไม่ active');
            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        $entries = $payload['entry'] ?? [];
        if (!is_array($entries)) {
            $this->noteWebhookProcessing($logId, 0, 'ไม่มี entry ใน payload');
            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        $processed = 0;
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $pageId = (string) ($entry['id'] ?? '');
            if ($pageId === '' || $pageId === '0') {
                $pageId = trim((string) ($this->cfg['page_id'] ?? ''));
            }

            $messaging = $entry['messaging'] ?? [];
            if (is_array($messaging)) {
                foreach ($messaging as $event) {
                    if (!is_array($event)) {
                        continue;
                    }
                    try {
                        if ($this->processMessagingEvent($event, (int) $channel['id'], $pageId)) {
                            $processed++;
                        }
                    } catch (Throwable $e) {
                        $this->logError($channelId, $e->getMessage());
                    }
                }
            }

            $changes = $entry['changes'] ?? [];
            if (!is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                if (!is_array($change) || (string) ($change['field'] ?? '') !== 'messages') {
                    continue;
                }
                $value = $change['value'] ?? null;
                if (!is_array($value)) {
                    continue;
                }
                $event = $this->eventFromChangeValue($value);
                if ($event === null) {
                    continue;
                }
                try {
                    if ($this->processMessagingEvent($event, (int) $channel['id'], $pageId)) {
                        $processed++;
                    }
                } catch (Throwable $e) {
                    $this->logError($channelId, $e->getMessage());
                }
            }
        }

        $this->noteWebhookProcessing(
            $logId,
            $processed,
            $processed > 0 ? 'บันทึกข้อความเข้า Inbox แล้ว' : 'ได้รับ webhook แต่ไม่มีข้อความใหม่ (อาจเป็น delivery/read หรือ Subscribe ยังไม่ครบ)'
        );
        WebhookTrace::log(
            'handled sig=' . ($sigOk ? 'ok' : 'fail')
            . ' processed=' . $processed
            . ' log_id=' . (string) ($logId ?? 0)
        );

        http_response_code(200);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'EVENT_RECEIVED';
    }

    public function sendTextToPsid(string $psid, string $text, ?string $pageId = null): ?string
    {
        $token = $this->resolvePageToken($pageId);
        if ($token === '' || trim($text) === '') {
            return null;
        }

        $version = (string) ($this->cfg['graph_version'] ?? 'v21.0');
        $url = 'https://graph.facebook.com/' . $version . '/me/messages?access_token=' . urlencode($token);
        $body = json_encode([
            'recipient' => ['id' => $psid],
            'message' => ['text' => $text],
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->httpPostJson($url, $body);
        if ($response === null) {
            return null;
        }
        /** @var array<string, mixed>|null $data */
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }
        $mid = $data['message_id'] ?? null;

        return is_string($mid) ? $mid : null;
    }

    public function sendReplyForConversation(int $conversationId, string $text, ?int $localMessageId = null): bool
    {
        $conv = Conversation::findWithRelations($conversationId);
        if ($conv === null || ($conv['channel_code'] ?? '') !== self::CHANNEL_CODE) {
            return false;
        }

        $psid = Contact::externalIdForConversation($conversationId, (int) $conv['channel_id']);
        if ($psid === null) {
            return false;
        }

        $pageId = isset($conv['external_page_id']) ? (string) $conv['external_page_id'] : null;
        $mid = $this->sendTextToPsid($psid, $text, $pageId !== '' ? $pageId : null);
        if ($mid === null) {
            return false;
        }
        if ($localMessageId !== null && $localMessageId > 0) {
            Message::setExternalMessageId($localMessageId, $mid);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function processMessagingEvent(array $event, int $channelId, string $pageId = ''): bool
    {
        if (!empty($event['delivery']) || !empty($event['read']) || !empty($event['postback'])) {
            return false;
        }
        if (!empty($event['message']['is_echo'])) {
            return false;
        }

        $psid = (string) ($event['sender']['id'] ?? '');
        if ($psid === '') {
            return false;
        }

        $pageId = trim($pageId);
        if ($pageId !== '' && $pageId !== '0' && hash_equals($pageId, $psid)) {
            return false;
        }

        $message = $event['message'] ?? null;
        if (is_string($message)) {
            $message = [
                'mid' => 'fb_' . hash('sha256', 'str|' . $message . '|' . $psid),
                'text' => $message,
            ];
        }
        if (!is_array($message)) {
            return false;
        }
        if (!empty($message['is_deleted'])) {
            return false;
        }

        $mid = (string) ($message['mid'] ?? '');
        if ($mid === '') {
            $mid = 'fb_' . hash('sha256', $psid . '|' . ($message['text'] ?? '') . '|' . (string) ($event['timestamp'] ?? ''));
        }
        if (WebhookDedup::isDuplicate(self::PROVIDER, $mid)) {
            return false;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $messageType = 'text';
        if ($text === '') {
            if (isset($message['attachments']) && is_array($message['attachments'])) {
                $text = '[ไฟล์แนบจาก Facebook — ยังไม่รองรับแสดงผลเต็มรูปแบบ]';
                $messageType = 'file';
            } elseif (isset($message['sticker_id'])) {
                $text = '[สติกเกอร์ Facebook]';
                $messageType = 'text';
            } else {
                return false;
            }
        }

        $profile = $this->fetchUserProfile($psid, $pageId);
        $displayName = $this->displayNameFromProfile($profile, $psid);

        $branchId = $this->branchIdForPage($pageId);
        $contact = Contact::findOrCreateByExternalId(
            $channelId,
            $psid,
            $displayName,
            $profile,
            $branchId
        );

        $slaDue = SlaService::dueAtForChannel(self::CHANNEL_CODE);
        $convId = Conversation::findOrCreateForInbound(
            $contact['contact_id'],
            $channelId,
            $branchId,
            $slaDue,
            $pageId !== '' ? $pageId : null
        );

        $msgId = Message::insertInbound(
            $convId,
            $text,
            $mid,
            $messageType,
            ['facebook' => ['mid' => $mid, 'page_id' => $pageId, 'raw' => $message]]
        );

        return $msgId !== null;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>|null
     */
    private function eventFromChangeValue(array $value): ?array
    {
        if (isset($value['delivery']) || isset($value['read']) || isset($value['postback'])) {
            return null;
        }

        $sender = $value['sender'] ?? $value['from'] ?? null;
        if (!is_array($sender)) {
            return null;
        }

        $recipient = $value['recipient'] ?? $value['to'] ?? [];
        if (is_array($recipient) && isset($recipient['data'][0]['id'])) {
            $recipient = ['id' => (string) $recipient['data'][0]['id']];
        } elseif (!is_array($recipient)) {
            $recipient = [];
        }

        $message = $value['message'] ?? null;
        if (is_string($message)) {
            $message = [
                'mid' => 'fb_' . hash('sha256', 'chg|' . $message . '|' . (string) ($sender['id'] ?? '')),
                'text' => $message,
            ];
        } elseif (!is_array($message)) {
            return null;
        }

        return [
            'sender' => $sender,
            'recipient' => $recipient,
            'timestamp' => $value['timestamp'] ?? (int) round(microtime(true) * 1000),
            'message' => $message,
        ];
    }

    private function resolvePageToken(?string $pageId): string
    {
        if ($pageId !== null && trim($pageId) !== '') {
            $token = IntegrationConfigService::tokenForPageId(trim($pageId));
            if ($token !== null) {
                return $token;
            }
        }

        return trim((string) ($this->cfg['page_access_token'] ?? ''));
    }

    private function branchIdForPage(string $pageId): int
    {
        $default = (int) ($this->cfg['default_branch_id'] ?? 1);
        if ($pageId === '') {
            return $default;
        }
        $row = \App\Models\FacebookPage::findByPageId($pageId);
        if ($row !== null && !empty($row['branch_id'])) {
            return (int) $row['branch_id'];
        }

        return $default;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchUserProfile(string $psid, string $pageId = ''): ?array
    {
        $token = $this->resolvePageToken($pageId !== '' ? $pageId : null);
        if ($token === '') {
            return null;
        }

        $version = (string) ($this->cfg['graph_version'] ?? 'v21.0');
        $url = 'https://graph.facebook.com/' . $version . '/'
            . urlencode($psid)
            . '?fields=first_name,last_name,profile_pic&access_token='
            . urlencode($token);

        $raw = HttpClient::get($url);
        if ($raw === null || $raw === '') {
            return null;
        }
        /** @var array<string, mixed>|null $data */
        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed>|null $profile
     */
    private function displayNameFromProfile(?array $profile, string $psid): string
    {
        if ($profile === null) {
            return 'FB ' . substr($psid, -6);
        }
        $first = trim((string) ($profile['first_name'] ?? ''));
        $last = trim((string) ($profile['last_name'] ?? ''));
        $name = trim($first . ' ' . $last);

        return $name !== '' ? $name : ('FB ' . substr($psid, -6));
    }

    /**
     * @param array<string, mixed> $server
     */
    private function signatureFailureReason(array $server): string
    {
        $secret = trim((string) ($this->cfg['app_secret'] ?? ''));
        if ($secret === '') {
            return 'ไม่มี App Secret — webhook ถูกปฏิเสธ (403)';
        }
        if (!IntegrationConfigService::hasWebhookSignatureHeader($server)) {
            if (IntegrationConfigService::webhookTrustUnsigned()) {
                return 'รับ webhook แบบ trust_unsigned (โฮสต์ block header — เปิดใน Channel Settings)';
            }

            return 'ไม่พบ X-Hub-Signature header — โฮสต์อาจ block header นี้ (ModSecurity/proxy)';
        }

        return 'App Secret ไม่ตรงกับ Meta — คัดลอกใหม่จาก App settings → Basic แล้วบันทึก';
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    private function normalizeWebhookPayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }
        if (($payload['object'] ?? '') === 'page') {
            return $payload;
        }
        if (($payload['field'] ?? '') === 'messages' && is_array($payload['value'] ?? null)) {
            $pageId = trim((string) ($this->cfg['page_id'] ?? ''));

            return [
                'object' => 'page',
                'entry' => [[
                    'id' => $pageId !== '' ? $pageId : '0',
                    'time' => (int) round(microtime(true) * 1000),
                    'changes' => [[
                        'field' => 'messages',
                        'value' => $payload['value'],
                    ]],
                ]],
            ];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $server
     */
    private function validateSignature(string $rawBody, array $server): bool
    {
        $secret = trim((string) ($this->cfg['app_secret'] ?? ''));
        if ($secret === '') {
            return !empty(self::app()['debug']);
        }

        if (IntegrationConfigService::hasWebhookSignatureHeader($server)) {
            return IntegrationConfigService::verifyWebhookSignature($rawBody, $server, $secret);
        }

        if (IntegrationConfigService::webhookTrustUnsigned()) {
            WebhookTrace::log('WARN trust_unsigned=1 (hosting blocks signature header)');

            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $server
     */
    private function logWebhook(?int $channelId, string $rawBody, array $server, bool $sigOk): ?int
    {
        try {
            $pdo = Db::pdo();
            $headers = [];
            foreach ($server as $k => $v) {
                if (str_starts_with((string) $k, 'HTTP_')) {
                    $headers[$k] = $v;
                }
            }
            $failReason = $sigOk ? null : $this->signatureFailureReason($server);
            $st = $pdo->prepare(
                'INSERT INTO webhook_logs (channel_id, provider, raw_body, headers_json, signature_ok, error_message, created_at)
                 VALUES (:ch, :p, :body, :hdr, :sig, :err, NOW())'
            );
            $st->execute([
                'ch' => $channelId,
                'p' => self::PROVIDER,
                'body' => mb_substr($rawBody, 0, 65000),
                'hdr' => json_encode($headers, JSON_UNESCAPED_UNICODE),
                'sig' => $sigOk ? 1 : 0,
                'err' => $failReason !== null ? mb_substr($failReason, 0, 500) : null,
            ]);

            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            WebhookTrace::log('DB logWebhook FAIL: ' . $e->getMessage());

            return null;
        }
    }

    private function noteWebhookProcessing(?int $logId, int $processedCount, string $note): void
    {
        if ($logId === null || $logId <= 0) {
            return;
        }
        try {
            $pdo = Db::pdo();
            $st = $pdo->prepare(
                'UPDATE webhook_logs
                 SET error_message = :msg, processed_at = NOW(), http_status = 200
                 WHERE id = :id'
            );
            $st->execute([
                'msg' => mb_substr($processedCount . ' msg — ' . $note, 0, 500),
                'id' => $logId,
            ]);
        } catch (Throwable) {
        }
    }

    private function logError(?int $channelId, string $msg): void
    {
        try {
            $pdo = Db::pdo();
            $st = $pdo->prepare(
                'INSERT INTO webhook_logs (channel_id, provider, raw_body, error_message, created_at)
                 VALUES (:ch, :p, :body, :err, NOW())'
            );
            $st->execute([
                'ch' => $channelId,
                'p' => self::PROVIDER,
                'body' => '',
                'err' => mb_substr($msg, 0, 500),
            ]);
        } catch (Throwable) {
        }
    }

    private function httpPostJson(string $url, string $jsonBody): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);

            return is_string($resp) ? $resp : null;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $jsonBody,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);

        return is_string($resp) ? $resp : null;
    }
}
